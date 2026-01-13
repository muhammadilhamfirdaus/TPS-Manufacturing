@extends('layouts.app_simple')

@section('content')
<div class="row justify-content-center">
    <div class="col-12">
        
        {{-- Header & Toolbar --}}
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
            <div>
                <h4 class="fw-bold text-dark mb-1">Loading Report</h4>
                <p class="text-muted small mb-0 text-uppercase fw-bold text-primary">
                    <i class="far fa-calendar-alt me-1"></i> Periode: {{ date('F Y') }}
                </p>
            </div>

            <div class="d-flex gap-2 align-items-center">
                {{-- Filter Line --}}
                <form action="{{ route('plans.loading_report') }}" method="GET" class="d-flex align-items-center">
                    <select name="line_id" class="form-select form-select-sm bg-white border shadow-sm fw-bold text-secondary" style="min-width: 200px;" onchange="this.form.submit()">
                        @foreach($allLines as $l)
                            <option value="{{ $l->id }}" {{ $line->id == $l->id ? 'selected' : '' }}>
                                {{ $l->name }}
                            </option>
                        @endforeach
                    </select>
                </form>

                <div class="vr h-50 my-auto text-secondary opacity-25"></div>

                {{-- Tombol Download --}}
                <div class="btn-group shadow-sm" role="group">
                    <a href="{{ route('plans.loading_excel', ['line_id' => $line->id]) }}" class="btn btn-sm btn-white border text-success fw-bold" target="_blank" title="Download Excel">
                        <i class="fas fa-file-excel me-1"></i> Excel
                    </a>
                    <a href="{{ route('plans.loading_pdf', ['line_id' => $line->id]) }}" class="btn btn-sm btn-white border text-danger fw-bold" target="_blank" title="Download PDF">
                        <i class="fas fa-file-pdf me-1"></i> PDF
                    </a>
                </div>

                {{-- Tombol Kembali --}}
                <a href="{{ route('plans.index') }}" class="btn btn-sm btn-light border text-secondary" title="Kembali">
                    <i class="fas fa-arrow-left"></i>
                </a>
            </div>
        </div>

        {{-- Main Report Card --}}
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-header bg-white py-3 border-bottom text-center">
                <h6 class="fw-bold text-dark mb-0 text-uppercase ls-1">
                    {{ $line->name }} - Machine Loading Analysis
                </h6>
            </div>
            
            <div class="card-body p-0">
                <div class="table-responsive">
                    {{-- Tabel Matriks --}}
                    <table class="table table-bordered table-sm align-middle text-center mb-0" style="font-size: 0.7rem; border-color: #e5e7eb;">
                        <thead class="bg-light text-secondary">
                            <tr>
                                <th rowspan="2" class="align-middle py-3 bg-light" width="30">#</th>
                                <th rowspan="2" class="align-middle py-3 bg-light">CODE PART</th>
                                <th rowspan="2" class="align-middle py-3 bg-light">PART NO</th>
                                <th rowspan="2" class="align-middle py-3 bg-light">PART NAME</th>
                                <th rowspan="2" class="align-middle py-3 bg-light">PROCESS</th>
                                <th rowspan="2" class="align-middle py-3 bg-light">PLAN</th>
                                <th rowspan="2" class="align-middle py-3 bg-light text-primary">PCS/H</th>
                                <th rowspan="2" class="align-middle py-3 bg-light text-muted">C/T</th>
                                
                                {{-- Header Group Mesin --}}
                                @foreach($groupedMachines as $groupName => $machines)
                                    <th colspan="{{ $machines->count() }}" class="bg-secondary bg-opacity-10 text-dark fw-bold border-bottom py-2">
                                        {{ strtoupper($groupName ?: 'GENERAL') }}
                                    </th>
                                @endforeach
                                
                                <th rowspan="2" class="align-middle py-3 bg-warning bg-opacity-10 text-dark border-start">TOTAL LOAD</th>
                            </tr>
                            <tr>
                                {{-- Header Nama Mesin --}}
                                @foreach($groupedMachines as $groupName => $machines)
                                    @foreach($machines as $machine)
                                        <th class="p-1 bg-white" style="min-width: 70px;">
                                            <div class="fw-bold text-dark text-truncate" title="{{ $machine->name }}">{{ $machine->name }}</div>
                                            <div class="text-muted" style="font-size: 0.6rem;">{{ $machine->machine_code ?? '-' }}</div>
                                        </th>
                                    @endforeach
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reportData as $index => $row)
                            <tr class="hover-bg-light">
                                <td class="text-muted">{{ $loop->iteration }}</td>
                                <td class="text-start fw-bold text-secondary">{{ $row->code_part }}</td>
                                <td class="text-start fw-bold text-dark">{{ $row->part_number }}</td>
                                <td class="text-start text-nowrap text-secondary">{{ $row->part_name }}</td>
                                
                                {{-- Process Name --}}
                                <td class="text-primary fw-bold text-uppercase" style="font-size: 0.65rem;">{{ $row->process_name }}</td>
                                
                                {{-- Plan & Cap --}}
                                <td class="fw-bold text-dark bg-light">{{ number_format($row->qty_plan) }}</td>
                                <td class="fw-bold text-primary bg-primary bg-opacity-10">{{ number_format($row->pcs_per_hour) }}</td>
                                <td class="text-muted">{{ number_format($row->cycle_time, 1) }}</td>

                                {{-- Loop Matriks Mesin --}}
                                @foreach($groupedMachines as $groupName => $machines)
                                    @foreach($machines as $machine)
                                        @if($machine->id == $row->machine_id)
                                            <td class="bg-success bg-opacity-25 fw-bold text-dark border border-success border-opacity-25 position-relative">
                                                {{ number_format($row->load_hours, 1) }}
                                            </td>
                                        @else
                                            <td class="bg-white"></td> 
                                        @endif
                                    @endforeach
                                @endforeach

                                {{-- Total Load --}}
                                <td class="fw-bold bg-warning bg-opacity-10 border-start">{{ number_format($row->load_hours, 1) }}</td>
                            </tr>
                            @empty
                            <tr>
                                @php $cols = 9 + $line->machines->count(); @endphp
                                <td colspan="{{ $cols }}" class="text-center py-5 text-muted bg-light">
                                    <i class="fas fa-chart-bar fa-2x mb-3 text-secondary opacity-50"></i><br>
                                    Belum ada data loading untuk periode ini.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white small text-muted text-end border-top-0 py-2">
                * Satuan Load dalam Jam (Hours)
            </div>
        </div>
    </div>
</div>
@endsection