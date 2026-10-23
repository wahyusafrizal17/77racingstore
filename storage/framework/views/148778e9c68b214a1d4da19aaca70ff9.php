<?php $__env->startSection('title', 'Rekap Pendapatan'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $fmt = fn ($v) => number_format((float) $v, 0, ',', '.');
    $speedshopLabel = ! empty($selectedLaporanSpeedshopName)
        ? strtoupper($selectedLaporanSpeedshopName)
        : (! empty($laporanAllSpeedshops)
            ? 'SEMUA SPEEDSHOP'
            : strtoupper($speedshop?->nama ?? 'SEMUA OUTLET'));

    $laporanFilterQueryKeys = ['mode', 'tanggal', 'bulan', 'tahun', 'dari', 'sampai'];
    if (auth()->user()?->isOwner()) {
        $laporanFilterQueryKeys[] = 'speedshop_id';
    }

    $hasActiveFilters = count(request()->query()) > 0;

    $summaryOmzetKotor = (float) ($summaryOmzetKotor ?? 0);
    $summaryTotalBiaya = (float) ($summaryTotalBiaya ?? 0);
    $summaryTotalBiayaSparepart = (float) ($summaryTotalBiayaSparepart ?? 0);
    $summaryTotalBiayaBop = (float) ($summaryTotalBiayaBop ?? 0);
    $summaryGrossProfit = (float) ($summaryGrossProfit ?? 0);
?>

<?php $__env->startPush('styles'); ?>
<?php echo $__env->make('partials.speedshop.dashboard-load-animations', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('partials.speedshop.dashboard-manager-template-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('partials.speedshop.work-order-create-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<style>
.ss-dashboard .summary-laporan-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 20px;
}
@media (max-width: 900px) {
  .ss-dashboard .summary-laporan-grid { grid-template-columns: 1fr; }
}
.ss-dashboard .pay-icon.emerald { background: rgba(16,185,129,0.10); color: #059669; }
.ss-dashboard .pay-icon.red { background: rgba(239,68,68,0.10); color: #DC2626; }
.ss-dashboard .pay-icon.gray { background: rgba(107,114,128,0.10); color: #4B5563; }
</style>
<?php $__env->stopPush(); ?>

<div
    class="ss-dashboard ss-dash-page ss-wo-create"
    x-data="{ loading: false }"
    x-init="$nextTick(() => { requestAnimationFrame(() => $el.classList.add('is-ready')); })"
    :class="loading && 'is-loading'"
>
<div class="ss-dash-content space-y-5">
    <?php echo $__env->make('partials.speedshop.dashboard-filter-strip', [
        'filterAction' => route('speedshop.laporan.summary'),
        'dashboardFilterExports' => [],
        'dashboardFilterQueryKeys' => $laporanFilterQueryKeys,
        'dashboardSpeedshopOptions' => $laporanSpeedshopOptions ?? collect(),
        'selectedDashboardSpeedshopId' => $selectedLaporanSpeedshopId ?? null,
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>


    <div class="summary-laporan-grid ss-dash-enter" style="--ss-delay: 80ms">
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 0">
            <div class="pay-card-top">
                <span class="pay-card-label">Pendapatan Kotor</span>
                <span class="pay-icon emerald">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="pay-amount text-[22px] sm:text-[26px]">Rp <?php echo e($fmt($summaryOmzetKotor)); ?></div>
            <p class="pay-period">Periode terpilih</p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 1">
            <div class="pay-card-top">
                <span class="pay-card-label">Total Biaya</span>
                <span class="pay-icon red">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                </span>
            </div>
            <div class="pay-amount text-[22px] sm:text-[26px]">Rp <?php echo e($fmt($summaryTotalBiaya)); ?></div>
            <p class="pay-period">HPP <?php echo e($fmt($summaryTotalBiayaSparepart)); ?> + BOP <?php echo e($fmt($summaryTotalBiayaBop)); ?></p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 2">
            <div class="pay-card-top">
                <span class="pay-card-label">Gross Profit Before HPP</span>
                <span class="pay-icon <?php echo e($summaryGrossProfit >= 0 ? 'gray' : 'red'); ?>">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                </span>
            </div>
            <div class="pay-amount text-[22px] sm:text-[26px] <?php echo e($summaryGrossProfit < 0 ? 'text-red-700' : ''); ?>">Rp <?php echo e($fmt($summaryGrossProfit)); ?></div>
            <p class="pay-period">Periode terpilih</p>
        </div>
    </div>

    <div class="panel-card panel-card-flush ss-dash-enter" style="--ss-delay: 120ms">
        <div class="border-b border-gray-100 px-5 py-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">Total pendapatan <?php echo e($speedshopLabel); ?></h2>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="<?php echo e(route('speedshop.laporan.summary.export', request()->only($laporanFilterQueryKeys))); ?>" class="btn-export">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Export Excel
                    </a>
                    <a href="<?php echo e(route('speedshop.laporan.summary.export-pdf', request()->only($laporanFilterQueryKeys))); ?>" target="_blank" rel="noopener noreferrer" class="btn-export-pdf">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 13h6M9 17h4M9 9h1"/></svg>
                        Export PDF
                    </a>
                </div>
            </div>
            <p class="mt-1 text-[13px] text-gray-500">
                Periode <?php echo e($dari->format('d M Y')); ?> – <?php echo e($sampai->format('d M Y')); ?>

            </p>
        </div>

        <div class="max-h-[72vh] overflow-x-auto overflow-y-auto overscroll-x-contain">
            <table class="w-full min-w-[1450px] text-left text-sm">
                <thead class="sticky top-0 z-10 shadow-sm">
                    <tr style="background-color:#f7f3f4">
                        <th class="border-b border-gray-200 px-5 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">No</th>
                        <th class="border-b border-gray-200 px-5 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Tanggal</th>
                        <th class="border-b border-gray-200 px-5 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Keterangan</th>
                        <th colspan="2" class="border-b border-gray-200 px-5 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Pendapatan kotor</th>
                        <th colspan="2" class="border-b border-gray-200 px-5 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Biaya operasional</th>
                        <th colspan="2" class="border-b border-gray-200 px-5 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Gross profit</th>
                        <th colspan="2" class="border-b border-gray-200 px-5 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Total gross profit before HPP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php $__empty_1 = true; $__currentLoopData = $dailyReports; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dayIdx => $day): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php $__currentLoopData = $day->rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rowIdx => $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr class="transition hover:bg-rose-50/50 <?php echo e($rowIdx > 0 ? 'bg-gray-50/40' : ''); ?>">
                                <?php if($rowIdx === 0): ?>
                                    <td rowspan="<?php echo e($day->rows->count()); ?>" class="border-b border-gray-100 px-5 py-4 text-center text-sm font-medium tabular-nums text-gray-500"><?php echo e($dayIdx + 1); ?></td>
                                <?php endif; ?>
                                <td class="border-b border-gray-100 px-5 py-4 text-center text-sm tabular-nums text-gray-800"><?php echo e(\Carbon\Carbon::parse($day->tanggal)->locale('id')->translatedFormat('d F Y')); ?></td>
                                <td class="border-b border-gray-100 px-5 py-4 text-sm font-semibold text-gray-900"><?php echo e($row->label); ?></td>

                                <?php if($rowIdx === 0): ?>
                                    <td rowspan="<?php echo e($day->rows->count()); ?>" class="border-b border-gray-100 px-5 py-4 text-right text-sm text-gray-600">Rp</td>
                                    <td rowspan="<?php echo e($day->rows->count()); ?>" class="border-b border-gray-100 px-5 py-4 text-right text-sm font-semibold tabular-nums text-gray-900"><?php echo e($fmt($day->pendapatan_kotor)); ?></td>
                                <?php endif; ?>

                                <td class="border-b border-gray-100 px-5 py-4 text-right text-sm text-gray-600"><?php echo e((float) ($row->biaya_total ?? 0) > 0 ? 'Rp' : ''); ?></td>
                                <td class="border-b border-gray-100 px-5 py-4 text-right text-sm font-semibold tabular-nums text-gray-900">
                                    <?php if((float) ($row->biaya_total ?? 0) > 0): ?>
                                        <?php echo e($fmt($row->biaya_total)); ?>

                                    <?php endif; ?>
                                </td>

                                <td class="border-b border-gray-100 px-5 py-4 text-right text-sm text-gray-600">Rp</td>
                                <td class="border-b border-gray-100 px-5 py-4 text-right text-sm font-semibold tabular-nums <?php echo e($row->gross_profit >= 0 ? 'text-gray-900' : 'text-red-700'); ?>">
                                    <?php echo e($row->gross_profit < 0 ? '-' : ''); ?><?php echo e($fmt(abs($row->gross_profit))); ?>

                                </td>

                                <?php if($rowIdx === 0): ?>
                                    <td rowspan="<?php echo e($day->rows->count()); ?>" class="border-b border-gray-100 px-5 py-4 text-right text-sm text-gray-600">Rp</td>
                                    <td rowspan="<?php echo e($day->rows->count()); ?>" class="border-b border-gray-100 px-5 py-4 text-right text-sm font-bold tabular-nums <?php echo e($day->total_gross_profit_before_hpp >= 0 ? 'text-gray-900' : 'text-red-700'); ?>">
                                        <?php echo e($day->total_gross_profit_before_hpp < 0 ? '-' : ''); ?><?php echo e($fmt(abs($day->total_gross_profit_before_hpp))); ?>

                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="11" class="px-5 py-12 text-center text-sm text-gray-500">
                                <p class="font-medium text-gray-700">Tidak ada data pada periode ini.</p>
                                <p class="mt-1 text-xs text-gray-400">Ubah filter periode lalu terapkan lagi.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.speedshop', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/wahyusafrizal/work/Github/77racingstore/resources/views/speedshop/laporan/summary.blade.php ENDPATH**/ ?>