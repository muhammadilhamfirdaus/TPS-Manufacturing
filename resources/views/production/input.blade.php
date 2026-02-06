@extends('layouts.app_simple')

@section('title', 'Input Matrix Produksi')

@section('content')
    <style>
        /* === LAYOUT UTAMA === */
        .table-container {
            max-height: 78vh;
            overflow: auto;
            border: 1px solid #ccc;
            position: relative;
            background-color: #fff;
        }

        .table-matrix {
            width: max-content;
            border-collapse: separate;
            border-spacing: 0;
            /* table-layout: fixed; Hapus ini jika konten terpotong, tapi pakai width manual lebih aman */
        }

        .table-matrix th,
        .table-matrix td {
            padding: 4px 2px;
            vertical-align: middle;
            border: 1px solid #dee2e6;
            font-size: 0.7rem;
            box-sizing: border-box;
        }

        /* === STICKY CONFIGURATION === */
        
        /* 1. Sticky Header */
        .sticky-top {
            position: sticky;
            top: 0;
            z-index: 40;
            background-color: #f8f9fa;
            box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.1);
        }

        /* 2. Sticky Columns (Kolom Kiri) */
        .sticky-col {
            position: sticky;
            left: 0;
            background-color: #fff;
            z-index: 20;
            border-right: 1px solid #dee2e6;
        }

        /* 3. Intersection (Pojok Kiri Atas) */
        thead .sticky-col {
            z-index: 50;
            background-color: #e9ecef;
        }

        /* === POSISI KOLOM (FREEZE) === */
        
        /* 1. NO (35px) -> Start 0 */
        .col-1-no { left: 0px; width: 35px; min-width: 35px; }

        /* 2. CODE (70px) -> Start 35 */
        .col-2-code { left: 35px; width: 70px; min-width: 70px; }

        /* 3. PART NAME (120px) -> Start 105 */
        .col-3-part { left: 105px; width: 120px; min-width: 120px; }

        /* 4. KBN (50px) -> Start 225 */
        .col-4-kbn { left: 225px; width: 50px; min-width: 50px; }

        /* 5. PHOTO (60px) -> Start 275 */
        .col-5-photo { left: 275px; width: 60px; min-width: 60px; }

        /* 6. TARGET (60px) -> Start 335 */
        .col-6-total { left: 335px; width: 60px; min-width: 60px; }

        /* 7. SISA (60px) -> Start 395 */
        .col-7-sisa { left: 395px; width: 60px; min-width: 60px; }

        /* 8. TYPE (40px) -> Start 455 */
        .col-8-type { 
            left: 455px; width: 40px; min-width: 40px; 
            border-right: 2px solid #6c757d !important; /* Batas Freeze */
        }

        /* === STYLE LAIN === */
        .input-act { width: 100%; min-width: 70px; border: none; text-align: center; font-weight: bold; background: transparent; padding: 0; font-size: 0.75rem; color: #198754; }
        .input-act:focus { outline: 2px solid #198754; background: #fff; }
        .text-plan { font-weight: bold; color: #0d6efd; }
        .is-weekend { background-color: #e9ecef !important; } 
        .is-holiday { background-color: #fee2e2 !important; color: #b91c1c !important; }
        
        .img-part { width: 40px; height: 40px; object-fit: cover; border-radius: 4px; border: 1px solid #dee2e6; cursor: pointer; }
        .img-placeholder { font-size: 0.6rem; color: #ccc; text-align: center; display: flex; align-items: center; justify-content: center; height: 40px; border: 1px solid #eee; }
    </style>

    <div class="container-fluid px-0">

        <div class="card mb-3 border-0 shadow-sm">
        <div class="card-body py-2 d-flex justify-content-between align-items-center flex-wrap">
            
        {{-- BAGIAN KIRI: JUDUL & FILTER --}}
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <h5 class="fw-bold text-dark mb-0 me-2"><i class="fas fa-edit text-primary me-2"></i>Input Matrix</h5>

            {{-- FILTER FORM --}}
            <form id="filterForm" action="{{ route('production.input') }}" method="GET" class="d-flex gap-2 align-items-center">
                <select name="plant" id="plantSelect" class="form-select form-select-sm fw-bold border-primary text-primary" style="width: 100px;" onchange="filterLines()">
                    @foreach($plants as $p)
                        <option value="{{ $p }}" {{ $selectedPlant == $p ? 'selected' : '' }}>{{ $p }}</option>
                    @endforeach
                </select>

                <select name="line_id" id="lineSelect" class="form-select form-select-sm fw-bold border-primary text-primary" style="width: 150px;" onchange="this.form.submit()">
                    {{-- Option Lines di-generate via JS / Controller --}}
                    @foreach($lines as $l)
                        <option value="{{ $l->id }}" {{ $lineId == $l->id ? 'selected' : '' }}>{{ $l->name }}</option>
                    @endforeach
                </select>

                <select name="filter_month" class="form-select form-select-sm fw-bold border-secondary" style="width: 110px;" onchange="this.form.submit()">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $selectedMonth == $m ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option> 
                    @endfor
                </select>
                <select name="filter_year" class="form-select form-select-sm fw-bold border-secondary" style="width: 80px;" onchange="this.form.submit()">
                    @for($y = 2024; $y <= 2026; $y++)
                        <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>{{ $y }}</option> 
                    @endfor
                </select>
                {{-- [BARU] INPUT SEARCH --}}
                <div class="input-group input-group-sm ms-2" style="width: 200px;">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" id="tableSearch" class="form-control border-start-0" placeholder="Cari Part..." onkeyup="searchPart()">
                </div>
            </form>
        </div>

        {{-- BAGIAN KANAN: TOMBOL AKSI --}}
        <div class="d-flex gap-2">
            
            {{-- LOGIC CEK TANGGAL (UNTUK MENGUNCI TOMBOL SYNC) --}}
            @php
                // Buat tanggal dari filter
                $filterDate = \Carbon\Carbon::create($selectedYear, $selectedMonth, 1)->startOfMonth();
                // Buat tanggal hari ini
                $currDate   = \Carbon\Carbon::now()->startOfMonth();
                // Cek apakah lampau
                $isLocked   = $filterDate->lt($currDate);
            @endphp

            @if($isLocked)
                {{-- OPSI 1: TAMPILAN JIKA BULAN LALU (LOCKED) --}}
                <button type="button" class="btn btn-secondary btn-sm fw-bold shadow-sm disabled" style="cursor: not-allowed;" title="Data bulan lalu terkunci">
                    <i class="fas fa-lock me-1"></i> SYNC PLAN (LOCKED)
                </button>
            @else
             
                {{-- Tambahkan prefix 'production.' di depannya --}}
                <form id="formSyncPlan" action="{{ route('production.sync_plan') }}" method="POST" style="display: none;">
                    @csrf
                    <input type="hidden" name="month" value="{{ $selectedMonth }}">
                    <input type="hidden" name="year" value="{{ $selectedYear }}">
                </form>

                <button type="submit" form="formSyncPlan" class="btn btn-outline-primary btn-sm fw-bold shadow-sm" 
                    onclick="return confirm('Apakah Anda yakin ingin menarik data terbaru dari Google Sheet? Data manual bulan ini mungkin tertimpa.')">
                    <i class="fas fa-cloud-download-alt me-1"></i> SYNC PLAN
                </button>
            @endif

            {{-- Link ke Laporan Harian --}}
            <a href="{{ route('kanban.daily_report') }}" class="btn btn-primary btn-sm fw-bold shadow-sm">
                <i class="fas fa-edit me-1"></i> INPUT LAPORAN HARIAN
            </a>
        </div>

    </div>
    <div>

        @if(session('success')) <div class="alert alert-success py-2 mb-2 small fw-bold text-center border-0 bg-success bg-opacity-10 text-success">{{ session('success') }}</div> @endif
        @if(session('error')) <div class="alert alert-danger py-2 mb-2 small fw-bold text-center border-0 bg-danger bg-opacity-10 text-danger">{{ session('error') }}</div> @endif

      {{-- 1. FIX FORM SYNC: Gunakan 'sync_plan' (Sesuai routes/web.php) --}}
        {{-- Tambahkan prefix 'production.' di depannya --}}
        <form id="formSyncPlan" action="{{ route('production.sync_plan') }}" method="POST" style="display: none;">
            @csrf
            <input type="hidden" name="month" value="{{ $selectedMonth }}">
            <input type="hidden" name="year" value="{{ $selectedYear }}">
        </form>

        {{-- 2. FIX FORM STORE: Gunakan 'production.store' (Untuk Operator Actual) --}}
        {{-- Jangan pakai 'plans.store_actuals' karena itu milik PPIC/Admin --}}
        <form id="formInputAct" action="{{ route('production.store') }}" method="POST">
            @csrf
            <input type="hidden" name="month" value="{{ $selectedMonth }}">
            <input type="hidden" name="year" value="{{ $selectedYear }}">

            <div class="table-container shadow-sm">
                 {{-- ... isi tabel ... --}}
               <table class="table table-bordered table-matrix mb-0">
                <thead class="bg-light sticky-top">
                    <tr>
                        {{-- HEADER: GUNAKAN CLASS BARU --}}
                        <th class="sticky-col col-1-no text-center">NO</th>
                        <th class="sticky-col col-2-code text-center">CODE</th>
                        <th class="sticky-col col-3-part text-center">PART NAME</th>
                        <th class="sticky-col col-4-kbn text-center">KBN</th>
                        <th class="sticky-col col-5-photo text-center">PHOTO</th>
                        <th class="sticky-col col-6-total text-center">TARGET</th>
                        <th class="sticky-col col-7-sisa text-center">SISA</th>
                        <th class="sticky-col col-8-type text-center">TYPE</th>
                        
                        {{-- [TAMBAHAN BARU] KOLOM DELAY --}}
                        <th class="text-center bg-warning text-dark align-middle" style="min-width: 60px;">DELAY</th>
                        {{-- ----------------------------- --}}

                        @php $daysInMonth = \Carbon\Carbon::create($selectedYear, $selectedMonth)->daysInMonth; @endphp
                        @for($d = 1; $d <= $daysInMonth; $d++)
                            @php
                                $dt = \Carbon\Carbon::create($selectedYear, $selectedMonth, $d);
                                $isHol = isset($holidays) && in_array($dt->format('Y-m-d'), $holidays);
                                $bg = $isHol ? 'bg-danger text-white' : ($dt->isWeekend() ? 'bg-secondary text-white' : '');
                            @endphp
                            <th class="text-center {{ $bg }}" style="width: 35px; min-width: 35px;">{{ $d }}</th>
                        @endfor
                    </tr>
                </thead>
                <tbody>
                    @forelse($matrixData as $code => $items)
                        @php
                            $prod = $items->first()->product;
                            $partName = $prod->part_name ?? '-';
                            $qtyKbn = $prod->qty_per_box ?? '-';
                            $photo = $prod->photo ?? null;
                            
                            // 1. Target
                            $sumPlan = $items->sum('qty_plan');
                            
                            // 2. Actual
                            $myActuals = $actualData[$code] ?? [];
                            $sumAct = array_sum($myActuals);
                            
                            // 3. Sisa
                            $sisa = $sumPlan - ($sumAct*$qtyKbn);
                            
                            // 4. Delay
                            $valDelay = $delayData[$code] ?? 0;
                        @endphp

                        {{-- [REVISI 1] Tambahkan class 'search-item' & data-group --}}
                        <tr class="row-plan search-item" data-group="{{ $code }}">
                            
                            {{-- KOLOM IDENTITAS --}}
                            <td class="sticky-col col-1-no text-center fw-bold bg-white" rowspan="3">{{ $loop->iteration }}</td>
                            
                            {{-- [REVISI 2] Tambahkan class 'search-code' untuk target pencarian --}}
                            <td class="sticky-col col-2-code fw-bold text-center bg-white search-code" rowspan="3">{{ $code }}</td>
                            
                            {{-- [REVISI 3] Tambahkan class 'search-name' untuk target pencarian --}}
                            <td class="sticky-col col-3-part bg-white search-name" rowspan="3">
                                <span class="d-block text-truncate" style="max-width:115px;" title="{{ $partName }}">{{ $partName }}</span>
                            </td>

                            <td class="sticky-col col-4-kbn text-center fw-bold bg-white" rowspan="3">{{ $qtyKbn }}</td>
                            <td class="sticky-col col-5-photo text-center bg-white p-1" rowspan="3">
                                @if($photo)
                                    <img src="{{ asset('storage/' . $photo) }}" class="img-part" alt="Img" onclick="window.open(this.src, '_blank')">
                                @else
                                    <div class="img-placeholder mx-auto">No Pic</div>
                                @endif
                            </td>
                            <td class="sticky-col col-6-total text-center fw-bold text-primary bg-white" rowspan="3">{{ number_format($sumPlan) }}</td>
                            <td class="sticky-col col-7-sisa text-center fw-bold bg-white {{ $sisa > 0 ? 'text-danger' : 'text-success' }}" rowspan="3">{{ number_format($sisa) }}</td>
                            
                            {{-- KOLOM TYPE (PLN) --}}
                            <td class="sticky-col col-8-type text-center small fw-bold text-muted bg-white">PLN</td>

                            {{-- KOLOM DELAY --}}
                            @php
                                $colorDelay = $valDelay < 0 ? 'text-danger' : ($valDelay > 0 ? 'text-success' : 'text-dark');
                                $bgDelay = $valDelay < 0 ? 'bg-danger-subtle' : ''; 
                            @endphp
                            <td class="text-center align-middle fw-bold {{ $colorDelay }} {{ $bgDelay }}" rowspan="3" style="font-size: 13px;">
                                {{ $valDelay != 0 ? number_format($valDelay) : '-' }}
                            </td>

                            {{-- DATA HARIAN (PLAN) --}}
                            @for($d = 1; $d <= $daysInMonth; $d++)
                                <td class="text-center text-primary fw-bold" id="p_{{$code}}_{{$d}}" data-val="{{ $dailyPlanData[$code][$d] ?? 0 }}">
                                    {{ isset($dailyPlanData[$code][$d]) ? number_format($dailyPlanData[$code][$d]) : '-' }}
                                </td>
                            @endfor
                        </tr>

                        {{-- [REVISI 4] Baris ACTUAL (ACT) - Tambah search-item & data-group --}}
                        <tr class="search-item" data-group="{{ $code }}">
                            <td class="fw-bold text-success text-center">ACT</td>
                            @for($d = 1; $d <= $daysInMonth; $d++)
                                @php
                                    $actValue = $actualData[$code][$d] ?? 0;
                                @endphp
                                <td class="p-1 align-middle text-center" style="min-width: 60px;">
                                    @if($actValue > 0)
                                        <span class="d-block w-100 py-1 fw-bold text-success bg-success bg-opacity-10 rounded">
                                            {{ number_format($actValue) }}
                                        </span>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                            @endfor
                        </tr>

                        {{-- [REVISI 5] Baris DIFF (±) - Tambah search-item & data-group --}}
                        <tr class="row-diff search-item" data-group="{{ $code }}">
                            <td class="sticky-col col-8-type text-center small fw-bold text-muted bg-white">±</td>
                            
                            @php $akumulasi = $valDelay; @endphp

                            @for($d = 1; $d <= $daysInMonth; $d++)
                                @php 
                                    $p = $dailyPlanData[$code][$d] ?? 0;
                                    $a = $actualData[$code][$d] ?? 0;
                                    $akumulasi += ($a - $p); 
                                    $col = $akumulasi < 0 ? 'text-danger' : 'text-success';
                                @endphp
                                <td class="text-center small fw-bold {{ $col }}" id="d_{{$code}}_{{$d}}">
                                    {{ $akumulasi }}
                                </td>
                            @endfor
                        </tr>

                        {{-- [REVISI 6] Baris Separator - Tambah search-item & data-group --}}
                        <tr class="search-item" data-group="{{ $code }}">
                            <td colspan="46" class="p-0 bg-secondary" style="height: 2px;"></td>
                        </tr>

                    @empty
                        <tr><td colspan="46" class="text-center py-5 text-muted">Tidak ada part di Line ini.</td></tr>
                    @endforelse
                </tbody>
            </table> 
            </div>
        </form>
    </div>

    <script>
        const masterLines = @json($allLines); 
        const currentLineId = "{{ $lineId }}";

        function filterLines() {
            const plantSelect = document.getElementById('plantSelect');
            const lineSelect = document.getElementById('lineSelect');
            const selectedPlant = plantSelect.value;
            
            lineSelect.innerHTML = '<option value="">- Pilih Line -</option>';
            if (selectedPlant) {
                const filteredLines = masterLines.filter(line => line.plant == selectedPlant);
                filteredLines.forEach(line => {
                    const isSelected = (line.id == currentLineId) ? 'selected' : '';
                    lineSelect.innerHTML += `<option value="${line.id}" ${isSelected}>${line.name}</option>`;
                });
            }
        }

        document.addEventListener("DOMContentLoaded", function() {
            filterLines();
        });

        function calcDiff(code, day, input) {
            let planVal = parseInt(document.getElementById(`plan_${code}_${day}`).getAttribute('data-val')) || 0;
            let actVal = parseInt(input.value) || 0;
            let diff = actVal - planVal;

            let tdDiff = document.getElementById(`diff_${code}_${day}`);
            tdDiff.innerText = diff;
            tdDiff.className = "text-center " + (diff < 0 ? 'text-danger' : 'text-success');
        }

        // ... script yang sudah ada ...

        function searchPart() {
            // 1. Ambil value input & ubah ke lowercase
            let input = document.getElementById("tableSearch").value.toLowerCase();
            
            // 2. Ambil semua baris utama (row-plan) yang memiliki data text
            let mainRows = document.querySelectorAll(".row-plan");

            mainRows.forEach(row => {
                // 3. Ambil text Code & Name dari baris tersebut
                let code = row.querySelector(".search-code").innerText.toLowerCase();
                let name = row.querySelector(".search-name").innerText.toLowerCase();
                let groupId = row.getAttribute("data-group");

                // 4. Cek apakah input cocok dengan Code atau Name
                let isMatch = code.includes(input) || name.includes(input);

                // 5. Ambil semua baris yang satu grup (Plan, Act, Diff, Separator)
                let groupRows = document.querySelectorAll(`.search-item[data-group="${groupId}"]`);

                // 6. Tampilkan/Sembunyikan satu grup
                groupRows.forEach(r => {
                    r.style.display = isMatch ? "" : "none";
                });
            });
        }
    </script>
@endsection