@extends('layouts.pdf-modifikasi')

@section('pdf_title', 'Laporan Laba Rugi Speedshop')

@push('pdf_head')
<style>
  @page { margin: 10mm; size: A4 portrait; }
  .doc-body { padding: 18px 22px 24px; }
  .doc-body table thead th { padding: 7px 8px; font-size: 8.5px; }
  .doc-body table tbody td { padding: 6px 8px; font-size: 10px; vertical-align: middle; }
  .lr-row-light { background: #D9E1F2; }
  .lr-row-total { background: #B4C6E7; font-weight: 700; }
  .lr-row-footer { background: #E31837; color: #fff; font-weight: 700; }
  .lr-row-footer td { color: #fff; }
  .lr-indent-1 { padding-left: 14px !important; }
  .lr-indent-2 { padding-left: 24px !important; }
  .lr-indent-3 { padding-left: 34px !important; }
</style>
@endpush

@section('content')
@php
    use App\Support\SpeedshopLabaRugiFinancials;

    $fmt = fn ($n) => number_format((float) $n, 0, ',', '.');
    $fmtPct = fn ($n) => number_format((float) $n, 2, ',', '.').'%';

    $rowStyle = function (string $kind): string {
        return match ($kind) {
            'section', 'sub', 'line', 'pct' => 'lr-row-light',
            'subtotal', 'grand', 'ket' => 'lr-row-total',
            'footer' => 'lr-row-footer',
            default => '',
        };
    };

    $indentClass = function (array $row): string {
        return match (SpeedshopLabaRugiFinancials::labaRugiRowDepth($row)) {
            1 => 'lr-indent-1',
            2 => 'lr-indent-2',
            3 => 'lr-indent-3',
            default => '',
        };
    };
@endphp
@include('speedshop.partials.pdf-list-header', [
    'listTitle' => 'Rincian Laba Rugi Speedshop '.($speedshopLabel ?? 'SPEEDSHOP'),
    'metaLines' => $metaLines ?? [],
    'printedAt' => $printedAt ?? now(),
])

<div class="table-section">
    <div class="table-title">Rincian Laba Rugi</div>
    <table>
        <thead>
            <tr>
                <th style="width: 56px;">No.</th>
                <th>Keterangan</th>
                <th class="right" style="width: 140px;">Nilai</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows ?? [] as $row)
            @php
                $kind = $row['kind'] ?? 'line';
                $showRp = in_array($kind, ['line', 'subtotal', 'grand', 'footer'], true) && ! ($row['is_percent'] ?? false);
                $amount = $row['amount'] ?? null;
                $indent = $indentClass($row);
                $amountTone = in_array($kind, ['subtotal', 'grand'], true) ? 'color:#DC2626;' : '';
            @endphp
            <tr class="{{ $rowStyle($kind) }}">
                <td class="mono {{ $indent }}">{{ ($row['no'] ?? '') !== '' ? $row['no'] : '' }}</td>
                <td class="{{ $indent }}">{{ $row['label'] ?? '' }}</td>
                <td class="right mono">
                    @if($amount !== null)
                        @if($row['is_percent'] ?? false)
                            {{ $fmtPct($amount) }}
                        @elseif($showRp)
                            <span style="font-size:9px; margin-right:6px; {{ $kind === 'footer' ? '' : 'color:#DC2626;' }}">Rp</span><span style="{{ $amountTone }}">{{ $fmt($amount) }}</span>
                        @endif
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
