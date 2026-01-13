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
     * Ambil Data Master Product, bukan Data Plan
     */
    public function collection()
    {
        // Urutkan berdasarkan nama part agar mudah dicari planner
        return Product::orderBy('part_name', 'asc')->get();
    }

    public function headings(): array
    {
        return [
            'Plan Date (YYYY-MM-DD)', // Kosong (User isi)
            'Line Name',              // Kosong (User isi)
            'Shift',                  // Kosong (User isi)
            'Part Number',            // TERISI OTOMATIS
            'Part Name',              // TERISI OTOMATIS
            'Qty Plan',               // Kosong (User isi)
        ];
    }

    /**
     * Mapping: Kolom kiri kosong, Kolom kanan isi data Master
     */
    public function map($product): array
    {
        return [
            '', // Plan Date dibiarkan kosong
            '', // Line Name dibiarkan kosong
            '', // Shift dibiarkan kosong
            $product->part_number, // Pre-filled dari Master
            $product->part_name,   // Pre-filled dari Master
            '', // Qty Plan dibiarkan kosong
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Bold Header
            1 => ['font' => ['bold' => true]],
            
            // Beri warna kuning muda pada kolom yang WAJIB diisi user (A, B, C, F)
            'A1:C1' => ['fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FFFFE0']]],
            'F1'    => ['fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FFFFE0']]],
            
            // Beri warna abu-abu pada kolom Read-only (D, E) biar user tau itu otomatis
            'D1:E1' => ['fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'EEEEEE']]],
        ];
    }
}