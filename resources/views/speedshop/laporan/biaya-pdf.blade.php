@extends('layouts.pdf-modifikasi')

@section('pdf_title', 'Laporan Biaya Speedshop')

@push('pdf_head')
<style>
  @page { margin: 8mm; size: A4 landscape; }
  .doc-body { padding: 18px 22px 24px; }
  .doc-body table thead th { padding: 6px 5px; font-size: 7.5px; }
  .doc-body table tbody td { padding: 6px 5px; font-size: 9px; vertical-align: middle; }
  .doc-body table .right { text-align: right; }
  .doc-body table .center { text-align: center; }
</style>
@endpush

@section('content')
@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', '.');
    $rows = [
        [
            'jenis' => 'Real time',
            'tanggal' => ($realtimeDate ?? now())->format('d/m/y'),
            'data' => $realtime ?? [],
        ],
        [
            'jenis' => 'Bulan berjalan (s/d)',
            'tanggal' => ($dari ?? now())->format('d M Y').' – '.($sampai ?? now())->format('d M Y'),
            'data' => $periode ?? [],
        ],
    ];
@endphp
@include('speedshop.partials.pdf-list-header', [
    'listTitle' => 'Laporan Pendapatan & Biaya',
    'metaLines' => $metaLines ?? [],
    'printedAt' => $printedAt ?? now(),
])

<div class="table-section">
    <div class="table-title">Laporan harian pendapatan &amp; biaya</div>
    <table>
        <thead>
            <tr>
                <th class="center" rowspan="2" style="width: 72px;">Jenis data</th>
                <th class="center" rowspan="2" style="width: 88px;">Tanggal / periode</th>
                <th class="center" rowspan="2" style="width: 72px;">Total omset kotor</th>
                <th class="center" rowspan="2" style="width: 48px;">Unit entry</th>
                <th class="center" colspan="5">Sparepart</th>
                <th class="center" rowspan="2" style="width: 56px;">Profit</th>
                <th class="center" colspan="2">BOP</th>
                <th class="center" rowspan="2" style="width: 64px;">Gross profit</th>
            </tr>
            <tr>
                <th class="center" style="width: 56px;">Pendapatan jasa</th>
                <th class="center" style="width: 48px;">Part</th>
                <th class="center" style="width: 48px;">HPP part</th>
                <th class="center" style="width: 48px;">Profit part</th>
                <th class="center" style="width: 44px;">Diskon</th>
                <th class="center" style="width: 52px;">Operasional</th>
                <th class="center" style="width: 52px;">Non Operasional</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
            @php $data = $row['data']; @endphp
            <tr>
                <td class="center">{{ $row['jenis'] }}</td>
                <td class="center">{{ $row['tanggal'] }}</td>
                <td class="right mono">Rp {{ $fmt($data['omzet_kotor'] ?? 0) }}</td>
                <td class="center mono">{{ $data['unit_entry'] ?? 0 }}</td>
                <td class="right mono">Rp {{ $fmt($data['jasa'] ?? 0) }}</td>
                <td class="right mono">Rp {{ $fmt($data['part'] ?? 0) }}</td>
                <td class="right mono">Rp {{ $fmt($data['hpp_part'] ?? 0) }}</td>
                <td class="right mono">Rp {{ $fmt($data['profit_part'] ?? 0) }}</td>
                <td class="right mono">Rp {{ $fmt($data['diskon'] ?? 0) }}</td>
                <td class="right mono fw-600">Rp {{ $fmt($data['profit'] ?? 0) }}</td>
                <td class="right mono">Rp {{ $fmt($data['bop_operasional'] ?? 0) }}</td>
                <td class="right mono">Rp {{ $fmt($data['bop_non_operasional'] ?? 0) }}</td>
                <td class="right mono fw-600">Rp {{ $fmt($data['profit_bersih'] ?? 0) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
