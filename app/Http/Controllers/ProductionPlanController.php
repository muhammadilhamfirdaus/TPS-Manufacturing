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
            ->paginate(10);

        return view('plans.index', compact('plans'));
    }

    // 2. FORM CREATE (REVISI: Hapus variable $lines karena tidak dipakai lagi)
    public function create()
    {
        // Kita hanya butuh data produk, Line akan dideteksi otomatis
        $products = Product::orderBy('part_name', 'asc')->get();

        return view('plans.create', compact('products'));
    }

    // 3. STORE (REVISI TOTAL: LOGIC AUTO DETECT LINE)
    public function store(Request $request)
    {
        $request->validate([
            'plan_date' => 'required|date',
            'product_id' => 'required|exists:products,id', // Line ID dihapus dari validasi
            'qty_plan' => 'required|numeric|min:1',
        ]);

        DB::beginTransaction();
        try {
            // 1. Ambil Data Produk beserta Routing, Mesin, dan Line-nya
            $product = Product::with(['routings.machine.productionLine'])->findOrFail($request->product_id);

            // 2. Cek apakah produk punya routing?
            if ($product->routings->isEmpty()) {
                return back()->withErrors("Part {$product->part_number} belum memiliki Routing! Silakan setting Master Data terlebih dahulu.");
            }

            // 3. Cari Semua Line Unik yang terlibat dalam Routing Part ini
            // Logic: Product -> Routings -> Machine -> Line
            $uniqueLines = $product->routings->pluck('machine.productionLine')->filter()->unique('id');

            if ($uniqueLines->isEmpty()) {
                return back()->withErrors("Routing ditemukan, tetapi Mesin belum dilingkarkan ke Line Produksi manapun.");
            }

            // Default parameter (bisa diambil dari DB setting)
            $shiftDuration = 480;
            $effectiveTime = 440;

            // 4. LOOPING: Buat Plan untuk SETIAP LINE yang terlibat
            foreach ($uniqueLines as $line) {

                // Kalkulasi Logic TPS
                $loadingPct = $this->calculator->calculateMachineLoading(
                    $request->qty_plan,
                    $product->cycle_time,
                    $shiftDuration
                );

                $manpower = $this->calculator->calculateManPower(
                    $request->qty_plan,
                    $product->cycle_time,
                    $effectiveTime
                );

                $kanbanNeeded = $this->calculator->calculateKanbanCards(
                    $request->qty_plan,
                    0.5,
                    $product->qty_per_box,
                    $product->safety_stock
                );

                // Create Header Plan (Line ID diambil dari Loop, bukan Request)
                $plan = ProductionPlan::create([
                    'plan_date' => $request->plan_date,
                    'production_line_id' => $line->id,
                    'shift_id' => 1,
                    'status' => 'DRAFT',
                    'created_by' => auth()->id(),
                ]);

                // Create Detail Plan
                $plan->details()->create([
                    'product_id' => $product->id,
                    'qty_plan' => $request->qty_plan,
                    'calculated_loading_pct' => $loadingPct,
                    'calculated_manpower' => $manpower,
                    'calculated_kanban_cards' => $kanbanNeeded,
                ]);

                // Catat Log
                ActivityLog::create([
                    'user_name' => auth()->user()->name ?? 'System',
                    'action' => 'AUTO PLAN',
                    'description' => "Auto-Plan: {$product->part_name} (Qty: {$request->qty_plan}) di {$line->name}"
                ]);
            }

            DB::commit();

            return redirect()->route('plans.index')->with('success', "Plan Berhasil Disimpan! Terdeteksi " . $uniqueLines->count() . " Line terkait.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Gagal menyimpan: ' . $e->getMessage());
        }
    }

    // 4. DOWNLOAD TEMPLATE EXCEL
    public function export()
    {
        $fileName = 'Form_Input_Planning_' . date('Y-m-d') . '.xlsx';
        return Excel::download(new ProductionPlanExport, $fileName);
    }

    // 5. IMPORT EXCEL
    public function import(Request $request)
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls']);

        try {
            Excel::import(new ProductionPlanImport, $request->file('file'));

            ActivityLog::create([
                'user_name' => auth()->user()->name ?? 'System',
                'action' => 'IMPORT EXCEL',
                'description' => "Upload file planning: " . $request->file('file')->getClientOriginalName()
            ]);

            return back()->with('success', 'Import Excel Berhasil!');
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            return back()->withErrors('Gagal Import Baris ' . $failures[0]->row() . ': ' . $failures[0]->errors()[0]);
        } catch (\Exception $e) {
            return back()->withErrors('Gagal Import: ' . $e->getMessage());
        }
    }

    // 6. DELETE PLAN
    public function destroy($id)
    {
        $plan = ProductionPlan::findOrFail($id);
        $info = "Plan ID: $id Tanggal: " . $plan->plan_date;
        $plan->delete();

        ActivityLog::create([
            'user_name' => auth()->user()->name ?? 'System',
            'action' => 'DELETE PLAN',
            'description' => "Menghapus $info"
        ]);

        return back()->with('success', 'Plan berhasil dihapus.');
    }

    // ==================================================================
    // FITUR LOADING REPORT (WEB, PDF, EXCEL)
    // ==================================================================

    // PRIVATE: Pusat Logika Perhitungan Loading
    // PRIVATE: Pusat Logika Perhitungan Loading
    private function getLoadingReportData(Request $request)
    {
        $allLines = ProductionLine::all();
        $selectedLineId = $request->get('line_id', $allLines->first()->id ?? 0);
        $line = ProductionLine::with('machines')->find($selectedLineId);

        $month = $request->get('month', date('m'));
        $year = $request->get('year', date('Y'));

        if (!$line)
            return null;

        $groupedMachines = $line->machines->sortBy('name')->groupBy('machine_group');

        $details = ProductionPlanDetail::whereHas('productionPlan', function ($q) use ($line, $month, $year) {
            $q->where('production_line_id', $line->id)
                ->whereMonth('plan_date', $month)
                ->whereYear('plan_date', $year);
        })->with(['product.routings', 'productionPlan'])->get();

        $reportData = collect();

        // Variabel Penampung Total
        $machineTotals = [];
        $grandTotalLoad = 0;

        foreach ($details as $detail) {
            if (!$detail->product)
                continue;

            $relevantRoutings = $detail->product->routings->filter(function ($route) use ($line) {
                return $line->machines->contains('id', $route->machine_id);
            });

            if ($relevantRoutings->isEmpty())
                continue;

            foreach ($relevantRoutings as $routing) {
                $pcsPerHour = $routing->pcs_per_hour;
                $hours = $pcsPerHour > 0 ? ($detail->qty_plan / $pcsPerHour) : 0;
                $displayCT = $pcsPerHour > 0 ? (3600 / $pcsPerHour) : 0;
                $codePart = $detail->product->code_part ?? ('CP-' . str_pad($detail->product->id, 3, '0', STR_PAD_LEFT));

                // --- LOGIC PENJUMLAHAN TOTAL ---
                // 1. Sum per Mesin
                if (!isset($machineTotals[$routing->machine_id])) {
                    $machineTotals[$routing->machine_id] = 0;
                }
                $machineTotals[$routing->machine_id] += $hours;

                // 2. Sum Grand Total
                $grandTotalLoad += $hours;

                $reportData->push((object) [
                    'part_number' => $detail->product->part_number,
                    'part_name' => $detail->product->part_name,
                    'code_part' => $codePart,
                    'qty_plan' => $detail->qty_plan,
                    'process_name' => $routing->process_name,
                    'cycle_time' => $displayCT,
                    'pcs_per_hour' => $pcsPerHour,
                    'machine_id' => $routing->machine_id,
                    'load_hours' => $hours,
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
            // Passing data total ke View
            'machineTotals' => $machineTotals,
            'grandTotalLoad' => $grandTotalLoad
        ];
    }

    // A. TAMPILAN WEB
    public function loadingReport(Request $request)
    {
        $data = $this->getLoadingReportData($request);
        if (!$data)
            return redirect()->route('plans.index')->withErrors('Line tidak ditemukan.');

        return view('plans.loading_report', $data);
    }

    // B. DOWNLOAD PDF
    public function downloadLoadingPdf(Request $request)
    {
        $data = $this->getLoadingReportData($request);
        if (!$data)
            return back()->withErrors('Data tidak ditemukan.');

        // Render View khusus PDF
        $pdf = Pdf::loadView('plans.loading_report_pdf', $data);

        // Set Kertas Landscape agar tabel muat
        return $pdf->setPaper('a4', 'landscape')
            ->download('Loading_Report_' . $data['line']->name . '.pdf');
    }

    // C. DOWNLOAD EXCEL
    public function downloadLoadingExcel(Request $request)
    {
        $data = $this->getLoadingReportData($request);
        if (!$data)
            return back()->withErrors('Data tidak ditemukan.');

        // Gunakan Class Export yang sudah dibuat
        return Excel::download(new LoadingReportExport($data), 'Loading_Report_' . $data['line']->name . '.xlsx');
    }
}