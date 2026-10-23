@extends('layouts.pdf-modifikasi')

@section('pdf_title', 'Data Setoran Speedshop')

@section('content')
@php
    $tz = config('app.timezone');
    $statusLabel = fn ($row) => match (true) {
        $row->isPending() => 'Menunggu',
        $row->isRejected() => 'Ditolak',
        default => 'Selesai',
    };
    $metaLines = array_filter([
        'Periode: '.($periodeLabel ?? '—'),
        'Total: '.number_format((int) ($stats['total'] ?? 0), 0, ',', '.'),
        'Menunggu: '.number_format((int) ($stats['pending'] ?? 0), 0, ',', '.'),
        'Selesai: '.number_format((int) ($stats['completed'] ?? 0), 0, ',', '.'),
        'Ditolak: '.number_format((int) ($stats['rejected'] ?? 0), 0, ',', '.'),
        ! empty($filters['status']) ? 'Status: '.match ((string) $filters['status']) {
            'completed' => 'Selesai',
            'rejected' => 'Ditolak',
            default => 'Menunggu',
        } : null,
        ! empty($filters['tanggal_setor']) ? 'Tanggal setor: '.$filters['tanggal_setor'] : null,
        ! empty($filters['pengaju']) ? 'Pengaju: '.$filters['pengaju'] : null,
        ! empty($filters['rekening']) ? 'Rekening: '.$filters['rekening'] : null,
    ]);
    $totalNominal = (int) collect($rows ?? [])->sum(fn ($r) => $r->totalNominalSetor());
@endphp
@include('speedshop.partials.pdf-list-header', [
    'listTitle' => 'Data Setoran Cabang',
    'metaLines' => $metaLines,
    'printedAt' => $printedAt ?? now(),
])

<div class="table-section">
    <div class="table-title">Daftar Setoran</div>
    <table>
        <thead>
            <tr>
                <th class="center" style="width: 34px;">No.</th>
                <th style="width: 100px;">Tgl setor</th>
                <th class="center" style="width: 80px;">Status</th>
                <th>Diajukan oleh</th>
                <th style="width: 120px;">Dicatat</th>
                <th class="right" style="width: 110px;">Nominal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $i => $row)
            @php
                $tgl = $row->tanggal_setor?->timezone($tz);
                $dicatat = $row->isPending() || $row->isRejected()
                    ? $row->created_at?->timezone($tz)
                    : ($row->completed_at?->timezone($tz) ?? $row->updated_at?->timezone($tz));
            @endphp
            <tr>
                <td class="text-center muted" style="font-size:11px;">{{ $i + 1 }}</td>
                <td>{{ $tgl?->translatedFormat('j M Y') ?? '—' }}</td>
                <td class="text-center">{{ $statusLabel($row) }}</td>
                <td>{{ $row->user?->name ?? '—' }}</td>
                <td>{{ $dicatat?->translatedFormat('j M Y, H:i') ?? '—' }}</td>
                <td class="right mono">Rp {{ number_format($row->totalNominalSetor(), 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center muted">Belum ada setoran pada filter ini.</td>
            </tr>
            @endforelse
            @if(($rows ?? collect())->isNotEmpty())
            <tr>
                <td colspan="5" class="right fw-600">Total nominal</td>
                <td class="right mono fw-600">Rp {{ number_format($totalNominal, 0, ',', '.') }}</td>
            </tr>
            @endif
        </tbody>
    </table>
</div>

<div class="doc-footer">
    <p>77racing Speedshop — {{ now()->year }}</p>
</div>
@endsection
