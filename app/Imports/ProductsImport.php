<?php

namespace App\Imports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow; // Agar baris 1 dianggap Header

class ProductsImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // Validasi sederhana: Jika part number kosong, skip
        if (!isset($row['part_number'])) {
            return null;
        }

        // Update jika ada (Upsert), atau Create baru
        return Product::updateOrCreate(
            ['part_number' => $row['part_number']], // Kunci pencarian (biar gak duplikat)
            [
                'part_name'    => $row['part_name'],
                'uom'          => $row['uom'] ?? 'PCS',
                'cycle_time'   => $row['cycle_time'] ?? 0,
                'qty_per_box'  => $row['qty_per_box'] ?? 1,
                'safety_stock' => $row['safety_stock'] ?? 0,
            ]
        );
    }
}