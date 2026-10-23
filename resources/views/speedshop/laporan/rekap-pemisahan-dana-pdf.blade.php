@extends('layouts.pdf-modifikasi')

@section('pdf_title', 'Rekap Pemisahan Dana Speedshop')

@push('pdf_head')
<style>
  @page { margin: 8mm; size: A4 landscape; }
  .doc-body { padding: 18px 22px 24px; }
  .doc-body table thead th { padding: 5px 3px; font-size: 6.5px; line-height: 1.25; }
  .doc-body table tbody td { padding: 4px 3px; font-size: 7.5px; vertical-align: middle; }
  .doc-body table .right { text-align: right; }
  .doc-body table .center { text-align: center; }
  .doc-body table .muted { color: #757575; }
  .doc-body table .total { color: #dc2626; font-weight: 700; font-style: italic; }
  .doc-body table .kategori-rowspan { vertical-align: middle; }
</style>
@endpush

@section('content')
@php
    $fmt = fn ($v) => number_format((int) $v, 0, ',', '.');
    $weekColumns = $weekColumns ?? [];
    $rows = $rows ?? [];
@endphp
@include('speedshop.partials.pdf-list-header', [
    'listTitle' => 'Rekap Pemisahan Dana',
    'metaLines' => $metaLines ?? [],
    'printedAt' => $printedAt ?? now(),
])

<div class="table-section">
    <div class="table-title">Rekap pemisahan dana {{ $speedshopLabel ?? 'SPEEDSHOP' }}</div>
    <table>
        <thead>
            <tr>
                <th class="center" rowspan="2" style="width: 22px;">No</th>
                <th class="center" rowspan="2" style="width: 90px;">Kategori</th>
                <th class="center" rowspan="2" style="width: 70px;">Pembagian</th>
                @foreach($weekColumns as $col)
                <th class="center" colspan="2">{{ $col['title'] }}<br><span class="muted">({{ $col['range_label'] }})</span></th>
                @endforeach
                <th class="center" colspan="2">Total</th>
            </tr>
            <tr>
                @foreach($weekColumns as $col)
                <th class="center">Nominal</th>
                <th class="center">Tgl. Bayar</th>
                @endforeach
                <th class="center">Nominal</th>
                <th class="center">Tgl. Bayar</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
            @php $subRows = $row['sub_rows'] ?? []; @endphp
            @if(count($subRows) > 0)
                @foreach($subRows as $i => $sub)
                <tr>
                    @if($i === 0)
                    <td class="center" rowspan="{{ count($subRows) }}">{{ $row['no'] }}</td>
                    <td class="kategori-rowspan" rowspan="{{ count($subRows) }}">
                        {{ $row['label'] }}
                        @if(!empty($row['rekening_suffix']))
                        <span class="muted">{{ $row['rekening_suffix'] }}</span>
                        @endif
                    </td>
                    @endif
                    <td><strong>{{ $sub['label'] }}</strong></td>
                    @foreach($weekColumns as $col)
                    @php $cell = $sub['cells'][$col['key']] ?? ['nominal' => 0, 'tanggal' => '—']; @endphp
                    <td class="right mono">{{ (int) ($cell['nominal'] ?? 0) > 0 ? $fmt($cell['nominal']) : '—' }}</td>
                    <td class="center muted">{{ $cell['tanggal'] ?? '—' }}</td>
                    @endforeach
                    @php $subTotal = $sub['cells']['total'] ?? ['nominal' => 0, 'tanggal' => '—']; @endphp
                    <td class="right mono total">{{ (int) ($subTotal['nominal'] ?? 0) > 0 ? $fmt($subTotal['nominal']) : '—' }}</td>
                    <td class="center muted">{{ $subTotal['tanggal'] ?? '—' }}</td>
                </tr>
                @endforeach
            @else
            <tr>
                <td class="center">{{ $row['no'] }}</td>
                <td>
                    {{ $row['label'] }}
                    @if(!empty($row['rekening_suffix']))
                    <span class="muted">{{ $row['rekening_suffix'] }}</span>
                    @endif
                </td>
                <td class="center muted">—</td>
                @foreach($weekColumns as $col)
                @php $cell = $row['cells'][$col['key']] ?? ['nominal' => 0, 'tanggal' => '—']; @endphp
                <td class="right mono">{{ (int) ($cell['nominal'] ?? 0) > 0 ? $fmt($cell['nominal']) : '—' }}</td>
                <td class="center muted">{{ $cell['tanggal'] ?? '—' }}</td>
                @endforeach
                @php $total = $row['cells']['total'] ?? ['nominal' => 0, 'tanggal' => '—']; @endphp
                <td class="right mono total">{{ (int) ($total['nominal'] ?? 0) > 0 ? $fmt($total['nominal']) : '—' }}</td>
                <td class="center muted">{{ $total['tanggal'] ?? '—' }}</td>
            </tr>
            @endif
            @empty
            <tr>
                <td colspan="{{ 3 + (count($weekColumns) * 2) + 2 }}" class="text-center muted">Tidak ada data pada periode ini.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
