@extends('layouts.speedshop')

@php
    $filterBaseClass = 'h-8 w-full rounded-lg border border-gray-200 bg-white px-2.5 text-[13px] font-normal text-gray-700 placeholder-gray-400 outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/40';
    $isVerifikasiPending = (bool) ($verifikasiPembelianLuarManager ?? false);
    $listAllSpeedshops = (bool) ($listAllSpeedshops ?? false);
    $indexRoute = $isVerifikasiPending ? 'speedshop.verifikasi.pembelian-luar.index' : 'speedshop.stock-in.pembelian-luar.index';
    $showRoute = $isVerifikasiPending ? 'speedshop.verifikasi.pembelian-luar.show' : 'speedshop.stock-in.pembelian-luar.show';
    $statusFilter = request('status', '');
    $emptyColspan = $listAllSpeedshops ? 8 : 7;
    $pageTitle = $isVerifikasiPending ? 'Verifikasi Pembelian Part Luar' : 'Pembelian Part Luar';
    $pageDescription = $isVerifikasiPending
        ? 'Daftar pengajuan pembelian part luar menunggu verifikasi'
        : 'Input stok masuk dari supplier luar (perlu verifikasi jika total di atas batas)';
    $defaultPeriodMode = 'bulanan';
    $plPeriodLabel = match ($mode ?? $defaultPeriodMode) {
        'harian' => \Carbon\Carbon::parse($selectedDate ?? now())->translatedFormat('d M Y'),
        'bulanan' => \Carbon\Carbon::parse(($selectedMonth ?? now()->format('Y-m')).'-01')->translatedFormat('F Y'),
        'tahunan' => (string) ($selectedYear ?? now()->year),
        'range' => ($dari ?? now())->translatedFormat('d M Y').' – '.($sampai ?? now())->translatedFormat('d M Y'),
        default => \Carbon\Carbon::parse(($selectedMonth ?? now()->format('Y-m')).'-01')->translatedFormat('F Y'),
    };
    $filterQueryKeys = ['mode', 'tanggal', 'bulan', 'tahun', 'dari', 'sampai', 'kode', 'diajukan_oleh', 'status'];
    $plCardBaseQuery = array_filter([
        'mode' => $mode ?? $defaultPeriodMode,
        'tanggal' => ($mode ?? $defaultPeriodMode) === 'harian' ? ($selectedDate ?? now()->toDateString()) : null,
        'bulan' => ($mode ?? '') === 'bulanan' ? ($selectedMonth ?? now()->format('Y-m')) : null,
        'tahun' => ($mode ?? '') === 'tahunan' ? ($selectedYear ?? now()->year) : null,
        'dari' => ($mode ?? '') === 'range' ? ($selectedDari ?? ($dari ?? now())->format('Y-m-d')) : null,
        'sampai' => ($mode ?? '') === 'range' ? ($selectedSampai ?? ($sampai ?? now())->format('Y-m-d')) : null,
        'kode' => request('kode'),
        'diajukan_oleh' => request('diajukan_oleh'),
    ], fn ($v) => $v !== null && $v !== '');
    $plCardUrl = fn (?string $status = null) => route($indexRoute, $status
        ? array_merge($plCardBaseQuery, ['status' => $status])
        : $plCardBaseQuery);
    $plCardPendingLabel = $isVerifikasiPending ? 'Menunggu' : 'Pending';
    $plCardApprovedLabel = $isVerifikasiPending ? 'Selesai' : 'Disetujui';
    $plCardRejectedLabel = 'Ditolak';
@endphp

@section('title', $pageTitle)

@section('content')
@push('styles')
@include('partials.speedshop.dashboard-load-animations')
@include('partials.speedshop.dashboard-manager-template-styles')
@include('partials.speedshop.work-order-create-styles')
<style>
.ss-dashboard .pl-summary-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 20px;
}
@media (max-width: 1024px) {
  .ss-dashboard .pl-summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 640px) {
  .ss-dashboard .pl-summary-grid { grid-template-columns: 1fr; }
}
.ss-dashboard .pay-card.pay-card-filter {
  cursor: pointer;
  text-decoration: none;
  color: inherit;
}
.ss-dashboard .pay-icon.blue { background: rgba(46,107,230,0.10); color: #2E6BE6; }
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
            <p class="ss-dash-loading-text">Memuat pembelian luar…</p>
            <p class="ss-dash-loading-sub">Menyiapkan data periode terpilih</p>
        </div>
    </div>

    <div class="ss-dash-content space-y-5">
    @include('partials.speedshop.dashboard-filter-strip', [
        'filterAction' => route($indexRoute),
        'dashboardFilterExports' => [],
        'dashboardFilterQueryKeys' => $filterQueryKeys,
        'filterHiddenInputs' => array_filter([
            'status' => request('status'),
            'kode' => request('kode'),
            'diajukan_oleh' => request('diajukan_oleh'),
        ], fn ($v) => $v !== null && $v !== ''),
    ])

    <div class="pl-summary-grid ss-dash-enter" style="--ss-delay: 80ms">
        <a href="{{ $plCardUrl() }}" @click="loading = true" class="pay-card pay-card-filter ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 0">
            <div class="pay-card-top">
                <span class="pay-card-label">Total Transaksi</span>
                <span class="pay-icon blue">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </span>
            </div>
            <div class="pay-amount">{{ number_format((int) ($totalAll ?? 0)) }}</div>
            <p class="pay-period">Semua transaksi · {{ $plPeriodLabel }}</p>
        </a>
        <a href="{{ $plCardUrl('pending') }}" @click="loading = true" class="pay-card pay-card-filter ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 1">
            <div class="pay-card-top">
                <span class="pay-card-label">{{ $plCardPendingLabel }}</span>
                <span class="pay-icon amber">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="pay-amount">{{ number_format((int) ($totalPending ?? 0)) }}</div>
            <p class="pay-period">{{ $isVerifikasiPending ? 'Belum diverifikasi' : 'Menunggu verifikasi' }}</p>
        </a>
        <a href="{{ $plCardUrl('approved') }}" @click="loading = true" class="pay-card pay-card-filter ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 2">
            <div class="pay-card-top">
                <span class="pay-card-label">{{ $plCardApprovedLabel }}</span>
                <span class="pay-icon green">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </span>
            </div>
            <div class="pay-amount">{{ number_format((int) ($totalApproved ?? 0)) }}</div>
            <p class="pay-period">{{ $isVerifikasiPending ? 'Pengajuan disetujui' : 'Transaksi approved periode' }}</p>
        </a>
        <a href="{{ $plCardUrl('rejected') }}" @click="loading = true" class="pay-card pay-card-filter ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 3">
            <div class="pay-card-top">
                <span class="pay-card-label">{{ $plCardRejectedLabel }}</span>
                <span class="pay-icon red">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </span>
            </div>
            <div class="pay-amount">{{ number_format((int) ($totalRejected ?? 0)) }}</div>
            <p class="pay-period">{{ $isVerifikasiPending ? 'Pengajuan ditolak' : 'Transaksi rejected periode' }}</p>
        </a>
    </div>

    <div class="panel-card panel-card-flush ss-dash-enter" style="--ss-delay: 120ms">
        <div class="flex flex-col gap-3 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">{{ $pageTitle }}</h2>
                <p class="mt-1 text-[13px] text-gray-500">
                    {{ $pageDescription }} · {{ $plPeriodLabel }}
                    @if($listAllSpeedshops)
                        · Semua speedshop
                    @endif
                </p>
            </div>
            @unless($isVerifikasiPending || auth()->user()->isAccounting())
            <a href="{{ route('speedshop.stock-in.pembelian-luar.create') }}" class="btn-primary inline-flex h-10 shrink-0 items-center gap-2 px-5">
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Input Transaksi
            </a>
            @endunless
        </div>

        <div class="overflow-x-auto">
            <form method="GET" action="{{ route($indexRoute) }}" @submit="loading = true">
                <input type="hidden" name="mode" value="{{ $mode ?? $defaultPeriodMode }}">
                @if(($mode ?? $defaultPeriodMode) === 'harian')
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
                            @if($listAllSpeedshops)
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Speedshop</th>
                            @endif
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Kode</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Tanggal</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Diajukan Oleh</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-center">Jumlah Item</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Status</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 w-28"></th>
                        </tr>
                        <tr class="border-t border-gray-200 bg-[#fbf8f9]">
                            <th class="px-5 py-2"></th>
                            @if($listAllSpeedshops)
                            <th class="px-5 py-2"></th>
                            @endif
                            <th class="px-5 py-2">
                                <input name="kode" value="{{ request('kode') }}" placeholder="Cari kode..." class="{{ $filterBaseClass }}" onchange="this.form.submit()">
                            </th>
                            <th class="px-5 py-2"></th>
                            <th class="px-5 py-2">
                                <input name="diajukan_oleh" value="{{ request('diajukan_oleh') }}" placeholder="Cari user..." class="{{ $filterBaseClass }}" onchange="this.form.submit()">
                            </th>
                            <th class="px-5 py-2"></th>
                            <th class="px-5 py-2">
                                <select name="status" class="{{ $filterBaseClass }}" onchange="this.form.submit()">
                                    <option value="" @selected($statusFilter === '')>{{ $isVerifikasiPending ? 'Semua' : 'Semua status' }}</option>
                                    <option value="pending" @selected($statusFilter === 'pending')>{{ $isVerifikasiPending ? 'Menunggu' : 'Pending' }}</option>
                                    <option value="approved" @selected($statusFilter === 'approved')>{{ $isVerifikasiPending ? 'Selesai' : 'Approved' }}</option>
                                    <option value="rejected" @selected($statusFilter === 'rejected')>{{ $isVerifikasiPending ? 'Ditolak' : 'Rejected' }}</option>
                                </select>
                            </th>
                            <th class="px-5 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    @forelse($transaksis as $i => $t)
                    <tr class="transition hover:bg-[#fcfafb]">
                        <td class="px-5 py-4 text-gray-500">{{ $transaksis->firstItem() + $i }}</td>
                        @if($listAllSpeedshops)
                        <td class="px-5 py-4 whitespace-nowrap text-gray-700">{{ $t->user?->speedshop?->nama ?? '-' }}</td>
                        @endif
                        <td class="px-5 py-4 font-semibold text-gray-900">{{ $t->kode }}</td>
                        <td class="px-5 py-4 text-gray-600">{{ $t->created_at->format('d M Y H:i') }}</td>
                        <td class="px-5 py-4 text-gray-600">{{ $t->user->name }}</td>
                        <td class="px-5 py-4 text-center text-gray-600">{{ $t->items->count() }}</td>
                        <td class="px-5 py-4">
                            @php
                                $statusLabel = match ($t->status) {
                                    'pending' => $isVerifikasiPending ? 'Menunggu' : 'Pending',
                                    'approved' => $isVerifikasiPending ? 'Selesai' : 'Approved',
                                    'rejected' => $isVerifikasiPending ? 'Ditolak' : 'Rejected',
                                    default => ucfirst((string) $t->status),
                                };
                                $statusClass = match ($t->status) {
                                    'pending' => 'border-amber-200 bg-amber-50 text-amber-700',
                                    'approved' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                                    'rejected' => 'border-red-200 bg-red-50 text-red-700',
                                    default => 'border-gray-200 bg-gray-50 text-gray-700',
                                };
                            @endphp
                            <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">{{ $statusLabel }}</span>
                        </td>
                        <td class="px-5 py-4">
                            <a href="{{ route($showRoute, $t) }}" class="btn-detail">
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                Detail
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $emptyColspan }}" class="px-5 py-12 text-center text-gray-400">
                            @if(request()->hasAny(['kode', 'diajukan_oleh', 'status', 'search']))
                                Tidak ada transaksi sesuai filter.
                            @else
                                <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <p class="mt-3 text-sm font-medium">Belum ada transaksi untuk periode ini</p>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                    </tbody>
                </table>
            </form>
        </div>

        @if($transaksis->hasPages())
        <div class="border-t border-gray-100 px-5 py-4">{{ $transaksis->links() }}</div>
        @endif
    </div>
    </div>
</div>
@endsection
