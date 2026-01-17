@extends('layouts.app_simple')

@section('content')
<div class="container-fluid">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">Monitoring Pencapaian Produksi</h4>
            <p class="text-muted small mb-0">
                Periode: <span class="fw-bold text-primary">{{ date('F Y', mktime(0, 0, 0, $month, 1, $year)) }}</span>
            </span>
        </div>
        
        {{-- Filter Bulan --}}
        <form action="{{ route('production.monitoring') }}" method="GET" class="d-flex gap-2 bg-white p-1 rounded shadow-sm border">
            <select name="month" class="form-select border-0 fw-bold bg-light" style="width: 120px;">
                @for($m=1; $m<=12; $m++)
                    <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ date('F', mktime(0,0,0,$m,1)) }}</option>
                @endfor
            </select>
            <input type="number" name="year" class="form-control border-0 fw-bold bg-light" style="width: 80px;" value="{{ $year }}">
            <button type="submit" class="btn btn-primary px-3"><i class="fas fa-filter"></i></button>
        </form>
    </div>

    <div class="row">
        @forelse($plans as $plan)
            @php
                $pct = $plan->achievement_pct;
                // Warna Progress Bar
                if($pct >= 100) $color = 'bg-success';
                elseif($pct >= 80) $color = 'bg-info';
                elseif($pct >= 50) $color = 'bg-warning';
                else $color = 'bg-danger';
            @endphp

            <div class="col-md-4 mb-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="badge bg-light text-secondary border">{{ $plan->productionPlan->productionLine->name ?? '-' }}</span>
                            <span class="fw-bold {{ $pct >= 100 ? 'text-success' : 'text-muted' }}">
                                {{ number_format($pct, 1) }}%
                            </span>
                        </div>
                        
                        <h6 class="fw-bold text-dark mb-1">{{ $plan->product->part_name }}</h6>
                        <div class="text-muted small mb-3">{{ $plan->product->part_number }}</div>

                        {{-- Progress Bar --}}
                        <div class="progress mb-2" style="height: 10px;">
                            <div class="progress-bar {{ $color }} progress-bar-striped progress-bar-animated" 
                                 role="progressbar" 
                                 style="width: {{ min($pct, 100) }}%">
                            </div>
                        </div>

                        <div class="d-flex justify-content-between small fw-bold">
                            <span class="text-success">
                                <i class="fas fa-check-circle me-1"></i> Actual: {{ number_format($plan->total_actual) }}
                            </span>
                            <span class="text-primary">
                                <i class="fas fa-bullseye me-1"></i> Plan: {{ number_format($plan->qty_plan) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 text-center py-5">
                <div class="opacity-50 mb-3"><i class="fas fa-chart-pie fa-3x"></i></div>
                <h5 class="text-muted">Belum ada data Plan bulan ini.</h5>
            </div>
        @endforelse
    </div>
</div>
@endsection