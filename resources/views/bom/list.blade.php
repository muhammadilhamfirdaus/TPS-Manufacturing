@extends('layouts.app_simple')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12">

            {{-- HEADER & TOOLS --}}
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                <div>
                    <h4 class="fw-bold text-dark mb-1">Bill of Materials (BOM) Management</h4>
                    <p class="text-muted small mb-0">Kelola BOM untuk Barang Jadi & Setengah Jadi.</p>
                </div>

                {{-- SEARCH & FILTER TOOLS --}}
                <div class="d-flex flex-wrap gap-2">
                    <div class="input-group input-group-sm" style="width: 250px;">
                        <span class="input-group-text bg-white border-end-0 text-muted">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" id="bomSearchInput" class="form-control border-start-0 ps-0"
                            placeholder="Cari Code, Name, Number...">
                    </div>

                    <select id="categoryFilter" class="form-select form-select-sm" style="width: 150px;">
                        <option value="ALL">Semua Kategori</option>
                        <option value="FINISH GOOD">FINISH GOOD</option>
                        <option value="SEMI FINISH">SEMI FINISH</option>
                    </select>

                    {{-- Button A-Z utama tetap ada sebagai shortcut global --}}
                    <button id="sortAZ" class="btn btn-sm btn-outline-dark">
                        <i class="fas fa-sort-alpha-down me-1"></i> A-Z
                    </button>
                </div>

                {{-- TOMBOL ACTION DI ATAS TABEL --}}
                <div class="d-flex gap-2 mb-3">
                    <a href="{{ route('bom.template') }}" class="btn btn-success text-white">
                        <i class="fas fa-download me-1"></i> Template
                    </a>
                    <button type="button" class="btn btn-info text-white" data-bs-toggle="modal"
                        data-bs-target="#importBomModal">
                        <i class="fas fa-file-upload me-1"></i> Import BOM
                    </button>
                </div>

                {{-- MODAL IMPORT BOM --}}
                <div class="modal fade" id="importBomModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Import Bill of Materials</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form action="{{ route('bom.import') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="modal-body">
                                    <div class="alert alert-warning small">
                                        <i class="fas fa-info-circle"></i>
                                        Pastikan <strong>Parent Code</strong> dan <strong>Child Code</strong> sudah
                                        terdaftar di Master Part.<br>
                                        Jika kode part salah, data tidak akan masuk.
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Pilih File Excel</label>
                                        <input type="file" name="file" class="form-control" required>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" class="btn btn-primary">Upload</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="bomMainTable">
                            <thead class="bg-light">
                                <tr>
                                    {{-- Header dengan Toggle Sort --}}
                                    <th class="ps-4 py-3 sortable" data-sort="code" style="cursor:pointer;">
                                        Code Part <i class="fas fa-sort ms-1 small opacity-50"></i>
                                    </th>
                                    <th class="py-3 sortable" data-sort="number" style="cursor:pointer;">
                                        Part Number <i class="fas fa-sort ms-1 small opacity-50"></i>
                                    </th>
                                    <th class="py-3 sortable" data-sort="name" style="cursor:pointer;">
                                        Part Name <i class="fas fa-sort ms-1 small opacity-50"></i>
                                    </th>
                                    <th class="py-3">Category</th>
                                    <th class="py-3 text-center">Status BOM</th>
                                    <th class="py-3 text-end pe-4">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="bomTableBody">
                                @forelse($products as $p)
                                    <tr class="bom-item-row">
                                        <td class="ps-4">
                                            <span class="fw-bold text-dark code-text">
                                                {{ $p->code_part }}
                                            </span>
                                        </td>
                                        <td class=" text-dark number-text">{{ $p->part_number }}</td>
                                        <td class="name-text">{{ $p->part_name }}</td>
                                        <td class="category-text">
                                            @if($p->category == 'FINISH GOOD')
                                                <span class="badge bg-primary">FINISH GOOD</span>
                                            @else
                                                <span class="badge bg-info text-dark">SEMI FINISH</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($p->bom_components_count > 0)
                                                <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3">
                                                    <i class="fas fa-check-circle me-1"></i> Terisi ({{ $p->bom_components_count }}
                                                    Item)
                                                </span>
                                            @else
                                                <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-3">
                                                    <i class="fas fa-exclamation-circle me-1"></i> Kosong
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-end pe-4">
                                            <a href="{{ route('bom.index', $p->id) }}" class="btn btn-sm btn-dark">
                                                <i class="fas fa-cogs me-1"></i> Kelola BOM
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr id="emptyRow">
                                        <td colspan="6" class="text-center py-5 text-muted">Belum ada Part tipe FG/Semi-FG.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="p-3 border-top" id="paginationContainer">
                        {{ $products->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('bomSearchInput');
            const categoryFilter = document.getElementById('categoryFilter');
            const tableBody = document.getElementById('bomTableBody');
            const rows = Array.from(tableBody.getElementsByClassName('bom-item-row'));
            const sortHeaders = document.querySelectorAll('.sortable');

            // 1. FILTER LOGIC
            function applyFilters() {
                const searchTerm = searchInput.value.toLowerCase();
                const selectedCat = categoryFilter.value;

                rows.forEach(row => {
                    const code = row.querySelector('.code-text').textContent.toLowerCase();
                    const name = row.querySelector('.name-text').textContent.toLowerCase();
                    const number = row.querySelector('.number-text').textContent.toLowerCase();
                    const category = row.querySelector('.category-text').textContent.toUpperCase();

                    const matchesSearch = code.includes(searchTerm) || name.includes(searchTerm) || number.includes(searchTerm);
                    const matchesCategory = (selectedCat === 'ALL') || category.includes(selectedCat);

                    row.style.display = (matchesSearch && matchesCategory) ? '' : 'none';
                });
            }

            // 2. TOGGLE SORT LOGIC
            let currentSort = { column: null, asc: true };

            function sortTable(columnType) {
                const isAsc = currentSort.column === columnType ? !currentSort.asc : true;
                currentSort = { column: columnType, asc: isAsc };

                const sortedRows = rows.sort((a, b) => {
                    let valA, valB;

                    if (columnType === 'code') {
                        valA = a.querySelector('.code-text').textContent.trim().toLowerCase();
                        valB = b.querySelector('.code-text').textContent.trim().toLowerCase();
                    } else if (columnType === 'number') {
                        valA = a.querySelector('.number-text').textContent.trim().toLowerCase();
                        valB = b.querySelector('.number-text').textContent.trim().toLowerCase();
                    } else {
                        valA = a.querySelector('.name-text').textContent.trim().toLowerCase();
                        valB = b.querySelector('.name-text').textContent.trim().toLowerCase();
                    }

                    return isAsc ? valA.localeCompare(valB) : valB.localeCompare(valA);
                });

                sortedRows.forEach(row => tableBody.appendChild(row));
                updateSortIcons(columnType, isAsc);
            }

            function updateSortIcons(columnType, isAsc) {
                sortHeaders.forEach(header => {
                    const icon = header.querySelector('i');
                    if (header.dataset.sort === columnType) {
                        icon.className = isAsc ? 'fas fa-sort-up ms-1 text-dark' : 'fas fa-sort-down ms-1 text-dark';
                        icon.style.opacity = "1";
                    } else {
                        icon.className = 'fas fa-sort ms-1 small opacity-50';
                    }
                });
            }

            // EVENT LISTENERS
            searchInput.addEventListener('keyup', applyFilters);
            categoryFilter.addEventListener('change', applyFilters);

            sortHeaders.forEach(header => {
                header.addEventListener('click', () => sortTable(header.dataset.sort));
            });

            // Shortcut A-Z button tetap fungsi ke Part Name
            document.getElementById('sortAZ').addEventListener('click', () => sortTable('name'));
        });
    </script>
@endsection