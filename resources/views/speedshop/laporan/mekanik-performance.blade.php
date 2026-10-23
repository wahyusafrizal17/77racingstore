@extends('layouts.speedshop')

@section('title', 'Laporan Mekanik Performance')

@section('content')
<div class="space-y-8">
    <nav class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('speedshop.dashboard') }}" class="transition hover:text-gray-700">Home</a>
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <span class="font-medium text-gray-900">Mekanik Performance</span>
    </nav>

    <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm sm:p-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900">Mekanik Performance</h1>
                <p class="mt-1 text-sm text-gray-500">Periode {{ $dari->format('d M Y') }} - {{ $sampai->format('d M Y') }}</p>
            </div>
            @include('partials.speedshop.laporan-filter', ['exportRoute' => 'speedshop.laporan.mekanik-performance.export', 'dari' => $dari, 'sampai' => $sampai])
        </div>

        <div class="mt-8 overflow-x-auto rounded-xl border border-gray-100">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-5 py-3.5 text-xs font-bold uppercase text-gray-500">No.</th>
                        <th class="px-5 py-3.5 text-xs font-bold uppercase text-gray-500">Mekanik</th>
                        <th class="px-5 py-3.5 text-xs font-bold uppercase text-gray-500 text-center">Total Order</th>
                        <th class="px-5 py-3.5 text-xs font-bold uppercase text-gray-500 text-right">Total Jasa</th>
                        <th class="px-5 py-3.5 text-xs font-bold uppercase text-gray-500 text-right">Total Sparepart</th>
                        <th class="px-5 py-3.5 text-xs font-bold uppercase text-gray-500 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($performances as $i => $p)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-4 text-gray-500">{{ $i + 1 }}</td>
                        <td class="px-5 py-4 font-medium">{{ $p->mekanik->nama ?? '-' }}</td>
                        <td class="px-5 py-4 text-center font-semibold">{{ $p->total_order }}</td>
                        <td class="px-5 py-4 text-right">Rp {{ number_format($p->total_jasa, 0, ',', '.') }}</td>
                        <td class="px-5 py-4 text-right">Rp {{ number_format($p->total_sparepart, 0, ',', '.') }}</td>
                        <td class="px-5 py-4 text-right font-medium">Rp {{ number_format($p->total, 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-5 py-12 text-center text-gray-400">Tidak ada data</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
