@extends('layouts.app_simple')

@section('content')
    <style>
        /* Styling Tambahan */
        .table-responsive {
            max-height: 80vh;
            overflow-y: auto;
            border: 1px solid #dee2e6;
        }

        thead th {
            position: sticky;
            top: 0;
            z-index: 10;
            box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.4);
        }

        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        input[type=number] {
            -moz-appearance: textfield;
        }

        .form-control-xs {
            height: calc(1.5em + 0.25rem + 2px);
            padding: 0.125rem 0.25rem;
            font-size: 0.75rem;
            border-radius: 0.2rem;
            border: 1px solid #ced4da;
        }

        .font-mono {
            font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, Courier, monospace;
            letter-spacing: -0.5px;
        }

        .border-right-thick {
            border-right: 2px solid #6c757d !important;
        }

        .bg-header-primary {
            background-color: #4e73df;
            color: white;
        }
    </style>

    <div class="container-fluid py-3">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="text-gray-800 fw-bold"><i class="fas fa-calculator me-2"></i>Kalkulasi Kanban Produksi</h4>
            <div>
                @if(session('success'))
                    <span class="badge bg-success py-2 px-3"><i class="fas fa-check me-1"></i> {{ session('success') }}</span>
                @endif
                @if(session('error'))
                    <span class="badge bg-danger py-2 px-3"><i class="fas fa-exclamation-triangle me-1"></i>
                        {{ session('error') }}</span>
                @endif
            </div>
        </div>

        {{-- =================================== --}}
        {{-- [BARU] SEARCH & FILTER TOOLBAR --}}
        {{-- =================================== --}}
        <div class="card shadow-sm mb-3 border-0">
            <div class="card-body py-3 bg-light rounded">
                <div class="row g-2 align-items-center">

                    {{-- 1. SEARCH INPUT --}}
                    <div class="col-md-3">
                        <label class="small fw-bold text-muted mb-1">Cari Part</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0"><i
                                    class="fas fa-search text-muted"></i></span>
                            <input type="text" id="searchInput" class="form-control border-start-0"
                                placeholder="Kode / Nama Part...">
                        </div>
                    </div>

                    {{-- 2. FILTER LINE --}}
                    <div class="col-md-2">
                        <label class="small fw-bold text-muted mb-1">Filter Line</label>
                        <select id="filterLine" class="form-select form-select-sm">
                            <option value="">- Semua Line -</option>
                            @if(isset($filterLines))
                                @foreach($filterLines as $line)
                                    <option value="{{ $line }}">{{ $line }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    {{-- 3. FILTER TIPE --}}
                    <div class="col-md-2">
                        <label class="small fw-bold text-muted mb-1">Filter Tipe</label>
                        <select id="filterType" class="form-select form-select-sm">
                            <option value="">- Semua Tipe -</option>
                            <option value="FG">FINISH GOODS</option>
                            <option value="SUB">SUBCONT</option>
                            <option value="PROD">PRODUCTION</option>
                        </select>
                    </div>

                    {{-- 4. FILTER CUSTOMER --}}
                    <div class="col-md-2">
                        <label class="small fw-bold text-muted mb-1">Filter Customer</label>
                        <select id="filterCust" class="form-select form-select-sm">
                            <option value="">- Semua Cust -</option>
                            @if(isset($filterCustomers))
                                @foreach($filterCustomers as $cust)
                                    <option value="{{ $cust }}">{{ $cust }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    {{-- 5. RESET BUTTON --}}
                    <div class="col-md-3 text-end pt-3">
                        <button type="button" class="btn btn-outline-secondary btn-sm w-100" id="btnResetFilter">
                            <i class="fas fa-times me-1"></i> Reset Filter
                        </button>
                    </div>

                </div>
            </div>
        </div>

        {{-- INFO HASIL FILTER --}}
        <div id="filterInfo" class="small text-muted mb-2 fst-italic d-none">
            Menampilkan <span id="visibleCount" class="fw-bold text-dark">0</span> data dari total <span
                class="fw-bold">{{ count($kanbanData) }}</span> part.
        </div>
        {{-- =================================== --}}


        <form action="{{ route('kanban.save_inputs') }}" method="POST">
            @csrf

            <div class="card shadow-sm mb-3 border-left-primary">
                <div class="card-body py-2">
                    <div class="row align-items-center">
                        <div class="col-md-2">
                            <label class="small fw-bold text-uppercase text-muted mb-0">Hari Kerja</label>
                            <div class="input-group input-group-sm mt-1">
                                <span class="input-group-text bg-light"><i class="fas fa-calendar-day"></i></span>
                                <input type="number" class="form-control fw-bold text-center" value="{{ $workDays }}"
                                    readonly>
                            </div>
                        </div>

                        <div class="col-md-10 text-end">
                            <div class="btn-group me-2 shadow-sm" role="group">
                                <a href="{{ route('kanban.download_template') }}" class="btn btn-secondary btn-sm"
                                    title="Download Template Excel/CSV">
                                    <i class="fas fa-download me-1"></i> Template
                                </a>
                                <button type="button" class="btn btn-info btn-sm text-white" data-bs-toggle="modal"
                                    data-bs-target="#uploadModal" title="Upload Data by Code Part">
                                    <i class="fas fa-upload me-1"></i> Upload
                                </button>
                            </div>

                            <a href="{{ route('kanban.index') }}" class="btn btn-outline-primary btn-sm me-2 shadow-sm">
                                <i class="fas fa-sync-alt me-1"></i> Hitung Ulang
                            </a>
                            <button type="submit" class="btn btn-success btn-sm shadow-sm px-3 fw-bold">
                                <i class="fas fa-save me-1"></i> SIMPAN DATA
                            </button>
                        </div>
                    </div>
                </div>
            </div>




            <div class="card shadow mb-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                       <table class="table table-bordered table-striped table-hover table-sm mb-0"
                            style="font-size: 11px; white-space: nowrap;">
                            <thead class="text-center align-middle small">
                                <tr>
                                    <th rowspan="2" class="bg-primary text-white">NO</th>
                                    <th rowspan="2" class="bg-primary text-white">KODE PART</th>
                                    <th rowspan="2" class="bg-primary text-white">LINE</th>
                                    <th rowspan="2" class="bg-primary text-white" style="min-width: 150px;">NAMA PART</th>
                                    <th rowspan="2" class="bg-primary text-white border-right-thick">CUST</th>
                                    <th rowspan="2" class="bg-light text-dark">TOTAL<br>ORDER</th>
                                    
                                    {{-- [REVISI] DAILY ORDER JADI 2 KOLOM (PCS & KANBAN) --}}
                                    <th colspan="2" class="bg-light text-dark">DAILY ORDER</th>
                                    
                                    <th rowspan="2" class="bg-warning text-dark">PCS / KANBAN<br>CUST</th>
                                    <th rowspan="2" class="bg-primary text-white">ROUND<br>UP</th>
                                    <th rowspan="2" class="bg-light text-dark">QTY /<br>KANBAN CNK</th>
                                    <th rowspan="2" class="bg-info text-dark border-right-thick">KODE<br>BOX</th>
                                    <th colspan="2" class="bg-secondary text-white">LOT SIZE</th>
                                    <th rowspan="2" class="bg-secondary text-white border-right-thick">MAT.<br>REMARKS</th>
                                    <th rowspan="2" class="bg-dark text-white">CYCLE<br>TIME</th>
                                    <th rowspan="2" class="bg-light text-dark">OUTPUT<br>/ JAM</th>
                                    <th rowspan="2" class="bg-light text-dark">TAKT<br>TIME</th>
                                    <th rowspan="2" class="bg-light text-dark border-right-thick">LOAD<br>TIME</th>
                                    <th colspan="13" class="bg-success text-white border-right-thick">LEAD TIME PARAMETER (DETIK)</th>
                                    <th colspan="3" class="bg-warning text-dark">ANALISA KANBAN</th>
                                </tr>
                                <tr>
                                    {{-- [BARU] Sub Header Daily Order --}}
                                    <th class="bg-white text-dark" style="width: 50px;">PCS</th>
                                    <th class="bg-white text-dark" style="width: 50px;">KANBAN</th>

                                    {{-- Sub Header PCS / KANBAN CUST --}}
                                    <th class="bg-white text-dark" style="width: 60px;">PCS</th>
                                    
                                    {{-- Sub Header Lead Time Parameter --}}
                                    <th class="bg-white text-dark" style="width: 60px;">PCS</th> {{-- Kolom Lot Size PCS --}}

                                    <th class="bg-white text-dark">1<br>PULL</th>
                                    <th class="bg-white text-dark">STORE<br>INCOMING</th>
                                    <th class="bg-white text-dark">LINE<br>STORE</th>
                                    <th class="bg-white text-dark">COLLECTING<br>POST</th>
                                    <th class="bg-white text-dark">LOT<br>MAKING</th>
                                    <th class="bg-white text-dark">KANBAN<br>POST</th>
                                    <th class="bg-white text-dark">CHUTE</th>
                                    <th class="bg-white text-dark">PROSES</th>
                                    <th class="bg-white text-dark">OUTGOING</th>
                                    <th class="bg-white text-dark">SUBCONT</th>
                                    <th class="bg-white text-dark">INCOMING</th>
                                    <th class="bg-white text-dark">CONVEYANCE</th>
                                    <th class="bg-white text-dark">FLUKTUASI</th>
                                    <th class="bg-warning text-dark fw-bold border-right-thick">TOTAL<br>LEAD TIME</th>
                                    
                                    {{-- Sub Header Analisa --}}
                                    <th class="bg-white text-dark">TARGET</th>
                                    <th class="bg-white text-dark">CURRENT</th>
                                    <th class="bg-white text-dark">GAP</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($kanbanData as $index => $row)
                                    <tr class="item-row" data-code="{{ strtolower($row->code_part) }}">
                                        <td class="text-center align-middle">{{ $index + 1 }}</td>
                                        <td class="fw-bold align-middle">{{ $row->code_part }}</td>
                                        <td class="text-center text-primary fw-bold align-middle" style="font-size:10px;">{{ $row->line }}</td>
                                        <td class="align-middle text-truncate" style="max-width: 150px;" title="{{ $row->part_name }}">{{ $row->part_name }}</td>
                                        <td class="text-center align-middle border-right-thick">{{ $row->customer }}</td>
                                        <td class="text-end font-mono align-middle">{{ number_format($row->total_order) }}</td>
                                        
                                        {{-- [REVISI] Pecah Kolom Daily Order --}}
                                        {{-- 1. Daily Order PCS --}}
                                        <td class="text-end font-mono fw-bold align-middle bg-light">
                                            {{ number_format($row->daily_order) }}
                                        </td>
                                        {{-- 2. Daily Kanban (Calculated) --}}
                                        <td class="text-center font-mono fw-bold align-middle bg-light text-primary">
                                            {{ number_format($row->daily_kanban) }}
                                        </td>

                                        {{-- Kolom Input PCS KANBAN CUST --}}
                                        <td class="p-1" style="width: 80px;">
                                            <input type="number" 
                                                name="inputs[{{ $row->code_part }}][pcs_kanban_cust]" 
                                                value="{{ $row->pcs_kanban_cust }}" 
                                                class="form-control form-control-sm text-center fw-bold bg-white"
                                                style="font-size: 11px; border: 1px solid #ced4da;"
                                                placeholder="0" onfocus="this.select()">
                                        </td>

                                        <td class="text-center font-mono fw-bold align-middle bg-primary text-white">
                                            {{ number_format($row->round_up) }}
                                        </td>
                                        
                                        {{-- Sisa Kolom --}}
                                        <td class="text-center font-mono align-middle fw-bold">{{ number_format($row->qty_per_box) }}</td>
                                        <td class="text-center fw-bold text-danger bg-light border-right-thick align-middle">{{ $row->kode_box }}</td>
                                        
                                        {{-- ... Sisa kolom ke bawah sama seperti sebelumnya ... --}}
                                        
                                        {{-- Contoh singkat kolom selanjutnya agar tidak error copy paste --}}
                                        <td class="p-1 align-middle">
                                            <input type="number" name="inputs[{{ $row->code_part }}][lot_size_pcs]" class="form-control form-control-xs text-end input-pcs font-mono" data-qtybox="{{ $row->qty_per_box }}" value="{{ $row->lot_size_pcs }}" onfocus="this.select()">
                                        </td>
                                        <td class="text-center fw-bold bg-light span-kanban font-mono align-middle">{{ number_format($row->lot_size_kanban) }}</td>
                                        <td class="text-center bg-light fw-bold" style="color: #2c3e50;">{{ $row->material_remarks > 0 ? (float) number_format($row->material_remarks, 5) : '-' }}</td>
                                        
                                        <td class="text-center font-mono align-middle">{{ $row->cycle_time }}</td>
                                        <td class="text-center font-mono fw-bold bg-warning text-dark align-middle">{{ number_format($row->output_per_hour, 0) }}</td>
                                        <td class="text-center font-mono fw-bold bg-warning text-dark align-middle">{{ number_format($row->takt_time, 0) }}</td>
                                        <td class="text-center font-mono fw-bold bg-warning text-dark border-right-thick align-middle">{{ number_format($row->calc_load_time, 1) }}</td>
                                        <td class="text-center font-mono fw-bold bg-warning text-dark align-middle">
                                            {{ number_format($row->one_pull, 0) }}
                                        </td>
                                        {{-- [BARU] KOLOM STORE INCOMING --}}
                                        <td class="text-center font-mono fw-bold bg-warning text-dark align-middle">
                                            {{ number_format($row->store_incoming, 0) }}
                                        </td>
                                        <td class="text-center font-mono fw-bold bg-warning text-dark align-middle">{{ number_format($row->line_store, 0) }}</td>
                                        <td class="p-1 align-middle"><input type="number" name="inputs[{{ $row->code_part }}][collecting_post]" class="form-control form-control-xs text-center input-collecting font-mono" value="{{ $row->collecting_post }}" placeholder="0" onfocus="this.select()"></td>
                                        <td class="text-center font-mono fw-bold bg-warning text-dark align-middle">{{ number_format($row->lot_making, 0) }}</td>
                                        <td class="p-1 align-middle"><input type="number" name="inputs[{{ $row->code_part }}][kanban_post]" class="form-control form-control-xs text-center input-collecting font-mono" value="{{ $row->kanban_post }}" placeholder="0" onfocus="this.select()"></td>
                                        <td class="text-center font-mono fw-bold bg-warning text-dark align-middle">{{ number_format($row->chute, 0) }}</td>
                                        <td class="text-center font-mono fw-bold bg-warning text-dark align-middle">{{ number_format($row->proses, 1) }}</td>
                                        {{-- [BARU] KOLOM INPUT OUTGOING --}}
                                        <td class="p-1 align-middle">
                                            <input type="number" 
                                                name="inputs[{{ $row->code_part }}][outgoing]" 
                                                value="{{ $row->outgoing }}" 
                                                class="form-control form-control-sm text-center font-mono" 
                                                style="font-size: 11px;"
                                                min="0" onfocus="this.select()">
                                        </td>
                                        {{-- [BARU] KOLOM INPUT SUBCONT --}}
                                        <td class="p-1 align-middle">
                                            <input type="number" 
                                                name="inputs[{{ $row->code_part }}][subcont]" 
                                                value="{{ $row->subcont }}" 
                                                class="form-control form-control-sm text-center font-mono" 
                                                style="font-size: 11px;"
                                                min="0" onfocus="this.select()">
                                        </td>

                                        {{-- [BARU] KOLOM INPUT INCOMING --}}
                                        <td class="p-1 align-middle">
                                            <input type="number" 
                                                name="inputs[{{ $row->code_part }}][incoming]" 
                                                value="{{ $row->incoming }}" 
                                                class="form-control form-control-sm text-center font-mono" 
                                                style="font-size: 11px;"
                                                min="0" onfocus="this.select()">
                                        </td>
                                        <td class="p-1 align-middle"><input type="number" name="inputs[{{ $row->code_part }}][conveyance]" value="{{ $row->conveyance }}" class="form-control form-control-sm text-center font-mono" style="font-size: 11px;" min="0" onfocus="this.select()"></td>
                                        <td class="p-1 align-middle">
                                            <div class="input-group input-group-sm">
                                                <input type="number" name="inputs[{{ $row->code_part }}][fluctuation]" class="form-control form-control-xs text-center px-1 input-collecting font-mono" value="{{ $row->fluctuation_pct }}" placeholder="0" min="0" max="100" style="border-top-right-radius: 0; border-bottom-right-radius: 0;" onfocus="this.select()">
                                                <span class="input-group-text px-1 bg-light" style="font-size: 9px; border-top-left-radius: 0; border-bottom-left-radius: 0;">%</span>
                                            </div>
                                        </td>
                                        <td class="text-center font-mono fw-bold bg-warning text-dark border-right-thick align-middle" style="font-size: 12px;">{{ number_format($row->grand_total_lead_time, 0) }}</td>
                                        
                                        <td class="text-center font-mono fw-bold bg-warning kanban-target align-middle" style="font-size: 13px;">{{ number_format($row->kanban_target, 0) }}</td>
                                        <td class="p-1 align-middle"><input type="number" name="inputs[{{ $row->code_part }}][kanban_aktif]" class="form-control form-control-xs text-center input-kanban-aktif font-mono fw-bold" value="{{ $row->kanban_aktif }}" placeholder="0" onfocus="this.select()"></td>
                                        @php $bgGap = $row->gap > 0 ? 'bg-danger text-white' : ($row->gap < 0 ? 'bg-success text-white' : 'bg-light'); @endphp
                                        <td class="text-center font-mono fw-bold kanban-gap {{ $bgGap }} align-middle" style="font-size: 13px;">{{ $row->gap > 0 ? '+' : '' }}{{ $row->gap }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </form>

        {{-- FORM MODAL UPLOAD HARUS DIPISAH DARI FORM UTAMA --}}
        <div class="modal fade" id="uploadModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title small fw-bold"><i class="fas fa-file-upload me-2"></i>Upload Data by Code
                            Part</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('kanban.upload_data') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-body">
                            <div class="alert alert-warning small p-2 mb-3">
                                <i class="fas fa-info-circle me-1"></i>
                                Pastikan menggunakan file <b>.csv</b> dari hasil download template. <br>
                                Sistem akan mengupdate data berdasarkan <b>CODE PART</b>.
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Pilih File CSV</label>
                                <input class="form-control" type="file" name="csv_file" required accept=".csv">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary btn-sm">Upload & Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Upload --}}
    <div class="modal fade" id="uploadModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title small fw-bold"><i class="fas fa-file-upload me-2"></i>Upload Data by Code Part
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                {{-- Form Upload perlu dipisah jika ingin submit beda route,
                tapi karena tombolnya button type="button" yang men-trigger modal,
                form upload harus punya tag <form> sendiri di dalam modal ini --}}
            </div>
            {{-- Note: Form Upload sebaiknya dipisah dari Form Tabel Utama agar tidak bentrok --}}
        </div>
    </div>
    {{-- End Modal --}}

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            // --- 1. SEARCH & FILTER LOGIC (ROBUST DENGAN DATA ATTRIBUTES) ---
            const searchInput = document.getElementById('searchInput');
            const filterLine = document.getElementById('filterLine');
            const filterType = document.getElementById('filterType');
            const filterCust = document.getElementById('filterCust');
            const btnReset = document.getElementById('btnResetFilter');
            const visibleCountSpan = document.getElementById('visibleCount');
            const filterInfo = document.getElementById('filterInfo');
            const tableRows = document.querySelectorAll('tr.item-row'); // Seleksi Baris yang punya class item-row

            function filterTable() {
                const term = searchInput.value.toLowerCase();
                const selLine = filterLine.value.toLowerCase();
                const selType = filterType.value; // FG, SUB, PROD
                const selCust = filterCust.value.toLowerCase();

                let visibleRows = 0;

                tableRows.forEach(row => {
                    // AMBIL DATA DARI ATTRIBUTE (Lebih aman daripada urutan kolom)
                    const dataCode = row.getAttribute('data-code') || '';
                    const dataName = row.getAttribute('data-name') || '';
                    const dataLine = row.getAttribute('data-line') || '';
                    const dataCust = row.getAttribute('data-cust') || '';
                    const dataTypeRaw = row.getAttribute('data-type') || 'PRODUCTION';

                    // Mapping Tipe agar cocok dengan filter
                    let typeCode = 'PROD';
                    if (dataTypeRaw === 'FINISH_GOODS' || dataTypeRaw === 'FG') typeCode = 'FG';
                    else if (dataTypeRaw === 'SUBCONT' || dataTypeRaw === 'SUB') typeCode = 'SUB';
                    else typeCode = 'PROD';

                    // Logic Match
                    const matchSearch = dataCode.includes(term) || dataName.includes(term);
                    const matchLine = selLine === '' || dataLine.includes(selLine);
                    const matchType = selType === '' || typeCode === selType;
                    const matchCust = selCust === '' || dataCust.includes(selCust);

                    if (matchSearch && matchLine && matchType && matchCust) {
                        row.style.display = '';
                        visibleRows++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                if (visibleCountSpan) visibleCountSpan.innerText = visibleRows;
                if (filterInfo) filterInfo.classList.remove('d-none');
            }

            // Event Listeners
            if (searchInput) searchInput.addEventListener('keyup', filterTable);
            if (filterLine) filterLine.addEventListener('change', filterTable);
            if (filterType) filterType.addEventListener('change', filterTable);
            if (filterCust) filterCust.addEventListener('change', filterTable);

            if (btnReset) {
                btnReset.addEventListener('click', function () {
                    searchInput.value = '';
                    filterLine.value = '';
                    filterType.value = '';
                    filterCust.value = '';
                    filterTable();
                    filterInfo.classList.add('d-none');
                });
            }

            // --- 2. LOGIC HELPER LAINNYA (Format Angka, Lot Size, Gap) ---
            const formatNumber = (num) => {
                return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
            }

            // Logic Lot Size
            const inputsPcs = document.querySelectorAll('.input-pcs');
            inputsPcs.forEach(input => {
                input.addEventListener('input', function () {
                    const pcs = parseFloat(this.value) || 0;
                    const qtyBox = parseFloat(this.getAttribute('data-qtybox')) || 1;
                    const hasilKanban = Math.ceil(pcs / qtyBox);
                    const tr = this.closest('tr');
                    const tdKanban = tr.querySelector('.span-kanban');
                    if (tdKanban) tdKanban.innerText = formatNumber(hasilKanban);
                });
            });

            // Logic Gap
            const inputAktifs = document.querySelectorAll('.input-kanban-aktif');
            inputAktifs.forEach(input => {
                input.addEventListener('input', function () {
                    const tr = this.closest('tr');
                    const tdTarget = tr.querySelector('.kanban-target');
                    const tdGap = tr.querySelector('.kanban-gap');
                    const targetVal = parseFloat(tdTarget.innerText.replace(/,/g, '')) || 0;
                    const currentVal = parseFloat(this.value) || 0;
                    const gap = targetVal - currentVal;

                    tdGap.innerText = (gap > 0 ? '+' : '') + gap;
                    tdGap.className = 'text-center font-mono fw-bold kanban-gap align-middle';
                    if (gap > 0) tdGap.classList.add('bg-danger', 'text-white');
                    else if (gap < 0) tdGap.classList.add('bg-success', 'text-white');
                    else tdGap.classList.add('bg-light');
                });
            });
        });
    </script>
@endsection