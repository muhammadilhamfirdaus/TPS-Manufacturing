<?php

namespace App\Services;

class ManufacturingCalculatorService
{
    /**
     * 1. Hitung MACHINE LOADING (%)
     * Seberapa sibuk mesin/line? Jika > 100% berarti Overload (Butuh Lembur).
     * Rumus: (Total Waktu yang Dibutuhkan / Total Waktu Tersedia) * 100
     * * @param int $qtyPlan Target produksi (pcs)
     * @param float $cycleTimeSec Waktu bikin 1 barang (detik)
     * @param int $availableTimeMinutes Waktu kerja shift (menit)
     */
    public function calculateMachineLoading(int $qtyPlan, float $cycleTimeSec, int $availableTimeMinutes): float
    {
        // Cegah pembagian dengan nol
        if ($availableTimeMinutes <= 0) return 0;

        // Konversi semua ke DETIK agar satuan sama
        $availableSeconds = $availableTimeMinutes * 60;
        
        // Total detik yang dibutuhkan untuk menyelesaikan target
        $requiredSeconds = $qtyPlan * $cycleTimeSec;

        // Hitung persentase
        $loading = ($requiredSeconds / $availableSeconds) * 100;

        // Return hasil (di-round 2 desimal, misal: 95.50)
        return round($loading, 2);
    }

    /**
     * 2. Hitung MAN POWER PLANNING (MPP)
     * Berapa orang yang dibutuhkan? Mendukung Multi-Process Handling (Rasio).
     * * @param int $qtyPlan Target produksi
     * @param float $cycleTimeSec Cycle time (detik)
     * @param int $effectiveWorkMinutes Waktu kerja orang dikurangi istirahat (menit)
     * @param float $ratio (Optional) Rasio Orang:Mesin. Default 1. (Contoh: 2 artinya 1 orang pegang 2 mesin)
     * @return float Mengembalikan desimal (misal 0.5 orang), agar bisa dijumlahkan di total Line.
     */
    public function calculateManPower(int $qtyPlan, float $cycleTimeSec, int $effectiveWorkMinutes, float $ratio = 1.0): float
    {
        if ($effectiveWorkMinutes <= 0) return 0;

        $effectiveSeconds = $effectiveWorkMinutes * 60;

        // 1. Total beban kerja murni dalam detik
        $totalWorkLoad = $qtyPlan * $cycleTimeSec;

        // 2. Hitung kebutuhan orang jika 1:1 (Beban Kerja / Waktu Tersedia)
        $rawManpower = $totalWorkLoad / $effectiveSeconds;

        // 3. Bagi dengan Rasio Handling (Multi-Process)
        // Jika ratio = 2 (1 orang pegang 2 mesin), maka beban orang di mesin ini dibagi 2.
        $finalRatio = ($ratio > 0) ? $ratio : 1;
        $finalManpower = $rawManpower / $finalRatio;

        // PENTING: Kita return Float (misal 0.5), jangan di-Ceil dulu.
        // Ceil dilakukan nanti saat menjumlahkan Total Manpower satu Batch/Line.
        return round($finalManpower, 2);
    }

    /**
     * 3. Hitung KANBAN (Jumlah Kartu)
     * Berapa box stok yang harus disiapkan?
     * Rumus: (Daily Demand * Lead Time * Safety Factor) / Qty Per Box
     */
    public function calculateKanbanCards(float $dailyDemand, float $leadTimeDays, int $qtyPerBox, float $safetyStock = 0): int
    {
        if ($qtyPerBox <= 0) return 0;

        // Kebutuhan dasar selama lead time
        $baseDemand = $dailyDemand * $leadTimeDays;
        
        // Tambahkan safety stock (buffer)
        $totalNeed = $baseDemand + $safetyStock;

        // Bagi dengan isi per box
        $cards = $totalNeed / $qtyPerBox;

        // Pembulatan ke atas (lebih baik sisa sedikit daripada kurang)
        return (int) ceil($cards);
    }
}