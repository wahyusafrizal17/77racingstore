<?php
    $isWipPage = request()->routeIs('speedshop.wip');
    $filterBaseClass = 'h-8 w-full rounded-lg border border-gray-200 bg-white px-2.5 text-[13px] font-normal text-gray-700 placeholder-gray-400 outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/40';
?>

<?php $__env->startSection('title', $isWipPage ? 'Work In Progress' : 'Work Order'); ?>

<?php $__env->startSection('content'); ?>
<?php if($isWipPage): ?>
<?php $__env->startPush('styles'); ?>
<?php echo $__env->make('partials.speedshop.dashboard-load-animations', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('partials.speedshop.dashboard-manager-template-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('partials.speedshop.work-order-create-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<style>
.ss-dashboard .wip-summary-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 20px;
}
@media (max-width: 768px) {
  .ss-dashboard .wip-summary-grid { grid-template-columns: 1fr; }
}
</style>
<?php $__env->stopPush(); ?>

<?php
    $wipPeriodLabel = match ($mode ?? 'harian') {
        'harian' => \Carbon\Carbon::parse($selectedDate ?? now())->translatedFormat('d M Y'),
        'bulanan' => \Carbon\Carbon::parse(($selectedMonth ?? now()->format('Y-m')).'-01')->translatedFormat('F Y'),
        'tahunan' => (string) ($selectedYear ?? now()->year),
        'range' => ($dari ?? now())->translatedFormat('d M Y').' – '.($sampai ?? now())->translatedFormat('d M Y'),
        default => ($dari ?? now())->translatedFormat('d M Y'),
    };
?>
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
            <p class="ss-dash-loading-text">Memuat work in progress…</p>
            <p class="ss-dash-loading-sub">Menyiapkan data periode terpilih</p>
        </div>
    </div>

    <div class="ss-dash-content space-y-5">
    <?php echo $__env->make('partials.speedshop.dashboard-filter-strip', [
        'filterAction' => route('speedshop.wip'),
        'dashboardFilterExports' => [],
        'dashboardFilterQueryKeys' => ['mode', 'tanggal', 'bulan', 'tahun', 'dari', 'sampai'],
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="wip-summary-grid ss-dash-enter" style="--ss-delay: 80ms">
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 0">
            <div class="pay-card-top">
                <span class="pay-card-label">Antrian</span>
                <span class="pay-icon amber">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="pay-amount"><?php echo e(number_format($wipSummaryAntri ?? 0)); ?></div>
            <p class="pay-period">WO menunggu cetak / assign mekanik</p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 1">
            <div class="pay-card-top">
                <span class="pay-card-label">Estimasi Total</span>
                <span class="pay-icon purple">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="pay-amount"><span class="curr">Rp</span><?php echo e(number_format($wipSummaryEstimasi ?? 0, 0, ',', '.')); ?></div>
            <p class="pay-period">Akumulasi estimasi biaya WO antrian</p>
        </div>
    </div>

    <div class="panel-card panel-card-flush ss-dash-enter" style="--ss-delay: 120ms">
        <div class="flex flex-col gap-3 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">Work In Progress</h2>
                <p class="mt-1 text-[13px] text-gray-500">Daftar work order aktif speedshop · <?php echo e($wipPeriodLabel); ?></p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <form method="GET" action="<?php echo e(route('speedshop.wip')); ?>">
                <input type="hidden" name="mode" value="<?php echo e($mode ?? 'harian'); ?>">
                <?php if(($mode ?? 'harian') === 'harian'): ?>
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
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Kode</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Tanggal</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Pelanggan</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No Polisi</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Sumber</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Kategori</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Status</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500"></th>
                        </tr>
                        <tr class="border-t border-gray-200 bg-[#fbf8f9]">
                            <th class="px-5 py-2"></th>
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
                            <th class="px-5 py-2"></th>
                            <th class="px-5 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    <?php $__empty_1 = true; $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $o): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="transition hover:bg-[#fcfafb]">
                        <td class="px-5 py-4 text-gray-500"><?php echo e($orders->firstItem() + $i); ?></td>
                        <td class="px-5 py-4 font-semibold text-gray-900"><?php echo e($o->kode_servis); ?></td>
                        <td class="px-5 py-4 text-gray-600"><?php echo e($o->tanggal_masuk->format('d M Y')); ?></td>
                        <td class="px-5 py-4 text-gray-700"><?php echo e($o->pelanggan->nama ?? '-'); ?></td>
                        <td class="px-5 py-4 text-gray-600"><?php echo e($o->kendaraan?->nomor_polisi ?? '-'); ?></td>
                        <td class="px-5 py-4 text-gray-600"><?php echo e($o->sumber_kedatangan ? (\App\Models\ServiceOrder::SUMBER_KEDATANGAN[$o->sumber_kedatangan] ?? $o->sumber_kedatangan) : '-'); ?></td>
                        <td class="px-5 py-4 text-gray-600"><?php echo e($o->kategori_service ? (\App\Models\ServiceOrder::KATEGORI_SERVICE[$o->kategori_service] ?? $o->kategori_service) : '-'); ?></td>
                        <td class="px-5 py-4">
                            <?php if($o->status === 'antri'): ?>
                                <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">Antrian</span>
                            <?php elseif($o->status === 'proses'): ?>
                                <span class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">Dikerjakan</span>
                            <?php else: ?>
                                <span class="inline-flex items-center rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs font-semibold text-gray-700"><?php echo e(ucfirst($o->status)); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="px-5 py-4">
                            <a href="<?php echo e(route('speedshop.wip.show', $o)); ?>" class="btn-detail">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                Detail
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="9" class="px-5 py-12 text-center text-gray-400">Belum ada work order untuk filter ini.</td></tr>
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

<?php else: ?>
<div class="space-y-5">
    <nav class="flex items-center gap-2 text-sm text-gray-500">
        <a href="<?php echo e(route('speedshop.dashboard')); ?>" class="transition hover:text-gray-700">Home</a>
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <span class="font-medium text-gray-900">Work Order</span>
    </nav>

    <div class="rounded-2xl border border-gray-200 bg-white p-5">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">Work Order</h2>
                <p class="mt-1 text-[13px] text-gray-500">Daftar work order (service order) speedshop</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="<?php echo e(route('speedshop.work-orders.create')); ?>" class="inline-flex h-10 items-center gap-2 rounded-xl bg-brand px-5 text-sm font-medium text-brand-ink shadow-lg shadow-brand/35 transition hover:bg-brand-hover active:scale-[0.98]">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Buat Work Order
                </a>
            </div>
        </div>

        <div class="mt-5 overflow-x-auto rounded-xl border border-gray-200">
            <form method="GET">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="bg-[#f7f3f4]">
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No.</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Kode</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Tanggal</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Pelanggan</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No Polisi</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Sumber</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Kategori</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Status</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500"></th>
                        </tr>
                        <tr class="border-t border-gray-200 bg-[#fbf8f9]">
                            <th class="px-5 py-2"></th>
                            <th class="px-5 py-2">
                                <input name="kode" value="<?php echo e(request('kode')); ?>" placeholder="Cari kode..." class="<?php echo e($filterBaseClass); ?>" onchange="this.form.submit()">
                            </th>
                            <th class="px-5 py-2">
                                <input type="date" name="tanggal" value="<?php echo e(request('tanggal')); ?>" class="<?php echo e($filterBaseClass); ?>" onchange="this.form.submit()">
                            </th>
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
                            <th class="px-5 py-2">
                                <select name="status" class="<?php echo e($filterBaseClass); ?>" onchange="this.form.submit()">
                                    <option value="">Semua</option>
                                    <option value="antri" <?php echo e(request('status') === 'antri' ? 'selected' : ''); ?>>Antrian</option>
                                    <option value="proses" <?php echo e(request('status') === 'proses' ? 'selected' : ''); ?>>Dikerjakan</option>
                                    <option value="selesai" <?php echo e(request('status') === 'selesai' ? 'selected' : ''); ?>>Selesai</option>
                                    <option value="dibatalkan" <?php echo e(request('status') === 'dibatalkan' ? 'selected' : ''); ?>>Dibatalkan</option>
                                </select>
                            </th>
                            <th class="px-5 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    <?php $__empty_1 = true; $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $o): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="transition hover:bg-[#fcfafb]">
                        <td class="px-5 py-4 text-gray-500"><?php echo e($orders->firstItem() + $i); ?></td>
                        <td class="px-5 py-4 font-semibold text-gray-900"><?php echo e($o->kode_servis); ?></td>
                        <td class="px-5 py-4 text-gray-600"><?php echo e($o->tanggal_masuk->format('d M Y')); ?></td>
                        <td class="px-5 py-4 text-gray-700"><?php echo e($o->pelanggan->nama ?? '-'); ?></td>
                        <td class="px-5 py-4 text-gray-600"><?php echo e($o->kendaraan?->nomor_polisi ?? '-'); ?></td>
                        <td class="px-5 py-4 text-gray-600"><?php echo e($o->sumber_kedatangan ? (\App\Models\ServiceOrder::SUMBER_KEDATANGAN[$o->sumber_kedatangan] ?? $o->sumber_kedatangan) : '-'); ?></td>
                        <td class="px-5 py-4 text-gray-600"><?php echo e($o->kategori_service ? (\App\Models\ServiceOrder::KATEGORI_SERVICE[$o->kategori_service] ?? $o->kategori_service) : '-'); ?></td>
                        <td class="px-5 py-4">
                            <?php if($o->status === 'antri'): ?>
                                <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">Antrian</span>
                            <?php elseif($o->status === 'proses'): ?>
                                <span class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">Dikerjakan</span>
                            <?php elseif($o->status === 'selesai'): ?>
                                <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Selesai</span>
                            <?php else: ?>
                                <span class="inline-flex items-center rounded-full border border-rose-200 bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700"><?php echo e(ucfirst($o->status)); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="px-5 py-4">
                            <a href="<?php echo e(route('speedshop.wip.show', $o)); ?>" class="btn-detail">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                Detail
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="9" class="px-5 py-12 text-center text-gray-400">Belum ada work order.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </form>
        </div>

        <?php if($orders->hasPages()): ?>
        <div class="mt-5"><?php echo e($orders->links()); ?></div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.speedshop', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/wahyusafrizal/work/Github/77racingstore/resources/views/speedshop/work-order/index.blade.php ENDPATH**/ ?>