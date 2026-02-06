<!DOCTYPE html>
<html>
<head>
    <title>MPP Report - {{ date('F Y', mktime(0, 0, 0, $month, 1, $year)) }}</title>
    <style>
        @page { margin: 20px; }
        body { font-family: sans-serif; font-size: 9px; color: #000; }
        
        .header { text-align: center; margin-bottom: 15px; }
        .header h2 { margin: 0; font-size: 14px; text-transform: uppercase; }
        .header p { margin: 2px 0; font-size: 10px; }

        .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 10px; }
        .meta-table td { padding: 3px; }
        
        /* Table Utama */
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.data th, table.data td { border: 0.5px solid #000; padding: 4px 2px; text-align: center; vertical-align: middle; }
        table.data th { background-color: #e5e7eb; font-weight: bold; font-size: 8px; text-transform: uppercase; }
        
        /* Utility Classes */
        .text-left { text-align: left !important; padding-left: 5px !important; }
        .text-right { text-align: right !important; padding-right: 5px !important; }
        .fw-bold { font-weight: bold; }
        
        .bg-subtotal { background-color: #f3f4f6; font-weight: bold; }
        .bg-total { background-color: #374151; color: white; font-weight: bold; }
        .bg-highlight { background-color: #fef3c7; } /* Kuning Muda */
        .bg-dark { background-color: #1f2937; color: white; }

        /* Signature Box */
        .signature-table { width: 100%; margin-top: 20px; page-break-inside: avoid; border-collapse: collapse; }
        .signature-table td { border: 1px solid #000; text-align: center; vertical-align: top; width: 25%; font-size: 9px; }
        .sign-header { background-color: #e5e7eb; font-weight: bold; padding: 4px; }
        .sign-space { height: 50px; }
        .sign-name { padding: 4px; font-weight: bold; border-top: 1px dotted #999; }
    </style>
</head>
<body>

    {{-- HEADER --}}
    <div class="header">
        <h2>SUMMARY KEBUTUHAN OPERATOR (MPP)</h2>
        <p>Periode Produksi: {{ date('F Y', mktime(0, 0, 0, $month, 1, $year)) }}</p>
    </div>

    {{-- INFO TABLE --}}
    <table class="meta-table">
        <tr>
            <td width="50%" align="left">
                <strong>Periode:</strong> {{ date('F Y', mktime(0, 0, 0, $month, 1, $year)) }}
            </td>
            <td width="50%" align="right">
                <strong>Hari Kerja:</strong> {{ $workDays }} Hari &nbsp;|&nbsp; 
                <strong>Kapasitas Org:</strong> {{ number_format($totalHoursPerson, 0) }} Jam/Bulan
            </td>
        </tr>
    </table>

    {{-- DATA TABLE --}}
    <table class="data">
        <thead>
            <tr>
                <th width="4%" rowspan="2">NO</th>
                <th width="10%" rowspan="2">PLANT</th>
                <th width="20%" rowspan="2">LINE</th>
                <th width="10%" rowspan="2">KEB. JAM</th>
                <th colspan="2">KEBUTUHAN MPP</th>
                <th colspan="3">ADJUSTMENT</th>
                <th width="8%" rowspan="2" class="bg-dark">TOTAL MPP</th>
            </tr>
            <tr>
                <th width="8%">MURNI</th>
                <th width="8%">BULAT</th>
                <th width="7%">HELPER</th>
                <th width="7%">BACKUP</th>
                <th width="7%">ABSENSI</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $gTotalJam = 0; 
                $gTotalMpp = 0; 
                $no = 1; 
            @endphp

            @foreach($groupedMpp as $plant => $items)
                @php 
                    $subJam = 0; 
                    $subMpp = 0; 
                @endphp

                @foreach($items as $idx => $row)
                    @php
                        // Ambil Data Adjustment (Jika Ada)
                        // Perhatikan: Di PDF controller kita perlu mengirim variabel $adjustments juga
                        $adj = $adjustments[$row->line_id] ?? null;
                        $valHelper  = $adj->helper ?? 0;
                        $valBackup  = $adj->backup ?? 0;
                        $valAbsensi = $adj->absensi ?? 0;

                        // Hitung Total Baris
                        $totalRow = $row->mpp_aktual + $valHelper + $valBackup + $valAbsensi;
                    @endphp

                    <tr>
                        <td class="text-center">{{ $no++ }}</td>
                        
                        {{-- Merge Plant Name --}}
                        @if($idx == 0)
                            <td rowspan="{{ count($items) + 1 }}" class="fw-bold text-left" style="vertical-align: middle;">
                                {{ $plant }}
                            </td>
                        @endif

                        <td class="text-left">{{ $row->line_name }}</td>
                        <td class="text-right">{{ number_format($row->keb_jam_kerja, 1) }}</td>
                        <td class="text-center">{{ number_format($row->mpp_murni, 2) }}</td>
                        <td class="text-center fw-bold">{{ $row->mpp_aktual }}</td>
                        
                        {{-- DATA ADJUSTMENT --}}
                        <td class="text-center">{{ $valHelper > 0 ? $valHelper : '-' }}</td>
                        <td class="text-center">{{ $valBackup > 0 ? $valBackup : '-' }}</td>
                        <td class="text-center">{{ $valAbsensi > 0 ? $valAbsensi : '-' }}</td>

                        {{-- TOTAL --}}
                        <td class="bg-highlight fw-bold">{{ $totalRow }}</td>
                    </tr>

                    @php 
                        $subJam += $row->keb_jam_kerja; 
                        $subMpp += $totalRow; // Akumulasi Subtotal Plant
                    @endphp
                @endforeach

                {{-- SUBTOTAL ROW --}}
                <tr class="bg-subtotal">
                    <td></td>
                    {{-- Kolom Plant sudah di-merge --}}
                    <td class="text-right fw-bold">SUB TOTAL</td>
                    <td class="text-right fw-bold">{{ number_format($subJam, 1) }}</td>
                    <td colspan="5"></td>
                    <td class="bg-highlight fw-bold">{{ $subMpp }}</td>
                </tr>

                @php 
                    $gTotalJam += $subJam; 
                    $gTotalMpp += $subMpp; 
                @endphp
            @endforeach
        </tbody>
        <tfoot>
            <tr class="bg-total">
                <td colspan="3" class="text-right">GRAND TOTAL (ALL PLANT)</td>
                <td class="text-right">{{ number_format($gTotalJam, 1) }}</td>
                <td colspan="5"></td>
                <td class="bg-highlight" style="color: black;">{{ $gTotalMpp }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- FOOTER INFO --}}
    <div style="font-size: 8px; color: #555; margin-bottom: 20px;">
        <em>* Rumus: MPP Murni = Keb. Jam Kerja / {{ number_format($totalHoursPerson, 0) }} Jam. Total MPP = Bulat + Helper + Backup + Absensi.</em>
    </div>

    {{-- APPROVAL SIGNATURE --}}
    <table class="signature-table">
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
            <td>Date: ...................</td>
            <td>Date: ...................</td>
            <td>Date: ...................</td>
            <td>Date: ...................</td>
        </tr>
    </table>

</body>
</html>