<?php

namespace App\Http\Controllers;

use App\Models\ProductionPlan;
use App\Models\ProductionPlanDetail;
use App\Models\ProductionActual;
use App\Models\DailyPlan;
use App\Models\ProductionLine;
use App\Models\Holiday;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProductionActualController extends Controller
{
    // =================================================================
    // 1. INDEX: TAMPILAN MATRIX INPUT (OPERATOR)
    // =================================================================
    public function index(Request $request)
    {
        // 1. Filter Waktu
        $selectedMonth = $request->get('filter_month', date('m'));
        $selectedYear = $request->get('filter_year', date('Y'));

        // 2. Filter Plant
        $plants = \App\Models\ProductionLine::select('plant')->distinct()->orderBy('plant')->pluck('plant');
        $selectedPlant = $request->get('plant', $plants->first());

        // 3. Ambil Line
        $allLines = \App\Models\ProductionLine::orderBy('name')->get();
        $lines = $allLines->where('plant', $selectedPlant);
        $lineId = $request->get('line_id', $lines->first()->id ?? 0);

        // 4. LOGIC MATRIX DATA
        $matrixData = collect();
        if ($lineId) {
            $matrixData = \App\Models\ProductionPlanDetail::with(['product', 'productionPlan'])
                ->whereHas('productionPlan', function ($q) use ($selectedMonth, $selectedYear) {
                    $q->whereMonth('plan_date', $selectedMonth)
                        ->whereYear('plan_date', $selectedYear)
                        ->where('status', '!=', 'HISTORY');
                })
                ->whereHas('product.routings', function ($q) use ($lineId) {
                    $q->where('production_line_id', $lineId);
                })
                ->get()
                ->groupBy(function ($item) {
                    // PENTING: Trim agar kunci array bersih dari spasi
                    return trim($item->product->code_part);
                });
        }
        $validCodes = $matrixData->keys();

        // 5. Data Target Harian (DailyPlan)
        $dailyPlanData = [];
        $rawDaily = \App\Models\DailyPlan::whereIn('code_part', $validCodes)
            ->whereMonth('plan_date', $selectedMonth)
            ->whereYear('plan_date', $selectedYear)
            ->get();

        foreach ($rawDaily as $dPlan) {
            $code = trim($dPlan->code_part);
            $day = (int) $dPlan->day_only;
            $dailyPlanData[$code][$day] = $dPlan->qty;
        }

        // =====================================================================
        // 6. [SOLUSI] AMBIL DATA ACTUAL MENJADI ARRAY PHP MURNI
        // =====================================================================
        $actualData = [];
        $rawActuals = \App\Models\ProductionActual::whereIn('code_part', $validCodes)
            ->whereMonth('production_date', $selectedMonth)
            ->whereYear('production_date', $selectedYear)
            ->get();

        foreach ($rawActuals as $act) {
            // 1. Bersihkan Code Part (Trim) agar cocok dengan matrix
            $code = trim($act->code_part);

            // 2. Ubah Tanggal jadi Integer (misal "2026-01-05" -> 5)
            $day = (int) \Carbon\Carbon::parse($act->production_date)->format('d');

            // 3. Masukkan ke Array [code][tanggal] = qty
            $actualData[$code][$day] = $act->qty_final;
        }
        // =====================================================================

        // 7. Hari Libur & Kerja
        $holidays = \App\Models\Holiday::whereMonth('date', $selectedMonth)
            ->whereYear('date', $selectedYear)
            ->pluck('date')
            ->toArray();

        $daysInMonth = \Carbon\Carbon::create($selectedYear, $selectedMonth)->daysInMonth;
        $totalWorkingDays = 0;
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dt = \Carbon\Carbon::create($selectedYear, $selectedMonth, $d);
            if (!$dt->isWeekend() && !in_array($dt->format('Y-m-d'), $holidays)) {
                $totalWorkingDays++;
            }
        }

        return view('production.input', compact(
            'matrixData',
            'selectedMonth',
            'selectedYear',
            'plants',
            'selectedPlant',
            'allLines',
            'lines',
            'lineId',
            'dailyPlanData',
            'actualData',
            'holidays',
            'totalWorkingDays'
        ));
    }
    // =================================================================
    // 2. STORE: SIMPAN DATA ACTUAL (INPUT MANUAL)
    // =================================================================
    public function store(Request $request)
    {
        $month = $request->month;
        $year = $request->year;

        // Data input dari view: name="actuals[CODE_PART][DAY]"
        $inputs = $request->actuals;

        DB::beginTransaction();
        try {
            if ($inputs) {
                foreach ($inputs as $codePart => $days) {
                    foreach ($days as $day => $qty) {
                        // Simpan jika input tidak null (0 boleh disimpan)
                        if ($qty !== null && $qty !== '') {
                            $date = Carbon::create($year, $month, $day)->format('Y-m-d');

                            ProductionActual::updateOrCreate(
                                [
                                    'production_date' => $date,
                                    'code_part' => $codePart
                                ],
                                [
                                    'qty_delv' => $qty, // Simpan sebagai input manual
                                    'qty_final' => $qty, // Angka ini yang dipakai report
                                    'created_by' => auth()->id() ?? 1
                                ]
                            );
                        }
                    }
                }
            }

            DB::commit();
            return back()->with('success', 'Data Actual Produksi berhasil disimpan!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan: ' . $e->getMessage());
        }
    }
}