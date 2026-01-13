<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LoadingReportExport implements FromView, ShouldAutoSize, WithStyles
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function view(): View
    {
        // Kita gunakan view khusus yang hanya berisi TABEL (tanpa navbar/tombol)
        return view('plans.loading_report_table_only', $this->data);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style Header Bold
            1 => ['font' => ['bold' => true, 'size' => 14]], 
        ];
    }
}