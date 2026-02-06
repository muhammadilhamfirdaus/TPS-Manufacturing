<?php

namespace App\Http\Controllers;

use App\Models\ProductionPlan;
use App\Models\ProductionPlanDetail;
use App\Models\ProductionActual;
use App\Models\DailyPlan;
use App\Models\ProductionLine;
use App\Models\Holiday;
use App\Models\KanbanReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProductionActualController extends Controller
{
    // =================================================================
    // 1. INDEX: TAMPILAN MATRIX INPUT (OPERATOR)
    // =================================================================
    public function index(Request $request)
    {
        // 1. FILTER WAKTU
        $selectedMonth = $request->get('filter_month', \Carbon\Carbon::now()->month);
        $selectedYear = $request->get('filter_year', \Carbon\Carbon::now()->year);

        // 2. FILTER PLANT
        $plants = \App\Models\ProductionLine::select('plant')->distinct()->orderBy('plant')->pluck('plant');
        $selectedPlant = $request->get('plant', $plants->first());

        // 3. FILTER LINE (Tetap pertahankan variabel $allLines untuk JS)
        $allLines = \App\Models\ProductionLine::orderBy('name')->get();
        $lines = $allLines->where('plant', $selectedPlant);
        $lineId = $request->get('line_id', $lines->first()->id ?? 0);

        // =========================================================================
        // 4. LOGIC MATRIX DATA (REVISI: TAMPILKAN SEMUA PART DI LINE)
        // =========================================================================
        $matrixData = collect();

        if ($lineId) {
            // A. Ambil SEMUA Product yang routing-nya ada di Line ini
            $productsOnLine = \App\Models\Product::whereHas('routings', function ($q) use ($lineId) {
                $q->where('production_line_id', $lineId);
            })
                ->whereNull('deleted_at')
                ->orderBy('code_part', 'asc')
                ->get();

            // B. Ambil Data Plan yang SUDAH ADA (Existing)
            $existingPlans = \App\Models\ProductionPlanDetail::with(['product', 'productionPlan'])
                ->whereHas('productionPlan', function ($q) use ($selectedMonth, $selectedYear) {
                    $q->whereMonth('plan_date', $selectedMonth)
                        ->whereYear('plan_date', $selectedYear)
                        ->where('status', '!=', 'HISTORY');
                })
                ->whereHas('product.routings', function ($q) use ($lineId) {
                    $q->where('production_line_id', $lineId);
                })
                ->get()
                ->groupBy(function ($item) {
                    return trim($item->product->code_part);
                });

            // C. Gabungkan (Merge): Loop semua product, cek apakah ada plan?
            foreach ($productsOnLine as $prod) {
                $code = trim($prod->code_part);

                if (isset($existingPlans[$code])) {
                    // KASUS 1: Part ini punya Plan -> Pakai data plan asli
                    $matrixData[$code] = $existingPlans[$code];
                } else {
                    // KASUS 2: Part ini TIDAK punya Plan -> Buat Data Dummy (Target 0)
                    // Kita butuh object seolah-olah ini ProductionPlanDetail agar View tidak error
                    $dummyDetail = new \App\Models\ProductionPlanDetail();
                    $dummyDetail->product = $prod; // Pasang relasi product manual
                    $dummyDetail->qty_plan = 0;    // Target 0

                    // Masukkan ke collection (dibungkus collect karena view meloop grouping)
                    $matrixData[$code] = collect([$dummyDetail]);
                }
            }
        }

        // Ambil daftar Code Part yang valid (sekarang mencakup yang targetnya 0)
        $validCodes = $matrixData->keys()->toArray();

        // 5. DATA TARGET HARIAN (DailyPlan)
        $dailyPlanData = [];
        if (!empty($validCodes)) {
            $rawDaily = \App\Models\DailyPlan::whereIn('code_part', $validCodes)
                ->whereMonth('plan_date', $selectedMonth)
                ->whereYear('plan_date', $selectedYear)
                ->get();

            foreach ($rawDaily as $dPlan) {
                $code = trim($dPlan->code_part);
                $day = \Carbon\Carbon::parse($dPlan->plan_date)->day;
                $dailyPlanData[$code][$day] = $dPlan->qty;
            }
        }

        // 6. DATA ACTUAL (KANBAN REPORT) - Part tanpa plan tetap bisa input actual
        $actualData = [];
        if (!empty($validCodes)) {
            $kanbanReports = \App\Models\KanbanReport::whereIn('code_part', $validCodes)
                ->whereMonth('report_date', $selectedMonth)
                ->whereYear('report_date', $selectedYear)
                ->get();

            foreach ($kanbanReports as $rep) {
                $code = trim($rep->code_part);
                $day = \Carbon\Carbon::parse($rep->report_date)->day;
                $totalAct = ($rep->act_shift_1 ?? 0) + ($rep->act_shift_2 ?? 0);

                if (isset($actualData[$code][$day])) {
                    $actualData[$code][$day] += $totalAct;
                } else {
                    $actualData[$code][$day] = $totalAct;
                }
            }
        }

        // 7. KALKULASI HARI KERJA & LIBUR
        $holidays = \App\Models\Holiday::whereMonth('date', $selectedMonth)
            ->whereYear('date', $selectedYear)
            ->pluck('date')
            ->toArray();

        $daysInMonth = \Carbon\Carbon::create($selectedYear, $selectedMonth)->daysInMonth;
        $totalWorkingDays = 0;

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dt = \Carbon\Carbon::create($selectedYear, $selectedMonth, $d);
            if (!$dt->isWeekend() && !in_array($dt->format('Y-m-d'), $holidays)) {
                $totalWorkingDays++;
            }
        }

        // 8. LOGIC DELAY
        $delayData = [];
        if (!empty($validCodes)) {
            $prevDate = \Carbon\Carbon::create($selectedYear, $selectedMonth, 1)->subMonth();
            $pMonth = $prevDate->month;
            $pYear = $prevDate->year;

            $prevPlans = \App\Models\DailyPlan::whereIn('code_part', $validCodes)
                ->whereMonth('plan_date', $pMonth)
                ->whereYear('plan_date', $pYear)
                ->groupBy('code_part')
                ->selectRaw('code_part, SUM(qty) as total_plan')
                ->pluck('total_plan', 'code_part')
                ->toArray();

            $prevActs = \App\Models\KanbanReport::whereIn('code_part', $validCodes)
                ->whereMonth('report_date', $pMonth)
                ->whereYear('report_date', $pYear)
                ->groupBy('code_part')
                ->selectRaw('code_part, SUM(COALESCE(act_shift_1, 0) + COALESCE(act_shift_2, 0)) as total_act')
                ->pluck('total_act', 'code_part')
                ->toArray();

            foreach ($validCodes as $code) {
                $c = trim($code);
                $p_plan = $prevPlans[$c] ?? 0;
                $p_act = $prevActs[$c] ?? 0;
                $delayData[$c] = $p_act - $p_plan;
            }
        }

        return view('production.input', compact(
            'matrixData',
            'selectedMonth',
            'selectedYear',
            'plants',
            'selectedPlant',
            'allLines',
            'lines',
            'lineId',
            'dailyPlanData',
            'actualData',
            'holidays',
            'totalWorkingDays',
            'delayData'
        ));
    }
    // =================================================================
    // 2. STORE: SIMPAN DATA ACTUAL (INPUT MANUAL)
    // =================================================================
    public function store(Request $request)
    {
        $month = $request->month;
        $year = $request->year;

        // Data input dari view: name="actuals[CODE_PART][DAY]"
        $inputs = $request->actuals;

        DB::beginTransaction();
        try {
            if ($inputs) {
                foreach ($inputs as $codePart => $days) {
                    foreach ($days as $day => $qty) {
                        // Simpan jika input tidak null (0 boleh disimpan)
                        if ($qty !== null && $qty !== '') {
                            $date = Carbon::create($year, $month, $day)->format('Y-m-d');

                            ProductionActual::updateOrCreate(
                                [
                                    'production_date' => $date,
                                    'code_part' => $codePart
                                ],
                                [
                                    'qty_delv' => $qty, // Simpan sebagai input manual
                                    'qty_final' => $qty, // Angka ini yang dipakai report
                                    'created_by' => auth()->id() ?? 1
                                ]
                            );
                        }
                    }
                }
            }

            DB::commit();
            return back()->with('success', 'Data Actual Produksi berhasil disimpan!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan: ' . $e->getMessage());
        }
    }

    public function syncDailyPlan(Request $request)
    {
        $month = $request->month;
        $year  = $request->year;

        // 1. Tentukan batas hari (misal Feb 2026 = 28 hari)
        $planDate = \Carbon\Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $daysInMonth = $planDate->daysInMonth; 

        try {
            // Setup Google Client
            $client = new \Google\Client();
            $client->setAuthConfig(storage_path('app/google/credentials.json'));
            $client->addScope(\Google\Service\Sheets::SPREADSHEETS);
            $service = new \Google\Service\Sheets($client);

            $spreadsheetId = '1NUNFLdQJ-MILLRi-aRQgyMnP3mm-qzp1mY6nHDTXwFE';
            $range = 'db!A2:AF'; 

            $response = $service->spreadsheets_values->get($spreadsheetId, $range);
            $rows = $response->getValues();

            if (empty($rows)) {
                return back()->with('error', 'Data Google Sheet Kosong/Tidak Terbaca.');
            }

            \Illuminate\Support\Facades\DB::beginTransaction();

            // 2. BERSIHKAN DATA LAMA (Reset Total Bulan Ini)
            \App\Models\DailyPlan::whereMonth('plan_date', $month)
                ->whereYear('plan_date', $year)
                ->delete();

            $insertData = [];
            $now = now();
            $partsCount = 0; // Untuk menghitung berapa part yg diproses

            foreach ($rows as $row) {
                // Cek Kode Part (Kolom A / Index 0)
                if (!isset($row[0]) || empty($row[0])) continue;
                
                $codePart = trim($row[0]);
                $partsCount++; // Hitung part ini berhasil dibaca

                // Loop Tanggal 1 s/d Akhir Bulan (Sesuai kalender)
                for ($d = 1; $d <= $daysInMonth; $d++) {
                    
                    // Ambil data qty (Index Google Sheet dimulai dari 0=A, maka Tgl 1 ada di Index 1=B)
                    $rawQty = isset($row[$d]) ? $row[$d] : 0;

                    // Bersihkan format angka (hapus koma/titik)
                    $qty = (int) str_replace([',', '.'], '', $rawQty);

                    // LOGIC: Masukkan SEMUA data (termasuk 0)
                    $dateString = sprintf('%04d-%02d-%02d', $year, $month, $d);

                    $insertData[] = [
                        'plan_date'  => $dateString,
                        'code_part'  => $codePart,
                        'qty'        => $qty,
                        'created_at' => $now,
                        'updated_at' => $now
                    ];
                }
            }

            // 3. EKSEKUSI INSERT KE DATABASE
            // Kita pecah per 500 baris agar database tidak overload (Bulk Insert)
            if (count($insertData) > 0) {
                foreach (array_chunk($insertData, 500) as $chunk) {
                    \App\Models\DailyPlan::insert($chunk);
                }
            }

            \Illuminate\Support\Facades\DB::commit();
            return back()->with('success', "Sync Berhasil! $partsCount Part telah diproses & disimpan (termasuk nilai 0).");

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return back()->with('error', 'Gagal Sync Google Sheet: ' . $e->getMessage());
        }
    }
}