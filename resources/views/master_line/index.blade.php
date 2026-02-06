@extends('layouts.app_simple')

@section('content')
<div class="row justify-content-center">
    <div class="col-12">
        
        {{-- Header Page --}}
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
            <div>
                <h4 class="fw-bold text-dark mb-1">Master Line & Mesin</h4>
                <p class="text-muted small mb-0">Kelola line produksi, mesin internal, dan vendor subcont</p>
            </div>

            {{-- TOOLBOX: SEARCH, FILTER, & ACTION --}}
            <div class="d-flex flex-wrap gap-2">
                {{-- Search Input --}}
                <div class="input-group input-group-sm" style="width: 250px;">
                    <span class="input-group-text bg-white border-end-0 text-muted">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" id="lineSearchInput" class="form-control border-start-0 ps-0 shadow-none" placeholder="Cari Nama Line atau Plant...">
                </div>

                {{-- Plant Filter --}}
                <select id="plantFilter" class="form-select form-select-sm shadow-sm" style="width: 130px;">
                    <option value="ALL">Semua Plant</option>
                    @php 
                        $plants = $lines->pluck('plant')->unique()->filter();
                    @endphp
                    @foreach($plants as $p)
                        <option value="{{ $p }}">{{ $p }}</option>
                    @endforeach
                </select>

               {{-- TOMBOL ACTION --}}
                <div class="d-flex gap-2 mb-3">
                    <a href="{{ route('lines.template') }}" class="btn btn-success text-white">
                        <i class="fas fa-download me-1"></i> Template
                    </a>
                    <button type="button" class="btn btn-info text-white" data-bs-toggle="modal" data-bs-target="#importLineModal">
                        <i class="fas fa-file-upload me-1"></i> Import Excel
                    </button>
                    <a href="{{ route('lines.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Tambah Line Baru
                    </a>
                </div>

                {{-- MODAL IMPORT --}}
                <div class="modal fade" id="importLineModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Import Line & Machine</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form action="{{ route('lines.import') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="modal-body">
                                    <div class="alert alert-warning small">
                                        <i class="fas fa-exclamation-triangle"></i> 
                                        Pastikan nama <strong>PLANT</strong> ditulis konsisten (Contoh: "PLANT 1"). <br>
                                        Sistem akan otomatis menggabungkan mesin ke dalam Line jika <strong>Nama Line</strong> sama.
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
        </div>

        {{-- Main Card --}}
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="lineMainTable">
                        <thead class="bg-light">
                            <tr>
                                {{-- HEADER DENGAN TOGGLE SORT --}}
                                <th class="ps-4 py-3 text-secondary small text-uppercase fw-bold sortable" data-sort="plant" style="cursor:pointer;" width="15%">
                                    Plant <i class="fas fa-sort ms-1 opacity-50"></i>
                                </th>
                                <th class="py-3 text-secondary small text-uppercase fw-bold sortable" data-sort="name" style="cursor:pointer;" width="25%">
                                    Nama Line <i class="fas fa-sort ms-1 opacity-50"></i>
                                </th>
                                <th class="py-3 text-secondary small text-uppercase fw-bold text-center" width="15%">Total Resource</th>
                                <th class="py-3 text-secondary small text-uppercase fw-bold" width="30%">Daftar Mesin / Vendor</th>
                                <th class="pe-4 py-3 text-secondary small text-uppercase fw-bold text-end" width="15%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="lineTableBody">
                            @forelse($lines as $line)
                            <tr class="line-row">
                                {{-- Plant --}}
                                <td class="ps-4">
                                    <span class="fw-bold text-dark plant-val">
                                        {{ $line->plant ?? '-' }}
                                    </span>
                                </td>

                                {{-- Nama Line --}}
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <div class="fw-bold text-dark name-val">{{ $line->name }}</div>
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
                                        @forelse($line->machines->take(5) as $m)
                                            @php
                                                $isSubcont = ($m->type ?? 'INTERNAL') === 'SUBCONT';
                                                $badgeClass = $isSubcont 
                                                    ? 'bg-warning bg-opacity-25 text-dark border-warning border-opacity-50' 
                                                    : 'bg-light text-secondary border';
                                                $icon = $isSubcont ? '<i class="fas fa-truck small me-1"></i>' : '';
                                            @endphp
                                            <span class="badge {{ $badgeClass }} fw-normal border" title="{{ $isSubcont ? 'Vendor Subcont' : 'Mesin Internal' }}">
                                                {!! $icon !!}{{ $m->name }}
                                            </span>
                                        @empty
                                            <span class="text-muted small fst-italic">- Belum ada mesin -</span>
                                        @endforelse
                                        
                                        @if($line->machines_count > 5)
                                            <span class="badge bg-light text-muted border fw-normal">
                                                +{{ $line->machines_count - 5 }} lainnya
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Aksi --}}
                               {{-- Aksi --}}
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end gap-1">
                                        
                                        {{-- GANTI route('master-line.edit') JADI route('lines.edit') --}}
                                        <a href="{{ route('lines.edit', $line->id) }}" class="btn btn-sm btn-light text-primary border-0 shadow-sm" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>

                                        {{-- GANTI route('master-line.destroy') JADI route('lines.destroy') --}}
                                        <form action="{{ route('lines.destroy', $line->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin hapus Line ini?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-light text-danger border-0 shadow-sm" title="Hapus">
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
                
                <div id="paginationContainer" class="d-flex justify-content-end p-3 border-top">
                    {{ $lines->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('lineSearchInput');
    const plantFilter = document.getElementById('plantFilter');
    const tableBody = document.getElementById('lineTableBody');
    const rows = Array.from(tableBody.getElementsByClassName('line-row'));
    const sortHeaders = document.querySelectorAll('.sortable');

    // 1. REAL-TIME SEARCH & PLANT FILTER
    function applyFilters() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedPlant = plantFilter.value;

        rows.forEach(row => {
            const plantText = row.querySelector('.plant-val').textContent.trim();
            const nameText = row.querySelector('.name-val').textContent.toLowerCase();
            
            const matchesSearch = nameText.includes(searchTerm) || plantText.toLowerCase().includes(searchTerm);
            const matchesPlant = (selectedPlant === 'ALL') || plantText === selectedPlant;

            row.style.display = (matchesSearch && matchesPlant) ? '' : 'none';
        });
    }

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

    // EVENT LISTENERS
    searchInput.addEventListener('keyup', applyFilters);
    plantFilter.addEventListener('change', applyFilters);
    sortHeaders.forEach(header => {
        header.addEventListener('click', () => sortTable(header.dataset.sort));
    });
});
</script>
@endsection