@extends('layouts.pdf-modifikasi')

@section('pdf_title', 'Database Produk Speedshop')

@section('content')
@php
    $isMoCatalog = \App\Support\ProductMasterCatalog::isWarehouseSharedKind((string) ($catalogKind ?? ''));
@endphp
@include('produksi.master-data.partials.pdf-list-header', ['listTitle' => 'Database Produk Speedshop'])

<div class="table-section">
    <div class="table-title">Daftar Database Produk (cabang)</div>
    <table>
        <thead>
            <tr>
                <th class="center" style="width: 34px;">No.</th>
                <th style="width: 100px;">Kode</th>
                <th style="width: 70px;">Kode Supplier</th>
                <th>Nama Produk</th>
                <th style="width: 110px;">Kategori</th>
                <th style="width: 110px;">Brand</th>
                @if($isMoCatalog)
                <th class="right" style="width: 90px;">HPP Speedshop</th>
                <th class="right" style="width: 90px;">Harga Jual Cust</th>
                @else
                <th class="right" style="width: 90px;">HPP</th>
                <th class="right" style="width: 90px;">Harga Jual</th>
                @endif
                <th class="right" style="width: 56px;">Stok</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $i => $item)
                <tr>
                    <td class="text-center muted" style="font-size:11px;">{{ $i + 1 }}</td>
                    <td class="mono fw-600">{{ $item->kode_produk }}</td>
                    <td class="mono">{{ $item->kode_supplier ?? '-' }}</td>
                    <td>{{ $item->nama_produk }}</td>
                    <td>{{ $item->category?->nama ?: '-' }}</td>
                    <td>{{ $item->brand?->nama ?: '-' }}</td>
                    @if($isMoCatalog)
                    <td class="text-right mono">{{ number_format((float) ($item->harga_jual_speedshop ?? 0), 0, ',', '.') }}</td>
                    <td class="text-right mono fw-600">{{ number_format((float) ($item->harga_eceran_terendah ?? 0), 0, ',', '.') }}</td>
                    @else
                    <td class="text-right mono">{{ number_format((float) ($item->hpp ?? 0), 0, ',', '.') }}</td>
                    <td class="text-right mono fw-600">{{ number_format((float) ($item->harga_jual_speedshop ?? 0), 0, ',', '.') }}</td>
                    @endif
                    <td class="text-right mono">{{ ($ssid ?? null) ? number_format((int) ($item->combined_speedshop_stock ?? (($isAllScope ?? false) ? $item->speedshopStocks->sum('jumlah') : ($item->speedshopStocks->first()->jumlah ?? 0))), 0, ',', '.') : '–' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center muted">Belum ada data produk.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="doc-footer">
    <p>77racing Speedshop — {{ now()->year }}</p>
</div>
@endsection
