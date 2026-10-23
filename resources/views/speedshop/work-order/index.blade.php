@extends('layouts.speedshop')

@php
    $isWipPage = request()->routeIs('speedshop.wip');
    $filterBaseClass = 'h-8 w-full rounded-lg border border-gray-200 bg-white px-2.5 text-[13px] font-normal text-gray-700 placeholder-gray-400 outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/40';
@endphp

@section('title', $isWipPage ? 'Work In Progress' : 'Work Order')

@section('content')
@if($isWipPage)
@push('styles')
@include('partials.speedshop.dashboard-load-animations')
@include('partials.speedshop.dashboard-manager-template-styles')
@include('partials.speedshop.work-order-create-styles')
<style>
.ss-dashboard .wip-summary-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 20px;
}
@media (max-width: 768px) {
  .ss-dashboard .wip-summary-grid { grid-template-columns: 1fr; }
}
</style>
@endpush

@php
    $wipPeriodLabel = match ($mode ?? 'harian') {
        'harian' => \Carbon\Carbon::parse($selectedDate ?? now())->translatedFormat('d M Y'),
        'bulanan' => \Carbon\Carbon::parse(($selectedMonth ?? now()->format('Y-m')).'-01')->translatedFormat('F Y'),
        'tahunan' => (string) ($selectedYear ?? now()->year),
        'range' => ($dari ?? now())->translatedFormat('d M Y').' – '.($sampai ?? now())->translatedFormat('d M Y'),
        default => ($dari ?? now())->translatedFormat('d M Y'),
    };
@endphp
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
            <p class="ss-dash-loading-text">Memuat work in progress…</p>
            <p class="ss-dash-loading-sub">Menyiapkan data periode terpilih</p>
        </div>
    </div>

    <div class="ss-dash-content space-y-5">
    @include('partials.speedshop.dashboard-filter-strip', [
        'filterAction' => route('speedshop.wip'),
        'dashboardFilterExports' => [],
        'dashboardFilterQueryKeys' => ['mode', 'tanggal', 'bulan', 'tahun', 'dari', 'sampai'],
    ])

    <div class="wip-summary-grid ss-dash-enter" style="--ss-delay: 80ms">
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 0">
            <div class="pay-card-top">
                <span class="pay-card-label">Antrian</span>
                <span class="pay-icon amber">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="pay-amount">{{ number_format($wipSummaryAntri ?? 0) }}</div>
            <p class="pay-period">WO menunggu cetak / assign mekanik</p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 1">
            <div class="pay-card-top">
                <span class="pay-card-label">Estimasi Total</span>
                <span class="pay-icon purple">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="pay-amount"><span class="curr">Rp</span>{{ number_format($wipSummaryEstimasi ?? 0, 0, ',', '.') }}</div>
            <p class="pay-period">Akumulasi estimasi biaya WO antrian</p>
        </div>
    </div>

    <div class="panel-card panel-card-flush ss-dash-enter" style="--ss-delay: 120ms">
        <div class="flex flex-col gap-3 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">Work In Progress</h2>
                <p class="mt-1 text-[13px] text-gray-500">Daftar work order aktif speedshop · {{ $wipPeriodLabel }}</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <form method="GET" action="{{ route('speedshop.wip') }}">
                <input type="hidden" name="mode" value="{{ $mode ?? 'harian' }}">
                @if(($mode ?? 'harian') === 'harian')
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
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Kode</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Tanggal</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Pelanggan</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No Polisi</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Sumber</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Kategori</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Status</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500"></th>
                        </tr>
                        <tr class="border-t border-gray-200 bg-[#fbf8f9]">
                            <th class="px-5 py-2"></th>
                            <th class="px-5 py-2">
                                <input name="kode" value="{{ request('kode') }}" placeholder="Cari kode..." class="{{ $filterBaseClass }}" onchange="this.form.submit()">
                            </th>
                            <th class="px-5 py-2"></th>
                            <th class="px-5 py-2">
                                <input name="pelanggan" value="{{ request('pelanggan') }}" placeholder="Cari pelanggan..." class="{{ $filterBaseClass }}" onchange="this.form.submit()">
                            </th>
                            <th class="px-5 py-2">
                                <input name="no_polisi" value="{{ request('no_polisi') }}" placeholder="Cari no polisi..." class="{{ $filterBaseClass }}" onchange="this.form.submit()">
                            </th>
                            <th class="px-5 py-2">
                                <select name="sumber" class="{{ $filterBaseClass }}" onchange="this.form.submit()">
                                    <option value="">Semua</option>
                                    @foreach(\App\Models\ServiceOrder::SUMBER_KEDATANGAN as $val => $label)
                                        <option value="{{ $val }}" @selected(request('sumber') === (string) $val)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </th>
                            <th class="px-5 py-2">
                                <select name="kategori_service" class="{{ $filterBaseClass }}" onchange="this.form.submit()">
                                    <option value="">Semua</option>
                                    @foreach(\App\Models\ServiceOrder::KATEGORI_SERVICE as $val => $label)
                                        <option value="{{ $val }}" @selected(request('kategori_service') === (string) $val)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </th>
                            <th class="px-5 py-2"></th>
                            <th class="px-5 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    @forelse($orders as $i => $o)
                    <tr class="transition hover:bg-[#fcfafb]">
                        <td class="px-5 py-4 text-gray-500">{{ $orders->firstItem() + $i }}</td>
                        <td class="px-5 py-4 font-semibold text-gray-900">{{ $o->kode_servis }}</td>
                        <td class="px-5 py-4 text-gray-600">{{ $o->tanggal_masuk->format('d M Y') }}</td>
                        <td class="px-5 py-4 text-gray-700">{{ $o->pelanggan->nama ?? '-' }}</td>
                        <td class="px-5 py-4 text-gray-600">{{ $o->kendaraan?->nomor_polisi ?? '-' }}</td>
                        <td class="px-5 py-4 text-gray-600">{{ $o->sumber_kedatangan ? (\App\Models\ServiceOrder::SUMBER_KEDATANGAN[$o->sumber_kedatangan] ?? $o->sumber_kedatangan) : '-' }}</td>
                        <td class="px-5 py-4 text-gray-600">{{ $o->kategori_service ? (\App\Models\ServiceOrder::KATEGORI_SERVICE[$o->kategori_service] ?? $o->kategori_service) : '-' }}</td>
                        <td class="px-5 py-4">
                            @if($o->status === 'antri')
                                <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">Antrian</span>
                            @elseif($o->status === 'proses')
                                <span class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">Dikerjakan</span>
                            @else
                                <span class="inline-flex items-center rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs font-semibold text-gray-700">{{ ucfirst($o->status) }}</span>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            <a href="{{ route('speedshop.wip.show', $o) }}" class="btn-detail">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                Detail
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="px-5 py-12 text-center text-gray-400">Belum ada work order untuk filter ini.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </form>
        </div>

        @if($orders->hasPages())
        <div class="border-t border-gray-100 px-5 py-4">{{ $orders->links() }}</div>
        @endif
    </div>
    </div>
</div>

@else
<div class="space-y-5">
    <nav class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('speedshop.dashboard') }}" class="transition hover:text-gray-700">Home</a>
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <span class="font-medium text-gray-900">Work Order</span>
    </nav>

    <div class="rounded-2xl border border-gray-200 bg-white p-5">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">Work Order</h2>
                <p class="mt-1 text-[13px] text-gray-500">Daftar work order (service order) speedshop</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('speedshop.work-orders.create') }}" class="inline-flex h-10 items-center gap-2 rounded-xl bg-brand px-5 text-sm font-medium text-brand-ink shadow-lg shadow-brand/35 transition hover:bg-brand-hover active:scale-[0.98]">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Buat Work Order
                </a>
            </div>
        </div>

        <div class="mt-5 overflow-x-auto rounded-xl border border-gray-200">
            <form method="GET">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="bg-[#f7f3f4]">
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No.</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Kode</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Tanggal</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Pelanggan</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No Polisi</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Sumber</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Kategori</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Status</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500"></th>
                        </tr>
                        <tr class="border-t border-gray-200 bg-[#fbf8f9]">
                            <th class="px-5 py-2"></th>
                            <th class="px-5 py-2">
                                <input name="kode" value="{{ request('kode') }}" placeholder="Cari kode..." class="{{ $filterBaseClass }}" onchange="this.form.submit()">
                            </th>
                            <th class="px-5 py-2">
                                <input type="date" name="tanggal" value="{{ request('tanggal') }}" class="{{ $filterBaseClass }}" onchange="this.form.submit()">
                            </th>
                            <th class="px-5 py-2">
                                <input name="pelanggan" value="{{ request('pelanggan') }}" placeholder="Cari pelanggan..." class="{{ $filterBaseClass }}" onchange="this.form.submit()">
                            </th>
                            <th class="px-5 py-2">
                                <input name="no_polisi" value="{{ request('no_polisi') }}" placeholder="Cari no polisi..." class="{{ $filterBaseClass }}" onchange="this.form.submit()">
                            </th>
                            <th class="px-5 py-2">
                                <select name="sumber" class="{{ $filterBaseClass }}" onchange="this.form.submit()">
                                    <option value="">Semua</option>
                                    @foreach(\App\Models\ServiceOrder::SUMBER_KEDATANGAN as $val => $label)
                                        <option value="{{ $val }}" @selected(request('sumber') === (string) $val)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </th>
                            <th class="px-5 py-2">
                                <select name="kategori_service" class="{{ $filterBaseClass }}" onchange="this.form.submit()">
                                    <option value="">Semua</option>
                                    @foreach(\App\Models\ServiceOrder::KATEGORI_SERVICE as $val => $label)
                                        <option value="{{ $val }}" @selected(request('kategori_service') === (string) $val)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </th>
                            <th class="px-5 py-2">
                                <select name="status" class="{{ $filterBaseClass }}" onchange="this.form.submit()">
                                    <option value="">Semua</option>
                                    <option value="antri" {{ request('status') === 'antri' ? 'selected' : '' }}>Antrian</option>
                                    <option value="proses" {{ request('status') === 'proses' ? 'selected' : '' }}>Dikerjakan</option>
                                    <option value="selesai" {{ request('status') === 'selesai' ? 'selected' : '' }}>Selesai</option>
                                    <option value="dibatalkan" {{ request('status') === 'dibatalkan' ? 'selected' : '' }}>Dibatalkan</option>
                                </select>
                            </th>
                            <th class="px-5 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    @forelse($orders as $i => $o)
                    <tr class="transition hover:bg-[#fcfafb]">
                        <td class="px-5 py-4 text-gray-500">{{ $orders->firstItem() + $i }}</td>
                        <td class="px-5 py-4 font-semibold text-gray-900">{{ $o->kode_servis }}</td>
                        <td class="px-5 py-4 text-gray-600">{{ $o->tanggal_masuk->format('d M Y') }}</td>
                        <td class="px-5 py-4 text-gray-700">{{ $o->pelanggan->nama ?? '-' }}</td>
                        <td class="px-5 py-4 text-gray-600">{{ $o->kendaraan?->nomor_polisi ?? '-' }}</td>
                        <td class="px-5 py-4 text-gray-600">{{ $o->sumber_kedatangan ? (\App\Models\ServiceOrder::SUMBER_KEDATANGAN[$o->sumber_kedatangan] ?? $o->sumber_kedatangan) : '-' }}</td>
                        <td class="px-5 py-4 text-gray-600">{{ $o->kategori_service ? (\App\Models\ServiceOrder::KATEGORI_SERVICE[$o->kategori_service] ?? $o->kategori_service) : '-' }}</td>
                        <td class="px-5 py-4">
                            @if($o->status === 'antri')
                                <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">Antrian</span>
                            @elseif($o->status === 'proses')
                                <span class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">Dikerjakan</span>
                            @elseif($o->status === 'selesai')
                                <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Selesai</span>
                            @else
                                <span class="inline-flex items-center rounded-full border border-rose-200 bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700">{{ ucfirst($o->status) }}</span>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            <a href="{{ route('speedshop.wip.show', $o) }}" class="btn-detail">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                Detail
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="px-5 py-12 text-center text-gray-400">Belum ada work order.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </form>
        </div>

        @if($orders->hasPages())
        <div class="mt-5">{{ $orders->links() }}</div>
        @endif
    </div>
</div>
@endif
@endsection
