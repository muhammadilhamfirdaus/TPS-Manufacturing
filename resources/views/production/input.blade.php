@extends('layouts.app_simple')

@section('title', 'Monitoring Produksi')

@section('content')
<style>
    /* Styling Khusus Tabel Matrix */
    .table-matrix { font-size: 0.75rem; white-space: nowrap; }
    .table-matrix th, .table-matrix td { padding: 4px 6px; vertical-align: middle; border: 1px solid #dee2e6; }
    
    /* Sticky Columns */
    .sticky-col { position: sticky; left: 0; background-color: #fff; z-index: 10; border-right: 2px solid #ccc !important; }
    .sticky-col-1 { left: 0; width: 40px; }
    .sticky-col-2 { left: 40px; width: 100px; }
    .sticky-col-3 { left: 140px; width: 120px; } 
    .sticky-col-4 { left: 260px; width: 200px; } 
    .sticky-col-5 { left: 460px; width: 80px; } 
    .sticky-col-6 { left: 540px; width: 80px; } 
    .sticky-col-7 { left: 620px; width: 60px; } 

    .row-plan { background-color: #f8f9fa; color: #6c757d; }
    .row-act  { background-color: #ffffff; }
    .row-diff { background-color: #f1f5f9; font-weight: bold; }

    .input-act { width: 100%; border: none; text-align: center; font-weight: bold; color: #0d6efd; background: transparent; padding: 0; }
    .input-act:focus { outline: 2px solid #86b7fe; background: #fff; }

    .is-weekend { background-color: #e2e3e5 !important; } 
    .is-holiday { background-color: #fee2e2 !important; color: #b91c1c !important; }
</style>

<div class="container-fluid px-0">
    
    {{-- FILTER HEADER --}}
    <form action="{{ route('production.input') }}" method="GET" class="card mb-3 border-0 shadow-sm">
        <div class="card-body py-2 d-flex gap-3 align-items-center">
            <div class="d-flex align-items-center gap-2">
                <label class="fw-bold small text-muted">FILTER LINE:</label>
                <select name="line_id" class="form-select form-select-sm fw-bold border-secondary" style="width: 150px;" onchange="this.form.submit()">
                    @foreach($lines as $line)
                        <option value="{{ $line->id }}" {{ $lineId == $line->id ? 'selected' : '' }}>{{ $line->name }}</option>
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
            
            {{-- INFO HARI KERJA --}}
            <div class="ms-3 px-3 py-1 bg-light border rounded fw-bold text-primary small">
                Hari Kerja: {{ $totalWorkingDays }} Hari
            </div>

            <div class="ms-auto">
                <button type="submit" form="formMatrix" class="btn btn-primary btn-sm fw-bold">
                    <i class="fas fa-save me-1"></i> SIMPAN PERUBAHAN
                </button>
            </div>
        </div>
    </form>

    @if(session('success')) <div class="alert alert-success py-2 mb-2 small fw-bold text-center">{{ session('success') }}</div> @endif

    <form id="formMatrix" action="{{ route('production.store') }}" method="POST">
        @csrf
        <input type="hidden" name="month" value="{{ $month }}">
        <input type="hidden" name="year" value="{{ $year }}">

        <div class="table-responsive bg-white shadow-sm" style="max-height: 80vh; border: 1px solid #ccc;">
            <table class="table table-bordered table-matrix mb-0">
                <thead class="bg-light sticky-top" style="z-index: 20;">
                    <tr>
                        <th class="sticky-col sticky-col-1 text-center">NO</th>
                        <th class="sticky-col sticky-col-2 text-center">CODE PART</th>
                        <th class="sticky-col sticky-col-3 text-center">PART NUMBER</th>
                        <th class="sticky-col sticky-col-4 text-center">DESKRIPSI PART</th>
                        <th class="sticky-col sticky-col-5 text-center">RENCANA</th>
                        <th class="sticky-col sticky-col-6 text-center bg-warning bg-opacity-10">BALANCE</th>
                        <th class="sticky-col sticky-col-7 text-center">ADD</th>
                        
                        @for($d=1; $d<=$daysInMonth; $d++)
                            @php
                                $dateObj = \Carbon\Carbon::create($year, $month, $d);
                                $isWeekend = $dateObj->isWeekend();
                                $isHoliday = isset($holidays) && array_key_exists($d, $holidays);
                                
                                $colClass = '';
                                if ($isHoliday) $colClass = 'bg-danger text-white';
                                elseif ($isWeekend) $colClass = 'bg-secondary text-white';
                                
                                $title = $isHoliday ? ($holidays[$d] ?? 'Libur') : ($isWeekend ? 'Akhir Pekan' : '');
                            @endphp
                            <th class="text-center {{ $colClass }}" style="min-width: 40px;" title="{{ $title }}">
                                {{ $d }}
                            </th>
                        @endfor
                    </tr>
                </thead>
                <tbody>
                    @forelse($plans as $index => $plan)
                        @php
                            // --- RUMUS BARU: DIBAGI TOTAL HARI KERJA REAL ---
                            $dailyPlanAvg = ($plan->qty_plan > 0 && $totalWorkingDays > 0) 
                                            ? round($plan->qty_plan / $totalWorkingDays) 
                                            : 0; 
                            
                            $totalActualMonth = $plan->productionActuals->sum('qty_good');
                            $balance = $plan->qty_plan - $totalActualMonth;
                            $balanceColor = $balance > 0 ? 'text-danger' : 'text-success';
                        @endphp

                        <tr class="row-plan">
                            <td class="sticky-col sticky-col-1 text-center fw-bold" rowspan="3" style="background:#fff;">{{ $loop->iteration }}</td>
                            <td class="sticky-col sticky-col-2 fw-bold" rowspan="3" style="background:#fff;">{{ $plan->product->code_part ?? '-' }}</td>
                            <td class="sticky-col sticky-col-3 fw-bold" rowspan="3" style="background:#fff;">{{ $plan->product->part_number }}</td>
                            <td class="sticky-col sticky-col-4" rowspan="3" style="background:#fff;">
                                <div class="text-truncate" style="max-width: 190px;" title="{{ $plan->product->part_name }}">{{ $plan->product->part_name }}</div>
                            </td>
                            <td class="sticky-col sticky-col-5 text-center fw-bold text-primary" rowspan="3" style="background:#fff;">{{ number_format($plan->qty_plan) }}</td>
                            <td class="sticky-col sticky-col-6 text-center fw-bold {{ $balanceColor }} bg-light" rowspan="3">{{ number_format($balance) }}</td>
                            
                            <td class="sticky-col sticky-col-7 text-center small fw-bold">PLANN</td>

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
                            <td class="sticky-col sticky-col-7 text-center small fw-bold text-success">ACT</td>
                            @for($d=1; $d<=$daysInMonth; $d++)
                                @php 
                                    $dateObj = \Carbon\Carbon::create($year, $month, $d);
                                    $isWk = $dateObj->isWeekend();
                                    $isHol = isset($holidays) && array_key_exists($d, $holidays);
                                    $val = $matrixActuals[$plan->id][$d] ?? '';
                                    $bg = $isHol ? 'is-holiday' : ($isWk ? 'is-weekend' : '');
                                    
                                    // Boleh edit di hari libur HANYA jika ada datanya (koreksi)
                                    $readonly = (($isWk || $isHol) && empty($val)) ? 'readonly' : '';
                                @endphp
                                <td class="p-0 {{ $bg }}">
                                    <input type="number" name="actuals[{{ $plan->id }}][{{ $d }}]" value="{{ $val }}" 
                                           class="input-act" {{ $readonly }} onchange="calculateDiff(this, {{ $dailyPlanAvg }})">
                                </td>
                            @endfor
                        </tr>

                        <tr class="row-diff">
                            <td class="sticky-col sticky-col-7 text-center small fw-bold text-muted">±</td>
                            @for($d=1; $d<=$daysInMonth; $d++)
                                @php 
                                    $dateObj = \Carbon\Carbon::create($year, $month, $d);
                                    $isWk = $dateObj->isWeekend();
                                    $isHol = isset($holidays) && array_key_exists($d, $holidays);
                                    $act = $matrixActuals[$plan->id][$d] ?? 0;
                                    
                                    // Plan dianggap 0 jika libur/weekend
                                    $pln = (!$isWk && !$isHol) ? $dailyPlanAvg : 0;
                                    $diff = ($act > 0 || (!$isWk && !$isHol)) ? ($act - $pln) : '';
                                    
                                    $textColor = '';
                                    if(is_numeric($diff)) $textColor = $diff < 0 ? 'text-danger' : 'text-success';
                                    $bg = $isHol ? 'is-holiday' : ($isWk ? 'is-weekend' : '');
                                @endphp
                                <td class="text-center {{ $bg }} {{ $textColor }}">{{ $diff }}</td>
                            @endfor
                        </tr>
                        <tr><td colspan="45" style="padding:0; border-top: 2px solid #6c757d;"></td></tr>
                    @empty
                        <tr><td colspan="45" class="text-center py-5">Belum ada Plan untuk Line & Periode ini.</td></tr>
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