<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductionLine;
use App\Models\ProductionPlanDetail;
use App\Models\KanbanReport;
use App\Models\DailyPlan;
use App\Models\Product;
use App\Models\ProductRouting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB; // Tambahkan ini jika belum ada

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        // 1. FILTER REQUEST
        $month = $request->get('month', date('m'));
        $year = $request->get('year', date('Y'));
        $selectedLineId = $request->get('line_id');

        $monthName = Carbon::createFromDate($year, $month, 1)->format('F Y');

        $CAPACITY_PER_MONTH = 152;
        $WORK_HOURS_PER_DAY = 8;

        // =====================================================================
        // PART 1: KPI CARDS
        // =====================================================================
        $lines = ProductionLine::orderBy('name')->get();

        $planQuery = ProductionPlanDetail::whereHas('productionPlan', function ($q) use ($month, $year) {
            $q->whereMonth('plan_date', $month)->whereYear('plan_date', $year)->where('status', '!=', 'HISTORY');
        });
        $actQuery = KanbanReport::whereMonth('report_date', $month)->whereYear('report_date', $year);

        if ($selectedLineId) {
            $productIds = ProductRouting::where('production_line_id', $selectedLineId)->pluck('product_id');
            $filteredPartCodes = Product::whereIn('id', $productIds)->pluck('code_part')->toArray();

            $planQuery->whereHas('product', function ($q) use ($filteredPartCodes) {
                $q->whereIn('code_part', $filteredPartCodes);
            });
            $actQuery->whereIn('code_part', $filteredPartCodes);
        }

        $grandTotalPlan = $planQuery->sum('qty_plan');

        // Ambil Data Actual & NG Sekaligus
        $reportData = $actQuery->get();

        $grandTotalActual = $reportData->sum(function ($row) {
            return $row->act_shift_1 + $row->act_shift_2;
        });

        // [BARU] Hitung Total NG Global
        $grandTotalNg = $reportData->sum(function ($row) {
            return $row->ng_shift_1 + $row->ng_shift_2;
        });

        $achievementPct = $grandTotalPlan > 0 ? round(($grandTotalActual / $grandTotalPlan) * 100, 1) : 0;

        // [BARU] Hitung NG Rate Global (%)
        $totalProduction = $grandTotalActual + $grandTotalNg;
        $ngRatePct = $totalProduction > 0 ? round(($grandTotalNg / $totalProduction) * 100, 2) : 0;


        // =====================================================================
        // PART 2: CHART 1 (Unit per Line + Indikator Persen) + [NG DATA]
        // =====================================================================
        $labelsLine = [];
        $dataPlanLine = [];
        $dataActualLine = [];
        $dataPctLine = [];
        $dataNgLine = []; // <--- VARIABEL BARU: Data NG Per Line

        $linesForChart = $selectedLineId ? $lines->where('id', $selectedLineId) : $lines;

        foreach ($linesForChart as $line) {
            // 1. Plan
            $lPlan = ProductionPlanDetail::whereHas('productionPlan', function ($q) use ($line, $month, $year) {
                $q->where('production_line_id', $line->id)
                    ->whereMonth('plan_date', $month)
                    ->whereYear('plan_date', $year)
                    ->where('status', '!=', 'HISTORY');
            })->sum('qty_plan');

            // 2. Cari Part yg diproduksi di Line ini
            $plannedParts = ProductionPlanDetail::whereHas('productionPlan', function ($q) use ($line, $month, $year) {
                $q->where('production_line_id', $line->id)
                    ->whereMonth('plan_date', $month)
                    ->whereYear('plan_date', $year);
            })->with('product')->get()->pluck('product.code_part')->unique()->toArray();

            $lActual = 0;
            $lNg = 0; // <--- Init NG

            if (!empty($plannedParts)) {
                $reportsPerLine = KanbanReport::whereIn('code_part', $plannedParts)
                    ->whereMonth('report_date', $month)
                    ->whereYear('report_date', $year)
                    ->get();

                // Sum Actual
                $lActual = $reportsPerLine->sum(function ($row) {
                    return $row->act_shift_1 + $row->act_shift_2;
                });

                // [BARU] Sum NG
                $lNg = $reportsPerLine->sum(function ($row) {
                    return $row->ng_shift_1 + $row->ng_shift_2;
                });
            }

            // Hitung Persentase Achievement
            $lPct = ($lPlan > 0) ? round(($lActual / $lPlan) * 100, 1) : 0;

            $labelsLine[] = $line->name;
            $dataPlanLine[] = $lPlan;
            $dataActualLine[] = $lActual;
            $dataPctLine[] = $lPct;
            $dataNgLine[] = $lNg; // <--- Masukkan ke array NG
        }


        // =====================================================================
        // PART 3: CHART 2 & 3 (Loading MP Analysis) - LOGIKA TETAP
        // =====================================================================

        // A. Hitung Total MPP (Garis Merah)
        $mppTargetTotal = 0;
        $productsMap = Product::pluck('cycle_time', 'code_part');

        foreach ($linesForChart as $line) {
            $monthlyDetails = ProductionPlanDetail::whereHas('productionPlan', function ($q) use ($line, $month, $year) {
                $q->where('production_line_id', $line->id)->whereMonth('plan_date', $month)->whereYear('plan_date', $year)->where('status', '!=', 'HISTORY');
            })->get();

            $lineLoadHours = 0;
            foreach ($monthlyDetails as $detail) {
                $ct = $productsMap[$detail->product->code_part ?? ''] ?? 10;
                $lineLoadHours += ($detail->qty_plan * $ct) / 3600;
            }
            $mppTargetTotal += ceil($lineLoadHours / $CAPACITY_PER_MONTH);
        }

        // B. Hitung Harian
        $daysInMonth = Carbon::create($year, $month)->daysInMonth;
        $chartDates = [];
        $valPlanLoad = [];
        $valActLoad = [];
        $valDelayLoad = [];
        $valMppLimit = [];
        $valActPct = [];
        $valNgDaily = []; // <--- VARIABEL BARU: Trend NG Harian

        $dailyPlanQ = DailyPlan::whereMonth('plan_date', $month)->whereYear('plan_date', $year);
        $dailyActQ = KanbanReport::whereMonth('report_date', $month)->whereYear('report_date', $year);

        if ($selectedLineId) {
            $productIds = ProductRouting::where('production_line_id', $selectedLineId)->pluck('product_id');
            $validParts = Product::whereIn('id', $productIds)->pluck('code_part')->toArray();
            $dailyPlanQ->whereIn('code_part', $validParts);
            $dailyActQ->whereIn('code_part', $validParts);
        }

        $dailyPlans = $dailyPlanQ->get()->groupBy(function ($item) {
            return (int) date('d', strtotime($item->plan_date));
        });
        $dailyActs = $dailyActQ->get()->groupBy(function ($item) {
            return (int) date('d', strtotime($item->report_date));
        });

        $runningCumPlanHours = 0;
        $runningCumActHours = 0;

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $chartDates[] = $d;

            // Load Harian (Jam)
            $dailyPlanHours = 0;
            if (isset($dailyPlans[$d])) {
                foreach ($dailyPlans[$d] as $item) {
                    $ct = $productsMap[$item->code_part] ?? 30;
                    $dailyPlanHours += ($item->qty * $ct) / 3600;
                }
            }

            $dailyActHours = 0;
            $dailyNgPcs = 0; // <--- Init NG Harian (Pcs)

            if (isset($dailyActs[$d])) {
                foreach ($dailyActs[$d] as $item) {
                    $ct = $productsMap[$item->code_part] ?? 30;
                    $qty = $item->act_shift_1 + $item->act_shift_2;
                    $dailyActHours += ($qty * $ct) / 3600;

                    // [BARU] Hitung NG Harian
                    $dailyNgPcs += ($item->ng_shift_1 + $item->ng_shift_2);
                }
            }

            // Kumulatif & Delay
            $runningCumPlanHours += $dailyPlanHours;
            $runningCumActHours += $dailyActHours;
            $gapHours = $runningCumPlanHours - $runningCumActHours;
            $delayHours = ($gapHours > 0) ? $gapHours : 0;

            // Konversi ke Orang (MP)
            $pMp = $dailyPlanHours / $WORK_HOURS_PER_DAY;
            $aMp = $dailyActHours / $WORK_HOURS_PER_DAY;
            $dMp = $delayHours / $WORK_HOURS_PER_DAY;

            // Hitung Persentase MPP
            $pct = 0;
            if ($mppTargetTotal > 0) {
                $pct = ($aMp / $mppTargetTotal) * 100;
            }

            $valPlanLoad[] = round($pMp, 1);
            $valActLoad[] = round($aMp, 1);
            $valDelayLoad[] = round($dMp, 1);
            $valMppLimit[] = $mppTargetTotal;
            $valActPct[] = round($pct, 1);

            $valNgDaily[] = $dailyNgPcs; // <--- Masukkan Data NG ke Array
        }

        // 1. Cari nilai batang tertinggi (untuk skala grafik)
        $highestBarValue = 0;
        foreach ($valPlanLoad as $key => $val) {
            $totalTarget = $val + ($valDelayLoad[$key] ?? 0);
            $totalAct = $valActLoad[$key] ?? 0;
            $maxToday = max($totalTarget, $totalAct);
            if ($maxToday > $highestBarValue) {
                $highestBarValue = $maxToday;
            }
        }

        $baseMax = max($highestBarValue, $mppTargetTotal);
        $chartMaxY = $baseMax > 0 ? ($baseMax * 1.2) : 1;
        $chartMaxPct = $mppTargetTotal > 0 ? ($chartMaxY / $mppTargetTotal) * 100 : 100;

        return view('home', compact(
            'monthName',
            'month',
            'year',
            'lines',
            'selectedLineId',
            'grandTotalPlan',
            'grandTotalActual',
            'grandTotalNg', // <--- Dikirim ke View
            'ngRatePct',    // <--- Dikirim ke View
            'achievementPct',
            // Data Chart 1
            'labelsLine',
            'dataPlanLine',
            'dataActualLine',
            'dataPctLine',
            'dataNgLine',   // <--- Dikirim ke View (Grafik 1)
            // Data Chart 2 & 3
            'chartDates',
            'valPlanLoad',
            'valActLoad',
            'valDelayLoad',
            'valMppLimit',
            'valActPct',
            'valNgDaily',   // <--- Dikirim ke View (Grafik Baru Harian)
            // Skala
            'chartMaxY',
            'chartMaxPct'
        ));
    }
}