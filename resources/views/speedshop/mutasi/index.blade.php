@extends('layouts.speedshop')

@php
    $filterBaseClass = 'h-8 w-full rounded-lg border border-gray-200 bg-white px-2.5 text-[13px] font-normal text-gray-700 placeholder-gray-400 outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/40';
    $statusFilter = request('status', '');
    $filterQueryKeys = ['mode', 'tanggal', 'bulan', 'tahun', 'dari', 'sampai', 'kode', 'warehouse', 'status'];
    $mutasiPeriodLabel = match ($mode ?? 'bulanan') {
        'harian' => \Carbon\Carbon::parse($selectedDate ?? now())->translatedFormat('d M Y'),
        'bulanan' => \Carbon\Carbon::parse(($selectedMonth ?? now()->format('Y-m')).'-01')->translatedFormat('F Y'),
        'tahunan' => (string) ($selectedYear ?? now()->year),
        'range' => ($dari ?? now())->translatedFormat('d M Y').' – '.($sampai ?? now())->translatedFormat('d M Y'),
        default => \Carbon\Carbon::parse(($selectedMonth ?? now()->format('Y-m')).'-01')->translatedFormat('F Y'),
    };
    $mutasiCardBaseQuery = array_filter([
        'mode' => $mode ?? 'bulanan',
        'tanggal' => ($mode ?? 'bulanan') === 'harian' ? ($selectedDate ?? now()->toDateString()) : null,
        'bulan' => ($mode ?? '') === 'bulanan' ? ($selectedMonth ?? now()->format('Y-m')) : null,
        'tahun' => ($mode ?? '') === 'tahunan' ? ($selectedYear ?? now()->year) : null,
        'dari' => ($mode ?? '') === 'range' ? ($selectedDari ?? ($dari ?? now())->format('Y-m-d')) : null,
        'sampai' => ($mode ?? '') === 'range' ? ($selectedSampai ?? ($sampai ?? now())->format('Y-m-d')) : null,
        'kode' => request('kode'),
        'warehouse' => request('warehouse'),
    ], fn ($v) => $v !== null && $v !== '');
    $mutasiCardUrl = fn (?string $status = null) => route('speedshop.mutasi.index', $status
        ? array_merge($mutasiCardBaseQuery, ['status' => $status])
        : $mutasiCardBaseQuery);
@endphp

@section('title', 'Mutasi')

@section('content')
@push('styles')
@include('partials.speedshop.dashboard-load-animations')
@include('partials.speedshop.dashboard-manager-template-styles')
@include('partials.speedshop.work-order-create-styles')
<style>
.ss-dashboard .mutasi-summary-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 20px;
}
@media (max-width: 1024px) {
  .ss-dashboard .mutasi-summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 640px) {
  .ss-dashboard .mutasi-summary-grid { grid-template-columns: 1fr; }
}
.ss-dashboard .pay-card.pay-card-filter {
  cursor: pointer;
  text-decoration: none;
  color: inherit;
}
.ss-dashboard .pay-icon.teal { background: rgba(20,184,166,0.10); color: #14B8A6; }
.ss-dashboard .pay-icon.amber { background: rgba(245,158,11,0.10); color: #D97706; }
.ss-dashboard .pay-icon.green { background: rgba(16,185,129,0.10); color: #10B981; }
.ss-dashboard .pay-icon.red { background: rgba(239,68,68,0.10); color: #EF4444; }
</style>
@endpush

<div
    class="ss-dashboard ss-dash-page ss-wo-create"
    x-data="{ loading: false }"
    x-init="$nextTick(() => { requestAnimationFrame(() => $el.classList.add('is-ready')); })"
    :class="loading && 'is-loading'"
>
    <div
        x-show="loading"
        x-cloak
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="ss-dash-loading"
        aria-live="polite"
        aria-busy="true"
    >
        <div class="ss-dash-loading-panel">
            <div class="ss-dash-spinner" aria-hidden="true"></div>
            <p class="ss-dash-loading-text">Memuat mutasi…</p>
            <p class="ss-dash-loading-sub">Menyiapkan data periode terpilih</p>
        </div>
    </div>

    <div class="ss-dash-content space-y-5">
    @include('partials.speedshop.dashboard-filter-strip', [
        'filterAction' => route('speedshop.mutasi.index'),
        'dashboardFilterExports' => [],
        'dashboardFilterQueryKeys' => $filterQueryKeys,
        'filterHiddenInputs' => array_filter([
            'status' => request('status'),
            'kode' => request('kode'),
            'warehouse' => request('warehouse'),
        ], fn ($v) => $v !== null && $v !== ''),
    ])

    <div class="mutasi-summary-grid ss-dash-enter" style="--ss-delay: 80ms">
        <a href="{{ $mutasiCardUrl() }}" @click="loading = true" class="pay-card pay-card-filter ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 0">
            <div class="pay-card-top">
                <span class="pay-card-label">Total Mutasi</span>
                <span class="pay-icon teal">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                </span>
            </div>
            <div class="pay-amount">{{ number_format((int) ($summaryTotalMutasi ?? 0)) }}</div>
            <p class="pay-period">Semua mutasi · {{ $mutasiPeriodLabel }}</p>
        </a>
        <a href="{{ $mutasiCardUrl('dikirim') }}" @click="loading = true" class="pay-card pay-card-filter ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 1">
            <div class="pay-card-top">
                <span class="pay-card-label">Dikirim</span>
                <span class="pay-icon amber">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="pay-amount">{{ number_format((int) ($summaryDikirim ?? 0)) }}</div>
            <p class="pay-period">Menunggu verifikasi tujuan</p>
        </a>
        <a href="{{ $mutasiCardUrl('diterima') }}" @click="loading = true" class="pay-card pay-card-filter ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 2">
            <div class="pay-card-top">
                <span class="pay-card-label">Diterima</span>
                <span class="pay-icon green">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </span>
            </div>
            <div class="pay-amount">{{ number_format((int) ($summaryDiterima ?? 0)) }}</div>
            <p class="pay-period">Mutasi sudah diverifikasi</p>
        </a>
        <a href="{{ $mutasiCardUrl('ditolak') }}" @click="loading = true" class="pay-card pay-card-filter ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 3">
            <div class="pay-card-top">
                <span class="pay-card-label">Ditolak</span>
                <span class="pay-icon red">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </span>
            </div>
            <div class="pay-amount">{{ number_format((int) ($summaryDitolak ?? 0)) }}</div>
            <p class="pay-period">Mutasi ditolak tujuan</p>
        </a>
    </div>

    <div class="panel-card panel-card-flush ss-dash-enter" style="--ss-delay: 120ms">
        <div class="flex flex-col gap-3 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">Mutasi Stok</h2>
                <p class="mt-1 text-[13px] text-gray-500">
                    Kirim produk ke warehouse atau cabang Speedshop lain · {{ $mutasiPeriodLabel }}
                </p>
            </div>
            <a href="{{ route('speedshop.mutasi.create') }}" class="btn-primary inline-flex h-10 shrink-0 items-center gap-2 px-5">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Buat Mutasi
            </a>
        </div>

        <div class="overflow-x-auto">
            <form method="GET" action="{{ route('speedshop.mutasi.index') }}" @submit="loading = true">
                <input type="hidden" name="mode" value="{{ $mode ?? 'bulanan' }}">
                @if(($mode ?? 'bulanan') === 'harian')
                <input type="hidden" name="tanggal" value="{{ $selectedDate ?? now()->toDateString() }}">
                @elseif(($mode ?? '') === 'bulanan')
                <input type="hidden" name="bulan" value="{{ $selectedMonth ?? now()->format('Y-m') }}">
                @elseif(($mode ?? '') === 'tahunan')
                <input type="hidden" name="tahun" value="{{ $selectedYear ?? now()->year }}">
                @elseif(($mode ?? '') === 'range')
                <input type="hidden" name="dari" value="{{ $selectedDari ?? ($dari ?? now())->format('Y-m-d') }}">
                <input type="hidden" name="sampai" value="{{ $selectedSampai ?? ($sampai ?? now())->format('Y-m-d') }}">
                @endif
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="bg-[#f7f3f4]">
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No.</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Nomor Mutasi</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Tanggal</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Tujuan</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-center">Item</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Status</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 w-24"></th>
                        </tr>
                        <tr class="border-t border-gray-200 bg-[#fbf8f9]">
                            <th class="px-5 py-2"></th>
                            <th class="px-5 py-2">
                                <input name="kode" value="{{ request('kode') }}" placeholder="Cari nomor..." class="{{ $filterBaseClass }}" onchange="this.form.submit()">
                            </th>
                            <th class="px-5 py-2"></th>
                            <th class="px-5 py-2">
                                <input name="warehouse" value="{{ request('warehouse') }}" placeholder="Warehouse / Speedshop..." class="{{ $filterBaseClass }}" onchange="this.form.submit()">
                            </th>
                            <th class="px-5 py-2"></th>
                            <th class="px-5 py-2">
                                <select name="status" class="{{ $filterBaseClass }}" onchange="this.form.submit()">
                                    <option value="" {{ $statusFilter === '' ? 'selected' : '' }}>Semua</option>
                                    <option value="dikirim" {{ $statusFilter === 'dikirim' ? 'selected' : '' }}>Dikirim</option>
                                    <option value="diterima" {{ $statusFilter === 'diterima' ? 'selected' : '' }}>Diterima</option>
                                    <option value="ditolak" {{ $statusFilter === 'ditolak' ? 'selected' : '' }}>Ditolak</option>
                                </select>
                            </th>
                            <th class="px-5 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    @forelse($mutasis as $i => $m)
                    <tr class="transition hover:bg-[#fcfafb]">
                        <td class="px-5 py-4 text-gray-500">{{ $mutasis->firstItem() + $i }}</td>
                        <td class="px-5 py-4 font-semibold text-gray-900">{{ $m->kode }}</td>
                        <td class="px-5 py-4 text-gray-600">{{ $m->tanggal->format('d M Y') }}</td>
                        <td class="px-5 py-4">
                            @if(($m->destination_type ?? 'warehouse') === 'speedshop')
                                <div class="flex flex-col gap-0.5">
                                    <span class="font-medium text-gray-900">{{ $m->destinationSpeedshop->nama ?? '-' }}</span>
                                    <span class="text-[11px] font-medium uppercase tracking-wide text-red-600/90">Speedshop</span>
                                </div>
                            @else
                                <div class="flex flex-col gap-0.5">
                                    <span class="font-medium text-gray-900">{{ $m->warehouse->nama ?? '-' }}</span>
                                    <span class="text-[11px] font-medium uppercase tracking-wide text-gray-500">Warehouse</span>
                                </div>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-center text-gray-600">
                            <span class="font-medium text-gray-900">{{ $m->items->sum('quantity') }}</span>
                            <span class="text-gray-400"> unit</span>
                            <span class="block text-xs text-gray-500">{{ $m->items->count() }} SKU</span>
                        </td>
                        <td class="px-5 py-4">
                            @if($m->status === 'dikirim')
                                <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">Dikirim</span>
                            @elseif($m->status === 'ditolak')
                                <span class="inline-flex items-center rounded-full border border-red-200 bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700">Ditolak</span>
                            @else
                                <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Diterima</span>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            <a href="{{ route('speedshop.mutasi.show', $m) }}" class="btn-detail">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                Detail
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-5 py-12 text-center text-gray-400">
                            @if(request()->hasAny(['kode', 'warehouse', 'status', 'search']))
                                Tidak ada mutasi sesuai filter.
                            @else
                                <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                <p class="mt-3 text-sm font-medium">Belum ada mutasi untuk periode ini</p>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                    </tbody>
                </table>
            </form>
        </div>

        @if($mutasis->hasPages())
        <div class="border-t border-gray-100 px-5 py-4">{{ $mutasis->links() }}</div>
        @endif
    </div>
    </div>
</div>
@endsection
