@extends('layouts.pdf-modifikasi')

@section('pdf_title', 'Tagihan PO Speedshop')

@section('content')
@php
    $statusLabel = fn (?string $status) => \App\Models\SpeedshopPoTagihan::statusLabel((string) $status);
    $metaLines = array_filter([
        'Periode: '.($periodeLabel ?? '—'),
        'Total: '.number_format((int) ($stats['total'] ?? 0), 0, ',', '.'),
        'Belum lunas: '.number_format((int) ($stats['belum_lunas'] ?? 0), 0, ',', '.'),
        'Menunggu konfirmasi: '.number_format((int) ($stats['menunggu_konfirmasi'] ?? 0), 0, ',', '.'),
        'Lunas: '.number_format((int) ($stats['lunas'] ?? 0), 0, ',', '.'),
        ! empty($filters['status']) ? 'Status: '.$statusLabel($filters['status']) : null,
        ! empty($filters['kode']) ? 'Filter tagihan: '.$filters['kode'] : null,
        ! empty($filters['counterparty']) ? 'Filter warehouse: '.$filters['counterparty'] : null,
        ! empty($filters['tanggal_tagihan']) ? 'Tanggal tagihan: '.$filters['tanggal_tagihan'] : null,
    ]);
    $totalNominal = (int) collect($rows ?? [])->sum(fn ($r) => (int) ($r->total_nominal ?? 0));
    $tz = config('app.timezone');
@endphp
@include('speedshop.partials.pdf-list-header', [
    'listTitle' => 'Tagihan PO ke Warehouse',
    'metaLines' => $metaLines,
    'printedAt' => $printedAt ?? now(),
])

<div class="table-section">
    <div class="table-title">Daftar Tagihan PO</div>
    <table>
        <thead>
            <tr>
                <th class="center" style="width: 28px;">No.</th>
                <th>No. Tagihan</th>
                <th style="width: 80px;">PO</th>
                <th>Warehouse</th>
                <th style="width: 80px;">Tanggal</th>
                <th class="right" style="width: 90px;">Total</th>
                <th class="center" style="width: 90px;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $i => $row)
            <tr>
                <td class="text-center muted" style="font-size:11px;">{{ $i + 1 }}</td>
                <td class="fw-600">{{ $row->kode ?? '—' }}</td>
                <td>{{ $row->purchaseOrder?->kode ?? '—' }}</td>
                <td>{{ $row->warehouse?->nama ?? '—' }}</td>
                <td>{{ $row->tanggal_tagihan?->timezone($tz)->translatedFormat('j M Y') ?? '—' }}</td>
                <td class="right mono">Rp {{ number_format((int) ($row->total_nominal ?? 0), 0, ',', '.') }}</td>
                <td class="text-center">{{ $statusLabel($row->status ?? null) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center muted">Belum ada tagihan PO pada filter ini.</td>
            </tr>
            @endforelse
            @if(($rows ?? collect())->isNotEmpty())
            <tr>
                <td colspan="5" class="right fw-600">Total nominal</td>
                <td class="right mono fw-600">Rp {{ number_format($totalNominal, 0, ',', '.') }}</td>
                <td></td>
            </tr>
            @endif
        </tbody>
    </table>
</div>

<div class="doc-footer">
    <p>77racing Speedshop — {{ now()->year }}</p>
</div>
@endsection
