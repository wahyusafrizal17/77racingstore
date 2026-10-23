@extends('layouts.pdf-modifikasi')

@section('pdf_title', 'Pengembalian Dana BOP Speedshop')

@section('content')
@php
    $tz = config('app.timezone');
    $metaLines = array_filter([
        'Periode: '.($periodeLabel ?? '—'),
        'Penyesuaian: '.number_format((int) ($stats['count'] ?? 0), 0, ',', '.'),
        'Total pengurangan: Rp '.number_format((int) ($stats['total_pengurangan'] ?? 0), 0, ',', '.'),
    ]);
    $totalPengurangan = (int) collect($rows ?? [])->sum(fn ($r) => (int) ($r->nominal_pengurangan ?? 0));
    $totalAdmin = (int) collect($rows ?? [])->sum(fn ($r) => (int) ($r->biaya_admin ?? 0));
@endphp
@include('speedshop.partials.pdf-list-header', [
    'listTitle' => 'Pengembalian Dana BOP',
    'metaLines' => $metaLines,
    'printedAt' => $printedAt ?? now(),
])

<div class="table-section">
    <div class="table-title">Riwayat Penyesuaian BOP</div>
    <table>
        <thead>
            <tr>
                <th class="center" style="width: 28px;">No.</th>
                <th style="width: 72px;">Tgl refund</th>
                <th style="width: 72px;">Tgl BOP</th>
                <th style="width: 140px;">BOP</th>
                <th class="right" style="width: 80px;">Pengurangan</th>
                <th class="right" style="width: 70px;">Admin</th>
                <th class="right" style="width: 80px;">BOP efektif</th>
                <th>Catatan</th>
                <th style="width: 72px;">Oleh</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $i => $adj)
            @php
                $entry = $adj->rincianBiayaEntry;
            @endphp
            <tr>
                <td class="text-center muted" style="font-size:11px;">{{ $i + 1 }}</td>
                <td>{{ $adj->tanggal_pengembalian?->timezone($tz)->translatedFormat('j M Y') ?? '—' }}</td>
                <td>{{ $entry?->tanggal?->timezone($tz)->translatedFormat('j M Y') ?? '—' }}</td>
                <td>
                    <span class="mono fw-600">{{ $entry?->no_akun ?? '—' }}</span>
                    @if($entry?->nama_akun)
                    <br><span class="muted">{{ $entry->nama_akun }}</span>
                    @endif
                    @if($entry?->keterangan)
                    <br><span class="muted">{{ $entry->keterangan }}</span>
                    @endif
                </td>
                <td class="right mono">Rp {{ number_format((int) $adj->nominal_pengurangan, 0, ',', '.') }}</td>
                <td class="right mono">{{ (int) $adj->biaya_admin > 0 ? 'Rp '.number_format((int) $adj->biaya_admin, 0, ',', '.') : '—' }}</td>
                <td class="right mono fw-600">Rp {{ number_format($entry ? (int) $entry->nominalEfektif() : 0, 0, ',', '.') }}</td>
                <td>{{ filled($adj->catatan) ? $adj->catatan : '—' }}</td>
                <td>{{ $adj->createdByUser?->name ?? '—' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="text-center muted">Belum ada penyesuaian BOP pada filter ini.</td>
            </tr>
            @endforelse
            @if(($rows ?? collect())->isNotEmpty())
            <tr>
                <td colspan="4" class="right fw-600">Total</td>
                <td class="right mono fw-600">Rp {{ number_format($totalPengurangan, 0, ',', '.') }}</td>
                <td class="right mono fw-600">{{ $totalAdmin > 0 ? 'Rp '.number_format($totalAdmin, 0, ',', '.') : '—' }}</td>
                <td colspan="3"></td>
            </tr>
            @endif
        </tbody>
    </table>
</div>

<div class="doc-footer">
    <p>77racing Speedshop — {{ now()->year }}</p>
</div>
@endsection
