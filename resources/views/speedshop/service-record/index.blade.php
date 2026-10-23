@extends('layouts.speedshop')

@php
    $filterBaseClass = 'h-8 w-full rounded-lg border border-gray-200 bg-white px-2.5 text-[13px] font-normal text-gray-700 placeholder-gray-400 outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/40';
    $serviceRecordPeriodLabel = match ($mode ?? 'harian') {
        'harian' => \Carbon\Carbon::parse($selectedDate ?? now())->translatedFormat('d M Y'),
        'bulanan' => \Carbon\Carbon::parse(($selectedMonth ?? now()->format('Y-m')).'-01')->translatedFormat('F Y'),
        'tahunan' => (string) ($selectedYear ?? now()->year),
        'range' => ($dari ?? now())->translatedFormat('d M Y').' – '.($sampai ?? now())->translatedFormat('d M Y'),
        default => ($dari ?? now())->translatedFormat('d M Y'),
    };

    $pelangganDetails = [];
    foreach ($byPelanggan as $row) {
        $id = (int) ($row->pelanggan?->id ?? 0);
        if ($id <= 0) {
            continue;
        }
        $pelangganDetails[$id] = [
            'nama' => $row->pelanggan->nama ?? '-',
            'no_hp' => $row->pelanggan->no_hp ?? '-',
            'total_kunjungan' => (int) $row->total_kunjungan,
            'total_pengeluaran' => (int) $row->total_pengeluaran,
            'pdf_url' => route('speedshop.service-record.pdf', $row->pelanggan),
            'orders' => $row->orders->map(function ($order) {
                $tanggalMasuk = $order->tanggal_masuk;
                $tanggalSelesai = $order->tanggal_selesai;
                $tanggal = $tanggalSelesai ?? $tanggalMasuk;
                $k = $order->kendaraan;
                $tipeMotor = $k ? trim(implode(' ', array_filter([(string) ($k->merk ?? ''), (string) ($k->tipe ?? '')]))) : '';
                $jenisBayarRaw = strtolower((string) ($order->jenis_pembayaran ?? ''));
                $jenisBayar = match ($jenisBayarRaw) {
                    'cash', 'tunai' => 'Tunai',
                    'transfer' => 'Transfer',
                    'qris' => 'QRIS',
                    'edc' => 'EDC',
                    default => $jenisBayarRaw !== '' ? ucfirst($jenisBayarRaw) : '—',
                };

                return [
                    'kode_servis' => $order->kode_servis,
                    'tanggal' => $tanggal?->format('d M Y') ?? '—',
                    'tanggal_masuk' => $tanggalMasuk?->format('d M Y') ?? '—',
                    'km' => $order->kilometer !== null && $order->kilometer !== ''
                        ? number_format((int) $order->kilometer, 0, ',', '.').' km'
                        : '—',
                    'tipe_motor' => $tipeMotor !== '' ? $tipeMotor : '—',
                    'sumber' => $order->sumber_kedatangan
                        ? (\App\Models\ServiceOrder::SUMBER_KEDATANGAN[$order->sumber_kedatangan] ?? $order->sumber_kedatangan)
                        : '—',
                    'kategori' => $order->kategoriServiceDisplay(),
                    'jenis_bayar' => $jenisBayar,
                    'mekanik' => $order->mekanik?->nama ?? '—',
                    'speedshop' => $order->speedshop?->nama,
                    'invoice_kode' => $order->invoice?->kode_invoice,
                    'total' => $order->finalGrandTotal(),
                    'jasa' => $order->jasaServis->map(function ($j) {
                        $q = (int) ($j->pivot->qty ?? 1);
                        $biaya = (int) ($j->pivot->biaya ?? 0);
                        $diskon = (int) ($j->pivot->diskon ?? 0);

                        return [
                            'nama' => $j->nama,
                            'qty' => $q,
                            'total' => ($q * $biaya) - $diskon,
                        ];
                    })->values()->all(),
                    'sparepart' => $order->products->map(function ($p) {
                        return [
                            'nama' => $p->nama_produk,
                            'qty' => (int) $p->pivot->qty,
                            'total' => (int) $p->pivot->qty * (int) $p->pivot->harga - (int) ($p->pivot->diskon ?? 0),
                        ];
                    })->values()->all(),
                ];
            })->values()->all(),
        ];
    }
@endphp

@section('title', 'Service Record')

@section('content')
@push('styles')
@include('partials.speedshop.dashboard-load-animations')
@include('partials.speedshop.dashboard-manager-template-styles')
@include('partials.speedshop.work-order-create-styles')
<style>
.ss-dashboard .service-record-summary-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 20px;
}
@media (max-width: 640px) {
  .ss-dashboard .service-record-summary-grid { grid-template-columns: 1fr; }
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
        records: @js($pelangganDetails),
        allSpeedshops: @js(!empty($serviceRecordAllSpeedshops)),
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
            <p class="ss-dash-loading-text">Memuat service record…</p>
            <p class="ss-dash-loading-sub">Menyiapkan data periode terpilih</p>
        </div>
    </div>

    <div class="ss-dash-content space-y-5">
    @include('partials.speedshop.dashboard-filter-strip', [
        'filterAction' => route('speedshop.service-record'),
        'dashboardFilterExports' => [],
        'dashboardFilterQueryKeys' => ['mode', 'tanggal', 'bulan', 'tahun', 'dari', 'sampai', 'pelanggan', 'no_hp', 'sort'],
    ])

    <div class="service-record-summary-grid ss-dash-enter" style="--ss-delay: 80ms">
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 0">
            <div class="pay-card-top">
                <span class="pay-card-label">Total Pelanggan</span>
                <span class="pay-icon blue">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </span>
            </div>
            <div class="pay-amount">{{ number_format((int) ($summaryTotalPelanggan ?? 0)) }}</div>
            <p class="pay-period">{{ number_format((int) ($summaryTotalKunjungan ?? 0)) }} kunjungan selesai</p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 1">
            <div class="pay-card-top">
                <span class="pay-card-label">Total Pengeluaran</span>
                <span class="pay-icon purple">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="pay-amount"><span class="curr">Rp</span>{{ number_format((int) ($summaryTotalPengeluaran ?? 0), 0, ',', '.') }}</div>
            <p class="pay-period">Akumulasi transaksi selesai periode</p>
        </div>
    </div>

    <div class="panel-card panel-card-flush ss-dash-enter" style="--ss-delay: 120ms">
        <div class="flex flex-col gap-3 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">Service Record</h2>
                <p class="mt-1 text-[13px] text-gray-500">
                    Riwayat pelanggan berdasarkan frekuensi kunjungan dan total pengeluaran · {{ $serviceRecordPeriodLabel }}
                    @if(!empty($serviceRecordAllSpeedshops))
                        · Semua speedshop
                    @endif
                </p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <form method="GET" action="{{ route('speedshop.service-record') }}" @submit="loading = true">
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
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Rank</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Pelanggan</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No HP</th>
                            <th class="px-5 py-3.5 text-center text-[11px] font-semibold uppercase tracking-wider text-gray-500">Total Kunjungan</th>
                            <th class="px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-gray-500">Total Pengeluaran</th>
                            <th class="px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-gray-500">Aksi</th>
                        </tr>
                        <tr class="border-t border-gray-200 bg-[#fbf8f9]">
                            <th class="px-5 py-2"></th>
                            <th class="px-5 py-2">
                                <input name="pelanggan" value="{{ request('pelanggan') }}" placeholder="Cari pelanggan..." class="{{ $filterBaseClass }}" onchange="this.form.submit()">
                            </th>
                            <th class="px-5 py-2">
                                <input name="no_hp" value="{{ request('no_hp') }}" placeholder="Cari no HP..." class="{{ $filterBaseClass }}" onchange="this.form.submit()">
                            </th>
                            <th class="px-5 py-2">
                                @php $sortKunjungan = str_starts_with((string) ($sort ?? ''), 'kunjungan'); @endphp
                                <input type="hidden" name="sort" value="{{ $sort ?? 'kunjungan_desc' }}">
                                <select
                                    class="{{ $filterBaseClass }}"
                                    aria-label="Urutkan total kunjungan"
                                    onchange="if(this.value){ this.form.querySelector('input[name=sort]').value=this.value; this.form.submit(); }"
                                >
                                    <option value="" {{ ! $sortKunjungan ? 'selected' : '' }}>—</option>
                                    <option value="kunjungan_desc" {{ ($sort ?? 'kunjungan_desc') === 'kunjungan_desc' ? 'selected' : '' }}>Kunjungan terbanyak</option>
                                    <option value="kunjungan_asc" {{ ($sort ?? '') === 'kunjungan_asc' ? 'selected' : '' }}>Kunjungan tersedikit</option>
                                </select>
                            </th>
                            <th class="px-5 py-2">
                                @php $sortPengeluaran = str_starts_with((string) ($sort ?? ''), 'pengeluaran'); @endphp
                                <select
                                    class="{{ $filterBaseClass }}"
                                    aria-label="Urutkan total pengeluaran"
                                    onchange="if(this.value){ this.form.querySelector('input[name=sort]').value=this.value; this.form.submit(); }"
                                >
                                    <option value="" {{ ! $sortPengeluaran ? 'selected' : '' }}>—</option>
                                    <option value="pengeluaran_desc" {{ ($sort ?? '') === 'pengeluaran_desc' ? 'selected' : '' }}>Pengeluaran tertinggi</option>
                                    <option value="pengeluaran_asc" {{ ($sort ?? '') === 'pengeluaran_asc' ? 'selected' : '' }}>Pengeluaran terendah</option>
                                </select>
                            </th>
                            <th class="px-5 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    @forelse($byPelanggan as $rank => $row)
                    @php
                        $globalRank = (int) $byPelanggan->firstItem() + (int) $rank;
                        $pelangganId = (int) ($row->pelanggan?->id ?? 0);
                    @endphp
                    <tr class="transition hover:bg-[#fcfafb]">
                        <td class="px-5 py-4">
                            @if($globalRank <= 3)
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold
                                {{ $globalRank === 1 ? 'bg-amber-100 text-amber-800' : ($globalRank === 2 ? 'bg-gray-200 text-gray-800' : 'bg-amber-50 text-amber-800') }}">
                                {{ $globalRank }}
                            </span>
                            @else
                            <span class="text-gray-500">{{ $globalRank }}</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 font-semibold text-gray-900">{{ $row->pelanggan->nama ?? '-' }}</td>
                        <td class="px-5 py-4 text-gray-600">{{ $row->pelanggan->no_hp ?? '-' }}</td>
                        <td class="px-5 py-4 text-center">
                            <span class="wo-badge">{{ number_format($row->total_kunjungan) }}×</span>
                        </td>
                        <td class="px-5 py-4 text-right font-semibold tabular-nums text-gray-900">
                            Rp {{ number_format($row->total_pengeluaran, 0, ',', '.') }}
                        </td>
                        <td class="px-5 py-4 text-right">
                            @if($row->orders->isNotEmpty() && $pelangganId > 0)
                            <div class="flex flex-wrap items-center justify-end gap-2">
                                <button type="button"
                                        @click="openDetail({{ $pelangganId }})"
                                        class="btn-detail">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    Riwayat
                                </button>
                                <a href="{{ route('speedshop.service-record.pdf', $row->pelanggan) }}"
                                   target="_blank"
                                   class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-700 transition hover:border-red-200 hover:bg-red-50 hover:text-red-600">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 14h12v8H6z"/></svg>
                                    PDF
                                </a>
                            </div>
                            @else
                            <span class="text-xs text-gray-400">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center text-gray-400">
                            @if(request()->hasAny(['pelanggan','no_hp','search']))
                                Tidak ada pelanggan sesuai pencarian.
                            @else
                                Belum ada data service record untuk periode ini.
                            @endif
                        </td>
                    </tr>
                    @endforelse
                    </tbody>
                </table>
            </form>
        </div>

        @if($byPelanggan->hasPages())
        <div class="border-t border-gray-100 px-5 py-4">{{ $byPelanggan->links() }}</div>
        @endif
    </div>
    </div>

    {{-- Modal riwayat transaksi --}}
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
                        <h3 class="text-lg font-semibold text-gray-900" x-text="detail?.nama ?? 'Pelanggan'"></h3>
                        <p class="mt-1 text-sm text-gray-500">
                            <span x-text="detail?.no_hp ?? '—'"></span>
                            <span class="mx-2 text-gray-300">·</span>
                            <span x-text="(detail?.total_kunjungan ?? 0) + ' kunjungan'"></span>
                            <span class="mx-2 text-gray-300">·</span>
                            <span>Rp <span x-text="formatRp(detail?.total_pengeluaran)"></span></span>
                        </p>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <a x-show="detail?.pdf_url"
                           :href="detail?.pdf_url"
                           target="_blank"
                           class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-700 transition hover:border-red-200 hover:bg-red-50 hover:text-red-600">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 14h12v8H6z"/></svg>
                            PDF
                        </a>
                        <button type="button" @click="closeDetail()" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Tutup">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>

                <div class="overflow-y-auto px-6 py-4">
                    <p class="mb-3 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Riwayat transaksi</p>
                    <div class="space-y-4">
                        <template x-for="(order, idx) in (detail?.orders ?? [])" :key="idx">
                            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                                <div class="border-b border-gray-100 px-4 py-3">
                                    <div class="flex flex-wrap items-center gap-3">
                                        <span class="font-semibold text-gray-900" x-text="order.kode_servis"></span>
                                        <span class="text-sm text-gray-500" x-text="order.tanggal"></span>
                                        <span x-show="allSpeedshops && order.speedshop"
                                              class="rounded-md bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-700"
                                              x-text="order.speedshop"></span>
                                        <span x-show="order.invoice_kode" class="text-sm text-gray-400" x-text="'· ' + order.invoice_kode"></span>
                                    </div>
                                    <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-[11px] uppercase tracking-[0.12em] text-gray-400">
                                        <span>Masuk: <span class="font-semibold text-gray-700" x-text="order.tanggal_masuk"></span></span>
                                        <span>KM: <span class="font-semibold text-gray-700" x-text="order.km"></span></span>
                                        <span>Motor: <span class="font-semibold text-gray-700" x-text="order.tipe_motor"></span></span>
                                        <span>Sumber: <span class="font-semibold text-gray-700" x-text="order.sumber"></span></span>
                                        <span>Kategori: <span class="font-semibold text-gray-700" x-text="order.kategori"></span></span>
                                        <span>Pembayaran: <span class="font-semibold text-gray-700" x-text="order.jenis_bayar"></span></span>
                                        <span>Mekanik: <span class="font-semibold text-gray-700" x-text="order.mekanik"></span></span>
                                    </div>
                                </div>
                                <div class="grid gap-3 px-4 py-3 sm:grid-cols-2">
                                    <div x-show="(order.jasa ?? []).length">
                                        <p class="mb-1.5 text-[11px] font-semibold uppercase tracking-wide text-gray-500">Jasa</p>
                                        <ul class="space-y-1 text-sm text-gray-700">
                                            <template x-for="(j, jIdx) in (order.jasa ?? [])" :key="'j-' + idx + '-' + jIdx">
                                                <li class="flex justify-between gap-2">
                                                    <span x-text="j.nama + (j.qty > 1 ? ' (' + j.qty + '×)' : '')"></span>
                                                    <span class="shrink-0 font-medium tabular-nums text-gray-900" x-text="'Rp ' + formatRp(j.total)"></span>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                    <div x-show="(order.sparepart ?? []).length">
                                        <p class="mb-1.5 text-[11px] font-semibold uppercase tracking-wide text-gray-500">Sparepart</p>
                                        <ul class="space-y-1 text-sm text-gray-700">
                                            <template x-for="(p, pIdx) in (order.sparepart ?? [])" :key="'p-' + idx + '-' + pIdx">
                                                <li class="flex justify-between gap-2">
                                                    <span x-text="p.nama + ' (' + p.qty + '×)'"></span>
                                                    <span class="shrink-0 font-medium tabular-nums text-gray-900" x-text="'Rp ' + formatRp(p.total)"></span>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </div>
                                <div class="flex justify-end border-t border-gray-100 bg-[#fbf8f9] px-4 py-2.5 text-sm font-semibold tabular-nums text-gray-900">
                                    Total: Rp <span x-text="formatRp(order.total)"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="flex shrink-0 justify-end border-t border-gray-100 px-6 py-4">
                    <button type="button" @click="closeDetail()" class="btn-secondary">Tutup</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
