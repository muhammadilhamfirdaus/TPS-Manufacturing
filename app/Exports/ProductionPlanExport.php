<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductionPlanExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    /**
     * Mengambil Data Produk (Hanya Kategori Finish Good)
     */
    public function collection()
    {
        // FILTER: Ambil produk yang kategorinya 'FINISH GOOD' (atau 'FG')
        // Pastikan nama kolom 'category' sesuai dengan di database Anda (bisa 'category', 'part_category', atau 'type')
        return Product::where('category', 'FINISH GOOD') 
                      ->orderBy('part_name', 'asc')
                      ->get();
    }

    /**
     * Mapping Data Produk ke Kolom Excel
     */
    public function map($product): array
    {
        return [
            $product->code_part,    // Kolom A: CODE_PART
            $product->part_number,  // Kolom B: PART_NUMBER
            $product->part_name,    // Kolom C: PART_NAME
            '',                     // Kolom D: MONTH (User isi)
            '',                     // Kolom E: YEAR (User isi)
            '',                     // Kolom F: QTY (User isi)
        ];
    }

    /**
     * Header Judul Kolom
     */
    public function headings(): array
    {
        return [
            'CODE_PART',    
            'PART_NUMBER',  
            'PART_NAME',    
            'MONTH',        
            'YEAR',         
            'QTY_PLAN',     
        ];
    }

    /**
     * Styling Header
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFFFFF00'] // Kuning
                ]
            ],
        ];
    }
}