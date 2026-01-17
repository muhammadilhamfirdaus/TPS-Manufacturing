<?php

namespace App\Http\Controllers;

use App\Models\ProductionPlan;
use App\Models\ProductionLine;
use App\Models\Product;
use App\Models\ProductionPlanDetail;
use App\Models\ActivityLog;
use App\Services\ManufacturingCalculatorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\LoadingReportExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProductionPlanExport;
use App\Imports\ProductionPlanImport;
use Carbon\Carbon;

class ProductionPlanController extends Controller
{
    protected $calculator;

    public function __construct(ManufacturingCalculatorService $calculator)
    {
        $this->middleware('auth');
        $this->calculator = $calculator;
    }

    // 1. DASHBOARD INDEX
    public function index()
    {
        $plans = ProductionPlan::with(['productionLine', 'details.product'])
            ->orderBy('plan_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('plans.index', compact('plans'));
    }

    // ==================================================================
    // STORE DENGAN LOGIKA BOM EXPLOSION (REKURSIF)
    // ==================================================================
    
    // 2. FORM CREATE
    public function create()
    {
        $products = Product::orderBy('part_name', 'asc')->get();
        return view('plans.create', compact('products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'plan_month' => 'required',
            'product_id' => 'required|exists:products,id',
            'qty_plan'   => 'required|numeric|min:1',
        ]);

        DB::beginTransaction();
        try {
            // Panggil fungsi helper recursive untuk memproses plan induk & turunannya
            $this->processPlanRecursive(
                $request->product_id, 
                $request->qty_plan, 
                $request->plan_month
            );

            DB::commit();
            
            return redirect()->route('plans.index')->with('success', 
                "Sukses! Plan beserta turunan BOM-nya berhasil digenerate."
            );

        } catch (\Exception $e) {
            DB::rollBack();
            // Debugging: Uncomment baris di bawah ini jika ingin melihat detail error di layar
            // dd($e->getMessage(), $e->getFile(), $e->getLine()); 
            return back()->withErrors('Gagal menyimpan: ' . $e->getMessage());
        }
    }

    /**
     * FUNGSI PINTAR (REKURSIF):
     * Menyimpan Plan Produk ini, lalu cek apakah punya komponen?
     * Jika punya, panggil fungsi ini lagi untuk komponennya.
     */
    private function processPlanRecursive($productId, $qty, $monthStr)
    {
        // 1. Setup Data Dasar (Tanggal 1 bulan tersebut)
        $planDate = Carbon::parse($monthStr)->startOfMonth()->format('Y-m-d');
        
        // Load Produk beserta Routing & BOM Components (Anak-anaknya)
        $product = Product::with(['routings.machine.productionLine', 'bomComponents'])->find($productId);
        
        if (!$product) return;

        // 2. Tentukan Line Produksi
        $lineId = null;
        if ($product->routings->isNotEmpty()) {
            $lineId = $product->routings->first()->machine->production_line_id ?? null;
        }
        
        // Fallback: Jika tidak ada routing, ambil Line pertama yang ada di DB
        if (!$lineId) {
            $firstLine = ProductionLine::first();
            if ($firstLine) {
                $lineId = $firstLine->id;
            } else {
                 // Jika tabel line kosong, throw error agar user sadar
                throw new \Exception("Master Data Production Line kosong. Harap isi data Line dahulu.");
            }
        }

        // 3. Simpan Header Plan
        // Menggunakan 'DRAFT' karena 'PENDING'/'MANUAL' mungkin tidak ada di ENUM database Anda
        $plan = ProductionPlan::firstOrCreate(
            [
                'plan_date' => $planDate, 
                'production_line_id' => $lineId, 
                'shift_id' => 1
            ],
            [
                'status' => 'DRAFT', // Pastikan status ini valid di database Anda
                'created_by' => auth()->id()
            ]
        );

        // 4. Hitung & Simpan Detail
        $shiftDuration = 480; 
        $effectiveTime = 440;
        
        $loadingPct = $this->calculator->calculateMachineLoading($qty, $product->cycle_time, $shiftDuration);
        $manpower = $this->calculator->calculateManPower($qty, $product->cycle_time, $effectiveTime);
        $kanbanNeeded = $this->calculator->calculateKanbanCards($qty, 0.5, $product->qty_per_box, $product->safety_stock);

        ProductionPlanDetail::updateOrCreate(
            [
                'production_plan_id' => $plan->id,
                'product_id' => $productId
            ],
            [
                'qty_plan' => $qty,
                'calculated_loading_pct' => $loadingPct,
                'calculated_manpower' => $manpower,
                'calculated_kanban_cards' => $kanbanNeeded
            ]
        );

        // ==========================================================
        // CEK BOM (ANAK)
        // ==========================================================
        if ($product->bomComponents->isNotEmpty()) {
            foreach ($product->bomComponents as $child) {
                // Hitung kebutuhan anak: Qty Induk * Usage per Unit
                $childQty = $qty * $child->pivot->quantity;

                // REKURSIF: Panggil fungsi ini sendiri untuk si Anak
                $this->processPlanRecursive($child->id, $childQty, $monthStr);
            }
        }
    }

    // ==================================================================
    // FITUR PENDUKUNG (Summary, Delete, Export, Import, Report)
    // ==================================================================

    public function destroy($id)
    {
        $plan = ProductionPlan::findOrFail($id);
        $plan->delete();
        return back()->with('success', 'Plan berhasil dihapus.');
    }

    public function summary(Request $request)
    {
        $month = $request->get('month', date('m'));
        $year = $request->get('year', date('Y'));

        $summaries = ProductionPlanDetail::whereHas('productionPlan', function ($q) use ($month, $year) {
            $q->whereMonth('plan_date', $month)->whereYear('plan_date', $year);
        })->with('product')
          ->select('product_id', DB::raw('SUM(qty_plan) as total_qty'), DB::raw('COUNT(id) as freq'))
          ->groupBy('product_id')
          ->get();

        $summaries->transform(function ($item) {
            $ct = $item->product->cycle_time ?? 0;
            $item->total_hours = $ct > 0 ? ($item->total_qty * $ct) / 3600 : 0;
            return $item;
        });

        return view('plans.summary', compact('summaries', 'month', 'year'));
    }

    // --- Loading Report Logic ---
   // --- Loading Report Logic (REVISI: Tambah code_part) ---
    private function getLoadingReportData(Request $request)
    {
        $allLines = ProductionLine::all();
        $selectedLineId = $request->get('line_id', $allLines->first()->id ?? 0);
        $line = ProductionLine::with('machines')->find($selectedLineId);
        $month = $request->get('month', date('m'));
        $year = $request->get('year', date('Y'));

        if (!$line) return null;

        $groupedMachines = $line->machines->sortBy('name')->groupBy('machine_group');
        $details = ProductionPlanDetail::whereHas('productionPlan', function ($q) use ($line, $month, $year) {
            $q->where('production_line_id', $line->id)
                ->whereMonth('plan_date', $month)
                ->whereYear('plan_date', $year);
        })->with(['product.routings', 'productionPlan'])->get();

        $reportData = collect();
        $machineTotals = [];
        $grandTotalLoad = 0;

        foreach ($details as $detail) {
            if (!$detail->product) continue;
            
            // Filter routing yang sesuai dengan Line yang dipilih
            $relevantRoutings = $detail->product->routings->filter(function ($route) use ($line) {
                return $line->machines->contains('id', $route->machine_id);
            });
            
            if ($relevantRoutings->isEmpty()) continue;

            foreach ($relevantRoutings as $routing) {
                $pcsPerHour = $routing->pcs_per_hour;
                $hours = $pcsPerHour > 0 ? ($detail->qty_plan / $pcsPerHour) : 0;
                $displayCT = $pcsPerHour > 0 ? (3600 / $pcsPerHour) : 0;
                $machineObj = $line->machines->find($routing->machine_id);
                $isSubcont = ($machineObj && $machineObj->type === 'SUBCONT');

                if (!isset($machineTotals[$routing->machine_id])) { $machineTotals[$routing->machine_id] = 0; }
                $machineTotals[$routing->machine_id] += $hours;
                if (!$isSubcont) { $grandTotalLoad += $hours; }

                // --- PERBAIKAN DISINI: Menambahkan 'code_part' ---
                $reportData->push((object) [
                    'part_number' => $detail->product->part_number,
                    'part_name'   => $detail->product->part_name,
                    'code_part'   => $detail->product->code_part ?? '-', // <--- INI YANG TADINYA HILANG
                    'qty_plan'    => $detail->qty_plan,
                    'process_name'=> $routing->process_name,
                    'cycle_time'  => $displayCT,
                    'pcs_per_hour'=> $pcsPerHour,
                    'machine_id'  => $routing->machine_id,
                    'load_hours'  => $hours,
                ]);
            }
        }
        
        return [
            'line' => $line,
            'groupedMachines' => $groupedMachines,
            'reportData' => $reportData->sortBy('part_number'),
            'allLines' => $allLines,
            'period' => date('F Y', mktime(0, 0, 0, $month, 1, $year)),
            'month' => $month,
            'year' => $year,
            'machineTotals' => $machineTotals,
            'grandTotalLoad' => $grandTotalLoad
        ];
    }

    public function loadingReport(Request $request) {
        $data = $this->getLoadingReportData($request);
        if (!$data) return redirect()->route('plans.index')->withErrors('Line tidak ditemukan.');
        return view('plans.loading_report', $data);
    }

    public function downloadLoadingPdf(Request $request) {
        $data = $this->getLoadingReportData($request);
        if (!$data) return back()->withErrors('Data tidak ditemukan.');
        $pdf = Pdf::loadView('plans.loading_report_pdf', $data);
        return $pdf->setPaper('a4', 'landscape')->download('Loading_Report.pdf');
    }

    public function downloadLoadingExcel(Request $request) {
        $data = $this->getLoadingReportData($request);
        if (!$data) return back()->withErrors('Data tidak ditemukan.');
        return Excel::download(new LoadingReportExport($data), 'Loading_Report.xlsx');
    }

    public function export(Request $request) {
        $type = $request->query('type', 'empty');
        return Excel::download(new ProductionPlanExport($type), 'Template_Plan.xlsx');
    }

    public function import(Request $request) {
        $request->validate(['file' => 'required|mimes:xlsx,xls']);
        try {
            Excel::import(new ProductionPlanImport, $request->file('file'));
            return back()->with('success', 'Import Excel Berhasil!');
        } catch (\Exception $e) {
            return back()->withErrors('Gagal Import: ' . $e->getMessage());
        }
    }
}