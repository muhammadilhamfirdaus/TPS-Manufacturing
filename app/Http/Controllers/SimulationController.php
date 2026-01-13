<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductionLine;
use App\Services\ManufacturingCalculatorService;
use Illuminate\Http\Request;

class SimulationController extends Controller
{
    protected $calculator;

    // Inject Service Calculator tadi ke sini
    public function __construct(ManufacturingCalculatorService $calculator)
    {
        $this->calculator = $calculator;
    }

    public function testLogic()
    {
        // 1. Ambil Data Dummy dari Database (yang sudah kita seed)
        // Ambil Part "Cover Engine Front" (Cycle time: 30.5 detik)
        $part = Product::where('part_number', 'COVER-ENG-001')->first();
        
        // Ambil Line "Line Assembling A" (Std Manpower: 8 orang)
        $line = ProductionLine::where('name', 'Line Assembling A')->first();

        // 2. Skenario Simulasi
        $targetProduksi = 1000; // Kita mau bikin 1000 pcs
        $shiftDuration = 480;   // 8 Jam kerja (480 menit)
        $breakTime = 40;        // Istirahat 40 menit
        $effectiveTime = $shiftDuration - $breakTime; // 440 menit kerja efektif

        // 3. Panggil Service Calculator (OTAKNYA)
        $loadingMesin = $this->calculator->calculateMachineLoading(
            $targetProduksi, 
            $part->cycle_time, 
            $shiftDuration
        );

        $kebutuhanOrang = $this->calculator->calculateManPower(
            $targetProduksi,
            $part->cycle_time,
            $effectiveTime
        );

        $jumlahKanban = $this->calculator->calculateKanbanCards(
            $targetProduksi, // Asumsi demand harian sama dengan target
            0.5, // Lead time setengah hari
            $part->qty_per_box,
            $part->safety_stock
        );

        // 4. Return Hasil JSON agar mudah dibaca
        return response()->json([
            'Skenario' => [
                'Part Name' => $part->part_name,
                'Cycle Time' => $part->cycle_time . ' detik',
                'Target Produksi' => $targetProduksi . ' pcs',
                'Durasi Shift' => $shiftDuration . ' menit',
                'Ketersediaan Line' => $line->name . ' (Std: ' . $line->std_manpower . ' org)',
            ],
            'Hasil Perhitungan AI' => [
                '1. Machine Loading' => $loadingMesin . '%',
                'Status Loading' => $loadingMesin > 100 ? 'OVERLOAD ⚠️' : 'AMAN ✅',
                
                '2. Man Power Planning' => [
                    'Butuh Orang' => $kebutuhanOrang . ' Orang',
                    'Standar Line' => $line->std_manpower . ' Orang',
                    'Analisa' => ($kebutuhanOrang > $line->std_manpower) 
                        ? 'KURANG ORANG! Butuh tambah ' . ($kebutuhanOrang - $line->std_manpower) 
                        : 'Orang Cukup / Kelebihan'
                ],

                '3. Kebutuhan Kanban' => $jumlahKanban . ' Kartu/Box',
            ]
        ]);
    }
}