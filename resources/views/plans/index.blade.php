@extends('layouts.app_simple')

@section('content')
<div class="row justify-content-center">
    <div class="col-12">
        
        {{-- Header & Statistik Ringkas --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-1">Production Planning</h4>
                <p class="text-muted small mb-0">Monitoring jadwal, beban mesin (Loading), dan kebutuhan resources.</p>
            </div>
            <div class="d-flex gap-2">
                {{-- Dropdown Template --}}
                <div class="btn-group shadow-sm">
                    <button type="button" class="btn btn-light border text-success fw-bold dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-file-excel me-1"></i> Download Template
                    </button>
                    <ul class="dropdown-menu border-0 shadow">
                        <li>
                            <a class="dropdown-item small py-2" href="{{ route('plans.export', ['type' => 'empty']) }}">
                                <div class="d-flex align-items-center">
                                    <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 30px; height: 30px;">
                                        <i class="fas fa-plus text-secondary"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark">Add Data (Blank)</div>
                                        <div class="text-muted fst-italic small">Template kosong.</div>
                                    </div>
                                </div>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item small py-2" href="{{ route('plans.export', ['type' => 'set_data']) }}">
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 30px; height: 30px;">
                                        <i class="fas fa-list-ul text-primary"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark">Set Data (Pre-filled)</div>
                                        <div class="text-muted fst-italic small">Isi data master.</div>
                                    </div>
                                </div>
                            </a>
                        </li>
                    </ul>
                </div>
                
                {{-- Button Upload --}}
                <button type="button" class="btn btn-light border text-primary fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#importModal">
                    <i class="fas fa-upload me-1"></i> Upload Plan
                </button>

                {{-- Button Input --}}
                <a href="{{ route('plans.create') }}" class="btn btn-primary fw-bold shadow-sm px-4">
                    <i class="fas fa-plus me-1"></i> Input Plan Baru
                </a>
            </div>
        </div>

        

        {{-- Flash Message --}}
        @if(session('success'))
            <div class="alert alert-success border-0 bg-success bg-opacity-10 text-success mb-4 rounded-3 shadow-sm">
                <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            </div>
        @endif

        {{-- Error Message --}}
        @if($errors->any())
            <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger mb-4 rounded-3 shadow-sm">
                <i class="fas fa-exclamation-triangle me-2"></i> {{ $errors->first() }}
            </div>
        @endif

        {{-- Main Table Card --}}
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4 py-3 text-secondary small text-uppercase fw-bold">Jadwal & Line</th>
                                <th class="py-3 text-secondary small text-uppercase fw-bold">Identitas Produk</th>
                                <th class="py-3 text-secondary small text-uppercase fw-bold text-center">Target</th>
                                <th class="py-3 text-secondary small text-uppercase fw-bold" width="25%">Analisa Beban (Loading)</th>
                                <th class="py-3 text-secondary small text-uppercase fw-bold">Resources</th>
                                <th class="pe-4 py-3 text-secondary small text-uppercase fw-bold text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($plans as $plan)
                                {{-- Ambil Detail Pertama --}}
                                @php 
                                    $detail = $plan->details->first(); 
                                    $product = $detail->product ?? null;
                                    $ct = $product->cycle_time ?? 0;
                                    $loadingHours = $ct > 0 ? ($detail->qty_plan * $ct) / 3600 : 0;
                                    $isOverload = $loadingHours > 8; 
                                @endphp

                                <tr class="border-bottom">
                                    <td class="ps-4">
                                        <div class="d-flex flex-column">
                                            <span class="fw-bold text-dark mb-1">
                                                <i class="far fa-calendar-alt me-1 text-primary"></i> 
                                                {{ date('d M Y', strtotime($plan->plan_date)) }}
                                            </span>
                                            <div class="d-flex gap-1">
                                                <span class="badge bg-light text-secondary border">
                                                    {{ date('l', strtotime($plan->plan_date)) }}
                                                </span>
                                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">
                                                    SHIFT {{ $plan->shift_id }}
                                                </span>
                                            </div>
                                            <small class="text-muted mt-2 fw-bold text-uppercase" style="font-size: 0.7rem;">
                                                <i class="fas fa-industry me-1"></i> {{ $plan->productionLine->name ?? '-' }}
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        @if($product)
                                            <div class="mb-1">
                                                <span class="badge bg-dark text-white rounded-1" style="font-size: 0.65rem;">
                                                    {{ $product->code_part ?? 'N/A' }}
                                                </span>
                                                <span class="fw-bold text-primary ms-1">{{ $product->part_number }}</span>
                                            </div>
                                            <div class="fw-bold text-dark" style="font-size: 0.9rem;">
                                                {{ Str::limit($product->part_name, 30) }}
                                            </div>
                                            <div class="text-muted small mt-1 d-flex align-items-center gap-2">
                                                <span title="Cycle Time"><i class="fas fa-stopwatch me-1"></i> C/T: <strong>{{ $product->cycle_time }}s</strong></span>
                                                <span class="vr"></span>
                                                <span title="Kapasitas per Jam"><i class="fas fa-cogs me-1"></i> Cap: <strong>{{ $product->cycle_time > 0 ? round(3600/$product->cycle_time) : 0 }} pcs/h</strong></span>
                                            </div>
                                        @else
                                            <span class="text-danger small fst-italic">Produk terhapus</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="fw-bold text-dark display-6" style="font-size: 1.2rem;">
                                            {{ number_format($detail->qty_plan) }}
                                        </div>
                                        <small class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">Pcs</small>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-between mb-1 small fw-bold">
                                            <span class="{{ $isOverload ? 'text-danger' : 'text-success' }}">
                                                {{ number_format($detail->calculated_loading_pct, 1) }}% Load
                                            </span>
                                            <span class="text-muted">
                                                {{ number_format($loadingHours, 1) }} Jam / 8.0 Jam
                                            </span>
                                        </div>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar {{ $isOverload ? 'bg-danger' : ($detail->calculated_loading_pct > 90 ? 'bg-warning' : 'bg-success') }}" 
                                                 role="progressbar" 
                                                 style="width: {{ min($detail->calculated_loading_pct, 100) }}%">
                                            </div>
                                        </div>
                                        @if($isOverload)
                                            <div class="mt-1 text-danger fw-bold small">
                                                <i class="fas fa-exclamation-triangle me-1"></i> OVERLOAD (+{{ number_format($loadingHours - 8, 1) }} Jam)
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <div class="d-flex align-items-center small">
                                                <div class="bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 24px; height: 24px;">
                                                    <i class="fas fa-users" style="font-size: 0.7rem;"></i>
                                                </div>
                                                <div>
                                                    <span class="fw-bold">{{ $detail->calculated_manpower }}</span> Org
                                                    <span class="text-muted" style="font-size: 0.65rem;">(Std)</span>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center small">
                                                <div class="bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 24px; height: 24px;">
                                                    <i class="fas fa-tags" style="font-size: 0.7rem;"></i>
                                                </div>
                                                <div>
                                                    <span class="fw-bold">{{ $detail->calculated_kanban_cards }}</span> Kartu
                                                    <span class="text-muted" style="font-size: 0.65rem;">(Req)</span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="pe-4 text-end">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-cog"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end border-0 shadow">
                                                <li>
                                                    <form action="{{ route('plans.destroy', $plan->id) }}" method="POST" onsubmit="return confirm('Hapus plan ini?')">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="dropdown-item small text-danger fw-bold">
                                                            <i class="fas fa-trash-alt me-2"></i> Hapus
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                        <div class="mt-2">
                                            @if($plan->status == 'AUTO-MRP')
                                                <span class="badge bg-purple-100 text-purple-700 border border-purple-200" style="background: #f3e8ff; color: #7e22ce;">AUTO MRP</span>
                                            @else
                                                <span class="badge bg-light text-secondary border">MANUAL</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <div class="text-muted opacity-50">
                                            <i class="far fa-calendar-times fa-3x mb-3"></i>
                                            <p class="mb-1 fw-bold">Belum ada Planning Produksi</p>
                                            <small>Silakan input manual atau upload excel.</small>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-end p-3 border-top">
                    {{ $plans->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal Import Excel --}}
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('plans.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Import Plan dari Excel</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Pilih File Excel</label>
                        <input type="file" name="file" class="form-control" required>
                        <small class="text-muted">Gunakan template yang sudah disediakan.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Upload & Generate</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection