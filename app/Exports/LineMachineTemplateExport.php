<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LineMachineTemplateExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize
{
    public function headings(): array
    {
        return [
            'plant',          
            'nama_line',      
            'jumlah_shift',   
            'nama_mesin',     
            'tipe_mesin',     
            'kode_aset',
            'group'           // <--- GANTI MAKER/TAHUN JADI GROUP
        ];
    }

    public function array(): array
    {
        return [
            [
                'PLANT 1',              
                'LINE STAMPING A',      
                3,                      
                'PRESS 100T #1',        
                'PRESS',                
                'AST-100-01',           
                'STAMPING' // Contoh isi Group
            ],
            [
                'PLANT 1', 
                'LINE STAMPING A',      
                3, 
                'PRESS 100T #2', 
                'PRESS', 
                'AST-100-02', 
                'STAMPING'
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