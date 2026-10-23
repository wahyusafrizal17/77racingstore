<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Jasa Servis</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        .title { font-size: 16px; font-weight: bold; margin-bottom: 4px; }
        .meta { font-size: 11px; color: #4b5563; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 8px; }
        th { background: #f3f4f6; text-align: left; font-size: 11px; text-transform: uppercase; }
        .num { text-align: right; white-space: nowrap; }
    </style>
</head>
<body>
    <div class="title">Master Data Jasa Servis</div>
    <div class="meta">
        Dicetak: {{ $printedAt->format('d-m-Y H:i') }}
        @if(!empty($filters['nama']) || ($filters['biaya'] ?? '') !== '')
            | Filter:
            @if(!empty($filters['nama'])) Nama = {{ $filters['nama'] }} @endif
            @if(($filters['biaya'] ?? '') !== '') Biaya = {{ $filters['biaya'] }} @endif
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 48px;">No</th>
                <th>Nama Jasa</th>
                <th style="width: 170px;" class="num">Biaya</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item->nama }}</td>
                    <td class="num">Rp {{ number_format((float) $item->biaya, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="text-align:center; color:#6b7280;">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
