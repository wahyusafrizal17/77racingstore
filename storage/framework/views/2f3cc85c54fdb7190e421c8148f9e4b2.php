<?php $__env->startSection('title', 'Laporan Laba Rugi'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $summary = $labaRugiSummary ?? [];
    $rows = $labaRugiRows ?? [];
    $fmt = fn ($n) => number_format((float) $n, 0, ',', '.');
    $fmtPct = fn ($n) => number_format((float) $n, 2, ',', '.').'%';

    $rowClass = fn (string $kind) => match ($kind) {
        'section' => 'bg-rose-50 font-semibold text-gray-900',
        'sub' => 'bg-[#f7f3f4] font-semibold text-gray-900',
        'subtotal' => 'bg-rose-50/90 font-semibold text-gray-900 ring-1 ring-inset ring-red-100',
        'grand' => 'bg-red-50 font-bold text-gray-900 ring-1 ring-inset ring-red-200/80',
        'footer' => 'bg-rose-500 font-bold text-white shadow-inner ring-1 ring-inset ring-white/10',
        'ket' => 'bg-[#f7f3f4] font-semibold text-gray-900',
        'pct' => 'bg-rose-50/40 font-semibold text-gray-800',
        default => 'bg-white transition hover:bg-rose-50/50',
    };

    $laporanFilterQueryKeys = ['mode', 'tanggal', 'bulan', 'tahun', 'dari', 'sampai'];
    if (auth()->user()?->isOwner()) {
        $laporanFilterQueryKeys[] = 'speedshop_id';
    }

    $hasActiveFilters = count(request()->query()) > 0;
    $nettProfit = (float) ($summary['nett_profit'] ?? 0);
    $speedshopLabel = ! empty($selectedLaporanSpeedshopName)
        ? strtoupper($selectedLaporanSpeedshopName)
        : (! empty($laporanAllSpeedshops)
            ? 'SEMUA SPEEDSHOP'
            : strtoupper($speedshop?->nama ?? 'SEMUA OUTLET'));
?>

<?php $__env->startPush('styles'); ?>
<?php echo $__env->make('partials.speedshop.dashboard-load-animations', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('partials.speedshop.dashboard-manager-template-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('partials.speedshop.work-order-create-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<style>
.ss-dashboard .lr-summary-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 20px;
}
@media (max-width: 1100px) {
  .ss-dashboard .lr-summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 600px) {
  .ss-dashboard .lr-summary-grid { grid-template-columns: 1fr; }
}
.ss-dashboard .pay-icon.emerald { background: rgba(16,185,129,0.10); color: #059669; }
.ss-dashboard .pay-icon.amber { background: rgba(245,158,11,0.10); color: #D97706; }
.ss-dashboard .pay-icon.sky { background: rgba(14,165,233,0.10); color: #0284C7; }
.ss-dashboard .pay-icon.indigo { background: rgba(99,102,241,0.10); color: #4F46E5; }
.ss-dashboard .pay-icon.red { background: rgba(239,68,68,0.10); color: #DC2626; }
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
        'filterAction' => route('speedshop.laporan.laba-rugi'),
        'dashboardFilterExports' => [],
        'dashboardFilterQueryKeys' => $laporanFilterQueryKeys,
        'dashboardSpeedshopOptions' => $laporanSpeedshopOptions ?? collect(),
        'selectedDashboardSpeedshopId' => $selectedLaporanSpeedshopId ?? null,
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>


    <div class="lr-summary-grid ss-dash-enter" style="--ss-delay: 80ms">
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 0">
            <div class="pay-card-top">
                <span class="pay-card-label">Total Pendapatan</span>
                <span class="pay-icon emerald">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="pay-amount text-[22px] sm:text-[26px]">Rp <?php echo e($fmt($summary['total_pendapatan'] ?? 0)); ?></div>
            <p class="pay-period">Periode terpilih</p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 1">
            <div class="pay-card-top">
                <span class="pay-card-label">Total Biaya</span>
                <span class="pay-icon amber">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                </span>
            </div>
            <div class="pay-amount text-[22px] sm:text-[26px]">Rp <?php echo e($fmt($summary['total_biaya'] ?? 0)); ?></div>
            <p class="pay-period">HPP + biaya operasional</p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 2">
            <div class="pay-card-top">
                <span class="pay-card-label">Gross Profit</span>
                <span class="pay-icon sky">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                </span>
            </div>
            <div class="pay-amount text-[22px] sm:text-[26px]">Rp <?php echo e($fmt($summary['gross_profit'] ?? 0)); ?></div>
            <p class="pay-period"><?php echo e($fmtPct($summary['pct_gross'] ?? 0)); ?> dari pendapatan</p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 3">
            <div class="pay-card-top">
                <span class="pay-card-label">Nett Profit</span>
                <span class="pay-icon <?php echo e($nettProfit >= 0 ? 'indigo' : 'red'); ?>">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </span>
            </div>
            <div class="pay-amount text-[22px] sm:text-[26px] <?php echo e($nettProfit < 0 ? 'text-red-700' : ''); ?>">Rp <?php echo e($fmt($nettProfit)); ?></div>
            <p class="pay-period">Setelah PPN &amp; zakat</p>
        </div>
    </div>

    <div class="panel-card panel-card-flush ss-dash-enter" style="--ss-delay: 120ms">
        <div class="border-b border-gray-100 px-5 py-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">Rincian Laba Rugi Speedshop <?php echo e($speedshopLabel); ?></h2>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="<?php echo e(route('speedshop.laporan.laba-rugi.export', request()->only($laporanFilterQueryKeys))); ?>" class="btn-export">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Export Excel
                    </a>
                    <a href="<?php echo e(route('speedshop.laporan.laba-rugi.export-pdf', request()->only($laporanFilterQueryKeys))); ?>" target="_blank" rel="noopener noreferrer" class="btn-export-pdf">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 13h6M9 17h4M9 9h1"/></svg>
                        Export PDF
                    </a>
                </div>
            </div>
            <p class="text-[13px] text-gray-500">
                Periode <?php echo e($dari->format('d M Y')); ?> – <?php echo e($sampai->format('d M Y')); ?>

            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-left text-sm text-gray-900">
                <thead>
                    <tr class="bg-[#f7f3f4]">
                        <th class="w-24 px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-500">No.</th>
                        <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-500">Keterangan</th>
                        <th class="w-48 px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-gray-500">Nilai</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php echo $__env->make('partials.laporan.laba-rugi-table-body', compact('rows', 'fmt', 'fmtPct', 'rowClass'), array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.speedshop', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/wahyusafrizal/work/Github/77racingstore/resources/views/speedshop/laporan/laba-rugi.blade.php ENDPATH**/ ?>