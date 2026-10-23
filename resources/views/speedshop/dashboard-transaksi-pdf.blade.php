<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard — List transaksi</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111827; }
        .title { font-size: 14px; font-weight: bold; margin-bottom: 4px; }
        .meta { font-size: 10px; color: #4b5563; margin-bottom: 10px; line-height: 1.4; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 5px 4px; vertical-align: top; }
        th { background: #f3f4f6; text-align: left; font-size: 8px; text-transform: uppercase; }
        td.num { text-align: right; white-space: nowrap; }
        td.center { text-align: center; }
    </style>
</head>
<body>
    <div class="title">List transaksi per invoice (selesai)</div>
    <div class="meta">
        Dicetak: {{ $printedAt->format('d/m/Y H:i') }}<br>
        Periode: {{ $dari->format('d/m/Y') }} — {{ $sampai->format('d/m/Y') }}
        &nbsp;|&nbsp; Filter: {{ strtoupper($mode) }}
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:22px;">No</th>
                <th style="width:40px;">Order ID</th>
                <th style="width:72px;">No. Invoice</th>
                <th style="width:48px;">Tgl. Inv.</th>
                <th style="width:78px;">Jenis Order</th>
                <th style="width:64px;">Kat. Serv.</th>
                <th style="width:72px;">Sumber</th>
                <th>Nama Customer</th>
                <th style="width:52px;">Mekanik</th>
                <th style="width:58px;">No. HP</th>
                <th style="width:48px;">No. Polisi</th>
                <th style="width:52px;">Jenis Kend.</th>
                <th style="width:44px;">KM</th>
                <th style="width:28px;">Thn</th>
                <th style="width:36px;">Bayar</th>
                <th style="width:64px;" class="num">Pend. Omset</th>
                <th style="width:52px;" class="num">Charge</th>
                <th style="width:52px;" class="num">PPN</th>
                <th style="width:64px;" class="num">Tot. Tagihan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $r)
                <tr>
                    <td class="center">{{ $r[0] }}</td>
                    <td>{{ $r[1] }}</td>
                    <td>{{ $r[2] }}</td>
                    <td>{{ $r[3] }}</td>
                    <td>{{ $r[4] }}</td>
                    <td>{{ $r[5] }}</td>
                    <td>{{ $r[6] }}</td>
                    <td>{{ $r[7] }}</td>
                    <td>{{ $r[8] }}</td>
                    <td>{{ $r[9] }}</td>
                    <td>{{ $r[10] }}</td>
                    <td>{{ $r[11] }}</td>
                    <td class="num">{{ $r[12] }}</td>
                    <td class="center">{{ $r[13] }}</td>
                    <td>{{ $r[14] }}</td>
                    <td class="num">{{ number_format((int) ($r[15] ?? 0), 0, ',', '.') }}</td>
                    <td class="num">{{ number_format((int) ($r[16] ?? 0), 0, ',', '.') }}</td>
                    <td class="num">{{ number_format((int) ($r[17] ?? 0), 0, ',', '.') }}</td>
                    <td class="num">{{ number_format((int) ($r[18] ?? 0), 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="19" style="text-align:center; color:#6b7280; padding:12px;">Tidak ada data pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
