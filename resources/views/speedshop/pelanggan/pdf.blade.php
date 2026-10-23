@extends('layouts.pdf-modifikasi')

@section('pdf_title', 'Pelanggan Speedshop')

@section('content')
@include('produksi.master-data.partials.pdf-list-header', ['listTitle' => 'Pelanggan Speedshop'])

<div class="table-section">
    <div class="table-title">Daftar Pelanggan Speedshop</div>
    <table>
        <thead>
            <tr>
                <th class="center" style="width: 34px;">No.</th>
                <th>Nama</th>
                <th style="width: 120px;">No HP</th>
                <th>Alamat</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $i => $item)
                <tr>
                    <td class="text-center muted" style="font-size:11px;">{{ $i + 1 }}</td>
                    <td>{{ $item->nama }}</td>
                    <td>{{ $item->no_hp ?: '-' }}</td>
                    <td>{{ $item->alamat ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center muted">Belum ada data pelanggan speedshop.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="doc-footer">
    <p>77racing Speedshop — {{ now()->year }}</p>
</div>
@endsection

