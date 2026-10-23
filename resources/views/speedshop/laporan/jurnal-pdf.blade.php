@extends('layouts.pdf-modifikasi')

@section('pdf_title', 'Jurnal Speedshop')

@push('pdf_head')
<style>
  @page { margin: 8mm; size: A4 landscape; }
  .doc-body { padding: 18px 22px 24px; }
  .doc-body table thead th { padding: 6px 4px; font-size: 7.5px; }
  .doc-body table tbody td { padding: 5px 4px; font-size: 8.5px; vertical-align: top; }
  .doc-body table .right { text-align: right; }
  .doc-body table .center { text-align: center; }
  .doc-body table .muted { color: #757575; }
  .doc-body table .highlight { background: #FEF3C7; }
</style>
@endpush

@section('content')
@php
    $fmt = fn ($v) => number_format((float) $v, 0, ',', '.');
    $rows = collect($rows ?? []);
@endphp
@include('speedshop.partials.pdf-list-header', [
    'listTitle' => $listTitle ?? 'Jurnal',
    'metaLines' => $metaLines ?? [],
    'printedAt' => $printedAt ?? now(),
])

@if(!empty($titleLine1) || !empty($titleLine2))
<div style="margin-bottom: 10px;">
    @if(!empty($titleLine1))
        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase;">{{ $titleLine1 }}</div>
    @endif
    @if(!empty($titleLine2))
        <div style="font-size: 10px; font-weight: 600; color: #616161; margin-top: 2px;">{{ $titleLine2 }}</div>
    @endif
</div>
@endif

<div class="table-section">
    <div class="table-title">Daftar jurnal · {{ $speedshopLabel ?? 'SPEEDSHOP' }}</div>
    <table>
        <thead>
            <tr>
                <th class="center" style="width: 24px;">No</th>
                <th class="center" style="width: 72px;">Tanggal</th>
                <th style="width: 88px;">Akun</th>
                <th>Keterangan</th>
                <th style="width: 64px;">PIC</th>
                <th class="center" colspan="2">Kredit</th>
                <th class="center" colspan="2">Debet</th>
                <th class="center" colspan="2">Total (Rp)</th>
            </tr>
            <tr>
                <th colspan="5"></th>
                <th class="center muted" style="width: 16px;"></th>
                <th class="center" style="width: 56px;">Nilai</th>
                <th class="center muted" style="width: 16px;"></th>
                <th class="center" style="width: 56px;">Nilai</th>
                <th class="center muted" style="width: 16px;"></th>
                <th class="center" style="width: 56px;">Saldo</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
            @php $isSaldoAkhir = strtoupper(trim((string) ($row->akun ?? ''))) === 'SALDO AKHIR'; @endphp
            <tr @class(['highlight' => $isSaldoAkhir])>
                <td class="center">{{ $row->no }}</td>
                <td class="center">{{ \Carbon\Carbon::parse($row->tanggal)->format('d M Y') }}</td>
                <td>{{ $row->akun }}</td>
                <td>{{ $row->keterangan ?? '—' }}</td>
                <td>{{ $row->pic ?? '—' }}</td>
                <td class="center muted">{{ ($row->kredit ?? 0) > 0 ? 'Rp' : '' }}</td>
                <td class="right mono">{{ ($row->kredit ?? 0) > 0 ? $fmt($row->kredit) : '' }}</td>
                <td class="center muted">{{ ($row->debet ?? 0) > 0 ? 'Rp' : '' }}</td>
                <td class="right mono">{{ ($row->debet ?? 0) > 0 ? $fmt($row->debet) : '' }}</td>
                <td class="center muted">Rp</td>
                <td class="right mono fw-600">{{ $fmt($row->saldo ?? 0) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="11" class="text-center muted">Belum ada data jurnal pada periode ini.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
