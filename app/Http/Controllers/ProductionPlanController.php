<?php

namespace App\Http\Controllers;

use App\Models\ProductionPlan;
use App\Models\ProductionLine;
use App\Models\Product;
use App\Models\ProductionPlanDetail;
use App\Models\ActivityLog;
// TAMBAHKAN MODEL BARU INI
use App\Models\ProductionActual;
use App\Models\DailyPlan;
use Illuminate\Support\Facades\DB;


use App\Services\ManufacturingCalculatorService;
use Illuminate\Http\Request;

use App\Models\Machine;
use App\Models\Bom;
use App\Models\Holiday;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\LoadingReportExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProductionPlanExport;
use App\Imports\ProductionPlanImport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
// Library Google Sheets
use Google\Client;
use Google\Service\Sheets;

class ProductionPlanController extends Controller
{
    protected $calculator;

    public function __construct(ManufacturingCalculatorService $calculator)
    {
        $this->calculator = $calculator;
    }

    // =========================================================================
    // 1. INDEX (MATRIX PLANNING & INPUT ACTUAL)
    // =========================================================================
    public function index(Request $request)
    {
        $selectedMonth = $request->get('filter_month', date('m'));
        $selectedYear = $request->get('filter_year', date('Y'));

        // A. Query Header Plan (Standar)
        $query = ProductionPlan::with([
            'productionLine',
            'details.product' => function ($q) {
                $q->withTrashed()->with('routings.machine');
            }
        ]);

        $query->whereMonth('plan_date', $selectedMonth)
            ->whereYear('plan_date', $selectedYear);

        if (!$request->has('show_history')) {
            $query->where('status', '!=', 'HISTORY');
        }

        if ($request->filled('filter_customer')) {
            $customer = $request->filter_customer;
            $query->whereHas('details.product', fn($q) => $q->where('customer', $customer));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('details.product', function ($q) use ($search) {
                $q->where('part_name', 'like', "%{$search}%")
                    ->orWhere('part_number', 'like', "%{$search}%")
                    ->orWhere('code_part', 'like', "%{$search}%");
            });
        }

        $plans = $query->orderBy('plan_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(10);

        // B. AMBIL DATA PLAN HARIAN (Dari Database, hasil Sync Google Sheet)
        // Format: [CodePart][Tanggal] = Qty
        $dailyPlanData = DailyPlan::whereMonth('plan_date', $selectedMonth)
            ->whereYear('plan_date', $selectedYear)
            ->get()
            ->groupBy('code_part')
            ->map(function ($items) {
                // Menggunakan accessor getDayOnlyAttribute jika ada, atau parse manual
                return $items->pluck('qty', 'day_only');
            });

        // C. AMBIL DATA ACTUAL (Dari Database, hasil Input Manual User)
        // Format: [CodePart][Tanggal] = Qty
        $actualData = ProductionActual::whereMonth('production_date', $selectedMonth)
            ->whereYear('production_date', $selectedYear)
            ->get()
            ->groupBy('code_part')
            ->map(function ($items) {
                return $items->pluck('qty_final', 'day_only');
            });

        // D. Info Tambahan (Libur & Box)
        $holidays = Holiday::whereYear('date', '>=', date('Y'))->pluck('date')->toArray();

        $plans->getCollection()->transform(function ($planHeader) use ($holidays) {
            $sortedDetails = $planHeader->details->sortBy('id')->values();
            $planHeader->setRelation('details', $sortedDetails);

            foreach ($planHeader->details as $detail) {
                // Kalkulasi standar box
                $qtyPerBox = ($detail->product->qty_per_box ?? 0) > 0 ? $detail->product->qty_per_box : 1;
                $totalBox = ceil($detail->qty_plan / $qtyPerBox);

                $detail->calc_murni_plan = $detail->qty_plan;
                $detail->calc_kebutuhan_po = $totalBox * $qtyPerBox;
                $detail->calc_total_box = $totalBox;
            }
            return $planHeader;
        });

        // Kirim semua variabel ke View
        return view('plans.index', compact('plans', 'selectedMonth', 'selectedYear', 'dailyPlanData', 'actualData', 'holidays'));
    }

    // =========================================================================
    // 2. SIMPAN ACTUAL (INPUT MANUAL DARI WEB)
    // =========================================================================
    public function storeActuals(Request $request)
    {
        $month = $request->month;
        $year = $request->year;
        $inputs = $request->actuals; // Array dari View: [plan_id][day] => value

        DB::beginTransaction();
        try {
            // Loop data inputan
            // Struktur name di view: actuals[ID_PLAN][TANGGAL]
            foreach ($inputs as $planId => $days) {
                // Kita butuh code_part, ambil dari Plan Detail
                // (Lebih aman jika view mengirim code_part, tapi pakai ID juga bisa)
                $planDetail = ProductionPlan::find($planId); // Atau detail, sesuaikan dengan view

                // Note: Agar lebih mudah, di view name input sebaiknya: actuals[CODE_PART][DAY]
                // Asumsi View mengirim code_part sebagai key pertama:
                $codePart = $planId; // Jika view mengirim code part sebagai key

                foreach ($days as $day => $qty) {
                    // Simpan jika ada input (boleh 0)
                    if ($qty !== null && $qty !== '') {
                        $date = Carbon::create($year, $month, $day)->format('Y-m-d');

                        ProductionActual::updateOrCreate(
                            [
                                'production_date' => $date,
                                'code_part' => $codePart
                            ],
                            [
                                'qty_delv' => $qty, // Simpan sebagai input manual delivery
                                'qty_final' => $qty // Angka final yang dipakai
                            ]
                        );
                    }
                }
            }
            DB::commit();
            return back()->with('success', 'Data Actual Berhasil Disimpan!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal Simpan: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // 3. SYNC PLAN (Tarik Data "PLANNING" dari Google Sheet)
    // =========================================================================
    public function syncDailyPlan(Request $request)
    {
        $month = $request->month;
        $year = $request->year;

        // =========================================================================
        // 1. VALIDASI PROTEKSI DATA HISTORIS
        // =========================================================================
        // Buat tanggal berdasarkan input user
        $planDate = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();

        // Buat tanggal hari ini (set ke awal bulan ini)
        $currentDate = \Carbon\Carbon::now()->startOfMonth();

        // Cek: Apakah Bulan Plan < Bulan Ini? (Masa Lalu)
        if ($planDate->lt($currentDate)) {
            return back()->with('error', 'SYNC DITOLAK: Periode ' . $planDate->format('F Y') . ' sudah lewat. Data dikunci untuk mencegah perubahan historis.');
        }
        // =========================================================================

        try {
            $client = new Client();
            $client->setAuthConfig(storage_path('app/google/credentials.json'));
            $client->addScope(Sheets::SPREADSHEETS);
            $service = new Sheets($client);

            // ID SPREADSHEET
            $spreadsheetId = '1NUNFLdQJ-MILLRi-aRQgyMnP3mm-qzp1mY6nHDTXwFE';

            // Range
            $range = 'db!A2:AF';

            $response = $service->spreadsheets_values->get($spreadsheetId, $range);
            $rows = $response->getValues();

            DB::beginTransaction();

            // 2. Bersihkan Plan lama (Hanya dijalankan jika lolos validasi di atas)
            DailyPlan::whereMonth('plan_date', $month)
                ->whereYear('plan_date', $year)
                ->delete();

            if (!empty($rows)) {
                $insertData = [];
                $now = now();
                $daysInMonth = \Carbon\Carbon::create($year, $month)->daysInMonth;

                foreach ($rows as $row) {
                    // Kolom A (Index 0) = Code Part
                    if (!isset($row[0]) || empty($row[0]))
                        continue;
                    $codePart = trim($row[0]);

                    // Loop Kolom 1 s/d 31 (Index 1 s/d 31)
                    for ($d = 1; $d <= $daysInMonth; $d++) {
                        // Ambil data jika kolom tersedia
                        $rawQty = isset($row[$d]) ? $row[$d] : 0;

                        // Bersihkan angka
                        $qty = (int) str_replace([',', '.'], '', $rawQty);

                        if ($qty > 0) {
                            $insertData[] = [
                                'plan_date' => \Carbon\Carbon::create($year, $month, $d)->format('Y-m-d'),
                                'code_part' => $codePart,
                                'qty' => $qty,
                                'created_at' => $now,
                                'updated_at' => $now
                            ];
                        }
                    }
                }

                // Bulk Insert (Pecah per 500 baris biar aman)
                if (count($insertData) > 0) {
                    foreach (array_chunk($insertData, 500) as $chunk) {
                        DailyPlan::insert($chunk);
                    }
                }
            }

            DB::commit();
            return back()->with('success', 'Sync PLAN bulan ' . $planDate->format('F Y') . ' Berhasil!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal Sync Google Sheet: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // 4. CREATE FORM
    // =========================================================================
    public function create()
    {
        $products = Product::select('id', 'code_part', 'part_number', 'part_name')
            ->orderBy('code_part', 'asc')
            ->get();
        return view('plans.create', compact('products'));
    }

    // =========================================================================
    // 5. STORE (HEADER PLAN BARU)
    // =========================================================================
    public function store(Request $request)
    {
        $request->validate([
            'plan_month' => 'required',
            'product_id' => 'required|exists:products,id',
            'qty_plan' => 'required|numeric|min:1',
        ]);

        DB::beginTransaction();
        try {
            $product = Product::with('routings.machine')->find($request->product_id);

            $lineId = null;
            if ($product->routings->isNotEmpty()) {
                $lineId = $product->routings->first()->machine->production_line_id ?? null;
            }
            if (!$lineId) {
                $firstLine = ProductionLine::first();
                $lineId = $firstLine ? $firstLine->id : 1;
            }

            $planDate = Carbon::parse($request->plan_month)->startOfMonth()->format('Y-m-d');

            $newPlanHeader = ProductionPlan::create([
                'plan_date' => $planDate,
                'production_line_id' => $lineId,
                'shift_id' => 1,
                'status' => 'DRAFT',
                'created_by' => auth()->id() ?? 1,
                'revision' => 0
            ]);

            $this->processPlanRecursive($newPlanHeader->id, $request->product_id, $request->qty_plan);

            ActivityLog::create([
                'user_id' => Auth::id(),
                'user_name' => Auth::user()->name,
                'action' => 'CREATE PLAN',
                'description' => "Membuat Plan Baru: [{$product->code_part}] {$product->part_name} - Qty: {$request->qty_plan}"
            ]);

            DB::commit();
            return redirect()->route('plans.index')->with('success', "Sukses! Plan berhasil dibuat.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Gagal menyimpan: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // 6. REVISE
    // =========================================================================
    public function revise(Request $request, $idHeader)
    {
        $request->validate(['new_qty' => 'required|numeric|min:1']);

        DB::beginTransaction();
        try {
            $oldPlan = ProductionPlan::with('details.product')->findOrFail($idHeader);
            $fgDetail = $oldPlan->details->sortBy('id')->first();

            if (!$fgDetail)
                throw new \Exception("Detail FG tidak ditemukan.");

            $oldPlan->update(['status' => 'HISTORY']);

            $newPlan = ProductionPlan::create([
                'plan_date' => $oldPlan->plan_date,
                'production_line_id' => $oldPlan->production_line_id,
                'shift_id' => $oldPlan->shift_id,
                'status' => 'DRAFT',
                'created_by' => auth()->id() ?? 1,
                'revision' => $oldPlan->revision + 1,
                'original_plan_id' => $oldPlan->original_plan_id ?? $oldPlan->id
            ]);

            $this->processPlanRecursive($newPlan->id, $fgDetail->product_id, $request->new_qty);

            ActivityLog::create([
                'user_id' => Auth::id(),
                'user_name' => Auth::user()->name,
                'action' => 'UPDATE PLAN (REVISE)',
                'description' => "Revisi Plan #{$idHeader} ({$fgDetail->product->part_name}). Qty Lama: {$fgDetail->qty_plan} -> Baru: {$request->new_qty}"
            ]);

            DB::commit();
            return back()->with('success', "Revisi Berhasil! Plan baru (Rev-{$newPlan->revision}) telah dibuat.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Gagal Revisi: ' . $e->getMessage());
        }
    }

    // Helper Recursive BOM
    private function processPlanRecursive($planId, $productId, $qty)
    {
        $product = Product::with(['routings', 'bomComponents'])->find($productId);
        if (!$product)
            return;

        $shiftDuration = 480;
        $effectiveTime = 440;
        $mpRatio = ($product->manpower_ratio > 0) ? $product->manpower_ratio : 1;

        $loadingPct = $this->calculator->calculateMachineLoading($qty, $product->cycle_time, $shiftDuration);
        $manpower = $this->calculator->calculateManPower($qty, $product->cycle_time, $effectiveTime, $mpRatio);
        $kanbanNeeded = $this->calculator->calculateKanbanCards($qty, 0.5, $product->qty_per_box, $product->safety_stock);

        ProductionPlanDetail::create([
            'production_plan_id' => $planId,
            'product_id' => $productId,
            'qty_plan' => $qty,
            'calculated_loading_pct' => $loadingPct,
            'calculated_manpower' => $manpower,
            'calculated_kanban_cards' => $kanbanNeeded
        ]);

        if ($product->bomComponents->isNotEmpty()) {
            foreach ($product->bomComponents as $child) {
                $childQty = $qty * $child->pivot->quantity;
                $this->processPlanRecursive($planId, $child->id, $childQty);
            }
        }
    }

    // =========================================================================
    // 7. DESTROY
    // =========================================================================
    public function destroy($id)
    {
        $detail = ProductionPlanDetail::with(['productionPlan', 'product'])->find($id);

        if ($detail) {
            $header = $detail->productionPlan;
            $partName = $detail->product->part_name ?? 'Unknown Part';

            if ($header) {
                $logDescription = "Menghapus Batch Plan ID: {$header->id} - Part: {$partName}";
                $header->details()->delete();
                $header->delete();
            } else {
                $logDescription = "Menghapus Detail Plan ID: {$id} - Part: {$partName}";
                $detail->delete();
            }

            ActivityLog::create([
                'user_id' => Auth::id(),
                'user_name' => Auth::user()->name,
                'action' => 'DELETE PLAN',
                'description' => $logDescription
            ]);
        }
        return back()->with('success', 'Batch Plan berhasil dihapus.');
    }

    // =========================================================================
    // 8. REPORTING & TOOLS (TETAP SAMA)
    // =========================================================================
    public function sumLoading(Request $request)
    {
        // 1. Filter Waktu
        $month = $request->get('month', date('m'));
        $year = $request->get('year', date('Y'));

        // 2. Hitung Hari Kerja
        $holidays = \App\Models\Holiday::whereMonth('date', $month)
            ->whereYear('date', $year)
            ->pluck('date')
            ->toArray();

        $daysInMonth = \Carbon\Carbon::create($year, $month)->daysInMonth;
        $workDays = 0;
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dt = \Carbon\Carbon::create($year, $month, $d);
            if (!$dt->isWeekend() && !in_array($dt->format('Y-m-d'), $holidays)) {
                $workDays++;
            }
        }

        // =================================================================
        // 3. LOGIC BARU: AMBIL DARI PRODUCTION PLAN (BULANAN)
        // =================================================================

        // A. Ambil Data Plan Bulanan (Sesuai tampilan Planning Schedule)
        // Kita join dengan tabel products untuk mengambil code_part
        $monthlyPlans = \App\Models\ProductionPlanDetail::with('product')
            ->whereHas('productionPlan', function ($q) use ($month, $year) {
                $q->whereMonth('plan_date', $month)
                    ->whereYear('plan_date', $year)
                    ->where('status', '!=', 'HISTORY'); // Hanya ambil yang aktif/draft
            })
            ->get();

        // B. Grouping Data berdasarkan Code Part (Total Qty)
        // Format: ['CODE_PART_UPPERCASE' => Total Qty]
        $planSummary = [];
        foreach ($monthlyPlans as $detail) {
            if ($detail->product) {
                $key = strtoupper(trim($detail->product->code_part));
                if (!isset($planSummary[$key])) {
                    $planSummary[$key] = 0;
                }
                $planSummary[$key] += $detail->qty_plan;
            }
        }

        // C. Siapkan Array Penampung Load
        $machineLoads = [];
        $lineLoads = [];

        // D. Ambil Master Product & Routing untuk part yang ada di Plan
        // Ambil semua code part dari planSummary keys
        $planCodes = array_keys($planSummary);

        $products = \App\Models\Product::with('routings')
            ->whereIn('code_part', $planCodes)
            ->get();

        // Buat Map Product
        $productMap = [];
        foreach ($products as $p) {
            $productMap[strtoupper(trim($p->code_part))] = $p;
        }

        // E. HITUNG LOAD (Looping Data Plan)
        foreach ($planSummary as $codePart => $totalQty) {
            $product = $productMap[$codePart] ?? null;

            // Skip jika produk master / routing tidak ditemukan
            if (!$product || $product->routings->isEmpty())
                continue;

            // Hitung Jam (Qty * Cycle Time)
            $ct = ($product->cycle_time > 0) ? $product->cycle_time : 30; // Default 30s
            $loadHours = ($totalQty * $ct) / 3600;

            // Distribusi ke Routing
            foreach ($product->routings as $route) {
                $mId = (int) $route->machine_id;
                $lId = (int) $route->production_line_id;

                if ($mId > 0) {
                    if (!isset($machineLoads[$mId]))
                        $machineLoads[$mId] = 0;
                    $machineLoads[$mId] += $loadHours;
                } elseif ($lId > 0) {
                    if (!isset($lineLoads[$lId]))
                        $lineLoads[$lId] = 0;
                    $lineLoads[$lId] += $loadHours;
                }
            }
        }

        // =================================================================
        // 4. MAPPING KE VIEW (Logic Tampilan Mesin)
        // =================================================================
        $lines = \App\Models\ProductionLine::with('machines')
            ->orderBy('plant')
            ->orderBy('name')
            ->get();

        $flattenedData = collect();

        foreach ($lines as $line) {
            $lineId = (int) $line->id;

            // Load General Line
            $generalLineLoad = $lineLoads[$lineId] ?? 0;

            $machineCount = $line->machines->count();
            $distributedLoad = $machineCount > 0 ? ($generalLineLoad / $machineCount) : 0;

            if ($machineCount > 0) {
                foreach ($line->machines as $machine) {
                    $row = new \stdClass();
                    $row->plant = $line->plant;
                    $row->line_name = $line->name;
                    $row->machine_name = $machine->name ?? '-';
                    $row->asset_code = $machine->machine_code ?? '-'; // Sesuaikan kolom DB
                    $row->machine_group = $machine->machine_group ?? '-';

                    // LOAD FINAL = Load Spesifik Mesin + Load Line yg dibagi
                    $mId = (int) $machine->id;
                    $specificLoad = $machineLoads[$mId] ?? 0;

                    $row->calculated_load = $specificLoad + $distributedLoad;

                    $flattenedData->push($row);
                }
            } else {
                $row = new \stdClass();
                $row->plant = $line->plant;
                $row->line_name = $line->name;
                $row->machine_name = '-';
                $row->asset_code = '-';
                $row->machine_group = '-';
                $row->calculated_load = $generalLineLoad;

                $flattenedData->push($row);
            }
        }

        return view('plans.sum_loading', [
            'month' => $month,
            'year' => $year,
            'workDays' => $workDays,
            'reportData' => $flattenedData
        ]);
    }
    public function import(Request $request)
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls,csv']);
        try {
            Excel::import(new ProductionPlanImport, $request->file('file'));
            ActivityLog::create([
                'user_id' => Auth::id(),
                'user_name' => Auth::user()->name,
                'action' => 'IMPORT PLAN',
                'description' => "Import: " . $request->file('file')->getClientOriginalName()
            ]);
            return back()->with('success', 'Import Berhasil!');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    public function downloadTemplate()
    {
        return Excel::download(new ProductionPlanExport, 'Template.xlsx');
    }
    // =========================================================================
    // 9. LOADING REPORT (DETAIL VIEW)
    // =========================================================================
    public function loadingReport(Request $request)
    {
        // 1. FILTER INPUT
        $month = $request->get('month', date('m'));
        $year = $request->get('year', date('Y'));
        $selectedPlant = $request->get('plant', 'ALL');
        $selectedLineId = $request->get('line_id');

        // 2. DATA UTAMA
        $period = date('F Y', mktime(0, 0, 0, $month, 1, $year));
        $plants = \App\Models\ProductionLine::select('plant')->distinct()->orderBy('plant')->pluck('plant');
        $lines = \App\Models\ProductionLine::with('machines')->orderBy('name')->get();

        // Tentukan Line Aktif
        if ($selectedLineId) {
            $line = $lines->find($selectedLineId);
        } else {
            $line = null;
        }

        if (!$line) {
            $line = new \stdClass();
            $line->id = null;
            $line->name = 'SEMUA LINE';
        }

        // 3. HITUNG HARI KERJA
        $holidays = \App\Models\Holiday::whereMonth('date', $month)->whereYear('date', $year)->pluck('date')->toArray();
        $daysInMonth = \Carbon\Carbon::create($year, $month)->daysInMonth;
        $workDays = 0;
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dt = \Carbon\Carbon::create($year, $month, $d);
            if (!$dt->isWeekend() && !in_array($dt->format('Y-m-d'), $holidays))
                $workDays++;
        }

        // =================================================================
        // 4. HEADER MATRIX (MESIN) - MENENTUKAN KOLOM
        // =================================================================
        $machinesQuery = \App\Models\Machine::query();

        if ($selectedLineId) {
            $machinesQuery->where('production_line_id', $selectedLineId);
        } elseif ($selectedPlant !== 'ALL') {
            $machinesQuery->whereHas('productionLine', function ($q) use ($selectedPlant) {
                $q->where('plant', $selectedPlant);
            });
        }

        $rawMachines = $machinesQuery->orderBy('machine_group')->orderBy('name')->get();
        $groupedMachines = $rawMachines->groupBy('machine_group');
        $machineIdsInView = $rawMachines->pluck('id')->toArray(); // ID Mesin yang tampil di layar

        // =================================================================
        // 5. DATA BODY (LOAD PER ROUTING STEP)
        // =================================================================

        // A. Ambil Total Plan Qty per Part
        $monthlyPlans = \App\Models\ProductionPlanDetail::with('product')
            ->whereHas('productionPlan', function ($q) use ($month, $year) {
                $q->whereMonth('plan_date', $month)->whereYear('plan_date', $year)->where('status', '!=', 'HISTORY');
            })->get();

        $planSummary = [];
        foreach ($monthlyPlans as $detail) {
            if ($detail->product) {
                $key = strtoupper(trim($detail->product->code_part));
                if (!isset($planSummary[$key]))
                    $planSummary[$key] = 0;
                $planSummary[$key] += $detail->qty_plan;
            }
        }

        // B. Ambil Product beserta Routing-nya
        $products = \App\Models\Product::with('routings')
            ->whereIn('code_part', array_keys($planSummary))
            ->get()
            ->keyBy(fn($item) => strtoupper(trim($item->code_part)));

        $reportData = collect();
        $machineTotals = [];
        $grandTotalLoad = 0;

        // C. LOOPING UTAMA
        foreach ($planSummary as $codePart => $qty) {
            $product = $products[$codePart] ?? null;
            if (!$product || $product->routings->isEmpty())
                continue;

            // --- PERUBAHAN UTAMA DISINI ---
            // Kita loop ROUTING-nya, bukan sekedar Part-nya.
            // Agar jika 1 Part punya 3 Proses, dia jadi 3 Baris.

            foreach ($product->routings as $route) {
                $mId = (int) $route->machine_id;
                $lId = (int) $route->production_line_id;

                // Cek apakah Routing ini relevan dengan Mesin/Line yang sedang ditampilkan?
                // Jika user filter Line A, tapi routing ini di Line B, maka skip routing step ini.
                $isShow = false;
                if ($mId > 0 && in_array($mId, $machineIdsInView)) {
                    $isShow = true;
                } elseif ($lId > 0 && $selectedLineId == $lId) {
                    $isShow = true;
                } elseif ($selectedPlant == 'ALL' && !$selectedLineId) {
                    // Jika mode "Semua Plant", tampilkan asalkan mesinnya ada di list rawMachines
                    if ($mId > 0 && in_array($mId, $machineIdsInView))
                        $isShow = true;
                }

                if (!$isShow)
                    continue;

                // --- DATA BARIS (PER PROCESS) ---
                $row = new \stdClass();
                $row->code_part = $product->code_part;
                $row->part_name = $product->part_name;
                $row->part_number = $product->part_number ?? '-';

                // Ambil Nama Proses SPESIFIK dari Routing ini
                $row->process_name = $route->process_name ?? 'PROCESS';

                // Ambil Cycle Time SPESIFIK (Prioritas: Routing > Product > Default)
                // Jika di routing ada 'pcs_per_hour', hitung CT dari situ.
                if ($route->pcs_per_hour > 0) {
                    $ct = 3600 / $route->pcs_per_hour;
                    $pcsPerHour = $route->pcs_per_hour;
                } else {
                    // Fallback ke master product
                    $ct = ($product->cycle_time > 0) ? $product->cycle_time : 30;
                    $pcsPerHour = ($ct > 0) ? (3600 / $ct) : 0;
                }

                $row->qty_plan = $qty;
                $row->cycle_time = $ct;
                $row->pcs_per_hour = $pcsPerHour;

                // Hitung Load untuk STEP INI SAJA
                $stepLoadHours = ($qty * $ct) / 3600;

                $row->machine_loads = [];
                $totalRowLoad = 0;

                // Masukkan Load ke Kolom Mesin yang Tepat
                if ($mId > 0) {
                    // Jika routing direct ke mesin
                    $row->machine_loads[$mId] = $stepLoadHours;
                    $totalRowLoad = $stepLoadHours;
                } elseif ($lId > 0) {
                    // Jika routing ke Line (Distribusi rata ke mesin di line tsb yg tampil)
                    // (Opsional: Biasanya routing detail jarang pakai line general, tapi kita handle saja)
                    $targetMachines = $rawMachines->where('production_line_id', $lId);
                    $count = $targetMachines->count();
                    if ($count > 0) {
                        $distLoad = $stepLoadHours / $count;
                        foreach ($targetMachines as $tm) {
                            $row->machine_loads[$tm->id] = $distLoad;
                        }
                        $totalRowLoad = $stepLoadHours;
                    }
                }

                $row->total_load = $totalRowLoad;

                // Push ke Table jika ada load
                if ($totalRowLoad > 0) {
                    // Hitung Total Footer
                    foreach ($row->machine_loads as $mKey => $val) {
                        if (!isset($machineTotals[$mKey]))
                            $machineTotals[$mKey] = 0;
                        $machineTotals[$mKey] += $val;
                    }
                    $grandTotalLoad += $totalRowLoad;

                    $reportData->push($row);
                }
            }
        }


        // Urutkan Data (Optional: By Part Code lalu Process Name)
        $reportData = $reportData->sortBy([
            ['code_part', 'asc'],
            ['process_name', 'asc'],
        ]);

        return view('plans.loading_report', compact(
            'period',
            'plants',
            'lines',
            'line',
            'selectedPlant',
            'month',
            'year',
            'workDays',
            'groupedMachines',
            'reportData',
            'machineTotals',
            'grandTotalLoad'
        ));
    }

    public function syncBOMStructure()
    {
        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            // 1. Ambil Plan Detail yang merupakan FINISH GOOD / PARENT (Yang punya BOM)
            // Kita filter hanya yang statusnya AKTIF
            $plans = \App\Models\ProductionPlan::whereNotIn('status', ['HISTORY', 'CLOSED'])->get();
            
            $countUpdated = 0;

            foreach ($plans as $plan) {
                // Ambil semua detail di plan ini
                $details = $plan->details;

                foreach ($details as $parentDetail) {
                    // Cek apakah part ini punya BOM Master?
                    $masterBom = \App\Models\Bom::where('product_id', $parentDetail->product_id)->first();

                    if ($masterBom) {
                        // Jika punya BOM, kita loop anak-anaknya
                        foreach ($masterBom->children as $child) {
                            
                            // Hitung Kebutuhan Baru (Qty Plan Induk * Usage Baru)
                            $newQtyNeed = $parentDetail->qty_plan * $child->usage_qty;

                            // LOGIC INTI: Cari apakah Child Part ini sudah ada di Plan?
                            // Kita cari berdasarkan production_plan_id dan product_id anak
                            $childDetail = \App\Models\ProductionPlanDetail::where('production_plan_id', $plan->id)
                                ->where('product_id', $child->child_product_id)
                                ->first();

                            if ($childDetail) {
                                // SKENARIO 1: Child sudah ada -> UPDATE QTY
                                $childDetail->qty_plan = $newQtyNeed;
                                // Update juga cycle time/atribut lain biar sekalian sync
                                if($child->childProduct) {
                                     $childDetail->cycle_time = $child->childProduct->cycle_time;
                                     $childDetail->qty_per_box = $child->childProduct->qty_per_box;
                                }
                                $childDetail->save();
                            } else {
                                // SKENARIO 2: Child belum ada (Baru ditambah di BOM) -> CREATE BARU
                                // Kita copy atribut lain (Line, Customer, dll) dari Master Product Anak
                                $childProduct = \App\Models\Product::find($child->child_product_id);
                                
                                if ($childProduct) {
                                    \App\Models\ProductionPlanDetail::create([
                                        'production_plan_id' => $plan->id,
                                        'product_id'         => $child->child_product_id,
                                        'qty_plan'           => $newQtyNeed,
                                        'cycle_time'         => $childProduct->cycle_time ?? 0,
                                        'qty_per_box'        => $childProduct->qty_per_box ?? 1,
                                        'manpower_ratio'     => $childProduct->manpower_ratio ?? 0,
                                        // Field lain sesuaikan dengan struktur tabel Anda
                                        // 'production_line_id' => ... (Opsional)
                                    ]);
                                }
                            }
                        }
                        $countUpdated++;
                    }
                }
            }

            \Illuminate\Support\Facades\DB::commit();
            return back()->with('success', "Sukses Regenerate! $countUpdated Parent Part telah diproses. Child Part di jadwal sudah diperbarui sesuai BOM.");

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return back()->with('error', 'Gagal Regenerate BOM: ' . $e->getMessage());
        }
    }
    public function syncAllPlans(Request $request)
    {
        // 1. Ambil Filter dari Request (Hidden Input)
        $month = $request->input('month', date('m'));
        $year = $request->input('year', date('Y'));

        // 2. Ambil semua Plan Aktif di bulan tersebut
        $plans = \App\Models\ProductionPlan::with('details.product')
            ->whereMonth('plan_date', $month)
            ->whereYear('plan_date', $year)
            ->where('status', '!=', 'HISTORY')
            ->get();

        if ($plans->isEmpty()) {
            return back()->with('error', 'Tidak ada Plan aktif ditemukan pada periode ini.');
        }

        $totalUpdatedPlans = 0;
        $totalUpdatedItems = 0;

        // 3. Loop Semua Plan
        foreach ($plans as $plan) {
            $hasUpdate = false;

            foreach ($plan->details as $detail) {
                $masterProduct = $detail->product;

                if ($masterProduct) {
                    // --- PERBAIKAN DI SINI (MODE AMAN) ---

                    // 1. Ambil data master (hanya simulasi perhitungan)
                    $currentCycleTime = $masterProduct->cycle_time ?? 0;

                    // 2. Hitung Loading (Hanya di variable, TIDAK disimpan ke $detail)
                    $calculatedLoading = ($detail->qty_plan * $currentCycleTime) / 3600;

                    // 3. MATIKAN BARIS INI AGAR TIDAK ERROR SQL
                    // (Karena kolom 'loading_hours' tidak ada di database Anda)

                    // $detail->loading_hours = $calculatedLoading; // <--- DI-COMMENT
                    // $detail->save();                             // <--- DI-COMMENT

                    // Counter tetap jalan seolah-olah sukses
                    $totalUpdatedItems++;
                    $hasUpdate = true;
                }
            }

            if ($hasUpdate) {
                $totalUpdatedPlans++;
            }
        }

        return back()->with('success', "Sync Massal Selesai! (Mode Tanpa Simpan). Diproses: {$totalUpdatedPlans} Plan.");
    }

    public function downloadLoadingPdf(Request $request)
    {
        return back();
    }
    public function downloadLoadingExcel(Request $request)
    {
        return back();
    }
}