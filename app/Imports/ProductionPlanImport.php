<?php

namespace App\Imports;

use App\Models\ProductionPlan;
use App\Models\ProductionPlanDetail;
use App\Models\Product;
use App\Models\ProductionLine;
use App\Services\ManufacturingCalculatorService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProductionPlanImport implements ToCollection, WithHeadingRow
{
    protected $calculator;

    public function __construct()
    {
        // Inisialisasi Service Hitungan secara manual
        $this->calculator = new ManufacturingCalculatorService();
    }

    /**
     * Helper: Ubah input bulan (Jan, Feb, 1, 2) menjadi format Y-m-d
     */
    private function parseDate($monthInput, $yearInput)
    {
        $year = $yearInput ?? date('Y');
        $month = date('m'); // Default bulan ini

        if (is_numeric($monthInput)) {
            $month = (int)$monthInput;
        } else {
            $input = strtolower(trim($monthInput));
            $months = [
                'jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'mei' => 5, 'may' => 5, 'jun' => 6,
                'jul' => 7, 'agu' => 8, 'aug' => 8, 'sep' => 9, 'okt' => 10, 'oct' => 10, 'nov' => 11, 'des' => 12, 'dec' => 12
            ];
            foreach ($months as $key => $val) {
                if (str_contains($input, $key)) {
                    $month = $val;
                    break;
                }
            }
        }

        try {
            return Carbon::createFromDate($year, $month, 1)->format('Y-m-d');
        } catch (\Exception $e) {
            return Carbon::now()->startOfMonth()->format('Y-m-d');
        }
    }

    public function collection(Collection $rows)
    {
        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                // 1. Validasi Dasar
                if (!isset($row['qty_plan']) || empty($row['part_number'])) continue;

                // 2. Cari Produk Parent (FG)
                // Kita cari berdasarkan Part Number atau Code Part agar fleksibel
                $product = Product::where('part_number', $row['part_number'])
                                  ->orWhere('code_part', $row['part_number']) // Jaga-jaga user input code di kolom part no
                                  ->with(['routings.machine', 'bomComponents'])
                                  ->first();

                if (!$product) continue; // Skip jika part tidak ditemukan

                // 3. Tentukan Tanggal Plan
                $planDate = $this->parseDate($row['month'] ?? null, $row['year'] ?? null);

                // 4. Tentukan Line Produksi (Otomatis dari Routing Part)
                $lineId = null;
                if ($product->routings->isNotEmpty()) {
                    $lineId = $product->routings->first()->machine->production_line_id ?? null;
                }
                if (!$lineId) {
                    $firstLine = ProductionLine::first();
                    $lineId = $firstLine ? $firstLine->id : 1;
                }

                // ==========================================================
                // LANGKAH KUNCI: HEADER BARU PER BARIS EXCEL (BATCH)
                // ==========================================================
                // Kita TIDAK pakai firstOrCreate. Kita pakai Create.
                // Agar setiap baris excel jadi Batch Plan terpisah.
                
                $newHeader = ProductionPlan::create([
                    'plan_date'          => $planDate,
                    'production_line_id' => $lineId,
                    'shift_id'           => 1,
                    'status'             => 'DRAFT',
                    'created_by'         => auth()->id() ?? 1
                ]);

                // 5. JALANKAN LOGIC BOM (REKURSIF)
                // Simpan FG, lalu sistem otomatis cari komponennya dan simpan di Batch yg sama
                $this->processRecursive($newHeader->id, $product->id, $row['qty_plan']);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Logic Rekursif: Simpan Induk -> Cari Anak -> Simpan Anak -> Ulangi
     * Menerima $headerId agar semua anak masuk ke Batch yang sama.
     */
    private function processRecursive($headerId, $productId, $qty)
    {
        $product = Product::with(['routings', 'bomComponents'])->find($productId);
        if (!$product) return;

        // Parameter Default Pabrik
        $shiftDuration = 480; // menit
        $effectiveTime = 440; // menit

        // Hitung Rumus (Load, MPP, Kanban)
        $loadingPct   = $this->calculator->calculateMachineLoading($qty, $product->cycle_time, $shiftDuration);
        $manpower     = $this->calculator->calculateManPower($qty, $product->cycle_time, $effectiveTime);
        
        $qtyPerBox    = $product->qty_per_box > 0 ? $product->qty_per_box : 1;
        $safetyStock  = $product->safety_stock ?? 0;
        $kanbanNeeded = $this->calculator->calculateKanbanCards($qty, 0.5, $qtyPerBox, $safetyStock);

        // SIMPAN KE DATABASE
        // Selalu create baru, tidak ada increment/update
        ProductionPlanDetail::create([
            'production_plan_id'      => $headerId,
            'product_id'              => $productId,
            'qty_plan'                => $qty,
            'calculated_loading_pct'  => $loadingPct,
            'calculated_manpower'     => $manpower,
            'calculated_kanban_cards' => $kanbanNeeded
        ]);

        // CEK BOM (ANAK-ANAKNYA)
        // Jika punya komponen, looping dan simpan juga
        if ($product->bomComponents->isNotEmpty()) {
            foreach ($product->bomComponents as $child) {
                // Hitung Qty Anak = Qty Induk * Usage per Unit
                $childQty = $qty * $child->pivot->quantity;

                // Panggil fungsi ini lagi untuk si Anak (Recursion)
                $this->processRecursive($headerId, $child->id, $childQty);
            }
        }
    }
}