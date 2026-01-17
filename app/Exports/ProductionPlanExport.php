<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductionPlanExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $type;

    // Terima parameter tipe (empty / set_data)
    public function __construct($type = 'empty')
    {
        $this->type = $type;
    }

    /**
    * Mengambil Data
    */
    public function collection()
    {
        if ($this->type === 'set_data') {
            // Ambil semua produk yang aktif untuk dilist di Excel
            // Urutkan berdasarkan Nama Part agar rapi
            return Product::orderBy('part_name', 'asc')->get();
        }

        // Jika tipe 'empty', kembalikan collection kosong (hanya header nanti)
        return collect([]);
    }

    /**
    * Mapping Data ke Kolom Excel
    */
    public function map($product): array
    {
        // Jika Set Data, kita isi kolom identitas, biarkan kolom Plan kosong
        return [
            $product->code_part,    // A: Code Part (Primary Key buat sistem)
            $product->part_number,  // B: Part Number (Info user)
            $product->part_name,    // C: Part Name (Info user)
            '',                     // D: PLAN DATE (User isi sendiri)
            '',                     // E: QTY PLAN (User isi sendiri)
        ];
    }

    /**
    * Header Judul Kolom
    */
    public function headings(): array
    {
        return [
            'CODE PART (Wajib Unik)',
            'PART NUMBER',
            'PART NAME',
            'PLAN DATE (YYYY-MM-DD)',
            'QTY PLAN',
        ];
    }

    /**
    * Styling agar Header Tebal & Kuning (Optional biar cantik)
    */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style baris pertama (Header)
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFFF00']]],
        ];
    }
}