<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductionLine;
use App\Models\ProductionPlanDetail;
use App\Models\ProductionActual;
use Carbon\Carbon;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        // 1. Tentukan Periode (Bulan Ini)
        $month = now()->month;
        $year  = now()->year;
        $monthName = now()->format('F Y');

        // 2. Siapkan Array untuk Grafik
        $labels = []; // Nama Line
        $dataPlan = []; // Angka Plan
        $dataActual = []; // Angka Actual

        // 3. Ambil Semua Line
        $lines = ProductionLine::all();

        // Variabel untuk KPI Global (Kotak Atas)
        $grandTotalPlan = 0;
        $grandTotalActual = 0;

        foreach ($lines as $line) {
            // A. Hitung Plan Bulan Ini per Line
            // Relasi: PlanDetail -> Plan (Header) -> Line
            $linePlan = ProductionPlanDetail::whereHas('productionPlan', function($q) use ($line, $month, $year) {
                $q->where('production_line_id', $line->id)
                  ->whereMonth('plan_date', $month)
                  ->whereYear('plan_date', $year);
            })->sum('qty_plan');

            // B. Hitung Actual Bulan Ini per Line
            // Relasi: Actual -> PlanDetail -> Plan -> Line
            $lineActual = ProductionActual::whereHas('planDetail.productionPlan', function($q) use ($line, $month, $year) {
                $q->where('production_line_id', $line->id);
            })
            ->whereMonth('production_date', $month)
            ->whereYear('production_date', $year)
            ->sum('qty_good');

            // Masukkan ke Array Data Grafik
            $labels[] = $line->name;
            $dataPlan[] = $linePlan;
            $dataActual[] = $lineActual;

            // Tambahkan ke Grand Total
            $grandTotalPlan += $linePlan;
            $grandTotalActual += $lineActual;
        }

        // Hitung Persentase Global
        $achievementPct = $grandTotalPlan > 0 
            ? round(($grandTotalActual / $grandTotalPlan) * 100, 1) 
            : 0;

        return view('home', compact(
            'monthName', 
            'labels', 
            'dataPlan', 
            'dataActual',
            'grandTotalPlan',
            'grandTotalActual',
            'achievementPct'
        ));
    }
}