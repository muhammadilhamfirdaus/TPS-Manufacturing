@extends('layouts.app_simple')

@section('title', 'Planning Schedule')

@section('content')
    <div class="row">
        <div class="col-12">

            {{-- Header & Action Buttons --}}
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold text-dark mb-1">Production Planning Schedule</h4>
                    <p class="text-muted small mb-0">
                        Estimasi Perencanaan Produksi bulan 
                        <span class="fw-bold text-primary">
                            {{ date('F Y', mktime(0, 0, 0, $selectedMonth, 1, $selectedYear)) }}
                        </span>
                    </p>
                </div>
                <div class="d-flex gap-2">
                    
                    
                    {{-- 1. TOMBOL TOGGLE HISTORY --}}
                    <div class="btn-group me-2">
                        <a href="{{ route('plans.index') }}" 
                           class="btn btn-sm {{ !request('show_history') ? 'btn-primary' : 'btn-outline-primary' }}">
                            Aktif
                        </a>
                        <a href="{{ route('plans.index', ['show_history' => 1]) }}" 
                           class="btn btn-sm {{ request('show_history') ? 'btn-secondary' : 'btn-outline-secondary' }}">
                            <i class="fas fa-history me-1"></i> History
                        </a>
                    </div>

                    {{-- Search Form & Filter --}}
                    <form action="{{ route('plans.index') }}" method="GET" class="d-flex gap-2 align-items-center">
                        @if(request('show_history'))
                            <input type="hidden" name="show_history" value="1">
                        @endif

                        {{-- Filter Bulan --}}
                        <select name="filter_month" class="form-select form-select-sm border-secondary fw-bold" style="width: 110px;" onchange="this.form.submit()">
                            @for($m=1; $m<=12; $m++)
                                <option value="{{ $m }}" {{ $selectedMonth == $m ? 'selected' : '' }}>
                                    {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                                </option>
                            @endfor
                        </select>

                        {{-- Filter Tahun --}}
                        <select name="filter_year" class="form-select form-select-sm border-secondary fw-bold" style="width: 90px;" onchange="this.form.submit()">
                            @for($y=date('Y')-1; $y<=date('Y')+1; $y++)
                                <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>
                                    {{ $y }}
                                </option>
                            @endfor
                        </select>

                        <div class="input-group input-group-sm">
                            <input type="text" name="search" class="form-control"
                                placeholder="Cari Part..." value="{{ request('search') }}">
                            <button type="submit" class="btn btn-outline-secondary">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>

                  {{-- [BARU] Tombol Sync BOM (Struktur & Material) --}}
                    <form action="{{ route('plans.sync_bom') }}" method="POST" id="formSyncBom">
                        @csrf
                        <button type="button" onclick="confirmSyncBom()" class="btn btn-info fw-bold text-white shadow-sm" title="Update Struktur BOM & Kebutuhan Material">
                            <i class="fas fa-sitemap me-1"></i> Regenerate BOM
                        </button>
                    </form>
                    
                    <a href="{{ route('plans.template') }}" class="btn btn-sm btn-success text-white">
                        <i class="fas fa-file-excel"></i> Template
                    </a>

                    <button type="button" class="btn btn-sm btn-info text-white" data-bs-toggle="modal"
                        data-bs-target="#importModal">
                        <i class="fas fa-upload"></i> Import
                    </button>

                    <a href="{{ route('plans.create') }}" class="btn btn-sm btn-dark">
                        <i class="fas fa-plus"></i> Baru
                    </a>
                </div>
            </div>

            {{-- Alerts --}}
            @if(session('success'))
                <div class="alert alert-success border-0 bg-success bg-opacity-10 text-success mb-4">
                    <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
                </div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger mb-4">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- MAIN TABLE --}}
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle mb-0" style="font-size: 0.85rem;">
                            <thead class="bg-light text-center align-middle">
                                <tr style="border-bottom: 2px solid #000;">
                                    <th class="py-3 bg-light text-uppercase">Kode</th>
                                    <th class="py-3 bg-light text-uppercase">Nama Part</th>
                                    <th class="py-3 bg-light text-uppercase">Nomor Part</th>
                                    <th class="py-3 bg-light text-uppercase">Customer</th>
                                    <th class="py-3 bg-light text-uppercase">Flow Proses</th>
                                    <th class="py-3 bg-light text-uppercase">Seksi</th>
                                    <th class="py-3 bg-info bg-opacity-10 text-uppercase">Murni (Plan)</th>
                                    <th class="py-3 bg-warning bg-opacity-10 text-uppercase">Kebutuhan (PO)</th>
                                    <th class="py-3 bg-light text-uppercase" style="width: 100px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- LOOPING PER BATCH / HEADER  --}}
                                @forelse($plans as $header)
                                    
                                    {{-- SEPARATOR: Garis tebal pemisah antar Batch Input --}}
                                    @if(!$loop->first)
                                        <tr style="border-top: 3px solid #6c757d;"></tr>
                                    @endif

                                    {{-- LOOPING DETAIL BARANG DI DALAM 1 BATCH --}}
                                    @foreach($header->details as $index => $item)
                                        
                                        {{-- Styling Baris: FG (Putih) vs Komponen (Abu) --}}
                                        <tr class="{{ $index == 0 ? 'bg-white fw-bold border-bottom' : 'bg-light text-secondary' }} 
                                                   {{ $header->status == 'HISTORY' ? 'opacity-50' : '' }}">

                                            {{-- 1. KODE PART (FIXED WITH OPTIONAL) --}}
                                            <td class="text-center align-middle">
                                                @if($index == 0)
                                                    <span class="badge bg-primary">{{ optional($item->product)->code_part ?? 'FG' }}</span>
                                                @else
                                                    <span class="badge bg-secondary opacity-75">{{ optional($item->product)->code_part ?? 'RM' }}</span>
                                                @endif
                                            </td>

                                            {{-- 2. NAMA PART (FIXED WITH OPTIONAL) --}}
                                            <td class="align-middle">
                                                @if($index == 0)
                                                    <span class="text-dark fw-bold">{{ optional($item->product)->part_name ?? 'Unknown Part' }}</span>
                                                    
                                                    {{-- BADGE REVISI --}}
                                                    @if($header->revision > 0)
                                                        <span class="badge bg-danger ms-2" style="font-size: 0.6em;">REV-{{ $header->revision }}</span>
                                                    @endif
                                                    
                                                    @if($header->status == 'HISTORY')
                                                        <span class="badge bg-secondary ms-1" style="font-size: 0.6em;">HISTORY</span>
                                                    @endif
                                                @else
                                                    {{-- KOMPONEN --}}
                                                    <div class="ps-4 d-flex align-items-center">
                                                        <i class="fas fa-level-up-alt fa-rotate-90 me-2 text-muted small"></i>
                                                        <span>{{ optional($item->product)->part_name ?? 'Unknown Part' }}</span>
                                                    </div>
                                                @endif
                                            </td>

                                            {{-- 3. NOMOR PART --}}
                                            <td class="align-middle {{ $index > 0 ? 'small' : '' }}">
                                                {{ optional($item->product)->part_number ?? '-' }}
                                            </td>

                                            {{-- 4. CUSTOMER --}}
                                            <td class="text-center align-middle small">
                                                {{ optional($item->product)->customer ?? '-' }}
                                            </td>

                                            {{-- 5. FLOW PROSES (Ambil dari Routing: process_name) --}}
                                            <td class="text-center align-middle small">
                                                @if($item->product && $item->product->routings->isNotEmpty())
                                                    {{-- Gabungkan semua nama proses dengan tanda panah (->) --}}
                                                    <div class="text-primary" style="font-size: 0.75rem;">
                                                        {{ $item->product->routings->pluck('process_name')->implode(' ➝ ') }}
                                                    </div>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>

                                            {{-- 6. SEKSI / MESIN (Ambil dari Routing: machine->name) --}}
                                            <td class="text-center align-middle text-uppercase small">
                                                @if($item->product && $item->product->routings->isNotEmpty())
                                                    @foreach($item->product->routings as $route)
                                                        {{-- Tampilkan Nama Mesin per proses --}}
                                                        <div class="mb-1">
                                                            <span class="badge bg-light text-dark border">
                                                                {{ optional($route->machine)->name ?? 'No Machine' }}
                                                            </span>
                                                        </div>
                                                    @endforeach
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>

                                            {{-- 7. MURNI (PLAN) --}}
                                            <td class="text-end align-middle {{ $index == 0 ? 'bg-info bg-opacity-10 fw-bold' : '' }}">
                                                {{ number_format($item->calc_murni_plan, 0, ',', '.') }}
                                            </td>

                                            {{-- 8. KEBUTUHAN (PO) --}}
                                            <td class="text-end align-middle {{ $index == 0 ? 'bg-warning bg-opacity-10 fw-bold' : '' }}">
                                                {{ number_format($item->calc_kebutuhan_po, 0, ',', '.') }}
                                                
                                                @if($item->calc_total_box > 0)
                                                    <div style="font-size: 0.65rem;" class="text-muted">
                                                        ({{ number_format($item->calc_total_box) }} Box)
                                                    </div>
                                                @endif
                                            </td>

                                            {{-- 9. AKSI --}}
                                            <td class="text-center align-middle">
                                                @if($index == 0 && $header->status != 'HISTORY')
                                                    <div class="d-flex justify-content-center gap-1">
                                                        {{-- Tombol Hapus --}}
                                                        <form onsubmit="return confirm('Hapus Batch Plan #{{ $header->id }}?');"
                                                            action="{{ route('plans.destroy', $item->id) }}" method="POST">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-outline-danger border-0" title="Hapus">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </button>
                                                        </form>

                                                        {{-- Tombol Revisi --}}
                                                        <button type="button" class="btn btn-sm btn-outline-warning border-0" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#reviseModal-{{ $header->id }}"
                                                                title="Revisi Plan (Buat Versi Baru)">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                    </div>

                                                    {{-- MODAL REVISI --}}
                                                    <div class="modal fade" id="reviseModal-{{ $header->id }}" tabindex="-1">
                                                        <div class="modal-dialog modal-sm">
                                                            <form action="{{ route('plans.revise', $header->id) }}" method="POST">
                                                                @csrf
                                                                <div class="modal-content">
                                                                    <div class="modal-header bg-warning bg-opacity-10 py-2">
                                                                        <h6 class="modal-title fw-bold small">Revisi Plan #{{ $header->id }}</h6>
                                                                        <button type="button" class="btn-close small" data-bs-dismiss="modal"></button>
                                                                    </div>
                                                                    <div class="modal-body text-start">
                                                                        <div class="mb-2">
                                                                            <label class="small text-muted mb-0">Part FG</label>
                                                                            <div class="fw-bold text-dark">{{ optional($item->product)->part_name ?? 'Unknown' }}</div>
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <label class="small text-muted mb-0">Qty Saat Ini</label>
                                                                            <div class="fw-bold text-primary">{{ number_format($item->qty_plan) }}</div>
                                                                        </div>
                                                                        <div class="mb-2">
                                                                            <label class="form-label fw-bold small mb-1">Qty Baru (Revisi)</label>
                                                                            <input type="number" name="new_qty" class="form-control form-control-sm" 
                                                                                   value="{{ $item->qty_plan }}" min="1" required>
                                                                        </div>
                                                                        <div class="alert alert-info py-1 px-2 mb-0" style="font-size: 0.7rem;">
                                                                            <i class="fas fa-info-circle me-1"></i>
                                                                            Plan lama -> History. Plan baru -> <strong>Rev-{{ $header->revision + 1 }}</strong>.
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer py-1">
                                                                        <button type="submit" class="btn btn-warning btn-sm w-100">
                                                                            Simpan Revisi
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach

                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-5 text-muted">
                                            <i class="fas fa-box-open fa-2x mb-3"></i><br>
                                            Belum ada data Planning bulan ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    <div class="p-3 border-top">
                        {{ $plans->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Import Excel --}}
    <div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Import Plan dari Excel</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('plans.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Pilih File Excel (.xlsx)</label>
                            <input type="file" name="file" class="form-control" required>
                            <small class="text-muted">Pastikan format sesuai template.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Upload & Generate</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    {{-- Letakkan ini SEBELUM script custom Anda --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- Baru kemudian script custom Anda --}}
    <script>
        function confirmSyncBom() {
            Swal.fire({
                title: 'Update Struktur BOM?',
                text: "Sistem akan menghapus daftar material lama...",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#0dcaf0',
                confirmButtonText: 'Ya, Update Struktur!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.showLoading(); // Ini juga butuh library Swal
                    document.getElementById('formSyncBom').submit();
                }
            })
        }
    </script>
@endsection