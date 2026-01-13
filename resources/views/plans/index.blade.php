@extends('layouts.app_simple')

@section('content')
<div class="row justify-content-center">
    <div class="col-12">
        
        {{-- Header & Toolbar --}}
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
            <div>
                <h4 class="fw-bold text-dark mb-1">Production Planning</h4>
                <p class="text-muted small mb-0">Monitoring Loading Mesin, Manpower & Kebutuhan Kanban</p>
            </div>

            <div class="d-flex gap-2 align-items-center">
                {{-- Form Import (Hidden Input Trick) --}}
                <form action="{{ route('plans.import') }}" method="POST" enctype="multipart/form-data" class="d-flex">
                    @csrf
                    <div class="input-group">
                        <input type="file" name="file" class="form-control form-control-sm border-end-0" required accept=".xlsx, .xls" style="max-width: 200px;">
                        <button type="submit" class="btn btn-sm btn-white border border-start-0 text-success" title="Upload Excel">
                            <i class="fas fa-file-import"></i>
                        </button>
                    </div>
                </form>

                <div class="vr h-50 my-auto text-secondary opacity-25"></div>

                {{-- Tombol Template --}}
                <a href="{{ route('plans.export') }}" class="btn btn-white border shadow-sm btn-sm text-secondary" title="Download Template">
                    <i class="fas fa-download me-1"></i> Template
                </a>

                {{-- Tombol Manual Input --}}
                <a href="{{ route('plans.create') }}" class="btn btn-primary btn-sm px-3 shadow-sm">
                    <i class="fas fa-plus me-1"></i> Input Plan
                </a>
            </div>
        </div>

        {{-- Alert Messages --}}
        @if(session('success'))
            <div class="alert alert-success border-0 bg-success bg-opacity-10 text-success d-flex align-items-center shadow-sm mb-4">
                <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger d-flex align-items-center shadow-sm mb-4">
                <i class="fas fa-exclamation-triangle me-2"></i> 
                <div><strong>Gagal Import:</strong> {{ $errors->first() }}</div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Main Table Card --}}
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4 py-3 text-secondary small text-uppercase fw-bold">Tanggal</th>
                                <th class="py-3 text-secondary small text-uppercase fw-bold">Line & Produk</th>
                                <th class="py-3 text-secondary small text-uppercase fw-bold text-center">Target (Qty)</th>
                                <th class="py-3 text-secondary small text-uppercase fw-bold" style="width: 25%;">Machine Loading</th>
                                <th class="py-3 text-secondary small text-uppercase fw-bold">Manpower</th>
                                <th class="py-3 text-secondary small text-uppercase fw-bold text-center">Kanban</th>
                                <th class="py-3 text-secondary small text-uppercase fw-bold text-center">Status</th>
                                <th class="pe-4 py-3 text-secondary small text-uppercase fw-bold text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($plans as $plan)
                                @foreach($plan->details as $detail)
                                <tr>
                                    {{-- Tanggal --}}
                                    <td class="ps-4">
                                        <div class="d-flex flex-column">
                                            <span class="fw-bold text-dark">{{ \Carbon\Carbon::parse($plan->plan_date)->format('d M Y') }}</span>
                                            <small class="text-muted">{{ \Carbon\Carbon::parse($plan->plan_date)->format('l') }}</small>
                                        </div>
                                    </td>
                                    
                                    {{-- Line & Produk --}}
                                    <td>
                                        <div class="mb-1">
                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">
                                                {{ $plan->productionLine->name }}
                                            </span>
                                        </div>
                                        @if($detail->product)
                                            <div class="fw-bold text-dark small">{{ $detail->product->part_name }}</div>
                                            <div class="text-muted small" style="font-size: 0.75rem;">{{ $detail->product->part_number }}</div>
                                        @else
                                            <div class="text-danger small fst-italic"><i class="fas fa-exclamation-circle"></i> Part Terhapus</div>
                                        @endif
                                    </td>

                                    {{-- Target Qty --}}
                                    <td class="text-center">
                                        <span class="fw-bold fs-6 text-dark">{{ number_format($detail->qty_plan) }}</span>
                                        <div class="text-muted small" style="font-size: 0.7rem;">PCS</div>
                                    </td>
                                    
                                    {{-- Machine Loading Bar --}}
                                    <td>
                                        @php $load = $detail->calculated_loading_pct; @endphp
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <small class="fw-bold {{ $load > 100 ? 'text-danger' : 'text-success' }}">
                                                {{ $load }}%
                                            </small>
                                            @if($load > 100)
                                                <small class="badge bg-danger text-white py-0 px-1" style="font-size: 0.6rem;">OVER</small>
                                            @endif
                                        </div>
                                        <div class="progress rounded-pill bg-light" style="height: 6px;">
                                            <div class="progress-bar rounded-pill {{ $load > 100 ? 'bg-danger' : 'bg-success' }}" 
                                                 role="progressbar" 
                                                 style="width: {{ min($load, 100) }}%">
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Man Power --}}
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="fw-bold text-dark fs-6 me-1">{{ $detail->calculated_manpower }}</div>
                                            <small class="text-muted">Org</small>
                                        </div>
                                        
                                        {{-- Indikator Kurang Orang --}}
                                        @if($detail->calculated_manpower > $plan->productionLine->std_manpower)
                                            <div class="d-flex align-items-center text-danger small mt-1" style="font-size: 0.7rem;">
                                                <i class="fas fa-user-plus me-1"></i>
                                                <span>Need +{{ $detail->calculated_manpower - $plan->productionLine->std_manpower }}</span>
                                            </div>
                                        @else
                                            <div class="text-success small mt-1" style="font-size: 0.7rem;">
                                                <i class="fas fa-check-circle me-1"></i> OK
                                            </div>
                                        @endif
                                    </td>

                                    {{-- Kanban Cards --}}
                                    <td class="text-center">
                                        <div class="bg-light rounded-3 py-1 px-2 border d-inline-block">
                                            <div class="fw-bold text-primary">{{ $detail->calculated_kanban_cards ?? 0 }}</div>
                                            <div class="text-muted" style="font-size: 0.65rem;">Cards</div>
                                        </div>
                                    </td>

                                    {{-- Status --}}
                                    <td class="text-center">
                                        @if($plan->status == 'DRAFT')
                                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-3">DRAFT</span>
                                        @else
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3">FIXED</span>
                                        @endif
                                    </td>

                                    {{-- Aksi --}}
                                    <td class="text-end pe-4">
                                        <form action="{{ route('plans.destroy', $plan->id) }}" method="POST" onsubmit="return confirm('Hapus plan tanggal {{ $plan->plan_date }}?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light text-danger border-0 rounded-circle p-2" title="Hapus" style="width: 32px; height: 32px;">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <div class="text-muted opacity-50">
                                            <i class="fas fa-clipboard-list fa-3x mb-3"></i>
                                            <p class="mb-0">Belum ada jadwal produksi.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                {{-- Pagination --}}
                <div class="d-flex justify-content-end p-3 border-top">
                    {{ $plans->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection