<?php

namespace App\Http\Controllers;

use App\Models\ProductionPlanDetail;
use App\Models\ProductionLine;
use App\Models\Holiday; // Pastikan Model Holiday ada, atau hapus logic hari libur jika tidak pakai
use App\Models\MppAdjustment;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class MppController extends Controller
{
    /**
     * 1. HALAMAN UTAMA (INDEX)
     */
    public function index(Request $request)
    {
        // 1. Filter Input
        $month = $request->get('month', date('m'));
        $year = $request->get('year', date('Y'));

        // 2. Panggil Logic Perhitungan (Sentralisasi Logic)
        // Ini akan mengembalikan array ['groupedMpp', 'totalHoursPerson', 'workDays']
        $calcData = $this->getMppData($month, $year);

        // 3. Ambil Data Adjustment (Helper/Backup) dari Database
        $adjustments = MppAdjustment::where('month', $month)
            ->where('year', $year)
            ->get()
            ->keyBy('production_line_id');

        // 4. Kirim semua data ke View
        return view('mpp.index', array_merge($calcData, [
            'adjustments' => $adjustments,
            'month' => $month,
            'year' => $year
        ]));
    }

    /**
     * 2. FUNGSI SIMPAN ADJUSTMENT (HELPER/BACKUP)
     */
    public function storeAdjustment(Request $request)
    {
        $month = $request->input('month');
        $year = $request->input('year');
        $data = $request->input('adjustments', []);

        foreach ($data as $lineId => $values) {
            // Pastikan nilai integer (cegah null)
            $helper = (int) ($values['helper'] ?? 0);
            $backup = (int) ($values['backup'] ?? 0);
            $absensi = (int) ($values['absensi'] ?? 0);

            // Simpan atau Update
            MppAdjustment::updateOrCreate(
                [
                    'production_line_id' => $lineId,
                    'month' => $month,
                    'year' => $year
                ],
                [
                    'helper' => $helper,
                    'backup' => $backup,
                    'absensi' => $absensi,
                ]
            );
        }

        return back()->with('success', 'Data Adjustment (Helper/Backup) berhasil disimpan!');
    }

    /**
     * 3. EXPORT PDF
     */
    public function exportPdf(Request $request)
    {
        $month = $request->get('month', date('m'));
        $year = $request->get('year', date('Y'));

        // Panggil logic yang SAMA dengan index agar data konsisten
        $data = $this->getMppData($month, $year);

        // Tambahkan variabel extra untuk View PDF
        $data['month'] = $month;
        $data['year'] = $year;

        // Ambil adjustments juga untuk ditampilkan di PDF (Opsional)
        $data['adjustments'] = MppAdjustment::where('month', $month)
            ->where('year', $year)->get()->keyBy('production_line_id');

        $pdf = Pdf::loadView('mpp.pdf', $data);

        return $pdf->setPaper('a4', 'landscape')
            ->download('MPP_Report_' . $year . '_' . $month . '.pdf');
    }

    /**
     * 4. LOGIC PENGOLAH DATA (CORE ENGINE)
     * Digunakan oleh Index dan ExportPdf
     */
    private function getMppData($month, $year)
    {
        // =========================================================
        // A. HITUNG HARI KERJA (REVISI: BACA TABEL HOLIDAY)
        // =========================================================
        $daysInMonth = Carbon::create($year, $month)->daysInMonth;

        // 1. Ambil data libur dari database berdasarkan bulan & tahun terpilih
        $holidays = Holiday::whereMonth('date', $month)
            ->whereYear('date', $year)
            ->pluck('date') // Ambil kolom 'date' saja
            ->toArray();    // Konversi ke array sederhana ['2026-01-01', '2026-01-02']

        $workDays = 0;

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date = Carbon::create($year, $month, $d);
            $dateStr = $date->format('Y-m-d');

            // Syarat Hari Kerja:
            // 1. Bukan Sabtu/Minggu (isWeekday)
            // 2. Tanggal TIDAK ada di dalam array $holidays
            if ($date->isWeekday() && !in_array($dateStr, $holidays)) {
                $workDays++;
            }
        }

        // Fallback: Jika perhitungan 0 (misal error), set default 20. 
        // Tapi jika normal, pakai hasil perhitungan $workDays.
        $workDays = $workDays > 0 ? $workDays : 20;

        $workHoursPerDay = 8;
        $totalHoursPerson = $workDays * $workHoursPerDay;

        // =========================================================
        // B. QUERY DATABASE (SAMA SEPERTI SEBELUMNYA)
        // =========================================================
        $details = ProductionPlanDetail::with([
            'productionPlan.productionLine',
            'product.routings.machine.productionLine'
        ])
            ->whereHas('productionPlan', function ($q) use ($month, $year) {
                $q->whereMonth('plan_date', $month)
                    ->whereYear('plan_date', $year)
                    ->where('status', '!=', 'HISTORY');
            })
            ->get();

        // C. AKUMULASI DATA PER LINE
        $aggregatedData = collect();

        foreach ($details as $detail) {
            if (!$detail->product)
                continue;

            $routings = $detail->product->routings;

            if ($routings->isEmpty()) {
                $line = $detail->productionPlan->productionLine;
                if ($line) {
                    $this->accumulateData($aggregatedData, $line, $detail, null);
                }
            } else {
                foreach ($routings as $routing) {
                    $line = $routing->machine->productionLine ?? null;
                    if ($line) {
                        $this->accumulateData($aggregatedData, $line, $detail, $routing);
                    }
                }
            }
        }

        // D. HITUNG HASIL AKHIR (MPP)
        $mppData = $aggregatedData->map(function ($item) use ($totalHoursPerson) {
            // REVISI RUMUS: Total Jam / Kapasitas Orang (Dinamis sesuai hari kerja)
            $mppMurni = $totalHoursPerson > 0 ? ($item->total_man_hours / $totalHoursPerson) : 0;

            $item->mpp_murni = $mppMurni;
            $item->mpp_aktual = ceil($mppMurni);

            return $item;
        });

        // E. GROUPING DATA
        $groupedMpp = $mppData->sortBy(function ($item) {
            return $item->plant . $item->line_name;
        })->groupBy('plant');

        return compact('groupedMpp', 'totalHoursPerson', 'workDays');
    }

    /**
     * 5. HELPER AKUMULASI DATA
     */
    private function accumulateData($collection, $line, $detail, $routing = null)
    {
        $lineId = $line->id;

        // Ambil parameter teknis (Cycle Time / Pcs Per Hour)
        // Prioritas: Routing -> Product Global
        $pcsPerHour = $routing ? $routing->pcs_per_hour : ($detail->product->pcs_per_hour_global ?? 0);

        // Safety Division by Zero
        if ($pcsPerHour <= 0)
            $pcsPerHour = 1;

        $cycleTime = 3600 / $pcsPerHour; // Detik per pcs

        // Rasio Manpower (1 Mesin dikerjakan berapa orang, atau 1 orang pegang berapa mesin)
        // Default 1
        $mpRatio = $routing ? $routing->manpower_ratio : 1;
        $finalRatio = ($mpRatio > 0) ? $mpRatio : 1;

        // Rumus Jam Mesin = (Qty * CT) / 3600
        $machineHours = ($detail->qty_plan * $cycleTime) / 3600;

        // Rumus Jam Orang = Jam Mesin / Rasio (atau dikali rasio tergantung definisi rasio di DB Anda)
        // Asumsi: mp_ratio 1 = 1 orang 1 mesin. mp_ratio 0.5 = 1 orang 2 mesin (lebih ringan beban orangnya)
        // Jika mp_ratio artinya "Butuh X orang per mesin", maka dikali. 
        // Disini saya pakai asumsi standar: Man Hours = Machine Hours * MP Ratio (Jumlah Orang)
        // Namun kode lama Anda dibagi. Sesuaikan dengan definisi data Anda.
        // Jika mp_ratio = "Jumlah Orang yg dibutuhkan", maka:
        $manHours = $machineHours * $finalRatio;

        // Inisialisasi Object jika Line ID ini belum ada di collection
        if (!$collection->has($lineId)) {
            $collection->put($lineId, (object) [
                'line_id' => $lineId, // <--- PENTING UNTUK FORM INPUT
                'plant' => $line->plant ?? 'OTHER',
                'line_name' => $line->name,
                'keb_jam_kerja' => 0, // Machine Hours
                'total_man_hours' => 0, // Man Hours
                'mpp_murni' => 0,
                'mpp_aktual' => 0,
            ]);
        }

        // Tambahkan value ke object yang sudah ada
        $data = $collection->get($lineId);
        $data->keb_jam_kerja += $machineHours;
        $data->total_man_hours += $manHours;
    }
}