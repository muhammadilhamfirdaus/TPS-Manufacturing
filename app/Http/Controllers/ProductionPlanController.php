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
use Barryvdh\DomPDF\Facade\Pdf; // Library PDF
use App\Exports\LoadingReportExport; // Export Class Excel
use Maatwebsite\Excel\Facades\Excel; // Library Excel
use App\Exports\ProductionPlanExport; // Template Input
use App\Imports\ProductionPlanImport; // Import Logic

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

    // 2. FORM CREATE MANUAL
    public function create()
    {
        $lines = ProductionLine::all();
        $products = Product::orderBy('part_name', 'asc')->get();

        return view('plans.create', compact('lines', 'products'));
    }

    // 3. STORE (SIMPAN MANUAL + LOGGING)
    public function store(Request $request)
    {
        $request->validate([
            'plan_date' => 'required|date',
            'production_line_id' => 'required|exists:production_lines,id',
            'product_id' => 'required|exists:products,id',
            'qty_plan' => 'required|numeric|min:1',
        ]);

        DB::beginTransaction();
        try {
            $product = Product::find($request->product_id);
            $line = ProductionLine::find($request->production_line_id);

            // Default parameter (bisa diambil dari DB jika ada settingan global)
            $shiftDuration = 480; 
            $effectiveTime = 440;

            // --- KALKULASI LOGIC TPS ---
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

            // --- SIMPAN KE DATABASE ---
            $plan = ProductionPlan::create([
                'plan_date' => $request->plan_date,
                'production_line_id' => $line->id,
                'shift_id' => 1,
                'status' => 'DRAFT',
                'created_by' => auth()->id(),
            ]);

            $plan->details()->create([
                'product_id' => $product->id,
                'qty_plan' => $request->qty_plan,
                'calculated_loading_pct' => $loadingPct,
                'calculated_manpower' => $manpower,
                'calculated_kanban_cards' => $kanbanNeeded,
            ]);

            // --- CATAT LOG ---
            ActivityLog::create([
                'user_name' => auth()->user()->name ?? 'System',
                'action'    => 'CREATE PLAN',
                'description' => "Input Manual: {$product->part_name} (Qty: {$request->qty_plan}) di Line {$line->name}"
            ]);

            DB::commit();

            return redirect()->route('plans.index')->with('success', "Plan Berhasil Disimpan!");

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
                'action'    => 'IMPORT EXCEL',
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
            'action'    => 'DELETE PLAN',
            'description' => "Menghapus $info"
        ]);

        return back()->with('success', 'Plan berhasil dihapus.');
    }

    // ==================================================================
    // FITUR LOADING REPORT (WEB, PDF, EXCEL)
    // ==================================================================

    // PRIVATE: Pusat Logika Perhitungan Loading
    private function getLoadingReportData(Request $request)
    {
        $allLines = ProductionLine::all();
        $selectedLineId = $request->get('line_id', $allLines->first()->id ?? 0);
        $line = ProductionLine::with('machines')->find($selectedLineId);

        if (!$line) return null;

        $groupedMachines = $line->machines->sortBy('name')->groupBy('machine_group');

        // Filter Plan Berdasarkan Bulan & Tahun Saat Ini
        $details = ProductionPlanDetail::whereHas('productionPlan', function ($q) use ($line) {
            $q->where('production_line_id', $line->id)
                ->whereMonth('plan_date', date('m'))
                ->whereYear('plan_date', date('Y'));
        })->with(['product.routings', 'productionPlan'])->get();

        $reportData = collect();

        foreach ($details as $detail) {
            if (!$detail->product) continue;

            // Ambil routing yang hanya relevan dengan mesin di Line ini
            $relevantRoutings = $detail->product->routings->filter(function ($route) use ($line) {
                return $line->machines->contains('id', $route->machine_id);
            });

            if ($relevantRoutings->isEmpty()) continue;

            foreach ($relevantRoutings as $routing) {
                $pcsPerHour = $routing->pcs_per_hour;
                
                // Rumus Loading Hours = Qty Plan / Pcs Per Jam
                $hours = $pcsPerHour > 0 ? ($detail->qty_plan / $pcsPerHour) : 0;
                
                // Konversi Pcs/Jam ke Cycle Time (Detik) hanya untuk display
                $displayCT = $pcsPerHour > 0 ? (3600 / $pcsPerHour) : 0;

                // Handle Code Part (Jika kosong di DB, generate dummy)
                $codePart = $detail->product->code_part ?? ('CP-' . str_pad($detail->product->id, 3, '0', STR_PAD_LEFT));

                $reportData->push((object) [
                    'part_number' => $detail->product->part_number,
                    'part_name'   => $detail->product->part_name,
                    'code_part'   => $codePart,
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
            'line'            => $line,
            'groupedMachines' => $groupedMachines,
            'reportData'      => $reportData->sortBy('part_number'),
            'allLines'        => $allLines,
            'period'          => date('F Y')
        ];
    }

    // A. TAMPILAN WEB
    public function loadingReport(Request $request)
    {
        $data = $this->getLoadingReportData($request);
        if (!$data) return redirect()->route('plans.index')->withErrors('Line tidak ditemukan.');

        return view('plans.loading_report', $data);
    }

    // B. DOWNLOAD PDF
    public function downloadLoadingPdf(Request $request)
    {
        $data = $this->getLoadingReportData($request);
        if (!$data) return back()->withErrors('Data tidak ditemukan.');

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
        if (!$data) return back()->withErrors('Data tidak ditemukan.');

        // Gunakan Class Export yang sudah dibuat
        return Excel::download(new LoadingReportExport($data), 'Loading_Report_' . $data['line']->name . '.xlsx');
    }
}