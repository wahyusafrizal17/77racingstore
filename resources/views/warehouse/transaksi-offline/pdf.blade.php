@extends('layouts.pdf-modifikasi')

@section('pdf_title', 'Transaksi Offline - '.$transaksiOffline->no_transaksi)

@section('body_class', 'pdf-screen')

@push('pdf_head')
<style>
  .doc-body table.items tfoot td {
    font-size: 11.5px;
    padding: 10px 12px;
    vertical-align: middle;
    border-top: 1px solid var(--gray-200);
    border-bottom: 1px solid var(--gray-200);
    background: var(--gray-50);
  }

  .doc-body table.items tfoot tr.items-total-row td.lbl {
    color: var(--gray-600);
    font-weight: 600;
  }

  .doc-body table.items tfoot tr.items-total-row td.val {
    color: var(--gray-800);
    font-weight: 700;
  }

  .doc-body table.items tfoot tr.items-total-row.grand td {
    background: var(--red);
    border-top: none;
    border-bottom: none;
    color: #142000;
    font-size: 13px;
    font-weight: 800;
  }
</style>
@endpush

@section('print_bar')
<div class="print-bar">
    <button type="button" class="btn-print" onclick="window.print()">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
        Cetak Transaksi Offline
    </button>
</div>
@endsection

@section('content')
<div class="doc-header">
    <div class="doc-brand">
        <div class="doc-brand-logo">
            <div class="logo-mark">@include('layouts.partials.pdf-logo-img')</div>
            <span class="logo-name">77racing</span>
        </div>
        <div class="doc-sub">77racing Warehouse</div>
        <div class="doc-title-block">
            <div class="doc-title">TRANSAKSI OFFLINE</div>
        </div>
    </div>
    <div class="doc-meta">
        <div class="doc-inv-no">{{ $transaksiOffline->no_transaksi }}</div>
        <div class="doc-meta-row">Tanggal: <span>{{ $transaksiOffline->created_at->format('d M Y H:i') }}</span></div>
        @if($transaksiOffline->warehouse)
        <div class="doc-meta-row">Warehouse: <span>{{ $transaksiOffline->warehouse->nama }}</span></div>
        @endif
    </div>
</div>

<div class="info-section">
    <div class="info-row">
        <div class="info-col">
            <div class="info-col-label">No. Invoice</div>
            <div class="info-col-value">{{ $transaksiOffline->no_transaksi }}</div>
            <div class="info-col-sub">
                Tujuan: {{ $transaksiOffline->tujuan === 'speedshop' ? 'Speedshop' : ($transaksiOffline->tujuan === 'reseller' ? 'Reseller' : 'Umum') }}
                @if($transaksiOffline->tujuan === 'reseller' && $transaksiOffline->warehouseReseller)
                    — {{ $transaksiOffline->warehouseReseller->kategori ?? 'OFFLINE' }}
                @endif
                @if($transaksiOffline->tujuan === 'reseller' && $transaksiOffline->no_urut_harian !== null)
                    — Urut: {{ $transaksiOffline->no_urut_harian }}
                @endif
            </div>
        </div>
        <div class="info-col">
            <div class="info-col-label">Dibuat Oleh</div>
            <div class="info-col-value">{{ $transaksiOffline->user->name }}</div>
            <div class="info-col-sub">{{ $transaksiOffline->created_at->format('d M Y H:i') }}</div>
        </div>
    </div>
    <div class="info-row" style="border-top: 1px solid #EEEEEE;">
        <div class="info-col" style="width: 100%;">
            <div class="info-col-label">Toko / Tujuan</div>
            <div class="info-col-value">{{ $transaksiOffline->nama_toko ?: '-' }}</div>
            @if($transaksiOffline->alamat)<div class="info-col-sub">Alamat: {{ $transaksiOffline->alamat }}</div>@endif
            @if($transaksiOffline->no_hp)<div class="info-col-sub">No. HP: {{ $transaksiOffline->no_hp }}</div>@endif
            @if($transaksiOffline->jenis_pembayaran)
                @php
                    $jpPdf = strtolower((string) $transaksiOffline->jenis_pembayaran);
                    $splitPdf = ($jpPdf === 'split' && is_array($transaksiOffline->pembayaran_split ?? null))
                        ? $transaksiOffline->pembayaran_split
                        : null;
                    $payLabel = $splitPdf
                        ? 'Split bill'
                        : match($jpPdf) {
                            'cash' => 'Cash',
                            'tempo' => 'Tempo / Hutang',
                            'transfer' => 'Transfer',
                            'qris' => 'Qris',
                            'edc' => 'EDC',
                            default => $transaksiOffline->jenis_pembayaran,
                        };
                @endphp
                <div class="info-col-sub">Pembayaran: {{ $payLabel }}</div>
                @if($splitPdf && count($splitPdf))
                    @foreach($splitPdf as $row)
                        @php
                            $m = strtolower((string) ($row['metode'] ?? ''));
                            $lbl = match ($m) {
                                'cash' => 'Cash',
                                'transfer' => 'Transfer',
                                'qris' => 'Qris',
                                'edc' => 'EDC',
                                default => $row['metode'] ?? '-',
                            };
                            $nom = (int) ($row['jumlah'] ?? 0);
                        @endphp
                        <div class="info-col-sub">{{ $lbl }}: Rp {{ number_format($nom, 0, ',', '.') }}</div>
                    @endforeach
                @endif
                @if($transaksiOffline->tujuan === 'reseller' && strtolower((string) $transaksiOffline->jenis_pembayaran) === 'tempo' && $transaksiOffline->tanggal_jatuh_tempo)
                    <div class="info-col-sub">Jatuh tempo: {{ $transaksiOffline->tanggal_jatuh_tempo->format('d M Y') }}</div>
                @endif
            @endif
            @if($transaksiOffline->petugas)<div class="info-col-sub">Petugas: {{ $transaksiOffline->petugas->name }}</div>@endif
        </div>
    </div>
</div>

<div class="table-section">
    <div class="table-title">Daftar Produk</div>
    <table class="items">
        <thead>
            <tr>
                <th class="center" style="width:36px;">No</th>
                <th style="width:110px;">Kode Produk</th>
                <th>Nama Produk</th>
                <th class="right" style="width:90px;">Harga</th>
                <th class="center" style="width:56px;">Qty</th>
                <th class="right" style="width:100px;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transaksiOffline->items as $i => $item)
            @php
                $harga = $transaksiOffline->itemLineUnitPriceRp($item);
                $subtotal = $harga * (int) $item->qty;
            @endphp
            <tr>
                <td class="text-center muted" style="font-size:11px;">{{ $i + 1 }}</td>
                <td class="fw-700 mono">{{ $item->product->kode_produk ?? '-' }}</td>
                <td><div class="item-name">{{ $item->product->nama_produk ?? '-' }}</div></td>
                <td class="text-right mono">Rp {{ number_format($harga, 0, ',', '.') }}</td>
                <td class="text-center fw-600 mono">{{ $item->qty }}</td>
                <td class="text-right mono fw-700">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="items-total-row">
                <td colspan="5" class="text-right lbl">Total Item</td>
                <td class="text-right val">{{ $transaksiOffline->items->count() }} item</td>
            </tr>
            <tr class="items-total-row">
                <td colspan="5" class="text-right lbl">Total Qty</td>
                <td class="text-right val">{{ number_format($transaksiOffline->items->sum('qty')) }} unit</td>
            </tr>
            <tr class="items-total-row grand">
                <td colspan="5" class="text-right lbl">Total Harga</td>
                <td class="text-right val">Rp {{ number_format($transaksiOffline->itemsGrandTotalRp(), 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>
</div>

<div class="doc-footer">
    <p>Dokumen ini dicetak dari 77racing Warehouse — {{ now()->format('d M Y H:i') }}</p>
</div>
@endsection
