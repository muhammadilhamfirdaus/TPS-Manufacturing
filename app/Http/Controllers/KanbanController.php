<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductionPlanDetail;
use App\Models\Holiday;
use App\Services\KanbanCalculatorService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;


class KanbanController extends Controller
{
    protected $calculator;

    public function __construct(KanbanCalculatorService $calculator)
    {
        $this->calculator = $calculator;
    }
    public function index(Request $request)
    {
        // 1. Filter Bulan & Tahun
        $month = $request->get('month', date('m'));
        $year = $request->get('year', date('Y'));

        // 2. AMBIL HARI LIBUR DARI DATABASE
        $holidays = \App\Models\Holiday::whereYear('date', $year)
            ->whereMonth('date', $month)
            ->pluck('date')
            ->toArray();

        // 3. HITUNG HARI KERJA EFEKTIF
        $daysInMonth = \Carbon\Carbon::create($year, $month)->daysInMonth;
        $workDays = 0;

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $currentDate = \Carbon\Carbon::create($year, $month, $day);
            $dateString = $currentDate->format('Y-m-d');

            // Logic: (Senin-Jumat) DAN (Bukan Libur)
            if ($currentDate->isWeekday() && !in_array($dateString, $holidays)) {
                $workDays++;
            }
        }
        // Safeguard agar tidak bagi dengan 0
        $workDays = $workDays > 0 ? $workDays : 20;

        // 4. QUERY DATA
        $kanbanData = \App\Models\ProductionPlanDetail::with('product')
            ->whereHas('productionPlan', function ($q) use ($month, $year) {
                $q->whereMonth('plan_date', $month)
                    ->whereYear('plan_date', $year)
                    ->where('status', '!=', 'HISTORY');
            })
            ->get()
            ->groupBy('product_id')
            ->map(function ($details) use ($workDays) {
                $product = $details->first()->product;

                // [BARU] Ambil Tipe Kanban
                $kanbanType = $product->kanban_type ?? 'PRODUCTION';

                // --- (Logic Line & Data Utama) ---
                $routing = DB::table('product_routings')
                    ->leftJoin('production_lines', 'product_routings.production_line_id', '=', 'production_lines.id')
                    ->where('product_routings.product_id', $product->id)
                    ->select('production_lines.name as line_name')
                    ->first();
                $lineName = $routing ? $routing->line_name : '-';

                $totalOrder = $details->sum('qty_plan');
                $dailyOrder = ($workDays > 0) ? floor($totalOrder / $workDays) : 0; // Prevent division by zero
                $qtyPerBox = ($product->qty_per_box > 0) ? $product->qty_per_box : 1;
                $kodeBox = $product->kode_box ?? '-';

                // --- [BARU] LOGIC DAILY KANBAN ---
                // Rumus: Daily Order / Qty Per Box (Dibulatkan ke atas / ceil)
                $dailyKanban = ceil($dailyOrder / $qtyPerBox);

                $pcsKanbanCust = $product->pcs_kanban_cust ?? 0;

                // Logic Lot Size
                $lotSizePcs = $product->lot_size_pcs ?? 0;
                $lotSizeKanban = ceil($lotSizePcs / $qtyPerBox);

                // --- [REVISI FINAL] LOGIC MAT. REMARKS ---
                $materialRemarks = 0;
                $bomUsage = 0;

                $typeCheck = strtoupper($kanbanType);

                if ($typeCheck === 'PRODUCTION' || $typeCheck === 'PRODUKSI') {
                    // 1. Cari Child/Raw Material di BOM (Cari dimana Part ini sebagai PARENT)
                    $bomData = \Illuminate\Support\Facades\DB::table('bom_details')
                        ->where('parent_product_id', $product->id)
                        ->first();

                    if ($bomData) {
                        $bomUsage = $bomData->quantity;
                        // 2. Hitung: Lot Size Pcs * Usage
                        if ($bomUsage > 0) {
                            $materialRemarks = $lotSizePcs * $bomUsage;
                        }
                    }
                }

                // --- [LOGIC WAKTU] ---
                $cycleTime = $product->cycle_time ?? 0;

                // 1. Output / Jam (3600 / CT)
                $outputPerHour = ($cycleTime > 0) ? (3600 / $cycleTime) : 0;

                // 2. Takt Time (57600 / Daily Order)
                $taktTime = ($dailyOrder > 0) ? (57600 / $dailyOrder) : 0;

                // 3. Calculated Load Time (Qty/Box * Cycle Time)
                $calcLoadTime = $qtyPerBox * $cycleTime;

                // 4. Logic Line Store
                if ($qtyPerBox > $dailyOrder) {
                    $lineStore = $qtyPerBox * $taktTime;
                } else {
                    $lineStore = $dailyOrder * $taktTime;
                }

                // 5. AMBIL DATA COLLECTING POST
                $collectingPost = $product->collecting_post ?? 0;

                // 6. LOGIC LOT MAKING
                $lotMaking = max(0, ($lotSizeKanban - 1)) * ($qtyPerBox * $taktTime);
                $kanbanPost = $product->kanban_post ?? 0;

                // 7. LOGIC CHUTE
                if ($lotSizeKanban > 1) {
                    $chute = ($lotSizeKanban - 1) * ($qtyPerBox * $taktTime);
                } else {
                    $chute = $qtyPerBox * $taktTime;
                }

                // 8. LOGIC PROSES
                $proses = $calcLoadTime;

                $outgoing = $product->outgoing ?? 0;
                $subcont = $product->subcont ?? 0;
                $incoming = $product->incoming ?? 0;

                // 9. LOGIC CONVEYANCE (PURE INPUT)
                $conveyance = $product->conveyance ?? 0;

                // 10. Logic Fluktuasi
                $fluctuationPct = $product->fluctuation ?? 0;
                $fluctuationTime = ($fluctuationPct / 100) * 57600;

                // --- [BARU] LOGIC STORE INCOMING ---
                // Rumus: (Base * Takt) + (1 * Takt * QtyBox)
                // Bagian kanan rumus sama untuk kedua kondisi
                $termRight = 1 * $taktTime * $qtyPerBox;

                $storeIncoming = 0;
                if ($qtyPerBox > $dailyOrder) {
                    // Kondisi 1: Qty/Box > Daily Order
                    $storeIncoming = ($qtyPerBox * $taktTime) + $termRight;
                } else {
                    // Kondisi 2: Qty/Box <= Daily Order
                    $storeIncoming = ($dailyOrder * $taktTime) + $termRight;
                }

                // --- [BARU] LOGIC 1 PULL ---
                // Rumus: Jika Qty/Box > (Daily Order * 3) ? (Qty/Box * Takt Time) : 57600
                $onePull = 0;
                if ($qtyPerBox > ($dailyOrder * 3)) {
                    $onePull = $qtyPerBox * $taktTime;
                } else {
                    $onePull = 57600; // 57600 * 1
                }

                // 4. Logic Line Store (Existing)
                if ($qtyPerBox > $dailyOrder) {
                    $lineStore = $qtyPerBox * $taktTime;
                } else {
                    $lineStore = $dailyOrder * $taktTime;
                }


                // 11. TOTAL LEAD TIME
                $grandTotalLeadTime = $onePull
                    +$storeIncoming
                    +$lineStore
                    + $collectingPost
                    + $lotMaking
                    + $kanbanPost
                    + $chute
                    + $proses
                    + $outgoing
                    + $subcont
                    + $incoming
                    + $conveyance
                    + $fluctuationTime;

                // Parameter Lead Time (DB)
                $loadTime = $product->load_time ?? 0;
                $leadTime = $product->lead_time ?? 0;
                $totalLeadTime = $loadTime + $leadTime;

                // 12. LOGIC TOTAL KANBAN (TARGET)
                if ($taktTime > 0 && $qtyPerBox > 0) {
                    $calcTarget = ($grandTotalLeadTime / $taktTime) / $qtyPerBox;
                    $kanbanTarget = ceil($calcTarget);
                } else {
                    $kanbanTarget = 0;
                }

                $pcsKanbanCust = $product->pcs_kanban_cust ?? 0;

                // [BARU] LOGIC ROUND UP
                // Rumus: ceil(Daily Order / Pcs Kanban Cust) * Pcs Kanban Cust
                $roundUp = 0;
                if ($pcsKanbanCust > 0) {
                    $roundUp = ceil($dailyOrder / $pcsKanbanCust) * $pcsKanbanCust;
                } elseif ($pcsKanbanCust == 0) {
                    // Jika pembagi 0, logic fallback (bisa disamakan dengan daily order atau 0)
                    $roundUp = $dailyOrder; 
                }

                
                $kanbanAktif = $product->kanban_aktif ?? 0;
                $gap = $kanbanTarget - $kanbanAktif;

                return (object) [
                    
                    'product_id' => $product->id,
                    'code_part' => $product->code_part,
                    'part_name' => $product->part_name,
                    'part_number' => $product->part_number,
                    'customer' => $product->customer,
                    'kanban_type' => $kanbanType,
                    'line' => $lineName,
                    'total_order' => $totalOrder,
                    'daily_order' => $dailyOrder,     // PCS
                    'daily_kanban' => $dailyKanban,   // <--- DATA BARU (KANBAN)
                    'pcs_kanban_cust' => $pcsKanbanCust,
                    'round_up' => $roundUp,
                    'qty_per_box' => $qtyPerBox,
                    'kode_box' => $kodeBox,
                    'lot_size_pcs' => $lotSizePcs,
                    'kanban_post' => $kanbanPost,
                    'lot_size_kanban' => $lotSizeKanban,

                    'material_remarks' => $materialRemarks,
                    'bom_usage' => $bomUsage,

                    'cycle_time' => $cycleTime,
                    'output_per_hour' => $outputPerHour,
                    'takt_time' => $taktTime,
                    'calc_load_time' => $calcLoadTime,
                    'one_pull' => $onePull,
                    'store_incoming' => $storeIncoming,
                    'line_store' => $lineStore,
                    'collecting_post' => $collectingPost,
                    'lot_making' => $lotMaking,
                    'chute' => $chute,
                    'proses' => $proses,
                    'outgoing' => $outgoing,
                    'subcont' => $subcont,
                    'incoming' => $incoming,
                    'conveyance' => $conveyance,
                    'fluctuation_pct' => $fluctuationPct,
                    'fluctuation_time' => $fluctuationTime,
                    'grand_total_lead_time' => $grandTotalLeadTime,
                    'load_time' => $loadTime,
                    'lead_time' => $leadTime,
                    'total_lead_time' => $totalLeadTime,
                    'kanban_target' => $kanbanTarget,
                    'kanban_aktif' => $kanbanAktif,
                    'gap' => $gap
                ];

            });

        // 5. [BARU] SIAPKAN DATA FILTER DROPDOWN
        // Ambil data unik dari hasil collection di atas untuk dropdown frontend
        $filterLines = $kanbanData->pluck('line')->unique()->values();
        $filterCustomers = $kanbanData->pluck('customer')->unique()->values();

        return view('kanban.index', [
            'title' => 'Kalkulasi Kanban Produksi',
            'kanbanData' => $kanbanData,
            'month' => $month,
            'year' => $year,
            'workDays' => $workDays,

            // Kirim data filter ke View
            'filterLines' => $filterLines,
            'filterCustomers' => $filterCustomers
        ]);
    }

    public function saveInputs(Request $request)
    {
        // Validasi data
        $request->validate([
            'inputs' => 'required|array',
            'inputs.*.lot_size_pcs' => 'nullable|numeric|min:0',
            'inputs.*.collecting_post' => 'nullable|numeric|min:0',
            'inputs.*.kanban_post' => 'nullable|numeric|min:0',
            'inputs.*.conveyance' => 'nullable|numeric|min:0',
            'inputs.*.fluctuation' => 'nullable|numeric|min:0|max:100',
            'inputs.*.kanban_aktif' => 'nullable|numeric|min:0',
            'inputs.*.material_remarks' => 'nullable|string|max:255',
            'inputs.*.outgoing' => 'nullable|numeric|min:0',
            'inputs.*.subcont' => 'nullable|numeric|min:0',
            'inputs.*.incoming' => 'nullable|numeric|min:0',
            // [BARU] Validasi kolom baru
            'inputs.*.pcs_kanban_cust' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            // Loop setiap data yang dikirim dari form
            // $key adalah code_part (sesuai settingan name di view: inputs[CODE_PART][field])
            foreach ($request->inputs as $key => $data) {

                // Update tabel products berdasarkan CODE PART
                \App\Models\Product::where('code_part', $key)->update([
                    'lot_size_pcs' => $data['lot_size_pcs'] ?? 0,
                    'collecting_post' => $data['collecting_post'] ?? 0,
                    'kanban_post' => $data['kanban_post'] ?? 0,
                    'conveyance' => $data['conveyance'] ?? 0,
                    'outgoing'         => $data['outgoing'] ?? 0,
                    'subcont'     => $data['subcont'] ?? 0,
                    'incoming'    => $data['incoming'] ?? 0,
                    'fluctuation' => $data['fluctuation'] ?? 0,
                    'kanban_aktif' => $data['kanban_aktif'] ?? 0,
                    'material_remarks' => $data['material_remarks'] ?? null,

                    // [BARU] Simpan kolom baru
                    'pcs_kanban_cust' => $data['pcs_kanban_cust'] ?? 0,
                ]);
            }

            DB::commit();
            return back()->with('success', 'Data input berhasil disimpan!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan data: ' . $e->getMessage());
        }
    }

    // --- METHOD DOWNLOAD TEMPLATE CSV ---
    public function downloadTemplate()
    {
        $fileName = 'template_input_kanban_' . date('Y-m-d') . '.csv';

        // Ambil semua produk (Code Part & Nama Part sebagai referensi)
        $products = \App\Models\Product::select('code_part', 'part_name', 'lot_size_pcs', 'material_remarks', 'collecting_post', 'kanban_post', 'fluctuation', 'kanban_aktif')
            ->whereNull('deleted_at')
            ->orderBy('code_part', 'asc')
            ->get();

        $headers = array(
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        );

        $columns = array(
            'CODE PART (JANGAN UBAH)',
            'NAMA PART (REF)',
            'LOT SIZE PCS',
            'MAT. REMARKS',
            'COLLECTING POST',
            'KANBAN POST',
            'FLUKTUASI (%)',
            'KANBAN AKTIF'
        );

        $callback = function () use ($products, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($products as $row) {
                fputcsv($file, array(
                    $row->code_part,
                    $row->part_name,
                    $row->lot_size_pcs,
                    $row->material_remarks,
                    $row->collecting_post,
                    $row->kanban_post,
                    $row->fluctuation,
                    $row->kanban_aktif
                ));
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    // --- METHOD UPLOAD DATA CSV ---
    public function uploadData(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|mimes:csv,txt'
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->path(), 'r');

        // Skip Header (Baris pertama)
        fgetcsv($handle);

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle, 1000, ',')) !== FALSE) {
                // Mapping kolom CSV ke Database
                // Index 0: Code Part
                // Index 2: Lot Size Pcs
                // Index 3: Mat Remarks
                // Index 4: Collecting Post
                // Index 5: Kanban Post
                // Index 6: Fluktuasi
                // Index 7: Kanban Aktif

                $codePart = $row[0];

                // Cari produk berdasarkan Code Part
                $product = \App\Models\Product::where('code_part', $codePart)->first();

                if ($product) {
                    $product->update([
                        'lot_size_pcs' => is_numeric($row[2]) ? $row[2] : 0,
                        'material_remarks' => $row[3],
                        'collecting_post' => is_numeric($row[4]) ? $row[4] : 0,
                        'kanban_post' => is_numeric($row[5]) ? $row[5] : 0,
                        'fluctuation' => is_numeric($row[6]) ? $row[6] : 0,
                        'kanban_aktif' => is_numeric($row[7]) ? $row[7] : 0,
                    ]);
                }
            }

            DB::commit();
            fclose($handle);

            return back()->with('success', 'Data berhasil diimport dari CSV!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal import: ' . $e->getMessage());
        }
    }

    public function dailyReport(Request $request)
    {
        $date = $request->get('date', date('Y-m-d'));

        // --- 1. SETUP FILTER ---
        $selectedPlant = $request->get('plant', 'ALL');
        $selectedLineId = $request->get('line_id');

        // Ambil List Plant (Unik)
        $plants = \App\Models\ProductionLine::select('plant')->distinct()->orderBy('plant')->pluck('plant');

        // Ambil List Line (Sesuai Plant yang dipilih)
        $linesQuery = \App\Models\ProductionLine::orderBy('name');
        if ($selectedPlant !== 'ALL') {
            $linesQuery->where('plant', $selectedPlant);
        }
        $lines = $linesQuery->get();
        // -----------------------

        // Tentukan Tanggal H-1
        $prevDateObj = \Carbon\Carbon::parse($date)->subDay();
        $prevDate = $prevDateObj->format('Y-m-d');
        $month = $prevDateObj->format('m');
        $year = $prevDateObj->format('Y');

        // --- 2. AMBIL PRODUCT (FILTERED) ---
        // Kita hanya ambil produk yang memiliki Routing di Line/Plant yang dipilih
        $productsQuery = \App\Models\Product::orderBy('code_part');

        if ($selectedLineId) {
            // Filter by Line ID
            $productsQuery->whereHas('routings', function ($q) use ($selectedLineId) {
                $q->where('production_line_id', $selectedLineId);
            });
        } elseif ($selectedPlant !== 'ALL') {
            // Filter by Plant
            $productsQuery->whereHas('routings.productionLine', function ($q) use ($selectedPlant) {
                $q->where('plant', $selectedPlant);
            });
        }

        $products = $productsQuery->get();
        // -----------------------------------

        // 3. Ambil Target (Plan) HARI INI
        $todayPlans = \App\Models\DailyPlan::where('plan_date', $date)
            ->pluck('qty', 'code_part');

        // 4. HITUNG BALANCE H-1 (Sama seperti sebelumnya)
        $cumPlans = \App\Models\DailyPlan::whereMonth('plan_date', $month)
            ->whereYear('plan_date', $year)
            ->whereDate('plan_date', '<=', $prevDate)
            ->selectRaw('code_part, SUM(qty) as total_plan')
            ->groupBy('code_part')
            ->pluck('total_plan', 'code_part');

        $cumActuals = \App\Models\KanbanReport::whereMonth('report_date', $month)
            ->whereYear('report_date', $year)
            ->whereDate('report_date', '<=', $prevDate)
            ->selectRaw('code_part, SUM(act_shift_1 + act_shift_2) as total_act')
            ->groupBy('code_part')
            ->pluck('total_act', 'code_part');

        // 5. Ambil Data Report Hari Ini
        $todayReports = \App\Models\KanbanReport::where('report_date', $date)
            ->get()
            ->keyBy('code_part');

        $tableData = [];

        foreach ($products as $prod) {
            $code = $prod->code_part;

            $planToday = $todayPlans[$code] ?? 0;
            $totalPlanUntilYesterday = $cumPlans[$code] ?? 0;
            $totalActUntilYesterday = $cumActuals[$code] ?? 0;
            $balanceH1 = $totalActUntilYesterday - $totalPlanUntilYesterday;
            $current = $todayReports[$code] ?? null;

            $tableData[] = (object) [
                'product' => $prod,
                'qty_delay' => $balanceH1,
                'qty_target' => $planToday,
                'data_report' => $current
            ];
        }

        return view('kanban.daily_report', compact(
            'tableData',
            'date',
            'plants',
            'lines',
            'selectedPlant',
            'selectedLineId' // Kirim variabel filter ke view
        ));
    }

    public function storeDailyReport(Request $request)
    {
        $date = $request->date;
        $inputs = $request->input('reports', []);

        foreach ($inputs as $codePart => $data) {
            // Casting ke integer agar aman
            $act1 = (int) ($data['act_shift_1'] ?? 0);
            $ng1 = (int) ($data['ng_shift_1'] ?? 0); // <--- BARU

            $act2 = (int) ($data['act_shift_2'] ?? 0);
            $ng2 = (int) ($data['ng_shift_2'] ?? 0); // <--- BARU

            \App\Models\KanbanReport::updateOrCreate(
                ['report_date' => $date, 'code_part' => $codePart],
                [
                    'qty_delay' => $data['qty_delay'] ?? 0,
                    'qty_target' => $data['qty_target'] ?? 0,

                    // Shift 1
                    'pic_shift_1' => $data['pic_shift_1'] ?? null,
                    'act_shift_1' => $act1,
                    'ng_shift_1' => $ng1,             // <--- SIMPAN NG
                    'lot_shift_1' => $data['lot_shift_1'] ?? null,

                    // Shift 2
                    'pic_shift_2' => $data['pic_shift_2'] ?? null,
                    'act_shift_2' => $act2,
                    'ng_shift_2' => $ng2,             // <--- SIMPAN NG
                    'lot_shift_2' => $data['lot_shift_2'] ?? null,

                    // Note
                    'keterangan' => $data['keterangan'] ?? null,
                ]
            );
        }

        return back()->with('success', 'Laporan Harian (OK & NG) Berhasil Disimpan!');
    }





}