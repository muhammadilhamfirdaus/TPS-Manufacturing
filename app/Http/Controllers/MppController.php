<?php

namespace App\Http\Controllers;

use App\Models\ProductionPlanDetail;
use App\Models\ProductionLine;
use App\Models\Holiday;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf; // PENTING: Jangan lupa import ini

class MppController extends Controller
{
    /**
     * 1. HALAMAN UTAMA (INDEX)
     */
    public function index(Request $request)
    {
        $month = $request->get('month', date('m'));
        $year  = $request->get('year', date('Y'));

        // Panggil fungsi sentral pengolah data
        $data = $this->getMppData($month, $year);

        // Kirim data ke View Index
        return view('mpp.index', array_merge($data, [
            'month' => $month, 
            'year' => $year
        ]));
    }

    /**
     * 2. EXPORT PDF (METHOD YANG HILANG)
     */
    public function exportPdf(Request $request)
    {
        $month = $request->get('month', date('m'));
        $year  = $request->get('year', date('Y'));

        // Panggil fungsi sentral yang SAMA dengan index
        $data = $this->getMppData($month, $year);

        // Tambahkan info bulan/tahun untuk judul di PDF
        $data['month'] = $month;
        $data['year'] = $year;

        // Generate PDF menggunakan View 'mpp.pdf'
        $pdf = Pdf::loadView('mpp.pdf', $data);
        
        // Set ukuran kertas A4 Landscape
        return $pdf->setPaper('a4', 'landscape')
                   ->download('MPP_Report_'.date('Y_m').'.pdf');
    }

    /**
     * 3. LOGIC PENGOLAH DATA (REUSABLE)
     * Digunakan oleh Index dan ExportPdf agar hasilnya konsisten.
     */
    private function getMppData($month, $year)
    {
        // A. HITUNG HARI KERJA
        $daysInMonth = Carbon::create($year, $month)->daysInMonth;
        $holidays = Holiday::whereMonth('date', $month)->whereYear('date', $year)->pluck('date')->toArray();

        $workDays = 0;
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date = Carbon::create($year, $month, $d);
            if (!$date->isWeekend() && !in_array($date->format('Y-m-d'), $holidays)) {
                $workDays++;
            }
        }
        $workDays = $workDays > 0 ? $workDays : 22; 
        $workHoursPerDay = 8; 
        $totalHoursPerson = $workDays * $workHoursPerDay; 

        // B. QUERY DATABASE
        $details = ProductionPlanDetail::with([
                'productionPlan.productionLine',
                'product.routings.machine.productionLine'
            ])
            ->whereHas('productionPlan', function($q) use ($month, $year) {
                $q->whereMonth('plan_date', $month)
                  ->whereYear('plan_date', $year)
                  ->where('status', '!=', 'HISTORY');
            })
            ->get();

        // C. AKUMULASI DATA PER LINE
        $aggregatedData = collect();

        foreach ($details as $detail) {
            if (!$detail->product) continue;
            $routings = $detail->product->routings;

            if ($routings->isEmpty()) {
                // Fallback jika tidak ada routing
                $line = $detail->productionPlan->productionLine;
                if($line) $this->accumulateData($aggregatedData, $line, $detail, null);
            } else {
                // Loop Routing
                foreach ($routings as $routing) {
                    $line = $routing->machine->productionLine ?? null;
                    if ($line) $this->accumulateData($aggregatedData, $line, $detail, $routing);
                }
            }
        }

        // D. HITUNG HASIL AKHIR (MPP)
        $mppData = $aggregatedData->map(function ($item) use ($totalHoursPerson) {
            $mppMurni = $totalHoursPerson > 0 ? ($item->total_man_hours / $totalHoursPerson) : 0;
            $item->mpp_murni  = $mppMurni;
            $item->mpp_aktual = ceil($mppMurni);
            return $item;
        });

        // Grouping & Sorting
        $groupedMpp = $mppData->sortBy(function ($item) {
            return $item->plant . $item->line_name;
        })->groupBy('plant');

        // Return array data
        return compact('groupedMpp', 'totalHoursPerson', 'workDays');
    }

    /**
     * 4. HELPER PERHITUNGAN MATEMATIS
     */
    private function accumulateData($collection, $line, $detail, $routing = null)
    {
        $lineId = $line->id;
        
        // Ambil data teknis
        $pcsPerHour = $routing ? $routing->pcs_per_hour : ($detail->product->pcs_per_hour_global ?? 0);
        if ($pcsPerHour <= 0) $pcsPerHour = 1; // Cegah division by zero

        $mpRatio    = $routing ? $routing->manpower_ratio : 1; 
        $cycleTime  = 3600 / $pcsPerHour; 

        // Rumus Jam
        $machineHours = ($detail->qty_plan * $cycleTime) / 3600;
        
        // Rumus Manpower dengan Rasio
        $finalRatio = ($mpRatio > 0) ? $mpRatio : 1;
        $manHours = $machineHours / $finalRatio;

        // Inisialisasi jika belum ada
        if (!$collection->has($lineId)) {
            $collection->put($lineId, (object) [
                'plant'           => $line->plant ?? 'OTHER',
                'line_name'       => $line->name,
                'keb_jam_kerja'   => 0,
                'total_man_hours' => 0,
                'mpp_murni'       => 0,
                'mpp_aktual'      => 0,
            ]);
        }

        // Tambahkan value
        $data = $collection->get($lineId);
        $data->keb_jam_kerja   += $machineHours;
        $data->total_man_hours += $manHours; 
    }
}