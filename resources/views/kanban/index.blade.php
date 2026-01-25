@extends('layouts.app_simple')

@section('content')
<div class="card shadow-sm border-0 rounded-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="fw-bold text-dark mb-1">KALKULASI KANBAN PRODUKSI</h5>
            <p class="text-muted small mb-0">Periode: {{ date('F Y', mktime(0,0,0,$month, 1, $year)) }} | Hari Kerja: {{ $workDays }} Hari</p>
        </div>
        
        {{-- Filter Form (Bisa dicopy dari module lain) --}}
        <form action="{{ route('kanban.index') }}" method="GET" class="d-flex gap-2">
            <select name="month" class="form-select form-select-sm" onchange="this.form.submit()">
                @for($m=1; $m<=12; $m++) <option value="{{ $m }}" {{ $month==$m ? 'selected':'' }}>{{ date('F', mktime(0,0,0,$m,1)) }}</option> @endfor
            </select>
            <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="2025" {{ $year==2025 ? 'selected':'' }}>2025</option>
                <option value="2026" {{ $year==2026 ? 'selected':'' }}>2026</option>
            </select>
        </form>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-sm align-middle text-center" style="font-size: 0.75rem;">
                <thead class="bg-light text-secondary fw-bold">
                    <tr>
                        <th rowspan="2">NO</th>
                        <th rowspan="2">KODE PART</th>
                        <th rowspan="2" class="text-start">NAMA PART</th>
                        <th rowspan="2">CUSTOMER</th>
                        <th rowspan="2" class="bg-primary bg-opacity-10 text-primary">TOTAL ORDER</th>
                        <th rowspan="2">DAILY PLAN</th>
                        <th rowspan="2">LOT SIZE<br>(QTY/BOX)</th>
                        <th rowspan="2">LEAD TIME<br>(HARI)</th>
                        <th rowspan="2">SAFETY<br>STOCK</th>
                        <th colspan="2" class="bg-warning bg-opacity-10 text-dark">HASIL KANBAN</th>
                    </tr>
                    <tr>
                        <th class="bg-warning bg-opacity-25">JML KARTU</th>
                        <th class="bg-warning bg-opacity-25">MAX STOCK</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($kanbanData as $row)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td class="fw-bold">{{ $row->code_part }}</td>
                        <td class="text-start">{{ $row->part_name }}<br><small class="text-muted">{{ $row->part_number }}</small></td>
                        <td>{{ $row->customer }}</td>
                        
                        {{-- Data Demand --}}
                        <td class="fw-bold bg-primary bg-opacity-10 text-primary">{{ number_format($row->total_order) }}</td>
                        <td>{{ number_format($row->daily_plan, 1) }}</td>
                        
                        {{-- Parameter Master --}}
                        <td>{{ number_format($row->qty_per_box) }}</td>
                        <td>{{ $row->lead_time }}</td>
                        <td>{{ number_format($row->safety_stock) }}</td>
                        
                        {{-- Hasil Kalkulasi --}}
                        <td class="fw-bold fs-6 bg-warning bg-opacity-25">{{ number_format($row->kanban_qty) }}</td>
                        <td class="bg-warning bg-opacity-10">{{ number_format($row->max_stock) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="py-5 text-muted">Belum ada Plan Produksi bulan ini.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection