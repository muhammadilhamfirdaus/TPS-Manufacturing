@extends('layouts.app_simple')

@section('title', 'Machine Capacity Analysis')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12">

            {{-- HEADER --}}
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-end mb-4 gap-3">
                <div>
                    <h4 class="fw-bold text-dark mb-1">Machine Capacity Summary</h4>
                    <p class="text-muted small mb-0">
                        Analisis beban per-mesin. Periode:
                        <span class="fw-bold text-primary">
                            {{ date('F Y', mktime(0, 0, 0, request('month', date('m')), 1, request('year', date('Y')))) }}
                        </span>
                    </p>
                </div>

                <div class="d-flex flex-wrap gap-2 align-items-center">
                    {{-- SEARCH & FILTER --}}
                    <input type="text" id="capacitySearch"
                        class="form-control form-control-sm ps-3 bg-white border shadow-sm"
                        placeholder="Cari Mesin/Asset..." style="width: 200px; height: 38px; border-radius: 10px;">

                    <form action="{{ route('plans.sum_loading') }}" method="GET"
                        class="d-flex align-items-center gap-2 bg-white p-1 rounded-3 shadow-sm border"
                        style="height: 38px; border-radius: 10px !important;">
                        <select name="month" class="form-select form-select-sm border-0 fw-bold bg-light"
                            style="width: 110px;">
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                    {{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                            @endfor
                        </select>
                        <select name="year" class="form-select form-select-sm border-0 fw-bold bg-light"
                            style="width: 80px;">
                            @for($y = 2024; $y <= 2026; $y++)
                                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary border-0"><i
                                class="fas fa-sync-alt"></i></button>
                    </form>

                    <span class="badge bg-white text-dark border px-3 py-2 rounded-pill shadow-sm"
                        style="height: 38px; display: flex; align-items: center;">
                        <i class="fas fa-calendar-day me-2 text-warning"></i> {{ $workDays }} Hari Kerja
                    </span>
                </div>
            </div>

            {{-- TABEL DATA --}}
            <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="capacityTable" style="font-size: 0.7rem;">
                            <thead class="bg-light sticky-top shadow-sm" style="z-index: 10;">
                                <tr class="text-secondary text-uppercase fw-bold text-center border-bottom">
                                    {{-- KOLOM IDENTITAS --}}
                                    <th rowspan="2" class="py-3 border-end bg-white">Plant</th>
                                    <th rowspan="2" class="py-3 border-end bg-white">Line</th>

                                    {{-- NAMA MESIN & ASSET (NO MESIN DIHAPUS) --}}
                                    <th rowspan="2" class="py-3 border-end bg-white text-start ps-3">Nama Mesin</th>
                                    <th rowspan="2" class="py-3 border-end bg-white">Code Asset</th>

                                    <th rowspan="2" class="py-3 border-end bg-white">Group</th>
                                    <th rowspan="2" class="py-3 border-end bg-white text-end pe-3">Load (Jam)</th>

                                    {{-- SCENARIOS --}}
                                    <th colspan="2" class="py-2 border-end border-bottom text-primary bg-light">1 SHIFT (8H)
                                    </th>
                                    <th colspan="2" class="py-2 border-end border-bottom text-warning bg-light">2 SHIFT
                                        (16H)</th>
                                    <th colspan="2" class="py-2 border-bottom text-success bg-light">3 SHIFT (24H)</th>
                                </tr>
                                <tr class="text-secondary text-uppercase fw-bold text-center border-bottom"
                                    style="font-size: 0.65rem;">
                                    <th class="py-1 border-end bg-light">Cap</th>
                                    <th class="py-1 border-end text-primary bg-light">%</th>
                                    <th class="py-1 border-end bg-light">Cap</th>
                                    <th class="py-1 border-end text-warning bg-light">%</th>
                                    <th class="py-1 border-end bg-light">Cap</th>
                                    <th class="py-1 text-success bg-light">%</th>
                                </tr>
                            </thead>

                            <tbody id="capacityTableBody">
                                @forelse($reportData as $row)
                                    @php
                                        $load = $row->calculated_load ?? 0;
                                        $cap1 = $workDays * 8;
                                        $cap2 = $workDays * 16;
                                        $cap3 = $workDays * 24;

                                        $pct1 = $cap1 > 0 ? ($load / $cap1) * 100 : 0;
                                        $pct2 = $cap2 > 0 ? ($load / $cap2) * 100 : 0;
                                        $pct3 = $cap3 > 0 ? ($load / $cap3) * 100 : 0;
                                    @endphp

                                    <tr class="capacity-row border-bottom">
                                        {{-- INFO DASAR --}}
                                        <td class="text-center fw-bold text-muted">{{ $row->plant }}</td>
                                        <td class="text-center fw-bold">{{ $row->line_name }}</td>

                                        {{-- NAMA MESIN --}}
                                        <td class="fw-bold text-dark ps-3">{{ $row->machine_name }}</td>

                                        {{-- CODE ASSET (Dari Machine Code) --}}
                                        <td class="text-center text-primary font-monospace small">
                                            {{ $row->asset_code }}
                                        </td>

                                        <td class="text-center">{{ $row->machine_group }}</td>
                                        <td class="text-end pe-3 fw-bold bg-light">
                                            {{ $load > 0 ? number_format($load, 1) : '-' }}
                                        </td>

                                        {{-- PROGRESS BARS --}}
                                        {{-- 1 SHIFT --}}
                                        <td class="text-center text-muted border-start">{{ $cap1 }}</td>
                                        <td class="px-2">
                                            @php
                                                $color = $pct1 >= 90 ? ($pct1 > 100 ? 'bg-danger' : 'bg-warning') : 'bg-success';
                                                $width = $pct1 > 100 ? 100 : ($pct1 < 0 ? 0 : $pct1);
                                            @endphp
                                            <div class="d-flex align-items-center" style="font-size: 0.65rem;">
                                                <div class="progress flex-grow-1 me-1" style="height: 5px;">
                                                    <div class="progress-bar {{ $color }}" style="width: {{ $width }}%"></div>
                                                </div>
                                                <span class="fw-bold text-dark"
                                                    style="min-width: 25px; text-align: right;">{{ number_format($pct1, 0) }}%</span>
                                            </div>
                                        </td>

                                        {{-- 2 SHIFT --}}
                                        <td class="text-center text-muted border-start">{{ $cap2 }}</td>
                                        <td class="px-2">
                                            @php
                                                $color = $pct2 >= 90 ? ($pct2 > 100 ? 'bg-danger' : 'bg-warning') : 'bg-success';
                                                $width = $pct2 > 100 ? 100 : ($pct2 < 0 ? 0 : $pct2);
                                            @endphp
                                            <div class="d-flex align-items-center" style="font-size: 0.65rem;">
                                                <div class="progress flex-grow-1 me-1" style="height: 5px;">
                                                    <div class="progress-bar {{ $color }}" style="width: {{ $width }}%"></div>
                                                </div>
                                                <span class="fw-bold text-dark"
                                                    style="min-width: 25px; text-align: right;">{{ number_format($pct2, 0) }}%</span>
                                            </div>
                                        </td>

                                        {{-- 3 SHIFT --}}
                                        <td class="text-center text-muted border-start">{{ $cap3 }}</td>
                                        <td class="px-2">
                                            @php
                                                $color = $pct3 >= 90 ? ($pct3 > 100 ? 'bg-danger' : 'bg-warning') : 'bg-success';
                                                $width = $pct3 > 100 ? 100 : ($pct3 < 0 ? 0 : $pct3);
                                            @endphp
                                            <div class="d-flex align-items-center" style="font-size: 0.65rem;">
                                                <div class="progress flex-grow-1 me-1" style="height: 5px;">
                                                    <div class="progress-bar {{ $color }}" style="width: {{ $width }}%"></div>
                                                </div>
                                                <span class="fw-bold text-dark"
                                                    style="min-width: 25px; text-align: right;">{{ number_format($pct3, 0) }}%</span>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="12" class="text-center py-5 text-muted">Tidak ada data mesin.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Script Pencarian Sederhana --}}
    <script>
        document.getElementById('capacitySearch').addEventListener('keyup', function () {
            let val = this.value.toLowerCase();
            document.querySelectorAll('.capacity-row').forEach(row => {
                row.style.display = row.innerText.toLowerCase().includes(val) ? '' : 'none';
            });
        });
    </script>
@endsection