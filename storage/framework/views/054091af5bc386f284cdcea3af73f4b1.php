<?php
    $verifikasiDiskonWorkOrder = $verifikasiDiskonWorkOrder ?? false;
    $listAllSpeedshops = $listAllSpeedshops ?? false;
    $diskonStatusFilter = request('status', '');
    $filterBaseClass = 'h-8 w-full rounded-lg border border-gray-200 bg-white px-2.5 text-[13px] font-normal text-gray-700 placeholder-gray-400 outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/40';
    $emptyColspan = $listAllSpeedshops ? 10 : 9;
    $indexRoute = $verifikasiDiskonWorkOrder ? 'speedshop.verifikasi.diskon-work-order.index' : 'speedshop.selesaikan-transaksi.index';
    $defaultPeriodMode = $verifikasiDiskonWorkOrder ? 'bulanan' : 'harian';
    $filterQueryKeys = ['mode', 'tanggal', 'bulan', 'tahun', 'dari', 'sampai', 'kode', 'pelanggan', 'no_polisi', 'sumber', 'kategori_service'];
    if ($verifikasiDiskonWorkOrder) {
        $filterQueryKeys[] = 'status';
    }
    $selesaikanPeriodLabel = match ($mode ?? $defaultPeriodMode) {
        'harian' => \Carbon\Carbon::parse($selectedDate ?? now())->translatedFormat('d M Y'),
        'bulanan' => \Carbon\Carbon::parse(($selectedMonth ?? now()->format('Y-m')).'-01')->translatedFormat('F Y'),
        'tahunan' => (string) ($selectedYear ?? now()->year),
        'range' => ($dari ?? now())->translatedFormat('d M Y').' – '.($sampai ?? now())->translatedFormat('d M Y'),
        default => ($dari ?? now())->translatedFormat('d M Y'),
    };
?>

<?php $__env->startSection('title', $verifikasiDiskonWorkOrder ? 'Verifikasi Diskon WO' : 'Selesaikan Transaksi'); ?>

<?php $__env->startSection('content'); ?>
<?php $__env->startPush('styles'); ?>
<?php echo $__env->make('partials.speedshop.dashboard-load-animations', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('partials.speedshop.dashboard-manager-template-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('partials.speedshop.work-order-create-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<style>
.ss-dashboard .selesaikan-summary-grid {
  display: grid;
  gap: 12px;
  margin-bottom: 20px;
}
.ss-dashboard .selesaikan-summary-grid.cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
.ss-dashboard .selesaikan-summary-grid.cols-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
@media (max-width: 1024px) {
  .ss-dashboard .selesaikan-summary-grid.cols-4 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 640px) {
  .ss-dashboard .selesaikan-summary-grid.cols-2,
  .ss-dashboard .selesaikan-summary-grid.cols-4 { grid-template-columns: 1fr; }
}
.ss-dashboard .pay-icon.green { background: rgba(16,185,129,0.10); color: #10B981; }
.ss-dashboard .pay-icon.red { background: rgba(239,68,68,0.10); color: #EF4444; }
</style>
<?php $__env->stopPush(); ?>

<div
    class="ss-dashboard ss-dash-page ss-wo-create"
    x-data="selesaikanTransaksiIndex()"
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
            <p class="ss-dash-loading-text"><?php echo e($verifikasiDiskonWorkOrder ? 'Memuat verifikasi diskon…' : 'Memuat selesaikan transaksi…'); ?></p>
            <p class="ss-dash-loading-sub">Menyiapkan data periode terpilih</p>
        </div>
    </div>

    <div class="ss-dash-content space-y-5">
    <?php echo $__env->make('partials.speedshop.dashboard-filter-strip', [
        'filterAction' => route($indexRoute),
        'dashboardFilterExports' => [],
        'dashboardFilterQueryKeys' => $filterQueryKeys,
        'filterHiddenInputs' => array_filter([
            'status' => $verifikasiDiskonWorkOrder ? request('status') : null,
            'kode' => request('kode'),
            'pelanggan' => request('pelanggan'),
            'no_polisi' => request('no_polisi'),
            'sumber' => request('sumber'),
            'kategori_service' => request('kategori_service'),
        ], fn ($v) => $v !== null && $v !== ''),
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <?php if($verifikasiDiskonWorkOrder): ?>
    <div class="selesaikan-summary-grid cols-4 ss-dash-enter" style="--ss-delay: 80ms">
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 0">
            <div class="pay-card-top">
                <span class="pay-card-label">Total Pengajuan</span>
                <span class="pay-icon purple">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </span>
            </div>
            <div class="pay-amount"><?php echo e(number_format($totalAll ?? 0)); ?></div>
            <p class="pay-period">Semua pengajuan · <?php echo e($selesaikanPeriodLabel); ?></p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 1">
            <div class="pay-card-top">
                <span class="pay-card-label">Menunggu</span>
                <span class="pay-icon amber">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="pay-amount"><?php echo e(number_format($totalPending ?? 0)); ?></div>
            <p class="pay-period">Belum diverifikasi · <?php echo e($selesaikanPeriodLabel); ?></p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 2">
            <div class="pay-card-top">
                <span class="pay-card-label">Disetujui</span>
                <span class="pay-icon green">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </span>
            </div>
            <div class="pay-amount"><?php echo e(number_format($totalApproved ?? 0)); ?></div>
            <p class="pay-period">Diskon disetujui · <?php echo e($selesaikanPeriodLabel); ?></p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 3">
            <div class="pay-card-top">
                <span class="pay-card-label">Ditolak</span>
                <span class="pay-icon red">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </span>
            </div>
            <div class="pay-amount"><?php echo e(number_format($totalRejected ?? 0)); ?></div>
            <p class="pay-period">Diskon ditolak · <?php echo e($selesaikanPeriodLabel); ?></p>
        </div>
    </div>
    <?php else: ?>
    <div class="selesaikan-summary-grid cols-2 ss-dash-enter" style="--ss-delay: 80ms">
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 0">
            <div class="pay-card-top">
                <span class="pay-card-label">Dikerjakan</span>
                <span class="pay-icon blue">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </span>
            </div>
            <div class="pay-amount"><?php echo e(number_format($selesaikanSummaryProses ?? 0)); ?></div>
            <p class="pay-period">WO status proses periode terpilih</p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 1">
            <div class="pay-card-top">
                <span class="pay-card-label">Estimasi Total</span>
                <span class="pay-icon purple">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="pay-amount"><span class="curr">Rp</span><?php echo e(number_format($selesaikanSummaryEstimasi ?? 0, 0, ',', '.')); ?></div>
            <p class="pay-period">Akumulasi estimasi WO dikerjakan</p>
        </div>
    </div>
    <?php endif; ?>

    <div class="panel-card panel-card-flush ss-dash-enter" style="--ss-delay: 120ms">
        <div class="flex flex-col gap-3 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900"><?php echo e($verifikasiDiskonWorkOrder ? 'Verifikasi Diskon WO' : 'Selesaikan Transaksi'); ?></h2>
                <p class="mt-1 text-[13px] text-gray-500">
                    <?php if($verifikasiDiskonWorkOrder): ?>
                        Daftar pengajuan diskon &gt; 10% · <?php echo e($selesaikanPeriodLabel); ?>

                        <?php if($listAllSpeedshops): ?>
                            · Semua speedshop
                        <?php endif; ?>
                    <?php else: ?>
                        Work order status proses untuk penyelesaian transaksi · <?php echo e($selesaikanPeriodLabel); ?>

                        <?php if($listAllSpeedshops): ?>
                            · Semua speedshop
                        <?php endif; ?>
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <form method="GET" action="<?php echo e(route($indexRoute)); ?>" @submit="loading = true">
                <input type="hidden" name="mode" value="<?php echo e($mode ?? $defaultPeriodMode); ?>">
                <?php if(($mode ?? $defaultPeriodMode) === 'harian'): ?>
                <input type="hidden" name="tanggal" value="<?php echo e($selectedDate ?? now()->toDateString()); ?>">
                <?php elseif(($mode ?? '') === 'bulanan'): ?>
                <input type="hidden" name="bulan" value="<?php echo e($selectedMonth ?? now()->format('Y-m')); ?>">
                <?php elseif(($mode ?? '') === 'tahunan'): ?>
                <input type="hidden" name="tahun" value="<?php echo e($selectedYear ?? now()->year); ?>">
                <?php elseif(($mode ?? '') === 'range'): ?>
                <input type="hidden" name="dari" value="<?php echo e($selectedDari ?? ($dari ?? now())->format('Y-m-d')); ?>">
                <input type="hidden" name="sampai" value="<?php echo e($selectedSampai ?? ($sampai ?? now())->format('Y-m-d')); ?>">
                <?php endif; ?>
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="bg-[#f7f3f4]">
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No.</th>
                            <?php if($listAllSpeedshops): ?>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Speedshop</th>
                            <?php endif; ?>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Kode</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Tanggal</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Pelanggan</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No Polisi</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Sumber</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Kategori</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500"><?php echo e($verifikasiDiskonWorkOrder ? 'Status Verifikasi' : 'Status'); ?></th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500"></th>
                        </tr>
                        <tr class="border-t border-gray-200 bg-[#fbf8f9]">
                            <th class="px-5 py-2"></th>
                            <?php if($listAllSpeedshops): ?>
                            <th class="px-5 py-2"></th>
                            <?php endif; ?>
                            <th class="px-5 py-2">
                                <input name="kode" value="<?php echo e(request('kode')); ?>" placeholder="Cari kode..." class="<?php echo e($filterBaseClass); ?>" onchange="this.form.submit()">
                            </th>
                            <th class="px-5 py-2"></th>
                            <th class="px-5 py-2">
                                <input name="pelanggan" value="<?php echo e(request('pelanggan')); ?>" placeholder="Cari pelanggan..." class="<?php echo e($filterBaseClass); ?>" onchange="this.form.submit()">
                            </th>
                            <th class="px-5 py-2">
                                <input name="no_polisi" value="<?php echo e(request('no_polisi')); ?>" placeholder="Cari no polisi..." class="<?php echo e($filterBaseClass); ?>" onchange="this.form.submit()">
                            </th>
                            <th class="px-5 py-2">
                                <select name="sumber" class="<?php echo e($filterBaseClass); ?>" onchange="this.form.submit()">
                                    <option value="">Semua</option>
                                    <?php $__currentLoopData = \App\Models\ServiceOrder::SUMBER_KEDATANGAN; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($val); ?>" <?php if(request('sumber') === (string) $val): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </th>
                            <th class="px-5 py-2">
                                <select name="kategori_service" class="<?php echo e($filterBaseClass); ?>" onchange="this.form.submit()">
                                    <option value="">Semua</option>
                                    <?php $__currentLoopData = \App\Models\ServiceOrder::KATEGORI_SERVICE; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($val); ?>" <?php if(request('kategori_service') === (string) $val): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </th>
                            <?php if($verifikasiDiskonWorkOrder): ?>
                            <th class="px-5 py-2">
                                <select name="status" class="<?php echo e($filterBaseClass); ?>" onchange="this.form.submit()">
                                    <option value="" <?php if($diskonStatusFilter === ''): echo 'selected'; endif; ?>>Semua</option>
                                    <option value="pending" <?php if($diskonStatusFilter === 'pending'): echo 'selected'; endif; ?>>Menunggu</option>
                                    <option value="approved" <?php if($diskonStatusFilter === 'approved'): echo 'selected'; endif; ?>>Disetujui</option>
                                    <option value="rejected" <?php if($diskonStatusFilter === 'rejected'): echo 'selected'; endif; ?>>Ditolak</option>
                                </select>
                            </th>
                            <?php else: ?>
                            <th class="px-5 py-2"></th>
                            <?php endif; ?>
                            <th class="px-5 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    <?php $__empty_1 = true; $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $o): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="transition hover:bg-[#fcfafb]">
                        <td class="px-5 py-4 text-gray-500"><?php echo e($orders->firstItem() + $i); ?></td>
                        <?php if($listAllSpeedshops): ?>
                        <td class="px-5 py-4 whitespace-nowrap text-gray-700"><?php echo e($o->speedshop?->nama ?? '-'); ?></td>
                        <?php endif; ?>
                        <td class="px-5 py-4 font-semibold text-gray-900"><?php echo e($o->kode_servis); ?></td>
                        <td class="px-5 py-4 text-gray-600"><?php echo e($o->tanggal_masuk->format('d M Y')); ?></td>
                        <td class="px-5 py-4 text-gray-700"><?php echo e($o->pelanggan->nama ?? '-'); ?></td>
                        <td class="px-5 py-4 text-gray-600"><?php echo e($o->kendaraan?->nomor_polisi ?? '-'); ?></td>
                        <td class="px-5 py-4 text-gray-600"><?php echo e($o->sumber_kedatangan ? (\App\Models\ServiceOrder::SUMBER_KEDATANGAN[$o->sumber_kedatangan] ?? $o->sumber_kedatangan) : '-'); ?></td>
                        <td class="px-5 py-4 text-gray-600"><?php echo e($o->kategori_service ? (\App\Models\ServiceOrder::KATEGORI_SERVICE[$o->kategori_service] ?? $o->kategori_service) : '-'); ?></td>
                        <td class="px-5 py-4">
                            <?php if($verifikasiDiskonWorkOrder): ?>
                                <?php
                                    $diskonStatus = $o->diskon_verifikasi_status;
                                    $diskonStatusLabel = match ($diskonStatus) {
                                        'pending' => 'Menunggu',
                                        'approved' => 'Disetujui',
                                        'rejected' => 'Ditolak',
                                        default => ucfirst((string) $diskonStatus),
                                    };
                                    $diskonStatusClass = match ($diskonStatus) {
                                        'pending' => 'border-amber-200 bg-amber-50 text-amber-800',
                                        'approved' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
                                        'rejected' => 'border-red-200 bg-red-50 text-red-800',
                                        default => 'border-gray-200 bg-gray-50 text-gray-700',
                                    };
                                ?>
                                <span class="inline-flex items-center rounded-lg border px-2.5 py-1 text-xs font-semibold <?php echo e($diskonStatusClass); ?>"><?php echo e($diskonStatusLabel); ?></span>
                            <?php else: ?>
                                <span class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">Dikerjakan</span>
                            <?php endif; ?>
                        </td>
                        <td class="whitespace-nowrap px-5 py-4">
                            <div class="flex flex-nowrap items-center justify-end gap-2">
                                <?php if(!$verifikasiDiskonWorkOrder && $o->status === 'proses'): ?>
                                    <button
                                        type="button"
                                        title="Panggil ke monitor"
                                        aria-label="Panggil antrian ke monitor TV"
                                        @click.prevent="panggil(<?php echo e((int) $o->id); ?>, <?php echo \Illuminate\Support\Js::from(route('speedshop.selesaikan-transaksi.panggil-antrian', $o))->toHtml() ?>)"
                                        :disabled="loadingId !== null"
                                        class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border-2 border-red-500 bg-white text-red-600 shadow-sm transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        <svg x-show="loadingId !== <?php echo e((int) $o->id); ?>" class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V18.118a1 1 0 001.447.894l4.853-2.647V8.235L12.447 4.106A1 1 0 0011 5.882z"/><path stroke-linecap="round" stroke-linejoin="round" d="M5.5 10.5v3a1 1 0 001.106.895L9 15.118V8.882L6.606 7.605A1 1 0 005.5 8.5v2z"/></svg>
                                        <svg x-show="loadingId === <?php echo e((int) $o->id); ?>" class="h-4 w-4 shrink-0 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                    </button>
                                <?php endif; ?>
                                <a href="<?php echo e($verifikasiDiskonWorkOrder ? route('speedshop.verifikasi.diskon-work-order.show', $o) : route('speedshop.selesaikan-transaksi.show', $o)); ?>" class="btn-detail shrink-0">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <?php echo e($verifikasiDiskonWorkOrder ? (($o->diskon_verifikasi_status ?? null) === 'pending' ? 'Verifikasi' : 'Detail') : 'Selesaikan'); ?>

                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="<?php echo e($emptyColspan); ?>" class="px-5 py-12 text-center text-gray-400">
                        <?php if($verifikasiDiskonWorkOrder): ?>
                            Belum ada data verifikasi diskon work order untuk filter ini.
                        <?php else: ?>
                            Tidak ada work order dikerjakan untuk periode ini.
                        <?php endif; ?>
                    </td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </form>
        </div>

        <?php if($orders->hasPages()): ?>
        <div class="border-t border-gray-100 px-5 py-4"><?php echo e($orders->links()); ?></div>
        <?php endif; ?>
    </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    function selesaikanTransaksiIndex() {
        return {
            loading: false,
            loadingId: null,
            async panggil(id, url) {
                if (this.loadingId !== null) return;
                const meta = document.querySelector('meta[name="csrf-token"]');
                const csrf = meta ? (meta.getAttribute('content') || '') : '';
                this.loadingId = id;
                try {
                    const res = await fetch(url, {
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
                    this.loadingId = null;
                }
            },
        };
    }
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.speedshop', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/wahyusafrizal/work/Github/77racingstore/resources/views/speedshop/selesaikan-transaksi/index.blade.php ENDPATH**/ ?>