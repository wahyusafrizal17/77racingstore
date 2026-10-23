@extends('layouts.speedshop')

@php
    $activeTipe = $activeTipe ?? (string) request('tipe', '');
    $qsBase = request()->except(['page', 'tipe']);
    $tabBase = 'inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold leading-snug transition';
    $tabOn = 'bg-white text-red-600 shadow-sm ring-1 ring-gray-200/80';
    $tabOff = 'text-gray-600 hover:bg-white/40 hover:text-gray-900';
    $filterBaseClass = 'h-8 w-full rounded-lg border border-gray-200 bg-white px-2.5 text-[13px] font-normal text-gray-700 placeholder-gray-400 outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/40';
    $historyPeriodLabel = match ($mode ?? 'harian') {
        'harian' => \Carbon\Carbon::parse($selectedDate ?? now())->translatedFormat('d M Y'),
        'bulanan' => \Carbon\Carbon::parse(($selectedMonth ?? now()->format('Y-m')).'-01')->translatedFormat('F Y'),
        'tahunan' => (string) ($selectedYear ?? now()->year),
        'range' => ($dari ?? now())->translatedFormat('d M Y').' – '.($sampai ?? now())->translatedFormat('d M Y'),
        default => ($dari ?? now())->translatedFormat('d M Y'),
    };
    $filterHiddenInputs = $activeTipe !== '' ? ['tipe' => $activeTipe] : [];
@endphp

@section('title', !empty($historyForMekanik) ? 'Riwayat' : 'History')

@section('content')
@push('styles')
@include('partials.speedshop.dashboard-load-animations')
@include('partials.speedshop.dashboard-manager-template-styles')
@include('partials.speedshop.work-order-create-styles')
<style>
.ss-dashboard .history-summary-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 20px;
}
.ss-dashboard .history-summary-grid.single {
  grid-template-columns: minmax(0, 1fr);
}
@media (max-width: 640px) {
  .ss-dashboard .history-summary-grid { grid-template-columns: 1fr; }
}
.ss-dashboard .pay-icon.teal { background: rgba(20,184,166,0.10); color: #14B8A6; }
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
            <p class="ss-dash-loading-text">Memuat history…</p>
            <p class="ss-dash-loading-sub">Menyiapkan data periode terpilih</p>
        </div>
    </div>

    <div class="ss-dash-content space-y-5">
    @include('partials.speedshop.dashboard-filter-strip', [
        'filterAction' => route('speedshop.history'),
        'dashboardFilterExports' => [],
        'dashboardFilterQueryKeys' => ['mode', 'tanggal', 'bulan', 'tahun', 'dari', 'sampai', 'tipe'],
        'filterHiddenInputs' => $filterHiddenInputs,
    ])

    <div @class(['history-summary-grid', 'ss-dash-enter', 'single' => !empty($historyForMekanik)]) style="--ss-delay: 80ms">
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 0">
            <div class="pay-card-top">
                <span class="pay-card-label">Jumlah Transaksi</span>
                <span class="pay-icon teal">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </span>
            </div>
            <div class="pay-amount">{{ number_format((int) ($totalTransaksi ?? 0)) }}</div>
            <p class="pay-period">{{ !empty($historyForMekanik) ? 'Motor selesai periode terpilih' : 'Work order selesai periode terpilih' }}</p>
        </div>
        @unless(!empty($historyForMekanik))
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 1">
            <div class="pay-card-top">
                <span class="pay-card-label">Grand Total</span>
                <span class="pay-icon purple">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="pay-amount"><span class="curr">Rp</span>{{ number_format((int) ($grandTotal ?? 0), 0, ',', '.') }}</div>
            <p class="pay-period">Total nilai transaksi selesai</p>
        </div>
        @endunless
    </div>

    <div class="panel-card panel-card-flush ss-dash-enter" style="--ss-delay: 120ms">
        <div class="flex flex-col gap-4 border-b border-gray-100 px-5 py-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h2 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">
                    {{ !empty($historyForMekanik) ? 'Riwayat Motor' : 'History Work Order' }}
                </h2>
                <p class="mt-1 text-[13px] text-gray-500">
                    @if(!empty($historyForMekanik))
                        Motor yang dikerjakan {{ $mekanikNama ?? 'Anda' }} · {{ $historyPeriodLabel }}
                    @else
                        Work order yang sudah selesai · {{ $historyPeriodLabel }}
                        @if(!empty($historyAllSpeedshops))
                            · Semua speedshop
                        @endif
                    @endif
                </p>
            </div>

            @unless(!empty($historyForMekanik))
            <div class="flex max-w-full flex-wrap gap-0.5 overflow-x-auto rounded-xl bg-gray-200/55 p-1 lg:inline-flex lg:w-auto lg:flex-nowrap lg:overflow-visible">
                <a href="{{ route('speedshop.history', $qsBase) }}"
                   class="{{ $tabBase }} {{ $activeTipe === '' ? $tabOn : $tabOff }}">
                    Semua
                </a>
                <a href="{{ route('speedshop.history', array_merge($qsBase, ['tipe' => \App\Models\ServiceOrder::TIPE_SERVIS])) }}"
                   class="{{ $tabBase }} {{ $activeTipe === \App\Models\ServiceOrder::TIPE_SERVIS ? $tabOn : $tabOff }}">
                    Servis &amp; Penggantian Parts
                </a>
                <a href="{{ route('speedshop.history', array_merge($qsBase, ['tipe' => \App\Models\ServiceOrder::TIPE_PEMBELIAN_PART])) }}"
                   class="{{ $tabBase }} {{ $activeTipe === \App\Models\ServiceOrder::TIPE_PEMBELIAN_PART ? $tabOn : $tabOff }}">
                    Pembelian Parts
                </a>
            </div>
            @endunless
        </div>

        <div class="overflow-x-auto">
            <form method="GET" action="{{ route('speedshop.history') }}" @submit="loading = true">
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
                @if($activeTipe !== '')
                <input type="hidden" name="tipe" value="{{ $activeTipe }}">
                @endif
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="bg-[#f7f3f4]">
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No.</th>
                            @if(!empty($historyAllSpeedshops))
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Speedshop</th>
                            @endif
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Kode</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Tanggal</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Jenis Order</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Pelanggan</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No Polisi</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Metode Pembayaran</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Sumber</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Kategori</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-right">Total Bayar</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Status</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500"></th>
                        </tr>
                        <tr class="border-t border-gray-200 bg-[#fbf8f9]">
                            <th class="px-5 py-2"></th>
                            @if(!empty($historyAllSpeedshops))
                            <th class="px-5 py-2"></th>
                            @endif
                            <th class="px-5 py-2">
                                <input name="kode" value="{{ request('kode') }}" placeholder="Cari kode..." class="{{ $filterBaseClass }}" onchange="this.form.submit()">
                            </th>
                            <th class="px-5 py-2"></th>
                            <th class="px-5 py-2"></th>
                            <th class="px-5 py-2">
                                <input name="pelanggan" value="{{ request('pelanggan') }}" placeholder="Cari pelanggan..." class="{{ $filterBaseClass }}" onchange="this.form.submit()">
                            </th>
                            <th class="px-5 py-2">
                                <input name="no_polisi" value="{{ request('no_polisi') }}" placeholder="Cari no polisi..." class="{{ $filterBaseClass }}" onchange="this.form.submit()">
                            </th>
                            <th class="px-5 py-2">
                                <select name="jenis_pembayaran" class="{{ $filterBaseClass }}" onchange="this.form.submit()">
                                    <option value="">Semua</option>
                                    <option value="cash" @selected(request('jenis_pembayaran') === 'cash')>Cash</option>
                                    <option value="transfer" @selected(request('jenis_pembayaran') === 'transfer')>Transfer</option>
                                    <option value="qris" @selected(request('jenis_pembayaran') === 'qris')>QRIS</option>
                                    <option value="edc" @selected(request('jenis_pembayaran') === 'edc')>EDC</option>
                                    <option value="split" @selected(request('jenis_pembayaran') === 'split')>Split bill</option>
                                </select>
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
                            <th class="px-5 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    @forelse($orders as $i => $o)
                    <tr class="transition hover:bg-[#fcfafb]">
                        <td class="px-5 py-4 text-gray-500">{{ $orders->firstItem() + $i }}</td>
                        @if(!empty($historyAllSpeedshops))
                        <td class="px-5 py-4 whitespace-nowrap text-gray-700">{{ $o->speedshop?->nama ?? '-' }}</td>
                        @endif
                        <td class="px-5 py-4 font-semibold text-gray-900">{{ $o->kode_servis }}</td>
                        <td class="px-5 py-4 text-gray-600">{{ $o->tanggal_masuk?->format('d M Y') ?? '-' }}</td>
                        <td class="px-5 py-4 text-gray-700">{{ method_exists($o, 'jenisOrderDisplay') ? $o->jenisOrderDisplay() : ($o->tipe_pemesanan ?? '-') }}</td>
                        <td class="px-5 py-4 text-gray-700">{{ $o->pelanggan->nama ?? '-' }}</td>
                        <td class="px-5 py-4 text-gray-600">{{ $o->kendaraan?->nomor_polisi ?? '-' }}</td>
                        <td class="px-5 py-4 text-gray-600">{{ $o->pembayaranDisplayLabel() }}</td>
                        <td class="px-5 py-4 text-gray-600">{{ $o->sumber_kedatangan ? (\App\Models\ServiceOrder::SUMBER_KEDATANGAN[$o->sumber_kedatangan] ?? $o->sumber_kedatangan) : '-' }}</td>
                        <td class="px-5 py-4 text-gray-600">{{ $o->kategori_service ? (\App\Models\ServiceOrder::KATEGORI_SERVICE[$o->kategori_service] ?? $o->kategori_service) : '-' }}</td>
                        <td class="px-5 py-4 text-right font-semibold tabular-nums text-gray-900">
                            @php $grand = $o->finalGrandTotal(); @endphp
                            Rp {{ number_format($grand, 0, ',', '.') }}
                        </td>
                        <td class="px-5 py-4">
                            <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Selesai</span>
                        </td>
                        <td class="px-5 py-4">
                            <a href="{{ route('speedshop.wip.show', $o) }}" class="btn-detail">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                Detail
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ !empty($historyAllSpeedshops) ? 13 : 12 }}" class="px-5 py-12 text-center text-gray-400">
                            @if(request()->hasAny(['kode', 'pelanggan', 'no_polisi', 'mekanik', 'jenis_pembayaran', 'sumber', 'kategori_service', 'search']))
                                Tidak ada data history sesuai filter.
                            @elseif(!empty($historyForMekanik))
                                Belum ada motor selesai yang dikerjakan pada periode ini.
                            @else
                                Belum ada history work order selesai untuk periode ini.
                            @endif
                        </td>
                    </tr>
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
@endsection
