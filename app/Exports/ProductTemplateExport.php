<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductTemplateExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize
{
    public function headings(): array
    {
        return [
            'code_part', 
            'nama_part', 
            'part_number', 
            'customer', 
            'uom', 
            'qty_box', 
            'safety_stock', 
            'flow_label', 
            'urutan_proses', 
            'nama_proses', 
            'nama_mesin', 
            'nama_line', 
            'cap_per_jam', 
            'rasio_mp'
        ];
    }

    public function array(): array
    {
        return [
            // Contoh Baris 1: Part Baru + Proses Pertama
            [
                'CP-001',           // code_part
                'COVER TENSION',    // nama_part
                '14301-74783',      // part_number
                'KUBOTA',           // customer
                'PCS',              // uom
                100,                // qty_box
                50,                 // safety_stock
                'OP10->OP20',       // flow_label
                1,                  // urutan_proses
                'OP10 - BLANKING',  // nama_proses
                'PRESS-100T',       // nama_mesin (Harus sama dgn Master Mesin)
                'LINE A',           // nama_line (Opsional jika mesin kosong)
                500,                // cap_per_jam
                1                   // rasio_mp
            ],
            // Contoh Baris 2: Part SAMA, tapi Proses Kedua (Data Part dikosongi gapapa / disamakan)
            [
                'CP-001',           // code_part (KUNCI: HARUS SAMA)
                'COVER TENSION', 
                '14301-74783', 
                'KUBOTA', 
                'PCS', 
                100, 
                50, 
                'OP10->OP20',
                2,                  // urutan_proses
                'OP20 - BENDING',   // nama_proses
                'PRESS-80T',        // nama_mesin
                'LINE A', 
                450, 
                1
            ],
        ];
    }

    // Bikin Header jadi Tebal (Bold) biar enak dilihat
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}