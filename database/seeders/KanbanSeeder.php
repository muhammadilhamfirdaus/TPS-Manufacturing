<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\KanbanMaster;
use App\Models\KanbanTransaction;
use App\Services\ManufacturingCalculatorService;

class KanbanSeeder extends Seeder
{
    public function run(): void
    {
        $calculator = new ManufacturingCalculatorService();
        $products = Product::all();

        foreach ($products as $product) {
            // Simulasi Demand
            $dailyDemand = 500; // Misal butuh 500 pcs/hari
            $leadTime = 0.5;    // Setengah hari
            
            // 1. Hitung Jumlah Kartu Ideal pakai Service yg sudah kita buat
            $cards = $calculator->calculateKanbanCards(
                $dailyDemand, 
                $leadTime, 
                $product->qty_per_box, 
                $product->safety_stock
            );

            // 2. Buat Master Kanban
            $kanban = KanbanMaster::create([
                'product_id' => $product->id,
                'kanban_type' => 'FG', // Finish Good
                'number_of_cards' => $cards,
                'location_code' => 'RK-01-' . $product->id,
                'daily_demand_forecast' => $dailyDemand,
                'lead_time_days' => $leadTime,
            ]);

            // 3. Isi Stok Awal (Initial Stock)
            // Kita isi stoknya acak biar warnanya beda-beda (Merah/Kuning/Hijau)
            
            // Logic random: 
            // ID 1 kita bikin kritis (sedikit), ID 2 sedang, ID 3 penuh
            $initialBox = match($product->id) {
                1 => 1, // Kritis (Merah)
                2 => floor($cards / 2), // Setengah (Kuning)
                default => $cards // Penuh (Hijau)
            };

            if ($initialBox > 0) {
                KanbanTransaction::create([
                    'kanban_master_id' => $kanban->id,
                    'transaction_type' => 'IN',
                    'qty_box' => $initialBox,
                    'qty_total' => $initialBox * $product->qty_per_box,
                    'user_id' => 1, // Admin
                    'scanned_at' => now(),
                ]);
            }
        }
    }
}