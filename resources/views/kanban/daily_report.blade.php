@extends('layouts.app_simple')

@section('content')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <div class="container-fluid">
        {{-- HEADER & TOOLBAR --}}
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 d-print-none gap-3">
            <h4 class="fw-bold mb-0">Laporan Produktifitas Harian</h4>

            <div class="d-flex flex-wrap gap-2 align-items-center">
                <form action="{{ route('kanban.daily_report') }}" method="GET" class="d-flex gap-2 align-items-center">
                    <select name="plant" class="form-select form-select-sm fw-bold border-secondary" style="width: 130px;"
                        onchange="this.form.submit()">
                        <option value="ALL">Semua Plant</option>
                        @foreach($plants as $p)
                            <option value="{{ $p }}" {{ $selectedPlant == $p ? 'selected' : '' }}>{{ $p }}</option>
                        @endforeach
                    </select>

                    <select name="line_id" class="form-select form-select-sm fw-bold border-secondary" style="width: 150px;"
                        onchange="this.form.submit()">
                        <option value="">- Semua Line -</option>
                        @foreach($lines as $l)
                            <option value="{{ $l->id }}" {{ $selectedLineId == $l->id ? 'selected' : '' }}>{{ $l->name }}</option>
                        @endforeach
                    </select>

                    <input type="date" name="date" class="form-control form-control-sm fw-bold border-secondary"
                        value="{{ $date }}" onchange="this.form.submit()">
                </form>

                <div class="vr h-100 mx-1"></div>

                <button onclick="window.print()" class="btn btn-dark btn-sm fw-bold">
                    <i class="fas fa-print me-1"></i> CETAK
                </button>
            </div>
        </div>

        {{-- JUDUL PRINT --}}
        <div class="d-none d-print-block text-center mb-2">
            <h5 class="fw-bold mb-0">LAPORAN PRODUKTIFITAS HARIAN</h5>
            <p class="mb-0" style="font-size: 10px;">
                Tanggal: {{ \Carbon\Carbon::parse($date)->isoFormat('dddd, D MMMM Y') }} |
                Plant: {{ $selectedPlant }}
            </p>
        </div>

        <form action="{{ route('kanban.store_daily_report') }}" method="POST">
        @csrf
        <input type="hidden" name="date" value="{{ $date }}">

        <div class="card shadow-sm border-0 print-border-none">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm text-center align-middle mb-0" style="font-size: 0.7rem;">
                        {{-- HEADER TABEL --}}
                        <thead class="bg-light text-secondary fw-bold">
                            <tr>
                                <th rowspan="2" width="20">NO</th>
                                <th rowspan="2" style="width: 80px;">KODE PART</th>
                                <th rowspan="2" class="text-start" style="width: 120px;">NAMA PART</th>
                                <th rowspan="2" width="40" class="bg-warning bg-opacity-10 print-bg-none">DELAY<br>(H-1)</th>
                                <th rowspan="2" width="40">TARGET</th>
                                
                                {{-- HEADER SHIFT 1 --}}
                                <th colspan="4" class="bg-primary bg-opacity-10 text-primary print-bg-none print-text-black">
                                    SHIFT 1
                                </th>
                                
                                {{-- HEADER SHIFT 2 --}}
                                <th colspan="4" class="bg-info bg-opacity-10 text-info print-bg-none print-text-black">
                                    SHIFT 2
                                </th>
                                
                                <th rowspan="2" width="40" class="bg-success bg-opacity-25 text-success border-start border-success print-bg-none print-text-black">
                                    TOTAL<br>(OK)
                                </th>
                                <th rowspan="2" width="80">KET (NG)</th>
                            </tr>
                            <tr>
                                {{-- Sub Header Shift 1 --}}
                                <th width="30">PIC</th>
                                <th width="40" class="text-primary">OK</th>
                                <th width="40" class="text-danger">NG</th> {{-- Kolom NG Baru --}}
                                <th width="50">LOT</th>

                                {{-- Sub Header Shift 2 --}}
                                <th width="30">PIC</th>
                                <th width="40" class="text-info">OK</th>
                                <th width="40" class="text-danger">NG</th> {{-- Kolom NG Baru --}}
                                <th width="50">LOT</th>
                            </tr>
                        </thead>

                        {{-- BODY TABEL --}}
                        <tbody>
                            @forelse($tableData as $index => $row)
                                @php 
                                    $r = $row->data_report; 
                                    $code = $row->product->code_part;
                                @endphp
                                
                                <tr class="align-middle input-row">
                                    <td>{{ $index + 1 }}</td>
                                    <td class="fw-bold" style="font-size: 0.65rem;">{{ $code }}</td>
                                    <td class="text-start" style="font-size: 0.6rem; line-height: 1.1;">
                                        {{ $row->product->part_name }}
                                    </td>

                                    {{-- DELAY INFO --}}
                                    <td class="bg-warning bg-opacity-10 print-bg-none fw-bold">
                                        <input type="hidden" name="reports[{{ $code }}][qty_delay]" value="{{ $row->qty_delay }}">
                                        @if($row->qty_delay < 0)
                                            <span class="text-danger print-text-black">{{ number_format($row->qty_delay) }}</span>
                                        @elseif($row->qty_delay > 0)
                                            <span class="text-primary print-text-black">+{{ number_format($row->qty_delay) }}</span>
                                        @else
                                            <span class="text-muted opacity-50">-</span>
                                        @endif
                                    </td>

                                    {{-- TARGET --}}
                                    <td>
                                        <input type="hidden" name="reports[{{ $code }}][qty_target]" value="{{ $row->qty_target }}">
                                        <strong>{{ $row->qty_target }}</strong>
                                    </td>

                                    {{-- INPUT SHIFT 1 --}}
                                    <td>
                                        <input type="text" name="reports[{{ $code }}][pic_shift_1]" 
                                            class="form-control form-control-sm border-0 bg-light text-center p-0" 
                                            value="{{ $r->pic_shift_1 ?? '' }}">
                                    </td>
                                    <td>
                                        <input type="number" name="reports[{{ $code }}][act_shift_1]" 
                                            class="form-control form-control-sm border-0 fw-bold text-center p-0 text-primary val-ok-1" 
                                            value="{{ $r->act_shift_1 ?? 0 }}">
                                    </td>
                                    <td class="bg-danger bg-opacity-10">
                                        {{-- INPUT NG SHIFT 1 --}}
                                        <input type="number" name="reports[{{ $code }}][ng_shift_1]" 
                                            class="form-control form-control-sm border-0 fw-bold text-center p-0 text-danger bg-transparent" 
                                            value="{{ $r->ng_shift_1 ?? 0 }}">
                                    </td>
                                    <td>
                                        <input type="text" name="reports[{{ $code }}][lot_shift_1]" 
                                            class="form-control form-control-sm border-0 text-center p-0" 
                                            value="{{ $r->lot_shift_1 ?? '' }}">
                                    </td>

                                    {{-- INPUT SHIFT 2 --}}
                                    <td>
                                        <input type="text" name="reports[{{ $code }}][pic_shift_2]" 
                                            class="form-control form-control-sm border-0 bg-light text-center p-0" 
                                            value="{{ $r->pic_shift_2 ?? '' }}">
                                    </td>
                                    <td>
                                        <input type="number" name="reports[{{ $code }}][act_shift_2]" 
                                            class="form-control form-control-sm border-0 fw-bold text-center p-0 text-info val-ok-2" 
                                            value="{{ $r->act_shift_2 ?? 0 }}">
                                    </td>
                                    <td class="bg-danger bg-opacity-10">
                                        {{-- INPUT NG SHIFT 2 --}}
                                        <input type="number" name="reports[{{ $code }}][ng_shift_2]" 
                                            class="form-control form-control-sm border-0 fw-bold text-center p-0 text-danger bg-transparent" 
                                            value="{{ $r->ng_shift_2 ?? 0 }}">
                                    </td>
                                    <td>
                                        <input type="text" name="reports[{{ $code }}][lot_shift_2]" 
                                            class="form-control form-control-sm border-0 text-center p-0" 
                                            value="{{ $r->lot_shift_2 ?? '' }}">
                                    </td>

                                    {{-- TOTAL (AUTO CALCULATED) --}}
                                    <td class="bg-success bg-opacity-10 fw-bold text-dark border-start border-success border-opacity-25">
                                        <span class="val-total">{{ ($r->act_shift_1 ?? 0) + ($r->act_shift_2 ?? 0) }}</span>
                                    </td>

                                    {{-- KETERANGAN / REMARKS --}}
                                    <td>
                                        <input type="text" name="reports[{{ $code }}][keterangan]" 
                                            class="form-control form-control-sm border-0 text-start px-1" 
                                            style="font-size: 0.6rem;"
                                            value="{{ $r->keterangan ?? '' }}" placeholder="...">
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="15" class="text-center py-5 text-muted">Tidak ada data produksi.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card-footer bg-white text-end d-print-none sticky-bottom">
                <button type="submit" class="btn btn-primary fw-bold shadow">
                    <i class="fas fa-save me-1"></i> SIMPAN LAPORAN
                </button>
            </div>

            {{-- FOOTER PRINT --}}
            <div class="d-none d-print-block mt-4">
                <div class="d-flex justify-content-between px-5 text-center" style="font-size: 10px;">
                    <div style="width: 25%;">
                        <p class="mb-5">Dibuat Oleh,</p>
                        <p class="fw-bold text-decoration-underline">( Operator )</p>
                    </div>
                    <div style="width: 25%;">
                        <p class="mb-5">Diperiksa Oleh,</p>
                        <p class="fw-bold text-decoration-underline">( Leader / Foreman )</p>
                    </div>
                    <div style="width: 25%;">
                        <p class="mb-5">Diketahui Oleh,</p>
                        <p class="fw-bold text-decoration-underline">( Supervisor )</p>
                    </div>
                </div>
            </div>
        </div>
    </form>


   

    {{-- STYLES --}}
    <style>
        @media print {
            @page {
                size: portrait;
                margin: 5mm;
            }

            body {
                background: white !important;
                font-family: Arial, Helvetica, sans-serif;
                color: #000;
            }

            nav,
            header,
            aside,
            .btn,
            .d-print-none {
                display: none !important;
            }

            .main,
            #main,
            .content-wrapper,
            .container-fluid {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }

            .card {
                border: none !important;
                box-shadow: none !important;
            }

            table {
                width: 100% !important;
                border-collapse: collapse !important;
                font-size: 9px !important;
            }

            table th,
            table td {
                border: 1px solid #000 !important;
                padding: 2px !important;
                color: #000 !important;
                vertical-align: middle !important;
            }

            input.print-input {
                border: none !important;
                background: transparent !important;
                text-align: center;
                width: 100%;
                color: #000 !important;
                font-size: 9px !important;
            }

            .bg-light,
            .bg-warning,
            .bg-success,
            .bg-primary,
            .bg-info {
                background-color: transparent !important;
            }

            .text-primary,
            .text-info,
            .text-success,
            .text-danger {
                color: #000 !important;
            }

            input::placeholder {
                color: transparent !important;
            }
        }

        /* Animasi Kedip untuk Alert */
        @keyframes blinker {
            50% {
                opacity: 0.7;
            }
        }

        .blink-bg {
            animation: blinker 1s linear infinite;
        }
    </style>

    {{-- SCRIPTS REVISI --}}
   <script>
        document.addEventListener("DOMContentLoaded", function () {
            const inputs = document.querySelectorAll('.calc-input');

            inputs.forEach(input => {
                input.addEventListener('input', function () {
                    const row = this.closest('tr');
                    
                    // 1. Ambil Nilai Input Actual
                    let val1 = parseFloat(row.querySelector('.act-s1').value) || 0;
                    let val2 = parseFloat(row.querySelector('.act-s2').value) || 0;
                    let currentTotal = val1 + val2;

                    // 2. Ambil Data Target & Delay
                    // (Target dan Delay bisa negatif atau 0, itu valid)
                    const target = parseFloat(row.getAttribute('data-target')) || 0;
                    const delay  = parseFloat(row.getAttribute('data-delay')) || 0;

                    // 3. Hitung Batas Maksimal (Target - Delay)
                    // Contoh 1: Target 0, Delay 0 => MaxNeed = 0. Jika input 1, ALERT.
                    // Contoh 2: Target 100, Delay -50 => MaxNeed = 150.
                    // Contoh 3: Target 100, Delay 20 (Surplus) => MaxNeed = 80.
                    const maxNeed = target - delay;
                    
                    const totalCell = row.querySelector('.total-cell');

                    // 4. LOGIKA VALIDASI (HAPUS SYARAT maxNeed > 0)
                    // Cek apakah Total melebihi Kebutuhan
                    // Kita berikan toleransi jika MaxNeed negatif (berarti surplus parah), set MaxNeed ke 0
                    const effectiveMax = Math.max(0, maxNeed); 

                    if (currentTotal > effectiveMax) {
                        
                        // A. TAMPILKAN ALERT
                        Swal.fire({
                            icon: 'error',
                            title: 'STOP! OVERPRODUCTION',
                            html: `
                                Total Output <b>(${currentTotal})</b> melebihi kebutuhan saat ini <b>(${effectiveMax})</b>.<br>
                                <small class="text-muted">Target: ${target}, Delay (H-1): ${delay}</small><br><br>
                                <b>Input akan dikembalikan ke 0.</b>
                            `,
                            confirmButtonText: 'OK, PAHAM',
                            confirmButtonColor: '#d33',
                            allowOutsideClick: false,
                            allowEscapeKey: false
                        });

                        // B. RESET INPUT JADI 0
                        this.value = 0;

                        // C. HITUNG ULANG TOTAL
                        val1 = parseFloat(row.querySelector('.act-s1').value) || 0;
                        val2 = parseFloat(row.querySelector('.act-s2').value) || 0;
                        const safeTotal = val1 + val2;

                        // D. KEMBALIKAN TAMPILAN KE NORMAL
                        totalCell.textContent = safeTotal;
                        totalCell.classList.remove('bg-danger', 'text-white', 'blink-bg');
                        totalCell.classList.add('bg-success', 'text-dark', 'bg-opacity-10');

                    } else {
                        // JIKA AMAN: Update Total
                        totalCell.textContent = currentTotal;
                        totalCell.classList.remove('bg-danger', 'text-white', 'blink-bg');
                        totalCell.classList.add('bg-success', 'text-dark', 'bg-opacity-10');
                    }
                });
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
        const rows = document.querySelectorAll('.input-row');

        rows.forEach(row => {
            const inputOk1 = row.querySelector('.val-ok-1');
            const inputOk2 = row.querySelector('.val-ok-2');
            const spanTotal = row.querySelector('.val-total');

            function calculateTotal() {
                const val1 = parseInt(inputOk1.value) || 0;
                const val2 = parseInt(inputOk2.value) || 0;
                spanTotal.textContent = val1 + val2;
            }

            // Event Listener biar live update saat diketik
            inputOk1.addEventListener('input', calculateTotal);
            inputOk2.addEventListener('input', calculateTotal);
        });
    });
    </script>
@endsection