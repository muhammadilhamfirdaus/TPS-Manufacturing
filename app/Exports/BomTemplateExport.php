<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BomTemplateExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize
{
    public function headings(): array
    {
        return [
            'parent_part_code', // Kode Barang Jadi (Finish Good)
            'child_part_code',  // Kode Komponen (Material)
            'qty_usage',        // Jumlah Pemakaian
        ];
    }

    public function array(): array
    {
        return [
            // Contoh 1: Membuat BOM untuk CP-001
            [
                'CP-001',       // Parent: COVER TENSION
                'MAT-STEEL-01', // Child: PLAT BESI
                1               // Butuh 1 PCS
            ],
            // Contoh 2: Komponen kedua untuk CP-001
            [
                'CP-001',       // Parent: COVER TENSION (Sama)
                'BOLT-M8',      // Child: BAUT M8
                4               // Butuh 4 PCS
            ],
            // Contoh 3: BOM Produk Lain
            [
                'FG-X99', 
                'MAT-PLASTIC', 
                0.5
            ],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]], 
        ];
    }
}