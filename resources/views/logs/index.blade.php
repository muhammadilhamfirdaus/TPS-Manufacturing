@extends('layouts.app_simple')

@section('content')
<div class="row justify-content-center">
    <div class="col-12">
        
        {{-- Card Utama --}}
        <div class="card shadow-sm border-0 rounded-4">
            
            {{-- Card Header --}}
            <div class="card-header bg-white pt-4 pb-3 border-bottom-0 ps-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold mb-1 text-dark">
                            <i class="fas fa-history text-primary me-2"></i> Riwayat Aktivitas
                        </h5>
                        <p class="text-muted small mb-0">Memantau semua tindakan user di dalam sistem</p>
                    </div>
                    {{-- Opsional: Tombol Filter atau Export bisa ditaruh sini --}}
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4 text-uppercase text-secondary small fw-bold py-3" width="20%">Waktu</th>
                                <th class="text-uppercase text-secondary small fw-bold" width="20%">User</th>
                                <th class="text-uppercase text-secondary small fw-bold text-center" width="15%">Aksi</th>
                                <th class="text-uppercase text-secondary small fw-bold" width="45%">Deskripsi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                            <tr>
                                {{-- Kolom Waktu --}}
                                <td class="ps-4">
                                    <div class="d-flex flex-column">
                                        <span class="fw-bold text-dark" style="font-size: 0.9rem;">
                                            {{ $log->created_at->format('d M Y') }}
                                        </span>
                                        <span class="text-muted small">
                                            <i class="far fa-clock me-1"></i> {{ $log->created_at->format('H:i:s') }}
                                        </span>
                                    </div>
                                </td>

                                {{-- Kolom User dengan Avatar --}}
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-secondary bg-opacity-10 text-secondary rounded-circle d-flex align-items-center justify-content-center me-2" 
                                             style="width: 32px; height: 32px; font-size: 0.8rem; fw-bold">
                                            {{ substr($log->user_name, 0, 1) }}
                                        </div>
                                        <span class="fw-bold text-dark" style="font-size: 0.9rem;">{{ $log->user_name }}</span>
                                    </div>
                                </td>

                                {{-- Kolom Aksi (Soft Badge) --}}
                                <td class="text-center">
                                    @php
                                        // Tentukan warna dan icon berdasarkan aksi
                                        $action = strtoupper($log->action);
                                        $badgeClass = 'bg-secondary text-secondary';
                                        $icon = 'fa-info-circle';

                                        if (str_contains($action, 'CREATE')) {
                                            $badgeClass = 'bg-success bg-opacity-10 text-success border border-success border-opacity-25';
                                            $icon = 'fa-plus-circle';
                                        } elseif (str_contains($action, 'DELETE')) {
                                            $badgeClass = 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25';
                                            $icon = 'fa-trash-alt';
                                        } elseif (str_contains($action, 'IMPORT')) {
                                            $badgeClass = 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25';
                                            $icon = 'fa-file-import';
                                        } elseif (str_contains($action, 'UPDATE')) {
                                            $badgeClass = 'bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25';
                                            $icon = 'fa-edit';
                                        }
                                    @endphp

                                    <span class="badge rounded-pill {{ $badgeClass }} px-3 py-2 fw-bold" style="font-size: 0.75rem;">
                                        <i class="fas {{ $icon }} me-1"></i> {{ $log->action }}
                                    </span>
                                </td>

                                {{-- Kolom Deskripsi --}}
                                <td>
                                    <span class="text-secondary" style="font-size: 0.9rem;">
                                        {{ $log->description }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-5">
                                    <div class="text-muted opacity-50">
                                        <i class="fas fa-history fa-3x mb-3"></i>
                                        <p class="mb-0">Belum ada aktivitas yang tercatat.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            
            {{-- Pagination Footer --}}
            <div class="card-footer bg-white border-top-0 py-3">
                <div class="d-flex justify-content-end">
                    {{ $logs->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection