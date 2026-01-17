@extends('layouts.app_simple')

@section('title', 'Monitoring Produksi')

@section('content')
<style>
    /* 1. KUNCI LAYOUT TABEL AGAR TIDAK GOYANG SAAT ZOOM */
    .table-container {
        max-height: 75vh;
        overflow: auto;
        border: 1px solid #ccc;
        position: relative;
    }

    .table-matrix {
        width: max-content; /* Pastikan tabel melebar sesuai konten */
        border-collapse: separate; /* Wajib untuk sticky */
        border-spacing: 0;
        table-layout: fixed; /* KUNCI: Agar lebar kolom patuh pada CSS */
    }

    .table-matrix th, .table-matrix td {
        padding: 6px 4px;
        vertical-align: middle;
        border: 1px solid #dee2e6;
        font-size: 0.7rem;
        box-sizing: border-box; /* Agar padding tidak menambah lebar total */
    }

    /* 2. DEFINISI STICKY COLUMN (FREEZE) */
    .sticky-col {
        position: sticky;
        left: 0;
        background-color: #fff;
        z-index: 10; /* Di atas kolom tanggal */
        
        /* Kunci Lebar agar tidak berubah saat zoom */
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    /* Header Sticky butuh z-index lebih tinggi agar menimpa isi tabel saat scroll ke bawah */
    thead .sticky-col {
        z-index: 20; 
        background-color: #f8f9fa; /* Warna header */
    }

    /* 3. MATEMATIKA LEBAR KOLOM (SANGAT PENTING!)
       Rumus: Left Kolom Ini = Left Kolom Sebelumnya + Lebar Kolom Sebelumnya
    */

    /* Kolom 1: NO (Lebar 35px) */
    .col-1-no { 
        left: 0px; 
        width: 35px; min-width: 35px; max-width: 35px;
    }

    /* Kolom 2: CODE (Lebar 80px) -> Left = 35 */
    .col-2-code { 
        left: 35px; 
        width: 80px; min-width: 80px; max-width: 80px;
    }

    /* Kolom 3: PART NO (Lebar 110px) -> Left = 35 + 80 = 115 */
    .col-3-part { 
        left: 115px; 
        width: 110px; min-width: 110px; max-width: 110px;
    }

    /* Kolom 4: DESKRIPSI (Lebar 180px) -> Left = 115 + 110 = 225 */
    .col-4-desc { 
        left: 225px; 
        width: 180px; min-width: 180px; max-width: 180px;
    }

    /* Kolom 5: PLAN (Lebar 60px) -> Left = 225 + 180 = 405 */
    .col-5-plan { 
        left: 405px; 
        width: 60px; min-width: 60px; max-width: 60px;
    }

    /* Kolom 6: SISA (Lebar 60px) -> Left = 405 + 60 = 465 */
    .col-6-sisa { 
        left: 465px; 
        width: 60px; min-width: 60px; max-width: 60px;
    }

    /* Kolom 7: ADD (Lebar 45px) -> Left = 465 + 60 = 525 */
    /* Ini kolom terakhir yang freeze, kasih border kanan tebal */
    .col-7-add { 
        left: 525px; 
        width: 45px; min-width: 45px; max-width: 45px;
        border-right: 3px solid #6c757d !important; /* Batas Freeze */
    }


    /* Styling Lainnya */
    .row-plan { background-color: #f8f9fa; color: #6c757d; }
    .row-act  { background-color: #ffffff; }
    .row-diff { background-color: #f1f5f9; font-weight: bold; }

    .input-act {
        width: 100%;
        min-width: 30px;
        border: none;
        text-align: center;
        font-weight: bold;
        color: #0d6efd;
        background: transparent;
        padding: 0;
        font-size: 0.75rem;
    }
    .input-act:focus { outline: 2px solid #86b7fe; background: #fff; }

    .is-weekend { background-color: #e2e3e5 !important; } 
    .is-holiday { background-color: #fee2e2 !important; color: #b91c1c !important; }
</style>

<div class="container-fluid px-0">
    
    {{-- FILTER HEADER --}}
    <form action="{{ route('production.input') }}" method="GET" class="card mb-3 border-0 shadow-sm">
        <div class="card-body py-2 d-flex gap-3 align-items-center flex-wrap">
            <div class="d-flex align-items-center gap-2">
                <label class="fw-bold small text-muted">FILTER LINE:</label>
                <select name="line_id" class="form-select form-select-sm fw-bold border-secondary" style="width: 160px;" onchange="this.form.submit()">
                    @foreach($lines as $line)
                        <option value="{{ $line->id }}" {{ $lineId == $line->id ? 'selected' : '' }}>
                            {{ $line->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="d-flex align-items-center gap-2">
                <select name="month" class="form-select form-select-sm" onchange="this.form.submit()">
                    @for($m=1; $m<=12; $m++)
                        <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ date('F', mktime(0,0,0,$m,1)) }}</option>
                    @endfor
                </select>
                <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="2025" {{ $year == 2025 ? 'selected' : '' }}>2025</option>
                    <option value="2026" {{ $year == 2026 ? 'selected' : '' }}>2026</option>
                </select>
            </div>
            
            <div class="px-3 py-1 bg-light border rounded fw-bold text-primary small d-none d-md-block">
                <i class="fas fa-calendar-day me-1"></i> Hari Kerja: {{ $totalWorkingDays }} Hari
            </div>

            <div class="ms-auto">
                <button type="submit" form="formMatrix" class="btn btn-primary btn-sm fw-bold shadow-sm">
                    <i class="fas fa-save me-1"></i> SIMPAN
                </button>
            </div>
        </div>
    </form>

    @if(session('success'))
        <div class="alert alert-success py-2 mb-2 small fw-bold text-center border-0 bg-success bg-opacity-25 text-success">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
        </div>
    @endif

    {{-- TABLE MATRIX --}}
    <form id="formMatrix" action="{{ route('production.store') }}" method="POST">
        @csrf
        <input type="hidden" name="month" value="{{ $month }}">
        <input type="hidden" name="year" value="{{ $year }}">

        <div class="table-container bg-white shadow-sm">
            <table class="table table-bordered table-matrix mb-0">
                <thead class="bg-light sticky-top">
                    <tr>
                        <th class="sticky-col col-1-no text-center">NO</th>
                        <th class="sticky-col col-2-code text-center">CODE</th>
                        <th class="sticky-col col-3-part text-center">PART NO</th>
                        <th class="sticky-col col-4-desc text-center">DESKRIPSI</th>
                        <th class="sticky-col col-5-plan text-center">PLAN</th>
                        <th class="sticky-col col-6-sisa text-center bg-warning bg-opacity-25 text-dark border-warning">SISA</th>
                        <th class="sticky-col col-7-add text-center">ADD</th>
                        
                        {{-- Loop Tanggal --}}
                        @for($d=1; $d<=$daysInMonth; $d++)
                            @php
                                $dateObj = \Carbon\Carbon::create($year, $month, $d);
                                $isWeekend = $dateObj->isWeekend();
                                $isHoliday = isset($holidays) && array_key_exists($d, $holidays);
                                
                                $colClass = '';
                                if ($isHoliday) $colClass = 'bg-danger text-white border-danger';
                                elseif ($isWeekend) $colClass = 'bg-secondary text-white border-secondary';
                                
                                $title = $isHoliday ? ($holidays[$d] ?? 'Libur') : ($isWeekend ? 'Weekend' : '');
                            @endphp
                            <th class="text-center {{ $colClass }}" style="width: 35px; min-width: 35px;" title="{{ $title }}">
                                {{ $d }}
                            </th>
                        @endfor
                    </tr>
                </thead>
                <tbody>
                    @forelse($plans as $index => $plan)
                        @php
                            $dailyPlanAvg = ($plan->qty_plan > 0 && $totalWorkingDays > 0) 
                                            ? round($plan->qty_plan / $totalWorkingDays) : 0; 
                            
                            $totalActualMonth = $plan->productionActuals->sum('qty_good');
                            $balance = $plan->qty_plan - $totalActualMonth;
                            $balanceColor = $balance > 0 ? 'text-danger' : 'text-success';
                        @endphp

                        <tr class="row-plan">
                            <td class="sticky-col col-1-no text-center fw-bold" rowspan="3" style="background:#fff;">{{ $loop->iteration }}</td>
                            <td class="sticky-col col-2-code fw-bold" rowspan="3" style="background:#fff;">{{ $plan->product->code_part ?? '-' }}</td>
                            <td class="sticky-col col-3-part fw-bold" rowspan="3" style="background:#fff;">{{ $plan->product->part_number }}</td>
                            <td class="sticky-col col-4-desc" rowspan="3" style="background:#fff;">
                                <div title="{{ $plan->product->part_name }}">
                                    {{ $plan->product->part_name }}
                                </div>
                            </td>
                            <td class="sticky-col col-5-plan text-center fw-bold text-primary" rowspan="3" style="background:#fff;">
                                {{ number_format($plan->qty_plan) }}
                            </td>
                            <td class="sticky-col col-6-sisa text-center fw-bold {{ $balanceColor }} bg-light border-warning" rowspan="3">
                                {{ number_format($balance) }}
                            </td>
                            <td class="sticky-col col-7-add text-center small fw-bold">PLAN</td>

                            {{-- Isi Data Plan --}}
                            @for($d=1; $d<=$daysInMonth; $d++)
                                @php 
                                    $isWk = \Carbon\Carbon::create($year, $month, $d)->isWeekend(); 
                                    $isHol = isset($holidays) && array_key_exists($d, $holidays);
                                @endphp
                                <td class="text-center {{ ($isWk || $isHol) ? ($isHol ? 'is-holiday' : 'is-weekend') : '' }}">
                                    {{ (!$isWk && !$isHol) ? $dailyPlanAvg : '' }}
                                </td>
                            @endfor
                        </tr>

                        <tr class="row-act">
                            <td class="sticky-col col-7-add text-center small fw-bold text-success">ACT</td>
                            @for($d=1; $d<=$daysInMonth; $d++)
                                @php 
                                    $dateObj = \Carbon\Carbon::create($year, $month, $d);
                                    $isWk = $dateObj->isWeekend();
                                    $isHol = isset($holidays) && array_key_exists($d, $holidays);
                                    $val = $matrixActuals[$plan->id][$d] ?? '';
                                    $bg = $isHol ? 'is-holiday' : ($isWk ? 'is-weekend' : '');
                                    $readonly = (($isWk || $isHol) && empty($val)) ? 'readonly' : '';
                                @endphp
                                <td class="p-0 {{ $bg }}">
                                    <input type="number" name="actuals[{{ $plan->id }}][{{ $d }}]" value="{{ $val }}" 
                                           class="input-act" {{ $readonly }} onchange="calculateDiff(this, {{ $dailyPlanAvg }})">
                                </td>
                            @endfor
                        </tr>

                        <tr class="row-diff">
                            <td class="sticky-col col-7-add text-center small fw-bold text-muted">±</td>
                            @for($d=1; $d<=$daysInMonth; $d++)
                                @php 
                                    $dateObj = \Carbon\Carbon::create($year, $month, $d);
                                    $isWk = $dateObj->isWeekend();
                                    $isHol = isset($holidays) && array_key_exists($d, $holidays);
                                    $act = $matrixActuals[$plan->id][$d] ?? 0;
                                    $pln = (!$isWk && !$isHol) ? $dailyPlanAvg : 0;
                                    $diff = ($act > 0 || (!$isWk && !$isHol)) ? ($act - $pln) : '';
                                    $textColor = '';
                                    if(is_numeric($diff)) $textColor = $diff < 0 ? 'text-danger' : 'text-success';
                                    $bg = $isHol ? 'is-holiday' : ($isWk ? 'is-weekend' : '');
                                @endphp
                                <td class="text-center {{ $bg }} {{ $textColor }}">{{ $diff }}</td>
                            @endfor
                        </tr>
                        <tr><td colspan="45" style="padding:0; border-top: 2px solid #adb5bd;"></td></tr>
                    @empty
                        <tr><td colspan="45" class="text-center py-5 text-muted">Belum ada Plan untuk Line & Periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>
</div>

<script>
    function calculateDiff(input, dailyPlan) {
        let td = input.parentElement;
        let trAct = td.parentElement;
        let cellIndex = td.cellIndex;
        let trDiff = trAct.nextElementSibling;
        let tdDiff = trDiff.children[cellIndex]; 
        
        let actualVal = parseInt(input.value) || 0;
        let planVal = parseInt(dailyPlan) || 0;
        
        if(td.classList.contains('is-holiday') || td.classList.contains('is-weekend')) {
            planVal = 0;
        }

        let diff = actualVal - planVal;
        tdDiff.innerText = diff;
        
        if(diff < 0) tdDiff.className = "text-center text-danger fw-bold " + td.className;
        else tdDiff.className = "text-center text-success fw-bold " + td.className;
    }
</script>
@endsection