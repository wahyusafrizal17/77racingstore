@extends('layouts.speedshop')

@php
    $config = $config ?? [
        'pageTitle' => 'Purchase Order',
        'description' => 'Purchase Order ke warehouse 77racing untuk diproses pengiriman ke Speedshop Anda.',
    ];
    $filterBaseClass = 'h-8 w-full rounded-lg border border-gray-200 bg-white px-2.5 text-[13px] font-normal text-gray-700 placeholder-gray-400 outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/40';
@endphp

@section('title', 'Stock IN — Purchase Order')

@section('content')
@push('styles')
@include('partials.speedshop.dashboard-load-animations')
@include('partials.speedshop.dashboard-manager-template-styles')
@include('partials.speedshop.work-order-create-styles')
<style>
.ss-dashboard .po-summary-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 20px;
}
@media (max-width: 640px) {
  .ss-dashboard .po-summary-grid { grid-template-columns: 1fr; }
}
.ss-dashboard .pay-icon.blue { background: rgba(46,107,230,0.10); color: #2E6BE6; }
.ss-dashboard .pay-icon.amber { background: rgba(245,158,11,0.10); color: #D97706; }
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
            <p class="ss-dash-loading-text">Memuat purchase order…</p>
            <p class="ss-dash-loading-sub">Menyiapkan daftar PO</p>
        </div>
    </div>

    <div class="ss-dash-content space-y-5">
    <div class="po-summary-grid ss-dash-enter" style="--ss-delay: 80ms">
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 0">
            <div class="pay-card-top">
                <span class="pay-card-label">Total Purchase Order</span>
                <span class="pay-icon blue">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </span>
            </div>
            <div class="pay-amount">{{ number_format((int) ($summaryTotalPo ?? 0)) }}</div>
            <p class="pay-period">Semua riwayat PO ke warehouse 77racing</p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 1">
            <div class="pay-card-top">
                <span class="pay-card-label">Dalam Proses</span>
                <span class="pay-icon amber">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="pay-amount">{{ number_format((int) ($summaryMenunggu ?? 0)) }}</div>
            <p class="pay-period">Belum selesai diproses</p>
        </div>
    </div>

    <div class="panel-card panel-card-flush ss-dash-enter" style="--ss-delay: 120ms">
        <div class="flex flex-col gap-3 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">{{ $config['pageTitle'] }}</h2>
                <p class="mt-1 text-[13px] text-gray-500">
                    {{ $config['description'] }}
                </p>
            </div>
            <a href="{{ route('speedshop.stock-in.pembelian-mo.create') }}" class="btn-primary inline-flex h-10 shrink-0 items-center gap-2 px-5">
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Buat Purchase Order
            </a>
        </div>

        <div class="overflow-x-auto">
            <form method="GET" action="{{ route('speedshop.stock-in.pembelian-mo.index') }}" @submit="loading = true">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="bg-[#f7f3f4]">
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Tanggal</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No PO</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Speedshop</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Status</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Diajukan Oleh</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 w-24"></th>
                        </tr>
                        <tr class="border-t border-gray-200 bg-[#fbf8f9]">
                            <th class="px-5 py-2"></th>
                            <th class="px-5 py-2"></th>
                            <th class="px-5 py-2">
                                <input name="kode" value="{{ request('kode') }}" placeholder="Cari no PO..." class="{{ $filterBaseClass }}" onchange="this.form.submit()">
                            </th>
                            <th class="px-5 py-2">
                                <input name="speedshop" value="{{ request('speedshop') }}" placeholder="Cari speedshop..." class="{{ $filterBaseClass }}" onchange="this.form.submit()">
                            </th>
                            <th class="px-5 py-2">
                                <select name="status" class="{{ $filterBaseClass }}" onchange="this.form.submit()">
                                    <option value="">Semua</option>
                                    <option value="submitted" @selected(request('status') === 'submitted')>Diajukan</option>
                                    <option value="warehouse_decision" @selected(request('status') === 'warehouse_decision')>Verifikasi Warehouse</option>
                                    <option value="processed" @selected(request('status') === 'processed')>Diproses</option>
                                    <option value="speedshop_decision" @selected(request('status') === 'speedshop_decision')>Verifikasi Speedshop</option>
                                    <option value="finished" @selected(request('status') === 'finished')>Selesai</option>
                                </select>
                            </th>
                            <th class="px-5 py-2">
                                <input name="dibuat_oleh" value="{{ request('dibuat_oleh') }}" placeholder="Cari user..." class="{{ $filterBaseClass }}" onchange="this.form.submit()">
                            </th>
                            <th class="px-5 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    @forelse(($rows ?? []) as $r)
                    <tr class="transition hover:bg-[#fcfafb]">
                        <td class="px-5 py-4 text-gray-500">{{ $loop->iteration }}</td>
                        <td class="px-5 py-4 text-gray-600">{{ \Illuminate\Support\Carbon::parse($r['tanggal'])->format('d M Y H:i') }}</td>
                        <td class="px-5 py-4 font-semibold text-gray-900">{{ $r['kode'] }}</td>
                        <td class="px-5 py-4 text-gray-700">{{ $r['speedshop'] ?? '-' }}</td>
                        <td class="px-5 py-4">
                            @php $st = $r['status']; @endphp
                            @if($st === 'finished')
                                <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Selesai</span>
                            @elseif($st === 'speedshop_decision')
                                <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">Verifikasi Speedshop</span>
                            @elseif($st === 'processed')
                                <span class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">Diproses</span>
                            @elseif($st === 'warehouse_decision')
                                <span class="inline-flex items-center rounded-full border border-rose-200 bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700">Verifikasi Warehouse</span>
                            @else
                                <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">Diajukan</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-gray-700">{{ $r['dibuat_oleh'] ?? '-' }}</td>
                        <td class="px-5 py-4">
                            @if(!empty($r['detail_url']))
                                <a href="{{ $r['detail_url'] }}" class="btn-detail">
                                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    Detail
                                </a>
                            @else
                                <span class="text-xs text-gray-400">-</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-5 py-12 text-center text-gray-400">
                            @if(request()->hasAny(['kode', 'speedshop', 'status', 'dibuat_oleh']))
                                Tidak ada purchase order sesuai filter.
                            @else
                                <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <p class="mt-3 text-sm font-medium">Belum ada purchase order</p>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                    </tbody>
                </table>
            </form>
        </div>
    </div>
    </div>
</div>
@endsection
