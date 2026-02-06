@extends('layouts.app_simple')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12">

            {{-- Alert Messages --}}
            @if(session('success'))
                <div
                    class="alert alert-success border-0 bg-success bg-opacity-10 text-success d-flex align-items-center shadow-sm mb-4">
                    <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{-- Header & Actions --}}
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <div>
                    <h4 class="fw-bold text-dark mb-1">Master Part Data</h4>
                    <p class="text-muted small mb-0">Kelola database part number, BOM, dan proses produksi</p>
                </div>

                {{-- TOOLBOX: SEARCH & FILTER --}}
                <div class="d-flex flex-wrap gap-2">
                    {{-- Search Input --}}
                    <div class="input-group input-group-sm" style="width: 250px;">
                        <span class="input-group-text bg-white border-end-0 text-muted">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" id="masterSearchInput" class="form-control border-start-0 ps-0 shadow-none"
                            placeholder="Cari Code, Name, Number...">
                    </div>

                    {{-- TOMBOL DI HEADER --}}
                    <div class="d-flex gap-2">
                        <a href="{{ route('master.template') }}" class="btn btn-success text-white">
                            <i class="fas fa-download me-1"></i> Template
                        </a>
                        <button type="button" class="btn btn-info text-white" data-bs-toggle="modal"
                            data-bs-target="#importModal">
                            <i class="fas fa-file-upload me-1"></i> Import Excel
                        </button>
                        <a href="{{ route('master.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Buat Part Baru
                        </a>
                    </div>

                    {{-- MODAL IMPORT --}}
                    <div class="modal fade" id="importModal" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Import Data Part & Routing</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form action="{{ route('master.import') }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <div class="modal-body">
                                        <div class="alert alert-info small">
                                            <i class="fas fa-info-circle"></i> Gunakan template yang disediakan.
                                            Pastikan nama <strong>Mesin</strong> di Excel sama persis dengan di Master Line
                                            & Machine.
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Pilih File Excel (.xlsx / .csv)</label>
                                            <input type="file" name="file" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary"
                                            data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-primary">Upload & Proses</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                
                </div>
            </div>

            {{-- Main Table Card --}}
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="masterPartTable">
                            <thead class="bg-light">
                                <tr>
                                    {{-- HEADER DENGAN TOGGLE SORT --}}
                                    <th class="ps-4 py-3 text-secondary small text-uppercase fw-bold sortable"
                                        data-sort="code" style="cursor:pointer;">
                                        Code <i class="fas fa-sort ms-1 opacity-50"></i>
                                    </th>
                                    <th class="py-3 text-secondary small text-uppercase fw-bold sortable" data-sort="number"
                                        style="cursor:pointer;">
                                        Part Number <i class="fas fa-sort ms-1 opacity-50"></i>
                                    </th>
                                    <th class="py-3 text-secondary small text-uppercase fw-bold sortable" data-sort="name"
                                        style="cursor:pointer;">
                                        Part Name / Customer <i class="fas fa-sort ms-1 opacity-50"></i>
                                    </th>
                                    <th class="py-3 text-secondary small text-uppercase fw-bold">Flow Process</th>
                                    <th class="py-3 text-secondary small text-uppercase fw-bold text-center">Routing</th>
                                    <th class="py-3 text-secondary small text-uppercase fw-bold text-center">C/T</th>
                                    <th class="pe-4 py-3 text-secondary small text-uppercase fw-bold text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="masterTableBody">
                                @forelse($products as $product)
                                    <tr class="master-row">
                                        <td class="ps-4">
                                            <span class="fw-bold text-dark code-val">{{ $product->code_part ?? '-' }}</span>
                                        </td>
                                        <td class="fw-bold text-muted number-val">{{ $product->part_number }}</td>
                                        <td>
                                            <div class="fw-bold text-dark name-val">{{ $product->part_name }}</div>
                                            <div class="text-muted small"><i class="far fa-building me-1"></i>
                                                {{ $product->customer }}</div>
                                        </td>
                                        <td class="text-secondary small">{{ $product->flow_process ?? '-' }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-3">
                                                {{ $product->routings_count }} Proses
                                            </span>
                                        </td>
                                        <td class="text-center fw-bold text-dark">{{ $product->cycle_time }}s</td>
                                        <td class="text-end pe-4">
                                            <div class="d-flex justify-content-end gap-1">
                                                <a href="{{ route('bom.index', $product->id) }}"
                                                    class="btn btn-sm btn-light text-info border-0 shadow-sm" title="BOM"><i
                                                        class="fas fa-sitemap"></i></a>
                                                <a href="{{ route('master.edit', $product->id) }}"
                                                    class="btn btn-sm btn-light text-primary border-0 shadow-sm" title="Edit"><i
                                                        class="fas fa-edit"></i></a>
                                                <form action="{{ route('master.destroy', $product->id) }}" method="POST"
                                                    class="d-inline" onsubmit="return confirm('Hapus part ini?')">
                                                    @csrf @method('DELETE')
                                                    <button class="btn btn-sm btn-light text-danger border-0 shadow-sm"><i
                                                            class="fas fa-trash-alt"></i></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted">Belum ada data part.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div id="paginationContainer" class="d-flex justify-content-end p-3 border-top">
                        {{ $products->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL IMPORT (Sama Seperti Sebelumnya) --}}
    <div class="modal fade" id="importModal" tabindex="-1">...</div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('masterSearchInput');
            const tableBody = document.getElementById('masterTableBody');
            const rows = Array.from(tableBody.getElementsByClassName('master-row'));
            const sortHeaders = document.querySelectorAll('.sortable');

            // 1. REAL-TIME SEARCH
            searchInput.addEventListener('keyup', function () {
                const term = this.value.toLowerCase();
                rows.forEach(row => {
                    const text = row.innerText.toLowerCase();
                    row.style.display = text.includes(term) ? '' : 'none';
                });
            });

            // 2. TOGGLE SORT LOGIC
            let currentSort = { col: null, asc: true };

            function sortTable(type) {
                const isAsc = currentSort.col === type ? !currentSort.asc : true;
                currentSort = { col: type, asc: isAsc };

                const sortedRows = rows.sort((a, b) => {
                    let valA = a.querySelector(`.${type}-val`).textContent.trim().toLowerCase();
                    let valB = b.querySelector(`.${type}-val`).textContent.trim().toLowerCase();
                    return isAsc ? valA.localeCompare(valB) : valB.localeCompare(valA);
                });

                sortedRows.forEach(row => tableBody.appendChild(row));
                updateIcons(type, isAsc);
            }

            function updateIcons(type, isAsc) {
                sortHeaders.forEach(h => {
                    const icon = h.querySelector('i');
                    if (h.dataset.sort === type) {
                        icon.className = isAsc ? 'fas fa-sort-up ms-1 text-dark' : 'fas fa-sort-down ms-1 text-dark';
                        icon.style.opacity = "1";
                    } else {
                        icon.className = 'fas fa-sort ms-1 small opacity-50';
                    }
                });
            }

            sortHeaders.forEach(header => {
                header.addEventListener('click', () => sortTable(header.dataset.sort));
            });
        });
    </script>
@endsection