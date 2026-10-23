@extends('layouts.pdf-modifikasi')

@section('pdf_title', 'Permintaan Kas Kecil Speedshop')

@section('content')
@php
    $tz = config('app.timezone');
    $hasCatatanCol = $hasCatatanCol ?? false;
    $colCount = 7 + ($hasCatatanCol ? 1 : 0);
    $statusLabel = fn (?string $status) => match ((string) $status) {
        'completed' => 'Disetujui',
        'rejected' => 'Ditolak',
        default => 'Menunggu',
    };
    $verifikasiLabel = function ($row) use ($tz) {
        if ($row->status === 'completed') {
            return ($row->approver?->name ?? 'Accounting').' · '.($row->approved_at?->timezone($tz)->translatedFormat('j M Y, H:i') ?? '—');
        }
        if ($row->status === 'rejected') {
            $label = ($row->approver?->name ?? 'Accounting').' · '.($row->rejected_at?->timezone($tz)->translatedFormat('j M Y, H:i') ?? '—');
            if (filled($row->rejected_reason)) {
                $label .= ' — '.$row->rejected_reason;
            }

            return $label;
        }

        return '—';
    };
    $metaLines = array_filter([
        'Periode: '.($periodeLabel ?? '—'),
        'Total: '.number_format((int) ($stats['total'] ?? 0), 0, ',', '.'),
        'Menunggu: '.number_format((int) ($stats['pending'] ?? 0), 0, ',', '.'),
        'Disetujui: '.number_format((int) ($stats['completed'] ?? 0), 0, ',', '.'),
        'Ditolak: '.number_format((int) ($stats['rejected'] ?? 0), 0, ',', '.'),
        ! empty($filters['status']) ? 'Status: '.$statusLabel($filters['status']) : null,
        ! empty($filters['tanggal_jurnal']) ? 'Tanggal jurnal: '.$filters['tanggal_jurnal'] : null,
        ! empty($filters['nominal']) ? 'Nominal: Rp '.number_format((int) $filters['nominal'], 0, ',', '.') : null,
        ! empty($filters['dicatat_oleh']) ? 'Pemohon: '.$filters['dicatat_oleh'] : null,
    ]);
    $totalNominal = (int) collect($rows ?? [])->sum(fn ($r) => (int) ($r->nominal ?? 0));
@endphp
@include('speedshop.partials.pdf-list-header', [
    'listTitle' => 'Permintaan Kas Kecil',
    'metaLines' => $metaLines,
    'printedAt' => $printedAt ?? now(),
])

<div class="table-section">
    <div class="table-title">Daftar Permintaan Kas Kecil</div>
    <table>
        <thead>
            <tr>
                <th class="center" style="width: 28px;">No.</th>
                <th style="width: 90px;">Tgl jurnal</th>
                <th class="center" style="width: 80px;">Status</th>
                <th class="right" style="width: 90px;">Nominal</th>
                <th style="width: 110px;">Diajukan</th>
                <th>Pemohon</th>
                @if($hasCatatanCol)
                <th>Alasan / catatan</th>
                @endif
                <th>Verifikasi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $i => $row)
            <tr>
                <td class="text-center muted" style="font-size:11px;">{{ $i + 1 }}</td>
                <td>{{ $row->tanggal?->timezone($tz)->translatedFormat('j M Y') ?? '—' }}</td>
                <td class="text-center">{{ $statusLabel($row->status ?? null) }}</td>
                <td class="right mono">Rp {{ number_format((int) ($row->nominal ?? 0), 0, ',', '.') }}</td>
                <td>{{ $row->created_at?->timezone($tz)->translatedFormat('j M Y, H:i') ?? '—' }}</td>
                <td>{{ $row->requester?->name ?? '—' }}</td>
                @if($hasCatatanCol)
                <td>{{ filled($row->catatan) ? $row->catatan : '—' }}</td>
                @endif
                <td>{{ $verifikasiLabel($row) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="{{ $colCount }}" class="text-center muted">Belum ada permintaan kas kecil pada filter ini.</td>
            </tr>
            @endforelse
            @if(($rows ?? collect())->isNotEmpty())
            <tr>
                <td colspan="3" class="right fw-600">Total nominal</td>
                <td class="right mono fw-600">Rp {{ number_format($totalNominal, 0, ',', '.') }}</td>
                <td colspan="{{ 3 + ($hasCatatanCol ? 1 : 0) }}"></td>
            </tr>
            @endif
        </tbody>
    </table>
</div>

<div class="doc-footer">
    <p>77racing Speedshop — {{ now()->year }}</p>
</div>
@endsection
