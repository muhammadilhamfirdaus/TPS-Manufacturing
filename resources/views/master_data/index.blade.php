@extends('layouts.app_simple')

@section('content')
<div class="row justify-content-center">
    <div class="col-12">
        
        {{-- Header & Actions --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-1">Master Part Data</h4>
                <p class="text-muted small mb-0">Kelola database part number dan proses produksi</p>
            </div>
            <div class="d-flex gap-2">
                {{-- Tombol Excel Dropdown --}}
                <div class="dropdown">
                    <button class="btn btn-white border shadow-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-file-excel text-success me-1"></i> Excel Tools
                    </button>
                    <ul class="dropdown-menu shadow-sm border-0">
                        <li>
                            <a class="dropdown-item" href="{{ route('master.template') }}">
                                <i class="fas fa-download me-2 text-muted"></i> Download Template
                            </a>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#importModal">
                                <i class="fas fa-file-import me-2 text-muted"></i> Import Data
                            </button>
                        </li>
                    </ul>
                </div>

                {{-- Tombol Tambah --}}
                <a href="{{ route('master.create') }}" class="btn btn-primary px-3">
                    <i class="fas fa-plus me-1"></i> Tambah Part
                </a>
            </div>
        </div>

        {{-- Main Table Card --}}
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4 py-3 text-secondary small text-uppercase fw-bold">Code Part</th>
                                <th class="py-3 text-secondary small text-uppercase fw-bold">Part Number</th>
                                <th class="py-3 text-secondary small text-uppercase fw-bold">Part Name / Customer</th>
                                <th class="py-3 text-secondary small text-uppercase fw-bold">Flow Process</th>
                                <th class="py-3 text-secondary small text-uppercase fw-bold text-center">Routing</th>
                                <th class="py-3 text-secondary small text-uppercase fw-bold text-center">C/T</th>
                                <th class="pe-4 py-3 text-secondary small text-uppercase fw-bold text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($products as $product)
                            <tr>
                                {{-- Code Part --}}
                                <td class="ps-4">
                                    <span class="badge bg-light text-primary border border-primary border-opacity-25 fw-bold">
                                        {{ $product->code_part ?? '-' }}
                                    </span>
                                </td>
                                
                                {{-- Part Number --}}
                                <td class="fw-bold text-dark">{{ $product->part_number }}</td>
                                
                                {{-- Part Name & Customer --}}
                                <td>
                                    <div class="fw-bold text-dark">{{ $product->part_name }}</div>
                                    <div class="text-muted small">
                                        <i class="far fa-building me-1"></i> {{ $product->customer }}
                                    </div>
                                </td>

                                {{-- Flow Process --}}
                                <td class="text-secondary small">{{ $product->flow_process ?? '-' }}</td>
                                
                                {{-- Routing Count --}}
                                <td class="text-center">
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-3">
                                        {{ $product->routings_count }} Proses
                                    </span>
                                </td>
                                
                                {{-- Cycle Time --}}
                                <td class="text-center fw-bold text-dark">{{ $product->cycle_time }}s</td>
                                
                                {{-- Aksi --}}
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end gap-1">
                                        <a href="{{ route('master.edit', $product->id) }}" class="btn btn-sm btn-light text-primary border-0" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('master.destroy', $product->id) }}" method="POST" onsubmit="return confirm('Yakin hapus part ini?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-light text-danger border-0" title="Hapus">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="text-muted opacity-50">
                                        <i class="fas fa-box-open fa-3x mb-3"></i>
                                        <p class="mb-0">Belum ada data part.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                {{-- Pagination --}}
                <div class="d-flex justify-content-end p-3 border-top">
                    {{ $products->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal Import --}}
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-bottom-0">
                <h5 class="modal-title fw-bold">Import Master Data</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('master.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <div class="text-center mb-4">
                        <div class="bg-success bg-opacity-10 text-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                            <i class="fas fa-file-excel fa-2x"></i>
                        </div>
                        <p class="text-muted small">Silakan upload file Excel sesuai template yang telah disediakan.</p>
                    </div>
                    
                    <div class="mb-3">
                        <input type="file" name="file" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer border-top-0 bg-light">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4">Upload Data</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection