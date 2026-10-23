@extends('layouts.speedshop')

@section('title', 'Transaksi Offline dari Warehouse')

@section('content')
<div class="space-y-8">
    <nav class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('speedshop.dashboard') }}" class="transition hover:text-gray-700">Home</a>
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('speedshop.stock-in.pembelian-mo.index') }}" class="transition hover:text-gray-700">Stock IN</a>
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <span class="font-medium text-gray-900">Transaksi Offline</span>
    </nav>

    <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm sm:p-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900">Transaksi Offline dari Warehouse</h1>
                <p class="mt-1 text-sm text-gray-500">Daftar transaksi offline yang dikirim ke Speedshop Anda. Verifikasi agar stok masuk ke stok produk.</p>
            </div>
            <form method="GET" action="{{ route('speedshop.stock-in.transaksi-offline.index') }}" class="flex flex-wrap items-center gap-2">
                <div class="relative">
                    <svg class="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari No. Invoice / Nama Toko..." class="h-11 w-full min-w-[200px] rounded-xl border border-gray-200 bg-gray-50 py-2.5 pl-10 pr-4 text-sm text-gray-900 placeholder-gray-400 transition focus:border-brand focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand/40">
                </div>
                <button type="submit" class="h-11 rounded-xl bg-gray-900 px-4 text-sm font-medium text-white transition hover:bg-gray-800">Cari</button>
            </form>
        </div>

        <div class="mt-8 overflow-x-auto rounded-xl border border-gray-100">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-5 py-3.5 text-xs font-bold uppercase tracking-wider text-gray-500">No.</th>
                        <th class="px-5 py-3.5 text-xs font-bold uppercase tracking-wider text-gray-500">No. Invoice</th>
                        <th class="px-5 py-3.5 text-xs font-bold uppercase tracking-wider text-gray-500">Tanggal</th>
                        <th class="px-5 py-3.5 text-xs font-bold uppercase tracking-wider text-gray-500">Dari Warehouse</th>
                        <th class="px-5 py-3.5 text-xs font-bold uppercase tracking-wider text-gray-500">Nama Toko</th>
                        <th class="px-5 py-3.5 text-xs font-bold uppercase tracking-wider text-gray-500 text-center">Item</th>
                        <th class="px-5 py-3.5 text-xs font-bold uppercase tracking-wider text-gray-500 text-center">Total Qty</th>
                        <th class="px-5 py-3.5 text-xs font-bold uppercase tracking-wider text-gray-500"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($transaksis as $i => $t)
                    <tr class="transition hover:bg-gray-50">
                        <td class="px-5 py-4 text-gray-500">{{ $transaksis->firstItem() + $i }}</td>
                        <td class="px-5 py-4 font-medium text-gray-900">{{ $t->no_transaksi }}</td>
                        <td class="px-5 py-4 text-gray-600">{{ $t->created_at->format('d M Y H:i') }}</td>
                        <td class="px-5 py-4 text-gray-700">{{ $t->warehouse?->nama ?? '-' }}</td>
                        <td class="px-5 py-4 text-gray-700">{{ $t->nama_toko ?: '-' }}</td>
                        <td class="px-5 py-4 text-center text-gray-600">{{ $t->items->count() }}</td>
                        <td class="px-5 py-4 text-center font-semibold text-gray-900">{{ $t->items->sum('qty') }}</td>
                        <td class="px-5 py-4">
                            <a href="{{ route('speedshop.stock-in.transaksi-offline.show', $t) }}" class="inline-flex items-center gap-1.5 rounded-lg bg-brand px-3 py-2 text-xs font-semibold text-brand-ink shadow-sm transition hover:bg-brand-hover">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                Lihat & Verifikasi
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-5 py-12 text-center text-gray-400">
                            <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            <p class="mt-3 text-sm font-medium">Belum ada transaksi offline menunggu verifikasi</p>
                            <p class="mt-1 text-xs">Notifikasi akan muncul ketika warehouse mengirim transaksi ke Speedshop Anda</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($transaksis->hasPages())
        <div class="mt-8 pt-4">{{ $transaksis->links() }}</div>
        @endif
    </div>
</div>
@endsection
