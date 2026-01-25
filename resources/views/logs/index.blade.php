@extends('layouts.app_simple')

@section('content')
<div class="row justify-content-center">
    <div class="col-12">
        
        {{-- Header & Filters --}}
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
            <div>
                <h4 class="fw-bold text-dark mb-1">
                    <i class="fas fa-history text-primary me-2"></i>Activity Logs
                </h4>
                <p class="text-muted small mb-0">
                    Memantau riwayat aktivitas dan tindakan pengguna dalam sistem.
                </p>
            </div>

            {{-- Form Filter --}}
            <form action="{{ route('logs.index') }}" method="GET" class="d-flex gap-2 align-items-center flex-wrap">
                
                {{-- Filter Search --}}
                <div class="input-group input-group-sm shadow-sm" style="max-width: 250px;">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 ps-0" 
                           placeholder="Cari user atau deskripsi..." value="{{ request('search') }}">
                </div>

                {{-- Filter Action --}}
                <select name="action" class="form-select form-select-sm shadow-sm border fw-bold text-secondary" 
                        style="width: 150px;" onchange="this.form.submit()">
                    <option value="">- Semua Aksi -</option>
                    <option value="CREATE" {{ request('action') == 'CREATE' ? 'selected' : '' }}>CREATE</option>
                    <option value="UPDATE" {{ request('action') == 'UPDATE' ? 'selected' : '' }}>UPDATE</option>
                    <option value="DELETE" {{ request('action') == 'DELETE' ? 'selected' : '' }}>DELETE</option>
                    <option value="IMPORT" {{ request('action') == 'IMPORT' ? 'selected' : '' }}>IMPORT</option>
                    <option value="LOGIN" {{ request('action') == 'LOGIN' ? 'selected' : '' }}>LOGIN</option>
                </select>

                {{-- Tombol Reset --}}
                @if(request('search') || request('action'))
                    <a href="{{ route('logs.index') }}" class="btn btn-sm btn-light border text-danger shadow-sm" title="Reset Filter">
                        <i class="fas fa-times"></i>
                    </a>
                @endif

                <button type="submit" class="btn btn-sm btn-primary shadow-sm fw-bold">
                    <i class="fas fa-filter me-1"></i> Filter
                </button>
            </form>
        </div>

        {{-- Card Utama --}}
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                        <thead class="bg-light border-bottom">
                            <tr>
                                <th class="ps-4 py-3 text-secondary text-uppercase small fw-bold" width="5%">No</th>
                                <th class="py-3 text-secondary text-uppercase small fw-bold" width="20%">User</th>
                                <th class="py-3 text-secondary text-uppercase small fw-bold text-center" width="15%">Tipe Aksi</th>
                                <th class="py-3 text-secondary text-uppercase small fw-bold" width="40%">Deskripsi Aktivitas</th>
                                <th class="py-3 text-secondary text-uppercase small fw-bold text-end pe-4" width="20%">Waktu</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $index => $log)
                                <tr>
                                    {{-- Nomor --}}
                                    <td class="ps-4 text-muted">{{ $logs->firstItem() + $index }}</td>

                                    {{-- User --}}
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle bg-primary bg-opacity-10 text-primary fw-bold rounded-circle d-flex align-items-center justify-content-center me-2" 
                                                 style="width: 32px; height: 32px; font-size: 0.8rem;">
                                                {{ substr($log->user_name, 0, 1) }}
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark">{{ $log->user_name }}</div>
                                                <div class="small text-muted" style="font-size: 0.7rem;">ID: {{ $log->user_id ?? '-' }}</div>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Badge Aksi --}}
                                    <td class="text-center">
                                        @php
                                            $act = strtoupper($log->action);
                                            $badgeClass = 'bg-secondary text-secondary';
                                            $icon = 'fa-circle';

                                            if (str_contains($act, 'CREATE')) {
                                                $badgeClass = 'bg-success text-success';
                                                $icon = 'fa-plus-circle';
                                            } elseif (str_contains($act, 'UPDATE')) {
                                                $badgeClass = 'bg-warning text-warning'; // Orange
                                                $icon = 'fa-edit';
                                            } elseif (str_contains($act, 'DELETE')) {
                                                $badgeClass = 'bg-danger text-danger';
                                                $icon = 'fa-trash-alt';
                                            } elseif (str_contains($act, 'IMPORT')) {
                                                $badgeClass = 'bg-info text-info';
                                                $icon = 'fa-file-import';
                                            } elseif (str_contains($act, 'LOGIN') || str_contains($act, 'LOGOUT')) {
                                                $badgeClass = 'bg-primary text-primary';
                                                $icon = 'fa-sign-in-alt';
                                            }
                                        @endphp
                                        <span class="badge {{ $badgeClass }} bg-opacity-10 border border-opacity-25 rounded-pill px-3 py-2">
                                            <i class="fas {{ $icon }} me-1"></i> {{ $act }}
                                        </span>
                                    </td>

                                    {{-- Deskripsi --}}
                                    <td>
                                        <span class="text-dark">{{ $log->description }}</span>
                                        @if(str_contains($act, 'DELETE'))
                                            <i class="fas fa-exclamation-triangle text-warning ms-1" title="Penghapusan Data"></i>
                                        @endif
                                    </td>

                                    {{-- Waktu --}}
                                    <td class="text-end pe-4">
                                        <div class="d-flex flex-column">
                                            <span class="fw-bold text-dark" style="font-size: 0.8rem;">
                                                {{ $log->created_at->format('d M Y, H:i') }}
                                            </span>
                                            <span class="text-muted fst-italic" style="font-size: 0.7rem;">
                                                {{ $log->created_at->diffForHumans() }}
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5">
                                        <div class="d-flex flex-column align-items-center justify-content-center opacity-50">
                                            <i class="fas fa-search fa-3x text-secondary mb-3"></i>
                                            <h6 class="text-secondary fw-bold">Tidak ada aktivitas ditemukan.</h6>
                                            <p class="text-muted small mb-0">Coba ubah filter pencarian Anda.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Footer Pagination --}}
            <div class="card-footer bg-white py-3 border-top-0">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted small">
                        Menampilkan {{ $logs->firstItem() ?? 0 }} - {{ $logs->lastItem() ?? 0 }} dari {{ $logs->total() }} data
                    </div>
                    <div>
                        {{ $logs->withQueryString()->links() }}
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection