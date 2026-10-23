@extends('layouts.speedshop')

@section('title', 'Rekap Pemisahan Dana')

@section('content')
@php
    $fmt = fn ($v) => number_format((int) $v, 0, ',', '.');
    $weekColumns = $weekColumns ?? [];
    $rows = $rows ?? [];
    $monthLabel = $monthLabel ?? now()->translatedFormat('F Y');
    $speedshopLabel = ! empty($selectedLaporanSpeedshopName)
        ? strtoupper($selectedLaporanSpeedshopName)
        : (! empty($laporanAllSpeedshops)
            ? 'SEMUA SPEEDSHOP'
            : strtoupper($speedshop?->nama ?? 'SPEEDSHOP'));

    $laporanFilterQueryKeys = ['mode', 'tanggal', 'bulan', 'tahun', 'dari', 'sampai'];
    if (auth()->user()?->isOwner()) {
        $laporanFilterQueryKeys[] = 'speedshop_id';
    }

    $rowToneMap = [
        'row-red' => ['bg' => 'bg-red-50/60', 'hover' => 'hover:bg-red-50'],
        'row-blue' => ['bg' => 'bg-sky-50/60', 'hover' => 'hover:bg-sky-50'],
        'row-green' => ['bg' => 'bg-emerald-50/60', 'hover' => 'hover:bg-emerald-50'],
        'row-yellow' => ['bg' => 'bg-amber-50/60', 'hover' => 'hover:bg-amber-50'],
        'row-orange' => ['bg' => 'bg-orange-50/60', 'hover' => 'hover:bg-orange-50'],
        'row-gray' => ['bg' => 'bg-gray-50/60', 'hover' => 'hover:bg-gray-50'],
        'row-teal' => ['bg' => 'bg-teal-50/60', 'hover' => 'hover:bg-teal-50'],
        'row-purple' => ['bg' => 'bg-violet-50/60', 'hover' => 'hover:bg-violet-50'],
    ];

    $cardOmset = (int) ($cardOmset ?? 0);
    $cardHpp = (int) ($cardHpp ?? 0);
    $cardAlokasiBiaya = (int) ($cardAlokasiBiaya ?? 0);
    $cardSisaProfit = (int) ($cardSisaProfit ?? 0);
    $cardTotalBiaya = $cardHpp + $cardAlokasiBiaya;
@endphp

@push('styles')
@include('partials.speedshop.dashboard-load-animations')
@include('partials.speedshop.dashboard-manager-template-styles')
@include('partials.speedshop.work-order-create-styles')
<style>
.ss-dashboard .rpd-summary-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 20px;
}
@media (max-width: 900px) {
  .ss-dashboard .rpd-summary-grid { grid-template-columns: 1fr; }
}
.ss-dashboard .pay-icon.emerald { background: rgba(16,185,129,0.10); color: #059669; }
.ss-dashboard .pay-icon.red { background: rgba(239,68,68,0.10); color: #DC2626; }
.ss-dashboard .pay-icon.amber { background: rgba(245,158,11,0.10); color: #D97706; }
.ss-dashboard .pay-icon.purple { background: rgba(139,92,246,0.10); color: #7C3AED; }
.ss-dashboard .pay-icon.gray { background: rgba(107,114,128,0.10); color: #4B5563; }
.ss-dashboard .rpd-kategori-rowspan {
  vertical-align: middle;
}
</style>
@endpush

<div
    class="ss-dashboard ss-dash-page ss-wo-create"
    x-data="{ loading: false }"
    x-init="$nextTick(() => { requestAnimationFrame(() => $el.classList.add('is-ready')); })"
    :class="loading && 'is-loading'"
>
<div class="ss-dash-content space-y-5">
    @include('partials.flash')

    @include('partials.speedshop.dashboard-filter-strip', [
        'filterAction' => route('speedshop.laporan.rekap-pemisahan-dana'),
        'dashboardFilterExports' => [],
        'dashboardFilterQueryKeys' => $laporanFilterQueryKeys,
        'dashboardSpeedshopOptions' => $laporanSpeedshopOptions ?? collect(),
        'selectedDashboardSpeedshopId' => $selectedLaporanSpeedshopId ?? null,
    ])

    <div class="rpd-summary-grid ss-dash-enter" style="--ss-delay: 80ms">
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 0">
            <div class="pay-card-top">
                <span class="pay-card-label">Omset</span>
                <span class="pay-icon emerald">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="pay-amount text-[22px] sm:text-[26px]">Rp {{ $fmt($cardOmset) }}</div>
            <p class="pay-period">{{ $monthLabel }}</p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 1">
            <div class="pay-card-top">
                <span class="pay-card-label">Total Biaya &amp; Alokasi</span>
                <span class="pay-icon red">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                </span>
            </div>
            <div class="pay-amount text-[22px] sm:text-[26px]">Rp {{ $fmt($cardTotalBiaya) }}</div>
            <p class="pay-period">HPP {{ $fmt($cardHpp) }} + alokasi {{ $fmt($cardAlokasiBiaya) }}</p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 2">
            <div class="pay-card-top">
                <span class="pay-card-label">Sisa Profit</span>
                <span class="pay-icon {{ $cardSisaProfit >= 0 ? 'purple' : 'red' }}">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                </span>
            </div>
            <div class="pay-amount text-[22px] sm:text-[26px] {{ $cardSisaProfit < 0 ? 'text-red-700' : '' }}">Rp {{ $fmt($cardSisaProfit) }}</div>
            <p class="pay-period">{{ $speedshopLabel }} · {{ $monthLabel }}</p>
        </div>
    </div>

    <div class="panel-card panel-card-flush ss-dash-enter" style="--ss-delay: 120ms">
        <div class="border-b border-gray-100 px-5 py-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">Rekap pemisahan dana {{ $speedshopLabel }}</h2>
                    <p class="mt-1 text-[13px] text-gray-500">
                        Periode {{ ($periodDari ?? $dari)->format('d M Y') }} – {{ ($periodSampai ?? $sampai)->format('d M Y') }}
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex shrink-0 items-center rounded-full bg-brand px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-brand-ink">
                        {{ strtoupper($monthLabel) }}
                    </span>
                    <a href="{{ route('speedshop.laporan.rekap-pemisahan-dana.export', request()->only($laporanFilterQueryKeys)) }}" class="btn-export">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Export Excel
                    </a>
                    <a href="{{ route('speedshop.laporan.rekap-pemisahan-dana.export-pdf', request()->only($laporanFilterQueryKeys)) }}" target="_blank" rel="noopener noreferrer" class="btn-export-pdf">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 13h6M9 17h4M9 9h1"/></svg>
                        Export PDF
                    </a>
                </div>
            </div>
        </div>

        <div class="max-h-[72vh] overflow-x-auto overflow-y-auto overscroll-x-contain">
            <table class="w-full min-w-[1200px] text-left text-sm">
                <thead class="sticky top-0 z-10 shadow-sm">
                    <tr style="background-color:#f7f3f4">
                        <th rowspan="2" class="border-b border-gray-200 px-4 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-500" style="width:48px">No</th>
                        <th rowspan="2" class="border-b border-gray-200 px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500" style="min-width:180px">Kategori</th>
                        <th rowspan="2" class="border-b border-gray-200 px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500" style="min-width:120px">Pembagian</th>
                        @foreach($weekColumns as $col)
                        <th colspan="2" class="border-b border-gray-200 px-4 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <div>{{ $col['title'] }}</div>
                            <div class="mt-0.5 text-[10px] font-medium normal-case tracking-normal text-gray-400">({{ $col['range_label'] }})</div>
                        </th>
                        @endforeach
                        <th colspan="2" class="border-b border-gray-200 px-4 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Total</th>
                    </tr>
                    <tr style="background-color:#f7f3f4">
                        @foreach($weekColumns as $col)
                        <th class="border-b border-gray-200 px-3 py-2 text-center text-[10px] font-semibold uppercase tracking-wider text-gray-500">Nominal</th>
                        <th class="border-b border-gray-200 px-3 py-2 text-center text-[10px] font-semibold uppercase tracking-wider text-gray-500">Tgl. Pembayaran</th>
                        @endforeach
                        <th class="border-b border-gray-200 px-3 py-2 text-center text-[10px] font-semibold uppercase tracking-wider text-gray-500">Nominal</th>
                        <th class="border-b border-gray-200 px-3 py-2 text-center text-[10px] font-semibold uppercase tracking-wider text-gray-500">Tgl. Pembayaran</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($rows as $row)
                    @php
                        $tone = $rowToneMap[$row['color'] ?? ''] ?? ['bg' => '', 'hover' => 'hover:bg-rose-50/50'];
                        $rowBg = $tone['bg'];
                        $rowHover = $tone['hover'];
                        $subRows = $row['sub_rows'] ?? [];
                    @endphp
                    @if(count($subRows) > 0)
                        @foreach($subRows as $i => $sub)
                        <tr class="transition {{ $rowBg }} {{ $rowHover }}">
                            @if($i === 0)
                            <td rowspan="{{ count($subRows) }}" class="border-b border-gray-100 px-4 py-3.5 text-center text-sm font-medium tabular-nums text-gray-500">{{ $row['no'] }}</td>
                            <td rowspan="{{ count($subRows) }}" class="rpd-kategori-rowspan border-b border-gray-100 px-4 py-3.5 text-sm font-semibold uppercase text-gray-900">
                                {{ $row['label'] }}
                                @if(!empty($row['rekening_suffix']))
                                <span class="ml-1 font-medium normal-case text-gray-600">{{ $row['rekening_suffix'] }}</span>
                                @endif
                            </td>
                            @endif
                            <td class="border-b border-gray-100 px-4 py-3.5 text-sm font-semibold uppercase text-gray-800">{{ $sub['label'] }}</td>
                            @foreach($weekColumns as $col)
                            @php $cell = $sub['cells'][$col['key']] ?? ['nominal_fmt' => '—', 'tanggal' => '—']; @endphp
                            <td class="border-b border-gray-100 px-3 py-3.5 text-right text-sm font-semibold tabular-nums text-gray-900">{{ $cell['nominal_fmt'] }}</td>
                            <td class="border-b border-gray-100 px-3 py-3.5 text-center text-xs text-gray-500">{{ $cell['tanggal'] }}</td>
                            @endforeach
                            @php $subTotal = $sub['cells']['total'] ?? ['nominal_fmt' => '—', 'tanggal' => '—']; @endphp
                            <td class="border-b border-gray-100 px-3 py-3.5 text-right text-sm font-bold italic tabular-nums text-red-600">{{ $subTotal['nominal_fmt'] }}</td>
                            <td class="border-b border-gray-100 px-3 py-3.5 text-center text-xs text-gray-400">{{ $subTotal['tanggal'] }}</td>
                        </tr>
                        @endforeach
                    @else
                    <tr class="transition {{ $rowBg }} {{ $rowHover }}">
                        <td class="border-b border-gray-100 px-4 py-3.5 text-center text-sm font-medium tabular-nums text-gray-500">{{ $row['no'] }}</td>
                        <td class="border-b border-gray-100 px-4 py-3.5 text-sm font-semibold uppercase text-gray-900">
                            {{ $row['label'] }}
                            @if(!empty($row['rekening_suffix']))
                            <span class="ml-1 font-medium normal-case text-gray-600">{{ $row['rekening_suffix'] }}</span>
                            @endif
                        </td>
                        <td class="border-b border-gray-100 px-4 py-3.5 text-center text-xs text-gray-400">—</td>
                        @foreach($weekColumns as $col)
                        @php $cell = $row['cells'][$col['key']] ?? ['nominal_fmt' => '—', 'tanggal' => '—', 'is_empty' => true]; @endphp
                        <td class="border-b border-gray-100 px-3 py-3.5 text-right text-sm font-semibold tabular-nums text-gray-900">{{ $cell['nominal_fmt'] }}</td>
                        <td class="border-b border-gray-100 px-3 py-3.5 text-center text-xs text-gray-500">{{ $cell['tanggal'] }}</td>
                        @endforeach
                        @php $total = $row['cells']['total'] ?? ['nominal_fmt' => '—', 'tanggal' => '—']; @endphp
                        <td class="border-b border-gray-100 px-3 py-3.5 text-right text-sm font-bold italic tabular-nums text-red-600">{{ $total['nominal_fmt'] }}</td>
                        <td class="border-b border-gray-100 px-3 py-3.5 text-center text-xs text-gray-400">{{ $total['tanggal'] }}</td>
                    </tr>
                    @endif
                    @empty
                    <tr>
                        <td colspan="{{ 3 + (count($weekColumns) * 2) + 2 }}" class="px-5 py-12 text-center text-sm text-gray-500">
                            <p class="font-medium text-gray-700">Tidak ada data pada periode ini.</p>
                            <p class="mt-1 text-xs text-gray-400">Ubah filter bulan lalu terapkan lagi.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
@endsection
