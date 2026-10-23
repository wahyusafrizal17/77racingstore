<?php $__env->startSection('title', 'Laporan Biaya'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $fmt = fn ($v) => number_format((float) $v, 0, ',', '.');

    $laporanFilterQueryKeys = ['mode', 'tanggal', 'bulan', 'tahun', 'dari', 'sampai'];
    if (auth()->user()?->isOwner()) {
        $laporanFilterQueryKeys[] = 'speedshop_id';
    }

    $hasActiveFilters = count(request()->query()) > 0;
    $profitBersih = (float) ($periode['profit_bersih'] ?? 0);
?>

<?php $__env->startPush('styles'); ?>
<?php echo $__env->make('partials.speedshop.dashboard-load-animations', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('partials.speedshop.dashboard-manager-template-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('partials.speedshop.work-order-create-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<style>
.ss-dashboard .biaya-laporan-summary-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 20px;
}
@media (max-width: 900px) {
  .ss-dashboard .biaya-laporan-summary-grid { grid-template-columns: 1fr; }
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
        'filterAction' => route('speedshop.laporan.biaya'),
        'dashboardFilterExports' => [],
        'dashboardFilterQueryKeys' => $laporanFilterQueryKeys,
        'dashboardSpeedshopOptions' => $laporanSpeedshopOptions ?? collect(),
        'selectedDashboardSpeedshopId' => $selectedLaporanSpeedshopId ?? null,
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>


    <div class="biaya-laporan-summary-grid ss-dash-enter" style="--ss-delay: 80ms">
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 0">
            <div class="pay-card-top">
                <span class="pay-card-label">Omzet Kotor</span>
                <span class="pay-icon emerald">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="pay-amount text-[22px] sm:text-[26px]">Rp <?php echo e($fmt($periode['omzet_kotor'])); ?></div>
            <p class="pay-period">Periode terpilih</p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 1">
            <div class="pay-card-top">
                <span class="pay-card-label">Total Biaya (HPP)</span>
                <span class="pay-icon red">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                </span>
            </div>
            <div class="pay-amount text-[22px] sm:text-[26px]">Rp <?php echo e($fmt($totalBiaya)); ?></div>
            <p class="pay-period">HPP <?php echo e($fmt($totalBiayaSparepart ?? 0)); ?> + BOP <?php echo e($fmt(($totalBiayaOperasional ?? 0) + ($totalBiayaNonOperasional ?? 0))); ?></p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 2">
            <div class="pay-card-top">
                <span class="pay-card-label">Gross Profit</span>
                <span class="pay-icon <?php echo e($profitBersih >= 0 ? 'gray' : 'red'); ?>">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                </span>
            </div>
            <div class="pay-amount text-[22px] sm:text-[26px] <?php echo e($profitBersih < 0 ? 'text-red-700' : ''); ?>">Rp <?php echo e($fmt($profitBersih)); ?></div>
            <p class="pay-period">Periode terpilih</p>
        </div>
    </div>

    <div class="panel-card panel-card-flush ss-dash-enter" style="--ss-delay: 120ms">
        <div class="border-b border-gray-100 px-5 py-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">Laporan harian pendapatan &amp; biaya</h2>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="<?php echo e(route('speedshop.laporan.biaya.export', request()->only($laporanFilterQueryKeys))); ?>" class="btn-export">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Export Excel
                    </a>
                    <a href="<?php echo e(route('speedshop.laporan.biaya.export-pdf', request()->only($laporanFilterQueryKeys))); ?>" target="_blank" rel="noopener noreferrer" class="btn-export-pdf">
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
            <table class="w-max min-w-[2400px] whitespace-nowrap text-left text-sm">
                <thead class="sticky top-0 z-10 shadow-sm">
                    <tr style="background-color:#f7f3f4">
                        <th rowspan="2" class="border-b border-gray-200 px-5 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Jenis data</th>
                        <th rowspan="2" class="border-b border-gray-200 px-5 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Tanggal / periode</th>
                        <th rowspan="2" class="border-b border-gray-200 px-5 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Total omset kotor</th>
                        <th rowspan="2" class="border-b border-gray-200 px-5 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Unit entry</th>
                        <th colspan="5" class="border-b border-gray-200 px-5 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Sparepart</th>
                        <th rowspan="2" class="border-b border-gray-200 px-5 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Profit</th>
                        <th colspan="2" class="border-b border-gray-200 px-5 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">BOP</th>
                        <th rowspan="2" class="border-b border-gray-200 px-5 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Gross profit</th>
                    </tr>
                    <tr style="background-color:#f7f3f4">
                        <th class="border-b border-gray-200 px-5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Pendapatan jasa</th>
                        <th class="border-b border-gray-200 px-5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Part</th>
                        <th class="border-b border-gray-200 px-5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">HPP part</th>
                        <th class="border-b border-gray-200 px-5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Profit part</th>
                        <th class="border-b border-gray-200 px-5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Diskon</th>
                        <th class="border-b border-gray-200 px-5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Operasional</th>
                        <th class="border-b border-gray-200 px-5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Non Operasional</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr class="transition hover:bg-rose-50/50">
                        <td class="px-5 py-4 text-center text-sm font-medium text-gray-700">Real time</td>
                        <td class="px-5 py-4 text-center text-sm tabular-nums text-gray-800"><?php echo e($realtimeDate->format('d/m/y')); ?></td>
                        <td class="px-5 py-4 text-right text-sm font-semibold tabular-nums text-gray-900">Rp <?php echo e($fmt($realtime['omzet_kotor'])); ?></td>
                        <td class="px-5 py-4 text-center text-sm font-semibold tabular-nums text-gray-900"><?php echo e($realtime['unit_entry']); ?></td>
                        <td class="px-5 py-4 text-right text-sm tabular-nums text-gray-800">Rp <?php echo e($fmt($realtime['jasa'])); ?></td>
                        <td class="px-5 py-4 text-right text-sm tabular-nums text-gray-800">Rp <?php echo e($fmt($realtime['part'])); ?></td>
                        <td class="px-5 py-4 text-right text-sm tabular-nums text-gray-800">Rp <?php echo e($fmt($realtime['hpp_part'])); ?></td>
                        <td class="px-5 py-4 text-right text-sm tabular-nums text-gray-800">Rp <?php echo e($fmt($realtime['profit_part'])); ?></td>
                        <td class="px-5 py-4 text-right text-sm tabular-nums text-gray-800">Rp <?php echo e($fmt($realtime['diskon'])); ?></td>
                        <td class="px-5 py-4 text-right text-sm font-semibold tabular-nums text-gray-900">Rp <?php echo e($fmt($realtime['profit'])); ?></td>
                        <td class="px-5 py-4 text-right text-sm tabular-nums text-gray-800">Rp <?php echo e($fmt($realtime['bop_operasional'])); ?></td>
                        <td class="px-5 py-4 text-right text-sm tabular-nums text-gray-800">Rp <?php echo e($fmt($realtime['bop_non_operasional'])); ?></td>
                        <td class="px-5 py-4 text-right text-sm font-bold tabular-nums <?php echo e($realtime['profit_bersih'] >= 0 ? 'text-gray-900' : 'text-red-700'); ?>">Rp <?php echo e($fmt($realtime['profit_bersih'])); ?></td>
                    </tr>
                    <tr class="bg-gray-50/80 transition hover:bg-rose-50/50">
                        <td class="px-5 py-4 text-center text-sm font-medium text-gray-700">Bulan berjalan (s/d)</td>
                        <td class="px-5 py-4 text-center text-sm text-gray-800"><?php echo e($dari->format('d M Y')); ?> – <?php echo e($sampai->format('d M Y')); ?></td>
                        <td class="px-5 py-4 text-right text-sm font-semibold tabular-nums text-gray-900">Rp <?php echo e($fmt($periode['omzet_kotor'])); ?></td>
                        <td class="px-5 py-4 text-center text-sm font-semibold tabular-nums text-gray-900"><?php echo e($periode['unit_entry']); ?></td>
                        <td class="px-5 py-4 text-right text-sm tabular-nums text-gray-800">Rp <?php echo e($fmt($periode['jasa'])); ?></td>
                        <td class="px-5 py-4 text-right text-sm tabular-nums text-gray-800">Rp <?php echo e($fmt($periode['part'])); ?></td>
                        <td class="px-5 py-4 text-right text-sm tabular-nums text-gray-800">Rp <?php echo e($fmt($periode['hpp_part'])); ?></td>
                        <td class="px-5 py-4 text-right text-sm tabular-nums text-gray-800">Rp <?php echo e($fmt($periode['profit_part'])); ?></td>
                        <td class="px-5 py-4 text-right text-sm tabular-nums text-gray-800">Rp <?php echo e($fmt($periode['diskon'])); ?></td>
                        <td class="px-5 py-4 text-right text-sm font-semibold tabular-nums text-gray-900">Rp <?php echo e($fmt($periode['profit'])); ?></td>
                        <td class="px-5 py-4 text-right text-sm tabular-nums text-gray-800">Rp <?php echo e($fmt($periode['bop_operasional'])); ?></td>
                        <td class="px-5 py-4 text-right text-sm tabular-nums text-gray-800">Rp <?php echo e($fmt($periode['bop_non_operasional'])); ?></td>
                        <td class="px-5 py-4 text-right text-sm font-bold tabular-nums <?php echo e($periode['profit_bersih'] >= 0 ? 'text-gray-900' : 'text-red-700'); ?>">Rp <?php echo e($fmt($periode['profit_bersih'])); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.speedshop', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/wahyusafrizal/work/Github/77racingstore/resources/views/speedshop/laporan/biaya.blade.php ENDPATH**/ ?>