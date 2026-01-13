<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductionLine;
use App\Models\Machine;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat Line Produksi
        // Line A: Padat Karya (Banyak orang, manual)
        $lineA = ProductionLine::create([
            'name' => 'Line Assembling A',
            'std_manpower' => 8, // Butuh 8 orang standar
        ]);

        // Line B: Otomatis (Sedikit orang, mesin canggih)
        $lineB = ProductionLine::create([
            'name' => 'Line Machining B',
            'std_manpower' => 2, // Cuma butuh 2 operator
        ]);

        // 2. Buat Mesin & Assign ke Line
        Machine::create([
            'production_line_id' => $lineA->id,
            'name' => 'Conveyor Assy 01',
            'capacity_per_hour' => 100, // Bisa lewat 100 unit/jam
        ]);

        Machine::create([
            'production_line_id' => $lineB->id,
            'name' => 'CNC Milling Brother',
            'capacity_per_hour' => 60, // Proses lama, cuma 60 unit/jam
        ]);
        
        Machine::create([
            'production_line_id' => $lineB->id,
            'name' => 'Drilling Machine',
            'capacity_per_hour' => 120,
        ]);

        // 3. Buat Data Produk (Parts)
        // Kasus 1: Part Besar, Cycle Time Cepat
        Product::create([
            'part_number' => 'COVER-ENG-001',
            'part_name' => 'Cover Engine Front',
            'uom' => 'PCS',
            'cycle_time' => 30.5, // 30.5 detik per pcs
            'qty_per_box' => 5,   // Barang besar, 1 box cuma muat 5
            'safety_stock' => 50, // Buffer 50 pcs
        ]);

        // Kasus 2: Part Kecil, Cycle Time Lama (Rumit)
        Product::create([
            'part_number' => 'GEAR-BOX-X10',
            'part_name' => 'Gear Transmission S',
            'uom' => 'PCS',
            'cycle_time' => 120, // 2 menit per pcs
            'qty_per_box' => 50, // Barang kecil, 1 box muat 50
            'safety_stock' => 200,
        ]);
        
        // Kasus 3: Part Fast Moving
        Product::create([
            'part_number' => 'BOLT-M10-HEX',
            'part_name' => 'Bolt M10 Hexagon',
            'uom' => 'KG', // Satuan Kilo
            'cycle_time' => 0.5, // Sangat cepat
            'qty_per_box' => 1000, 
            'safety_stock' => 5000,
        ]);
    }
}