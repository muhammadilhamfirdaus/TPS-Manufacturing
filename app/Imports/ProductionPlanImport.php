<?php

namespace App\Imports;

use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\Http\Controllers\ProductionPlanController; 

class ProductionPlanImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        // Kita butuh akses ke Controller untuk menjalankan logika MRP (Generate Plan)
        // Cara praktis memanggil method public di controller dari sini:
        $controller = app(ProductionPlanController::class);

        foreach ($rows as $row) {
            // 1. Validasi Data Dasar
            // Pastikan kolom excel sesuai (library excel biasanya convert spasi jadi _)
            $codePart = $row['code_part_wajib_unik'] ?? $row['code_part'] ?? null;
            $planDate = $row['plan_date_yyyy_mm_dd'] ?? $row['plan_date'] ?? null;
            $qtyPlan  = $row['qty_plan'] ?? 0;

            // Jika baris kosong atau Qty 0, skip saja
            if (!$codePart || !$planDate || $qtyPlan <= 0) {
                continue;
            }

            // 2. Cari Produk Berdasarkan CODE PART (Unik)
            $product = Product::where('code_part', $codePart)->first();

            if ($product) {
                // 3. Jalankan Logic MRP / Plan
                // Kita buat method public helper di controller atau panggil logic generate
                // Untuk amannya, kita panggil logic via Controller instance
                
                // Variabel dummy untuk log
                $log = []; 
                
                // Panggil method generateMrp di Controller
                // Pastikan visibility method generateMrp di Controller diubah jadi 'public' 
                // atau copy logicnya kesini (rekomendasi: ubah method controller jadi public)
                $controller->generateMrpPublic($product->id, $qtyPlan, \Carbon\Carbon::parse($planDate), $log);
            }
        }
    }
}