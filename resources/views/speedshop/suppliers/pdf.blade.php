@extends('layouts.pdf-modifikasi')

@section('pdf_title', 'Inventori Supplier Speedshop')

@section('content')
@include('produksi.master-data.partials.pdf-list-header', ['listTitle' => 'Inventori — Supplier (Pembelian Part Luar)'])

<div class="table-section">
    <div class="table-title">Supplier dari Pembelian Part Luar</div>
    <table>
        <thead>
            <tr>
                <th class="center" style="width: 34px;">No.</th>
                <th style="width: 110px;">Kode</th>
                <th style="width: 170px;">Nama Supplier</th>
                <th style="width: 95px;">No. HP</th>
                <th style="width: 160px;">Email</th>
                <th style="width: 90px;">Kota</th>
                <th>Alamat</th>
                <th style="width: 170px;">Rekening</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $i => $supplier)
                <tr>
                    <td class="text-center muted" style="font-size:11px;">{{ $i + 1 }}</td>
                    <td>{{ $supplier->kode_supplier ?: '-' }}</td>
                    <td>{{ $supplier->nama }}</td>
                    <td>{{ $supplier->no_hp ?: '-' }}</td>
                    <td>{{ $supplier->email ?: '-' }}</td>
                    <td>{{ $supplier->kota?->nama ?: '-' }}</td>
                    <td>{{ $supplier->alamat ?: '-' }}</td>
                    <td>
                        @if($supplier->jenis_rekening || $supplier->nama_rekening || $supplier->nomor_rekening)
                            {{ $supplier->jenis_rekening ?: '-' }}<br>
                            {{ $supplier->nama_rekening ?: '-' }}<br>
                            {{ $supplier->nomor_rekening ?: '-' }}
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center muted">Belum ada data supplier speedshop.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="doc-footer">
    <p>77racing Speedshop — {{ now()->year }}</p>
</div>
@endsection

