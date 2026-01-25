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

    public function index(Request $request)
    {
        // 1. Tentukan Periode (Ambil dari Request filter, atau default ke Bulan Ini)
        $month = $request->get('month', date('m'));
        $year  = $request->get('year', date('Y'));
        
        // Nama Bulan untuk Judul Dashboard
        $monthName = Carbon::createFromDate($year, $month, 1)->format('F Y');

        // 2. Siapkan Array untuk Grafik
        $labels = [];     // Nama Line
        $dataPlan = [];   // Angka Plan
        $dataActual = []; // Angka Actual

        // 3. Ambil Semua Line
        $lines = ProductionLine::all();

        // Variabel untuk KPI Global (Kotak Atas)
        $grandTotalPlan = 0;
        $grandTotalActual = 0;

        foreach ($lines as $line) {
            // =================================================================
            // A. HITUNG PLAN (TARGET)
            // =================================================================
            // Cari Plan yang production_line_id-nya sesuai
            $linePlan = ProductionPlanDetail::whereHas('productionPlan', function($q) use ($line, $month, $year) {
                $q->where('production_line_id', $line->id)
                  ->whereMonth('plan_date', $month)
                  ->whereYear('plan_date', $year)
                  ->where('status', '!=', 'HISTORY');
            })->sum('qty_plan');

            // =================================================================
            // B. HITUNG ACTUAL (REALISASI) - LOGIKA BARU
            // =================================================================
            // Karena tabel Actual tidak punya Line ID, kita cari lewat Code Part.
            
            // 1. Cari dulu: Part apa saja yang direncanakan jalan di Line ini bulan ini?
            $plannedParts = ProductionPlanDetail::whereHas('productionPlan', function($q) use ($line, $month, $year) {
                $q->where('production_line_id', $line->id)
                  ->whereMonth('plan_date', $month)
                  ->whereYear('plan_date', $year);
            })
            ->with('product') // Load relasi ke tabel products
            ->get()
            ->pluck('product.code_part') // Ambil list Code Part-nya
            ->unique()
            ->toArray();

            // 2. Sum QTY_FINAL dari tabel Actual berdasarkan Code Part tersebut
            // Jika Part A ada di Line ini, maka semua hasil produksi Part A dianggap milik Line ini.
            $lineActual = 0;
            if (!empty($plannedParts)) {
                $lineActual = ProductionActual::whereIn('code_part', $plannedParts)
                    ->whereMonth('production_date', $month)
                    ->whereYear('production_date', $year)
                    ->sum('qty_final'); // <--- PENTING: Pakai 'qty_final', bukan 'qty_good'
            }

            // =================================================================
            // C. Masukkan ke Array Data Grafik
            // =================================================================
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
            'achievementPct',
            'month', // Kirim balik ke view untuk select option
            'year'
        ));
    }
}