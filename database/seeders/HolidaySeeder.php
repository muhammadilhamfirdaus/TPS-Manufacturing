<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Holiday;

class HolidaySeeder extends Seeder
{
    public function run()
    {
        // Daftar Libur Nasional 2025 & 2026 (Data Riset)
        $holidays = [
            // --- 2025 ---
            ['date' => '2025-01-01', 'description' => 'Tahun Baru 2025'],
            ['date' => '2025-01-27', 'description' => 'Isra Mikraj'],
            ['date' => '2025-01-29', 'description' => 'Tahun Baru Imlek'],
            ['date' => '2025-03-29', 'description' => 'Hari Suci Nyepi'],
            ['date' => '2025-03-31', 'description' => 'Idul Fitri 1446 H'],
            ['date' => '2025-04-01', 'description' => 'Idul Fitri 1446 H'],
            ['date' => '2025-04-18', 'description' => 'Wafat Yesus Kristus'],
            ['date' => '2025-04-20', 'description' => 'Paskah'],
            ['date' => '2025-05-01', 'description' => 'Hari Buruh'],
            ['date' => '2025-05-12', 'description' => 'Hari Raya Waisak'],
            ['date' => '2025-05-29', 'description' => 'Kenaikan Yesus Kristus'],
            ['date' => '2025-06-01', 'description' => 'Hari Lahir Pancasila'],
            ['date' => '2025-06-06', 'description' => 'Idul Adha 1446 H'],
            ['date' => '2025-06-27', 'description' => 'Tahun Baru Islam 1447 H'],
            ['date' => '2025-08-17', 'description' => 'HUT RI Ke-80'],
            ['date' => '2025-09-05', 'description' => 'Maulid Nabi Muhammad SAW'],
            ['date' => '2025-12-25', 'description' => 'Hari Raya Natal'],

            // --- 2026 ---
            ['date' => '2026-01-01', 'description' => 'Tahun Baru 2026'],
            ['date' => '2026-01-02', 'description' => 'libur Tahun Baru 2026'],
            ['date' => '2026-01-16', 'description' => 'Isra Mikraj'],
            ['date' => '2026-02-17', 'description' => 'Tahun Baru Imlek'],
            ['date' => '2026-03-19', 'description' => 'Hari Suci Nyepi'],
            ['date' => '2026-03-21', 'description' => 'Idul Fitri 1447 H'],
            ['date' => '2026-03-22', 'description' => 'Idul Fitri 1447 H'],
            ['date' => '2026-04-03', 'description' => 'Wafat Yesus Kristus'],
            ['date' => '2026-04-05', 'description' => 'Paskah'],
            ['date' => '2026-05-01', 'description' => 'Hari Buruh'],
            ['date' => '2026-05-14', 'description' => 'Kenaikan Yesus Kristus'],
            ['date' => '2026-05-27', 'description' => 'Idul Adha 1447 H'],
            ['date' => '2026-05-31', 'description' => 'Hari Raya Waisak'],
            ['date' => '2026-06-01', 'description' => 'Hari Lahir Pancasila'],
            ['date' => '2026-06-16', 'description' => 'Tahun Baru Islam 1448 H'],
            ['date' => '2026-08-17', 'description' => 'HUT RI Ke-81'],
            ['date' => '2026-08-25', 'description' => 'Maulid Nabi Muhammad SAW'],
            ['date' => '2026-12-25', 'description' => 'Hari Raya Natal'],
        ];

        foreach ($holidays as $h) {
            Holiday::updateOrCreate(['date' => $h['date']], $h);
        }
    }
}

// cara jalanin seeder ini = php artisan db:seed --class=HolidaySeeder