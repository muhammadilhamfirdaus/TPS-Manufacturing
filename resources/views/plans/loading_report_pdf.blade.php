<!DOCTYPE html>
<html>
<head>
    <title>Loading Report</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 4px; text-align: center; }
        th { background-color: #f0f0f0; }
        .bg-dark { background-color: #333; color: #fff; }
        .bg-load { background-color: #d1e7dd; }
        .bg-total { background-color: #ffe69c; }
        .text-left { text-align: left; }
    </style>
</head>
<body>
    <h2 style="text-align: center; margin-bottom: 5px;">LOADING REPORT: {{ $line->name }}</h2>
    <h4 style="text-align: center; margin-top: 0;">Periode: {{ strtoupper($period) }}</h4>

    <table>
        <thead>
            <tr>
                <th rowspan="2">NO</th>
                <th rowspan="2">CODE</th>
                <th rowspan="2">PART NO</th>
                <th rowspan="2">PART NAME</th>
                <th rowspan="2">PROCESS</th>
                <th rowspan="2">PLAN</th>
                <th rowspan="2">PCS/H</th>
                
                @foreach($groupedMachines as $groupName => $machines)
                    <th colspan="{{ $machines->count() }}" class="bg-dark">
                        {{ strtoupper($groupName ?: 'GEN') }}
                    </th>
                @endforeach
                
                <th rowspan="2" class="bg-total">LOAD</th>
            </tr>
            <tr>
                @foreach($groupedMachines as $groupName => $machines)
                    @foreach($machines as $machine)
                        <th>{{ $machine->name }}</th>
                    @endforeach
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($reportData as $row)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td class="text-left">{{ $row->code_part }}</td>
                <td class="text-left">{{ $row->part_number }}</td>
                <td class="text-left">{{ $row->part_name }}</td>
                <td>{{ $row->process_name }}</td>
                <td>{{ number_format($row->qty_plan) }}</td>
                <td>{{ number_format($row->pcs_per_hour) }}</td>

                @foreach($groupedMachines as $groupName => $machines)
                    @foreach($machines as $machine)
                        @if($machine->id == $row->machine_id)
                            <td class="bg-load">
                                {{ number_format($row->load_hours, 1) }}
                            </td>
                        @else
                            <td></td> 
                        @endif
                    @endforeach
                @endforeach

                <td class="bg-total">{{ number_format($row->load_hours, 1) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>