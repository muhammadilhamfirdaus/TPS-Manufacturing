@extends('layouts.app_simple')

@section('content')
<div class="row justify-content-center">
    <div class="col-12">
        
        {{-- Tampilkan Error Validasi --}}
        @if ($errors->any())
            <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger mb-4 rounded-3">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong class="me-2">Gagal Menyimpan:</strong>
                    <span class="small">Silakan periksa input di bawah ini.</span>
                </div>
                <ul class="mb-0 mt-2 small ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        
        <form action="{{ route('master-line.store', $line->id ?? '') }}" method="POST">
            @csrf
            
            {{-- Header Page & Actions --}}
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold text-dark mb-1">
                        {{ isset($line) ? 'Edit Line Produksi' : 'Tambah Line Baru' }}
                    </h4>
                    <p class="text-muted small mb-0">Kelola line produksi dan daftar mesin yang tersedia</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('master-line.index') }}" class="btn btn-light border text-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fas fa-save me-1"></i> Simpan Data
                    </button>
                </div>
            </div>

            <div class="row">
                {{-- KOLOM KIRI: Data Line --}}
                <div class="col-lg-4 mb-4">
                    <div class="card shadow-sm border-0 rounded-3 h-100">
                        <div class="card-header bg-white py-3 border-bottom-0">
                            <h6 class="fw-bold text-primary mb-0"><i class="fas fa-info-circle me-2"></i>Informasi Line</h6>
                        </div>
                        <div class="card-body">
                            {{-- Dropdown Plant --}}
                            <div class="mb-3">
                                <label class="form-label small text-muted text-uppercase fw-bold">Lokasi Plant</label>
                                <select name="plant" class="form-select fw-bold text-dark bg-light border-0" required>
                                    <option value="">-- Pilih Plant --</option>
                                    @php 
                                        $plants = ['PLANT 1', 'PLANT 2', 'PLANT 3A', 'PLANT 3B', 'EXTERNAL']; 
                                    @endphp
                                    @foreach($plants as $p)
                                        <option value="{{ $p }}" {{ old('plant', $line->plant ?? '') == $p ? 'selected' : '' }}>
                                            {{ $p }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Input Nama Line --}}
                            <div class="mb-3">
                                <label class="form-label small text-muted text-uppercase fw-bold">Nama Line</label>
                                <input type="text" name="name" class="form-control fw-bold text-dark bg-light border-0" 
                                       placeholder="Contoh: LINE STAMPING P-2"
                                       value="{{ old('name', $line->name ?? '') }}" required>
                            </div>

                            {{-- INPUT BARU: JUMLAH SHIFT --}}
                            <div class="mb-3">
                                <label class="form-label small text-muted text-uppercase fw-bold">Jumlah Shift Op.</label>
                                <select name="total_shifts" class="form-select fw-bold text-dark bg-light border-0" required>
                                    <option value="1" {{ old('total_shifts', $line->total_shifts ?? 3) == 1 ? 'selected' : '' }}>1 Shift (Normal)</option>
                                    <option value="2" {{ old('total_shifts', $line->total_shifts ?? 3) == 2 ? 'selected' : '' }}>2 Shift (Long Shift)</option>
                                    <option value="3" {{ old('total_shifts', $line->total_shifts ?? 3) == 3 ? 'selected' : '' }}>3 Shift (24 Jam)</option>
                                </select>
                            </div>
                            
                            {{-- Info Box --}}
                            <div class="alert alert-light border-0 bg-light small text-secondary d-flex align-items-start mt-4 rounded-3">
                                <i class="fas fa-lightbulb text-warning me-2 mt-1"></i>
                                <div>
                                    <strong>Tips:</strong> Data Plant digunakan untuk pengelompokan di report MPP Summary.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- KOLOM KANAN: Daftar Mesin --}}
                <div class="col-lg-8 mb-4">
                    <div class="card shadow-sm border-0 rounded-3 h-100">
                        <div class="card-header bg-white py-3 border-bottom-0 d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold text-dark mb-0"><i class="fas fa-cogs me-2 text-secondary"></i>Daftar Mesin</h6>
                            <button type="button" class="btn btn-sm btn-dark rounded-pill px-3" onclick="addMachineRow()">
                                <i class="fas fa-plus me-1"></i> Tambah Mesin
                            </button>
                        </div>
                        <div class="card-body p-0">
                            
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="ps-4 py-3 text-secondary small text-uppercase" width="30%">Nama Mesin</th>
                                            <th class="py-3 text-secondary small text-uppercase" width="20%">Tipe</th>
                                            <th class="py-3 text-secondary small text-uppercase" width="20%">Kode Aset</th>
                                            <th class="py-3 text-secondary small text-uppercase" width="20%">Group</th>
                                            <th class="text-center py-3 text-secondary small text-uppercase" width="10%">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="machine-table-body">
                                        {{-- Loop Data Lama (Edit Mode) --}}
                                        @if(isset($line) && $line->machines->count() > 0)
                                            @foreach($line->machines as $index => $machine)
                                                <tr>
                                                    <input type="hidden" name="machines[{{ $index }}][id]" value="{{ $machine->id }}">
                                                    
                                                    <td class="ps-4">
                                                        <input type="text" name="machines[{{ $index }}][name]" 
                                                               class="form-control form-control-sm border-0 bg-light fw-bold" required 
                                                               placeholder="Ex: P2-1" value="{{ $machine->name }}">
                                                    </td>
                                                    <td>
                                                        <select name="machines[{{ $index }}][type]" class="form-select form-select-sm border-0 bg-light text-secondary fw-bold">
                                                            <option value="INTERNAL" {{ ($machine->type ?? 'INTERNAL') == 'INTERNAL' ? 'selected' : '' }}>INTERNAL</option>
                                                            <option value="SUBCONT" {{ ($machine->type ?? '') == 'SUBCONT' ? 'selected' : '' }}>SUBCONT (Vendor)</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <input type="text" name="machines[{{ $index }}][machine_code]" 
                                                               class="form-control form-control-sm border-0 bg-light" required 
                                                               placeholder="Ex: 11-P45-43" value="{{ $machine->machine_code }}">
                                                    </td>
                                                    <td>
                                                        <input type="text" name="machines[{{ $index }}][machine_group]" 
                                                               class="form-control form-control-sm border-0 bg-light" 
                                                               placeholder="Ex: 45 TON" value="{{ $machine->machine_group }}">
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
                            
                            {{-- Empty State --}}
                            <div id="empty-message" class="text-center py-5 {{ (isset($line) && $line->machines->count() > 0) ? 'd-none' : '' }}">
                                <div class="text-muted opacity-50 mb-2">
                                    <i class="fas fa-robot fa-3x"></i>
                                </div>
                                <p class="text-muted small mb-0">Belum ada mesin terdaftar.</p>
                                <small class="text-muted">Klik tombol "+ Tambah Mesin" di atas.</small>
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
    let machineIndex = {{ isset($line) ? $line->machines->count() : 0 }};

    function addMachineRow() {
        document.getElementById('empty-message').classList.add('d-none');
        
        let tableBody = document.getElementById('machine-table-body');
        let row = document.createElement('tr');
        
        row.innerHTML = `
            <td class="ps-4">
                <input type="text" name="machines[${machineIndex}][name]" 
                       class="form-control form-control-sm border-0 bg-light fw-bold" required placeholder="Nama Mesin (Ex: P2-5)">
            </td>
            <td>
                <select name="machines[${machineIndex}][type]" class="form-select form-select-sm border-0 bg-light text-secondary fw-bold">
                    <option value="INTERNAL">INTERNAL</option>
                    <option value="SUBCONT">SUBCONT (Vendor)</option>
                </select>
            </td>
            <td>
                <input type="text" name="machines[${machineIndex}][machine_code]" 
                       class="form-control form-control-sm border-0 bg-light" required placeholder="Kode Aset">
            </td>
            <td>
                <input type="text" name="machines[${machineIndex}][machine_group]" 
                       class="form-control form-control-sm border-0 bg-light" placeholder="Ex: 45 TON">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-link text-danger p-0" onclick="removeRow(this)">
                    <i class="fas fa-times-circle"></i>
                </button>
            </td>
        `;
        
        tableBody.appendChild(row);
        machineIndex++;
    }

    function removeRow(btn) {
        btn.closest('tr').remove();
        let tableBody = document.getElementById('machine-table-body');
        if (tableBody.children.length === 0) {
            document.getElementById('empty-message').classList.remove('d-none');
        }
    }
</script>
@endsection