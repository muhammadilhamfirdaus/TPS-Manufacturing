<!DOCTYPE html>
<html>
<head>
    <title>MPP Report</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 0; }
        .meta { width: 100%; margin-bottom: 10px; border-bottom: 2px solid #000; padding-bottom: 5px; }
        
        /* Table Styling mirip Excel */
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.data th, table.data td { border: 1px solid #000; padding: 4px; text-align: center; }
        table.data th { background-color: #d1d5db; }
        .text-left { text-align: left !important; }
        .text-right { text-align: right !important; }
        .bg-subtotal { background-color: #f3f4f6; font-weight: bold; }
        .bg-total { background-color: #374151; color: white; font-weight: bold; }
        .bg-yellow { background-color: #fcd34d; }

        /* Signature / Approval Box */
        .signature-table { width: 100%; margin-top: 30px; page-break-inside: avoid; }
        .signature-table td { border: 1px solid #000; text-align: center; vertical-align: top; width: 25%; }
        .sign-header { font-weight: bold; background-color: #e5e7eb; padding: 5px; }
        .sign-space { height: 60px; }
        .sign-name { padding: 5px; font-weight: bold; border-top: 1px dotted #999; }
    </style>
</head>
<body>

    <div class="header">
        <h2>SUMMARY KEBUTUHAN OPERATOR (MPP)</h2>
    </div>

    <table class="meta" style="border: none;">
        <tr>
            <td align="left"><strong>PERIODE: {{ strtoupper(date('F Y', mktime(0, 0, 0, $month, 1, $year))) }}</strong></td>
            <td align="right">Hari Kerja: {{ $workDays }} Hari | Kapasitas Org: {{ $totalHoursPerson }} Jam</td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th width="5%">NO</th>
                <th width="10%">PLANT</th>
                <th width="25%">LINE</th>
                <th width="12%">KEB. JAM</th>
                <th width="10%">MPP MURNI</th>
                <th width="10%">MPP BULAT</th>
                <th width="10%">ADJUSTMENT</th>
                <th width="10%" class="bg-yellow">TOTAL MPP</th>
            </tr>
        </thead>
        <tbody>
            @php $gTotalJam = 0; $gTotalMpp = 0; $no = 1; @endphp

            @foreach($groupedMpp as $plant => $items)
                @php $subJam = 0; $subMpp = 0; @endphp

                @foreach($items as $idx => $row)
                    <tr>
                        <td>{{ $no++ }}</td>
                        @if($idx == 0)
                            <td rowspan="{{ count($items) + 1 }}" style="vertical-align: middle; font-weight:bold;">
                                {{ $plant }}
                            </td>
                        @endif
                        <td class="text-left">{{ $row->line_name }}</td>
                        <td class="text-right">{{ number_format($row->keb_jam_kerja, 1) }}</td>
                        <td>{{ number_format($row->mpp_murni, 2) }}</td>
                        <td><strong>{{ $row->mpp_aktual }}</strong></td>
                        <td>-</td>
                        <td class="bg-yellow"><strong>{{ $row->mpp_aktual }}</strong></td>
                    </tr>
                    @php 
                        $subJam += $row->keb_jam_kerja; 
                        $subMpp += $row->mpp_aktual; 
                    @endphp
                @endforeach

                <tr class="bg-subtotal">
                    <td></td>
                    <td class="text-right">SUB TOTAL</td>
                    <td class="text-right">{{ number_format($subJam, 1) }}</td>
                    <td>-</td>
                    <td>{{ $subMpp }}</td>
                    <td></td>
                    <td class="bg-yellow">{{ $subMpp }}</td>
                </tr>

                @php 
                    $gTotalJam += $subJam; 
                    $gTotalMpp += $subMpp; 
                @endphp
            @endforeach
        </tbody>
        <tfoot>
            <tr class="bg-total">
                <td colspan="3" class="text-right">GRAND TOTAL</td>
                <td class="text-right">{{ number_format($gTotalJam, 1) }}</td>
                <td></td>
                <td>{{ $gTotalMpp }}</td>
                <td></td>
                <td style="color: black; background-color: #fcd34d;">{{ $gTotalMpp }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- APPROVAL SIGNATURE BLOCK (Sesuai Request) --}}
    <table class="signature-table" cellspacing="5">
        <tr>
            <td class="sign-header">PREPARED BY</td>
            <td class="sign-header">CHECKED BY</td>
            <td class="sign-header">APPROVED BY (PPC)</td>
            <td class="sign-header">APPROVED BY (PROD)</td>
        </tr>
        <tr>
            <td class="sign-space"></td>
            <td class="sign-space"></td>
            <td class="sign-space"></td>
            <td class="sign-space"></td>
        </tr>
        <tr>
            <td class="sign-name">Staff PPC</td>
            <td class="sign-name">SPV PPC</td>
            <td class="sign-name">Manager PPC</td>
            <td class="sign-name">Manager Prod</td>
        </tr>
        <tr>
            <td style="font-size: 9px;">Date: ...................</td>
            <td style="font-size: 9px;">Date: ...................</td>
            <td style="font-size: 9px;">Date: ...................</td>
            <td style="font-size: 9px;">Date: ...................</td>
        </tr>
    </table>

</body>
</html>