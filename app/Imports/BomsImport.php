<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Bom; 
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\DB;

class BomsImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                // Validasi data kosong
                if (empty($row['parent_part_code']) || empty($row['child_part_code'])) {
                    continue; 
                }

                // Cari ID Product
                $parent = Product::where('code_part', $row['parent_part_code'])->first();
                $child  = Product::where('code_part', $row['child_part_code'])->first();

                // Skip jika part tidak ditemukan
                if (!$parent || !$child) continue; 

                // --- BAGIAN INI YANG DIUBAH SESUAI TABEL 'bom_details' ---
                Bom::updateOrCreate(
                    [
                        // Kunci pencarian (WHERE)
                        'parent_product_id' => $parent->id, 
                        'child_product_id'  => $child->id,
                    ],
                    [
                        // Data yang diupdate/insert
                        'quantity' => $row['qty_usage'] ?? 0, 
                    ]
                );
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}