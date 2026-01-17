@extends('layouts.app_simple')

@section('content')
<div class="row justify-content-center">
    <div class="col-12">
        
        {{-- Header Page --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-1">Master Line & Mesin</h4>
                <p class="text-muted small mb-0">Kelola line produksi, mesin internal, dan vendor subcont</p>
            </div>
            <div class="d-flex gap-2">
                {{-- Tombol Tambah Line --}}
                <a href="{{ route('master-line.create') }}" class="btn btn-primary px-3 shadow-sm">
                    <i class="fas fa-plus me-1"></i> Tambah Line
                </a>
            </div>
        </div>

        {{-- Main Card --}}
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4 py-3 text-secondary small text-uppercase fw-bold" width="15%">Plant</th>
                                <th class="py-3 text-secondary small text-uppercase fw-bold" width="25%">Nama Line</th>
                                <th class="py-3 text-secondary small text-uppercase fw-bold text-center" width="15%">Total Resource</th>
                                <th class="py-3 text-secondary small text-uppercase fw-bold" width="30%">Daftar Mesin / Vendor</th>
                                <th class="pe-4 py-3 text-secondary small text-uppercase fw-bold text-end" width="15%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($lines as $line)
                            <tr>
                                {{-- Plant --}}
                                <td class="ps-4">
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 fw-bold px-3">
                                        {{ $line->plant ?? 'PLANT -' }}
                                    </span>
                                </td>

                                {{-- Nama Line --}}
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 35px; height: 35px;">
                                            <i class="fas fa-industry"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark">{{ $line->name }}</div>
                                            <div class="text-muted small" style="font-size: 0.75rem;">Production Line</div>
                                        </div>
                                    </div>
                                </td>

                                {{-- Jumlah Mesin --}}
                                <td class="text-center">
                                    <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-3">
                                        {{ $line->machines_count }} Unit
                                    </span>
                                </td>

                                {{-- Preview Mesin (UPDATED FOR SUBCONT) --}}
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        {{-- Tampilkan 5 mesin pertama --}}
                                        @forelse($line->machines->take(5) as $m)
                                            @php
                                                // Cek Tipe Mesin (Internal vs Subcont)
                                                // Pastikan model Machine sudah memiliki kolom 'type'
                                                $isSubcont = ($m->type ?? 'INTERNAL') === 'SUBCONT';
                                                
                                                // Styling Badge: Kuning untuk Subcont, Abu-abu untuk Internal
                                                $badgeClass = $isSubcont 
                                                    ? 'bg-warning bg-opacity-25 text-dark border-warning border-opacity-50' 
                                                    : 'bg-light text-secondary border';
                                                
                                                // Ikon Truk untuk Subcont
                                                $icon = $isSubcont ? '<i class="fas fa-truck small me-1"></i>' : '';
                                            @endphp

                                            <span class="badge {{ $badgeClass }} fw-normal border" title="{{ $isSubcont ? 'Vendor Subcont' : 'Mesin Internal' }}">
                                                {!! $icon !!}{{ $m->name }}
                                            </span>
                                        @empty
                                            <span class="text-muted small fst-italic">- Belum ada mesin -</span>
                                        @endforelse
                                        
                                        {{-- Indikator Sisa --}}
                                        @if($line->machines_count > 5)
                                            <span class="badge bg-light text-muted border fw-normal">
                                                +{{ $line->machines_count - 5 }} lainnya
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Aksi --}}
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end gap-1">
                                        <a href="{{ route('master-line.edit', $line->id) }}" class="btn btn-sm btn-light text-primary border-0" title="Edit Line & Mesin">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('master-line.destroy', $line->id) }}" method="POST" onsubmit="return confirm('Yakin hapus Line ini? Semua mesin di dalamnya akan ikut terhapus!')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-light text-danger border-0" title="Hapus Line">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="text-muted opacity-50">
                                        <i class="fas fa-network-wired fa-3x mb-3"></i>
                                        <p class="mb-0">Belum ada data Line Produksi.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                {{-- Pagination --}}
                <div class="d-flex justify-content-end p-3 border-top">
                    {{ $lines->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection