@extends('layouts.speedshop')

@php
    $tz = config('app.timezone');
    $filters = $filters ?? [];
    $setoranStats = $setoranStats ?? ['total' => 0, 'pending' => 0, 'completed' => 0, 'rejected' => 0];
    $fv = fn (string $k) => old($k, $filters[$k] ?? '');
    $statusFilter = $fv('status');
    $tableFilterClass = 'h-8 w-full rounded-lg border border-gray-200 bg-white px-2.5 text-[13px] font-normal text-gray-700 placeholder-gray-400 outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/40';
    $mode = $mode ?? 'bulanan';
    $setoranPeriodLabel = match ($mode) {
        'harian' => \Carbon\Carbon::parse($selectedDate ?? now())->translatedFormat('d M Y'),
        'bulanan' => \Carbon\Carbon::parse(($selectedMonth ?? now()->format('Y-m')).'-01')->translatedFormat('F Y'),
        'tahunan' => (string) ($selectedYear ?? now()->year),
        'range' => ($dari ?? now())->translatedFormat('d M Y').' – '.($sampai ?? now())->translatedFormat('d M Y'),
        default => now()->translatedFormat('F Y'),
    };
    $setoranFilterQueryKeys = ['mode', 'tanggal', 'bulan', 'tahun', 'dari', 'sampai', 'status', 'tanggal_setor', 'pengaju', 'rekening'];
    $exportQuery = $exportQuery ?? array_filter([
        'mode' => $mode,
        'tanggal' => $mode === 'harian' ? ($selectedDate ?? now()->toDateString()) : null,
        'bulan' => $mode === 'bulanan' ? ($selectedMonth ?? now()->format('Y-m')) : null,
        'tahun' => $mode === 'tahunan' ? ($selectedYear ?? now()->year) : null,
        'dari' => $mode === 'range' ? ($selectedDari ?? ($dari ?? now())->format('Y-m-d')) : null,
        'sampai' => $mode === 'range' ? ($selectedSampai ?? ($sampai ?? now())->format('Y-m-d')) : null,
        'status' => request('status'),
        'tanggal_setor' => request('tanggal_setor'),
        'pengaju' => request('pengaju'),
        'rekening' => request('rekening'),
    ], fn ($v) => $v !== null && $v !== '');
    $isVerifikasiPage = request()->routeIs('speedshop.verifikasi.setoran*');
    $indexRoute = $isVerifikasiPage ? 'speedshop.verifikasi.setoran.index' : 'speedshop.keuangan.setoran.index';
    $showRoute = $isVerifikasiPage ? 'speedshop.verifikasi.setoran.show' : 'speedshop.keuangan.setoran.show';
    $setoranCardBaseQuery = array_filter([
        'mode' => $mode,
        'tanggal' => $mode === 'harian' ? ($selectedDate ?? now()->toDateString()) : null,
        'bulan' => $mode === 'bulanan' ? ($selectedMonth ?? now()->format('Y-m')) : null,
        'tahun' => $mode === 'tahunan' ? ($selectedYear ?? now()->year) : null,
        'dari' => $mode === 'range' ? ($selectedDari ?? ($dari ?? now())->format('Y-m-d')) : null,
        'sampai' => $mode === 'range' ? ($selectedSampai ?? ($sampai ?? now())->format('Y-m-d')) : null,
        'tanggal_setor' => request('tanggal_setor'),
        'pengaju' => request('pengaju'),
        'rekening' => request('rekening'),
    ], fn ($v) => $v !== null && $v !== '');
    $setoranCardUrl = fn (?string $status = null) => route($indexRoute, $status
        ? array_merge($setoranCardBaseQuery, ['status' => $status])
        : $setoranCardBaseQuery);
    $hasTableFilters = collect($filters ?? [])->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
    $hasPeriodFilters = collect(request()->only(['mode', 'tanggal', 'bulan', 'tahun', 'dari', 'sampai']))->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
    $hasActiveFilters = $hasTableFilters || $hasPeriodFilters;
    $pageTitle = $isVerifikasiPage ? 'Verifikasi Setoran' : 'Data Setoran Cabang';
    $pageSubtitle = $isVerifikasiPage
        ? 'Pengajuan menunggu verifikasi Accounting'
        : 'Daftar pengajuan dan setoran selesai cabang';
@endphp

@section('title', $isVerifikasiPage ? 'Verifikasi Setoran' : 'Setoran')

@section('content')
@push('styles')
@include('partials.speedshop.dashboard-load-animations')
@include('partials.speedshop.dashboard-manager-template-styles')
@include('partials.speedshop.work-order-create-styles')
<style>
.ss-dashboard .setoran-summary-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 20px;
}
@media (max-width: 1024px) {
  .ss-dashboard .setoran-summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 640px) {
  .ss-dashboard .setoran-summary-grid { grid-template-columns: 1fr; }
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
    x-data="{ loading: false }"
    x-init="$nextTick(() => { requestAnimationFrame(() => $el.classList.add('is-ready')); })"
    :class="loading && 'is-loading'"
>
<div class="ss-dash-content space-y-5">
    @include('partials.speedshop.dashboard-filter-strip', [
        'filterAction' => route($indexRoute),
        'dashboardFilterExports' => [],
        'dashboardFilterQueryKeys' => $setoranFilterQueryKeys,
        'filterHiddenInputs' => array_filter([
            'status' => request('status'),
            'tanggal_setor' => request('tanggal_setor'),
            'pengaju' => request('pengaju'),
            'rekening' => request('rekening'),
        ], fn ($v) => $v !== null && $v !== ''),
    ])


    @if(! $isVerifikasiPage && count($pendingDates) > 0)
    <div class="panel-card panel-card-flush ss-dash-enter rounded-xl border-amber-200/80 bg-amber-50/80 p-5" style="--ss-delay: 60ms">
        <div class="flex items-start gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-800">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-amber-950">Perlu setoran untuk tanggal berikut</p>
                <p class="mt-1 text-xs leading-relaxed text-amber-900/85">WO baru dan penyelesaian invoice tertahan sampai hari-hari ini diselesaikan.</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach($pendingDates as $d)
                    <a href="{{ route('speedshop.keuangan.setoran.create', ['setoran_tanggal' => $d]) }}" class="inline-flex items-center rounded-xl border border-amber-200/90 bg-white px-3.5 py-2 text-xs font-semibold text-amber-950 shadow-sm transition hover:border-amber-300 hover:bg-amber-50">
                        {{ \Carbon\Carbon::parse($d)->translatedFormat('l, j M Y') }}
                    </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="setoran-summary-grid ss-dash-enter" style="--ss-delay: 80ms">
        <a href="{{ $setoranCardUrl() }}" @click="loading = true" class="pay-card pay-card-filter ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 0">
            <div class="pay-card-top">
                <span class="pay-card-label">Total Setoran</span>
                <span class="pay-icon blue">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </span>
            </div>
            <div class="pay-amount">{{ number_format($setoranStats['total'], 0, ',', '.') }}</div>
            <p class="pay-period">Semua setoran · {{ $setoranPeriodLabel }}</p>
        </a>
        <a href="{{ $setoranCardUrl('pending') }}" @click="loading = true" class="pay-card pay-card-filter ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 1">
            <div class="pay-card-top">
                <span class="pay-card-label">Menunggu</span>
                <span class="pay-icon amber">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="pay-amount">{{ number_format($setoranStats['pending'], 0, ',', '.') }}</div>
            <p class="pay-period">{{ $isVerifikasiPage ? 'Belum diverifikasi' : 'Menunggu verifikasi' }}</p>
        </a>
        <a href="{{ $setoranCardUrl('completed') }}" @click="loading = true" class="pay-card pay-card-filter ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 2">
            <div class="pay-card-top">
                <span class="pay-card-label">Selesai</span>
                <span class="pay-icon green">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </span>
            </div>
            <div class="pay-amount">{{ number_format($setoranStats['completed'], 0, ',', '.') }}</div>
            <p class="pay-period">Setoran selesai · {{ $setoranPeriodLabel }}</p>
        </a>
        <a href="{{ $setoranCardUrl('rejected') }}" @click="loading = true" class="pay-card pay-card-filter ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 3">
            <div class="pay-card-top">
                <span class="pay-card-label">Ditolak</span>
                <span class="pay-icon red">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </span>
            </div>
            <div class="pay-amount">{{ number_format($setoranStats['rejected'], 0, ',', '.') }}</div>
            <p class="pay-period">{{ $isVerifikasiPage ? 'Pengajuan ditolak' : 'Setoran ditolak' }}</p>
        </a>
    </div>

    <div class="panel-card panel-card-flush ss-dash-enter" style="--ss-delay: 120ms">
        <div class="border-b border-gray-100 px-5 py-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">{{ $pageTitle }}</h2>
                @unless($isVerifikasiPage)
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('speedshop.keuangan.setoran.export-excel', $exportQuery) }}" class="btn-export">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Export Excel
                    </a>
                    <a href="{{ route('speedshop.keuangan.setoran.export-pdf', $exportQuery) }}" target="_blank" rel="noopener noreferrer" class="btn-export-pdf">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/><path d="M9 13h6M9 17h4M9 9h1"/></svg>
                        Export PDF
                    </a>
                    <a href="{{ route('speedshop.keuangan.setoran.create') }}" class="btn-primary inline-flex h-10 shrink-0 items-center gap-2 px-5">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        Tambah Setoran
                    </a>
                </div>
                @endunless
            </div>
            <p class="mt-1 text-[13px] text-gray-500">
                Periode {{ $setoranPeriodLabel }}
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
                <table class="w-full min-w-max text-left text-sm">
                    <thead>
                        <tr class="bg-[#f7f3f4]">
                            <th class="w-16 px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No.</th>
                            <th class="min-w-[160px] px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Tanggal</th>
                            <th class="min-w-[130px] px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Status</th>
                            <th class="min-w-[180px] px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Diajukan Oleh</th>
                            <th class="min-w-[160px] px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Dicatat</th>
                            <th class="w-28 px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500"></th>
                        </tr>
                        <tr class="border-t border-gray-200 bg-[#fbf8f9]">
                            <th class="px-5 py-2"></th>
                            <th class="px-5 py-2">
                                <input type="date" name="tanggal_setor" value="{{ $fv('tanggal_setor') }}" class="{{ $tableFilterClass }}" onchange="this.form.submit()" title="Tanggal setor">
                            </th>
                            <th class="px-5 py-2">
                                <select name="status" class="{{ $tableFilterClass }}" onchange="this.form.submit()">
                                    <option value="" @selected($statusFilter === '')>{{ $isVerifikasiPage ? 'Semua' : 'Semua status' }}</option>
                                    <option value="pending" @selected($statusFilter === 'pending')>Menunggu</option>
                                    <option value="completed" @selected($statusFilter === 'completed')>Selesai</option>
                                    <option value="rejected" @selected($statusFilter === 'rejected')>Ditolak</option>
                                </select>
                            </th>
                            <th class="px-5 py-2">
                                <input type="text" name="pengaju" value="{{ $fv('pengaju') }}" placeholder="Cari nama…" class="{{ $tableFilterClass }}" onchange="this.form.submit()">
                            </th>
                            <th class="px-5 py-2"></th>
                            <th class="px-5 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($setorans as $i => $s)
                        @php
                            $tgl = $s->tanggal_setor->timezone($tz);
                            $dicatat = $s->isPending() || $s->isRejected()
                                ? $s->created_at->timezone($tz)
                                : ($s->completed_at?->timezone($tz) ?? $s->updated_at->timezone($tz));
                            $statusLabel = match (true) {
                                $s->isPending() => 'Menunggu',
                                $s->isRejected() => 'Ditolak',
                                default => 'Selesai',
                            };
                            $statusClass = match (true) {
                                $s->isPending() => 'border-amber-200 bg-amber-50 text-amber-700',
                                $s->isRejected() => 'border-red-200 bg-red-50 text-red-700',
                                default => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                            };
                            $rowNo = $setorans->firstItem() + $i;
                        @endphp
                        <tr class="transition hover:bg-[#fcfafb]">
                            <td class="px-5 py-4 text-gray-500 tabular-nums">{{ $rowNo }}</td>
                            <td class="px-5 py-4 align-top">
                                <span class="font-semibold text-gray-900">{{ $tgl->translatedFormat('j M Y') }}</span>
                                <p class="text-xs text-gray-500">{{ $tgl->translatedFormat('l') }}</p>
                            </td>
                            <td class="px-5 py-4 align-top">
                                <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">{{ $statusLabel }}</span>
                            </td>
                            <td class="px-5 py-4 align-top text-gray-800">{{ $s->user?->name ?? '—' }}</td>
                            <td class="px-5 py-4 align-top text-xs tabular-nums text-gray-600">{{ $dicatat->translatedFormat('j M Y, H:i') }}</td>
                            <td class="px-5 py-4">
                                <a href="{{ route($showRoute, $s) }}"
                                   class="btn-detail"
                                   title="Detail">
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
                            <td colspan="6" class="px-5 py-12 text-center text-gray-400">
                                @if($hasActiveFilters)
                                    Tidak ada setoran sesuai filter.
                                @else
                                    <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <p class="mt-3 text-sm font-medium">Belum ada setoran</p>
                                    @unless($isVerifikasiPage)
                                    <p class="mt-1 text-xs">Klik Tambah Setoran untuk memulai pengajuan</p>
                                    @endunless
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </form>
        </div>

        @if($setorans->hasPages())
        <div class="border-t border-gray-100 px-5 py-4">{{ $setorans->links() }}</div>
        @endif
    </div>
</div>
</div>
@endsection
