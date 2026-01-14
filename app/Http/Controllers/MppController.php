<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductionLine;
use App\Models\ProductionPlanDetail;
use Illuminate\Support\Facades\DB;

class MppController extends Controller
{
    public function index(Request $request)
    {
        // 1. Filter Bulan (Default bulan ini)
        $month = $request->get('month', date('m'));
        $year = $request->get('year', date('Y'));
        
        // 2. Setting Konstanta Kerja
        $workDays = 22; // Hari kerja sebulan
        $workHoursPerDay = 7.5; // Jam efektif per hari
        $totalHoursPerson = $workDays * $workHoursPerDay; // 165 Jam/Orang

        // 3. Ambil Line beserta Mesin-mesinnya (PENTING: Eager Load 'machines')
        $lines = ProductionLine::with('machines')->orderBy('plant')->orderBy('name')->get();
        
        // 4. Hitung Load per Line
        $mppData = $lines->map(function($line) use ($month, $year, $totalHoursPerson) {
            
            // Ambil semua plan di line ini pada bulan terpilih
            $details = ProductionPlanDetail::whereHas('productionPlan', function($q) use ($line, $month, $year) {
                $q->where('production_line_id', $line->id)
                  ->whereMonth('plan_date', $month)
                  ->whereYear('plan_date', $year);
            })->with(['product.routings'])->get();

            // --- REVISI LOGIKA PERHITUNGAN LOAD ---
            $totalHoursLine = 0;

            // Ambil ID semua mesin di line ini untuk filter routing
            $lineMachineIds = $line->machines->pluck('id')->toArray();

            foreach($details as $d) {
                if(!$d->product) continue;

                // Loop setiap proses/routing dari produk tersebut
                foreach($d->product->routings as $routing) {
                    // Cek: Apakah proses ini dikerjakan di mesin milik Line ini?
                    if(in_array($routing->machine_id, $lineMachineIds)) {
                        
                        $capPerHour = $routing->pcs_per_hour;
                        
                        // Rumus Load: Qty Plan / Kapasitas per Jam
                        if($capPerHour > 0) {
                            $hours = $d->qty_plan / $capPerHour;
                            $totalHoursLine += $hours;
                        }
                    }
                }
            }
            
            // Sekarang $totalHoursLine adalah jumlah akumulasi dari SEMUA proses di line tersebut
            // Contoh: (8000/700) + (8000/700) = 11.4 + 11.4 = 22.8 Jam

            // Hitung MPP
            $mppMurni = $totalHoursPerson > 0 ? ($totalHoursLine / $totalHoursPerson) : 0;

            return (object) [
                'plant' => $line->plant,
                'line_name' => $line->name,
                'keb_jam_kerja' => $totalHoursLine, // Ini sekarang sudah 22.8
                'mpp_murni' => $mppMurni,
                'mpp_aktual' => ceil($mppMurni), 
                'helper' => 0,
                'backup' => 0,
            ];
        });

        // 5. Grouping by Plant
        $groupedMpp = $mppData->groupBy('plant');

        return view('mpp.index', compact('groupedMpp', 'month', 'year', 'totalHoursPerson'));
    }
}