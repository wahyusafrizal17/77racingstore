@extends('layouts.pdf-modifikasi')

@section('pdf_title', 'Biaya Operasional Speedshop')

@push('pdf_head')
<style>
  @page { margin: 8mm; size: A4 landscape; }
  .doc-body { padding: 18px 22px 24px; }
  .doc-body table thead th { padding: 7px 8px; font-size: 8.5px; }
  .doc-body table tbody td { padding: 7px 8px; font-size: 10px; }
</style>
@endpush

@section('content')
@php
    $approvalLabel = match ($filters['approval_status'] ?? '') {
        'pending' => 'Menunggu verifikasi',
        'approved' => 'Tercatat / disetujui',
        'rejected' => 'Ditolak',
        default => 'Semua status',
    };
    $metaLines = array_filter([
        'Periode: '.($periodeLabel ?? '—'),
        'Status filter: '.$approvalLabel,
        'Menunggu verifikasi: '.number_format((int) ($stats['pending'] ?? 0), 0, ',', '.'),
        'Tercatat / disetujui: '.number_format((int) ($stats['disetujui'] ?? 0), 0, ',', '.'),
        'Total biaya: Rp '.number_format((int) ($stats['total_nominal'] ?? 0), 0, ',', '.'),
    ]);
    $totalTunai = (int) collect($rows ?? [])->where('counts_toward_total', true)->sum(fn ($r) => (int) ($r->tunai ?? 0));
    $totalTransfer = (int) collect($rows ?? [])->where('counts_toward_total', true)->sum(fn ($r) => (int) ($r->transfer ?? 0));
    $totalBiaya = (int) collect($rows ?? [])->where('counts_toward_total', true)->sum(fn ($r) => (int) ($r->total ?? 0));
@endphp
@include('speedshop.partials.pdf-list-header', [
    'listTitle' => 'Biaya Operasional',
    'metaLines' => $metaLines,
    'printedAt' => $printedAt ?? now(),
])

<div class="table-section">
    <div class="table-title">Rincian Biaya Operasional</div>
    <table>
        <thead>
            <tr>
                <th style="width: 72px;">Tanggal</th>
                <th style="width: 100px;">Jenis</th>
                <th style="width: 140px;">Akun</th>
                <th>Keterangan</th>
                <th style="width: 72px;">Pemohon</th>
                <th style="width: 72px;">Pengaju</th>
                <th class="right" style="width: 80px;">Tunai</th>
                <th class="right" style="width: 80px;">Transfer</th>
                <th class="right" style="width: 88px;">Total Biaya</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
            @php
                $akunLabel = trim(($row->no_akun ?? '').' - '.($row->nama_akun ?? ''), ' -');
                $akunLabel = $akunLabel !== '' ? $akunLabel : '—';
            @endphp
            <tr>
                <td>{{ $row->tanggal_label ?? '—' }}</td>
                <td>{{ $row->jenis_label ?? '—' }}</td>
                <td class="mono" style="font-size:11px;">{{ $akunLabel }}</td>
                <td>{{ $row->keterangan ?? '—' }}</td>
                <td>{{ $row->pic ?? '—' }}</td>
                <td>{{ $row->pengaju ?? '—' }}</td>
                <td class="right mono">{{ (int) ($row->tunai ?? 0) > 0 ? 'Rp '.number_format((int) $row->tunai, 0, ',', '.') : '—' }}</td>
                <td class="right mono">{{ (int) ($row->transfer ?? 0) > 0 ? 'Rp '.number_format((int) $row->transfer, 0, ',', '.') : '—' }}</td>
                <td class="right mono fw-600">{{ (int) ($row->total ?? 0) > 0 ? 'Rp '.number_format((int) $row->total, 0, ',', '.') : '—' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="text-center muted">Belum ada data biaya pada filter ini.</td>
            </tr>
            @endforelse
            @if(($rows ?? collect())->isNotEmpty())
            <tr>
                <td colspan="6" class="right fw-600">Total tercatat</td>
                <td class="right mono fw-600">Rp {{ number_format($totalTunai, 0, ',', '.') }}</td>
                <td class="right mono fw-600">Rp {{ number_format($totalTransfer, 0, ',', '.') }}</td>
                <td class="right mono fw-600">Rp {{ number_format($totalBiaya, 0, ',', '.') }}</td>
            </tr>
            @endif
        </tbody>
    </table>
</div>

<div class="doc-footer">
    <p>77racing Speedshop — {{ now()->year }}</p>
</div>
@endsection
