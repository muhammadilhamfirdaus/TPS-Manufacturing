<?php

namespace App\Imports;

use App\Models\ProductionPlan;
use App\Models\ProductionPlanDetail;
use App\Models\ProductionLine;
use App\Models\Product;
use App\Services\ManufacturingCalculatorService;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ProductionPlanImport implements ToModel, WithHeadingRow
{
    protected $calculator;

    public function __construct()
    {
        $this->calculator = new ManufacturingCalculatorService();
    }

    public function model(array $row)
    {
        // 1. REVISI UTAMA: FILTER BARIS KOSONG
        // Ambil nilai qty, jika kosong anggap 0
        $qty = isset($row['qty_plan']) ? (int) $row['qty_plan'] : 0;

        // SKIP baris ini jika user tidak mengisi Qty atau isinya 0
        if ($qty <= 0) {
            return null; 
        }

        // 2. VALIDASI KOLOM WAJIB
        // Jika Qty diisi, maka Tanggal & Line Name WAJIB ada.
        // Nama key array mengikuti header Excel (lowercase + snake_case)
        if (empty($row['plan_date_yyyy_mm_dd']) || empty($row['line_name'])) {
            return null; // Skip baris yang datanya tidak lengkap
        }

        // 3. CARI DATA MASTER
        // Cari Part
        $product = Product::where('part_number', $row['part_number'])->first();
        if (!$product) return null; // Skip jika part tidak ditemukan di database

        // Cari Line
        $line = ProductionLine::where('name', trim($row['line_name']))->first();
        if (!$line) return null; // Skip jika nama line salah/tidak ada

        // 4. PARSING TANGGAL EXCEL (Lebih Robust)
        try {
            $rawDate = $row['plan_date_yyyy_mm_dd'];
            
            // Cek apakah formatnya angka (Serial Number Excel)
            if (is_numeric($rawDate)) {
                $planDate = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($rawDate);
            } else {
                // Jika formatnya String ("2026-01-13")
                $planDate = Carbon::parse($rawDate);
            }
        } catch (\Exception $e) {
            return null; // Skip jika format tanggal error
        }

        // 5. LOGIC HEADER: Cari atau Buat Plan Baru
        // Kita kunci berdasarkan Tanggal, Line, dan Shift
        $planHeader = ProductionPlan::firstOrCreate(
            [
                'plan_date' => $planDate,
                'production_line_id' => $line->id,
                'shift_id' => $row['shift'] ?? 1,
            ],
            [
                'status' => 'DRAFT',
                'created_by' => Auth::id() ?? 1,
            ]
        );

        // 6. LOGIC KALKULASI TPS
        $shiftDuration = 480; // 8 Jam (Standar)
        $effectiveTime = 440; // Potong istirahat
        
        // Hitung ulang semua parameter
        $loadingPct = $this->calculator->calculateMachineLoading($qty, $product->cycle_time, $shiftDuration);
        $manpower = $this->calculator->calculateManPower($qty, $product->cycle_time, $effectiveTime);
        $kanban = $this->calculator->calculateKanbanCards($qty, 0.5, $product->qty_per_box, $product->safety_stock);

        // 7. SIMPAN DETAIL PLAN (Update jika part sudah ada di plan tsb, Create jika belum)
        return ProductionPlanDetail::updateOrCreate(
            [
                'production_plan_id' => $planHeader->id,
                'product_id' => $product->id,
            ],
            [
                'qty_plan' => $qty,
                'calculated_loading_pct' => $loadingPct,
                'calculated_manpower' => $manpower,
                'calculated_kanban_cards' => $kanban,
            ]
        );
    }
}