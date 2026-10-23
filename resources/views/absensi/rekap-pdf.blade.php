<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Absensi {{ $calendarMonth->translatedFormat('F Y') }}</title>
    <style>
        @page { margin: 12mm 10mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #111827; }
        h1 { font-size: 14px; margin: 0 0 2px; }
        .sub { font-size: 9px; color: #6b7280; margin-bottom: 8px; }
        .summary { margin-bottom: 8px; font-size: 8px; }
        .summary strong { color: #111827; }
        table.grid {
            border-collapse: collapse;
            width: 100%;
            table-layout: fixed;
        }
        table.grid th, table.grid td {
            border: 0.5px solid #9ca3af;
            padding: 1px 1px;
            text-align: center;
            vertical-align: middle;
            font-size: 6px;
            word-wrap: break-word;
        }
        table.grid .head-blue { background: #4472c4; color: #fff; font-weight: 700; font-size: 8px; }
        table.grid .head-day { background: #fff; font-weight: 700; width: 18px; }
        table.grid .head-dow { background: #fff; font-size: 5.5px; text-transform: capitalize; }
        table.grid .col-sunday { background: #ff0000 !important; color: #fff !important; }
        table.grid .group-row td { background: #bfbfbf; font-weight: 700; letter-spacing: .04em; padding: 3px; }
        table.grid .name-col { text-align: left; padding: 2px 4px; font-weight: 700; font-size: 6.5px; width: 90px; }
        table.grid .no-col { width: 16px; font-weight: 700; }
        .cell-hadir, .cell-late, .cell-alpha, .cell-pending, .cell-libur, .cell-izin, .cell-sakit, .cell-cuti, .cell-wfh, .cell-dinas, .cell-sunday, .cell-off, .cell-future {
            height: 28px;
        }
        .cell-hadir { background: #86efac; color: #14532d; font-weight: 700; }
        .cell-late { background: #fcd34d; color: #78350f; font-weight: 700; }
        .cell-alpha { background: #fca5a5; color: #7f1d1d; font-weight: 700; }
        .cell-pending { background: #fdba74; color: #7c2d12; font-weight: 700; }
        .cell-libur { background: #ffc000; color: #111; font-weight: 700; font-size: 5px; }
        .cell-izin { background: #c7d2fe; color: #312e81; font-weight: 700; font-size: 5px; }
        .cell-sakit { background: #fbcfe8; color: #9d174d; font-weight: 700; font-size: 5px; }
        .cell-cuti { background: #a5f3fc; color: #155e75; font-weight: 700; font-size: 5px; }
        .cell-wfh { background: #ddd6fe; color: #5b21b6; font-weight: 700; font-size: 5px; }
        .cell-dinas { background: #7dd3fc; color: #0c4a6e; font-weight: 700; font-size: 5px; }
        .cell-sunday { background: #ff0000; }
        .cell-off { background: #f3f4f6; }
        .cell-future { background: #fff; }
        .cell-code { display: block; font-size: 6.5px; font-weight: 700; }
        .cell-time { display: block; font-size: 5.2px; font-weight: 700; line-height: 1.15; }
        .legend { margin-top: 8px; font-size: 7px; color: #4b5563; }
        .footer { margin-top: 6px; font-size: 7px; color: #9ca3af; }
    </style>
</head>
<body>
@php
    $dayCount = count($monthDays);
    $scopeLabel = $scopeLabel ?? (($seesAllEmployees ?? $isOwner ?? false) ? 'Semua karyawan' : 'Bawahan di bawah tanggung jawab Anda');
    $totalCodes = \App\Services\AttendanceScheduleService::RECAP_TOTAL_CODES;
    $totalLabels = ['H' => 'H', 'T' => 'T', 'A' => 'A', 'IZIN' => 'Iz', 'SAKIT' => 'Sk', 'CUTI' => 'Ct', 'WFH' => 'WFH', 'DINAS' => 'Dn', 'P' => 'P'];
    $gridColspan = 2 + $dayCount + count($totalCodes);
@endphp

<h1>Rekap Absensi · {{ $calendarMonth->translatedFormat('F Y') }}</h1>
<div class="sub">{{ $scopeLabel }}@if(!empty($nameFilter)) · Filter: {{ $nameFilter }}@endif</div>
<div class="summary">
    Hadir <strong>{{ number_format($summary['present']) }}</strong>
    · Telat <strong>{{ number_format($summary['late']) }}</strong>
    · Tidak hadir <strong>{{ number_format($summary['absent']) }}</strong>
    · Pending <strong>{{ number_format($summary['pending']) }}</strong>
    · Libur/Dinas <strong>{{ number_format($summary['off_days']) }}</strong>
</div>

@if($displayGroups->isEmpty())
<p>Belum ada data karyawan pada filter ini.</p>
@else
<table class="grid">
    <thead>
        <tr>
            <th class="head-blue no-col">No</th>
            <th class="head-blue name-col">Nama</th>
            <th colspan="{{ $dayCount }}" class="head-blue">{{ $calendarMonth->translatedFormat('F Y') }}</th>
            <th colspan="{{ count($totalCodes) }}" class="head-blue">Total</th>
        </tr>
        <tr>
            <th class="head-day"></th>
            <th class="head-day"></th>
            @foreach($monthDays as $d)
            <th class="head-day @if($d['is_sunday']) col-sunday @endif">{{ $d['day'] }}</th>
            @endforeach
            @foreach($totalCodes as $code)
            <th class="head-day">{{ $totalLabels[$code] }}</th>
            @endforeach
        </tr>
        <tr>
            <th class="head-dow"></th>
            <th class="head-dow"></th>
            @foreach($monthDays as $d)
            <th class="head-dow @if($d['is_sunday']) col-sunday @endif">{{ $d['dow'] }}</th>
            @endforeach
            @foreach($totalCodes as $code)
            <th class="head-dow"></th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($displayGroups as $group)
        <tr class="group-row">
            <td colspan="{{ $gridColspan }}">{{ $group['label'] }}</td>
        </tr>
        @foreach($group['employees'] as $idx => $emp)
        @php
            $empId = (int) $emp->id;
            $rowTotals = \App\Services\AttendanceScheduleService::recapStatusTotals($cells[$empId] ?? []);
        @endphp
        <tr>
            <td class="no-col">{{ $idx + 1 }}</td>
            <td class="name-col">{{ $emp->name }}</td>
            @foreach($monthDays as $d)
            @php
                $cell = $cells[$empId][$d['key']] ?? ['code' => '', 'class' => 'cell-future', 'in_time' => null, 'out_time' => null];
                $hasTimes = ! empty($cell['in_time']);
            @endphp
            <td class="{{ $cell['class'] }}">
                @if($hasTimes)
                <span class="cell-code">{{ $cell['code'] }}</span>
                <span class="cell-time">{{ $cell['in_time'] }}</span>
                <span class="cell-time">{{ $cell['out_time'] ?? '—' }}</span>
                @else
                {{ $cell['code'] }}
                @endif
            </td>
            @endforeach
            @foreach($totalCodes as $code)
            <td>{{ $rowTotals[$code] ?? 0 }}</td>
            @endforeach
        </tr>
        @endforeach
        @endforeach
    </tbody>
</table>
@endif

<div class="legend">
    H = Hadir · T = Telat · A = Tidak hadir · P = Pending · WFH · IZIN · SAKIT · CUTI · LIBUR · DINAS · Merah = Minggu · Abu = Non-kerja
</div>
<div class="footer">Dicetak {{ ($printedAt ?? now())->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</div>
</body>
</html>
