@extends('layouts.app_simple')

@section('content')
<div class="row justify-content-center">
    <div class="col-12">
        
        {{-- Header Page --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-1">Master Line & Mesin</h4>
                <p class="text-muted small mb-0">Kelola line produksi dan daftar mesin yang tersedia</p>
            </div>
            <a href="{{ route('master-line.create') }}" class="btn btn-primary px-3">
                <i class="fas fa-plus me-1"></i> Tambah Line
            </a>
        </div>

        {{-- Main Card --}}
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4 py-3 text-secondary small text-uppercase fw-bold" width="15%">Plant</th> {{-- Kolom Baru --}}
                                <th class="py-3 text-secondary small text-uppercase fw-bold" width="25%">Nama Line</th>
                                <th class="py-3 text-secondary small text-uppercase fw-bold text-center" width="15%">Jumlah Mesin</th>
                                <th class="py-3 text-secondary small text-uppercase fw-bold" width="30%">Preview Mesin</th>
                                <th class="pe-4 py-3 text-secondary small text-uppercase fw-bold text-end" width="15%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($lines as $line)
                            <tr>
                                {{-- Plant (BARU) --}}
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

                                {{-- Preview Mesin --}}
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        @forelse($line->machines->take(4) as $m)
                                            <span class="badge bg-light text-secondary border fw-normal">
                                                {{ $m->name }}
                                            </span>
                                        @empty
                                            <span class="text-muted small fst-italic">- Belum ada mesin -</span>
                                        @endforelse
                                        
                                        @if($line->machines_count > 4)
                                            <span class="badge bg-light text-muted border fw-normal">
                                                +{{ $line->machines_count - 4 }} lainnya
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Aksi --}}
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end gap-1">
                                        <a href="{{ route('master-line.edit', $line->id) }}" class="btn btn-sm btn-light text-primary border-0" title="Edit Line">
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