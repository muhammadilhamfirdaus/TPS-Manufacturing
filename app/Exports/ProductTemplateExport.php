<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductTemplateExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    /**
    * 1. Ambil semua data produk dari database
    */
    public function collection()
    {
        return Product::all();
    }

    /**
    * 2. Tentukan Header Kolom (Baris 1 di Excel)
    * Pastikan urutannya SAMA dengan di function map()
    */
    public function headings(): array
    {
        return [
            'part_number',  // Kunci Utama (Jangan diubah user jika ingin update)
            'part_name',
            'uom',
            'cycle_time',
            'qty_per_box',
            'safety_stock',
        ];
    }

    /**
    * 3. Mapping Data Database ke Kolom Excel
    * $product adalah satu baris data dari database
    */
    public function map($product): array
    {
        return [
            $product->part_number,
            $product->part_name,
            $product->uom,
            $product->cycle_time,
            $product->qty_per_box,
            $product->safety_stock,
        ];
    }

    /**
    * 4. Styling (Opsional: Bold Header)
    */
    public function styles(Worksheet $sheet)
    {
        return [
            // Baris 1 di-Bold
            1 => ['font' => ['bold' => true]],
        ];
    }
}