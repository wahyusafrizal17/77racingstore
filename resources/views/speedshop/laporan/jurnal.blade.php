@extends('layouts.speedshop')

@section('title', 'Jurnal')

@section('content')
@php
    $fmt = fn ($v) => number_format((float) $v, 0, ',', '.');
    $isOwnerViewer = auth()->user()?->isOwner();
    $speedshopLabel = ! empty($selectedLaporanSpeedshopName)
        ? strtoupper($selectedLaporanSpeedshopName)
        : (! empty($laporanAllSpeedshops)
            ? 'SEMUA SPEEDSHOP'
            : strtoupper($speedshop->nama ?? 'SEMUA OUTLET'));
    $jurnalKind = $jurnalKind ?? 'kas_kecil';
    $isLedger = $jurnalKind === 'ledger_rekening';
    $monitorOnly = $monitorOnly ?? false;
    $showManual = ($canManualEntry ?? false) && ! $monitorOnly && ! $isOwnerViewer;
    $showAksi = in_array($jurnalKind, ['kas_kecil', 'digital'], true) && ! $monitorOnly && ! $isOwnerViewer;
    $sumberDelete = $jurnalKind === 'digital' ? 'digital' : 'kas_kecil';
    $emptyMessage = $emptyMessage ?? 'Belum ada data pada periode ini.';
    $thirdCardTitle = $thirdCardTitle ?? 'Saldo';
    $sumberSelected = $sumber_selected ?? $sumber_query;
    $laporanFilterQueryKeys = ['mode', 'tanggal', 'bulan', 'tahun', 'dari', 'sampai', 'sumber'];
    if ($isOwnerViewer) {
        $laporanFilterQueryKeys[] = 'speedshop_id';
    }
    $filterQuery = array_filter(
        array_merge(request()->only($laporanFilterQueryKeys), ['sumber' => request()->query('sumber', $sumber_query)]),
        fn ($v) => $v !== null && $v !== ''
    );
    $emptyStateHint = $isOwnerViewer
        ? 'Ubah filter periode atau sumber.'
        : 'Ubah filter periode atau sumber, atau tambah entri jurnal (kas kecil / digital).';

    $hasActiveFilters = count(request()->query()) > 0;
@endphp

@push('styles')
@include('partials.speedshop.dashboard-load-animations')
@include('partials.speedshop.dashboard-manager-template-styles')
@include('partials.speedshop.work-order-create-styles')
<style>
.ss-dashboard .jurnal-summary-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 20px;
}
@media (max-width: 900px) {
  .ss-dashboard .jurnal-summary-grid { grid-template-columns: 1fr; }
}
.ss-dashboard .pay-icon.emerald { background: rgba(16,185,129,0.10); color: #059669; }
.ss-dashboard .pay-icon.red { background: rgba(239,68,68,0.10); color: #DC2626; }
.ss-dashboard .pay-icon.gray { background: rgba(107,114,128,0.10); color: #4B5563; }
.ss-dashboard .filter-select-sumber { min-width: 180px; max-width: 280px; }
</style>
@endpush

<div
    class="ss-dashboard ss-dash-page ss-wo-create"
    x-data="{ loading: false, showAddModal: {{ $errors->any() ? 'true' : 'false' }} }"
    x-init="$nextTick(() => { requestAnimationFrame(() => $el.classList.add('is-ready')); })"
    :class="loading && 'is-loading'"
>
<div class="ss-dash-content space-y-5">
    <form method="GET" action="{{ route('speedshop.laporan.jurnal') }}" class="filter-strip ss-dash-enter" style="--ss-delay: 0ms" @submit="loading = true">
        <span class="filter-label">Filter:</span>
        @if($isOwnerViewer && ($laporanSpeedshopOptions ?? collect())->isNotEmpty())
        <select name="speedshop_id" class="filter-select">
            <option value="" @selected(empty($selectedLaporanSpeedshopId))>Semua Speedshop</option>
            @foreach($laporanSpeedshopOptions as $speedshopOption)
            <option value="{{ $speedshopOption->id }}" @selected((int) ($selectedLaporanSpeedshopId ?? 0) === (int) $speedshopOption->id)>{{ $speedshopOption->nama }}</option>
            @endforeach
        </select>
        @endif
        <select name="sumber" class="filter-select filter-select-sumber">
            @foreach($sumberSelectSpec['top_options'] as $opt)
                <option value="{{ $opt['value'] }}" @selected($sumberSelected === $opt['value'])>{{ $opt['label'] }}</option>
            @endforeach
            @foreach($sumberSelectSpec['optgroups'] as $og)
                <optgroup label="{{ $og['label'] }}">
                    @foreach($og['options'] as $opt)
                        <option value="{{ $opt['value'] }}" @selected($sumberSelected === $opt['value'])>{{ $opt['label'] }}</option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
        <select name="mode" class="filter-select" @change="loading = true; $el.form.submit()">
            <option value="harian" @selected($mode === 'harian')>Harian</option>
            <option value="bulanan" @selected($mode === 'bulanan')>Bulanan</option>
            <option value="tahunan" @selected($mode === 'tahunan')>Tahunan</option>
            <option value="range" @selected($mode === 'range')>Range tanggal</option>
        </select>
        @if($mode === 'harian')
        <input type="date" name="tanggal" value="{{ $selectedDate }}" class="filter-date">
        @elseif($mode === 'bulanan')
        <input type="month" name="bulan" value="{{ $selectedMonth }}" class="filter-date">
        @elseif($mode === 'tahunan')
        <input type="number" name="tahun" min="2000" max="2100" value="{{ $selectedYear }}" class="filter-date filter-date-year">
        @elseif($mode === 'range')
        <input type="date" name="dari" value="{{ $selectedDari ?? $dari->format('Y-m-d') }}" class="filter-date">
        <input type="date" name="sampai" value="{{ $selectedSampai ?? $sampai->format('Y-m-d') }}" class="filter-date">
        @endif
        <button type="submit" class="btn-apply">Terapkan</button>
    </form>


    @if($isLedger && isset($ledgerRekening))
    <div class="panel-card panel-card-flush ss-dash-enter rounded-xl border-sky-200/80 bg-sky-50/80 p-5" style="--ss-delay: 60ms">
        <div class="flex items-start gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-sky-100 text-sky-700">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-sky-950">Tampilan buku besar per rekening</p>
                <p class="mt-1 text-xs leading-relaxed text-sky-900/85">Menampilkan mutasi dari <strong>{{ $ledgerRekening->nama }}</strong> (ledger keuangan). Baris otomatis berasal dari pendapatan, setoran, biaya operasional dari rekening, dan transaksi lain yang menggerakkan saldo rekening ini. Input manual jurnal tidak tersedia di sini; gunakan <strong>Kas Kecil</strong> atau <strong>Kas digital (jurnal gabungan)</strong> untuk koreksi manual.</p>
            </div>
        </div>
    </div>
    @endif

    <div class="jurnal-summary-grid ss-dash-enter" style="--ss-delay: 80ms">
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 0">
            <div class="pay-card-top">
                <span class="pay-card-label">Pemasukan</span>
                <span class="pay-icon emerald">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="pay-amount text-[22px] sm:text-[26px]">Rp {{ $fmt($pemasukan) }}</div>
            <p class="pay-period">Periode terpilih</p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 1">
            <div class="pay-card-top">
                <span class="pay-card-label">Pengeluaran</span>
                <span class="pay-icon red">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                </span>
            </div>
            <div class="pay-amount text-[22px] sm:text-[26px]">Rp {{ $fmt($pengeluaran) }}</div>
            <p class="pay-period">Periode terpilih</p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 2">
            <div class="pay-card-top">
                <span class="pay-card-label">{{ $thirdCardTitle }}</span>
                <span class="pay-icon gray">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                </span>
            </div>
            <div class="pay-amount text-[22px] sm:text-[26px]">Rp {{ $fmt($sisaSaldo) }}</div>
            <p class="pay-period">Periode terpilih</p>
        </div>
    </div>

    <div class="panel-card panel-card-flush ss-dash-enter" style="--ss-delay: 120ms">
        <div class="border-b border-gray-100 px-5 py-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">Daftar jurnal · {{ $speedshopLabel }}</h2>
                <div class="flex flex-wrap items-center gap-2">
                    @if(! $isLedger)
                    <a href="{{ route('speedshop.laporan.jurnal.export', $filterQuery) }}" class="btn-export">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Export Excel
                    </a>
                    <a href="{{ route('speedshop.laporan.jurnal.export-pdf', $filterQuery) }}" target="_blank" rel="noopener noreferrer" class="btn-export-pdf">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 13h6M9 17h4M9 9h1"/></svg>
                        Export PDF
                    </a>
                    @endif
                    @if($showManual)
                    <button type="button" @click="showAddModal = true" class="btn-primary inline-flex h-10 shrink-0 items-center gap-2 px-5">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        Tambah Jurnal
                    </button>
                    @endif
                </div>
            </div>
            <p class="mt-1 text-[13px] text-gray-500">
                {{ $dari->format('d M Y') }} – {{ $sampai->format('d M Y') }}
            </p>
        </div>

        <div class="max-h-[72vh] overflow-x-auto overflow-y-auto overscroll-x-contain">
            <table class="w-full min-w-max text-left text-sm">
                <thead class="sticky top-0 z-10 shadow-sm">
                    <tr style="background-color:#f7f3f4">
                        <th class="w-14 px-5 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">No</th>
                        <th class="min-w-[100px] px-5 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Tanggal</th>
                        <th class="min-w-[140px] px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Akun</th>
                        <th class="min-w-[200px] px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Keterangan</th>
                        <th class="min-w-[100px] px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">PIC</th>
                        <th colspan="2" class="min-w-[120px] px-5 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Kredit</th>
                        <th colspan="2" class="min-w-[120px] px-5 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Debet</th>
                        <th colspan="2" class="min-w-[120px] px-5 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Total (Rp)</th>
                        @if($showAksi)
                            <th class="w-24 px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($rows as $row)
                        <tr @class([
                            'transition hover:bg-rose-50/50',
                            'bg-amber-50/50' => strtoupper(trim((string) ($row->akun ?? ''))) === 'SALDO AKHIR',
                        ])>
                            <td class="px-5 py-4 text-center text-sm font-medium tabular-nums text-gray-500">{{ $row->no }}</td>
                            <td class="px-5 py-4 text-center text-sm tabular-nums text-gray-800">{{ \Carbon\Carbon::parse($row->tanggal)->format('d M Y') }}</td>
                            <td class="px-5 py-4 text-sm font-semibold text-gray-900">{{ $row->akun }}</td>
                            <td class="px-5 py-4 text-sm text-gray-800">{{ $row->keterangan }}</td>
                            <td class="px-5 py-4 text-sm text-gray-800">{{ $row->pic }}</td>
                            <td class="px-5 py-4 text-right text-sm text-gray-600">{{ $row->kredit > 0 ? 'Rp' : '' }}</td>
                            <td class="px-5 py-4 text-right text-sm font-medium tabular-nums text-gray-900">{{ $row->kredit > 0 ? $fmt($row->kredit) : '' }}</td>
                            <td class="px-5 py-4 text-right text-sm text-gray-600">{{ $row->debet > 0 ? 'Rp' : '' }}</td>
                            <td class="px-5 py-4 text-right text-sm font-medium tabular-nums text-gray-900">{{ $row->debet > 0 ? $fmt($row->debet) : '' }}</td>
                            <td class="px-5 py-4 text-right text-sm text-gray-600">Rp</td>
                            <td class="px-5 py-4 text-right text-sm font-bold tabular-nums text-gray-900">{{ $fmt($row->saldo) }}</td>
                            @if($showAksi)
                                <td class="px-5 py-4">
                                    <div class="flex justify-end">
                                        @if(!empty($row->allow_delete) && $row->allow_delete && !empty($row->entry_id))
                                            <form method="POST" action="{{ route('speedshop.laporan.jurnal.destroy', ['sumber' => $sumberDelete, 'entry' => $row->entry_id]) }}" onsubmit="return confirm('Hapus jurnal ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="wo-icon-btn bg-red-600 text-white hover:bg-red-700" title="Hapus">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $showAksi ? 12 : 11 }}" class="px-5 py-12 text-center text-sm text-gray-500">
                                <p class="font-medium text-gray-700">{{ $emptyMessage }}</p>
                                <p class="mt-1 text-xs text-gray-400">{{ $emptyStateHint }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($showManual)
<div x-show="showAddModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
    <div x-show="showAddModal" x-transition.opacity class="fixed inset-0 bg-black/50" @click="showAddModal = false"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div x-show="showAddModal" x-transition class="wo-modal-panel relative w-full max-w-3xl !p-6 sm:!p-8" @click.stop>
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Input Manual Jurnal</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        @if($jurnalKind === 'digital')
                            Simpan ke buku jurnal kas digital (gabungan Kas Besar + Rekening Utama).
                        @else
                            Simpan ke buku jurnal kas kecil (tunai / laci).
                        @endif
                    </p>
                </div>
                <button type="button" @click="showAddModal = false" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-gray-500 transition hover:bg-gray-100 hover:text-gray-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>


            <form method="POST" action="{{ route('speedshop.laporan.jurnal.store') }}" class="mt-6 grid gap-4 md:grid-cols-2">
                @csrf
                <input type="hidden" name="sumber" value="{{ $jurnalKind === 'digital' ? 'digital' : 'kas_kecil' }}">
                <div>
                    <label class="wo-label">Tanggal</label>
                    <input type="date" name="tanggal" value="{{ old('tanggal', $sampai->format('Y-m-d')) }}" required class="wo-input wo-native">
                </div>
                <div>
                    <label class="wo-label">Akun</label>
                    <input type="text" name="akun" value="{{ old('akun', $jurnalKind === 'digital' ? 'TRANSFER MASUK' : 'PENDAPATAN TRANSAKSI') }}" required class="wo-input wo-native">
                </div>
                <div class="md:col-span-2">
                    <label class="wo-label">Keterangan</label>
                    <input type="text" name="keterangan" value="{{ old('keterangan') }}" placeholder="Contoh: Koreksi / penyesuaian" class="wo-input wo-native">
                </div>
                <div class="md:col-span-2">
                    <label class="wo-label">PIC</label>
                    <input type="text" name="pic" value="{{ old('pic', 'ACCOUNTING SPEEDSHOP') }}" class="wo-input wo-native">
                </div>
                <div>
                    <label class="wo-label">Kredit</label>
                    <input type="number" name="kredit" value="{{ old('kredit') }}" min="0" step="0.01" class="wo-input wo-native">
                </div>
                <div>
                    <label class="wo-label">Debet</label>
                    <input type="number" name="debet" value="{{ old('debet') }}" min="0" step="0.01" class="wo-input wo-native">
                </div>
                <div class="mt-2 flex items-center justify-end gap-3 md:col-span-2">
                    <button type="button" @click="showAddModal = false" class="btn-secondary px-6 py-2.5">Batal</button>
                    <button type="submit" class="btn-primary inline-flex items-center gap-2 px-6 py-2.5">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        Simpan Jurnal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
</div>
@endsection
