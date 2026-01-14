@extends('layouts.app_simple')

@section('content')
<div class="row justify-content-center">
    <div class="col-12">
        
        {{-- Header & Filter --}}
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-1">SUMMARY KEBUTUHAN OPERATOR (MPP)</h4>
                <p class="text-muted small mb-0">Perhitungan Man Power Planning berdasarkan Load Produksi</p>
            </div>
            
            {{-- Form Filter Bulan --}}
            <form action="{{ route('mpp.index') }}" method="GET" class="d-flex gap-2">
                <select name="month" class="form-select form-select-sm fw-bold border-secondary">
                    @for($m=1; $m<=12; $m++)
                        <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                            {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                        </option>
                    @endfor
                </select>
                <select name="year" class="form-select form-select-sm fw-bold border-secondary">
                    @for($y=date('Y')-1; $y<=date('Y')+1; $y++)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
                <button type="submit" class="btn btn-dark btn-sm"><i class="fas fa-filter"></i> Filter</button>
            </form>
        </div>

        {{-- Tabel Report (Style mirip Excel Gambar) --}}
        <div class="card shadow-sm border-0 rounded-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle mb-0" style="border-color: #999; font-size: 0.75rem;">
                        <thead style="background-color: #d1d5db; border-bottom: 2px solid #666;">
                            <tr class="text-center align-middle">
                                <th rowspan="2" width="5%">NO</th>
                                <th rowspan="2" width="10%">PLANT</th>
                                <th rowspan="2" width="20%">LINE</th>
                                <th rowspan="2" width="10%">KEB. JAM KERJA</th>
                                <th colspan="2" class="border-bottom border-dark">KEBUTUHAN MPP</th>
                                <th colspan="3" class="border-bottom border-dark">ADJUSTMENT</th>
                                <th rowspan="2" width="8%" class="bg-warning bg-opacity-25">TOTAL MPP</th>
                            </tr>
                            <tr class="text-center align-middle">
                                <th width="8%" style="font-size: 0.7rem;">MURNI</th>
                                <th width="8%" style="font-size: 0.7rem;">BULAT</th>
                                <th width="8%" style="font-size: 0.7rem;">HELPER</th>
                                <th width="8%" style="font-size: 0.7rem;">BACKUP</th>
                                <th width="8%" style="font-size: 0.7rem;">ABSENSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php 
                                $grandTotalJam = 0;
                                $grandTotalMpp = 0;
                                $globalNo = 1;
                            @endphp

                            @foreach($groupedMpp as $plant => $items)
                                {{-- SUBTOTAL VARIABLES --}}
                                @php 
                                    $subTotalJam = 0;
                                    $subTotalMpp = 0;
                                @endphp

                                @foreach($items as $index => $row)
                                    <tr class="bg-white">
                                        <td class="text-center border-end">{{ $globalNo++ }}</td>
                                        
                                        {{-- Merge Cell Plant --}}
                                        @if($index == 0)
                                            <td rowspan="{{ count($items) }}" class="fw-bold align-middle bg-light border-end">{{ $plant }}</td>
                                        @endif
                                        
                                        <td class="fw-bold text-dark">{{ $row->line_name }}</td>
                                        
                                        {{-- Data Perhitungan --}}
                                        <td class="text-end pe-3">{{ number_format($row->keb_jam_kerja, 1) }}</td>
                                        <td class="text-center bg-info bg-opacity-10">{{ number_format($row->mpp_murni, 2) }}</td>
                                        <td class="text-center fw-bold">{{ $row->mpp_aktual }}</td>
                                        
                                        {{-- Placeholder Adjustment (Bisa diedit nanti) --}}
                                        <td class="text-center text-muted">-</td>
                                        <td class="text-center text-muted">-</td>
                                        <td class="text-center text-muted">-</td>
                                        
                                        {{-- Total Akhir --}}
                                        <td class="text-center fw-bold bg-warning bg-opacity-10">{{ $row->mpp_aktual }}</td>
                                    </tr>
                                    
                                    @php
                                        $subTotalJam += $row->keb_jam_kerja;
                                        $subTotalMpp += $row->mpp_aktual;
                                    @endphp
                                @endforeach

                                {{-- ROW SUBTOTAL PER PLANT --}}
                                <tr style="background-color: #e5e7eb; border-top: 2px solid #999;">
                                    <td colspan="3" class="text-end fw-bold pe-3">SUB TOTAL {{ $plant }}</td>
                                    <td class="text-end fw-bold pe-3">{{ number_format($subTotalJam, 1) }}</td>
                                    <td class="text-center fw-bold">-</td>
                                    <td class="text-center fw-bold">{{ $subTotalMpp }}</td>
                                    <td colspan="3"></td>
                                    <td class="text-center fw-bold bg-warning bg-opacity-25">{{ $subTotalMpp }}</td>
                                </tr>

                                @php
                                    $grandTotalJam += $subTotalJam;
                                    $grandTotalMpp += $subTotalMpp;
                                @endphp

                            @endforeach
                        </tbody>
                        
                        {{-- GRAND TOTAL FOOTER --}}
                        <tfoot style="background-color: #374151; color: white;">
                            <tr>
                                <td colspan="3" class="text-end fw-bold py-2 pe-3">GRAND TOTAL (ALL PLANT)</td>
                                <td class="text-end fw-bold py-2 pe-3">{{ number_format($grandTotalJam, 1) }}</td>
                                <td></td>
                                <td class="text-center fw-bold py-2">{{ $grandTotalMpp }}</td>
                                <td colspan="3"></td>
                                <td class="text-center fw-bold py-2 bg-warning text-dark">{{ $grandTotalMpp }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white border-top small text-muted">
                <i class="fas fa-info-circle me-1"></i> 
                <strong>Rumus:</strong> Keb. Jam Kerja = (Qty Plan × Cycle Time). 
                MPP Murni = Keb. Jam Kerja / {{ $totalHoursPerson }} Jam (Kapasitas per Orang/Bulan).
            </div>
        </div>

    </div>
</div>
@endsection