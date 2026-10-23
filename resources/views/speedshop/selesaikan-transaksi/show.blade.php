@extends('layouts.speedshop')

@php $isWipContext = ($context ?? null) === 'wip'; @endphp
@php
    $diskonVerifikasiManagerPage = (bool) ($diskonVerifikasiManagerPage ?? false);
    $pageTitle = $diskonVerifikasiManagerPage
        ? 'Verifikasi Diskon WO'
        : ($isWipContext ? 'Work In Progress' : 'Selesaikan Transaksi');
@endphp
@section('title', $pageTitle . ' - ' . $service_order->kode_servis)

@section('content')
@php
    $isDiskonVerifier = auth()->user()->isManager();
    $diskonVerifikasiManagerPage = (bool) ($diskonVerifikasiManagerPage ?? false);
    $diskonApproveUrl = $diskonVerifikasiManagerPage
        ? route('speedshop.verifikasi.diskon-work-order.approve', $service_order)
        : route('speedshop.selesaikan-transaksi.approve', $service_order);
    $diskonRejectUrl = $diskonVerifikasiManagerPage
        ? route('speedshop.verifikasi.diskon-work-order.reject', $service_order)
        : route('speedshop.selesaikan-transaksi.reject', $service_order);
    $diskonVerifikasiIndexUrl = route('speedshop.verifikasi.diskon-work-order.index');
    $diskonVerifikasiStatus = $service_order->diskon_verifikasi_status ?? null;
    $diskonVerifikasiStatusLabel = match ($diskonVerifikasiStatus) {
        'pending' => 'Menunggu',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
        default => $diskonVerifikasiStatus ? ucfirst((string) $diskonVerifikasiStatus) : '-',
    };
    $isAccounting = auth()->user()->isAccounting();
    $persistedChargeAmount = $service_order->invoice
        ? \App\Services\InvoiceSettingsService::resolveChargeAmountForInvoice(
            $service_order->invoice,
            (int) $service_order->grand_total,
        )
        : 0;
    $isPendingVerify = ($service_order->diskon_verifikasi_status ?? null) === 'pending';
    $isPendingBatal = ($service_order->batal_verifikasi_status ?? null) === 'pending';
    $isBatalApproved = ($service_order->batal_verifikasi_status ?? null) === 'approved';
    $isBatalRejected = ($service_order->batal_verifikasi_status ?? null) === 'rejected';
    $showBatalFlowSteps = $isWipContext && ($isPendingBatal || $isBatalApproved);
    $canRequestBatal = $isWipContext
        && in_array($service_order->status, ['antri', 'proses'], true)
        && $service_order->status !== 'dibatalkan'
        && ! $isPendingBatal;
    $isSplitSo = (($service_order->jenis_pembayaran ?? '') === 'split') && is_array($service_order->pembayaran_split ?? null);
    $defaultPembayaranMode = old('pembayaran_mode', $isSplitSo ? 'split' : 'single');
    $defaultSplitRows = old('pembayaran');
    if (! is_array($defaultSplitRows) || count($defaultSplitRows) < 2) {
        if ($isSplitSo) {
            $defaultSplitRows = $service_order->pembayaran_split;
        }
        if (! is_array($defaultSplitRows) || count($defaultSplitRows) < 2) {
            $defaultSplitRows = [
                ['metode' => 'cash', 'jumlah' => ''],
                ['metode' => 'transfer', 'jumlah' => ''],
            ];
        }
    }
    $splitRowsForJs = collect($defaultSplitRows)->map(function ($r) {
        return [
            'metode' => (string) ($r['metode'] ?? 'cash'),
            'jumlah' => isset($r['jumlah']) ? (string) $r['jumlah'] : '',
        ];
    })->values()->all();
    $jasaOptionsForJs = $jasaServisOptions
        ->map(function ($jasa) {
            return [
                'id' => (int) $jasa->id,
                'nama' => (string) $jasa->nama,
                'biaya' => (int) $jasa->biaya,
            ];
        })
        ->values()
        ->all();
    $workOrderSheetUrl = route('speedshop.work-orders.sheet.a4', $service_order);
    $canReprintWorkOrder = $service_order->status === 'proses';
    $useStyledShell = $isWipContext || ! $diskonVerifikasiManagerPage;
    $woPanelClass = $useStyledShell
        ? 'panel-card panel-card-flush overflow-visible ss-dash-enter-child'
        : 'overflow-hidden rounded-2xl border border-gray-200 bg-white';
@endphp

@if($useStyledShell)
@push('styles')
@include('partials.speedshop.dashboard-load-animations')
@include('partials.speedshop.work-order-create-styles')
@endpush
@endif

<div
    x-data="selesaikanTransaksiShow()"
    x-init="init(); @if($useStyledShell) $nextTick(() => { requestAnimationFrame(() => $el.classList.add('is-ready')); }); @endif"
    @class(['ss-dashboard ss-dash-page ss-wo-create' => $useStyledShell])
>
<div @class(['ss-dash-content space-y-5' => $useStyledShell, 'space-y-5' => ! $useStyledShell])>
    @if(! $useStyledShell)
    <nav class="flex items-center gap-2 text-sm text-gray-500 ss-dash-enter" style="--ss-delay: 0ms">
        <a href="{{ route('speedshop.dashboard') }}" class="transition hover:text-gray-700">Home</a>
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        @if($diskonVerifikasiManagerPage)
        <a href="{{ $diskonVerifikasiIndexUrl }}" class="transition hover:text-gray-700">Verifikasi Diskon WO</a>
        @else
        <a href="{{ route('speedshop.selesaikan-transaksi.index') }}" class="transition hover:text-gray-700">Selesaikan Transaksi</a>
        @endif
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <span class="font-medium text-gray-900">{{ $service_order->kode_servis }}</span>
    </nav>
    @endif

    @if($diskonVerifikasiManagerPage)
    <div class="flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white p-5 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">Detail Verifikasi Diskon</h1>
            <p class="mt-1 text-[13px] text-gray-500">Tinjau pengajuan diskon &gt; 10% pada work order ini. Manager hanya menyetujui atau menolak diskon.</p>
        </div>
        @php
            $diskonStatusClass = match ($diskonVerifikasiStatus) {
                'pending' => 'border-amber-200 bg-amber-50 text-amber-800',
                'approved' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
                'rejected' => 'border-red-200 bg-red-50 text-red-800',
                default => 'border-gray-200 bg-gray-50 text-gray-700',
            };
        @endphp
        <span class="inline-flex shrink-0 items-center rounded-lg border px-3 py-1.5 text-sm font-semibold {{ $diskonStatusClass }}">{{ $diskonVerifikasiStatusLabel }}</span>
    </div>
    @endif

    @if($isPendingBatal)
    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        <p class="font-semibold">Menunggu verifikasi pembatalan Accounting</p>
        @if(!empty($service_order->batal_verifikasi_reason))
        <p class="mt-1 text-xs text-amber-800/90"><span class="font-medium">Alasan:</span> {{ $service_order->batal_verifikasi_reason }}</p>
        @endif
        @if(!$isAccounting)
        <p class="mt-1 text-xs text-amber-800/90">Work order tidak dapat diubah sampai Accounting menyetujui atau menolak pengajuan.</p>
        @endif
    </div>
    @endif
    @if(($service_order->batal_verifikasi_status ?? null) === 'rejected' && !empty($service_order->batal_verifikasi_rejected_reason))
    <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900">
        <p class="font-semibold">Pembatalan ditolak Accounting</p>
        <p class="mt-1 text-xs text-rose-800/90">{{ $service_order->batal_verifikasi_rejected_reason }}</p>
    </div>
    @endif

    <form id="form-selesaikan-transaksi" action="{{ $isWipContext ? route('speedshop.wip.items.update', $service_order) : route('speedshop.selesaikan-transaksi.store', $service_order) }}" method="POST" @submit.prevent="submitForm($event)" @class(['ss-dash-enter' => $useStyledShell]) @if($useStyledShell) style="--ss-delay: 80ms" @endif>
        @csrf
        <div id="remove-jasa-container"></div>
        <div id="remove-sparepart-container"></div>
        <div id="add-jasa-container"></div>
        <div id="add-sparepart-container"></div>
        <div class="grid grid-cols-1 gap-5 lg:grid-cols-12">
            <div class="space-y-5 lg:col-span-8 @if($diskonVerifikasiManagerPage) pointer-events-none select-none @elseif($isPendingBatal) pointer-events-none opacity-60 @endif">
                <div class="{{ $woPanelClass }}" @if($useStyledShell) style="--ss-delay: 120ms; --ss-stagger: 0" @endif>
                    <div class="flex items-start justify-between border-b border-gray-100 px-5 py-4">
                        <div>
                            <h2 class="text-[20px] font-semibold text-gray-900">Informasi Work Order</h2>
                            <p class="mt-1 text-[13px] text-gray-500">Data pelanggan, kendaraan, dan detail service</p>
                        </div>
                        <div class="flex items-center gap-2">
                            @if($service_order->status === 'antri')
                                <span class="inline-flex items-center gap-1 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">Antri</span>
                            @elseif($service_order->status === 'proses')
                                @if($isPendingVerify)
                                    <span class="inline-flex max-w-[220px] items-center gap-1 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800" title="Status WO tetap Proses sampai Manager approve/tolak diskon; lalu WO selesai.">Menunggu verifikasi Manager</span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full border border-blue-200 bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">Proses</span>
                                @endif
                            @elseif($service_order->status === 'selesai')
                                <span class="inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Selesai</span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full border border-rose-200 bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700">{{ ucfirst($service_order->status) }}</span>
                            @endif
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-red-50 text-red-600">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 px-5 py-5 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Pelanggan</label>
                            <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm font-medium text-gray-900">{{ $service_order->pelanggan->nama ?? '-' }}</div>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">No HP</label>
                            <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700">{{ $service_order->pelanggan->no_hp ?? '-' }}</div>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Alamat</label>
                            <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700">{{ $service_order->pelanggan->alamat ?? '-' }}</div>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Kendaraan &amp; Service</label>
                            <div class="rounded-xl border border-gray-200 bg-[#f7f4f5]">
                                <div class="flex items-center gap-3 border-b border-gray-200 px-4 py-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand text-brand-ink shadow-md shadow-brand/35">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h12m-9-8.25h-1.5a2.25 2.25 0 0 0-2.25 2.25v7.5a2.25 2.25 0 0 0 2.25 2.25h3a2.25 2.25 0 0 0 2.25-2.25v-7.5a2.25 2.25 0 0 0-2.25-2.25H9.75m-6 0V9.75A2.25 2.25 0 0 1 5.25 7.5h13.5A2.25 2.25 0 0 1 21 9.75v1.5m-18 0A2.25 2.25 0 0 0 5.25 12.75h13.5A2.25 2.25 0 0 0 21 11.25v-1.5"/></svg>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="truncate text-base font-semibold text-gray-900">{{ $service_order->kendaraan?->nomor_polisi ?? '—' }}</div>
                                        <div class="text-[11px] uppercase tracking-wide text-gray-500">Identitas Kendaraan &amp; Layanan</div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 gap-1 px-4 py-3 sm:grid-cols-3">
                                    <div>
                                        <div class="text-[11px] font-medium uppercase tracking-wide text-gray-500">Merk / Tipe</div>
                                        <div class="mt-0.5 text-sm font-semibold text-gray-900">{{ $service_order->kendaraan ? trim(($service_order->kendaraan->merk ?? '').' '.($service_order->kendaraan->tipe ?? '')) ?: '—' : '—' }}</div>
                                    </div>
                                    <div>
                                        <div class="text-[11px] font-medium uppercase tracking-wide text-gray-500">Mekanik</div>
                                        <div class="mt-0.5 text-sm font-semibold text-gray-900">{{ $service_order->mekanik?->nama ?? '—' }}</div>
                                    </div>
                                    <div>
                                        <div class="text-[11px] font-medium uppercase tracking-wide text-gray-500">Sumber Kedatangan</div>
                                        <div class="mt-0.5 text-sm font-semibold text-gray-900">{{ $service_order->sumber_kedatangan ? (\App\Models\ServiceOrder::SUMBER_KEDATANGAN[$service_order->sumber_kedatangan] ?? $service_order->sumber_kedatangan) : '—' }}</div>
                                    </div>
                                    <div>
                                        <div class="text-[11px] font-medium uppercase tracking-wide text-gray-500">Kategori Service</div>
                                        <div class="mt-0.5 text-sm font-semibold text-gray-900">{{ $service_order->kategori_service ? (\App\Models\ServiceOrder::KATEGORI_SERVICE[$service_order->kategori_service] ?? $service_order->kategori_service) : '—' }}</div>
                                    </div>
                                    <div>
                                        <div class="text-[11px] font-medium uppercase tracking-wide text-gray-500">Tanggal Masuk</div>
                                        <div class="mt-0.5 text-sm font-semibold text-gray-900">{{ $service_order->tanggal_masuk?->format('d M Y') ?? '—' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($service_order->keluhan)
                    <div class="px-5 pb-5">
                        <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Keluhan</label>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700">{{ $service_order->keluhan }}</div>
                    </div>
                    @endif
                </div>

                <div class="{{ $woPanelClass }}" @if($useStyledShell) style="--ss-delay: 120ms; --ss-stagger: 1" @endif>
                    <div class="flex flex-col gap-3 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-[20px] font-semibold text-gray-900">Jasa Servis</h2>
                            <p class="mt-1 text-[13px] text-gray-500">
                                @if($diskonVerifikasiManagerPage)
                                    Rincian jasa dan diskon yang diajukan (hanya baca).
                                @elseif($isWipContext)
                                    Tambah/hapus jasa servis untuk work order ini.
                                @else
                                    Diskon per item. Lebih dari 10% memerlukan verifikasi Manager.
                                @endif
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span @class([
                                'inline-flex shrink-0 items-center px-3 py-1 text-xs font-semibold',
                                'wo-badge' => $useStyledShell,
                                'rounded-lg bg-gray-100 text-gray-600' => ! $useStyledShell,
                            ])>{{ $service_order->jasaServis->count() }} jasa</span>
                            @unless($diskonVerifikasiManagerPage)
                            <button type="button" id="btn-toggle-add-jasa" class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-100">
                                + Tambah Jasa Service
                            </button>
                            @endunless
                        </div>
                    </div>
                    @unless($diskonVerifikasiManagerPage)
                    <div class="border-b border-gray-100 px-5 py-4">
                        <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Tambahan Jasa Servis</label>
                        <p class="text-xs text-gray-400">Item tambahan akan langsung muncul di tabel Jasa Servis di bawah.</p>
                    </div>
                    @endunless
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="bg-[#f7f3f4]">
                                    <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No.</th>
                                    <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Nama Jasa</th>
                                    <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-center">Qty</th>
                                    @if($isWipContext)
                                        <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-right">Biaya Satuan</th>
                                        <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-right">Subtotal</th>
                                    @else
                                        <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-right">Harga Jasa</th>
                                        <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Jenis Diskon</th>
                                        <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-right">Nilai Diskon</th>
                                        <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-right">Total Diskon</th>
                                        <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Catatan Diskon</th>
                                    @endif
                                    @unless($diskonVerifikasiManagerPage)
                                    <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-center">Hapus</th>
                                    @endunless
                                </tr>
                            </thead>
                            <tbody id="jasa-table-body" class="divide-y divide-gray-100">
                                @foreach($service_order->jasaServis as $i => $j)
                                @php
                                    $qty = (int) ($j->pivot->qty ?? 1);
                                    $biaya = (int) ($j->pivot->qty ?? 1) * (int) $j->pivot->biaya;
                                    $maxDiskon = (int) floor($biaya * 0.10);
                                    $diskonSaved = (int) ($j->pivot->diskon ?? 0);
                                    $catatanDiskonSaved = (string) ($j->pivot->catatan_diskon ?? '');
                                @endphp
                                <tr class="diskon-row-jasa transition hover:bg-[#fcfafb]" data-biaya="{{ $biaya }}" data-jasa-id="{{ $j->id }}" data-harga-satuan="{{ (int) $j->pivot->biaya }}">
                                    <td class="px-5 py-4 text-gray-500">{{ $i + 1 }}</td>
                                    <td class="px-5 py-4 font-semibold text-gray-900">{{ $j->nama }}</td>
                                    <td class="px-5 py-4 text-center text-gray-700" data-role="qty">
                                        <input
                                            type="number"
                                            min="1"
                                            step="1"
                                            name="jasa[{{ $j->id }}][qty]"
                                            value="{{ $qty }}"
                                            class="qty-jasa w-20 rounded-xl border border-gray-200 bg-white px-2 py-1.5 text-center text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40"
                                        >
                                    </td>
                                    @if($isWipContext)
                                        @php $biayaSatuan = (int) ($j->pivot->biaya ?? 0); @endphp
                                        <td class="px-5 py-4 text-right text-gray-700 tabular-nums">Rp {{ number_format($biayaSatuan, 0, ',', '.') }}</td>
                                        <td class="px-5 py-4 text-right text-gray-700 tabular-nums" data-role="harga-total">Rp {{ number_format($biaya, 0, ',', '.') }}</td>
                                        <input type="hidden" name="jasa[{{ $j->id }}][diskon]" class="diskon-hidden-jasa" value="{{ $diskonSaved }}" data-id="j-{{ $j->id }}" data-biaya="{{ $biaya }}">
                                    @else
                                        <td class="px-5 py-4 text-right text-gray-700" data-role="harga-total">Rp {{ number_format($biaya, 0, ',', '.') }}</td>
                                        <td class="px-5 py-4">
                                            <select class="diskon-tipe-jasa w-20 rounded-xl border border-gray-200 bg-white px-2 py-1.5 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40" data-id="j-{{ $j->id }}">
                                                <option value="rp" @selected($diskonSaved > 0)>Rp</option>
                                                <option value="persen" @selected($diskonSaved <= 0)>%</option>
                                            </select>
                                        </td>
                                        <td class="px-5 py-4 text-right">
                                            <input type="number" class="diskon-value-jasa w-24 rounded-xl border border-gray-200 bg-white px-2 py-1.5 text-right text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40" value="{{ $diskonSaved }}" min="0" data-id="j-{{ $j->id }}" placeholder="0">
                                            <input type="hidden" name="jasa[{{ $j->id }}][diskon]" class="diskon-hidden-jasa" value="{{ $diskonSaved }}" data-id="j-{{ $j->id }}" data-biaya="{{ $biaya }}">
                                        </td>
                                        <td class="px-5 py-4 text-right text-xs text-gray-500 diskon-info-jasa" data-biaya="{{ $biaya }}">Rp {{ number_format($diskonSaved, 0, ',', '.') }}</td>
                                        <td class="px-5 py-4">
                                            @if($diskonVerifikasiManagerPage)
                                                <span class="text-xs text-gray-600">{{ $catatanDiskonSaved !== '' ? $catatanDiskonSaved : '-' }}</span>
                                            @else
                                                <input type="text"
                                                       name="jasa[{{ $j->id }}][catatan_diskon]"
                                                       value="{{ $catatanDiskonSaved }}"
                                                       maxlength="500"
                                                       placeholder="Wajib jika ada diskon"
                                                       class="catatan-diskon-jasa w-full min-w-[160px] rounded-xl border border-gray-200 bg-white px-2 py-1.5 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40">
                                            @endif
                                        </td>
                                    @endif
                                    @unless($diskonVerifikasiManagerPage)
                                    <td class="px-5 py-4 text-center">
                                        <button type="button" class="btn-remove-jasa inline-flex items-center rounded-lg border border-red-200 bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100" data-id="{{ $j->id }}">Hapus</button>
                                    </td>
                                    @endunless
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="{{ $woPanelClass }}" @if($useStyledShell) style="--ss-delay: 120ms; --ss-stagger: 2" @endif>
                    <div class="flex flex-col gap-3 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-[20px] font-semibold text-gray-900">Sparepart</h2>
                            <p class="mt-1 text-[13px] text-gray-500">
                                @if($diskonVerifikasiManagerPage)
                                    Rincian sparepart dan diskon yang diajukan (hanya baca).
                                @elseif($isWipContext)
                                    Tambah/hapus sparepart untuk work order ini.
                                @else
                                    Diskon per item. Lebih dari 10% memerlukan verifikasi Manager.
                                @endif
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span @class([
                                'inline-flex shrink-0 items-center px-3 py-1 text-xs font-semibold',
                                'wo-badge' => $useStyledShell,
                                'rounded-lg bg-gray-100 text-gray-600' => ! $useStyledShell,
                            ])>{{ $service_order->products->count() }} item</span>
                            @unless($diskonVerifikasiManagerPage)
                            <button type="button" id="btn-add-sparepart-row" class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-100">
                                + Tambah Sparepart
                            </button>
                            @endunless
                        </div>
                    </div>
                    @unless($diskonVerifikasiManagerPage)
                    <div class="border-b border-gray-100 px-5 py-4">
                        <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Daftar Tambahan Sparepart</label>
                        <p class="text-xs text-gray-400">Item tambahan akan langsung muncul di tabel Sparepart di bawah.</p>
                    </div>
                    @endunless
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="bg-[#f7f3f4]">
                                    <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No.</th>
                                    <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Kode</th>
                                    <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Nama</th>
                                    <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-center">Qty</th>
                                    @if($isWipContext)
                                        <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-right">Harga</th>
                                        <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-right">Subtotal</th>
                                        @unless($diskonVerifikasiManagerPage)
                                        <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-center">Hapus</th>
                                        @endunless
                                    @else
                                        <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-right">Subtotal</th>
                                        <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Jenis Diskon</th>
                                        <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-right">Nilai Diskon</th>
                                        <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-right">Total Diskon</th>
                                        <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Catatan Diskon</th>
                                        @unless($diskonVerifikasiManagerPage)
                                        <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-center">Hapus</th>
                                        @endunless
                                    @endif
                                </tr>
                            </thead>
                            <tbody id="sparepart-table-body" class="divide-y divide-gray-100">
                                @foreach($service_order->products as $i => $p)
                                @php
                                    $subtotal = $p->pivot->qty * $p->pivot->harga;
                                    $maxDiskon = (int) floor($subtotal * 0.10);
                                    $diskonSaved = (int) ($p->pivot->diskon ?? 0);
                                    $catatanDiskonSaved = (string) ($p->pivot->catatan_diskon ?? '');
                                @endphp
                                <tr class="diskon-row-sparepart transition hover:bg-[#fcfafb]" data-subtotal="{{ $subtotal }}" data-product-id="{{ $p->id }}" data-harga-satuan="{{ (int) $p->pivot->harga }}">
                                    <td class="px-5 py-4 text-gray-500">{{ $i + 1 }}</td>
                                    <td class="px-5 py-4 font-semibold text-gray-900">{{ $p->kode_produk }}</td>
                                    <td class="px-5 py-4 text-gray-700">{{ $p->nama_produk }}</td>
                                    <td class="px-5 py-4 text-center" data-role="qty">
                                        <input
                                            type="number"
                                            min="1"
                                            step="1"
                                            name="sparepart[{{ $p->id }}][qty]"
                                            value="{{ (int) $p->pivot->qty }}"
                                            class="qty-sparepart w-20 rounded-xl border border-gray-200 bg-white px-2 py-1.5 text-center text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40"
                                        >
                                    </td>
                                    @if($isWipContext)
                                        <td class="px-5 py-4 text-right text-gray-700 tabular-nums">Rp {{ number_format((int) $p->pivot->harga, 0, ',', '.') }}</td>
                                        <td class="px-5 py-4 text-right text-gray-700 tabular-nums" data-role="subtotal">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                                        <input type="hidden" name="sparepart[{{ $p->id }}][diskon]" class="diskon-hidden-sparepart" value="{{ $diskonSaved }}" data-id="p-{{ $p->id }}" data-subtotal="{{ $subtotal }}">
                                        @unless($diskonVerifikasiManagerPage)
                                        <td class="px-5 py-4 text-center">
                                            <button type="button" class="btn-remove-sparepart inline-flex items-center rounded-lg border border-red-200 bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100" data-id="{{ $p->id }}">Hapus</button>
                                        </td>
                                        @endunless
                                    @else
                                        <td class="px-5 py-4 text-right text-gray-700" data-role="subtotal">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                                        <td class="px-5 py-4">
                                            <select class="diskon-tipe-sparepart w-20 rounded-xl border border-gray-200 bg-white px-2 py-1.5 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40" data-id="p-{{ $p->id }}">
                                                <option value="rp" @selected($diskonSaved > 0)>Rp</option>
                                                <option value="persen" @selected($diskonSaved <= 0)>%</option>
                                            </select>
                                        </td>
                                        <td class="px-5 py-4 text-right">
                                            <input type="number" class="diskon-value-sparepart w-24 rounded-xl border border-gray-200 bg-white px-2 py-1.5 text-right text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40" value="{{ $diskonSaved }}" min="0" data-id="p-{{ $p->id }}" placeholder="0">
                                            <input type="hidden" name="sparepart[{{ $p->id }}][diskon]" class="diskon-hidden-sparepart" value="{{ $diskonSaved }}" data-id="p-{{ $p->id }}" data-subtotal="{{ $subtotal }}">
                                        </td>
                                        <td class="px-5 py-4 text-right text-xs text-gray-500 diskon-info-sparepart" data-subtotal="{{ $subtotal }}">Rp {{ number_format($diskonSaved, 0, ',', '.') }}</td>
                                        <td class="px-5 py-4">
                                            @if($diskonVerifikasiManagerPage)
                                                <span class="text-xs text-gray-600">{{ $catatanDiskonSaved !== '' ? $catatanDiskonSaved : '-' }}</span>
                                            @else
                                                <input type="text"
                                                       name="sparepart[{{ $p->id }}][catatan_diskon]"
                                                       value="{{ $catatanDiskonSaved }}"
                                                       maxlength="500"
                                                       placeholder="Wajib jika ada diskon"
                                                       class="catatan-diskon-sparepart w-full min-w-[160px] rounded-xl border border-gray-200 bg-white px-2 py-1.5 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40">
                                            @endif
                                        </td>
                                        @unless($diskonVerifikasiManagerPage)
                                        <td class="px-5 py-4 text-center">
                                            <button type="button" class="btn-remove-sparepart inline-flex items-center rounded-lg border border-red-200 bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100" data-id="{{ $p->id }}">Hapus</button>
                                        </td>
                                        @endunless
                                    @endif
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="@if($diskonVerifikasiManagerPage) space-y-5 @else space-y-4 @endif lg:col-span-4">
                @if($diskonVerifikasiManagerPage)
                    @include('speedshop.verifikasi.diskon-work-order.partials.ringkasan')
                @else
                <div class="panel-card panel-card-flush summary-card ss-dash-enter-child" @if($useStyledShell) style="--ss-delay: 120ms; --ss-stagger: 3" @endif>
                    <div class="section-hd">
                        <h2>Ringkasan</h2>
                        <p>{{ $isWipContext ? 'Estimasi biaya work order' : 'Rincian biaya transaksi' }}</p>
                    </div>
                    <div class="mt-2 mb-2">
                        <div class="summary-row">
                            <span>Total Jasa</span>
                            <span x-text="'Rp ' + formatNumber(totalJasa)"></span>
                        </div>
                        <div class="summary-row">
                            <span>Total Sparepart</span>
                            <span x-text="'Rp ' + formatNumber(totalSparepart)"></span>
                        </div>
                        <div class="summary-row">
                            <span>Biaya Charge</span>
                            <span x-text="'Rp ' + formatNumber(chargeAmount())"></span>
                        </div>
                        <div class="summary-row">
                            <span>PPN</span>
                            <span x-text="'Rp ' + formatNumber(ppnAmount())"></span>
                        </div>
                        <div class="summary-total">
                            <span>{{ $isWipContext ? 'Estimasi Total' : 'Total Akhir' }}</span>
                            <span x-text="'Rp ' + formatNumber(totalAkhirFinal())"></span>
                        </div>
                    </div>
                    <div class="border-t border-gray-100 px-5 py-4">
                        @if($showBatalFlowSteps)
                        <label class="mb-3 block text-[11px] font-semibold uppercase tracking-wider text-gray-500">Alur Pembatalan</label>
                        @php
                            $tz = config('app.timezone');
                            $steps = [
                                ['label' => 'Diajukan'],
                                ['label' => 'Menunggu Verifikasi Accounting'],
                                ['label' => 'Dibatalkan'],
                            ];
                            $currentStep = $isPendingBatal ? 1 : ($isBatalApproved ? count($steps) : 0);
                        @endphp
                        @else
                        <label class="mb-3 block text-[11px] font-semibold uppercase tracking-wider text-gray-500">Alur Pengerjaan</label>
                        @php
                            $steps = [
                                ['label' => 'Diajukan'],
                                ['label' => 'Antrian'],
                                ['label' => 'Dikerjakan'],
                                ['label' => 'Selesai'],
                            ];
                            $currentStep = match($service_order->status) {
                                'antri' => 1,
                                'proses' => 2,
                                'selesai' => 4,
                                default => 1,
                            };
                        @endphp
                        @endif
                        <div class="flex items-start justify-between gap-1">
                            @foreach($steps as $idx => $step)
                            @php
                                $isStepDone = $idx < $currentStep;
                                $isStepCurrent = $idx === $currentStep && ! $isStepDone;
                            @endphp
                            <div class="flex flex-col items-center" style="width: {{ 100 / count($steps) }}%">
                                <div class="relative flex w-full items-center justify-center">
                                    @if($idx > 0)
                                    <div class="absolute right-1/2 h-0.5 w-full {{ $isStepDone ? 'bg-green-400' : 'bg-gray-200' }}"></div>
                                    @endif
                                    @if($idx < count($steps) - 1)
                                    <div class="absolute left-1/2 h-0.5 w-full {{ ($idx + 1) < $currentStep ? 'bg-green-400' : 'bg-gray-200' }}"></div>
                                    @endif
                                    @if($isStepDone)
                                        <div class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full bg-green-500 shadow-sm shadow-green-200">
                                            <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        </div>
                                    @elseif($isStepCurrent)
                                        <div class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full border-2 border-amber-400 bg-amber-50">
                                            <div class="h-2.5 w-2.5 animate-pulse rounded-full bg-amber-400"></div>
                                        </div>
                                    @else
                                        <div class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full bg-gray-200">
                                            <span class="text-xs text-gray-400">{{ $idx + 1 }}</span>
                                        </div>
                                    @endif
                                </div>
                                <p class="mt-2 text-center text-[10px] font-medium leading-tight sm:text-xs {{ $isStepDone ? 'text-green-600' : ($isStepCurrent ? 'text-amber-600' : 'text-gray-400') }}">{{ $step['label'] }}</p>
                            </div>
                            @endforeach
                        </div>
                        @if($showBatalFlowSteps)
                            @if($isPendingBatal)
                            <p class="mt-3 text-center text-xs text-gray-500">Pengerjaan WO ditahan sampai Accounting menyetujui atau menolak pembatalan.</p>
                            @elseif($isBatalApproved)
                            <p class="mt-3 text-center text-xs text-gray-500">
                                @if($service_order->relationLoaded('batalVerifikasiVerifiedBy') && $service_order->batalVerifikasiVerifiedBy)
                                    Dibatalkan · disetujui {{ $service_order->batalVerifikasiVerifiedBy->name }}
                                    @if($service_order->batal_verifikasi_verified_at)
                                        · {{ $service_order->batal_verifikasi_verified_at->timezone($tz)->format('d M Y H:i') }}
                                    @endif
                                @else
                                    Work order telah dibatalkan.
                                @endif
                            </p>
                            @endif
                        @endif
                        @if($isBatalRejected && $isWipContext)
                        <p class="mt-3 text-center text-xs text-rose-600">Pengajuan pembatalan ditolak — alur pengerjaan WO dilanjutkan.</p>
                        @endif
                    </div>

                    <div class="space-y-3 border-t border-gray-100 px-5 py-4">
                        @unless($diskonVerifikasiManagerPage)
                        @unless($isWipContext)
                            <input type="hidden" name="pembayaran_mode" :value="pembayaranMode">
                            <div>
                                <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Pembayaran <span class="text-red-500">*</span></label>
                                <div class="flex rounded-xl border border-gray-200 bg-gray-50 p-0.5">
                                    <button type="button" @click="setPembayaranMode('single')"
                                            :class="pembayaranMode === 'single' ? 'flex-1 rounded-lg bg-white px-3 py-2 text-xs font-semibold text-gray-900 shadow-sm' : 'flex-1 rounded-lg px-3 py-2 text-xs font-medium text-gray-500 transition hover:text-gray-700'">
                                        Tunggal
                                    </button>
                                    <button type="button" @click="setPembayaranMode('split')"
                                            :class="pembayaranMode === 'split' ? 'flex-1 rounded-lg bg-white px-3 py-2 text-xs font-semibold text-gray-900 shadow-sm' : 'flex-1 rounded-lg px-3 py-2 text-xs font-medium text-gray-500 transition hover:text-gray-700'">
                                        Split bill
                                    </button>
                                </div>
                            </div>
                            <div x-show="pembayaranMode === 'single'" x-cloak class="space-y-1">
                                <label class="mb-1 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Jenis pembayaran</label>
                                <select name="jenis_pembayaran" :required="pembayaranMode === 'single'" :disabled="pembayaranMode !== 'single'"
                                        class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40 disabled:bg-gray-100">
                                    <option value="">-- Pilih --</option>
                                    <option value="cash" @selected(old('jenis_pembayaran', $service_order->jenis_pembayaran) === 'cash')>Tunai</option>
                                    <option value="transfer" @selected(old('jenis_pembayaran', $service_order->jenis_pembayaran) === 'transfer')>Transfer</option>
                                    <option value="qris" @selected(old('jenis_pembayaran', $service_order->jenis_pembayaran) === 'qris')>Qris</option>
                                    <option value="edc" @selected(old('jenis_pembayaran', $service_order->jenis_pembayaran) === 'edc')>EDC</option>
                                </select>
                                @error('jenis_pembayaran')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div x-show="pembayaranMode === 'split'" x-cloak class="space-y-2">
                                <template x-for="(row, idx) in splitRows" :key="idx">
                                    <div class="flex flex-wrap items-end gap-2">
                                        <div class="min-w-[120px] flex-1">
                                            <label class="mb-1 block text-[10px] font-medium uppercase tracking-wide text-gray-500">Metode</label>
                                            <select class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40"
                                                    x-model="row.metode"
                                                    :disabled="pembayaranMode !== 'split'"
                                                    :name="'pembayaran[' + idx + '][metode]'">
                                                <option value="cash">Tunai</option>
                                                <option value="transfer">Transfer</option>
                                                <option value="qris">QRIS</option>
                                                <option value="edc">EDC</option>
                                            </select>
                                        </div>
                                        <div class="min-w-[100px] flex-1">
                                            <label class="mb-1 block text-[10px] font-medium uppercase tracking-wide text-gray-500">Nominal (Rp)</label>
                                            <input type="text" inputmode="numeric" autocomplete="off"
                                                   class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm tabular-nums focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40"
                                                   :disabled="pembayaranMode !== 'split'"
                                                   x-model="row.jumlahDisplay"
                                                   @input="
                                                        const raw = (row.jumlahDisplay || '').toString();
                                                        const digits = raw.replace(/\D/g, '');
                                                        row.jumlah = digits ? parseInt(digits, 10) : 0;
                                                        row.jumlahDisplay = formatNumber(row.jumlah);
                                                   "
                                                   placeholder="0">
                                            <input type="hidden" :disabled="pembayaranMode !== 'split'"
                                                   :name="'pembayaran[' + idx + '][jumlah]'"
                                                   :value="row.jumlah">
                                        </div>
                                        <button type="button" @click="removeSplitRow(idx)" :disabled="splitRows.length <= 2"
                                                class="mb-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:bg-gray-50 disabled:opacity-40"
                                                title="Hapus baris">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                </template>
                                <div class="flex justify-end">
                                    <button type="button" @click="addSplitRow()" class="inline-flex items-center gap-1 rounded-lg border border-dashed border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-600 transition hover:border-gray-400 hover:bg-gray-50">
                                        + Tambah metode
                                    </button>
                                </div>
                                <div class="rounded-xl border px-3 py-2 text-xs" :class="splitPaymentOk ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-amber-200 bg-amber-50 text-amber-900'">
                                    <div class="flex justify-between gap-2">
                                        <span>Total dibayar</span>
                                        <span class="font-semibold tabular-nums" x-text="'Rp ' + formatNumber(splitTotalPaid)"></span>
                                    </div>
                                    <div class="mt-1 flex justify-between gap-2">
                                        <span>Total transaksi</span>
                                        <span class="font-semibold tabular-nums" x-text="'Rp ' + formatNumber(totalAkhirFinal())"></span>
                                    </div>
                                    <p class="mt-1.5 font-medium" x-show="!splitPaymentOk && pembayaranMode === 'split'" x-cloak>Selisih harus Rp 0 agar bisa diselesaikan.</p>
                                </div>
                                @error('pembayaran')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        @endunless
                        @endunless
                        @if(($service_order->diskon_verifikasi_status ?? null) === 'rejected' && !empty($service_order->diskon_verifikasi_rejected_reason) && ! $diskonVerifikasiManagerPage)
                            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3">
                                <p class="text-sm font-semibold text-rose-900">Diskon ditolak Manager</p>
                                <p class="mt-1 text-xs text-rose-800/90">{{ $service_order->diskon_verifikasi_rejected_reason }}</p>
                            </div>
                        @endif
                        @if(!$diskonVerifikasiManagerPage)
                        <div x-show="needManagerVerify && !isDiskonVerifier" x-cloak class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
                            <p class="text-sm font-medium text-amber-900">Ada diskon lebih dari 10%. Transaksi harus menunggu verifikasi Manager Speedshop.</p>
                        </div>
                        @endif
                        @if($isPendingVerify)
                            @if($isDiskonVerifier)
                                <button type="button" @click="approveDiskon()" :disabled="loading"
                                        class="flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/25 transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50">
                                    <svg x-show="!loading" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    <svg x-show="loading" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                    Approve — Setujui
                                </button>
                                <button type="button" @click="rejectDiskon()" :disabled="loading"
                                        class="flex h-11 w-full items-center justify-center gap-2 rounded-xl border border-gray-200 px-5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 disabled:opacity-50">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    Reject — Tolak
                                </button>
                                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
                                    <p class="text-sm font-semibold text-amber-900">Menunggu verifikasi</p>
                                    <p class="mt-1 text-xs text-amber-800/90">Periksa diskon yang diajukan, lalu approve atau reject.</p>
                                </div>
                                @if($diskonVerifikasiManagerPage)
                                <a href="{{ $diskonVerifikasiIndexUrl }}"
                                   class="flex h-11 w-full items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                                    Kembali ke Daftar Verifikasi
                                </a>
                                @endif
                            @else
                                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
                                    <p class="text-sm font-semibold text-amber-900">Menunggu verifikasi Manager</p>
                                    <p class="mt-1 text-xs text-amber-800/90">Work order ini belum selesai sampai Manager melakukan approve atau reject.</p>
                                    @if(!empty($service_order->diskon_verifikasi_rejected_reason))
                                        <p class="mt-2 text-xs text-amber-900"><span class="font-semibold">Alasan reject terakhir:</span> {{ $service_order->diskon_verifikasi_rejected_reason }}</p>
                                    @endif
                                </div>
                            @endif
                        @elseif($diskonVerifikasiManagerPage && ! $isPendingVerify)
                            <a href="{{ $diskonVerifikasiIndexUrl }}"
                               class="flex h-11 w-full items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                                Kembali ke Daftar Verifikasi
                            </a>
                            @if($diskonVerifikasiStatus === 'approved')
                                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                                    <p class="font-semibold">Diskon disetujui</p>
                                    <p class="mt-1 text-xs text-emerald-800/90">Work order telah diselesaikan setelah verifikasi diskon.</p>
                                </div>
                            @elseif($diskonVerifikasiStatus === 'rejected')
                                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900">
                                    <p class="font-semibold">Diskon ditolak</p>
                                    @if(!empty($service_order->diskon_verifikasi_rejected_reason))
                                        <p class="mt-1 text-xs text-rose-800/90">{{ $service_order->diskon_verifikasi_rejected_reason }}</p>
                                    @endif
                                </div>
                            @endif
                        @elseif($isPendingBatal && $isWipContext)
                            {{-- Form aksi disembunyikan saat menunggu verifikasi pembatalan Accounting --}}
                        @else
                            @if($isWipContext && $service_order->status === 'antri')
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold text-gray-600">Mekanik *</label>
                                    <select name="mekanik_id" id="mekanik-select-inline" class="select2-mekanik w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40" @disabled(empty($mekaniks ?? null) || ($mekaniks ?? collect())->isEmpty())>
                                        <option value="">- Pilih Mekanik -</option>
                                        @foreach(($mekaniks ?? collect()) as $m)
                                            <option value="{{ $m->id }}">{{ $m->nama }}{{ $m->spesialisasi ? " ({$m->spesialisasi})" : '' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <input type="hidden" name="print_sheet" value="1">
                                <button
                                    id="btn-cetak-wo"
                                    type="submit"
                                    :disabled="submitting || jasaCount < 1 || {{ empty($mekaniks ?? null) || ($mekaniks ?? collect())->isEmpty() ? 'true' : 'false' }}"
                                    class="mt-3 flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-brand px-5 text-sm font-semibold text-brand-ink shadow-lg shadow-brand/35 transition hover:bg-brand-hover disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <span x-text="submitting ? 'Memproses...' : 'Cetak Work Order'"></span>
                                </button>
                                <p class="mt-2 text-center text-xs text-gray-500">Pilih mekanik, lalu cetak. Status akan berubah ke Proses.</p>
                                @if($canRequestBatal)
                                <button
                                    type="button"
                                    @click="requestCancelWorkOrder()"
                                    :disabled="cancelLoading || submitting"
                                    class="mt-3 flex h-11 w-full items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <span x-text="cancelLoading ? 'Mengajukan...' : 'Batalkan Work Order'"></span>
                                </button>
                                @endif
                            @else
                                <div class="flex flex-col gap-2 sm:flex-row sm:gap-3">
                                    <button
                                        type="button"
                                        @click="panggilMonitorAntrian()"
                                        :disabled="panggilLoading || submitting"
                                        class="flex h-11 flex-1 items-center justify-center gap-2 rounded-xl border-2 border-red-500 bg-white px-5 text-sm font-semibold text-red-600 shadow-sm transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        <svg x-show="!panggilLoading" class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V18.118a1 1 0 001.447.894l4.853-2.647V8.235L12.447 4.106A1 1 0 0011 5.882z"/><path stroke-linecap="round" stroke-linejoin="round" d="M5.5 10.5v3a1 1 0 001.106.895L9 15.118V8.882L6.606 7.605A1 1 0 005.5 8.5v2z"/></svg>
                                        <svg x-show="panggilLoading" class="h-4 w-4 shrink-0 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                        <span x-text="panggilLoading ? 'Mengirim...' : 'Panggil'"></span>
                                    </button>
                                    <button type="submit" :disabled="submitting || (!isWipPage && pembayaranMode === 'split' && !splitPaymentOk)"
                                            class="flex h-11 flex-1 items-center justify-center gap-2 rounded-xl bg-brand px-5 text-sm font-semibold text-brand-ink shadow-lg shadow-brand/35 transition hover:bg-brand-hover disabled:cursor-not-allowed disabled:opacity-50">
                                        <span x-text="submitting ? 'Menyimpan...' : (needManagerVerify ? 'Ajukan Verifikasi' : 'Selesaikan Transaksi')"></span>
                                    </button>
                                </div>
                                @if($canReprintWorkOrder)
                                <a href="{{ $workOrderSheetUrl }}" target="_blank" rel="noopener noreferrer"
                                   class="mt-3 flex h-11 w-full items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                    Cetak Work Order
                                </a>
                                <p class="mt-1.5 text-center text-xs text-gray-500">Buka ulang lembar work order untuk dicetak.</p>
                                @endif
                                @if($isWipContext && $service_order->status === 'proses' && $canRequestBatal)
                                <button
                                    type="button"
                                    @click="requestCancelWorkOrder()"
                                    :disabled="cancelLoading || submitting"
                                    class="mt-3 flex h-11 w-full items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <span x-text="cancelLoading ? 'Mengajukan...' : 'Batalkan Work Order'"></span>
                                </button>
                                @endif
                            @endif
                        @endif
                        @if($isPendingBatal && $isAccounting && $isWipContext)
                        <div class="mt-4 border-t border-gray-100 pt-4">
                            <a href="{{ route('speedshop.verifikasi.batal-work-order.show', $service_order) }}"
                               class="flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-brand px-5 text-sm font-semibold text-brand-ink shadow-lg shadow-brand/35 transition hover:bg-brand-hover">
                                Buka halaman verifikasi
                            </a>
                        </div>
                        @endif
                    </div>
                </div>
                @endif
            </div>
        </div>
    </form>
</div>

    <div x-show="showAddJasaModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="closeAddJasaModal()">
        <div x-show="showAddJasaModal" x-transition.opacity class="fixed inset-0 bg-black/50" @click="closeAddJasaModal()"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div x-show="showAddJasaModal" x-transition class="relative w-full max-w-lg rounded-2xl border border-gray-200 bg-white p-6 shadow-xl" @click.stop>
                <h3 class="text-lg font-bold text-gray-900">Tambah Jasa Servis</h3>
                <p class="mt-1 text-sm text-gray-500">Pilih 1 jasa servis untuk ditambahkan.</p>
                <div class="mt-4">
                    <select id="modal-add-jasa-select" class="w-full rounded-xl border border-gray-200 bg-white text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40">
                        <option value="">Pilih jasa servis...</option>
                        @foreach($jasaServisOptions as $opt)
                            <option value="{{ $opt->id }}">{{ $opt->nama }} - Rp {{ number_format((int) $opt->biaya, 0, ',', '.') }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mt-3">
                    <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-gray-500">Qty</label>
                    <input id="modal-add-jasa-qty" type="number" min="1" value="1" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40">
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" @click="closeAddJasaModal()" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Batal</button>
                    <button type="button" @click="confirmAddJasa()" class="rounded-xl bg-brand px-4 py-2 text-sm font-semibold text-brand-ink hover:bg-brand-hover">Tambahkan</button>
                </div>
            </div>
        </div>
    </div>

    <div x-show="showAddSparepartModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="closeAddSparepartModal()">
        <div x-show="showAddSparepartModal" x-transition.opacity class="fixed inset-0 bg-black/50" @click="closeAddSparepartModal()"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div x-show="showAddSparepartModal" x-transition class="relative w-full max-w-lg rounded-2xl border border-gray-200 bg-white p-6 shadow-xl" @click.stop>
                <h3 class="text-lg font-bold text-gray-900">Tambah Sparepart</h3>
                <p class="mt-1 text-sm text-gray-500">Pilih sparepart dan qty untuk ditambahkan.</p>
                <div class="mt-4 space-y-3">
                    <div>
                        <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-gray-500">Sparepart</label>
                        <select id="modal-add-sparepart-select" class="w-full rounded-xl border border-gray-200 bg-white text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40"></select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-gray-500">Qty</label>
                        <input id="modal-add-sparepart-qty" type="number" min="1" value="1" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40">
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" @click="closeAddSparepartModal()" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Batal</button>
                    <button type="button" @click="confirmAddSparepart()" class="rounded-xl bg-brand px-4 py-2 text-sm font-semibold text-brand-ink hover:bg-brand-hover">Tambahkan</button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
@if($isWipContext)
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<style>
/* Select2 styling: samakan feel dengan TomSelect (rounded-xl, border, padding, focus ring merah) */
.select2-container { width: 100% !important; }
.select2-container--default .select2-selection--single {
  height: 44px;
  border: 1px solid #e5e7eb; /* gray-200 */
  border-radius: 12px;      /* rounded-xl */
  background: #fff;
  display: flex;
  align-items: center;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
  padding-left: 12px;
  padding-right: 36px;
  line-height: 1.25rem;
  color: #111827; /* gray-900 */
  font-size: 14px;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
  height: 44px;
  right: 10px;
}
/* Sembunyikan tombol clear (icon X) */
.select2-container--default .select2-selection--single .select2-selection__clear { display: none; }
.select2-container--default.select2-container--focus .select2-selection--single {
  border-color: #f87171; /* red-400 */
  box-shadow: 0 0 0 4px rgba(248, 113, 113, 0.20); /* ring-red-400/20 */
}
.select2-dropdown {
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  overflow: hidden;
}
.select2-container--default .select2-search--dropdown .select2-search__field {
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  padding: 10px 12px;
  font-size: 14px;
}
.select2-container--default .select2-results__option {
  font-size: 14px;
  padding: 10px 12px;
}
.select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
  background: #fee2e2; /* red-100 */
  color: #111827;
}
</style>
@endif
@if(session('open_work_order_a4_pdf_url'))
<script>
document.addEventListener('DOMContentLoaded', function () {
    var url = @json(session('open_work_order_a4_pdf_url'));
    if (url) {
        window.open(url, '_blank', 'noopener');
    }
});
</script>
@endif

@if($isWipContext)
<script>
(function () {
    function toggleCetakButton() {
        const sel = document.getElementById('mekanik-select-inline');
        const btn = document.getElementById('btn-cetak-wo');
        if (!sel || !btn) return;
        const has = String(sel.value || '').trim() !== '';
        // jangan override disabled permanen dari blade ketika mekanik list kosong
        if (btn.hasAttribute('data-force-disabled')) return;
        btn.disabled = !has;
    }

    function initMekanikSelect2() {
        const el = document.getElementById('mekanik-select-inline');
        if (!el) return false;
        if (typeof window.jQuery === 'undefined') return false;
        if (typeof window.$ === 'undefined') window.$ = window.jQuery;
        if (typeof window.jQuery.fn?.select2 === 'undefined') return false;

        try {
            const $el = window.jQuery(el);
            const alpineRoot = el.closest('[x-data]');
            const syncToAlpine = () => {
                try {
                    if (alpineRoot && alpineRoot.__x && alpineRoot.__x.$data) {
                        alpineRoot.__x.$data.syncMekanik();
                    }
                } catch (e) {}
            };
            if ($el.data('select2')) {
                $el.select2('destroy');
            }
            $el.select2({
                width: '100%',
                placeholder: '- Pilih Mekanik -',
                allowClear: false,
            });

            // Toggle tombol cetak berdasarkan pilihan mekanik.
            $el.off('change._toggleCetak select2:select._toggleCetak select2:clear._toggleCetak');
            $el.on('change._toggleCetak select2:select._toggleCetak select2:clear._toggleCetak', function () {
                toggleCetakButton();
                syncToAlpine();
            });
            toggleCetakButton();
            syncToAlpine();
            return true;
        } catch (e) {
            return false;
        }
    }

    let tries = 0;
    const maxTries = 60; // ~9s
    const timer = setInterval(function () {
        tries += 1;
        const ok = initMekanikSelect2();
        if (ok || tries >= maxTries) clearInterval(timer);
    }, 150);

    document.addEventListener('alpine:init', function () {
        initMekanikSelect2();
        setTimeout(initMekanikSelect2, 150);
        setTimeout(initMekanikSelect2, 500);
    });

    window.addEventListener('load', function () {
        initMekanikSelect2();
        setTimeout(initMekanikSelect2, 500);
        setTimeout(initMekanikSelect2, 1500);
    });

    // Initial state (sebelum select2 siap)
    toggleCetakButton();
    const rawSel = document.getElementById('mekanik-select-inline');
    if (rawSel) {
        rawSel.addEventListener('change', toggleCetakButton);
    }
})();
</script>
@endif
<script>
function selesaikanTransaksiShow() {
    const isDiskonVerifier = {{ $isDiskonVerifier ? 'true' : 'false' }};
    const isPendingVerify = {{ $isPendingVerify ? 'true' : 'false' }};
    const initialTotalJasa = {{ $service_order->jasaServis->sum(fn($j) => ((int)($j->pivot->qty ?? 1) * (int)$j->pivot->biaya)) }};
    const initialTotalSparepart = {{ $service_order->products->sum(fn($p) => $p->pivot->qty * $p->pivot->harga) }};
    let jasaOptions = @json($jasaOptionsForJs);
    const sparepartOptions = @json($productsForForm);
    const panggilAntrianUrl = @json(route('speedshop.selesaikan-transaksi.panggil-antrian', $service_order));

    function jasaById(jasaId) {
        return jasaOptions.find((x) => String(x.id) === String(jasaId));
    }

    function stockForProduct(productId) {
        const p = sparepartOptions.find((x) => String(x.id) === String(productId));
        return p ? (Number(p.jumlah || 0) || 0) : 0;
    }

    function computeBaseTotalsFromDom() {
        const jasaRows = Array.from(document.querySelectorAll('#jasa-table-body tr'));
        const spareRows = Array.from(document.querySelectorAll('#sparepart-table-body tr'));

        let jasaTotal = 0;
        document.querySelectorAll('input.diskon-hidden-jasa').forEach((inp) => {
            jasaTotal += (parseInt(inp.dataset.biaya || '0', 10) || 0);
        });

        let spareTotal = 0;
        document.querySelectorAll('input.diskon-hidden-sparepart').forEach((inp) => {
            spareTotal += (parseInt(inp.dataset.subtotal || '0', 10) || 0);
        });

        let spareQty = 0;
        spareRows.forEach((row) => {
            const qtyInp = row.querySelector('input.qty-sparepart, input.pending-qty-sparepart');
            spareQty += qtyInp ? (parseInt(qtyInp.value || '0', 10) || 0) : 0;
        });

        return {
            jasaCount: jasaRows.length,
            sparepartCount: spareRows.length,
            totalQtySparepart: spareQty,
            totalJasa: jasaTotal,
            totalSparepart: spareTotal,
            totalAwal: jasaTotal + spareTotal,
        };
    }

    function updateDiskonFromInputs() {
        // Penting: row tambahan (pending) tidak punya class .diskon-row-*, jadi proses berbasis hidden input
        // agar mencakup existing + pending.
        document.querySelectorAll('input.diskon-hidden-jasa').forEach((hiddenInp) => {
            const row = hiddenInp.closest('tr');
            if (!row) return;
            const tipeSelect = row.querySelector('.diskon-tipe-jasa');
            const valueInp = row.querySelector('.diskon-value-jasa');
            if (!tipeSelect || !valueInp) return;
            const biaya = parseInt(hiddenInp.dataset.biaya || row.dataset.biaya || '0', 10) || 0;
            const tipe = tipeSelect.value;
            const val = parseFloat(valueInp.value) || 0;
            let diskonRp = 0;
            if (tipe === 'rp') {
                valueInp.max = String(biaya);
                diskonRp = Math.min(Math.max(0, Math.floor(val)), biaya);
                valueInp.value = String(diskonRp);
            } else {
                valueInp.max = '100';
                const pct = Math.min(100, Math.max(0, val));
                diskonRp = Math.floor(biaya * pct / 100);
                valueInp.value = String(pct);
            }
            hiddenInp.value = String(diskonRp);
            const infoEl = row.querySelector('.diskon-info-jasa');
            if (infoEl) {
                infoEl.textContent = `Rp ${Number(diskonRp || 0).toLocaleString('id-ID')}`;
            }
        });
        document.querySelectorAll('input.diskon-hidden-sparepart').forEach((hiddenInp) => {
            const row = hiddenInp.closest('tr');
            if (!row) return;
            const tipeSelect = row.querySelector('.diskon-tipe-sparepart');
            const valueInp = row.querySelector('.diskon-value-sparepart');
            if (!tipeSelect || !valueInp) return;
            const subtotal = parseInt(hiddenInp.dataset.subtotal || row.dataset.subtotal || '0', 10) || 0;
            const tipe = tipeSelect.value;
            const val = parseFloat(valueInp.value) || 0;
            let diskonRp = 0;
            if (tipe === 'rp') {
                valueInp.max = String(subtotal);
                diskonRp = Math.min(Math.max(0, Math.floor(val)), subtotal);
                valueInp.value = String(diskonRp);
            } else {
                valueInp.max = '100';
                const pct = Math.min(100, Math.max(0, val));
                diskonRp = Math.floor(subtotal * pct / 100);
                valueInp.value = String(pct);
            }
            hiddenInp.value = String(diskonRp);
            const infoEl = row.querySelector('.diskon-info-sparepart');
            if (infoEl) {
                infoEl.textContent = `Rp ${Number(diskonRp || 0).toLocaleString('id-ID')}`;
            }
        });
    }

    function bindQtyEditors() {
        document.querySelectorAll('#jasa-table-body tr.diskon-row-jasa').forEach((row) => {
            const qtyInp = row.querySelector('input.qty-jasa');
            if (!qtyInp || qtyInp.dataset.bound) return;
            qtyInp.dataset.bound = '1';
            qtyInp.addEventListener('input', () => {
                const hargaSatuan = Number(row.dataset.hargaSatuan || 0);
                const q = Math.max(1, Math.floor(Number(qtyInp.value || 0) || 0));
                qtyInp.value = String(q);
                const biaya = hargaSatuan * q;
                row.dataset.biaya = String(biaya);
                const hargaCell = row.querySelector('[data-role=\"harga-total\"]');
                if (hargaCell) hargaCell.textContent = 'Rp ' + Number(biaya || 0).toLocaleString('id-ID');
                const hiddenDiskon = row.querySelector('.diskon-hidden-jasa');
                const infoDiskon = row.querySelector('.diskon-info-jasa');
                if (hiddenDiskon) hiddenDiskon.dataset.biaya = String(biaya);
                if (infoDiskon) infoDiskon.dataset.biaya = String(biaya);
                updateDiskonFromInputs();
            });
        });
        document.querySelectorAll('#sparepart-table-body tr.diskon-row-sparepart').forEach((row) => {
            const qtyInp = row.querySelector('input.qty-sparepart');
            if (!qtyInp || qtyInp.dataset.bound) return;
            qtyInp.dataset.bound = '1';
            qtyInp.addEventListener('input', () => {
                const hargaSatuan = Number(row.dataset.hargaSatuan || 0);
                const q = Math.max(1, Math.floor(Number(qtyInp.value || 0) || 0));
                qtyInp.value = String(q);
                const subtotal = hargaSatuan * q;
                row.dataset.subtotal = String(subtotal);
                const subtotalCell = row.querySelector('[data-role=\"subtotal\"]');
                if (subtotalCell) subtotalCell.textContent = 'Rp ' + Number(subtotal || 0).toLocaleString('id-ID');
                const hiddenDiskon = row.querySelector('.diskon-hidden-sparepart');
                const infoDiskon = row.querySelector('.diskon-info-sparepart');
                if (hiddenDiskon) hiddenDiskon.dataset.subtotal = String(subtotal);
                if (infoDiskon) infoDiskon.dataset.subtotal = String(subtotal);
                updateDiskonFromInputs();
            });
        });
    }

    return {
        submitting: false,
        panggilLoading: false,
        cancelLoading: false,
        loading: false,
        diskonBusy: false,
        showRejectForm: false,
        rejectedReason: '',
        rejectError: '',
        isDiskonVerifier,
        isPendingVerify,
        isWipPage: {{ $isWipContext ? 'true' : 'false' }},
        chargeMode: @json((string) ($service_order->invoice?->charge_mode ?? (($invoiceSettings ?? [])['invoice_charge_mode'] ?? 'percent'))),
        chargePercent: @json((float) ($service_order->invoice?->charge_percent ?? (($invoiceSettings ?? [])['invoice_charge_percent'] ?? 0))),
        chargePercentMinimum: @json(\App\Services\InvoiceSettingsService::CHARGE_PERCENT_MINIMUM),
        chargeRupiah: @json((int) ($service_order->invoice?->charge_rupiah ?? (($invoiceSettings ?? [])['invoice_charge_rupiah'] ?? 0))),
        ppnMode: @json((string) ($service_order->invoice?->ppn_mode ?? (($invoiceSettings ?? [])['invoice_ppn_mode'] ?? 'percent'))),
        ppnPercent: @json((float) ($service_order->invoice?->ppn_percent ?? (($invoiceSettings ?? [])['invoice_ppn_percent'] ?? 0))),
        ppnRupiah: @json((int) ($service_order->invoice?->ppn_rupiah ?? (($invoiceSettings ?? [])['invoice_ppn_rupiah'] ?? 0))),
        usePersistedInvoiceTotals: @json((bool) ($service_order->invoice && $service_order->status === 'selesai')),
        persistedChargeAmount: @json((int) $persistedChargeAmount),
        persistedPpnAmount: @json((int) ($service_order->invoice?->ppn_amount ?? 0)),
        pembayaranMode: @json($defaultPembayaranMode),
        splitRows: @json($splitRowsForJs),
        mekanikId: '',
        mekanikVersion: 0,
        syncMekanik() {
            const el = document.getElementById('mekanik-select-inline');
            this.mekanikId = el ? String(el.value || '') : '';
            // trigger reactivity even if same value
            this.mekanikVersion++;
        },
        get hasMekanik() {
            this.mekanikVersion;
            return String(this.mekanikId || '').trim() !== '';
        },
        showAddJasaModal: false,
        showAddSparepartModal: false,
        inputsVersion: 0,
        jasaCount: {{ $service_order->jasaServis->count() }},
        sparepartCount: {{ $service_order->products->count() }},
        totalQtySparepart: {{ $service_order->products->sum(fn($p) => (int) $p->pivot->qty) }},
        totalJasa: initialTotalJasa,
        totalSparepart: initialTotalSparepart,
        totalAkhir: initialTotalJasa + initialTotalSparepart,
        pendingAddJasa: [],
        pendingAddSparepart: [],
        jasaSelectModal: null,
        sparepartSelectModal: null,
        init() {
            this.splitRows = (this.splitRows || []).map((r) => {
                const digits = String(r?.jumlah ?? '').replace(/\D/g, '');
                const jumlah = digits ? parseInt(digits, 10) : 0;
                return {
                    ...r,
                    jumlah,
                    jumlahDisplay: this.formatNumber(jumlah),
                };
            });
        },
        setPembayaranMode(m) {
            this.pembayaranMode = m;
            if (m === 'split' && (!this.splitRows || this.splitRows.length < 2)) {
                this.splitRows = [
                    { metode: 'cash', jumlah: 0, jumlahDisplay: this.formatNumber(0) },
                    { metode: 'transfer', jumlah: 0, jumlahDisplay: this.formatNumber(0) },
                ];
            }
        },
        addSplitRow() {
            this.splitRows.push({ metode: 'cash', jumlah: 0, jumlahDisplay: this.formatNumber(0) });
        },
        removeSplitRow(i) {
            if (this.splitRows.length <= 2) {
                return;
            }
            this.splitRows.splice(i, 1);
        },
        get splitTotalPaid() {
            return this.splitRows.reduce((s, r) => {
                const n = parseInt(String(r.jumlah || '').replace(/\D/g, ''), 10);
                return s + (Number.isFinite(n) ? n : 0);
            }, 0);
        },
        get splitPaymentOk() {
            if (this.pembayaranMode !== 'split') {
                return true;
            }
            const activeLines = this.splitRows.filter((r) => (parseInt(String(r.jumlah || '').replace(/\D/g, ''), 10) || 0) > 0);
            return activeLines.length >= 2 && this.splitTotalPaid === this.totalAkhirFinal();
        },

        chargeAmount() {
            if (this.usePersistedInvoiceTotals) {
                return Math.max(0, Math.round(Number(this.persistedChargeAmount || 0)));
            }
            const base = Number(this.totalAkhir || 0);
            if (String(this.chargeMode || 'percent') === 'rupiah') {
                return Math.max(0, Math.round(Number(this.chargeRupiah || 0)));
            }
            const pct = Number(this.chargePercent || 0);
            if (pct <= 0) {
                return 0;
            }
            const fromPercent = Math.max(0, Math.round(base * (pct / 100)));

            return Math.max(this.chargePercentMinimum || 0, fromPercent);
        },

        ppnAmount() {
            if (this.usePersistedInvoiceTotals) {
                return Math.max(0, Math.round(Number(this.persistedPpnAmount || 0)));
            }
            const base = Number(this.totalAkhir || 0);
            if (String(this.ppnMode || 'percent') === 'rupiah') {
                return Math.max(0, Math.round(Number(this.ppnRupiah || 0)));
            }
            const pct = Number(this.ppnPercent || 0);
            return Math.max(0, Math.round(base * (pct / 100)));
        },

        totalAkhirFinal() {
            return Number(this.totalAkhir || 0) + this.chargeAmount() + this.ppnAmount();
        },
        get needManagerVerify() {
            this.inputsVersion;
            let need = false;
            document.querySelectorAll('.diskon-hidden-jasa').forEach(inp => {
                const base = parseInt(inp.dataset.biaya) || 0;
                const diskonRp = parseInt(inp.value) || 0;
                if (base > 0 && diskonRp > 0 && (diskonRp / base) > 0.10) need = true;
            });
            document.querySelectorAll('.diskon-hidden-sparepart').forEach(inp => {
                const base = parseInt(inp.dataset.subtotal) || 0;
                const diskonRp = parseInt(inp.value) || 0;
                if (base > 0 && diskonRp > 0 && (diskonRp / base) > 0.10) need = true;
            });
            return need;
        },
        updateTotal() {
            updateDiskonFromInputs();
            bindQtyEditors();
            const base = computeBaseTotalsFromDom();
            this.jasaCount = base.jasaCount;
            this.sparepartCount = base.sparepartCount;
            this.totalQtySparepart = base.totalQtySparepart;
            this.totalJasa = base.totalJasa;
            this.totalSparepart = base.totalSparepart;
            let totalDiskon = 0;
            document.querySelectorAll('.diskon-hidden-jasa').forEach(inp => { totalDiskon += parseInt(inp.value) || 0; });
            document.querySelectorAll('.diskon-hidden-sparepart').forEach(inp => { totalDiskon += parseInt(inp.value) || 0; });
            this.totalAkhir = Math.max(0, base.totalAwal - totalDiskon);
        },
        onDiskonChange() {
            this.inputsVersion++;
            this.updateTotal();
        },
        init() {
            const self = this;
            const btnAddRow = document.getElementById('btn-add-sparepart-row');
            const btnToggleAddJasa = document.getElementById('btn-toggle-add-jasa');
            const removeJasaContainer = document.getElementById('remove-jasa-container');
            const removeSparepartContainer = document.getElementById('remove-sparepart-container');
            if (btnAddRow) {
                btnAddRow.addEventListener('click', () => self.openAddSparepartModal());
            }
            if (btnToggleAddJasa) {
                btnToggleAddJasa.addEventListener('click', () => self.openAddJasaModal());
            }
            document.querySelectorAll('.btn-remove-jasa').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const id = btn.dataset.id;
                    if (!id || !removeJasaContainer) return;
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'remove_jasa_ids[]';
                    input.value = id;
                    removeJasaContainer.appendChild(input);
                    const row = btn.closest('tr');
                    if (row) row.remove();
                    self.onDiskonChange();
                });
            });
            document.querySelectorAll('.btn-remove-sparepart').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const id = btn.dataset.id;
                    if (!id || !removeSparepartContainer) return;
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'remove_sparepart_ids[]';
                    input.value = id;
                    removeSparepartContainer.appendChild(input);
                    const row = btn.closest('tr');
                    if (row) row.remove();
                    self.onDiskonChange();
                });
            });

            updateDiskonFromInputs();
            this.updateTotal();
            this.renderPendingAdditions();
            document.querySelectorAll('.diskon-tipe-jasa, .diskon-value-jasa, .diskon-tipe-sparepart, .diskon-value-sparepart').forEach(el => {
                el.addEventListener('input', () => self.onDiskonChange());
                el.addEventListener('change', () => self.onDiskonChange());
            });

            const mekEl = document.getElementById('mekanik-select-inline');
            if (mekEl) {
                self.syncMekanik();
                mekEl.addEventListener('change', () => self.syncMekanik());
            }
        },
        openAddJasaModal() {
            this.showAddJasaModal = true;
            this.$nextTick(() => {
                const el = document.getElementById('modal-add-jasa-select');
                const qtyEl = document.getElementById('modal-add-jasa-qty');
                if (qtyEl) qtyEl.value = 1;
                if (!el || typeof TomSelect === 'undefined') return;
                this.refreshJasaOptions().finally(() => {
                    const availableOptions = jasaOptions
                        .map((j) => ({
                            value: String(j.id),
                            text: `${j.nama} - Rp ${Number(j.biaya || 0).toLocaleString('id-ID')}`,
                            nama: j.nama,
                            biaya: Number(j.biaya || 0),
                        }));

                    if (this.jasaSelectModal) {
                        this.jasaSelectModal.destroy();
                    }
                    el.innerHTML = '<option value="">Pilih jasa servis...</option>';
                    this.jasaSelectModal = new TomSelect(el, {
                        create: false,
                        allowEmptyOption: true,
                        placeholder: 'Pilih jasa servis...',
                        options: availableOptions,
                        valueField: 'value',
                        labelField: 'text',
                        searchField: ['text', 'nama'],
                        render: {
                            option: (data, escape) => {
                                if (!data.value) {
                                    return `<div class="text-gray-400">Pilih jasa servis...</div>`;
                                }
                                const nama = data.nama || '';
                                const harga = `Rp ${Number(data.biaya || 0).toLocaleString('id-ID')}`;

                                return `<div class="flex items-center justify-between gap-3"><span class="font-medium">${escape(String(nama))}</span><span class="text-xs text-gray-400 text-right">${escape(String(harga))}</span></div>`;
                            },
                            item: (data, escape) => `<div>${escape(data.text || '')}</div>`,
                        },
                    });
                });
            });
        },
        closeAddJasaModal() {
            this.showAddJasaModal = false;
        },
        confirmAddJasa() {
            const selected = this.jasaSelectModal ? this.jasaSelectModal.getValue() : (document.getElementById('modal-add-jasa-select')?.value || '');
            const qty = Number(document.getElementById('modal-add-jasa-qty')?.value || 0);
            if (!selected) {
                Swal.fire('Perhatian', 'Pilih jasa servis terlebih dahulu.', 'warning');
                return;
            }
            if (qty < 1) {
                Swal.fire('Perhatian', 'Qty minimal 1.', 'warning');
                return;
            }
            // Jika jasa sudah ada di tabel utama, cukup tambah qty di baris tersebut (preview)
            const baseRow = document.querySelector(`#jasa-table-body tr.diskon-row-jasa[data-jasa-id="${selected}"]`);
            if (baseRow) {
                const qtyInp = baseRow.querySelector('input.qty-jasa');
                const hargaSatuan = Number(baseRow.dataset.hargaSatuan || 0);
                const currentQty = qtyInp ? (Number(qtyInp.value || 0) || 0) : 0;
                const newQty = currentQty + qty;
                if (qtyInp) {
                    qtyInp.value = String(newQty);
                }
                const newBiaya = hargaSatuan * newQty;
                baseRow.dataset.biaya = String(newBiaya);
                const hargaCell = baseRow.querySelector('[data-role="harga-total"]');
                if (hargaCell) {
                    hargaCell.textContent = 'Rp ' + this.formatNumber(newBiaya);
                }
                const hiddenDiskon = baseRow.querySelector('.diskon-hidden-jasa');
                const infoDiskon = baseRow.querySelector('.diskon-info-jasa');
                if (hiddenDiskon) hiddenDiskon.dataset.biaya = String(newBiaya);
                if (infoDiskon) infoDiskon.dataset.biaya = String(newBiaya);

                // Tambah payload add_jasa untuk backend (walaupun di UI tidak ada row "baru")
                const addJasaContainer = document.getElementById('add-jasa-container');
                const idx = addJasaContainer.querySelectorAll('input[name^="add_jasa["]').length;
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = `add_jasa[${idx}][jasa_servis_id]`;
                idInput.value = String(selected);
                addJasaContainer.appendChild(idInput);
                const qtyInput = document.createElement('input');
                qtyInput.type = 'hidden';
                qtyInput.name = `add_jasa[${idx}][qty]`;
                qtyInput.value = String(qty);
                addJasaContainer.appendChild(qtyInput);

                this.onDiskonChange();
                this.closeAddJasaModal();
                return;
            }

            const found = this.pendingAddJasa.find((x) => String(x.jasa_servis_id) === String(selected));
            if (found) {
                found.qty += qty;
            } else {
                this.pendingAddJasa.push({ jasa_servis_id: Number(selected), qty });
            }
            this.renderPendingAdditions();
            this.onDiskonChange();
            this.closeAddJasaModal();
        },
        openAddSparepartModal() {
            this.showAddSparepartModal = true;
            this.$nextTick(() => {
                const el = document.getElementById('modal-add-sparepart-select');
                const qtyEl = document.getElementById('modal-add-sparepart-qty');
                if (qtyEl) qtyEl.value = 1;
                if (!el || typeof TomSelect === 'undefined') return;
                if (this.sparepartSelectModal) {
                    this.sparepartSelectModal.destroy();
                }
                // Refresh stok terbaru tanpa reload halaman.
                this.refreshSparepartStocks().finally(() => {
                    // rebuild tomselect setelah stok terupdate
                    this.sparepartSelectModal = new TomSelect(el, {
                        create: false,
                        allowEmptyOption: true,
                        placeholder: 'Pilih sparepart...',
                        optgroupField: 'catalog_kind',
                        optgroupLabelField: 'label',
                        optgroupValueField: 'value',
                        lockOptgroupOrder: true,
                        optgroups: @json(\App\Support\ProductMasterCatalog::pickerOptgroups()),
                        options: sparepartOptions.map(p => {
                            const kind = p.catalog_kind || (p.is_part_luar ? 'part_luar' : 'mo');
                            const badgeText = { mo: 'MO', speedshop_supplier: 'Speedshop', part_luar: 'Part Luar', merchandise: 'Merch', consumable: 'Consumable' }[kind] || kind;
                            return {
                                id: String(p.id),
                                label: `[${p.kode_produk}] ${p.nama_produk} · ${badgeText} — Stok: ${Number(p.jumlah || 0).toLocaleString('id-ID')}`,
                                kode_produk: p.kode_produk,
                                nama_produk: p.nama_produk,
                                jumlah: Number(p.jumlah || 0),
                                catalog_kind: kind,
                            };
                        }),
                        valueField: 'id',
                        labelField: 'label',
                        searchField: ['label', 'kode_produk', 'nama_produk'],
                        render: {
                            optgroup_header: (data, escape) => `<div class="px-2 py-1.5 text-[10px] font-semibold uppercase tracking-wide text-gray-500 bg-gray-50 border-b border-gray-100">${escape(data.label)}</div>`,
                            option: (data, escape) => {
                                if (!data.id) {
                                    return `<div class="text-gray-400">Pilih sparepart...</div>`;
                                }
                                const kind = data.catalog_kind || 'mo';
                                const badgeText = { mo: 'MO', speedshop_supplier: 'Speedshop', part_luar: 'Part Luar', merchandise: 'Merch', consumable: 'Consumable' }[kind] || kind;
                                const badgeCls = { mo: 'bg-indigo-100 text-indigo-800', speedshop_supplier: 'bg-emerald-100 text-emerald-800', part_luar: 'bg-amber-100 text-amber-800', merchandise: 'bg-pink-100 text-pink-800', consumable: 'bg-sky-100 text-sky-800' }[kind] || 'bg-gray-100 text-gray-700';
                                const badge = `<span class="ml-1 rounded ${badgeCls} px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide">${badgeText}</span>`;
                                return `<div class="flex items-center justify-between gap-3"><span><span class="font-medium">${escape(data.kode_produk || '')}</span> - ${escape(data.nama_produk || '')}${badge}</span><span class="text-xs text-gray-400 text-right">Stok: ${Number(data.jumlah || 0).toLocaleString('id-ID')}</span></div>`;
                            },
                            item: (data, escape) => `<div>${escape(data.label || '')}</div>`,
                        },
                    });
                });
            });
        },
        closeAddSparepartModal() {
            this.showAddSparepartModal = false;
        },
        confirmAddSparepart() {
            const selected = this.sparepartSelectModal ? this.sparepartSelectModal.getValue() : (document.getElementById('modal-add-sparepart-select')?.value || '');
            const qty = Number(document.getElementById('modal-add-sparepart-qty')?.value || 0);
            if (!selected) {
                Swal.fire('Perhatian', 'Pilih sparepart terlebih dahulu.', 'warning');
                return;
            }
            if (qty < 1) {
                Swal.fire('Perhatian', 'Qty minimal 1.', 'warning');
                return;
            }

            const stok = stockForProduct(selected);
            if (stok <= 0) {
                Swal.fire('Stok kosong', 'Stok sparepart ini 0, tidak bisa ditambahkan.', 'warning');
                return;
            }
            // Jika sparepart sudah ada di tabel utama, cukup tambah qty di baris tersebut
            const baseRow = document.querySelector(`#sparepart-table-body tr.diskon-row-sparepart[data-product-id="${selected}"]`);
            if (baseRow) {
                const qtyInp = baseRow.querySelector('input.qty-sparepart');
                const hargaSatuan = Number(baseRow.dataset.hargaSatuan || 0);
                const currentQty = qtyInp ? (Number(qtyInp.value || 0) || 0) : 0;
                const newQty = currentQty + qty;
                if (newQty > stok) {
                    Swal.fire('Stok tidak cukup', `Stok tersedia: ${this.formatNumber(stok)}. Tambahan qty membuat total pemakaian menjadi ${this.formatNumber(newQty)}.`, 'warning');
                    return;
                }
                if (qtyInp) {
                    qtyInp.value = String(newQty);
                }
                const newSubtotal = hargaSatuan * newQty;
                baseRow.dataset.subtotal = String(newSubtotal);
                const subtotalCell = baseRow.querySelector('[data-role="subtotal"]');
                if (subtotalCell) {
                    subtotalCell.textContent = 'Rp ' + this.formatNumber(newSubtotal);
                }
                const hiddenDiskon = baseRow.querySelector('.diskon-hidden-sparepart');
                const infoDiskon = baseRow.querySelector('.diskon-info-sparepart');
                if (hiddenDiskon) hiddenDiskon.dataset.subtotal = String(newSubtotal);
                if (infoDiskon) infoDiskon.dataset.subtotal = String(newSubtotal);

                const addSparepartContainer = document.getElementById('add-sparepart-container');
                const idx = addSparepartContainer.querySelectorAll('input[name^="add_sparepart["]').length;
                const pidInput = document.createElement('input');
                pidInput.type = 'hidden';
                pidInput.name = `add_sparepart[${idx}][product_id]`;
                pidInput.value = String(selected);
                addSparepartContainer.appendChild(pidInput);
                const qtyInput = document.createElement('input');
                qtyInput.type = 'hidden';
                qtyInput.name = `add_sparepart[${idx}][qty]`;
                qtyInput.value = String(qty);
                addSparepartContainer.appendChild(qtyInput);

                this.onDiskonChange();
                this.closeAddSparepartModal();
                return;
            }

            const found = this.pendingAddSparepart.find((x) => String(x.product_id) === String(selected));
            if (found) {
                if ((Number(found.qty || 0) + qty) > stok) {
                    Swal.fire('Stok tidak cukup', `Stok tersedia: ${this.formatNumber(stok)}.`, 'warning');
                    return;
                }
                found.qty += qty;
            } else {
                if (qty > stok) {
                    Swal.fire('Stok tidak cukup', `Stok tersedia: ${this.formatNumber(stok)}.`, 'warning');
                    return;
                }
                this.pendingAddSparepart.push({ product_id: Number(selected), qty });
            }
            this.renderPendingAdditions();
            this.onDiskonChange();
            this.closeAddSparepartModal();
        },
        async refreshSparepartStocks() {
            try {
                const ids = sparepartOptions.map(p => Number(p.id || 0)).filter(Boolean);
                if (ids.length === 0) return;
                const res = await fetch('{{ route('speedshop.selesaikan-transaksi.sparepart-stock', $service_order) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ product_ids: ids }),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) return;
                const stocks = data?.stocks || {};
                sparepartOptions.forEach((p) => {
                    const v = stocks[String(p.id)] ?? stocks[p.id];
                    if (v !== undefined && v !== null) p.jumlah = Number(v) || 0;
                });
            } catch (e) {
                // ignore
            }
        },
        async refreshJasaOptions() {
            try {
                const res = await fetch('{{ route('speedshop.selesaikan-transaksi.jasa-options', $service_order) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) return;
                const items = Array.isArray(data?.items) ? data.items : [];
                jasaOptions = items;
            } catch (e) {
                // ignore
            }
        },
        renderPendingAdditions() {
            const isWipContext = {{ $isWipContext ? 'true' : 'false' }};
            const jasaTableBody = document.getElementById('jasa-table-body');
            const sparepartTableBody = document.getElementById('sparepart-table-body');
            const addJasaContainer = document.getElementById('add-jasa-container');
            const addSparepartContainer = document.getElementById('add-sparepart-container');
            if (!jasaTableBody || !sparepartTableBody || !addJasaContainer || !addSparepartContainer) return;

            jasaTableBody.querySelectorAll('.pending-add-jasa-row').forEach((row) => row.remove());
            addJasaContainer.innerHTML = '';
            const existingCount = jasaTableBody.querySelectorAll('tr:not(.pending-add-jasa-row)').length;
            this.pendingAddJasa.forEach((item, idx) => {
                const id = Number(item.jasa_servis_id || 0);
                const qty = Math.max(1, Number(item.qty) || 1);
                const jasa = jasaById(id);
                const nama = jasa?.nama ? String(jasa.nama) : `Jasa #${id}`;
                const biayaSatuan = Number(jasa?.biaya || 0) || 0;
                const biaya = biayaSatuan * qty;
                const row = document.createElement('tr');
                row.className = 'pending-add-jasa-row bg-amber-50/40 transition hover:bg-amber-50';
                if (isWipContext) {
                    row.innerHTML = `
                        <td class="px-5 py-4 text-gray-500">${existingCount + idx + 1}</td>
                        <td class="px-5 py-4 font-semibold text-gray-900">${nama}</td>
                        <td class="px-5 py-4 text-center text-gray-700">
                            <input type="number" min="1" step="1"
                                   class="pending-qty-jasa w-20 rounded-xl border border-gray-200 bg-white px-2 py-1.5 text-center text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40"
                                   value="${qty}">
                        </td>
                        <td class="px-5 py-4 text-right text-gray-700 tabular-nums">Rp ${this.formatNumber(biayaSatuan)}</td>
                        <td class="px-5 py-4 text-right text-gray-700 tabular-nums pending-jasa-total">Rp ${this.formatNumber(biaya)}</td>
                        <td class="px-5 py-4 text-center"><button type="button" class="inline-flex items-center rounded-lg border border-red-200 bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100">Hapus</button></td>
                        <input type="hidden" class="diskon-hidden-jasa" value="0" data-id="new-j-${idx}" data-biaya="${biaya}" name="add_jasa[${idx}][diskon]">
                    `;
                } else {
                    row.innerHTML = `
                        <td class="px-5 py-4 text-gray-500">${existingCount + idx + 1}</td>
                        <td class="px-5 py-4 font-semibold text-gray-900">${nama}</td>
                        <td class="px-5 py-4 text-center text-gray-700">
                            <input type="number" min="1" step="1"
                                   class="pending-qty-jasa w-20 rounded-xl border border-gray-200 bg-white px-2 py-1.5 text-center text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40"
                                   value="${qty}">
                        </td>
                        <td class="px-5 py-4 text-right text-gray-700 pending-jasa-total">Rp ${this.formatNumber(biaya)}</td>
                        <td class="px-5 py-4">
                            <select class="diskon-tipe-jasa w-20 rounded-xl border border-gray-200 bg-white px-2 py-1.5 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40">
                                <option value="rp">Rp</option>
                                <option value="persen" selected>%</option>
                            </select>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <input type="number" class="diskon-value-jasa w-24 rounded-xl border border-gray-200 bg-white px-2 py-1.5 text-right text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40" value="0" min="0" data-id="new-j-${idx}" placeholder="0">
                            <input type="hidden" class="diskon-hidden-jasa" value="0" data-id="new-j-${idx}" data-biaya="${biaya}" name="add_jasa[${idx}][diskon]">
                        </td>
                        <td class="px-5 py-4 text-right text-xs text-gray-500 diskon-info-jasa" data-biaya="${biaya}">Rp 0</td>
                        <td class="px-5 py-4">
                            <input type="text"
                                   name="add_jasa[${idx}][catatan_diskon]"
                                   maxlength="500"
                                   placeholder="Wajib jika ada diskon"
                                   class="catatan-diskon-jasa w-full min-w-[160px] rounded-xl border border-gray-200 bg-white px-2 py-1.5 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40">
                        </td>
                        <td class="px-5 py-4 text-center"><button type="button" class="inline-flex items-center rounded-lg border border-red-200 bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100">Hapus</button></td>
                    `;
                }
                row.querySelector('button')?.addEventListener('click', () => {
                    this.pendingAddJasa.splice(idx, 1);
                    this.renderPendingAdditions();
                    this.onDiskonChange();
                });
                row.querySelector('.pending-qty-jasa')?.addEventListener('input', (e) => {
                    const vRaw = e?.target?.value ?? '';
                    const nextQty = Math.max(1, Math.floor(Number(vRaw) || 0));
                    if (this.pendingAddJasa[idx]) {
                        this.pendingAddJasa[idx].qty = nextQty;
                    }
                    // update hidden qty
                    const hiddenQty = addJasaContainer.querySelector(`input[name="add_jasa[${idx}][qty]"]`);
                    if (hiddenQty) hiddenQty.value = String(nextQty);

                    const nextBiaya = biayaSatuan * nextQty;
                    const totalCell = row.querySelector('.pending-jasa-total');
                    if (totalCell) totalCell.textContent = `Rp ${this.formatNumber(nextBiaya)}`;

                    // update diskon basis (biaya)
                    const diskonHidden = row.querySelector(`input.diskon-hidden-jasa[data-id="new-j-${idx}"]`);
                    const info = row.querySelector('.diskon-info-jasa');
                    if (diskonHidden) diskonHidden.dataset.biaya = String(nextBiaya);
                    if (info) info.dataset.biaya = String(nextBiaya);

                    this.onDiskonChange();
                });
                if (!isWipContext) {
                    row.querySelectorAll('.diskon-tipe-jasa, .diskon-value-jasa').forEach((el) => {
                        el.addEventListener('input', () => this.onDiskonChange());
                        el.addEventListener('change', () => this.onDiskonChange());
                    });
                }
                jasaTableBody.appendChild(row);

                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `add_jasa[${idx}][jasa_servis_id]`;
                input.value = String(id);
                addJasaContainer.appendChild(input);

                const qtyInput = document.createElement('input');
                qtyInput.type = 'hidden';
                qtyInput.name = `add_jasa[${idx}][qty]`;
                qtyInput.value = String(qty);
                addJasaContainer.appendChild(qtyInput);
            });

            sparepartTableBody.querySelectorAll('.pending-add-sparepart-row').forEach((row) => row.remove());
            addSparepartContainer.innerHTML = '';
            const existingSparepartCount = sparepartTableBody.querySelectorAll('tr:not(.pending-add-sparepart-row)').length;
            this.pendingAddSparepart.forEach((item, idx) => {
                const p = sparepartOptions.find((x) => String(x.id) === String(item.product_id));
                const kode = p?.kode_produk || '-';
                const nama = p?.nama_produk || `Sparepart #${item.product_id}`;
                const harga = Number(p?.harga_jual_cust || p?.harga_eceran_terendah || p?.harga_jual || p?.harga_jual_speedshop || p?.hpp || 0);
                const subtotal = harga * Number(item.qty || 0);
                const row = document.createElement('tr');
                row.className = 'pending-add-sparepart-row bg-amber-50/40 transition hover:bg-amber-50';
                if (isWipContext) {
                    row.innerHTML = `
                        <td class="px-5 py-4 text-gray-500">${existingSparepartCount + idx + 1}</td>
                        <td class="px-5 py-4 font-semibold text-gray-900">${kode}</td>
                        <td class="px-5 py-4 text-gray-700">${nama}</td>
                        <td class="px-5 py-4 text-center">
                            <input type="number" min="1" step="1"
                                   class="pending-qty-sparepart w-20 rounded-xl border border-gray-200 bg-white px-2 py-1.5 text-center text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40"
                                   value="${Math.max(1, Number(item.qty || 1))}">
                        </td>
                        <td class="px-5 py-4 text-right text-gray-700 tabular-nums">Rp ${this.formatNumber(harga)}</td>
                        <td class="px-5 py-4 text-right text-gray-700 tabular-nums pending-sparepart-subtotal">Rp ${this.formatNumber(subtotal)}</td>
                        <td class="px-5 py-4 text-center"><button type="button" class="inline-flex items-center rounded-lg border border-red-200 bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100">Hapus</button></td>
                        <input type="hidden" class="diskon-hidden-sparepart" value="0" data-id="new-p-${idx}" data-subtotal="${subtotal}" name="add_sparepart[${idx}][diskon]">
                    `;
                } else {
                    row.innerHTML = `
                        <td class="px-5 py-4 text-gray-500">${existingSparepartCount + idx + 1}</td>
                        <td class="px-5 py-4 font-semibold text-gray-900">${kode}</td>
                        <td class="px-5 py-4 text-gray-700">${nama}</td>
                        <td class="px-5 py-4 text-center">
                            <input type="number" min="1" step="1"
                                   class="pending-qty-sparepart w-20 rounded-xl border border-gray-200 bg-white px-2 py-1.5 text-center text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40"
                                   value="${Math.max(1, Number(item.qty || 1))}">
                        </td>
                        <td class="px-5 py-4 text-right text-gray-700 pending-sparepart-subtotal">Rp ${this.formatNumber(subtotal)}</td>
                        <td class="px-5 py-4">
                            <select class="diskon-tipe-sparepart w-20 rounded-xl border border-gray-200 bg-white px-2 py-1.5 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40">
                                <option value="rp">Rp</option>
                                <option value="persen" selected>%</option>
                            </select>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <input type="number" class="diskon-value-sparepart w-24 rounded-xl border border-gray-200 bg-white px-2 py-1.5 text-right text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40" value="0" min="0" data-id="new-p-${idx}" placeholder="0">
                            <input type="hidden" class="diskon-hidden-sparepart" value="0" data-id="new-p-${idx}" data-subtotal="${subtotal}" name="add_sparepart[${idx}][diskon]">
                        </td>
                        <td class="px-5 py-4 text-right text-xs text-gray-500 diskon-info-sparepart" data-subtotal="${subtotal}">Rp 0</td>
                        <td class="px-5 py-4">
                            <input type="text"
                                   name="add_sparepart[${idx}][catatan_diskon]"
                                   maxlength="500"
                                   placeholder="Wajib jika ada diskon"
                                   class="catatan-diskon-sparepart w-full min-w-[160px] rounded-xl border border-gray-200 bg-white px-2 py-1.5 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40">
                        </td>
                        <td class="px-5 py-4 text-center"><button type="button" class="inline-flex items-center rounded-lg border border-red-200 bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100">Hapus</button></td>
                    `;
                }
                row.querySelector('button')?.addEventListener('click', () => {
                    this.pendingAddSparepart.splice(idx, 1);
                    this.renderPendingAdditions();
                    this.onDiskonChange();
                });
                row.querySelector('.pending-qty-sparepart')?.addEventListener('input', (e) => {
                    const vRaw = e?.target?.value ?? '';
                    const nextQty = Math.max(1, Math.floor(Number(vRaw) || 0));
                    const stok = stockForProduct(item.product_id);
                    if (stok <= 0) {
                        Swal.fire('Stok kosong', 'Stok sparepart ini 0, tidak bisa ditambahkan.', 'warning');
                        e.target.value = '1';
                        return;
                    }
                    if (nextQty > stok) {
                        Swal.fire('Stok tidak cukup', `Stok tersedia: ${this.formatNumber(stok)}.`, 'warning');
                        e.target.value = String(stok);
                        return;
                    }
                    if (this.pendingAddSparepart[idx]) {
                        this.pendingAddSparepart[idx].qty = nextQty;
                    }
                    // update hidden qty
                    const hiddenQty = addSparepartContainer.querySelector(`input[name="add_sparepart[${idx}][qty]"]`);
                    if (hiddenQty) hiddenQty.value = String(nextQty);

                    const nextSubtotal = harga * nextQty;
                    const subtotalCell = row.querySelector('.pending-sparepart-subtotal');
                    if (subtotalCell) subtotalCell.textContent = `Rp ${this.formatNumber(nextSubtotal)}`;

                    // update diskon basis (subtotal)
                    const diskonHidden = row.querySelector(`input.diskon-hidden-sparepart[data-id="new-p-${idx}"]`);
                    const info = row.querySelector('.diskon-info-sparepart');
                    if (diskonHidden) diskonHidden.dataset.subtotal = String(nextSubtotal);
                    if (info) info.dataset.subtotal = String(nextSubtotal);

                    this.onDiskonChange();
                });
                if (!isWipContext) {
                    row.querySelectorAll('.diskon-tipe-sparepart, .diskon-value-sparepart').forEach((el) => {
                        el.addEventListener('input', () => this.onDiskonChange());
                        el.addEventListener('change', () => this.onDiskonChange());
                    });
                }
                sparepartTableBody.appendChild(row);

                const pidInput = document.createElement('input');
                pidInput.type = 'hidden';
                pidInput.name = `add_sparepart[${idx}][product_id]`;
                pidInput.value = String(item.product_id);
                addSparepartContainer.appendChild(pidInput);

                const qtyInput = document.createElement('input');
                qtyInput.type = 'hidden';
                qtyInput.name = `add_sparepart[${idx}][qty]`;
                qtyInput.value = String(item.qty);
                addSparepartContainer.appendChild(qtyInput);
            });
        },
        syncPendingInputsToForm() {
            const addJasaContainer = document.getElementById('add-jasa-container');
            const addSparepartContainer = document.getElementById('add-sparepart-container');
            if (!addJasaContainer || !addSparepartContainer) return;

            addJasaContainer.innerHTML = '';
            this.pendingAddJasa.forEach((item, idx) => {
                const id = Number(item.jasa_servis_id || 0);
                const qty = Math.max(1, Number(item.qty) || 1);
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `add_jasa[${idx}][jasa_servis_id]`;
                input.value = String(id);
                addJasaContainer.appendChild(input);

                const qtyInput = document.createElement('input');
                qtyInput.type = 'hidden';
                qtyInput.name = `add_jasa[${idx}][qty]`;
                qtyInput.value = String(qty);
                addJasaContainer.appendChild(qtyInput);
            });

            addSparepartContainer.innerHTML = '';
            this.pendingAddSparepart.forEach((item, idx) => {
                const pidInput = document.createElement('input');
                pidInput.type = 'hidden';
                pidInput.name = `add_sparepart[${idx}][product_id]`;
                pidInput.value = String(item.product_id);
                addSparepartContainer.appendChild(pidInput);

                const qtyInput = document.createElement('input');
                qtyInput.type = 'hidden';
                qtyInput.name = `add_sparepart[${idx}][qty]`;
                qtyInput.value = String(item.qty);
                addSparepartContainer.appendChild(qtyInput);
            });
        },
        formatNumber(n) {
            const digits = String(n ?? '').replace(/\D/g, '');
            const val = digits ? parseInt(digits, 10) : 0;
            return Number.isFinite(val) ? val.toLocaleString('id-ID') : '0';
        },
        async requestCancelWorkOrder() {
            const result = await Swal.fire({
                title: 'Batalkan Work Order?',
                text: 'Pengajuan akan dikirim ke Accounting. Stok sparepart dikembalikan setelah disetujui.',
                input: 'textarea',
                inputLabel: 'Alasan pembatalan',
                inputPlaceholder: 'Tulis alasan pembatalan...',
                inputAttributes: { maxlength: '500' },
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, ajukan',
                cancelButtonText: 'Batal',
                preConfirm: (value) => {
                    const reason = (value || '').trim();
                    if (!reason) {
                        Swal.showValidationMessage('Alasan pembatalan wajib diisi.');
                        return false;
                    }
                    return reason;
                },
            });
            if (!result.isConfirmed) return;

            this.cancelLoading = true;
            try {
                const res = await fetch('{{ route('speedshop.wip.cancel.request', $service_order) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ reason: result.value }),
                });
                const data = await res.json().catch(() => ({}));
                this.cancelLoading = false;
                if (!res.ok) {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: data.message || 'Gagal mengajukan pembatalan.' });
                    return;
                }
                window.location.href = data.redirect || '{{ route('speedshop.wip.show', $service_order) }}';
            } catch (e) {
                this.cancelLoading = false;
                Swal.fire({ icon: 'error', title: 'Gagal', text: 'Gagal mengirim pengajuan pembatalan.' });
            }
        },

        async approveCancelWorkOrder() {
            const result = await Swal.fire({
                title: 'Setujui pembatalan?',
                text: 'Work order akan dibatalkan dan stok sparepart dikembalikan ke inventory.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, setujui',
                cancelButtonText: 'Batal',
            });
            if (!result.isConfirmed) return;

            this.cancelLoading = true;
            try {
                const res = await fetch('{{ route('speedshop.wip.cancel.approve', $service_order) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json().catch(() => ({}));
                this.cancelLoading = false;
                if (!res.ok) {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: data.message || 'Gagal menyetujui pembatalan.' });
                    return;
                }
                window.location.href = data.redirect || '{{ route('speedshop.wip.show', $service_order) }}';
            } catch (e) {
                this.cancelLoading = false;
                Swal.fire({ icon: 'error', title: 'Gagal', text: 'Gagal memproses persetujuan.' });
            }
        },

        async rejectCancelWorkOrder() {
            const result = await Swal.fire({
                title: 'Tolak pembatalan?',
                input: 'textarea',
                inputLabel: 'Alasan penolakan',
                inputPlaceholder: 'Tulis alasan penolakan...',
                inputAttributes: { maxlength: '500' },
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, tolak',
                cancelButtonText: 'Batal',
                preConfirm: (value) => {
                    const reason = (value || '').trim();
                    if (!reason) {
                        Swal.showValidationMessage('Alasan penolakan wajib diisi.');
                        return false;
                    }
                    return reason;
                },
            });
            if (!result.isConfirmed) return;

            this.cancelLoading = true;
            try {
                const res = await fetch('{{ route('speedshop.wip.cancel.reject', $service_order) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ rejected_reason: result.value }),
                });
                const data = await res.json().catch(() => ({}));
                this.cancelLoading = false;
                if (!res.ok) {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: data.message || 'Gagal menolak pembatalan.' });
                    return;
                }
                window.location.href = data.redirect || '{{ route('speedshop.wip.show', $service_order) }}';
            } catch (e) {
                this.cancelLoading = false;
                Swal.fire({ icon: 'error', title: 'Gagal', text: 'Gagal memproses penolakan.' });
            }
        },

        async panggilMonitorAntrian() {
            if (this.panggilLoading) return;
            const meta = document.querySelector('meta[name="csrf-token"]');
            const csrf = meta ? (meta.getAttribute('content') || '') : '';
            this.panggilLoading = true;
            try {
                const res = await fetch(panggilAntrianUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: '{}',
                });
                const data = await res.json().catch(() => ({}));
                if (res.ok && data.ok) {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Panggilan dikirim ke monitor',
                        showConfirmButton: false,
                        timer: 2200,
                    });
                } else {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Gagal memanggil',
                        text: data.message || 'Silakan coba lagi.',
                    });
                }
            } catch (e) {
                Swal.fire({ icon: 'error', title: 'Jaringan', text: 'Tidak dapat mengirim panggilan.' });
            } finally {
                this.panggilLoading = false;
            }
        },
        async submitForm(ev) {
            const form = ev && ev.target ? ev.target : document.getElementById('form-selesaikan-transaksi');
            this.syncPendingInputsToForm();
            // Pastikan qty existing selalu terisi sebelum submit (hindari error jasa.{id}.qty required).
            document.querySelectorAll('#jasa-table-body tr.diskon-row-jasa input.qty-jasa').forEach((inp) => {
                const q = Math.max(1, Math.floor(Number(inp.value || 0) || 0));
                inp.value = String(q);
            });
            document.querySelectorAll('#sparepart-table-body tr.diskon-row-sparepart input.qty-sparepart').forEach((inp) => {
                const q = Math.max(1, Math.floor(Number(inp.value || 0) || 0));
                inp.value = String(q);
            });
            this.updateTotal();
            const isWipContext = {{ $isWipContext ? 'true' : 'false' }};
            if (! isWipContext) {
                const missingCatatan = [];
                document.querySelectorAll('input.diskon-hidden-jasa').forEach((inp) => {
                    const diskon = parseInt(String(inp.value || '0'), 10) || 0;
                    if (diskon <= 0) return;
                    const row = inp.closest('tr');
                    const catatanInp = row?.querySelector('[name*="[catatan_diskon]"]');
                    if (!catatanInp || !String(catatanInp.value || '').trim()) {
                        missingCatatan.push('jasa');
                    }
                });
                document.querySelectorAll('input.diskon-hidden-sparepart').forEach((inp) => {
                    const diskon = parseInt(String(inp.value || '0'), 10) || 0;
                    if (diskon <= 0) return;
                    const row = inp.closest('tr');
                    const catatanInp = row?.querySelector('[name*="[catatan_diskon]"]');
                    if (!catatanInp || !String(catatanInp.value || '').trim()) {
                        missingCatatan.push('sparepart');
                    }
                });
                if (missingCatatan.length) {
                    Swal.fire('Perhatian', 'Catatan diskon wajib diisi untuk setiap item jasa/sparepart yang memiliki diskon.', 'warning');
                    return;
                }
            }
            // Diskon > 10% harus masuk verifikasi (tidak langsung selesai). Staff tetap boleh mengajukan.
            if (! isWipContext && this.pembayaranMode === 'split' && ! this.splitPaymentOk) {
                const labelMetode = (m) => {
                    const x = String(m || '').toLowerCase().trim();
                    if (x === 'cash' || x === 'tunai') return 'Tunai';
                    if (x === 'transfer') return 'Transfer';
                    if (x === 'qris') return 'QRIS';
                    if (x === 'edc') return 'EDC';
                    return x ? x.charAt(0).toUpperCase() + x.slice(1) : '-';
                };
                const activeLines = (this.splitRows || []).map((r) => {
                    const jumlah = parseInt(String(r?.jumlah ?? '').replace(/\D/g, ''), 10) || 0;
                    return { metode: String(r?.metode ?? ''), jumlah };
                }).filter((r) => r.jumlah > 0);
                const totalDibayar = activeLines.reduce((s, r) => s + (r.jumlah || 0), 0);
                const totalTransaksi = Number(this.totalAkhirFinal());
                const sisa = totalTransaksi - totalDibayar;

                const listHtml = activeLines.length
                    ? activeLines.map((r) => `<div style="display:flex; justify-content:space-between; gap:12px;"><span>${labelMetode(r.metode)}</span><strong>Rp ${this.formatNumber(r.jumlah)}</strong></div>`).join('')
                    : `<div style="color:#6b7280;">Belum ada nominal pembayaran.</div>`;

                const sisaLabel = sisa === 0 ? 'Sisa (harus Rp 0)' : (sisa > 0 ? 'Kurang bayar' : 'Kelebihan bayar');
                const sisaAbs = Math.abs(sisa);

                Swal.fire({
                    title: 'Split pembayaran belum pas',
                    icon: 'warning',
                    html: `
                        <div style="text-align:left; font-size:14px; line-height:1.45;">
                            ${listHtml}
                            <hr style="margin:10px 0; border:none; border-top:1px solid #e5e7eb;" />
                            <div style="display:flex; justify-content:space-between; gap:12px;">
                                <span>Total dibayar</span><strong>Rp ${this.formatNumber(totalDibayar)}</strong>
                            </div>
                            <div style="display:flex; justify-content:space-between; gap:12px; margin-top:4px;">
                                <span>Total transaksi</span><strong>Rp ${this.formatNumber(totalTransaksi)}</strong>
                            </div>
                            <div style="display:flex; justify-content:space-between; gap:12px; margin-top:6px;">
                                <span>${sisaLabel}</span><strong>Rp ${this.formatNumber(sisaAbs)}</strong>
                            </div>
                        </div>
                    `,
                    confirmButtonColor: '#ef4444',
                    confirmButtonText: 'OK',
                });
                return;
            }
            const isWipAntri = isWipContext && @json($service_order->status) === 'antri';
            if (isWipAntri) {
                if (this.jasaCount < 1) {
                    Swal.fire({
                        title: 'Jasa wajib diisi',
                        text: 'Tambahkan minimal satu jasa servis sebelum cetak Work Order.',
                        icon: 'warning',
                    });
                    return;
                }
                const mekEl = document.getElementById('mekanik-select-inline');
                const mekVal = mekEl ? String(mekEl.value || '').trim() : '';
                if (!mekVal) {
                    Swal.fire({ title: 'Pilih mekanik', text: 'Mekanik wajib dipilih sebelum cetak Work Order.', icon: 'warning' });
                    return;
                }
            }
            const result = await Swal.fire({
                title: isWipAntri ? 'Cetak Work Order?' : (isWipContext ? 'Simpan Perubahan?' : (this.needManagerVerify ? 'Ajukan Verifikasi?' : 'Selesaikan Transaksi?')),
                text: isWipAntri
                    ? 'Item akan disimpan, status berubah ke Proses, lalu Work Order akan dicetak. Lanjutkan?'
                    : (isWipContext
                        ? 'Perubahan jasa & sparepart akan disimpan. Lanjutkan?'
                        : (this.needManagerVerify
                            ? 'Diskon > 10% akan diajukan untuk verifikasi Manager (Approve/Tolak). Lanjutkan?'
                            : 'Work order akan ditandai selesai. Lanjutkan?')),
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: isWipAntri ? 'Ya, cetak' : (isWipContext ? 'Ya, simpan' : (this.needManagerVerify ? 'Ya, ajukan' : 'Ya, selesaikan')),
                cancelButtonText: 'Batal'
            });
            if (!result.isConfirmed) return;
            this.submitting = true;
            if (!form) return;

            try {
                const res = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: new FormData(form),
                });

                const data = await res.json().catch(() => ({}));
                this.submitting = false;

                if (!res.ok) {
                    const errs = data?.errors ? Object.values(data.errors).flat().filter(Boolean) : [];
                    const msg = (errs[0] || data?.message || 'Gagal memproses transaksi.');
                    Swal.fire({ title: 'Gagal', text: String(msg), icon: 'error' });
                    return;
                }

                if (data?.open_print_url) {
                    try { window.open(String(data.open_print_url), '_blank', 'noopener'); } catch (e) {}
                }
                const redirect = data?.redirect || null;
                if (redirect) {
                    window.location.href = redirect;
                    return;
                }

                Swal.fire({ title: 'Berhasil', text: String(data?.message || 'Transaksi berhasil diproses.'), icon: 'success' });
            } catch (e) {
                this.submitting = false;
                Swal.fire({ title: 'Gagal', text: 'Gagal mengirim data. Coba ulang.', icon: 'error' });
            }
        },

        async handleDiskonVerifikasiResponse(res, fallbackError) {
            if (res.ok) {
                const data = await res.json().catch(() => ({}));
                if (data && data.redirect) {
                    window.location.href = data.redirect;
                    return true;
                }
                window.location.reload();
                return true;
            }

            const d = await res.json().catch(() => ({}));
            this.loading = false;
            this.diskonBusy = false;
            if (res.status === 409 || d.already_processed) {
                await Swal.fire({
                    title: 'Sudah diproses',
                    text: d.message || 'Pengajuan diskon sudah tidak menunggu persetujuan.',
                    icon: 'info',
                    confirmButtonColor: '#6b7280',
                });
                window.location.href = d.redirect || window.location.href;
                return false;
            }
            Swal.fire('Error', d.message || fallbackError, 'error');
            return false;
        },

        async approveDiskon() {
            if (this.loading || this.diskonBusy) return;
            this.diskonBusy = true;
            const result = await Swal.fire({
                title: 'Approve Diskon?',
                text: 'Work order akan diselesaikan setelah diskon disetujui.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#22c55e',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Approve!',
                cancelButtonText: 'Batal',
                allowOutsideClick: false,
            });
            if (!result.isConfirmed) {
                this.diskonBusy = false;
                return;
            }

            this.loading = true;
            const res = await fetch(@json($diskonApproveUrl), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });
            if (!await this.handleDiskonVerifikasiResponse(res, 'Gagal approve.')) {
                return;
            }
        },

        async confirmRejectDiskon() {
            if (this.loading || this.diskonBusy) return;
            if (!this.rejectedReason.trim()) {
                this.rejectError = 'Masukkan alasan penolakan.';
                return;
            }
            this.rejectError = '';
            this.diskonBusy = true;

            const result = await Swal.fire({
                title: 'Reject Diskon?',
                text: 'Diskon akan ditolak dan di-reset ke 0 (bisa diisi ulang).',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Tolak!',
                cancelButtonText: 'Batal',
                allowOutsideClick: false,
            });
            if (!result.isConfirmed) {
                this.diskonBusy = false;
                return;
            }

            this.loading = true;
            const res = await fetch(@json($diskonRejectUrl), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ rejected_reason: this.rejectedReason.trim() })
            });
            if (!await this.handleDiskonVerifikasiResponse(res, 'Gagal menolak.')) {
                return;
            }
        },

        async rejectDiskon() {
            if (this.loading || this.diskonBusy) return;
            this.diskonBusy = true;
            const result = await Swal.fire({
                title: 'Reject Diskon?',
                text: 'Diskon akan ditolak dan di-reset ke 0 (bisa diisi ulang).',
                input: 'textarea',
                inputLabel: 'Keterangan Reject',
                inputPlaceholder: 'Tulis alasan reject...',
                inputAttributes: {
                    maxlength: '500',
                },
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Tolak!',
                cancelButtonText: 'Batal',
                allowOutsideClick: false,
                preConfirm: (value) => {
                    const reason = (value || '').trim();
                    if (!reason) {
                        Swal.showValidationMessage('Keterangan reject wajib diisi.');
                        return false;
                    }

                    return reason;
                },
            });
            if (!result.isConfirmed) {
                this.diskonBusy = false;
                return;
            }

            this.loading = true;
            const res = await fetch(@json($diskonRejectUrl), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    rejected_reason: result.value || '',
                }),
            });
            if (!await this.handleDiskonVerifikasiResponse(res, 'Gagal reject.')) {
                return;
            }
        },
    };
}
</script>
@endpush
@endsection
