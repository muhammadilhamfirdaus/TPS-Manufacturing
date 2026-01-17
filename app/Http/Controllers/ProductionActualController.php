<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductionPlanDetail;
use App\Models\ProductionActual;
use App\Models\ProductionLine;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProductionActualController extends Controller
{
    // =================================================================
    // 1. INDEX: TAMPILAN MATRIX (Menggantikan Input Harian Lama)
    // =================================================================
    public function index(Request $request)
    {
        $month = $request->get('month', date('m'));
        $year = $request->get('year', date('Y'));
        
        // ... (Kode Line & Plan query TETAP SAMA seperti sebelumnya) ...
        $lineId = $request->get('line_id');
        if(!$lineId) {
            $firstLine = ProductionLine::first();
            $lineId = $firstLine ? $firstLine->id : 0;
        }
        $lines = ProductionLine::all();
        $daysInMonth = Carbon::create($year, $month)->daysInMonth;

        $plans = ProductionPlanDetail::whereHas('productionPlan', function($q) use ($month, $year, $lineId) {
            $q->whereMonth('plan_date', $month)
              ->whereYear('plan_date', $year)
              ->where('production_line_id', $lineId);
        })
        ->with(['product', 'productionActuals', 'productionPlan.productionLine'])
        ->get();

        $matrixActuals = [];
        foreach($plans as $plan) {
            foreach($plan->productionActuals as $actual) {
                $d = (int) date('d', strtotime($actual->production_date));
                $matrixActuals[$plan->id][$d] = $actual->qty_good;
            }
        }

        // --- AMBIL DATA LIBUR ---
        $holidays = \App\Models\Holiday::whereMonth('date', $month)
                    ->whereYear('date', $year)
                    ->pluck('description', DB::raw('DAY(date) as day'))
                    ->toArray();

        // --- HITUNG TOTAL HARI KERJA (EFFECTIVE WORKING DAYS) ---
        $totalWorkingDays = 0;
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dateObj = Carbon::create($year, $month, $d);
            $isWeekend = $dateObj->isWeekend();
            $isHoliday = array_key_exists($d, $holidays);

            // Jika BUKAN Weekend DAN BUKAN Libur, hitung sebagai hari kerja
            if (!$isWeekend && !$isHoliday) {
                $totalWorkingDays++;
            }
        }

        return view('production.input', compact(
            'plans', 'lines', 'lineId', 'month', 'year', 
            'daysInMonth', 'matrixActuals', 'holidays', 
            'totalWorkingDays' // <--- KITA KIRIM VARIABEL INI
        ));
    }

    // =================================================================
    // 2. STORE: SIMPAN DATA DARI MATRIX (BULK SAVE)
    // =================================================================
    public function store(Request $request)
    {
        $data = $request->input('actuals'); // Array: [plan_detail_id][tanggal] => qty
        $month = $request->input('month');
        $year = $request->input('year');

        DB::beginTransaction();
        try {
            if ($data) {
                foreach ($data as $planDetailId => $dates) {
                    // Ambil info Plan Induk sekali saja untuk efisiensi
                    $planDetail = ProductionPlanDetail::with('productionPlan')->find($planDetailId);
                    if (!$planDetail)
                        continue;

                    foreach ($dates as $date => $qty) {
                        // Skip jika null (kosong) agar tidak menimpa data
                        if ($qty === null)
                            continue;

                        // Format Tanggal Lengkap: YYYY-MM-DD
                        $fullDate = sprintf('%s-%s-%02d', $year, $month, $date);

                        ProductionActual::updateOrCreate(
                            [
                                'production_plan_detail_id' => $planDetailId,
                                'production_date' => $fullDate,
                            ],
                            [
                                'production_line_id' => $planDetail->productionPlan->production_line_id,
                                'shift_id' => $planDetail->productionPlan->shift_id,
                                'product_id' => $planDetail->product_id,
                                'qty_good' => $qty,
                                'qty_reject' => 0, // Default 0
                                'created_by' => auth()->id()
                            ]
                        );
                    }
                }
            }
            DB::commit();
            return back()->with('success', 'Data Produksi berhasil disimpan!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Error: ' . $e->getMessage());
        }
    }

    // Method monitoring lama bisa dihapus atau dibiarkan saja
    public function monitoring(Request $request)
    {
        // ... (Optional)
        return redirect()->route('production.input'); // Redirect ke matrix aja
    }
}