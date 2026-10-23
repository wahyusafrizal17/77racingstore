@extends('layouts.speedshop')

@section('title')
{{ request()->routeIs('speedshop.verifikasi.biaya-operasional.index') ? 'Verifikasi Biaya Operasional' : 'Biaya Operasional' }}
@endsection

@section('content')
@php
    $isVerifikasiBiayaOp = request()->routeIs('speedshop.verifikasi.biaya-operasional.index');
    $indexRoute = $isVerifikasiBiayaOp ? 'speedshop.verifikasi.biaya-operasional.index' : 'speedshop.keuangan.biaya-operasional';
    $exportRoute = 'speedshop.keuangan.biaya-operasional.export';
    $exportPdfRoute = 'speedshop.keuangan.biaya-operasional.export-pdf';
    $storeRoute = 'speedshop.keuangan.biaya-operasional.store';
    $tableFilterClass = 'h-8 w-full rounded-lg border border-gray-200 bg-white px-2.5 text-[13px] font-normal text-gray-700 placeholder-gray-400 outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/40';
    $biayaStats = $biayaStats ?? ['pending' => 0, 'disetujui' => 0, 'total_nominal' => 0];
    $biayaPeriodLabel = match ($mode ?? 'bulanan') {
        'harian' => \Carbon\Carbon::parse($selectedDate ?? now())->translatedFormat('d M Y'),
        'bulanan' => \Carbon\Carbon::parse(($selectedMonth ?? now()->format('Y-m')).'-01')->translatedFormat('F Y'),
        'tahunan' => (string) ($selectedYear ?? now()->year),
        'range' => ($dari ?? now())->translatedFormat('d M Y').' – '.($sampai ?? now())->translatedFormat('d M Y'),
        default => ($dari ?? now())->translatedFormat('d M Y'),
    };
    $columnFilterKeys = [
        'filter_jenis', 'filter_no_akun', 'filter_nama_akun', 'filter_keterangan',
        'filter_pic', 'filter_pengaju', 'filter_tbl_status', 'filter_metode', 'filter_nominal',
    ];
    $filterQueryKeys = array_merge(['mode', 'tanggal', 'bulan', 'tahun', 'dari', 'sampai', 'approval_status'], $columnFilterKeys);
    $exportQuery = request()->only($filterQueryKeys);
    $filterHiddenInputs = array_merge(
        request()->filled('approval_status') ? ['approval_status' => request('approval_status')] : [],
        request()->only($columnFilterKeys),
    );
    $panelTitle = $isVerifikasiBiayaOp ? 'Daftar pengajuan' : 'Rincian biaya';
    $hasActiveFilters = collect(request()->only($filterQueryKeys))->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
    $hasActiveColumnFilters = collect(request()->only($columnFilterKeys))->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
    $clearColumnFilterUrl = route($indexRoute, request()->only(['mode', 'tanggal', 'bulan', 'tahun', 'dari', 'sampai', 'approval_status']));
    $fmt = fn ($v) => number_format((float) $v, 0, ',', '.');
    $isAccUser = auth()->user()?->isAccounting() ?? false;
    $outletModalOnError = ! $isVerifikasiBiayaOp && ! $isAccUser && $errors->any();
    $accModalOnError = ! $isVerifikasiBiayaOp && $isAccUser && old('form_context') === 'accounting_biaya' && $errors->any();
    $__outletNomDigits = (old('form_context') !== 'accounting_biaya' && old('nominal') !== null && old('nominal') !== '')
        ? (string) max(0, (int) round((float) old('nominal')))
        : '';
    $__accNomDigits = (old('form_context') === 'accounting_biaya' && old('nominal') !== null && old('nominal') !== '')
        ? (string) max(0, (int) round((float) old('nominal')))
        : '';
    $__rincianBiayaAlpineState = [
        'showAddModal' => (bool) $outletModalOnError,
        'showAccModal' => (bool) $accModalOnError,
        'detailEntry' => null,
        'auditEntry' => null,
        'auditDeskripsi' => '',
        'auditPenyelesaian' => '',
        'saldoRekMap' => $rekeningSaldoMap ?? [],
        'rekAccId' => (string) old('keuangan_rekening_id', ''),
        'rekAccTujuanId' => (string) old('keuangan_rekening_tujuan_id', ''),
        'accAkunId' => (string) old('akun_operasional_id', ''),
        'saldoKasKecilBase' => $saldoKasKecilBuku !== null ? (int) $saldoKasKecilBuku : null,
        'outletNominalDigits' => $__outletNomDigits,
        'accNominalDigits' => $__accNomDigits,
    ];
    $canEditAuditTemuan = ! empty($canEditAuditTemuan) && ! empty($hasAuditTemuanColumns);
    $showAuditTemuanCols = ! empty($hasAuditTemuanColumns);
@endphp
@push('styles')
@include('partials.speedshop.dashboard-load-animations')
@include('partials.speedshop.dashboard-manager-template-styles')
@include('partials.speedshop.work-order-create-styles')
<style>
.ss-dashboard .biaya-summary-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 20px;
}
@media (max-width: 900px) {
  .ss-dashboard .biaya-summary-grid { grid-template-columns: 1fr; }
}
.ss-dashboard .pay-icon.amber { background: rgba(245,158,11,0.10); color: #D97706; }
.ss-dashboard .pay-icon.teal { background: rgba(20,184,166,0.10); color: #14B8A6; }
.ss-dashboard .pay-icon.red { background: rgba(239,68,68,0.10); color: #DC2626; }
</style>
@endpush

<div
    class="ss-dashboard ss-dash-page ss-wo-create"
    x-data="rincianBiayaOpPage(@js($__rincianBiayaAlpineState))"
    x-init="$nextTick(() => { requestAnimationFrame(() => $el.classList.add('is-ready')); })"
    :class="loading && 'is-loading'"
>
<div class="ss-dash-content space-y-5">
    @include('partials.speedshop.dashboard-filter-strip', [
        'filterAction' => route($indexRoute),
        'dashboardFilterExports' => [],
        'dashboardFilterQueryKeys' => $filterQueryKeys,
        'filterHiddenInputs' => $filterHiddenInputs,
    ])


    <div class="biaya-summary-grid ss-dash-enter" style="--ss-delay: 80ms">
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 0">
            <div class="pay-card-top">
                <span class="pay-card-label">Menunggu Verifikasi</span>
                <span class="pay-icon amber">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="pay-amount">{{ number_format($biayaStats['pending'], 0, ',', '.') }}</div>
            <p class="pay-period">Pengajuan periode · {{ $biayaPeriodLabel }}</p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 1">
            <div class="pay-card-top">
                <span class="pay-card-label">Tercatat / Disetujui</span>
                <span class="pay-icon teal">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="pay-amount">{{ number_format($biayaStats['disetujui'], 0, ',', '.') }}</div>
            <p class="pay-period">Entri · {{ $biayaPeriodLabel }}</p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 2">
            <div class="pay-card-top">
                <span class="pay-card-label">Total Biaya</span>
                <span class="pay-icon red">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="pay-amount text-[22px] sm:text-[26px]">Rp {{ number_format($biayaStats['total_nominal'], 0, ',', '.') }}</div>
            <p class="pay-period">Nominal tercatat · {{ $biayaPeriodLabel }}</p>
        </div>
    </div>

    <div class="panel-card panel-card-flush ss-dash-enter" style="--ss-delay: 120ms">
        <div class="border-b border-gray-100 px-5 py-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">{{ $panelTitle }}</h2>
                <div class="flex flex-wrap items-center gap-2">
                    @unless($isVerifikasiBiayaOp)
                    <a href="{{ route($exportRoute, $exportQuery) }}" class="btn-export">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Export Excel
                    </a>
                    <a href="{{ route($exportPdfRoute, $exportQuery) }}" target="_blank" rel="noopener noreferrer" class="btn-export-pdf">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 13h6M9 17h4M9 9h1"/></svg>
                        Export PDF
                    </a>
                    @if(!empty($canSubmitBiaya) && $canSubmitBiaya)
                    <button type="button" @click="showAddModal = true" class="btn-primary inline-flex h-10 shrink-0 items-center gap-2 px-5">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        Ajukan Biaya
                    </button>
                    @endif
                    @if(!empty($canSubmitBiayaAccounting) && $canSubmitBiayaAccounting)
                    <button type="button" @click="showAccModal = true" class="btn-primary inline-flex h-10 shrink-0 items-center gap-2 px-5">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        Ajukan Biaya
                    </button>
                    @endif
                    @endunless
                </div>
            </div>
            <p class="mt-1 text-[13px] text-gray-500">
                Periode {{ $biayaPeriodLabel }}
            </p>
        </div>

        <form method="GET" action="{{ route($indexRoute) }}">
            <input type="hidden" name="mode" value="{{ $mode }}">
            @if($mode === 'harian')<input type="hidden" name="tanggal" value="{{ $selectedDate }}">@endif
            @if($mode === 'bulanan')<input type="hidden" name="bulan" value="{{ $selectedMonth }}">@endif
            @if($mode === 'tahunan')<input type="hidden" name="tahun" value="{{ $selectedYear }}">@endif
            @if($mode === 'range')
            <input type="hidden" name="dari" value="{{ $selectedDari ?? $dari->format('Y-m-d') }}">
            <input type="hidden" name="sampai" value="{{ $selectedSampai ?? $sampai->format('Y-m-d') }}">
            @endif
            @if(request()->filled('approval_status'))
            <input type="hidden" name="approval_status" value="{{ request('approval_status') }}">
            @endif

        <div class="overflow-x-auto">
            <table class="w-full min-w-max text-left text-sm">
                <thead>
                    <tr style="background-color:#f7f3f4">
                        <th class="w-16 px-5 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">No</th>
                        <th class="min-w-[52px] px-5 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Tgl</th>
                        <th class="min-w-[72px] px-5 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Bulan</th>
                        <th class="min-w-[56px] px-5 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Tahun</th>
                        <th class="min-w-[140px] px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Jenis</th>
                        <th class="min-w-[88px] px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">No. Akun</th>
                        <th class="min-w-[160px] px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Nama Akun</th>
                        <th class="min-w-[160px] px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Keterangan</th>
                        @if($showAuditTemuanCols)
                        <th class="min-w-[160px] px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Deskripsi Temuan</th>
                        <th class="min-w-[160px] px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Penyelesaian</th>
                        @endif
                        <th class="min-w-[120px] px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">PIC</th>
                        <th class="min-w-[140px] px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                        @if($isVerifikasiBiayaOp || $canEditAuditTemuan)
                        <th class="min-w-[96px] px-5 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Aksi</th>
                        @endif
                        <th colspan="2" class="min-w-[140px] px-5 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Tunai</th>
                        <th colspan="2" class="min-w-[140px] px-5 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Transfer</th>
                        <th colspan="2" class="min-w-[160px] px-5 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Total Biaya</th>
                    </tr>
                    <tr class="border-t border-gray-200 bg-[#fbf8f9]">
                        <th class="px-5 py-2" colspan="4"></th>
                        <th class="px-5 py-2">
                            <select name="filter_jenis" class="{{ $tableFilterClass }}" onchange="this.form.submit()">
                                <option value="">Semua jenis</option>
                                <option value="operasional" @selected(request('filter_jenis') === 'operasional')>Operasional</option>
                                <option value="non_operasional" @selected(request('filter_jenis') === 'non_operasional')>Non operasional</option>
                            </select>
                        </th>
                        <th class="px-5 py-2">
                            <input type="text" name="filter_no_akun" value="{{ request('filter_no_akun') }}" placeholder="Cari no. akun…" class="{{ $tableFilterClass }}" onchange="this.form.submit()">
                        </th>
                        <th class="px-5 py-2">
                            <input type="text" name="filter_nama_akun" value="{{ request('filter_nama_akun') }}" placeholder="Cari nama akun…" class="{{ $tableFilterClass }}" onchange="this.form.submit()">
                        </th>
                        <th class="px-5 py-2">
                            <input type="text" name="filter_keterangan" value="{{ request('filter_keterangan') }}" placeholder="Cari keterangan…" class="{{ $tableFilterClass }}" onchange="this.form.submit()">
                        </th>
                        @if($showAuditTemuanCols)
                        <th class="px-5 py-2" colspan="2"></th>
                        @endif
                        <th class="px-5 py-2">
                            <input type="text" name="filter_pic" value="{{ request('filter_pic') }}" placeholder="Cari PIC…" class="{{ $tableFilterClass }}" onchange="this.form.submit()">
                        </th>
                        <th class="px-5 py-2">
                            <select name="filter_tbl_status" class="{{ $tableFilterClass }}" onchange="this.form.submit()">
                                <option value="">Semua status</option>
                                <option value="pending" @selected(request('filter_tbl_status') === 'pending')>Menunggu</option>
                                <option value="approved" @selected(request('filter_tbl_status') === 'approved')>Disetujui</option>
                                <option value="rejected" @selected(request('filter_tbl_status') === 'rejected')>Ditolak</option>
                            </select>
                        </th>
                        @if($isVerifikasiBiayaOp || $canEditAuditTemuan)
                        <th class="px-5 py-2"></th>
                        @endif
                        <th class="px-5 py-2" colspan="2">
                            <select name="filter_metode" class="{{ $tableFilterClass }}" onchange="this.form.submit()">
                                <option value="">Semua metode</option>
                                <option value="tunai" @selected(request('filter_metode') === 'tunai')>Tunai</option>
                                <option value="transfer" @selected(request('filter_metode') === 'transfer')>Transfer</option>
                            </select>
                        </th>
                        <th class="px-5 py-2" colspan="2"></th>
                        <th class="px-5 py-2" colspan="2">
                            <input type="text" name="filter_nominal" value="{{ request('filter_nominal') }}" placeholder="Cari nominal…" class="{{ $tableFilterClass }}" onchange="this.form.submit()">
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @php $no = 1; @endphp
                    @forelse($dailyGroups as $group)
                        @php
                            $tgl = \Carbon\Carbon::parse($group->tanggal);
                            $rowspan = max(1, $group->rows->count());
                        @endphp
                        @foreach($group->rows as $idx => $row)
                            <tr @class([
                                'transition hover:bg-rose-50/50',
                                'bg-red-50' => ! empty($row->audit_has_temuan),
                                'bg-amber-50/50' => empty($row->audit_has_temuan) && ($row->approval_status ?? '') === 'pending',
                            ])>
                                @if($idx === 0)
                                    <td rowspan="{{ $rowspan }}" class="align-middle px-5 py-4 text-center text-sm font-medium tabular-nums text-gray-500">{{ $no++ }}</td>
                                @endif
                                <td class="px-5 py-4 text-center text-sm tabular-nums text-gray-800">{{ $tgl->format('d') }}</td>
                                <td class="px-5 py-4 text-center text-sm uppercase text-gray-800">{{ $tgl->translatedFormat('M') }}</td>
                                <td class="px-5 py-4 text-center text-sm tabular-nums text-gray-800">{{ $tgl->format('Y') }}</td>
                                <td class="px-5 py-4 text-sm text-gray-800">{{ $row->jenis_label ?? 'Biaya Operasional' }}</td>
                                <td class="px-5 py-4 font-mono text-sm font-medium text-gray-900">{{ $row->no_akun }}</td>
                                <td class="px-5 py-4 text-sm font-semibold text-gray-900">{{ $row->nama_akun }}</td>
                                <td class="px-5 py-4 text-sm text-gray-800">
                                    <div>{{ $row->keterangan }}</div>
                                    @if(!empty($row->has_penyesuaian))
                                    <p class="mt-1 text-[11px] font-medium text-violet-700">Nominal efektif Rp {{ $fmt($row->nominal_efektif ?? 0) }} (asli Rp {{ $fmt($row->nominal_asli ?? 0) }})</p>
                                    @endif
                                    @if(!empty($row->bukti_lampiran_url))
                                        <a href="{{ $row->bukti_lampiran_url }}" target="_blank" rel="noopener" class="mt-1 inline-flex text-xs font-medium text-red-700 underline hover:text-red-800">Lihat lampiran</a>
                                    @endif
                                </td>
                                @if($showAuditTemuanCols)
                                <td class="px-5 py-4 text-sm text-gray-800">
                                    @if(!empty($row->audit_has_temuan))
                                        <span class="mb-1 inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-800">Ada temuan</span>
                                        <div class="whitespace-pre-wrap text-gray-900">{{ $row->audit_deskripsi !== '' ? $row->audit_deskripsi : '—' }}</div>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-800">
                                    @if(!empty($row->audit_has_temuan))
                                        <div class="whitespace-pre-wrap text-gray-900">{{ $row->audit_penyelesaian !== '' ? $row->audit_penyelesaian : '—' }}</div>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                @endif
                                <td class="px-5 py-4 text-sm text-gray-800">{{ $row->pic }}</td>
                                <td class="px-5 py-4 text-sm text-gray-800">
                                    <span class="font-medium text-gray-900">{{ $row->status_label ?? '—' }}</span>
                                    @if(($row->approval_status ?? '') === 'pending')
                                        <p class="mt-1 text-[11px] leading-snug text-amber-800">
                                            @if(($row->pending_verifier ?? '') === 'manager_accounting_rekening')
                                                Menunggu persetujuan Manager (nominal > Rp {{ number_format(\App\Models\SpeedshopRincianBiayaEntry::KAS_KECIL_VERIFIKASI_ACCOUNTING_MAX_NOMINAL, 0, ',', '.') }}). Saldo buku rekening dipotong setelah disetujui.
                                            @elseif(($row->pending_verifier ?? '') === 'manager')
                                                Menunggu persetujuan Manager (nominal > Rp {{ number_format(\App\Models\SpeedshopRincianBiayaEntry::KAS_KECIL_VERIFIKASI_ACCOUNTING_MAX_NOMINAL, 0, ',', '.') }}). Setelah disetujui, saldo Kas Kecil (buku) dipotong.
                                            @elseif(($row->pending_verifier ?? '') === 'accounting')
                                                Menunggu persetujuan Accounting. Setelah disetujui, saldo Kas Kecil (buku) dipotong.
                                            @else
                                                Belum memotong saldo Kas Kecil.
                                            @endif
                                        </p>
                                    @elseif(!empty($row->is_accounting_direct) && $row->is_accounting_direct)
                                        <p class="mt-1 text-[11px] leading-snug text-red-800">Langsung memotong saldo buku rekening.</p>
                                    @endif
                                </td>
                                @if($isVerifikasiBiayaOp || $canEditAuditTemuan)
                                    @php
                                        $detailPayload = [
                                            'entry_id' => $row->entry_id,
                                            'tanggal_label' => $tgl->format('d/m/Y'),
                                            'jenis_label' => $row->jenis_label ?? 'Biaya Operasional',
                                            'no_akun' => $row->no_akun,
                                            'nama_akun' => $row->nama_akun,
                                            'keterangan' => $row->keterangan,
                                            'pic' => $row->pic,
                                            'status_label' => $row->status_label ?? '—',
                                            'tunai_fmt' => $row->tunai > 0 ? 'Rp '.$fmt($row->tunai) : '—',
                                            'transfer_fmt' => $row->transfer > 0 ? 'Rp '.$fmt($row->transfer) : '—',
                                            'rekening_label' => $row->rekening_label,
                                            'bukti_lampiran_url' => $row->bukti_lampiran_url,
                                            'approval_status' => $row->approval_status,
                                            'approve_url' => $row->approve_url,
                                            'reject_url' => $row->reject_url,
                                        ];
                                        $auditPayload = [
                                            'entry_id' => $row->entry_id,
                                            'keterangan' => $row->keterangan,
                                            'nama_akun' => $row->nama_akun,
                                            'audit_temuan_url' => $row->audit_temuan_url,
                                            'audit_has_temuan' => (bool) ($row->audit_has_temuan ?? false),
                                            'audit_deskripsi' => (string) ($row->audit_deskripsi ?? ''),
                                            'audit_penyelesaian' => (string) ($row->audit_penyelesaian ?? ''),
                                        ];
                                    @endphp
                                    <td class="px-5 py-4 text-center align-top">
                                        <div class="flex flex-col items-center gap-2">
                                            @if($isVerifikasiBiayaOp)
                                            <button
                                                type="button"
                                                class="btn-detail"
                                                @click="detailEntry = @js($detailPayload)"
                                            >
                                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                Detail
                                            </button>
                                            @endif
                                            @if($canEditAuditTemuan)
                                            <button
                                                type="button"
                                                class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-amber-200 bg-amber-50 px-3 text-xs font-semibold text-amber-900 transition hover:bg-amber-100"
                                                @click="openAuditTemuan(@js($auditPayload))"
                                            >
                                                Temuan
                                            </button>
                                            @endif
                                        </div>
                                    </td>
                                @endif
                                <td class="px-5 py-4 text-right text-sm text-gray-600">{{ $row->tunai > 0 ? 'Rp' : '' }}</td>
                                <td class="px-5 py-4 text-right text-sm font-medium tabular-nums text-gray-900">{{ $row->tunai > 0 ? $fmt($row->tunai) : '' }}</td>
                                <td class="px-5 py-4 text-right text-sm text-gray-600">{{ $row->transfer > 0 ? 'Rp' : '' }}</td>
                                <td class="px-5 py-4 text-right text-sm font-medium tabular-nums text-gray-900">
                                    @if($row->transfer > 0)
                                        <span>{{ $fmt($row->transfer) }}</span>
                                        @if(!empty($row->rekening_label))
                                            <span class="mt-0.5 block text-[11px] font-normal text-gray-500">{{ $row->rekening_label }}</span>
                                        @endif
                                    @endif
                                </td>
                                @if($idx === 0)
                                    <td rowspan="{{ $rowspan }}" class="align-middle px-5 py-4 text-right text-sm text-gray-600">Rp</td>
                                    <td rowspan="{{ $rowspan }}" class="align-middle px-5 py-4 text-right text-sm font-bold tabular-nums text-gray-900">{{ $fmt($group->total_harian) }}</td>
                                @endif
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="{{ ($isVerifikasiBiayaOp || $canEditAuditTemuan ? 17 : 16) + ($showAuditTemuanCols ? 2 : 0) }}" class="px-5 py-12 text-center text-gray-400">
                                <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <p class="mt-3 text-sm font-medium text-gray-700">Tidak ada data biaya pada periode ini</p>
                                <p class="mt-1 text-xs text-gray-500">{{ $isVerifikasiBiayaOp ? 'Belum ada pengajuan menunggu verifikasi untuk periode ini.' : 'Ubah filter periode atau ajukan biaya baru.' }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        </form>
    </div>
</div>

    @if($isVerifikasiBiayaOp)
    <div
        x-show="detailEntry !== null"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto overscroll-contain p-4"
        style="display: none;"
        @keydown.escape.window="detailEntry = null"
    >
        <div class="absolute inset-0 bg-black/40" @click="detailEntry = null"></div>
        <div class="relative mx-auto flex min-h-full items-center justify-center py-4">
        <div class="wo-modal-panel relative w-full max-w-lg !max-h-[min(90vh,720px)] !overflow-y-auto !p-6 sm:!p-8" @click.stop>
            <div class="flex items-start justify-between gap-3 border-b border-gray-100 pb-3">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Detail pengajuan</h3>
                    <p class="mt-0.5 font-mono text-xs text-gray-500" x-text="detailEntry ? 'ID #' + detailEntry.entry_id : ''"></p>
                </div>
                <button type="button" @click="detailEntry = null" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-gray-500 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Tutup">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <dt class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Status</dt>
                    <dd class="mt-0.5 font-medium text-gray-900" x-text="detailEntry?.status_label"></dd>
                </div>
                <div>
                    <dt class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Tanggal</dt>
                    <dd class="mt-0.5 text-gray-900" x-text="detailEntry?.tanggal_label"></dd>
                </div>
                <div>
                    <dt class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Jenis</dt>
                    <dd class="mt-0.5 text-gray-900" x-text="detailEntry?.jenis_label"></dd>
                </div>
                <div>
                    <dt class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">No. akun</dt>
                    <dd class="mt-0.5 font-mono text-gray-900" x-text="detailEntry?.no_akun"></dd>
                </div>
                <div>
                    <dt class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Nama akun</dt>
                    <dd class="mt-0.5 text-gray-900" x-text="detailEntry?.nama_akun"></dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Keterangan</dt>
                    <dd class="mt-0.5 text-gray-900" x-text="detailEntry?.keterangan"></dd>
                </div>
                <div>
                    <dt class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">PIC</dt>
                    <dd class="mt-0.5 text-gray-900" x-text="detailEntry?.pic"></dd>
                </div>
                <div>
                    <dt class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Tunai (Kas Kecil)</dt>
                    <dd class="mt-0.5 tabular-nums text-gray-900" x-text="detailEntry?.tunai_fmt"></dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Transfer</dt>
                    <dd class="mt-0.5 tabular-nums text-gray-900" x-text="detailEntry?.transfer_fmt"></dd>
                    <p class="mt-1 text-xs text-gray-500" x-show="detailEntry?.rekening_label" x-text="detailEntry?.rekening_label"></p>
                </div>
            </dl>
            <div class="mt-4" x-show="detailEntry?.bukti_lampiran_url">
                <a
                    :href="detailEntry?.bukti_lampiran_url"
                    target="_blank"
                    rel="noopener"
                    class="inline-flex text-sm font-medium text-red-700 underline hover:text-red-800"
                >Buka lampiran bukti</a>
            </div>
            <div class="mt-6 border-t border-gray-100 pt-4" x-show="detailEntry && detailEntry.approve_url">
                <p class="mb-3 text-xs text-gray-600">Anda dapat memproses pengajuan ini dari halaman verifikasi.</p>
                <form method="POST" :action="detailEntry.approve_url" class="mb-3">
                    @csrf
                    @foreach (['mode', 'tanggal', 'bulan', 'tahun', 'approval_status'] as $filterKey)
                        @if (request()->filled($filterKey))
                            <input type="hidden" name="{{ $filterKey }}" value="{{ request($filterKey) }}">
                        @endif
                    @endforeach
                    <button type="submit" class="btn-primary inline-flex w-full items-center justify-center gap-2 py-2.5">
                        Setujui
                    </button>
                </form>
                <form method="POST" :action="detailEntry.reject_url" class="space-y-2">
                    @csrf
                    @foreach (['mode', 'tanggal', 'bulan', 'tahun', 'approval_status'] as $filterKey)
                        @if (request()->filled($filterKey))
                            <input type="hidden" name="{{ $filterKey }}" value="{{ request($filterKey) }}">
                        @endif
                    @endforeach
                    <label class="wo-label">Catatan penolakan <span class="font-normal text-gray-400">(opsional)</span></label>
                    <textarea name="rejection_note" rows="2" maxlength="2000" class="wo-input wo-native min-h-[4.5rem] resize-y" placeholder="Contoh: Bukti kurang jelas"></textarea>
                    <button type="submit" class="btn-secondary inline-flex w-full items-center justify-center gap-2 border-red-200 py-2.5 text-red-800 hover:bg-red-50">
                        Tolak
                    </button>
                </form>
            </div>
        </div>
        </div>
    </div>
    @endif

    @if($canEditAuditTemuan)
    <div
        x-show="auditEntry !== null"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto overscroll-contain p-4"
        style="display: none;"
        @keydown.escape.window="auditEntry = null"
    >
        <div class="absolute inset-0 bg-black/40" @click="auditEntry = null"></div>
        <div class="relative mx-auto flex min-h-full items-center justify-center py-4">
            <div class="wo-modal-panel relative w-full max-w-lg !max-h-[min(90vh,720px)] !overflow-y-auto !p-6 sm:!p-8" @click.stop>
                <div class="flex items-start justify-between gap-3 border-b border-gray-100 pb-3">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Temuan kasus (Head Audit)</h3>
                        <p class="mt-0.5 text-xs text-gray-500" x-text="auditEntry ? ((auditEntry.nama_akun || '') + ' — ' + (auditEntry.keterangan || '')) : ''"></p>
                    </div>
                    <button type="button" @click="auditEntry = null" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-gray-500 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Tutup">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form method="POST" :action="auditEntry?.audit_temuan_url" class="finance-audit-allow mt-4 space-y-4">
                    @csrf
                    <input type="hidden" name="audit_has_temuan" value="1">
                    @foreach (['mode', 'tanggal', 'bulan', 'tahun', 'dari', 'sampai', 'approval_status'] as $filterKey)
                        @if (request()->filled($filterKey))
                            <input type="hidden" name="{{ $filterKey }}" value="{{ request($filterKey) }}">
                        @endif
                    @endforeach

                    <div class="space-y-4">
                        <div>
                            <label class="wo-label">Deskripsi <span class="text-red-500">*</span></label>
                            <textarea
                                name="audit_deskripsi"
                                rows="3"
                                maxlength="2000"
                                required
                                x-model="auditDeskripsi"
                                class="wo-input wo-native min-h-[5rem] resize-y"
                                placeholder="Jelaskan temuan kasus pada biaya ini"
                            ></textarea>
                            @error('audit_deskripsi')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="wo-label">Penyelesaian <span class="text-red-500">*</span></label>
                            <textarea
                                name="audit_penyelesaian"
                                rows="3"
                                maxlength="2000"
                                required
                                x-model="auditPenyelesaian"
                                class="wo-input wo-native min-h-[5rem] resize-y"
                                placeholder="Jelaskan tindak lanjut / penyelesaian temuan"
                            ></textarea>
                            @error('audit_penyelesaian')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-2 border-t border-gray-100 pt-4">
                        <button type="button" @click="auditEntry = null" class="btn-secondary">Batal</button>
                        <button type="submit" class="btn-primary">Simpan temuan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    @if(! $isVerifikasiBiayaOp && !empty($canSubmitBiaya) && $canSubmitBiaya)
    <div x-show="showAddModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto overscroll-contain p-4" style="display: none;">
        <div class="absolute inset-0 bg-black/40" @click="showAddModal = false"></div>
        <div class="relative mx-auto flex min-h-full items-center justify-center py-4">
        <div id="biaya-operasional-modal-panel" class="wo-modal-panel relative w-full max-w-3xl !max-h-[90vh] !overflow-y-auto !p-6 sm:!p-8">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Pengajuan biaya (Kas Kecil)</h2>
                    <p class="mt-1 text-sm text-gray-500">Nominal diambil dari Kas Kecil setelah Accounting menyetujui. Pastikan saldo mencukupi.</p>
                </div>
                <button type="button" @click="showAddModal = false" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 transition hover:bg-gray-100 hover:text-gray-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST" action="{{ route($storeRoute) }}" enctype="multipart/form-data" class="mt-4 grid gap-3 md:grid-cols-2">
                @csrf
                <input type="hidden" name="form_context" value="kas_kecil">
                @foreach (['mode', 'tanggal', 'bulan', 'tahun', 'approval_status'] as $filterKey)
                    @if (request()->filled($filterKey))
                        <input type="hidden" name="{{ $filterKey }}" value="{{ request($filterKey) }}">
                    @endif
                @endforeach
                <div>
                    <label class="mb-1 block text-xs font-semibold text-gray-600">Tanggal</label>
                    <input type="date" name="tanggal" value="{{ old('tanggal', $sampai->format('Y-m-d')) }}" required class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-gray-600">Jenis Operasional</label>
                    <select name="jenis_operasional" id="biaya-jenis-select" required class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40">
                        <option value="operasional" @selected(old('jenis_operasional', 'operasional') === 'operasional')>Biaya Operasional</option>
                        <option value="non_operasional" @selected(old('jenis_operasional') === 'non_operasional')>Biaya Non Operasional</option>
                    </select>
                    @error('jenis_operasional')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                @if(! $hasAnyAkunForBiaya)
                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-gray-600">Akun</label>
                    <p class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                        Belum ada akun dengan kode <strong>5.2.x</strong> atau <strong>5.3.x</strong> di master. Kelola di
                        <a href="{{ route('speedshop.keuangan.akun-operasional.index') }}" class="font-semibold text-red-700 underline hover:text-red-800">Keuangan → No Akun</a>.
                    </p>
                </div>
                @else
                <div>
                    <label class="mb-1 block text-xs font-semibold text-gray-600">Akun</label>
                    <select name="akun_operasional_id" id="biaya-akun-select" class="select2-akun-biaya w-full rounded-lg border border-gray-200 bg-white text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40" required>
                        <option value="">Pilih akun</option>
                    </select>
                    <p id="biaya-akun-empty-hint" class="mt-1 hidden text-xs text-amber-800"></p>
                    @error('akun_operasional_id')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-gray-600">Rekening sumber</label>
                    <input type="text" readonly tabindex="-1" value="Kas Kecil (buku)" class="w-full cursor-default rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm font-medium text-gray-800 shadow-inner focus:border-gray-200 focus:outline-none focus:ring-0" aria-readonly="true">
                </div>
                @endif
                <div>
                    <label class="mb-1 block text-xs font-semibold text-gray-600" for="biaya-saldo-kas-kecil">Saldo Kas Kecil (simulasi)</label>
                    <input
                        id="biaya-saldo-kas-kecil"
                        type="text"
                        readonly
                        tabindex="-1"
                        autocomplete="off"
                        :value="saldoKasKecilPreview()"
                        :class="(saldoKasKecilBase !== null && (saldoKasKecilBase - intFromDigits(outletNominalDigits)) < 0)
                            ? 'w-full cursor-default rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold tabular-nums text-red-800 shadow-inner focus:border-red-200 focus:outline-none focus:ring-0'
                            : 'w-full cursor-default rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm font-semibold tabular-nums text-gray-900 shadow-inner focus:border-gray-200 focus:outline-none focus:ring-0'"
                    >
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-gray-600" for="biaya-nominal-outlet">Nominal</label>
                    <input type="hidden" name="nominal" :value="intFromDigits(outletNominalDigits) > 0 ? intFromDigits(outletNominalDigits) : ''">
                    <input
                        id="biaya-nominal-outlet"
                        type="text"
                        inputmode="numeric"
                        autocomplete="off"
                        placeholder="0"
                        required
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm tabular-nums focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40"
                        x-ref="outletNominalIn"
                        x-init="$nextTick(() => { if ($refs.outletNominalIn) { $refs.outletNominalIn.value = fmtRpDigits(outletNominalDigits); } })"
                        @input="outletNominalDigits = $event.target.value.replace(/\D/g, ''); $event.target.value = fmtRpDigits(outletNominalDigits)"
                    >
                    @error('nominal')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="md:col-span-2 rounded-xl border border-gray-200 bg-gray-50/80 p-4">
                    <label class="mb-1 block text-xs font-semibold text-gray-600" for="biaya-bukti-outlet">
                        Lampiran bukti
                        @if(\Illuminate\Support\Facades\Schema::hasColumn('speedshop_rincian_biaya_entries', 'bukti_lampiran_path'))
                            <span class="text-red-600">*</span>
                        @else
                            <span class="font-normal text-gray-500">(opsional)</span>
                        @endif
                    </label>
                    <input id="biaya-bukti-outlet" type="file" name="bukti_lampiran" accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf" @if(\Illuminate\Support\Facades\Schema::hasColumn('speedshop_rincian_biaya_entries', 'bukti_lampiran_path')) required @endif class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-red-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-red-700 hover:file:bg-red-100">
                    @error('bukti_lampiran')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-gray-600">Keterangan</label>
                    <input type="text" name="keterangan" value="{{ old('keterangan') }}" placeholder="Contoh: Pembelian ATK" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40">
                </div>

                <div class="mt-2 flex items-center justify-end gap-2 md:col-span-2">
                    <button type="button" @click="showAddModal = false" class="btn-secondary px-4 py-2.5">Batal</button>
                    <button type="submit" @if(! $hasAnyAkunForBiaya) disabled @endif class="btn-primary inline-flex items-center gap-2 px-4 py-2.5 disabled:cursor-not-allowed disabled:opacity-50">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        Kirim pengajuan
                    </button>
                </div>
            </form>
        </div>
        </div>
    </div>
    @endif

    @if(! $isVerifikasiBiayaOp && !empty($canSubmitBiayaAccounting) && $canSubmitBiayaAccounting)
    <div x-show="showAccModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto overscroll-contain p-4" style="display: none;">
        <div class="absolute inset-0 bg-black/40" @click="showAccModal = false"></div>
        <div class="relative mx-auto flex min-h-full items-center justify-center py-4">
        <div id="biaya-acc-modal-panel" class="wo-modal-panel relative w-full max-w-3xl !max-h-[90vh] !overflow-y-auto !p-6 sm:!p-8">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Pengajuan biaya (Rekening)</h2>
                    <p class="mt-1 text-sm text-gray-500">Pilih rekening sumber, cek saldo buku, lalu isi nominal. Beberapa akun (gaji, THR, promosi, sewa, dll.) memerlukan rekening tujuan alokasi dana.</p>
                </div>
                <button type="button" @click="showAccModal = false" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 transition hover:bg-gray-100 hover:text-gray-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            @if($keuanganRekeningsForBiaya->isEmpty())
                <p class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                    Belum ada rekening Kas Besar / Rekening Utama untuk cabang ini. Tambah di
                    <a href="{{ route('speedshop.keuangan.rekening') }}" class="font-semibold text-red-700 underline hover:text-red-800">Keuangan → Rekening</a>.
                </p>
                <div class="mt-4 flex justify-end">
                    <button type="button" @click="showAccModal = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-50">Tutup</button>
                </div>
            @else
            <form method="POST" action="{{ route($storeRoute) }}" enctype="multipart/form-data" class="mt-4 grid gap-3 md:grid-cols-2">
                @csrf
                <input type="hidden" name="form_context" value="accounting_biaya">
                @foreach (['mode', 'tanggal', 'bulan', 'tahun', 'approval_status'] as $filterKey)
                    @if (request()->filled($filterKey))
                        <input type="hidden" name="{{ $filterKey }}" value="{{ request($filterKey) }}">
                    @endif
                @endforeach
                <div>
                    <label class="mb-1 block text-xs font-semibold text-gray-600">Tanggal</label>
                    <input type="date" name="tanggal" value="{{ old('tanggal', $sampai->format('Y-m-d')) }}" required class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-gray-600">Jenis Operasional</label>
                    <select name="jenis_operasional" id="biaya-acc-jenis-select" required class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40">
                        <option value="operasional" @selected(old('jenis_operasional', 'operasional') === 'operasional')>Biaya Operasional</option>
                        <option value="non_operasional" @selected(old('jenis_operasional') === 'non_operasional')>Biaya Non Operasional</option>
                    </select>
                    @error('jenis_operasional')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                @if(! $hasAnyAkunForBiaya)
                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-gray-600">Akun</label>
                    <p class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                        Belum ada akun dengan kode <strong>5.2.x</strong> atau <strong>5.3.x</strong> di master.
                        <a href="{{ route('speedshop.keuangan.akun-operasional.index') }}" class="font-semibold text-red-700 underline hover:text-red-800">Keuangan → No Akun</a>.
                    </p>
                </div>
                @else
                <div>
                    <label class="mb-1 block text-xs font-semibold text-gray-600">Akun</label>
                    <select name="akun_operasional_id" id="biaya-acc-akun-select" class="select2-akun-biaya-acc w-full rounded-lg border border-gray-200 bg-white text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40" required>
                        <option value="">Pilih akun</option>
                    </select>
                    <p id="biaya-acc-akun-empty-hint" class="mt-1 hidden text-xs text-amber-800"></p>
                    @error('akun_operasional_id')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-gray-600">Rekening sumber</label>
                    <select name="keuangan_rekening_id" x-model="rekAccId" required class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40">
                        <option value="">Pilih rekening</option>
                        @foreach($keuanganRekeningsForBiaya as $r)
                            @php $jenisLabel = \App\Models\SpeedshopKeuanganRekening::jenisOptions()[$r->jenis] ?? $r->jenis; @endphp
                            <option value="{{ $r->id }}" @selected((string) old('keuangan_rekening_id') === (string) $r->id)>{{ $jenisLabel }} — {{ $r->nama }}@if($r->nama_bank) ({{ $r->nama_bank }})@endif</option>
                        @endforeach
                    </select>
                    @error('keuangan_rekening_id')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div x-show="accNeedsRekeningTujuan()" x-cloak>
                    <label class="mb-1 block text-xs font-semibold text-gray-600">Rekening tujuan</label>
                    <select name="keuangan_rekening_tujuan_id" x-model="rekAccTujuanId" :required="accNeedsRekeningTujuan()" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40">
                        <option value="">Pilih rekening tujuan</option>
                        @foreach($keuanganRekeningsForBiaya as $r)
                            @php $jenisLabel = \App\Models\SpeedshopKeuanganRekening::jenisOptions()[$r->jenis] ?? $r->jenis; @endphp
                            <option value="{{ $r->id }}" @selected((string) old('keuangan_rekening_tujuan_id') === (string) $r->id)>{{ $jenisLabel }} — {{ $r->nama }}@if($r->nama_bank) ({{ $r->nama_bank }})@endif</option>
                        @endforeach
                    </select>
                    @error('keuangan_rekening_tujuan_id')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                @endif
                <div>
                    <label class="mb-1 block text-xs font-semibold text-gray-600" for="biaya-saldo-rek-preview" x-text="accNeedsRekeningTujuan() ? 'Saldo rekening sumber (setelah transfer)' : 'Saldo tersedia saat ini (rekening)'"></label>
                    <input
                        id="biaya-saldo-rek-preview"
                        type="text"
                        readonly
                        tabindex="-1"
                        autocomplete="off"
                        :value="saldoRekPreview()"
                        :class="saldoRekPreviewNegative()
                            ? 'w-full cursor-default rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold tabular-nums text-red-800 shadow-inner focus:border-red-200 focus:outline-none focus:ring-0'
                            : 'w-full cursor-default rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm font-semibold tabular-nums text-gray-900 shadow-inner focus:border-gray-200 focus:outline-none focus:ring-0'"
                    >
                </div>
                <div x-show="accNeedsRekeningTujuan()" x-cloak>
                    <label class="mb-1 block text-xs font-semibold text-gray-600" for="biaya-saldo-rek-tujuan-preview">Saldo rekening tujuan (setelah transfer)</label>
                    <input
                        id="biaya-saldo-rek-tujuan-preview"
                        type="text"
                        readonly
                        tabindex="-1"
                        autocomplete="off"
                        :value="saldoRekTujuanPreview()"
                        class="w-full cursor-default rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-semibold tabular-nums text-emerald-900 shadow-inner focus:border-emerald-200 focus:outline-none focus:ring-0"
                    >
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-gray-600" for="biaya-nominal-acc">Nominal</label>
                    <input type="hidden" name="nominal" :value="intFromDigits(accNominalDigits) > 0 ? intFromDigits(accNominalDigits) : ''">
                    <input
                        id="biaya-nominal-acc"
                        type="text"
                        inputmode="numeric"
                        autocomplete="off"
                        placeholder="0"
                        required
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm tabular-nums focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40"
                        x-ref="accNominalIn"
                        x-init="$nextTick(() => { if ($refs.accNominalIn) { $refs.accNominalIn.value = fmtRpDigits(accNominalDigits); } })"
                        @input="accNominalDigits = $event.target.value.replace(/\D/g, ''); $event.target.value = fmtRpDigits(accNominalDigits)"
                    >
                    @error('nominal')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="md:col-span-2 rounded-xl border border-gray-200 bg-gray-50/80 p-4">
                    <label class="mb-1 block text-xs font-semibold text-gray-600" for="biaya-bukti-acc">
                        Lampiran bukti
                        @if(\Illuminate\Support\Facades\Schema::hasColumn('speedshop_rincian_biaya_entries', 'bukti_lampiran_path'))
                            <span class="text-red-600">*</span>
                        @else
                            <span class="font-normal text-gray-500">(opsional)</span>
                        @endif
                    </label>
                    <input id="biaya-bukti-acc" type="file" name="bukti_lampiran" accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf" @if(\Illuminate\Support\Facades\Schema::hasColumn('speedshop_rincian_biaya_entries', 'bukti_lampiran_path')) required @endif class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-red-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-red-700 hover:file:bg-red-100">
                    @error('bukti_lampiran')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-gray-600">Keterangan</label>
                    <input type="text" name="keterangan" value="{{ old('keterangan') }}" placeholder="Contoh: Listrik kantor" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40">
                </div>

                <div class="mt-2 flex items-center justify-end gap-2 md:col-span-2">
                    <button type="button" @click="showAccModal = false" class="btn-secondary px-4 py-2.5">Batal</button>
                    <button type="submit" @if(! $hasAnyAkunForBiaya) disabled @endif class="btn-primary inline-flex items-center gap-2 px-4 py-2.5 disabled:cursor-not-allowed disabled:opacity-50">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        Kirim pengajuan
                    </button>
                </div>
            </form>
            @endif
        </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<style>
#biaya-operasional-modal-panel .select2-container { width: 100% !important; }
#biaya-operasional-modal-panel .select2-container--default .select2-selection--single {
    min-height: 42px;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    background: #fff;
    display: flex;
    align-items: center;
}
#biaya-operasional-modal-panel .select2-container--default .select2-selection--single .select2-selection__rendered {
    padding-left: 12px;
    line-height: 1.25rem;
    color: #111827;
    font-size: 14px;
}
#biaya-operasional-modal-panel .select2-container--default .select2-selection--single .select2-selection__arrow { height: 40px; right: 10px; }
#biaya-operasional-modal-panel .select2-container--default .select2-selection--single .select2-selection__clear { display: none; }
#biaya-operasional-modal-panel .select2-container--default.select2-container--focus .select2-selection--single {
    border-color: #f87171;
    box-shadow: 0 0 0 4px rgba(248, 113, 113, 0.2);
}
.select2-dropdown { z-index: 9999 !important; border-radius: 0.5rem; border: 1px solid #e5e7eb; }
.select2-container--default .select2-search--dropdown .select2-search__field {
    border: 1px solid #e5e7eb;
    border-radius: 0.375rem;
    padding: 8px 10px;
    font-size: 14px;
}
.select2-container--default .select2-results__option { font-size: 14px; padding: 8px 12px; }
.select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
    background: #fee2e2;
    color: #111827;
}
#biaya-acc-modal-panel .select2-container { width: 100% !important; }
#biaya-acc-modal-panel .select2-container--default .select2-selection--single {
    min-height: 42px;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    background: #fff;
    display: flex;
    align-items: center;
}
#biaya-acc-modal-panel .select2-container--default .select2-selection--single .select2-selection__rendered {
    padding-left: 12px;
    line-height: 1.25rem;
    color: #111827;
    font-size: 14px;
}
#biaya-acc-modal-panel .select2-container--default .select2-selection--single .select2-selection__arrow { height: 40px; right: 10px; }
#biaya-acc-modal-panel .select2-container--default .select2-selection--single .select2-selection__clear { display: none; }
#biaya-acc-modal-panel .select2-container--default.select2-container--focus .select2-selection--single {
    border-color: #f87171;
    box-shadow: 0 0 0 4px rgba(248, 113, 113, 0.2);
}
</style>
@endpush

@push('scripts')
<script>
window.rincianBiayaOpPage = function (init) {
    return {
        loading: false,
        ...init,
        openAuditTemuan(payload) {
            this.auditEntry = payload || null;
            this.auditDeskripsi = payload && payload.audit_deskripsi ? String(payload.audit_deskripsi) : '';
            this.auditPenyelesaian = payload && payload.audit_penyelesaian ? String(payload.audit_penyelesaian) : '';
        },
        fmtRpDigits(digits) {
            const x = String(digits == null ? '' : digits).replace(/\D/g, '');
            if (!x) return '';
            return x.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        },
        intFromDigits(digits) {
            const x = String(digits == null ? '' : digits).replace(/\D/g, '');
            if (!x) return 0;
            const n = parseInt(x, 10);
            return Number.isFinite(n) ? n : 0;
        },
        saldoKasKecilPreview() {
            if (this.saldoKasKecilBase === null || typeof this.saldoKasKecilBase === 'undefined') {
                return '—';
            }
            const n = this.intFromDigits(this.outletNominalDigits);
            const s = this.saldoKasKecilBase - n;
            return 'Rp ' + s.toLocaleString('id-ID');
        },
        saldoRekBaseNumber() {
            const id = String(this.rekAccId ?? '').trim();
            if (!id) return null;
            const m = this.saldoRekMap || {};
            let v = m[id];
            if (v === undefined || v === null) {
                v = m[parseInt(id, 10)];
            }
            if (v === undefined || v === null) return null;
            const base = Number(v);
            return Number.isFinite(base) ? base : null;
        },
        saldoRekPreview() {
            const base = this.saldoRekBaseNumber();
            if (base === null) return '—';
            const n = this.intFromDigits(this.accNominalDigits);
            const s = base - n;
            return 'Rp ' + s.toLocaleString('id-ID');
        },
        saldoRekPreviewNegative() {
            const base = this.saldoRekBaseNumber();
            if (base === null) return false;
            return base - this.intFromDigits(this.accNominalDigits) < 0;
        },
        accNeedsRekeningTujuan() {
            const akunId = String(this.accAkunId ?? '').trim();
            if (!akunId) return false;
            const jenisEl = document.getElementById('biaya-acc-jenis-select');
            const jenis = jenisEl ? String(jenisEl.value || 'operasional') : 'operasional';
            const list = (window.__biayaAkunByJenis || {})[jenis] || [];
            const row = list.find(function (a) { return String(a.id) === akunId; });
            return !!(row && row.requires_rekening_tujuan);
        },
        saldoRekTujuanBaseNumber() {
            const id = String(this.rekAccTujuanId ?? '').trim();
            if (!id) return null;
            const m = this.saldoRekMap || {};
            let v = m[id];
            if (v === undefined || v === null) {
                v = m[parseInt(id, 10)];
            }
            if (v === undefined || v === null) return null;
            const base = Number(v);
            return Number.isFinite(base) ? base : null;
        },
        saldoRekTujuanPreview() {
            const base = this.saldoRekTujuanBaseNumber();
            if (base === null) return '—';
            const n = this.intFromDigits(this.accNominalDigits);
            return 'Rp ' + (base + n).toLocaleString('id-ID');
        },
    };
};
</script>
<script>window.__biayaAkunByJenis = @json($akunByJenis);</script>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.jQuery === 'undefined' || typeof window.jQuery.fn.select2 === 'undefined') return;
    const $ = window.jQuery;
    const akunByJenis = window.__biayaAkunByJenis || { operasional: [], non_operasional: [] };
    const oldAkunId = @json((string) old('akun_operasional_id', ''));
    const oldJenis = @json((string) old('jenis_operasional', 'operasional'));

    function destroyBiayaAkunSelect2() {
        const $el = $('#biaya-akun-select');
        if ($el.length && $el.data('select2')) {
            $el.select2('destroy');
        }
    }

    function fillBiayaAkunOptions(jenis) {
        const $el = $('#biaya-akun-select');
        if (!$el.length) return;
        const list = Array.isArray(akunByJenis[jenis]) ? akunByJenis[jenis] : [];
        $el.empty().append($('<option>', { value: '', text: 'Pilih akun' }));
        list.forEach(function (row) {
            $el.append($('<option>', { value: String(row.id), text: String(row.no) + ' — ' + String(row.nama) }));
        });
    }

    function updateBiayaAkunEmptyHint(jenis) {
        const hint = document.getElementById('biaya-akun-empty-hint');
        if (!hint) return;
        const list = Array.isArray(akunByJenis[jenis]) ? akunByJenis[jenis] : [];
        if (list.length === 0) {
            hint.textContent = jenis === 'non_operasional'
                ? 'Belum ada akun berkode 5.3.x di master. Tambah di Master data → Akun operasional.'
                : 'Belum ada akun berkode 5.2.x di master. Tambah di Master data → Akun operasional.';
            hint.classList.remove('hidden');
        } else {
            hint.classList.add('hidden');
        }
    }

    function initBiayaAkunSelect2() {
        const $el = $('#biaya-akun-select');
        if (!$el.length || $el.data('select2')) return;
        $el.select2({
            width: '100%',
            placeholder: 'Cari atau pilih no. akun / nama akun',
            allowClear: true,
            dropdownParent: $(document.body),
        });
    }

    function syncBiayaAkunToJenis(jenis, keepSelectionIfValid) {
        destroyBiayaAkunSelect2();
        fillBiayaAkunOptions(jenis);
        updateBiayaAkunEmptyHint(jenis);
        const $el = $('#biaya-akun-select');
        if (!$el.length) return;
        if (keepSelectionIfValid && oldAkunId) {
            const hasOpt = $el.find('option').filter(function () { return String(this.value) === String(oldAkunId); }).length > 0;
            if (hasOpt) {
                $el.val(String(oldAkunId));
            }
        }
        if (! $el.val()) {
            $el.val(null);
        }
        initBiayaAkunSelect2();
    }

    const $akun = $('#biaya-akun-select');
    if ($akun.length) {
        const jenisEl = document.getElementById('biaya-jenis-select');
        const initialJenis = (jenisEl && jenisEl.value) ? jenisEl.value : oldJenis;
        if (jenisEl && oldJenis && jenisEl.querySelector('option[value="' + oldJenis + '"]')) {
            jenisEl.value = oldJenis;
        }
        syncBiayaAkunToJenis(initialJenis, true);

        if (jenisEl) {
            jenisEl.addEventListener('change', function () {
                syncBiayaAkunToJenis(this.value, false);
            });
        }
    }

    const $accAkun = $('#biaya-acc-akun-select');
    if ($accAkun.length) {
        function destroyAccBiayaAkunSelect2() {
            const $el = $('#biaya-acc-akun-select');
            if ($el.length && $el.data('select2')) {
                $el.select2('destroy');
            }
        }
        function fillAccBiayaAkunOptions(jenis) {
            const $el = $('#biaya-acc-akun-select');
            if (!$el.length) return;
            const list = Array.isArray(akunByJenis[jenis]) ? akunByJenis[jenis] : [];
            $el.empty().append($('<option>', { value: '', text: 'Pilih akun' }));
            list.forEach(function (row) {
                $el.append($('<option>', { value: String(row.id), text: String(row.no) + ' — ' + String(row.nama) }));
            });
        }
        function updateAccBiayaAkunEmptyHint(jenis) {
            const hint = document.getElementById('biaya-acc-akun-empty-hint');
            if (!hint) return;
            const list = Array.isArray(akunByJenis[jenis]) ? akunByJenis[jenis] : [];
            if (list.length === 0) {
                hint.textContent = jenis === 'non_operasional'
                    ? 'Belum ada akun berkode 5.3.x di master. Tambah di Master data → Akun operasional.'
                    : 'Belum ada akun berkode 5.2.x di master. Tambah di Master data → Akun operasional.';
                hint.classList.remove('hidden');
            } else {
                hint.classList.add('hidden');
            }
        }
        function initAccBiayaAkunSelect2() {
            const $el = $('#biaya-acc-akun-select');
            if (!$el.length || $el.data('select2')) return;
            $el.select2({
                width: '100%',
                placeholder: 'Cari atau pilih no. akun / nama akun',
                allowClear: true,
                dropdownParent: $(document.body),
            });
            $el.off('change.biayaAccAkun').on('change.biayaAccAkun', syncAccAkunToAlpine);
        }
        function syncAccAkunToAlpine() {
            const pageRoot = document.querySelector('.ss-dash-page[x-data]');
            if (!pageRoot || typeof window.Alpine === 'undefined') return;
            const comp = window.Alpine.$data(pageRoot);
            const $el = $('#biaya-acc-akun-select');
            if (!comp || !$el.length) return;
            comp.accAkunId = String($el.val() || '');
            if (typeof comp.accNeedsRekeningTujuan === 'function' && !comp.accNeedsRekeningTujuan()) {
                comp.rekAccTujuanId = '';
            }
        }
        function syncAccBiayaAkunToJenis(jenis, keepSelectionIfValid) {
            destroyAccBiayaAkunSelect2();
            fillAccBiayaAkunOptions(jenis);
            updateAccBiayaAkunEmptyHint(jenis);
            const $el = $('#biaya-acc-akun-select');
            if (!$el.length) return;
            if (keepSelectionIfValid && oldAkunId) {
                const hasOpt = $el.find('option').filter(function () { return String(this.value) === String(oldAkunId); }).length > 0;
                if (hasOpt) {
                    $el.val(String(oldAkunId));
                }
            }
            if (! $el.val()) {
                $el.val(null);
            }
            initAccBiayaAkunSelect2();
            syncAccAkunToAlpine();
        }

        const accJenisEl = document.getElementById('biaya-acc-jenis-select');
        const accInitialJenis = (accJenisEl && accJenisEl.value) ? accJenisEl.value : oldJenis;
        if (accJenisEl && oldJenis && accJenisEl.querySelector('option[value="' + oldJenis + '"]')) {
            accJenisEl.value = oldJenis;
        }
        syncAccBiayaAkunToJenis(accInitialJenis, true);

        if (accJenisEl) {
            accJenisEl.addEventListener('change', function () {
                syncAccBiayaAkunToJenis(this.value, false);
            });
        }
    }
});
</script>
@endpush
