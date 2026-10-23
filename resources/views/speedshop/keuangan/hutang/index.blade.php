@extends('layouts.speedshop')

@section('title', 'Keuangan — Hutang')

@section('content')
@php
    $filters = $filters ?? [];
    $stats = $stats ?? ['total' => 0, 'belum_lunas' => 0, 'lunas' => 0, 'nominal_belum_lunas' => 0];
    $fv = fn (string $k) => old($k, $filters[$k] ?? '');
    $statusFilter = $fv('status');
    $tableFilterClass = 'h-8 w-full rounded-lg border border-gray-200 bg-white px-2.5 text-[13px] font-normal text-gray-700 placeholder-gray-400 outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/40';
    $indexRoute = 'speedshop.keuangan.hutang.index';
    $mode = $mode ?? 'bulanan';
    $filterQueryKeys = ['mode', 'tanggal', 'bulan', 'tahun', 'dari', 'sampai', 'status', 'kode', 'counterparty'];
    $hutangPeriodLabel = match ($mode) {
        'harian' => \Carbon\Carbon::parse($selectedDate ?? now())->translatedFormat('d M Y'),
        'bulanan' => \Carbon\Carbon::parse(($selectedMonth ?? now()->format('Y-m')).'-01')->translatedFormat('F Y'),
        'tahunan' => (string) ($selectedYear ?? now()->year),
        'range' => ($dari ?? now())->translatedFormat('d M Y').' – '.($sampai ?? now())->translatedFormat('d M Y'),
        default => now()->translatedFormat('F Y'),
    };
    $hutangCardBaseQuery = array_filter([
        'mode' => $mode,
        'tanggal' => $mode === 'harian' ? ($selectedDate ?? now()->toDateString()) : null,
        'bulan' => $mode === 'bulanan' ? ($selectedMonth ?? now()->format('Y-m')) : null,
        'tahun' => $mode === 'tahunan' ? ($selectedYear ?? now()->year) : null,
        'dari' => $mode === 'range' ? ($selectedDari ?? ($dari ?? now())->format('Y-m-d')) : null,
        'sampai' => $mode === 'range' ? ($selectedSampai ?? ($sampai ?? now())->format('Y-m-d')) : null,
        'kode' => request('kode'),
        'counterparty' => request('counterparty'),
    ], fn ($v) => $v !== null && $v !== '');
    $hutangCardUrl = fn (?string $status = null) => route($indexRoute, $status
        ? array_merge($hutangCardBaseQuery, ['status' => $status])
        : $hutangCardBaseQuery);
    $hasActiveFilters = collect(request()->only($filterQueryKeys))->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
@endphp
@push('styles')
@include('partials.speedshop.dashboard-load-animations')
@include('partials.speedshop.dashboard-manager-template-styles')
@include('partials.speedshop.work-order-create-styles')
<style>
.ss-dashboard .tagihan-summary-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 20px;
}
@media (max-width: 1024px) {
  .ss-dashboard .tagihan-summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 640px) {
  .ss-dashboard .tagihan-summary-grid { grid-template-columns: 1fr; }
}
.ss-dashboard .pay-card.pay-card-filter {
  cursor: pointer;
  text-decoration: none;
  color: inherit;
}
.ss-dashboard .pay-icon.blue { background: rgba(46,107,230,0.10); color: #2E6BE6; }
.ss-dashboard .pay-icon.red { background: rgba(239,68,68,0.10); color: #EF4444; }
.ss-dashboard .pay-icon.amber { background: rgba(245,158,11,0.10); color: #D97706; }
.ss-dashboard .pay-icon.green { background: rgba(16,185,129,0.10); color: #10B981; }
</style>
@endpush

<div
    class="ss-dashboard ss-dash-page ss-wo-create"
    x-data="{ loading: false }"
    x-init="$nextTick(() => { requestAnimationFrame(() => $el.classList.add('is-ready')); })"
    :class="loading && 'is-loading'"
>
<div class="ss-dash-content space-y-5">
    @include('partials.speedshop.dashboard-filter-strip', [
        'filterAction' => route($indexRoute),
        'dashboardFilterExports' => [],
        'dashboardFilterQueryKeys' => $filterQueryKeys,
        'filterHiddenInputs' => array_filter([
            'status' => request('status'),
            'kode' => request('kode'),
            'counterparty' => request('counterparty'),
        ], fn ($v) => $v !== null && $v !== ''),
    ])

    <div class="tagihan-summary-grid ss-dash-enter" style="--ss-delay: 80ms">
        <a href="{{ $hutangCardUrl() }}" @click="loading = true" class="pay-card pay-card-filter ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 0">
            <div class="pay-card-top">
                <span class="pay-card-label">Total Supplier</span>
                <span class="pay-icon blue">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14l6-6m-5 5.5a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0zm10 0a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/></svg>
                </span>
            </div>
            <div class="pay-amount">{{ number_format($stats['total'] ?? 0, 0, ',', '.') }}</div>
            <p class="pay-period">Supplier pada periode · {{ $hutangPeriodLabel }}</p>
        </a>
        <a href="{{ $hutangCardUrl('belum_lunas') }}" @click="loading = true" class="pay-card pay-card-filter ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 1">
            <div class="pay-card-top">
                <span class="pay-card-label">Belum lunas</span>
                <span class="pay-icon red">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="pay-amount">{{ number_format($stats['belum_lunas'] ?? 0, 0, ',', '.') }}</div>
            <p class="pay-period">Supplier masih punya hutang</p>
        </a>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 2">
            <div class="pay-card-top">
                <span class="pay-card-label">Sisa hutang</span>
                <span class="pay-icon amber">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="pay-amount">Rp {{ number_format($stats['nominal_belum_lunas'] ?? 0, 0, ',', '.') }}</div>
            <p class="pay-period">Nominal belum dibayar</p>
        </div>
        <a href="{{ $hutangCardUrl('lunas') }}" @click="loading = true" class="pay-card pay-card-filter ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 3">
            <div class="pay-card-top">
                <span class="pay-card-label">Lunas</span>
                <span class="pay-icon green">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </span>
            </div>
            <div class="pay-amount">{{ number_format($stats['lunas'] ?? 0, 0, ',', '.') }}</div>
            <p class="pay-period">Stok In lunas · {{ $hutangPeriodLabel }}</p>
        </a>
    </div>

    <div class="panel-card panel-card-flush ss-dash-enter" style="--ss-delay: 120ms">
        <div class="border-b border-gray-100 px-5 py-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">Hutang per supplier</h2>
                    <p class="mt-1 text-[13px] text-gray-500">
                        Diringkas per supplier · detail menampilkan Stok In · {{ $hutangPeriodLabel }}
                        @if($hasActiveFilters)
                        · <a href="{{ route($indexRoute) }}" class="font-medium text-red-600 transition hover:text-red-700">Hapus filter</a>
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <form method="GET" action="{{ route($indexRoute) }}" @submit="loading = true">
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
            <div class="overflow-x-auto">
                <table class="w-full min-w-max text-left text-sm">
                    <thead>
                        <tr class="bg-[#f7f3f4]">
                            <th class="w-16 px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No.</th>
                            <th class="min-w-[180px] px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Supplier</th>
                            <th class="min-w-[120px] px-5 py-3.5 text-center text-[11px] font-semibold uppercase tracking-wider text-gray-500">Stok In</th>
                            <th class="min-w-[140px] px-5 py-3.5 text-center text-[11px] font-semibold uppercase tracking-wider text-gray-500">Belum lunas</th>
                            <th class="min-w-[150px] px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Jatuh tempo terdekat</th>
                            <th class="min-w-[150px] px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-gray-500">Sisa hutang</th>
                            <th class="min-w-[130px] px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Status</th>
                            <th class="w-28 px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Aksi</th>
                        </tr>
                        <tr class="border-t border-gray-200 bg-[#fbf8f9]">
                            <th class="px-5 py-2"></th>
                            <th class="px-5 py-2">
                                <input type="text" name="counterparty" value="{{ $fv('counterparty') }}" placeholder="Cari supplier…" class="{{ $tableFilterClass }}" onchange="this.form.submit()">
                            </th>
                            <th class="px-5 py-2">
                                <input type="text" name="kode" value="{{ $fv('kode') }}" placeholder="Cari stok in…" class="{{ $tableFilterClass }}" onchange="this.form.submit()">
                            </th>
                            <th class="px-5 py-2"></th>
                            <th class="px-5 py-2"></th>
                            <th class="px-5 py-2"></th>
                            <th class="px-5 py-2">
                                <select name="status" class="{{ $tableFilterClass }}" onchange="this.form.submit()">
                                    <option value="" @selected($statusFilter === '' || $statusFilter === 'semua')>Semua status</option>
                                    <option value="belum_lunas" @selected($statusFilter === 'belum_lunas')>Belum lunas</option>
                                    <option value="lunas" @selected($statusFilter === 'lunas')>Lunas</option>
                                </select>
                            </th>
                            <th class="px-5 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($supplierGroups as $i => $g)
                        @php
                            $rowNo = $supplierGroups->firstItem() + $i;
                            $hasUnpaid = (int) $g->jumlah_belum_lunas > 0;
                            $jatuhTempo = $g->jatuh_tempo_terdekat ? \Carbon\Carbon::parse($g->jatuh_tempo_terdekat) : null;
                        @endphp
                        <tr class="transition hover:bg-[#fcfafb]">
                            <td class="px-5 py-4 tabular-nums text-gray-500">{{ $rowNo }}</td>
                            <td class="px-5 py-4 align-top">
                                <div class="font-semibold text-gray-900">{{ $g->supplier_nama }}</div>
                                @if($g->supplier_kode)
                                <div class="mt-0.5 font-mono text-xs text-gray-500">{{ $g->supplier_kode }}</div>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-center tabular-nums text-gray-700">{{ number_format((int) $g->jumlah_invoice, 0, ',', '.') }}</td>
                            <td class="px-5 py-4 text-center tabular-nums {{ $hasUnpaid ? 'font-semibold text-red-700' : 'text-gray-500' }}">{{ number_format((int) $g->jumlah_belum_lunas, 0, ',', '.') }}</td>
                            <td class="px-5 py-4 align-top text-gray-800">
                                {{ $jatuhTempo?->translatedFormat('d M Y') ?? '—' }}
                            </td>
                            <td class="px-5 py-4 text-right font-semibold tabular-nums {{ $hasUnpaid ? 'text-red-700' : 'text-emerald-700' }}">Rp {{ number_format((int) $g->total_belum_lunas, 0, ',', '.') }}</td>
                            <td class="px-5 py-4 align-top">
                                @if($hasUnpaid)
                                <span class="inline-flex items-center rounded-lg border border-red-200 bg-red-50 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-red-700">Belum lunas</span>
                                @else
                                <span class="inline-flex items-center rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-emerald-700">Lunas</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end">
                                    <a href="{{ route('speedshop.keuangan.hutang.supplier', $g->supplier_id) }}" class="btn-detail" title="Detail">
                                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        Detail
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-5 py-12 text-center text-sm text-gray-500">
                                @if($hasActiveFilters)
                                    Tidak ada supplier sesuai filter.
                                @else
                                    Belum ada hutang supplier untuk periode ini. Hutang muncul setelah Stok In Supplier disetujui.
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </form>

        @if($supplierGroups->hasPages())
        <div class="border-t border-gray-100 px-5 py-4">{{ $supplierGroups->links() }}</div>
        @endif
    </div>
</div>
</div>
@endsection
