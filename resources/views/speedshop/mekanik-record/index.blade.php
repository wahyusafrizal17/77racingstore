@extends('layouts.speedshop')

@php
    $filterBaseClass = 'h-8 w-full rounded-lg border border-gray-200 bg-white px-2.5 text-[13px] font-normal text-gray-700 placeholder-gray-400 outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/40';
    $mekanikRecordPeriodLabel = match ($mode ?? 'harian') {
        'harian' => \Carbon\Carbon::parse($selectedDate ?? now())->translatedFormat('d M Y'),
        'bulanan' => \Carbon\Carbon::parse(($selectedMonth ?? now()->format('Y-m')).'-01')->translatedFormat('F Y'),
        'tahunan' => (string) ($selectedYear ?? now()->year),
        'range' => ($dari ?? now())->translatedFormat('d M Y').' – '.($sampai ?? now())->translatedFormat('d M Y'),
        default => ($dari ?? now())->translatedFormat('d M Y'),
    };

    $mekanikDetails = [];
    foreach ($byMekanik as $row) {
        $id = (int) ($row->mekanik?->id ?? 0);
        if ($id <= 0) {
            continue;
        }
        $mekanikDetails[$id] = [
            'nama' => $row->mekanik->nama ?? '-',
            'no_hp' => $row->mekanik->no_hp ?? '-',
            'total_work_order' => (int) $row->total_work_order,
            'total_pendapatan' => (int) $row->total_pendapatan,
            'orders' => $row->orders->map(function ($order) {
                $tanggal = $order->tanggal_selesai ?? $order->tanggal_masuk;

                return [
                    'kode_servis' => $order->kode_servis,
                    'tanggal' => $tanggal?->format('d M Y') ?? '—',
                    'pelanggan' => $order->pelanggan->nama ?? '—',
                    'polisi' => $order->kendaraan?->nomor_polisi ?? '—',
                    'speedshop' => $order->speedshop?->nama,
                    'total' => $order->finalGrandTotal(),
                    'url' => route('speedshop.wip.show', $order),
                ];
            })->values()->all(),
        ];
    }
@endphp

@section('title', 'Mekanik Record')

@section('content')
@push('styles')
@include('partials.speedshop.dashboard-load-animations')
@include('partials.speedshop.dashboard-manager-template-styles')
@include('partials.speedshop.work-order-create-styles')
<style>
.ss-dashboard .mekanik-record-summary-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 20px;
}
@media (max-width: 640px) {
  .ss-dashboard .mekanik-record-summary-grid { grid-template-columns: 1fr; }
}
.ss-dashboard .pay-icon.blue { background: rgba(46,107,230,0.10); color: #2E6BE6; }
</style>
@endpush

<div
    class="ss-dashboard ss-dash-page ss-wo-create"
    x-data="{
        loading: false,
        showDetail: false,
        detail: null,
        records: @js($mekanikDetails),
        allSpeedshops: @js(!empty($mekanikRecordAllSpeedshops)),
        openDetail(id) {
            this.detail = this.records[id] ?? null;
            this.showDetail = !!this.detail;
            if (this.showDetail) {
                document.body.classList.add('overflow-hidden');
            }
        },
        closeDetail() {
            this.showDetail = false;
            this.detail = null;
            document.body.classList.remove('overflow-hidden');
        },
        formatRp(n) {
            return new Intl.NumberFormat('id-ID').format(n ?? 0);
        }
    }"
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
            <p class="ss-dash-loading-text">Memuat mekanik record…</p>
            <p class="ss-dash-loading-sub">Menyiapkan data periode terpilih</p>
        </div>
    </div>

    <div class="ss-dash-content space-y-5">
    @include('partials.speedshop.dashboard-filter-strip', [
        'filterAction' => route('speedshop.mekanik-record'),
        'dashboardFilterExports' => [],
        'dashboardFilterQueryKeys' => ['mode', 'tanggal', 'bulan', 'tahun', 'dari', 'sampai', 'mekanik', 'no_hp', 'sort'],
    ])

    <div class="mekanik-record-summary-grid ss-dash-enter" style="--ss-delay: 80ms">
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 0">
            <div class="pay-card-top">
                <span class="pay-card-label">Total Mekanik</span>
                <span class="pay-icon blue">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </span>
            </div>
            <div class="pay-amount">{{ number_format((int) ($summaryTotalMekanik ?? 0)) }}</div>
            <p class="pay-period">{{ number_format((int) ($summaryTotalWorkOrder ?? 0)) }} work order selesai</p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 1">
            <div class="pay-card-top">
                <span class="pay-card-label">Total Pendapatan WO</span>
                <span class="pay-icon purple">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="pay-amount"><span class="curr">Rp</span>{{ number_format((int) ($summaryTotalPendapatan ?? 0), 0, ',', '.') }}</div>
            <p class="pay-period">Akumulasi nilai WO periode terpilih</p>
        </div>
    </div>

    <div class="panel-card panel-card-flush ss-dash-enter" style="--ss-delay: 120ms">
        <div class="flex flex-col gap-3 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">Mekanik Record</h2>
                <p class="mt-1 text-[13px] text-gray-500">
                    Mekanik yang paling sering menyelesaikan work order · {{ $mekanikRecordPeriodLabel }}
                    @if(!empty($mekanikRecordAllSpeedshops))
                        · Semua speedshop
                    @endif
                </p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <form method="GET" action="{{ route('speedshop.mekanik-record') }}" @submit="loading = true">
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
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Mekanik</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No HP</th>
                            <th class="px-5 py-3.5 text-center text-[11px] font-semibold uppercase tracking-wider text-gray-500">Total Work Order</th>
                            <th class="px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-gray-500">Total Pendapatan WO</th>
                            <th class="px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-gray-500">Action</th>
                        </tr>
                        <tr class="border-t border-gray-200 bg-[#fbf8f9]">
                            <th class="px-5 py-2"></th>
                            <th class="px-5 py-2">
                                <input name="mekanik" value="{{ request('mekanik') }}" placeholder="Cari mekanik..." class="{{ $filterBaseClass }}" onchange="this.form.submit()">
                            </th>
                            <th class="px-5 py-2">
                                <input name="no_hp" value="{{ request('no_hp') }}" placeholder="Cari no HP..." class="{{ $filterBaseClass }}" onchange="this.form.submit()">
                            </th>
                            <th class="px-5 py-2">
                                @php $sortWorkOrder = str_starts_with((string) ($sort ?? ''), 'work_order'); @endphp
                                <input type="hidden" name="sort" value="{{ $sort ?? 'work_order_desc' }}">
                                <select
                                    class="{{ $filterBaseClass }}"
                                    aria-label="Urutkan total work order"
                                    onchange="if(this.value){ this.form.querySelector('input[name=sort]').value=this.value; this.form.submit(); }"
                                >
                                    <option value="" {{ ! $sortWorkOrder ? 'selected' : '' }}>—</option>
                                    <option value="work_order_desc" {{ ($sort ?? 'work_order_desc') === 'work_order_desc' ? 'selected' : '' }}>WO terbanyak</option>
                                    <option value="work_order_asc" {{ ($sort ?? '') === 'work_order_asc' ? 'selected' : '' }}>WO tersedikit</option>
                                </select>
                            </th>
                            <th class="px-5 py-2">
                                @php $sortPendapatan = str_starts_with((string) ($sort ?? ''), 'pendapatan'); @endphp
                                <select
                                    class="{{ $filterBaseClass }}"
                                    aria-label="Urutkan total pendapatan"
                                    onchange="if(this.value){ this.form.querySelector('input[name=sort]').value=this.value; this.form.submit(); }"
                                >
                                    <option value="" {{ ! $sortPendapatan ? 'selected' : '' }}>—</option>
                                    <option value="pendapatan_desc" {{ ($sort ?? '') === 'pendapatan_desc' ? 'selected' : '' }}>Pendapatan tertinggi</option>
                                    <option value="pendapatan_asc" {{ ($sort ?? '') === 'pendapatan_asc' ? 'selected' : '' }}>Pendapatan terendah</option>
                                </select>
                            </th>
                            <th class="px-5 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    @forelse($byMekanik as $index => $row)
                    @php
                        $globalNo = (int) $byMekanik->firstItem() + (int) $index;
                        $mekanikId = (int) ($row->mekanik?->id ?? 0);
                    @endphp
                    <tr class="transition hover:bg-[#fcfafb]">
                        <td class="px-5 py-4">
                            @if($globalNo <= 3)
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold
                                {{ $globalNo === 1 ? 'bg-amber-100 text-amber-800' : ($globalNo === 2 ? 'bg-gray-200 text-gray-800' : 'bg-amber-50 text-amber-800') }}">
                                {{ $globalNo }}
                            </span>
                            @else
                            <span class="text-gray-500">{{ $globalNo }}</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 font-semibold text-gray-900">{{ $row->mekanik->nama ?? '-' }}</td>
                        <td class="px-5 py-4 text-gray-600">{{ $row->mekanik->no_hp ?? '-' }}</td>
                        <td class="px-5 py-4 text-center">
                            <span class="wo-badge">{{ number_format($row->total_work_order) }}×</span>
                        </td>
                        <td class="px-5 py-4 text-right font-semibold tabular-nums text-gray-900">
                            Rp {{ number_format($row->total_pendapatan, 0, ',', '.') }}
                        </td>
                        <td class="px-5 py-4 text-right">
                            @if($row->orders->isNotEmpty() && $mekanikId > 0)
                            <button type="button"
                                    @click="openDetail({{ $mekanikId }})"
                                    class="btn-detail">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                Detail
                            </button>
                            @else
                            <span class="text-xs text-gray-400">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center text-gray-400">
                            @if(request()->hasAny(['mekanik', 'no_hp', 'search']))
                                Tidak ada mekanik sesuai pencarian.
                            @else
                                Belum ada data mekanik record untuk periode ini.
                            @endif
                        </td>
                    </tr>
                    @endforelse
                    </tbody>
                </table>
            </form>
        </div>

        @if($byMekanik->hasPages())
        <div class="border-t border-gray-100 px-5 py-4">{{ $byMekanik->links() }}</div>
        @endif
    </div>
    </div>

    {{-- Modal detail work order --}}
    <div
        x-show="showDetail"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
        @keydown.escape.window="closeDetail()"
    >
        <div x-show="showDetail" x-transition.opacity class="fixed inset-0 bg-black/50" @click="closeDetail()"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div
                x-show="showDetail"
                x-transition
                class="wo-modal-panel relative flex w-full max-w-5xl max-h-[90vh] flex-col !p-0"
                @click.stop
            >
                <div class="flex shrink-0 items-start justify-between gap-4 border-b border-gray-100 px-6 py-5">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900" x-text="detail?.nama ?? 'Mekanik'"></h3>
                        <p class="mt-1 text-sm text-gray-500">
                            <span x-text="detail?.no_hp ?? '—'"></span>
                            <span class="mx-2 text-gray-300">·</span>
                            <span x-text="(detail?.total_work_order ?? 0) + ' work order'"></span>
                            <span class="mx-2 text-gray-300">·</span>
                            <span>Rp <span x-text="formatRp(detail?.total_pendapatan)"></span></span>
                        </p>
                    </div>
                    <button type="button" @click="closeDetail()" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Tutup">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="overflow-y-auto px-6 py-4">
                    <p class="mb-3 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Work order yang dikerjakan</p>
                    <div class="overflow-hidden rounded-xl border border-gray-200">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="bg-[#f7f3f4] text-[11px] font-semibold uppercase tracking-wider text-gray-500">
                                    <th class="px-4 py-2.5">Kode WO</th>
                                    <th class="px-4 py-2.5">Tanggal</th>
                                    <th class="px-4 py-2.5">Pelanggan</th>
                                    <th class="px-4 py-2.5">No. Polisi</th>
                                    <th class="px-4 py-2.5" x-show="allSpeedshops">Speedshop</th>
                                    <th class="px-4 py-2.5 text-right">Total</th>
                                    <th class="px-4 py-2.5 text-right"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="(order, idx) in (detail?.orders ?? [])" :key="idx">
                                    <tr class="hover:bg-[#fcfafb]">
                                        <td class="px-4 py-3 font-semibold text-gray-900" x-text="order.kode_servis"></td>
                                        <td class="px-4 py-3 text-gray-600" x-text="order.tanggal"></td>
                                        <td class="px-4 py-3 text-gray-700" x-text="order.pelanggan"></td>
                                        <td class="px-4 py-3 text-gray-600" x-text="order.polisi"></td>
                                        <td class="px-4 py-3 text-gray-700" x-show="allSpeedshops" x-text="order.speedshop ?? '—'"></td>
                                        <td class="px-4 py-3 text-right font-semibold tabular-nums text-gray-900">
                                            Rp <span x-text="formatRp(order.total)"></span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <a :href="order.url"
                                               class="inline-flex items-center rounded-lg border border-red-200 bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-600 transition hover:bg-brand-hover hover:text-white">
                                                Lihat WO
                                            </a>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex shrink-0 justify-end border-t border-gray-100 px-6 py-4">
                    <button type="button" @click="closeDetail()" class="btn-secondary">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
