<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Machine;
use App\Models\ProductionLine;
use App\Models\ProductRouting;

class RoutingSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Pastikan Line & Mesin ada (Sesuai kode aset Anda)
        $line = ProductionLine::firstOrCreate(['name' => 'LINE STAMPING P-2']);
        
        // Mesin 1: 11-P45-43 (Pierce Blank)
        $machine1 = Machine::firstOrCreate(
            ['machine_code' => '11-P45-43'],
            ['name' => 'P2-1', 'production_line_id' => $line->id, 'machine_group' => '45 TON', 'capacity_per_hour' => 500]
        );

        // Mesin 2: 11-P45-32 (Stamping)
        $machine2 = Machine::firstOrCreate(
            ['machine_code' => '11-P45-32'],
            ['name' => 'P2-3', 'production_line_id' => $line->id, 'machine_group' => '45 TON', 'capacity_per_hour' => 500]
        );

        // 2. Buat Produk KYB FW-052
        $product = Product::create([
            'part_number' => 'KYB-FW-052',
            'part_name' => 'BRACKET LOWER',
            'flow_process' => 'PIERCE -> STAMP', // Info text
            'cycle_time' => 8.5,
            'qty_per_box' => 50,
            'safety_stock' => 200
        ]);

        // 3. SETTING ROUTING (KUNCI OTOMATISNYA DISINI)
        // Proses 1: Pierce Blank di Mesin 1
        ProductRouting::create([
            'product_id' => $product->id,
            'machine_id' => $machine1->id,
            'process_name' => 'PIERCE BLANK'
        ]);

        // Proses 2: Stamping di Mesin 2
        ProductRouting::create([
            'product_id' => $product->id,
            'machine_id' => $machine2->id,
            'process_name' => 'STAMPING'
        ]);
        
        // 4. Buat Contoh Plan biar langsung muncul di report
        $plan = \App\Models\ProductionPlan::create([
            'plan_date' => now(), 'production_line_id' => $line->id, 
            'shift_id' => 1, 'status' => 'APPROVED', 'created_by' => 1
        ]);
        
        $plan->details()->create([
            'product_id' => $product->id,
            'qty_plan' => 3000, // Kita coba plan 3000 pcs
            'calculated_loading_pct' => 0, 'calculated_manpower' => 0
        ]);
    }
}