@extends('layouts.pdf-modifikasi')

@section('pdf_title', 'Rekap Pendapatan Speedshop')

@push('pdf_head')
<style>
  @page { margin: 8mm; size: A4 landscape; }
  .doc-body { padding: 18px 22px 24px; }
  .doc-body table thead th { padding: 6px 4px; font-size: 7px; }
  .doc-body table tbody td { padding: 5px 4px; font-size: 8.5px; vertical-align: middle; }
  .doc-body table .right { text-align: right; }
  .doc-body table .center { text-align: center; }
  .doc-body table .muted { color: #757575; }
</style>
@endpush

@section('content')
@php
    $fmt = fn ($v) => number_format((float) $v, 0, ',', '.');
    $fmtSigned = fn ($v) => ($v < 0 ? '-' : '').$fmt(abs((float) $v));
    $dailyReports = collect($dailyReports ?? []);
@endphp
@include('speedshop.partials.pdf-list-header', [
    'listTitle' => 'Rekap Pendapatan',
    'metaLines' => $metaLines ?? [],
    'printedAt' => $printedAt ?? now(),
])

<div class="table-section">
    <div class="table-title">Total pendapatan {{ $speedshopLabel ?? 'SPEEDSHOP' }}</div>
    <table>
        <thead>
            <tr>
                <th class="center" rowspan="2" style="width: 24px;">No</th>
                <th class="center" rowspan="2" style="width: 72px;">Tanggal</th>
                <th class="center" rowspan="2" style="width: 88px;">Keterangan</th>
                <th class="center" colspan="2">Pendapatan kotor</th>
                <th class="center" colspan="2">Biaya operasional</th>
                <th class="center" colspan="2">Gross profit</th>
                <th class="center" colspan="2">Total gross profit before HPP</th>
            </tr>
            <tr>
                <th class="center" style="width: 20px;"></th>
                <th class="center" style="width: 56px;">Nilai</th>
                <th class="center" style="width: 20px;"></th>
                <th class="center" style="width: 56px;">Nilai</th>
                <th class="center" style="width: 20px;"></th>
                <th class="center" style="width: 56px;">Nilai</th>
                <th class="center" style="width: 20px;"></th>
                <th class="center" style="width: 56px;">Nilai</th>
            </tr>
        </thead>
        <tbody>
            @forelse($dailyReports as $dayIdx => $day)
                @foreach($day->rows as $rowIdx => $row)
                @php $biayaTotal = (float) ($row->biaya_total ?? 0); @endphp
                <tr>
                    <td class="center">{{ $rowIdx === 0 ? ($dayIdx + 1) : '' }}</td>
                    <td class="center">{{ $rowIdx === 0 ? \Carbon\Carbon::parse($day->tanggal)->locale('id')->translatedFormat('d F Y') : '' }}</td>
                    <td>{{ $row->label }}</td>
                    <td class="center muted">{{ $rowIdx === 0 ? 'Rp' : '' }}</td>
                    <td class="right mono">{{ $rowIdx === 0 ? $fmt($day->pendapatan_kotor) : '' }}</td>
                    <td class="center muted">{{ $biayaTotal > 0 ? 'Rp' : '' }}</td>
                    <td class="right mono">{{ $biayaTotal > 0 ? $fmt($biayaTotal) : '' }}</td>
                    <td class="center muted">Rp</td>
                    <td class="right mono">{{ $fmtSigned($row->gross_profit) }}</td>
                    <td class="center muted">{{ $rowIdx === 0 ? 'Rp' : '' }}</td>
                    <td class="right mono fw-600">{{ $rowIdx === 0 ? $fmtSigned($day->total_gross_profit_before_hpp) : '' }}</td>
                </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="11" class="text-center muted">Tidak ada data pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
