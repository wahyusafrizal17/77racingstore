@extends('layouts.pdf-modifikasi')

@section('pdf_title', isset($mutasi) ? 'Mutasi '.$mutasi->kode : 'Surat Jalan Mutasi')

@section('content')
@foreach(($mutasis ?? collect([$mutasi])) as $sheetIndex => $mutasi)
@if($sheetIndex > 0)
<div style="page-break-before: always;"></div>
@endif
@php
    $destType = $mutasi->destination_type ?? 'warehouse';
    $destLabel = $destType === 'speedshop' ? 'Speedshop Tujuan' : 'Warehouse Tujuan';
    $destName = $destType === 'speedshop'
        ? ($mutasi->destinationSpeedshop->nama ?? '-')
        : ($mutasi->warehouse->nama ?? '-');
    $destAlamat = $destType === 'speedshop'
        ? ($mutasi->destinationSpeedshop->alamat ?? '')
        : ($mutasi->warehouse?->alamat ?? '');
@endphp

<div class="doc-header">
    <div class="doc-brand">
        <div class="doc-brand-logo">
            <div class="logo-mark">@include('layouts.partials.pdf-logo-img')</div>
            <span class="logo-name">77racing</span>
        </div>
        <div class="doc-sub">Speedshop</div>
        <div class="doc-title-block">
            <div class="doc-title">SURAT JALAN MUTASI</div>
        </div>
    </div>
    <div class="doc-meta">
        <div class="doc-inv-no">{{ $mutasi->kode }}</div>
        <div class="doc-meta-row">No. SJ: <span>{{ $mutasi->nomor_surat_jalan }}</span></div>
        <div class="doc-meta-row">Tanggal: <span>{{ $mutasi->tanggal->format('d M Y') }}</span></div>
        <div class="status-chip {{ $mutasi->isDikirim() ? 'warn' : '' }}">
            {{ $mutasi->isDikirim() ? 'DIKIRIM' : 'DITERIMA' }}
        </div>
    </div>
</div>

<div class="pdf-info-grid">
    <div class="info-card">
        <div class="info-label">{{ $destLabel }}</div>
        <div class="info-name">{{ $destName }}</div>
        <div class="info-detail">{{ $destAlamat }}</div>
    </div>
    <div class="info-card">
        <div class="info-label">Dibuat Oleh</div>
        <div class="info-name">{{ $mutasi->user->name ?? '-' }}</div>
        <div class="info-detail">{{ $mutasi->tanggal->format('d M Y') }}</div>
    </div>
    @if($mutasi->catatan)
    <div class="info-card full">
        <div class="info-label">Catatan</div>
        <div class="info-detail">{{ $mutasi->catatan }}</div>
    </div>
    @endif
</div>

<div class="table-section">
    <div class="table-title">Daftar Produk ({{ $mutasi->items->count() }} produk, {{ $mutasi->items->sum('quantity') }} pcs)</div>
    <table>
        <thead>
            <tr>
                <th class="center" style="width:8%;">No.</th>
                <th style="width:22%;">Kode</th>
                <th>Nama Produk</th>
                <th class="center" style="width:12%;">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @foreach($mutasi->items as $i => $item)
            <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td class="mono fw-600">{{ $item->product->kode_produk ?? '-' }}</td>
                <td>{{ $item->product->nama_produk ?? '-' }}</td>
                <td class="text-center fw-600">{{ $item->quantity }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="doc-footer">
    <p>Dokumen ini dicetak dari 77racing — Mutasi {{ $mutasi->kode }} — {{ $mutasi->nomor_surat_jalan }} — {{ now()->format('d/m/Y H:i') }}</p>
</div>
@endforeach
@endsection

