@extends('layouts.speedshop')

@php
    use Illuminate\Support\Facades\Schema;
    $tz = config('app.timezone');
    $filters = $filters ?? [];
    $permintaanStats = $permintaanStats ?? ['total' => 0, 'pending' => 0, 'completed' => 0, 'rejected' => 0];
    $fv = fn (string $k) => old($k, $filters[$k] ?? '');
    $statusFilter = $fv('status');
    $tableFilterClass = 'h-8 w-full rounded-lg border border-gray-200 bg-white px-2.5 text-[13px] font-normal text-gray-700 placeholder-gray-400 outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/40';
    $requiresCatatanPengajuan = auth()->user()->isSpeedshop() && ! auth()->user()->isManager();
    $showModalOnLoad = $errors->has('tanggal') || $errors->has('nominal') || $errors->has('catatan') || old('open_modal') === 'permintaan';
    $pkkHasCatatanCol = Schema::hasColumn('speedshop_finance_permintaan_kas_kecil', 'catatan');
    $pkkTableCols = $pkkHasCatatanCol ? 9 : 8;
    $mode = $mode ?? 'bulanan';
    $pkkPeriodLabel = match ($mode) {
        'harian' => \Carbon\Carbon::parse($selectedDate ?? now())->translatedFormat('d M Y'),
        'bulanan' => \Carbon\Carbon::parse(($selectedMonth ?? now()->format('Y-m')).'-01')->translatedFormat('F Y'),
        'tahunan' => (string) ($selectedYear ?? now()->year),
        'range' => ($dari ?? now())->translatedFormat('d M Y').' – '.($sampai ?? now())->translatedFormat('d M Y'),
        default => now()->translatedFormat('F Y'),
    };
    $isVerifikasiPage = request()->routeIs('speedshop.verifikasi.permintaan-kas-kecil*');
    $indexRoute = $isVerifikasiPage ? 'speedshop.verifikasi.permintaan-kas-kecil.index' : 'speedshop.keuangan.permintaan-kas-kecil.index';
    $storeRoute = 'speedshop.keuangan.permintaan-kas-kecil.store';
    $approveRoute = 'speedshop.keuangan.permintaan-kas-kecil.approve';
    $rejectRoute = 'speedshop.keuangan.permintaan-kas-kecil.reject';
    $panelTitle = $isVerifikasiPage ? 'Verifikasi Permintaan Kas Kecil' : 'Riwayat Permintaan';
    $pkkFilterQueryKeys = ['mode', 'tanggal', 'bulan', 'tahun', 'dari', 'sampai', 'status', 'tanggal_jurnal', 'nominal', 'dicatat_oleh'];
    $pkkCardBaseQuery = array_filter([
        'mode' => $mode,
        'tanggal' => $mode === 'harian' ? ($selectedDate ?? now()->toDateString()) : null,
        'bulan' => $mode === 'bulanan' ? ($selectedMonth ?? now()->format('Y-m')) : null,
        'tahun' => $mode === 'tahunan' ? ($selectedYear ?? now()->year) : null,
        'dari' => $mode === 'range' ? ($selectedDari ?? ($dari ?? now())->format('Y-m-d')) : null,
        'sampai' => $mode === 'range' ? ($selectedSampai ?? ($sampai ?? now())->format('Y-m-d')) : null,
        'tanggal_jurnal' => request('tanggal_jurnal'),
        'nominal' => request('nominal'),
        'dicatat_oleh' => request('dicatat_oleh'),
    ], fn ($v) => $v !== null && $v !== '');
    $pkkCardUrl = fn (?string $status = null) => route($indexRoute, $status
        ? array_merge($pkkCardBaseQuery, ['status' => $status])
        : $pkkCardBaseQuery);
    $hasTableFilters = collect($filters ?? [])->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
    $hasPeriodFilters = collect(request()->only(['mode', 'tanggal', 'bulan', 'tahun', 'dari', 'sampai']))->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
    $hasActiveFilters = $hasTableFilters || $hasPeriodFilters;
    $exportQuery = $exportQuery ?? $pkkCardBaseQuery;
@endphp

@section('title', $isVerifikasiPage ? 'Verifikasi Permintaan Kas Kecil' : 'Permintaan Kas Kecil')

@section('content')
@push('styles')
@include('partials.speedshop.dashboard-load-animations')
@include('partials.speedshop.dashboard-manager-template-styles')
@include('partials.speedshop.work-order-create-styles')
<style>
.ss-dashboard .pkk-summary-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 20px;
}
@media (max-width: 1024px) {
  .ss-dashboard .pkk-summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 640px) {
  .ss-dashboard .pkk-summary-grid { grid-template-columns: 1fr; }
}
.ss-dashboard .pay-card.pay-card-filter {
  cursor: pointer;
  text-decoration: none;
  color: inherit;
}
.ss-dashboard .pay-icon.blue { background: rgba(46,107,230,0.10); color: #2E6BE6; }
.ss-dashboard .pay-icon.amber { background: rgba(245,158,11,0.10); color: #D97706; }
.ss-dashboard .pay-icon.green { background: rgba(16,185,129,0.10); color: #10B981; }
.ss-dashboard .pay-icon.red { background: rgba(239,68,68,0.10); color: #EF4444; }
</style>
@endpush

<div
    class="ss-dashboard ss-dash-page ss-wo-create"
    x-data="{ loading: false, showPermintaanModal: @js($showModalOnLoad) }"
    x-init="$nextTick(() => { requestAnimationFrame(() => $el.classList.add('is-ready')); })"
    :class="loading && 'is-loading'"
>
<div class="ss-dash-content space-y-5">
    @include('partials.speedshop.dashboard-filter-strip', [
        'filterAction' => route($indexRoute),
        'dashboardFilterExports' => [],
        'dashboardFilterQueryKeys' => $pkkFilterQueryKeys,
        'filterHiddenInputs' => array_filter([
            'status' => request('status'),
            'tanggal_jurnal' => request('tanggal_jurnal'),
            'nominal' => request('nominal'),
            'dicatat_oleh' => request('dicatat_oleh'),
        ], fn ($v) => $v !== null && $v !== ''),
    ])


    <div class="pkk-summary-grid ss-dash-enter" style="--ss-delay: 80ms">
        <a href="{{ $pkkCardUrl() }}" @click="loading = true" class="pay-card pay-card-filter ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 0">
            <div class="pay-card-top">
                <span class="pay-card-label">Total Permintaan</span>
                <span class="pay-icon blue">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </span>
            </div>
            <div class="pay-amount">{{ number_format($permintaanStats['total'], 0, ',', '.') }}</div>
            <p class="pay-period">Semua permintaan · {{ $pkkPeriodLabel }}</p>
        </a>
        <a href="{{ $pkkCardUrl('pending') }}" @click="loading = true" class="pay-card pay-card-filter ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 1">
            <div class="pay-card-top">
                <span class="pay-card-label">Menunggu</span>
                <span class="pay-icon amber">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="pay-amount">{{ number_format($permintaanStats['pending'], 0, ',', '.') }}</div>
            <p class="pay-period">{{ $isVerifikasiPage ? 'Belum diverifikasi' : 'Menunggu verifikasi' }}</p>
        </a>
        <a href="{{ $pkkCardUrl('completed') }}" @click="loading = true" class="pay-card pay-card-filter ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 2">
            <div class="pay-card-top">
                <span class="pay-card-label">Disetujui</span>
                <span class="pay-icon green">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </span>
            </div>
            <div class="pay-amount">{{ number_format($permintaanStats['completed'], 0, ',', '.') }}</div>
            <p class="pay-period">Permintaan disetujui · {{ $pkkPeriodLabel }}</p>
        </a>
        <a href="{{ $pkkCardUrl('rejected') }}" @click="loading = true" class="pay-card pay-card-filter ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 3">
            <div class="pay-card-top">
                <span class="pay-card-label">Ditolak</span>
                <span class="pay-icon red">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </span>
            </div>
            <div class="pay-amount">{{ number_format($permintaanStats['rejected'], 0, ',', '.') }}</div>
            <p class="pay-period">{{ $isVerifikasiPage ? 'Pengajuan ditolak' : 'Permintaan ditolak' }}</p>
        </a>
    </div>

    <div class="panel-card panel-card-flush ss-dash-enter" style="--ss-delay: 120ms">
        <div class="border-b border-gray-100 px-5 py-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">{{ $panelTitle }}</h2>
                <div class="flex flex-wrap items-center gap-2">
                    @unless($isVerifikasiPage)
                    <a href="{{ route('speedshop.keuangan.permintaan-kas-kecil.export-excel', $exportQuery) }}" class="btn-export">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Export Excel
                    </a>
                    <a href="{{ route('speedshop.keuangan.permintaan-kas-kecil.export-pdf', $exportQuery) }}" target="_blank" rel="noopener noreferrer" class="btn-export-pdf">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/><path d="M9 13h6M9 17h4M9 9h1"/></svg>
                        Export PDF
                    </a>
                    @endunless
                    @if(! $isVerifikasiPage && ! auth()->user()->isAccounting())
                    <button
                        type="button"
                        @click="showPermintaanModal = true"
                        class="btn-primary inline-flex h-10 shrink-0 items-center gap-2 px-5"
                    >
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        Ajukan Permintaan
                    </button>
                    @endif
                </div>
            </div>
            <p class="mt-1 text-[13px] text-gray-500">
                Periode {{ $pkkPeriodLabel }}
                @if($hasActiveFilters)
                · <a href="{{ route($indexRoute) }}" class="font-medium text-red-600 transition hover:text-red-700">Hapus filter</a>
                @endif
            </p>
        </div>

        <div class="overflow-x-auto">
            <form method="GET" action="{{ route($indexRoute) }}" @submit="loading = true">
                <input type="hidden" name="mode" value="{{ $mode }}">
                @if($mode === 'harian')
                <input type="hidden" name="tanggal" value="{{ $selectedDate }}">
                @elseif($mode === 'bulanan')
                <input type="hidden" name="bulan" value="{{ $selectedMonth }}">
                @elseif($mode === 'tahunan')
                <input type="hidden" name="tahun" value="{{ $selectedYear }}">
                @elseif($mode === 'range')
                <input type="hidden" name="dari" value="{{ $selectedDari ?? $dari->format('Y-m-d') }}">
                <input type="hidden" name="sampai" value="{{ $selectedSampai ?? $sampai->format('Y-m-d') }}">
                @endif
                <table class="w-full min-w-[1020px] text-left text-sm">
                    <thead>
                        <tr class="bg-[#f7f3f4]">
                            <th class="w-16 px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No.</th>
                            <th class="w-40 px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Tanggal jurnal</th>
                            <th class="w-36 px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Status</th>
                            <th class="w-44 px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-gray-500">Nominal</th>
                            <th class="w-44 px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Diajukan</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Pemohon</th>
                            @if($pkkHasCatatanCol)
                            <th class="min-w-[200px] px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Alasan / catatan</th>
                            @endif
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Verifikasi</th>
                            <th class="w-44 px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500"></th>
                        </tr>
                        <tr class="border-t border-gray-200 bg-[#fbf8f9]">
                            <th class="px-5 py-2"></th>
                            <th class="px-5 py-2">
                                <input type="date" name="tanggal_jurnal" value="{{ $fv('tanggal_jurnal') }}" max="{{ $asOfYmd }}" class="{{ $tableFilterClass }}" onchange="this.form.submit()">
                            </th>
                            <th class="px-5 py-2">
                                <select name="status" class="{{ $tableFilterClass }}" onchange="this.form.submit()">
                                    <option value="" @selected($statusFilter === '')>{{ $isVerifikasiPage ? 'Semua' : 'Semua status' }}</option>
                                    <option value="pending" @selected($statusFilter === 'pending')>Menunggu</option>
                                    <option value="completed" @selected($statusFilter === 'completed')>Disetujui</option>
                                    <option value="rejected" @selected($statusFilter === 'rejected')>Ditolak</option>
                                </select>
                            </th>
                            <th class="px-5 py-2">
                                <input type="number" min="0" step="1" name="nominal" value="{{ $fv('nominal') }}" placeholder="Cari nominal…" class="{{ $tableFilterClass }}" onchange="this.form.submit()">
                            </th>
                            <th class="px-5 py-2"></th>
                            <th class="px-5 py-2">
                                <input type="text" name="dicatat_oleh" value="{{ $fv('dicatat_oleh') }}" placeholder="Cari nama pemohon…" class="{{ $tableFilterClass }}" onchange="this.form.submit()">
                            </th>
                            @if($pkkHasCatatanCol)
                            <th class="px-5 py-2"></th>
                            @endif
                            <th class="px-5 py-2"></th>
                            <th class="px-5 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    @forelse($riwayat as $i => $row)
                    @php
                        $statusLabel = match ($row->status) {
                            'completed' => 'Disetujui',
                            'rejected' => 'Ditolak',
                            default => 'Menunggu',
                        };
                        $statusClass = match ($row->status) {
                            'completed' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                            'rejected' => 'border-red-200 bg-red-50 text-red-700',
                            default => 'border-amber-200 bg-amber-50 text-amber-700',
                        };
                    @endphp
                    <tr class="transition hover:bg-[#fcfafb]">
                        <td class="px-5 py-4 tabular-nums text-gray-500">{{ $riwayat->firstItem() + $i }}</td>
                        <td class="px-5 py-4 align-top">
                            <span class="font-semibold text-gray-900">{{ $row->tanggal->timezone($tz)->translatedFormat('j M Y') }}</span>
                            <p class="text-xs text-gray-500">{{ $row->tanggal->timezone($tz)->translatedFormat('l') }}</p>
                        </td>
                        <td class="px-5 py-4 align-top">
                            <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">{{ $statusLabel }}</span>
                        </td>
                        <td class="px-5 py-4 text-right align-top font-semibold tabular-nums text-red-700">Rp {{ number_format((int) $row->nominal, 0, ',', '.') }}</td>
                        <td class="px-5 py-4 align-top text-xs tabular-nums text-gray-600">{{ $row->created_at?->timezone($tz)->translatedFormat('j M Y, H:i') ?? '—' }}</td>
                        <td class="px-5 py-4 align-top text-gray-800">{{ $row->requester?->name ?? '—' }}</td>
                        @if($pkkHasCatatanCol)
                        <td class="max-w-xs px-5 py-4 align-top text-xs text-gray-700">
                            @if(filled($row->catatan))
                                <p class="line-clamp-3 whitespace-pre-wrap break-words" title="{{ e($row->catatan) }}">{{ $row->catatan }}</p>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        @endif
                        <td class="px-5 py-4 align-top text-xs text-gray-600">
                            @if($row->status === 'completed')
                                {{ $row->approver?->name ?? 'Accounting' }} · {{ $row->approved_at?->timezone($tz)->translatedFormat('j M Y, H:i') ?? '—' }}
                            @elseif($row->status === 'rejected')
                                {{ $row->approver?->name ?? 'Accounting' }} · {{ $row->rejected_at?->timezone($tz)->translatedFormat('j M Y, H:i') ?? '—' }}
                                @if($row->rejected_reason)
                                    <p class="mt-1 text-red-700">{{ $row->rejected_reason }}</p>
                                @endif
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-5 py-4 align-top">
                            @if(auth()->user()->isAccounting() && $row->status === 'pending')
                            <div class="flex flex-wrap items-center justify-end gap-2">
                                <button
                                    type="button"
                                    class="inline-flex h-8 items-center justify-center gap-1.5 rounded-lg bg-emerald-600 px-3 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-700 active:scale-[0.98]"
                                    onclick="pkkConfirmApprove(@js(route($approveRoute, $row)))"
                                >
                                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    Setujui
                                </button>
                                <button
                                    type="button"
                                    class="btn-secondary inline-flex h-8 items-center justify-center gap-1.5 border-red-200 px-3 text-xs font-semibold text-red-700 hover:bg-red-50"
                                    onclick="pkkConfirmReject(@js(route($rejectRoute, $row)))"
                                >
                                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    Tolak
                                </button>
                            </div>
                            @else
                            <span class="text-xs text-gray-400">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $pkkTableCols }}" class="px-5 py-12 text-center text-gray-400">
                            @if($hasActiveFilters)
                                Tidak ada permintaan sesuai filter.
                            @else
                                <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <p class="mt-3 text-sm font-medium text-gray-700">Belum ada permintaan kas kecil</p>
                                @if(! $isVerifikasiPage && ! auth()->user()->isAccounting())
                                <p class="mt-1 text-xs">Klik Ajukan Permintaan untuk menambah data baru</p>
                                @endif
                            @endif
                        </td>
                    </tr>
                    @endforelse
                    </tbody>
                </table>
            </form>
        </div>

        @if($riwayat->hasPages())
        <div class="border-t border-gray-100 px-5 py-4">{{ $riwayat->links() }}</div>
        @endif
    </div>
</div>

@if(! $isVerifikasiPage && ! auth()->user()->isAccounting())
<div
    x-show="showPermintaanModal"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto"
    @keydown.escape.window="showPermintaanModal = false"
>
    <div x-show="showPermintaanModal" x-transition.opacity class="fixed inset-0 bg-black/50" @click="showPermintaanModal = false"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div x-show="showPermintaanModal" x-transition class="wo-modal-panel relative w-full max-w-md !p-6 sm:!p-8" @click.stop>
            <h3 class="text-lg font-bold text-gray-900">Permintaan Kas Kecil</h3>
            <p class="mt-1 text-sm text-gray-500">
                Isi tanggal dan nominal untuk diajukan ke Accounting.
                @if($requiresCatatanPengajuan)
                <span class="text-gray-700">Jika Anda bukan Manager cabang, alasan wajib diisi.</span>
                @endif
            </p>
            <form method="POST" action="{{ route($storeRoute) }}" class="mt-6 space-y-4">
                @csrf
                <input type="hidden" name="open_modal" value="permintaan">
                <div>
                    <label for="pkk-tanggal" class="wo-label">Tanggal <span class="text-red-500">*</span></label>
                    <input id="pkk-tanggal" type="date" name="tanggal" value="{{ old('tanggal', $asOfYmd) }}" max="{{ $asOfYmd }}" class="wo-input wo-native" required>
                    @error('tanggal')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="pkk-nominal" class="wo-label">Nominal (Rp) <span class="text-red-500">*</span></label>
                    <input id="pkk-nominal" type="number" name="nominal" value="{{ old('nominal') }}" min="1" step="1" placeholder="Contoh: 500000" class="wo-input wo-native tabular-nums" required>
                    @error('nominal')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="pkk-catatan" class="wo-label">Alasan / catatan @if($requiresCatatanPengajuan)<span class="text-red-500">*</span>@endif</label>
                    <textarea id="pkk-catatan" name="catatan" rows="3" placeholder="Jelaskan keperluan uang kas kecil (contoh: kembalian tunai, operasional harian, dll.)" class="wo-input wo-native min-h-[5rem] resize-y" @if($requiresCatatanPengajuan) required minlength="5" maxlength="2000" @else maxlength="2000" @endif>{{ old('catatan') }}</textarea>
                    @if($requiresCatatanPengajuan)
                    <p class="mt-1 text-xs text-gray-500">Wajib untuk peran selain Manager cabang (minimal 5 karakter).</p>
                    @endif
                    @error('catatan')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <div class="mt-8 flex items-center justify-end gap-3">
                    <button type="button" @click="showPermintaanModal = false" class="btn-secondary px-6 py-2.5">Batal</button>
                    <button type="submit" class="btn-primary px-6 py-2.5">Ajukan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
</div>

@if(auth()->user()->isAccounting())
@push('scripts')
<script>
async function pkkConfirmApprove(actionUrl) {
    if (typeof Swal === 'undefined') {
        if (confirm('Setujui permintaan kas kecil ini?')) {
            pkkSubmitPost(actionUrl);
        }
        return;
    }
    const result = await Swal.fire({
        title: 'Setujui permintaan?',
        text: 'Kas kecil akan ditambahkan sesuai nominal yang diajukan.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#059669',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, setujui',
        cancelButtonText: 'Batal',
        reverseButtons: true,
        focusCancel: true,
    });
    if (!result.isConfirmed) return;
    pkkSubmitPost(actionUrl);
}

async function pkkConfirmReject(actionUrl) {
    if (typeof Swal === 'undefined') {
        const reason = prompt('Alasan penolakan (wajib):');
        if (reason === null) return;
        const r = String(reason).trim();
        if (!r || r.length < 5) {
            alert('Alasan wajib diisi (minimal 5 karakter).');
            return;
        }
        pkkSubmitReject(actionUrl, r);
        return;
    }
    const result = await Swal.fire({
        title: 'Tolak permintaan?',
        text: 'Permohonan kas kecil akan ditolak. Berikan alasan untuk pemohon.',
        input: 'textarea',
        inputLabel: 'Alasan penolakan',
        inputPlaceholder: 'Tulis alasan...',
        inputAttributes: { maxlength: '1000', 'aria-label': 'Alasan penolakan' },
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, tolak',
        cancelButtonText: 'Batal',
        reverseButtons: true,
        focusCancel: true,
        preConfirm: (value) => {
            const reason = (value || '').trim();
            if (!reason) {
                Swal.showValidationMessage('Alasan penolakan wajib diisi.');
                return false;
            }
            if (reason.length < 5) {
                Swal.showValidationMessage('Alasan minimal 5 karakter.');
                return false;
            }
            return reason;
        },
    });
    if (!result.isConfirmed) return;
    pkkSubmitReject(actionUrl, result.value);
}

function pkkSubmitPost(actionUrl, extraFields = {}) {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = actionUrl;
    form.style.display = 'none';
    const csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = '_token';
    csrf.value = token || '';
    form.appendChild(csrf);
    Object.entries(extraFields).forEach(([name, value]) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        form.appendChild(input);
    });
    document.body.appendChild(form);
    form.submit();
}

function pkkSubmitReject(actionUrl, rejectedReason) {
    pkkSubmitPost(actionUrl, { rejected_reason: rejectedReason });
}
</script>
@endpush
@endif
@endsection
