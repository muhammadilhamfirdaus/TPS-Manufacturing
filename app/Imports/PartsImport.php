<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\ProductRouting;
use App\Models\Machine;
use App\Models\ProductionLine;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\DB;

class PartsImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                // Skip jika baris kosong / tidak ada code_part
                if (!isset($row['code_part']) || empty($row['code_part'])) continue;

                // ======================================================
                // 1. CARI ATAU BUAT PART (Tanpa Reset Cycle Time)
                // ======================================================
                $product = Product::where('code_part', $row['code_part'])->first();

                // Data Master Part dari Excel
                $partData = [
                    'part_name'     => $row['nama_part'],
                    'part_number'   => $row['part_number'] ?? '-',
                    'customer'      => $row['customer'] ?? 'General',
                    'uom'           => $row['uom'] ?? 'PCS',
                    'qty_per_box'   => $row['qty_box'] ?? 1,
                    'safety_stock'  => $row['safety_stock'] ?? 0,
                    'flow_process'  => $row['flow_label'] ?? null,
                ];

                if (!$product) {
                    // Jika Part Baru, Create dengan Cycle Time default 0
                    $partData['code_part']  = $row['code_part'];
                    $partData['cycle_time'] = 0; // Default awal
                    $product = Product::create($partData);
                } else {
                    // Jika Part Lama, Update data master TAPI JANGAN RESET cycle_time
                    $product->update($partData);
                }

                // ======================================================
                // 2. LOGIKA PINTAR CARI MESIN & LINE (AUTO PLANT)
                // ======================================================
                $machineId = null;
                $lineId    = null;
                $plantName = null;

                // A. Cari Berdasarkan Nama Mesin (Prioritas Utama)
                if (!empty($row['nama_mesin'])) {
                    // Coba cari persis dulu
                    $machine = Machine::where('name', $row['nama_mesin'])->first();
                    
                    // Kalau gak ketemu, cari yang MIRIP (LIKE)
                    if (!$machine) {
                        $machine = Machine::where('name', 'LIKE', '%'.$row['nama_mesin'].'%')->first();
                    }

                    if ($machine) {
                        $machineId = $machine->id;
                        $lineId    = $machine->production_line_id;
                        
                        // AMBIL PLANT DARI RELASI LINE
                        if ($machine->productionLine) {
                            $plantName = $machine->productionLine->plant;
                        }
                    }
                }

                // B. Cari Berdasarkan Nama Line (Jika Mesin Gak Ketemu)
                if (!$lineId && !empty($row['nama_line'])) {
                    $line = ProductionLine::where('name', $row['nama_line'])->first();
                    
                    // Cari mirip juga
                    if (!$line) {
                        $line = ProductionLine::where('name', 'LIKE', '%'.$row['nama_line'].'%')->first();
                    }

                    if ($line) {
                        $lineId    = $line->id;
                        $plantName = $line->plant; // Ambil Plant
                    }
                }

                // ======================================================
                // 3. UPDATE ROUTING & ISI PLANT
                // ======================================================
                ProductRouting::updateOrCreate(
                    [
                        'product_id'   => $product->id,
                        'process_name' => $row['nama_proses'],
                    ],
                    [
                        'machine_id'         => $machineId,
                        'production_line_id' => $lineId,
                        'plant'              => $plantName, // <--- INI KUNCI AGAR TIDAK KOSONG
                        'pcs_per_hour'       => $row['cap_per_jam'] ?? 0,
                        'manpower_ratio'     => $row['rasio_mp'] ?? 1,
                        'seq'                => $row['urutan_proses'] ?? 1,
                    ]
                );

                // ======================================================
                // 4. HITUNG CYCLE TIME PART (BOTTLENECK)
                // ======================================================
                // Logic: Cycle Time Part adalah proses yang PALING LAMBAT (CT Paling Besar)
                
                $pcsPerHour = (int) ($row['cap_per_jam'] ?? 0);
                
                // Hitung CT proses ini (3600 / Kapasitas)
                $currentProcessCT = ($pcsPerHour > 0) ? (3600 / $pcsPerHour) : 0;

                // Jika CT proses ini LEBIH LAMBAT dari CT yang sudah ada di Part, Update Part-nya
                if ($currentProcessCT > $product->cycle_time) {
                    $product->update(['cycle_time' => $currentProcessCT]);
                }
            }
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}