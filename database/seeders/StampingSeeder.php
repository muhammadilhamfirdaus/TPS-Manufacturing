<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductionLine;
use App\Models\Machine;
use App\Models\Product;
use App\Models\ProductionPlan;

class StampingSeeder extends Seeder
{
    public function run(): void
    {
        // ==========================================
        // SKENARIO 1: STAMPING (Group by TON)
        // ==========================================
        $lineStamp = ProductionLine::create(['name' => 'LINE STAMPING P-2', 'std_manpower' => 4]);

        // Group 45 TON
        $this->createMachine($lineStamp->id, 'P2-1', '11-P45-43', '45 TON');
        $this->createMachine($lineStamp->id, 'P2-2', '11-P45-40', '45 TON');
        
        // Group 25 TON
        $this->createMachine($lineStamp->id, 'P2-6', '11-P25-10', '25 TON');
        $this->createMachine($lineStamp->id, 'P2-7', '11-P25-09', '25 TON');

        // ==========================================
        // SKENARIO 2: WELDING (Group by TIPE) - Bukti Dinamis
        // ==========================================
        $lineWeld = ProductionLine::create(['name' => 'LINE WELDING A', 'std_manpower' => 3]);

        // Group ROBOT
        $this->createMachine($lineWeld->id, 'ROBOT-01', 'WLD-R-01', 'ROBOT WELD');
        $this->createMachine($lineWeld->id, 'ROBOT-02', 'WLD-R-02', 'ROBOT WELD');

        // Group MANUAL
        $this->createMachine($lineWeld->id, 'MANUAL-01', 'WLD-M-01', 'MANUAL JIG');

        // --- Dummy Product & Plan (Biar tabel gak kosong) ---
        $this->createDummyPlan($lineStamp->id, 'STP-001', 'BRACKET STAMPING');
        $this->createDummyPlan($lineWeld->id, 'WLD-001', 'FRAME WELDING');
    }

    private function createMachine($lineId, $name, $code, $group)
    {
        Machine::create([
            'production_line_id' => $lineId,
            'name' => $name,
            'machine_code' => $code,
            'machine_group' => $group, // INI KUNCINYA (Dinamis)
            'capacity_per_hour' => 100
        ]);
    }

    private function createDummyPlan($lineId, $partNo, $partName)
    {
        $prod = Product::create([
            'part_number' => $partNo, 'part_name' => $partName, 
            'cycle_time' => 10, 'qty_per_box' => 50, 'safety_stock' => 100
        ]);
        
        $plan = ProductionPlan::create([
            'plan_date' => now(), 'production_line_id' => $lineId, 
            'shift_id' => 1, 'status' => 'APPROVED', 'created_by' => 1
        ]);

        $plan->details()->create([
            'product_id' => $prod->id, 'qty_plan' => 1000, 
            'calculated_loading_pct' => 50, 'calculated_manpower' => 2
        ]);
    }
}