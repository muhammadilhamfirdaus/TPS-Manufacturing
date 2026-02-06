@extends('layouts.app_simple')

@section('content')
<div class="row justify-content-center">
    <div class="col-12">
        
        {{-- Header & Filter --}}
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4 gap-3">
            <div>
                <h4 class="fw-bold text-dark mb-1">SUMMARY KEBUTUHAN OPERATOR (MPP)</h4>
                <p class="text-muted small mb-0">
                    Perhitungan Man Power Planning berdasarkan Load Produksi Bulan: 
                    <span class="text-primary fw-bold">{{ date('F Y', mktime(0,0,0, $month, 1, $year)) }}</span>
                </p>
            </div>
            
            <div class="d-flex gap-2 align-items-center">
                {{-- Form Filter --}}
                <form action="{{ route('mpp.index') }}" method="GET" class="d-flex gap-2 align-items-center bg-white p-2 rounded shadow-sm border">
                    <label class="small fw-bold text-muted mb-0">PERIODE:</label>
                    <select name="month" class="form-select form-select-sm fw-bold border-secondary text-dark" style="width: 120px;" onchange="this.form.submit()">
                        @for($m=1; $m<=12; $m++)
                            <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                            </option>
                        @endfor
                    </select>
                    <select name="year" class="form-select form-select-sm fw-bold border-secondary text-dark" style="width: 90px;" onchange="this.form.submit()">
                        @for($y=date('Y')-1; $y<=date('Y')+1; $y++)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                    <div class="vr mx-1"></div>
                    <span class="badge bg-light text-primary border">
                        <i class="fas fa-calendar-day me-1"></i> {{ $workDays }} Hari Kerja
                    </span>
                     {{-- TOMBOL EXPORT PDF --}}
                    <a href="{{ route('mpp.pdf', ['month' => $month, 'year' => $year]) }}" target="_blank" class="btn btn-sm btn-danger text-white shadow-sm border-0 h-100 d-flex align-items-center">
                        <i class="fas fa-file-pdf me-1"></i> PDF
                    </a>

                    {{-- TOMBOL SIMPAN (Submit Form Table) --}}
                    <button type="submit" form="form-mpp-adjustment" class="btn btn-sm btn-primary fw-bold text-white shadow-sm border-0 h-100 d-flex align-items-center">
                        <i class="fas fa-save me-1"></i> SIMPAN PERUBAHAN
                    </button>
                </form>

               
            </div>
        </div>

        {{-- Tabel Report --}}
        <div class="card shadow-sm border-0 rounded-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    
                    {{-- FORM WRAPPER UNTUK INPUT ADJUSTMENT --}}
                    <form id="form-mpp-adjustment" action="{{ route('mpp.store_adjustment') }}" method="POST">
                        @csrf
                        {{-- Kirim Bulan & Tahun sebagai Hidden Input --}}
                        <input type="hidden" name="month" value="{{ $month }}">
                        <input type="hidden" name="year" value="{{ $year }}">

                        <table class="table table-bordered table-sm align-middle mb-0" style="border-color: #999; font-size: 0.75rem;">
                            <thead style="background-color: #d1d5db; border-bottom: 2px solid #666;">
                                <tr class="text-center align-middle">
                                    <th rowspan="2" width="5%">NO</th>
                                    <th rowspan="2" width="10%">PLANT</th>
                                    <th rowspan="2" width="20%">LINE</th>
                                    <th rowspan="2" width="10%">KEB. JAM KERJA</th>
                                    <th colspan="2" class="border-bottom border-dark">KEBUTUHAN MPP</th>
                                    <th colspan="3" class="border-bottom border-dark bg-warning bg-opacity-25">ADJUSTMENT (INPUT)</th>
                                    <th rowspan="2" width="8%" class="bg-dark text-white">TOTAL MPP</th>
                                </tr>
                                <tr class="text-center align-middle">
                                    <th width="8%" style="font-size: 0.7rem;">MURNI</th>
                                    <th width="8%" style="font-size: 0.7rem;">BULAT</th>
                                    <th width="8%" style="font-size: 0.7rem;" class="bg-warning bg-opacity-10">HELPER</th>
                                    <th width="8%" style="font-size: 0.7rem;" class="bg-warning bg-opacity-10">BACKUP</th>
                                    <th width="8%" style="font-size: 0.7rem;" class="bg-warning bg-opacity-10">ABSENSI</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php 
                                    $grandTotalJam = 0;
                                    $grandTotalMpp = 0; // Total Keseluruhan (Bulat + Adj)
                                    $globalNo = 1;
                                @endphp

                                @forelse($groupedMpp as $plant => $items)
                                    {{-- SUBTOTAL VARIABLES --}}
                                    @php 
                                        $subTotalJam = 0;
                                        $subTotalMpp = 0; // Subtotal Plant
                                    @endphp

                                    @foreach($items as $index => $row)
                                        @php
                                            // Ambil Data Adjustment (Jika Ada)
                                            $adj = $adjustments[$row->line_id] ?? null;
                                            $valHelper  = $adj->helper ?? 0;
                                            $valBackup  = $adj->backup ?? 0;
                                            $valAbsensi = $adj->absensi ?? 0;

                                            // Hitung Total Baris: Bulat + Helper + Backup + Absensi
                                            $totalRowMpp = $row->mpp_aktual + $valHelper + $valBackup + $valAbsensi;
                                        @endphp

                                        <tr class="bg-white hover-bg-light">
                                            <td class="text-center border-end">{{ $globalNo++ }}</td>
                                            
                                            {{-- Merge Cell Plant --}}
                                            @if($index == 0)
                                                <td rowspan="{{ count($items) + 1 }}" class="fw-bold align-middle bg-light border-end">{{ $plant }}</td>
                                            @endif
                                            
                                            <td class="fw-bold text-dark">{{ $row->line_name }}</td>
                                            
                                            {{-- Data Perhitungan --}}
                                            <td class="text-end pe-3">{{ number_format($row->keb_jam_kerja, 1) }}</td>
                                            <td class="text-center bg-info bg-opacity-10">{{ number_format($row->mpp_murni, 2) }}</td>
                                            <td class="text-center fw-bold">{{ $row->mpp_aktual }}</td>
                                            
                                            {{-- INPUT ADJUSTMENT --}}
                                            <td class="p-1">
                                                <input type="number" min="0" class="form-control form-control-sm text-center fw-bold border-warning p-0" 
                                                       style="font-size: 0.8rem;"
                                                       name="adjustments[{{ $row->line_id }}][helper]" 
                                                       value="{{ $valHelper }}">
                                            </td>
                                            <td class="p-1">
                                                <input type="number" min="0" class="form-control form-control-sm text-center fw-bold border-warning p-0" 
                                                       style="font-size: 0.8rem;"
                                                       name="adjustments[{{ $row->line_id }}][backup]" 
                                                       value="{{ $valBackup }}">
                                            </td>
                                            <td class="p-1">
                                                <input type="number" min="0" class="form-control form-control-sm text-center fw-bold border-warning p-0" 
                                                       style="font-size: 0.8rem;"
                                                       name="adjustments[{{ $row->line_id }}][absensi]" 
                                                       value="{{ $valAbsensi }}">
                                            </td>
                                            
                                            {{-- Total Akhir Baris --}}
                                            <td class="text-center fw-bold bg-dark text-white">{{ $totalRowMpp }}</td>
                                        </tr>
                                        
                                        @php
                                            $subTotalJam += $row->keb_jam_kerja;
                                            $subTotalMpp += $totalRowMpp; // Akumulasi Subtotal Plant
                                        @endphp
                                    @endforeach

                                    {{-- ROW SUBTOTAL PER PLANT --}}
                                    <tr style="background-color: #f8f9fa; border-top: 2px solid #999; font-weight: bold;">
                                        <td></td> 
                                        {{-- Kolom PLANT sudah dimerge --}}
                                        <td class="text-end pe-3 text-uppercase small text-muted">SUB TOTAL {{ $plant }}</td>
                                        <td class="text-end pe-3">{{ number_format($subTotalJam, 1) }}</td>
                                        <td colspan="5" class="text-center text-muted small fst-italic"></td>
                                        <td class="text-center bg-secondary text-white">{{ $subTotalMpp }}</td>
                                    </tr>

                                    @php
                                        $grandTotalJam += $subTotalJam;
                                        $grandTotalMpp += $subTotalMpp; // Akumulasi Grand Total
                                    @endphp

                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center py-5 text-muted">
                                            <i class="fas fa-search fa-2x mb-3 opacity-50"></i><br>
                                            Tidak ada data plan produksi di periode ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            
                            {{-- GRAND TOTAL FOOTER --}}
                            <tfoot style="background-color: #374151; color: white;">
                                <tr>
                                    <td colspan="3" class="text-end fw-bold py-2 pe-3">GRAND TOTAL (ALL PLANT)</td>
                                    <td class="text-end fw-bold py-2 pe-3">{{ number_format($grandTotalJam, 1) }}</td>
                                    <td colspan="5"></td>
                                    <td class="text-center fw-bold py-2 bg-warning text-dark fs-6">{{ $grandTotalMpp }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </form> {{-- END FORM --}}
                </div>
            </div>
            <div class="card-footer bg-white border-top small text-muted">
                <i class="fas fa-info-circle me-1"></i> 
                <strong>Rumus:</strong> Keb. Jam Kerja = (Qty Plan × Cycle Time). 
                MPP Murni = Keb. Jam Kerja / {{ number_format($totalHoursPerson, 0) }} Jam (Kapasitas per Orang/Bulan).
                Total MPP = Bulat + Helper + Backup + Absensi.
            </div>
        </div>

    </div>
</div>
@endsection