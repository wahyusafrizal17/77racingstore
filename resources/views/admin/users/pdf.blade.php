<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Master Data Karyawan</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111827; }
        .title { font-size: 16px; font-weight: bold; margin-bottom: 4px; }
        .meta { font-size: 10px; color: #4b5563; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 6px 8px; vertical-align: top; }
        th { background: #f3f4f6; text-align: left; font-size: 9px; text-transform: uppercase; }
        td.email { word-break: break-all; }
    </style>
</head>
<body>
    <div class="title">Master Data Karyawan</div>
    <div class="meta">
        Dicetak: {{ $printedAt->format('d-m-Y H:i') }}
        @if(!empty($filters['name']) || !empty($filters['email']) || !empty($filters['section']) || !empty($filters['role']))
            | Filter:
            @if(!empty($filters['name'])) Nama = {{ $filters['name'] }} @endif
            @if(!empty($filters['email'])) Email = {{ $filters['email'] }} @endif
            @if(!empty($filters['section'])) Divisi = {{ $filters['section'] }} @endif
            @if(!empty($filters['role'])) Role = {{ $filters['role'] }} @endif
        @endif
        | Total: {{ $users->count() }} pengguna
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 32px;">No</th>
                <th>Nama</th>
                <th style="width: 90px;">Username</th>
                <th style="width: 160px;">Email</th>
                <th style="width: 72px;">Divisi</th>
                <th style="width: 88px;">Role</th>
                <th style="width: 120px;">Cabang / Gudang</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $i => $u)
                @php
                    $lokasi = '—';
                    if ($u->relationLoaded('assignedSpeedshops') && $u->assignedSpeedshops->isNotEmpty()) {
                        $lokasi = $u->assignedSpeedshops->pluck('nama')->filter()->join(' · ');
                    } elseif ($u->isSpeedshopRegionalManager()) {
                        $lokasi = 'Semua Cabang';
                    } elseif (($u->section ?? '') === 'warehouse') {
                        $lokasi = $u->warehouse?->nama ?? 'Warehouse Katapang';
                    } elseif (($u->section ?? '') === 'produksi') {
                        $lokasi = 'Produksi Katapang';
                    } elseif ($u->speedshop_id) {
                        $lokasi = $u->speedshop?->nama ?? '—';
                    } elseif ($u->warehouse_id) {
                        $lokasi = $u->warehouse?->nama ?? '—';
                    }
                @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $u->name }}</td>
                    <td>{{ $u->username ?: '—' }}</td>
                    <td class="email">{{ $u->email }}</td>
                    <td>{{ $u->section }}</td>
                    <td>{{ $u->role }}</td>
                    <td>{{ $lokasi }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align:center; color:#6b7280;">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
