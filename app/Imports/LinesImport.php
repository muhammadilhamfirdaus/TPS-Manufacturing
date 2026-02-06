<?php

namespace App\Imports;

use App\Models\ProductionLine;
use App\Models\Machine;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\DB;

class LinesImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                // Skip jika nama line kosong
                if (empty($row['nama_line'])) continue;

                // 1. CEK / BUAT LINE (PARENT)
                $line = ProductionLine::updateOrCreate(
                    ['name' => $row['nama_line']], 
                    [
                        'plant'         => $row['plant'] ?? 'PLANT 1',
                        'total_shifts'  => $row['jumlah_shift'] ?? 3,
                        'std_manpower'  => 0, // Default nilai agar tidak error 1364
                    ]
                );

                // 2. CEK / BUAT MESIN (CHILD)
                if (!empty($row['nama_mesin'])) {
                    Machine::updateOrCreate(
                        [
                            'name' => $row['nama_mesin'], 
                            'production_line_id' => $line->id
                        ],
                        [
                            'type'              => $row['tipe_mesin'] ?? 'General',
                            
                            // --- BAGIAN INI YANG DIPERBAIKI (SESUAIKAN DENGAN MODEL) ---
                            'machine_code'      => $row['kode_aset'] ?? null,  // Masuk ke machine_code
                            'machine_group'     => $row['group'] ?? null,      // Masuk ke machine_group
                            // -----------------------------------------------------------

                            'capacity_per_hour' => 0, 
                        ]
                    );
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e; // Lempar error agar tertangkap debug controller
        }
    }
}