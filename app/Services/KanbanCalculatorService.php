<?php

namespace App\Services;

class KanbanCalculatorService
{
    /**
     * Rumus Kanban: N = (D * L * (1 + SS)) / C
     * D = Daily Demand (Rata-rata pemakaian harian)
     * L = Lead Time (Waktu tunggu)
     * SS = Safety Stock (Bisa % atau Qty tetap)
     * C = Container Capacity (Qty per Box)
     */
    public function calculate($totalOrder, $workDays, $leadTime, $qtyPerBox, $safetyStock)
    {
        // 1. Hitung Daily Plan (Demand Harian)
        $dailyPlan = $workDays > 0 ? ($totalOrder / $workDays) : 0;

        // 2. Hitung Kebutuhan Stock (Pipeline Stock)
        // Rumus sederhana: (Daily * Lead Time) + Safety Stock
        $requiredStock = ($dailyPlan * $leadTime) + $safetyStock;

        // 3. Hitung Jumlah Kartu Kanban
        // (Kebutuhan Stock / Isi per Box) -> Dibulatkan ke atas (Ceiling)
        $kanbanCards = $qtyPerBox > 0 ? ceil($requiredStock / $qtyPerBox) : 0;

        return [
            'daily_plan'     => $dailyPlan,
            'required_stock' => $requiredStock,
            'kanban_cards'   => $kanbanCards,
            'total_qty'      => $kanbanCards * $qtyPerBox // Total barang fisik
        ];
    }
}