<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Master Data Role</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111827; }
        .title { font-size: 16px; font-weight: bold; margin-bottom: 4px; }
        .meta { font-size: 10px; color: #4b5563; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 6px 8px; vertical-align: top; }
        th { background: #f3f4f6; text-align: left; font-size: 9px; text-transform: uppercase; }
    </style>
</head>
<body>
    <div class="title">Master Data Role</div>
    <div class="meta">
        Dicetak: {{ $printedAt->format('d-m-Y H:i') }}
        @if(!empty($filters['section']) || !empty($filters['role']))
            | Filter:
            @if(!empty($filters['section'])) Divisi = {{ $filters['section'] }} @endif
            @if(!empty($filters['role'])) Role = {{ $filters['role'] }} @endif
        @endif
        | Total: {{ $rows->count() }} role
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 32px;">No</th>
                <th style="width: 100px;">Divisi</th>
                <th>Role</th>
                <th style="width: 88px;">Jumlah Karyawan</th>
                <th style="width: 72px;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $row['section_label'] }}</td>
                    <td>{{ $row['role'] }}</td>
                    <td>{{ number_format($row['karyawan_count']) }}</td>
                    <td>{{ $row['in_catalog'] ? 'Aktif' : 'Legacy' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; color: #6b7280;">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
