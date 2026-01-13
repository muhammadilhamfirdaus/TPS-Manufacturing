@extends('layouts.app_simple')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2>📊 Digital Kanban Board</h2>
        <p class="text-muted">Monitoring Level Stok Finish Good (FG)</p>
    </div>
    <button class="btn btn-outline-dark" onclick="window.location.reload()">
        <i class="fas fa-sync"></i> Refresh Data
    </button>
</div>

<div class="row">
    @foreach($kanbans as $kanban)
    <div class="col-md-4 mb-4">
        {{-- Logika Warna Border Card --}}
        <div class="card h-100 border-{{ $kanban->status_color }} shadow-sm">
            <div class="card-header bg-{{ $kanban->status_color }} text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ $kanban->kanban_type }} LOOP</h5>
                <span class="badge bg-white text-{{ $kanban->status_color }}">
                    {{ strtoupper($kanban->status_color == 'danger' ? 'KRITIS' : ($kanban->status_color == 'warning' ? 'RESTOCK' : 'AMAN')) }}
                </span>
            </div>
            
            <div class="card-body">
                <h4 class="card-title">{{ $kanban->product->part_name }}</h4>
                <p class="text-muted mb-3">{{ $kanban->product->part_number }}</p>

                <div class="row text-center mb-3">
                    <div class="col-6">
                        <small class="text-muted">Stok Aktual</small>
                        <h2 class="fw-bold text-{{ $kanban->status_color }}">
                            {{ number_format($kanban->current_stock) }}
                        </h2>
                        <small>{{ $kanban->product->uom }}</small>
                    </div>
                    <div class="col-6 border-start">
                        <small class="text-muted">Max Kapasitas</small>
                        <h3>{{ number_format($kanban->number_of_cards * $kanban->product->qty_per_box) }}</h3>
                        <small>{{ $kanban->number_of_cards }} Kartu</small>
                    </div>
                </div>

                {{-- Progress Bar Level Stok --}}
                @php
                    $max = $kanban->number_of_cards * $kanban->product->qty_per_box;
                    $pct = $max > 0 ? ($kanban->current_stock / $max) * 100 : 0;
                @endphp
                <div class="progress" style="height: 10px;">
                    <div class="progress-bar bg-{{ $kanban->status_color }}" 
                         role="progressbar" 
                         style="width: {{ $pct }}%"></div>
                </div>
            </div>

            <div class="card-footer bg-white text-muted">
                <div class="d-flex justify-content-between small">
                    <span><i class="fas fa-map-marker-alt"></i> {{ $kanban->location_code }}</span>
                    <span>Box Qty: {{ $kanban->product->qty_per_box }}</span>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endsection