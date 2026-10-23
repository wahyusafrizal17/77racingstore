<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Mekanik</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        .title { font-size: 16px; font-weight: bold; margin-bottom: 4px; }
        .meta { font-size: 11px; color: #4b5563; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 8px; }
        th { background: #f3f4f6; text-align: left; font-size: 11px; text-transform: uppercase; }
    </style>
</head>
<body>
    <div class="title">Master Data Mekanik</div>
    <div class="meta">
        Dicetak: {{ $printedAt->format('d-m-Y H:i') }}
        @if(!empty($filters['nama']) || !empty($filters['no_hp']) || !empty($filters['level']))
            | Filter:
            @if(!empty($filters['nama'])) Nama = {{ $filters['nama'] }} @endif
            @if(!empty($filters['no_hp'])) No HP = {{ $filters['no_hp'] }} @endif
            @if(!empty($filters['level'])) Level = {{ $filters['level'] }} @endif
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 48px;">No</th>
                <th>Nama</th>
                <th style="width: 170px;">No HP</th>
                <th style="width: 220px;">Level</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item->nama }}</td>
                    <td>{{ $item->no_hp ?: '-' }}</td>
                    <td>{{ $item->spesialisasi ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align:center; color:#6b7280;">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

