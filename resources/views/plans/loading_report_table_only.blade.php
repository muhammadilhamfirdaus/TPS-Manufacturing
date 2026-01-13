<table border="1">
    <thead>
        <tr>
            <th colspan="15" style="text-align: center; font-weight: bold; font-size: 16px;">
                LOADING REPORT: {{ $line->name }} - {{ strtoupper($period) }}
            </th>
        </tr>
        <tr>
            <th rowspan="2" valign="middle" width="5">NO</th>
            <th rowspan="2" valign="middle" width="15">CODE PART</th>
            <th rowspan="2" valign="middle" width="20">PART NO</th>
            <th rowspan="2" valign="middle" width="25">PART NAME</th>
            <th rowspan="2" valign="middle" width="15">FLOW PROSES</th>
            <th rowspan="2" valign="middle" width="10">PLAN</th>
            <th rowspan="2" valign="middle" width="10">PCS/JAM</th>
            <th rowspan="2" valign="middle" width="10">C/T</th>
            
            @foreach($groupedMachines as $groupName => $machines)
                <th colspan="{{ $machines->count() }}" style="background-color: #333333; color: #ffffff; text-align: center;">
                    {{ strtoupper($groupName ?: 'GENERAL') }}
                </th>
            @endforeach
            
            <th rowspan="2" valign="middle" width="10" style="background-color: #ffe69c;">TOTAL (JAM)</th>
        </tr>
        <tr>
            @foreach($groupedMachines as $groupName => $machines)
                @foreach($machines as $machine)
                    <th style="background-color: #f2f2f2; text-align: center; font-weight: bold;">
                        {{ $machine->name }}
                    </th>
                @endforeach
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($reportData as $index => $row)
        <tr>
            <td align="center">{{ $loop->iteration }}</td>
            <td>{{ $row->code_part }}</td>
            <td>{{ $row->part_number }}</td>
            <td>{{ $row->part_name }}</td>
            <td>{{ $row->process_name }}</td>
            <td align="center">{{ $row->qty_plan }}</td>
            <td align="center" style="background-color: #cff4fc;">{{ $row->pcs_per_hour }}</td>
            <td align="center">{{ number_format($row->cycle_time, 1) }}</td>

            @foreach($groupedMachines as $groupName => $machines)
                @foreach($machines as $machine)
                    @if($machine->id == $row->machine_id)
                        <td align="center" style="background-color: #d1e7dd; font-weight: bold;">
                            {{ number_format($row->load_hours, 1) }}
                        </td>
                    @else
                        <td></td> 
                    @endif
                @endforeach
            @endforeach

            <td align="center" style="background-color: #ffe69c; font-weight: bold;">
                {{ number_format($row->load_hours, 1) }}
            </td>
        </tr>
        @endforeach
    </tbody>
</table>