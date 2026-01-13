@extends('layouts.app_simple')

@section('content')
<div class="row justify-content-center">
    <div class="col-12">
        
        <form action="{{ route('master.store', $product->id ?? '') }}" method="POST">
            @csrf
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold text-dark mb-1">
                        {{ isset($product) ? 'Edit Part Data' : 'Tambah Part Baru' }}
                    </h4>
                    <p class="text-muted small mb-0">Manajemen data part dan flow proses produksi</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('master.index') }}" class="btn btn-light border text-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fas fa-save me-1"></i> Simpan Data
                    </button>
                </div>
            </div>

            <div class="row">
                {{-- KOLOM KIRI: Data Dasar Produk --}}
                <div class="col-lg-5 mb-4">
                    <div class="card shadow-sm border-0 rounded-3 h-100">
                        <div class="card-header bg-white py-3 border-bottom-0">
                            <h6 class="fw-bold text-primary mb-0"><i class="fas fa-cube me-2"></i>Informasi Dasar</h6>
                        </div>
                        <div class="card-body">
                            
                            {{-- BARIS 1: Code Part & Part Number --}}
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label small text-muted text-uppercase fw-bold">Code Part</label>
                                    <input type="text" name="code_part" class="form-control fw-bold text-primary bg-light" 
                                           placeholder="CP-..."
                                           value="{{ old('code_part', $product->code_part ?? '') }}" required>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label small text-muted text-uppercase fw-bold">Part Number</label>
                                    <input type="text" name="part_number" class="form-control fw-bold" 
                                           placeholder="Nomor Part Asli"
                                           value="{{ old('part_number', $product->part_number ?? '') }}" required>
                                </div>
                            </div>

                            {{-- BARIS 2: Part Name --}}
                            <div class="mb-3">
                                <label class="form-label small text-muted text-uppercase fw-bold">Part Name</label>
                                <input type="text" name="part_name" class="form-control" 
                                       placeholder="Nama Deskripsi Part"
                                       value="{{ old('part_name', $product->part_name ?? '') }}" required>
                            </div>

                            {{-- BARIS 3: Customer --}}
                            <div class="mb-3">
                                <label class="form-label small text-muted text-uppercase fw-bold">Customer</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-building text-muted"></i></span>
                                    <input type="text" name="customer" class="form-control border-start-0 ps-0" 
                                           placeholder="Cth: PT. ASTRA HONDA MOTOR"
                                           value="{{ old('customer', $product->customer ?? '') }}" required>
                                </div>
                            </div>
                            
                            <hr class="border-light my-4">

                            {{-- Flow Process (Teks Saja) --}}
                            <div class="mb-3">
                                <label class="form-label small text-muted text-uppercase fw-bold">Flow Process (Label)</label>
                                <input type="text" name="flow_process" class="form-control" 
                                       placeholder="Cth: OP10 -> OP20"
                                       value="{{ old('flow_process', $product->flow_process ?? '') }}">
                                <div class="form-text small"><i class="fas fa-info-circle"></i> Hanya label visual. Kalkulasi menggunakan tabel routing.</div>
                            </div>

                            <div class="row g-3">
                                <div class="col-4">
                                    <label class="form-label small text-muted text-uppercase fw-bold">Qty/Box</label>
                                    <input type="number" name="qty_per_box" class="form-control text-center" 
                                           value="{{ old('qty_per_box', $product->qty_per_box ?? 1) }}">
                                </div>
                                <div class="col-4">
                                    <label class="form-label small text-muted text-uppercase fw-bold">Safety Stock</label>
                                    <input type="number" name="safety_stock" class="form-control text-center" 
                                           value="{{ old('safety_stock', $product->safety_stock ?? 0) }}">
                                </div>
                                <div class="col-4">
                                    <label class="form-label small text-muted text-uppercase fw-bold">UOM</label>
                                    <input type="text" name="uom" class="form-control text-center bg-light" 
                                           value="{{ old('uom', $product->uom ?? 'PCS') }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- KOLOM KANAN: Routing Editor --}}
                <div class="col-lg-7 mb-4">
                    <div class="card shadow-sm border-0 rounded-3 h-100">
                        <div class="card-header bg-white py-3 border-bottom-0 d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold text-dark mb-0"><i class="fas fa-network-wired me-2 text-warning"></i>Routing Mesin</h6>
                            <button type="button" class="btn btn-sm btn-dark rounded-pill px-3" onclick="addRoutingRow()">
                                <i class="fas fa-plus me-1"></i> Tambah Proses
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="ps-4 py-3 text-secondary small text-uppercase" width="50">#</th>
                                            <th class="py-3 text-secondary small text-uppercase">Nama Proses</th>
                                            <th class="py-3 text-secondary small text-uppercase">Mesin</th>
                                            <th class="py-3 text-secondary small text-uppercase" width="130">Cap (Pcs/Jam)</th>
                                            <th class="text-center py-3 text-secondary small text-uppercase" width="60">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="routing-table-body">
                                        {{-- Loop Data Lama --}}
                                        @if(isset($product) && $product->routings->count() > 0)
                                            @foreach($product->routings as $index => $route)
                                                <tr class="routing-row">
                                                    <td class="ps-4 text-center fw-bold text-muted row-num">{{ $index + 1 }}</td>
                                                    <td>
                                                        <input type="text" name="routings[{{ $index }}][process_name]" 
                                                               class="form-control form-control-sm border-0 bg-light" 
                                                               value="{{ $route->process_name }}" required placeholder="Nama Proses">
                                                    </td>
                                                    <td>
                                                        <select name="routings[{{ $index }}][machine_id]" class="form-select form-select-sm border-0 bg-light" required>
                                                            <option value="">-- Pilih Mesin --</option>
                                                            @foreach($machines as $machine)
                                                                <option value="{{ $machine->id }}" {{ $machine->id == $route->machine_id ? 'selected' : '' }}>
                                                                    {{ $machine->name }} ({{ $machine->machine_code }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <input type="number" name="routings[{{ $index }}][pcs_per_hour]" 
                                                               class="form-control form-control-sm text-center fw-bold border-0 bg-light text-primary" 
                                                               value="{{ $route->pcs_per_hour }}" required placeholder="0">
                                                    </td>
                                                    <td class="text-center">
                                                        <button type="button" class="btn btn-link text-danger p-0" onclick="removeRow(this)">
                                                            <i class="fas fa-times-circle"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                            
                            {{-- Pesan Kosong --}}
                            <div id="empty-message" class="text-center py-5 {{ (isset($product) && $product->routings->count() > 0) ? 'd-none' : '' }}">
                                <div class="text-muted opacity-50 mb-2">
                                    <i class="fas fa-random fa-3x"></i>
                                </div>
                                <p class="text-muted small mb-0">Belum ada routing proses.</p>
                                <small class="text-muted">Klik tombol "+ Tambah Proses" di atas.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </form>
    </div>
</div>

{{-- JAVASCRIPT --}}
<script>
    let rowIndex = {{ isset($product) ? $product->routings->count() : 0 }};

    function addRoutingRow() {
        document.getElementById('empty-message').classList.add('d-none');
        
        let tableBody = document.getElementById('routing-table-body');
        let row = document.createElement('tr');
        row.classList.add('routing-row');
        
        row.innerHTML = `
            <td class="ps-4 text-center fw-bold text-muted row-num">#</td>
            <td>
                <input type="text" name="routings[${rowIndex}][process_name]" 
                       class="form-control form-control-sm border-0 bg-light" required placeholder="Nama Proses">
            </td>
            <td>
                <select name="routings[${rowIndex}][machine_id]" class="form-select form-select-sm border-0 bg-light" required>
                    <option value="">-- Pilih Mesin --</option>
                    @foreach($machines as $machine)
                        <option value="{{ $machine->id }}">{{ $machine->name }} ({{ $machine->machine_code }})</option>
                    @endforeach
                </select>
            </td>
            <td>
                <input type="number" name="routings[${rowIndex}][pcs_per_hour]" 
                       class="form-control form-control-sm text-center fw-bold border-0 bg-light text-primary" required value="0">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-link text-danger p-0" onclick="removeRow(this)">
                    <i class="fas fa-times-circle"></i>
                </button>
            </td>
        `;
        
        tableBody.appendChild(row);
        rowIndex++;
        updateRowNumbers();
    }

    function removeRow(btn) {
        btn.closest('tr').remove();
        
        let tableBody = document.getElementById('routing-table-body');
        if (tableBody.children.length === 0) {
            document.getElementById('empty-message').classList.remove('d-none');
        }
        updateRowNumbers();
    }

    function updateRowNumbers() {
        let rows = document.querySelectorAll('.row-num');
        rows.forEach((cell, index) => {
            cell.textContent = index + 1;
        });
    }
</script>
@endsection