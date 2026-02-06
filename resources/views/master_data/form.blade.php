@extends('layouts.app_simple')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-10">

            {{-- PENTING: Tambahkan enctype untuk upload file --}}
            <form action="{{ route('master.store', $product->id ?? '') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="fw-bold text-dark mb-1">
                            {{ isset($product) ? 'Edit Master Part' : 'Buat Part Baru' }}
                        </h4>
                        <p class="text-muted small mb-0">Lengkapi data part dan alur proses produksi di bawah ini.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('master.index') }}"
                            class="btn btn-light border shadow-sm text-secondary btn-sm px-3">
                            <i class="fas fa-arrow-left me-1"></i> Batal
                        </a>
                        <button type="submit" class="btn btn-primary shadow-sm btn-sm px-4 fw-bold">
                            <i class="fas fa-save me-1"></i> Simpan Data
                        </button>
                    </div>
                </div>

                <div class="row g-4">
                    {{-- KOLOM KIRI: DATA UMUM --}}
                    <div class="col-md-4">
                        <div class="card shadow-sm border-0 rounded-4 h-100">
                            <div class="card-body p-4">

                                {{-- INPUT FOTO PRODUK --}}
                                <div class="text-center mb-4">
                                    <label for="photoInput"
                                        class="cursor-pointer position-relative d-inline-block group-hover-overlay">
                                        {{-- Preview Image --}}
                                        @if(isset($product) && $product->photo)
                                            <img id="photoPreview" src="{{ asset('storage/' . $product->photo) }}"
                                                class="rounded-3 shadow-sm border"
                                                style="width: 150px; height: 150px; object-fit: cover;" alt="Preview">
                                        @else
                                            <img id="photoPreview" src="https://via.placeholder.com/150x150?text=Upload+Foto"
                                                class="rounded-3 shadow-sm border bg-light"
                                                style="width: 150px; height: 150px; object-fit: cover;" alt="Preview">
                                        @endif

                                        {{-- Overlay Edit Icon --}}
                                        <div
                                            class="position-absolute top-50 start-50 translate-middle badge bg-dark bg-opacity-75 p-2 rounded-circle">
                                            <i class="fas fa-camera text-white"></i>
                                        </div>
                                    </label>
                                    <input type="file" name="photo" id="photoInput" class="d-none" accept="image/*"
                                        onchange="previewImage(event)">
                                    <div class="small text-muted mt-2">Klik gambar untuk upload foto</div>
                                </div>

                                <h6 class="fw-bold text-dark mb-3 border-bottom pb-2"><i
                                        class="fas fa-cube text-primary me-2"></i>Data Umum</h6>

                                {{-- Code Part & Number --}}
                                <div class="row g-2 mb-3">
                                    <div class="col-12">
                                        <label class="form-label small fw-bold text-muted">Code Part</label>
                                        <input type="text" name="code_part"
                                            class="form-control bg-light border-0 fw-bold text-primary"
                                            placeholder="Contoh: CP-001"
                                            value="{{ old('code_part', $product->code_part ?? '') }}" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-bold text-muted">Part Number</label>
                                        <input type="text" name="part_number" class="form-control"
                                            placeholder="Nomor Part Original"
                                            value="{{ old('part_number', $product->part_number ?? '') }}" required>
                                    </div>
                                </div>

                                {{-- Part Name --}}
                                <div class="mb-3">
                                    <label class="form-label small fw-bold text-muted">Nama Part</label>
                                    <input type="text" name="part_name" class="form-control"
                                        placeholder="Deskripsi Nama Part"
                                        value="{{ old('part_name', $product->part_name ?? '') }}" required>
                                </div>

                                {{-- Customer --}}
                                <div class="mb-4">
                                    <label class="form-label small fw-bold text-muted">Customer</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0 text-muted"><i
                                                class="fas fa-building"></i></span>
                                        <input type="text" name="customer" class="form-control border-start-0 ps-0"
                                            placeholder="Nama Customer"
                                            value="{{ old('customer', $product->customer ?? '') }}" required>
                                    </div>
                                </div>

                                {{-- Kategori --}}
                                <div class="mb-3">
                                    <label class="form-label small fw-bold text-muted mb-2">Kategori Part</label>
                                    <div class="d-flex flex-column gap-2">
                                        @php
                                            $cats = ['FINISH GOOD' => 'primary', 'SEMI FINISH GOOD' => 'info', 'RAW MATERIAL' => 'secondary', 'CONSUMABLE' => 'warning'];
                                            $curCat = old('category', $product->category ?? 'FINISH GOOD');
                                        @endphp
                                        @foreach($cats as $key => $color)
                                            <div class="form-check custom-radio-card">
                                                <input class="form-check-input" type="radio" name="category" id="cat_{{$key}}"
                                                    value="{{$key}}" {{ $curCat == $key ? 'checked' : '' }}>
                                                <label
                                                    class="form-check-label w-100 p-2 border rounded d-flex align-items-center justify-content-between cursor-pointer"
                                                    for="cat_{{$key}}">
                                                    <span class="small fw-bold">{{ $key }}</span>
                                                    <span class="badge bg-{{$color}} rounded-circle p-1"> </span>
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                   {{-- KOLOM KANAN: SPESIFIKASI & ROUTING --}}
                    <div class="col-md-8">

                        {{-- SPESIFIKASI --}}
                        <div class="card shadow-sm border-0 rounded-4 mb-4">
                            <div class="card-body p-4">
                                <h6 class="fw-bold text-dark mb-3">
                                    <i class="fas fa-sliders-h text-warning me-2"></i>Spesifikasi Produksi
                                </h6>

                                {{-- [BARU] TIPE KANBAN (Ditaruh paling atas agar menonjol) --}}
                                <div class="mb-4 p-3 bg-light rounded border border-primary border-start-0 border-end-0 border-top-0 border-3">
                                    <label for="kanban_type" class="form-label fw-bold text-primary">
                                        <i class="fas fa-calculator me-1"></i> Tipe Perhitungan Kanban
                                    </label>
                                    <select name="kanban_type" id="kanban_type" class="form-select" required>
                                        @php
                                            // Cek value lama (jika error validasi) ATAU data dari database ATAU default 'PRODUCTION'
                                            $selectedType = old('kanban_type', $product->kanban_type ?? 'PRODUCTION');
                                        @endphp
                                        <option value="PRODUCTION" {{ $selectedType == 'PRODUCTION' ? 'selected' : '' }}>PRODUCTION (Internal WIP)</option>
                                        <option value="SUBCONT" {{ $selectedType == 'SUBCONT' ? 'selected' : '' }}>SUBCONT (Vendor Part)</option>
                                        <option value="FINISH_GOODS" {{ $selectedType == 'FINISH_GOODS' ? 'selected' : '' }}>FINISH GOODS (Customer Part)</option>
                                    </select>
                                    <div class="form-text small text-muted">
                                        <i class="fas fa-info-circle me-1"></i> Pilihan ini menentukan rumus Lead Time di menu Kalkulasi Kanban.
                                    </div>
                                </div>
                                {{-- --------------------------------------------------------- --}}

                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-muted">Flow Process (Label)</label>
                                        <input type="text" name="flow_process" class="form-control"
                                            placeholder="Ex: OP10 -> OP20"
                                            value="{{ old('flow_process', $product->flow_process ?? '') }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small fw-bold text-muted">Qty/Box</label>
                                        <input type="number" name="qty_per_box" class="form-control text-center"
                                            value="{{ old('qty_per_box', $product->qty_per_box ?? 1) }}">
                                    </div>

                                    <div class="col-md-2">
                                        <label class="form-label small fw-bold text-primary">Kode Box</label>
                                        <input type="text" name="kode_box" class="form-control" 
                                            value="{{ old('kode_box', $product->kode_box ?? '') }}"
                                            placeholder="Cth: K-21">
                                    </div>

                                    <div class="col-md-2">
                                        <label class="form-label small fw-bold text-muted">Safety Stock</label>
                                        <input type="number" name="safety_stock" class="form-control text-center"
                                            value="{{ old('safety_stock', $product->safety_stock ?? 0) }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small fw-bold text-muted">UOM</label>
                                        <input type="text" name="uom" class="form-control text-center bg-light"
                                            value="{{ old('uom', $product->uom ?? 'PCS') }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ROUTING TABLE --}}
                        <div class="card shadow-sm border-0 rounded-4">
                            <div
                                class="card-header bg-white py-3 border-bottom-0 d-flex justify-content-between align-items-center px-4">
                                <h6 class="fw-bold text-dark mb-0"><i
                                        class="fas fa-network-wired me-2 text-success"></i>Routing Proses</h6>
                                <button type="button" class="btn btn-sm btn-dark rounded-pill px-3 shadow-sm"
                                    onclick="addRoutingRow()">
                                    <i class="fas fa-plus me-1"></i> Tambah Proses
                                </button>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                                        <thead class="bg-light text-secondary">
                                            <tr class="text-uppercase small">
                                                <th class="ps-4" width="5%">#</th>
                                                <th width="15%">Nama Proses</th>
                                                <th width="15%">Plant</th>

                                                <th width="20%">Line</th>

                                                <th width="20%">Mesin</th>
                                                <th width="10%" class="text-center">Cap/Jam</th>
                                                <th width="10%" class="text-center">Rasio</th>
                                                <th width="5%" class="text-center"><i class="fas fa-cog"></i></th>
                                            </tr>
                                        </thead>
                                        <tbody id="routingBody">
                                            @if(isset($product) && $product->routings->count() > 0)
                                                @foreach($product->routings as $index => $route)
                                                    <tr>
                                                        <td class="text-center row-index ps-4 text-muted fw-bold">{{ $index + 1 }}
                                                        </td>
                                                        <td>
                                                            <input type="hidden" name="routings[{{ $index }}][id]" value="{{ $route->id }}">
                                                            <input type="text" name="routings[{{ $index }}][process_name]"
                                                                class="form-control form-control-sm border-0 bg-transparent fw-bold"
                                                                value="{{ $route->process_name }}" required>
                                                        </td>

                                                        {{-- 1. PLANT --}}
                                                        <td>
                                                            <select name="routings[{{ $index }}][plant]"
                                                                class="form-select form-select-sm plant-select border-0 bg-light"
                                                                onchange="filterLines(this)" data-selected="{{ $route->plant }}"
                                                                required>
                                                                <option value="">- Pilih Plant -</option>
                                                                @foreach($plants as $p)
                                                                    <option value="{{ $p }}" {{ $route->plant == $p ? 'selected' : '' }}>
                                                                        {{ $p }}</option>
                                                                @endforeach
                                                            </select>
                                                        </td>

                                                        {{-- 2. LINE --}}
                                                        <td>
                                                            <select name="routings[{{ $index }}][production_line_id]"
                                                                class="form-select form-select-sm line-select border-0 bg-light"
                                                                onchange="filterMachines(this)"
                                                                data-selected="{{ $route->production_line_id }}" required>
                                                                {{-- Opsi akan diisi otomatis oleh JS saat load --}}
                                                            </select>
                                                        </td>

                                                        {{-- 3. MESIN --}}
                                                        <td>
                                                            <select name="routings[{{ $index }}][machine_id]"
                                                                class="form-select form-select-sm machine-select border-0 bg-light"
                                                                data-selected="{{ $route->machine_id }}" required>
                                                                {{-- Opsi akan diisi otomatis oleh JS saat load --}}
                                                            </select>
                                                        </td>

                                                        <td><input type="number" name="routings[{{ $index }}][pcs_per_hour]"
                                                                class="form-control form-control-sm text-center border-0 bg-light text-primary fw-bold"
                                                                value="{{ $route->pcs_per_hour }}" required></td>
                                                        <td><input type="number" step="0.1"
                                                                name="routings[{{ $index }}][manpower_ratio]"
                                                                class="form-control form-control-sm text-center border-0 bg-light text-success fw-bold"
                                                                value="{{ $route->manpower_ratio }}" required></td>
                                                        <td class="text-center"><button type="button"
                                                                class="btn btn-link text-danger p-0" onclick="removeRow(this)"><i
                                                                    class="fas fa-times-circle"></i></button></td>
                                                    </tr>
                                                @endforeach
                                            @endif
                                        </tbody>
                                    </table>

                                    <div id="empty-state"
                                        class="text-center py-5 {{ (isset($product) && $product->routings->count() > 0) ? 'd-none' : '' }}">
                                        <div class="text-muted opacity-25 mb-2"><i class="fas fa-layer-group fa-3x"></i>
                                        </div>
                                        <p class="text-muted small mb-0">Belum ada proses routing.</p>
                                    </div>

                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- CSS KHUSUS --}}
    <style>
        .cursor-pointer {
            cursor: pointer;
        }

        .form-control:focus,
        .form-select:focus {
            box-shadow: none;
            border-color: #0d6efd;
        }

        .custom-radio-card input:checked+label {
            border-color: #0d6efd !important;
            background-color: #eff6ff;
            color: #0d6efd;
        }
    </style>

    <script>
        // 1. SIAPKAN DATA MASTER DARI CONTROLLER KE JS
        const masterPlants = @json($plants);
        const masterLines = @json($lines);
        const masterMachines = @json($machines);

        let rowIndex = {{ isset($product) ? $product->routings->count() : 0 }};

        // Saat Halaman Load (Khusus Edit Mode)
        document.addEventListener("DOMContentLoaded", function () {
            // Loop semua baris yang sudah ada (dari database)
            document.querySelectorAll('#routingBody tr').forEach(row => {
                const plantSelect = row.querySelector('.plant-select');
                const lineSelect = row.querySelector('.line-select');
                const machineSelect = row.querySelector('.machine-select');

                const selectedPlant = plantSelect.getAttribute('data-selected');
                const selectedLine = lineSelect.getAttribute('data-selected');
                const selectedMachine = machineSelect.getAttribute('data-selected');

                // Set nilai awal dan jalankan filter berantai
                if (selectedPlant) {
                    plantSelect.value = selectedPlant;
                    filterLines(plantSelect, selectedLine); // Pass selectedLine agar tidak hilang
                }

                if (selectedLine) {
                    // lineSelect.value sudah diset di dalam filterLines, 
                    // sekarang kita filter mesinnya
                    filterMachines(lineSelect, selectedMachine);
                }
            });
        });

        // 2. FUNGSI TAMBAH BARIS BARU
        function addRoutingRow() {
            document.getElementById('empty-state').classList.add('d-none');

            let html = `
                <tr>
                    <td class="text-center row-index ps-4 text-muted fw-bold">${rowIndex + 1}</td>
                    <td>
                        <input type="text" name="routings[${rowIndex}][process_name]" class="form-control form-control-sm border-0 bg-transparent fw-bold" placeholder="Nama Proses" required>
                    </td>

                    {{-- 1. DROPDOWN PLANT --}}
                    <td>
                        <select name="routings[${rowIndex}][plant]" class="form-select form-select-sm plant-select border-0 bg-light" onchange="filterLines(this)" required>
                            <option value="">- Pilih Plant -</option>
                            ${masterPlants.map(p => `<option value="${p}">${p}</option>`).join('')}
                        </select>
                    </td>

                    {{-- 2. DROPDOWN LINE (KOSONG AWALNYA) --}}
                    <td>
                        <select name="routings[${rowIndex}][production_line_id]" class="form-select form-select-sm line-select border-0 bg-light" onchange="filterMachines(this)" required disabled>
                            <option value="">- Pilih Plant Dulu -</option>
                        </select>
                    </td>

                    {{-- 3. DROPDOWN MESIN (KOSONG AWALNYA) --}}
                    <td>
                        <select name="routings[${rowIndex}][machine_id]" class="form-select form-select-sm machine-select border-0 bg-light" required disabled>
                            <option value="">- Pilih Line Dulu -</option>
                        </select>
                    </td>

                    <td><input type="number" name="routings[${rowIndex}][pcs_per_hour]" class="form-control form-control-sm text-center border-0 bg-light text-primary fw-bold" placeholder="0" required></td>
                    <td><input type="number" step="0.1" name="routings[${rowIndex}][manpower_ratio]" class="form-control form-control-sm text-center border-0 bg-light text-success fw-bold" value="1" required></td>
                    <td class="text-center"><button type="button" class="btn btn-link text-danger p-0" onclick="removeRow(this)"><i class="fas fa-times-circle"></i></button></td>
                </tr>
            `;
            document.getElementById('routingBody').insertAdjacentHTML('beforeend', html);
            rowIndex++;
        }

        // 3. FUNGSI FILTER LINE (Dipanggil saat Plant Berubah)
        function filterLines(plantSelect, preSelectedValue = null) {
            let row = plantSelect.closest('tr');
            let lineSelect = row.querySelector('.line-select');
            let machineSelect = row.querySelector('.machine-select');
            let selectedPlant = plantSelect.value;

            // Reset Line & Machine
            lineSelect.innerHTML = '<option value="">- Pilih Line -</option>';
            machineSelect.innerHTML = '<option value="">- Pilih Line Dulu -</option>';
            machineSelect.disabled = true;

            if (selectedPlant === "") {
                lineSelect.disabled = true;
                return;
            }

            // Filter Data Line dari Master JS
            let filteredLines = masterLines.filter(line => line.plant == selectedPlant);

            // Isi Dropdown Line
            filteredLines.forEach(line => {
                let isSelected = (preSelectedValue && preSelectedValue == line.id) ? 'selected' : '';
                lineSelect.innerHTML += `<option value="${line.id}" ${isSelected}>${line.name}</option>`;
            });

            lineSelect.disabled = false;
        }

        // 4. FUNGSI FILTER MESIN (Dipanggil saat Line Berubah)
        function filterMachines(lineSelect, preSelectedValue = null) {
            let row = lineSelect.closest('tr');
            let machineSelect = row.querySelector('.machine-select');
            let selectedLineID = lineSelect.value;

            // Reset Machine
            machineSelect.innerHTML = '<option value="">- Pilih Mesin -</option>';

            if (selectedLineID === "") {
                machineSelect.disabled = true;
                return;
            }

            // Filter Data Mesin dari Master JS
            // (Pastikan machine.production_line_id cocok dengan selectedLineID)
            let filteredMachines = masterMachines.filter(m => m.production_line_id == selectedLineID);

            // Isi Dropdown Mesin
            filteredMachines.forEach(m => {
                let isSelected = (preSelectedValue && preSelectedValue == m.id) ? 'selected' : '';
                machineSelect.innerHTML += `<option value="${m.id}" ${isSelected}>${m.name}</option>`;
            });

            machineSelect.disabled = false;
        }

        // Fungsi Hapus Baris
        function removeRow(btn) {
            btn.closest('tr').remove();
            updateIndexes();
            if (document.getElementById('routingBody').children.length === 0) {
                document.getElementById('empty-state').classList.remove('d-none');
            }
        }

        function updateIndexes() {
            document.querySelectorAll('.row-index').forEach((el, i) => { el.innerText = i + 1; });
        }

        // Preview Image
        function previewImage(event) {
            const reader = new FileReader();
            reader.onload = function () { document.getElementById('photoPreview').src = reader.result; };
            reader.readAsDataURL(event.target.files[0]);
        }
    </script>
@endsection