@extends('layouts.pdf-modifikasi')

@section('pdf_title', 'Data Pembelian - '.$transaksi->kode)

@section('body_class', 'pdf-screen')

@section('print_bar')
<div class="print-bar">
    <button type="button" class="btn-print" onclick="window.print()">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
        Cetak PDF
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
            <div class="doc-title">DATA PEMBELIAN</div>
        </div>
    </div>
    <div class="doc-meta">
        <div class="doc-inv-no">{{ $transaksi->kode }}</div>
        <div class="doc-meta-row">Tanggal: <span>{{ $transaksi->created_at->format('d M Y H:i') }}</span></div>
        @if($transaksi->warehouse)
        <div class="doc-meta-row">Warehouse: <span>{{ $transaksi->warehouse->nama }}</span></div>
        @endif
    </div>
</div>

<div class="info-section">
    <div class="info-row">
        <div class="info-col">
            <div class="info-col-label">Kode</div>
            <div class="info-col-value">{{ $transaksi->kode }}</div>
            <span class="status-badge status-{{ $transaksi->status }}">
                @if($transaksi->status === 'pending') Menunggu Verifikasi
                @elseif($transaksi->status === 'approved') Disetujui
                @else Ditolak
                @endif
            </span>
        </div>
        <div class="info-col">
            <div class="info-col-label">Diajukan Oleh</div>
            <div class="info-col-value">{{ $transaksi->user->name }}</div>
            <div class="info-col-sub">{{ $transaksi->created_at->format('d M Y H:i') }}</div>
            @if($transaksi->approver)
            <div class="info-col-sub" style="margin-top: 8px;">Disetujui: {{ $transaksi->approver->name }} · {{ $transaksi->approved_at?->format('d M Y H:i') }}</div>
            @endif
        </div>
    </div>
    @if($transaksi->catatan)
    <div class="info-row" style="border-top: 1px solid #EEEEEE;">
        <div class="info-col" style="width: 100%;">
            <div class="info-col-label">Catatan</div>
            <div class="info-col-value" style="font-weight: 500;">{{ $transaksi->catatan }}</div>
        </div>
    </div>
    @endif
</div>

<div class="table-section">
    <div class="table-title">Daftar Item</div>
    <table class="items">
        <thead>
            <tr>
                <th class="center" style="width:36px;">No</th>
                <th class="center" style="width:64px;">Tipe</th>
                <th style="width:86px;">Kode</th>
                <th>Nama Item</th>
                <th class="right" style="width:90px;">HPP</th>
                <th class="center" style="width:50px;">Qty</th>
                <th class="center" style="width:50px;">Bonus</th>
                <th class="right" style="width:96px;">Subtotal</th>
                <th class="right" style="width:80px;">Diskon</th>
                <th class="right" style="width:96px;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transaksi->items as $i => $item)
            @php
                $isProduct = ($item->item_type ?? 'product') === 'product';
                $kode = $isProduct ? ($item->product->kode_produk ?? '-') : ($item->perlengkapanPacking->kode ?? '-');
                $nama = $isProduct ? ($item->product->nama_produk ?? '-') : ($item->perlengkapanPacking->nama ?? '-');
            @endphp
            <tr>
                <td class="text-center muted" style="font-size:11px;">{{ $i + 1 }}</td>
                <td class="text-center"><span class="type-badge">{{ $isProduct ? 'Produk' : 'PP' }}</span></td>
                <td class="fw-700 mono">{{ $kode }}</td>
                <td>
                    <div class="item-name">{{ $nama }}</div>
                    <div class="item-code">{{ $kode }}</div>
                </td>
                <td class="text-right mono">Rp {{ number_format($item->harga_pembelian, 0, ',', '.') }}</td>
                <td class="text-center fw-600 mono">{{ $item->qty }}</td>
                <td class="text-center fw-600 mono">{{ $item->bonusQty() > 0 ? $item->bonusQty() : '—' }}</td>
                <td class="text-right mono">Rp {{ number_format($item->subtotalBruto(), 0, ',', '.') }}</td>
                <td class="text-right mono">{{ (int) ($item->diskon ?? 0) > 0 ? 'Rp '.number_format((int) $item->diskon, 0, ',', '.') : '—' }}</td>
                <td class="text-right mono fw-700">Rp {{ number_format($item->subtotalNet(), 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="totals-section">
    <div class="totals-box">
        <div class="totals-row">
            <span class="lbl">Total Item</span>
            <span class="val">{{ $transaksi->items->count() }} item</span>
        </div>
        <div class="totals-row">
            <span class="lbl">Total Qty</span>
            <span class="val">{{ number_format($transaksi->items->sum('qty')) }} unit</span>
        </div>
        @php
            $ppnNominal = (int) ($transaksi->ppn ?? 0);
            $ongkirNominal = (int) ($transaksi->ongkir ?? 0);
            $itemsNetTotal = (int) $transaksi->items->sum(fn ($i) => $i->subtotalNet());
            $subtotalSebelumPpnOngkir = max(0, $itemsNetTotal - $ppnNominal - $ongkirNominal);
        @endphp
        @if($ppnNominal > 0 || $ongkirNominal > 0)
        <div class="totals-row">
            <span class="lbl">Subtotal Item</span>
            <span class="val">Rp {{ number_format($subtotalSebelumPpnOngkir, 0, ',', '.') }}</span>
        </div>
        @if($ppnNominal > 0)
        <div class="totals-row">
            <span class="lbl">PPN</span>
            <span class="val">Rp {{ number_format($ppnNominal, 0, ',', '.') }}</span>
        </div>
        @endif
        @if($ongkirNominal > 0)
        <div class="totals-row">
            <span class="lbl">Ongkir</span>
            <span class="val">Rp {{ number_format($ongkirNominal, 0, ',', '.') }}</span>
        </div>
        @endif
        @endif
        <div class="totals-row grand">
            <span class="lbl">Total Tagihan</span>
            <span class="val">Rp {{ number_format($itemsNetTotal, 0, ',', '.') }}</span>
        </div>
    </div>
</div>

@if($transaksi->rejected_reason)
<div class="reject-section" style="margin-top:14px;">
    <div class="reject-label">Alasan Penolakan</div>
    <div class="reject-note">{{ $transaksi->rejected_reason }}</div>
</div>
@endif

<div class="doc-footer">
    <p>Dokumen ini dicetak dari 77racing Warehouse — {{ now()->format('d M Y H:i') }}</p>
</div>
@endsection
