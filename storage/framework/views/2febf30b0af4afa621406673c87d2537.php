<?php $__env->startSection('title', 'Rekap Pemisahan Dana'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $fmt = fn ($v) => number_format((int) $v, 0, ',', '.');
    $weekColumns = $weekColumns ?? [];
    $rows = $rows ?? [];
    $monthLabel = $monthLabel ?? now()->translatedFormat('F Y');
    $speedshopLabel = ! empty($selectedLaporanSpeedshopName)
        ? strtoupper($selectedLaporanSpeedshopName)
        : (! empty($laporanAllSpeedshops)
            ? 'SEMUA SPEEDSHOP'
            : strtoupper($speedshop?->nama ?? 'SPEEDSHOP'));

    $laporanFilterQueryKeys = ['mode', 'tanggal', 'bulan', 'tahun', 'dari', 'sampai'];
    if (auth()->user()?->isOwner()) {
        $laporanFilterQueryKeys[] = 'speedshop_id';
    }

    $rowToneMap = [
        'row-red' => ['bg' => 'bg-red-50/60', 'hover' => 'hover:bg-red-50'],
        'row-blue' => ['bg' => 'bg-sky-50/60', 'hover' => 'hover:bg-sky-50'],
        'row-green' => ['bg' => 'bg-emerald-50/60', 'hover' => 'hover:bg-emerald-50'],
        'row-yellow' => ['bg' => 'bg-amber-50/60', 'hover' => 'hover:bg-amber-50'],
        'row-orange' => ['bg' => 'bg-orange-50/60', 'hover' => 'hover:bg-orange-50'],
        'row-gray' => ['bg' => 'bg-gray-50/60', 'hover' => 'hover:bg-gray-50'],
        'row-teal' => ['bg' => 'bg-teal-50/60', 'hover' => 'hover:bg-teal-50'],
        'row-purple' => ['bg' => 'bg-violet-50/60', 'hover' => 'hover:bg-violet-50'],
    ];

    $cardOmset = (int) ($cardOmset ?? 0);
    $cardHpp = (int) ($cardHpp ?? 0);
    $cardAlokasiBiaya = (int) ($cardAlokasiBiaya ?? 0);
    $cardSisaProfit = (int) ($cardSisaProfit ?? 0);
    $cardTotalBiaya = $cardHpp + $cardAlokasiBiaya;
?>

<?php $__env->startPush('styles'); ?>
<?php echo $__env->make('partials.speedshop.dashboard-load-animations', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('partials.speedshop.dashboard-manager-template-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('partials.speedshop.work-order-create-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<style>
.ss-dashboard .rpd-summary-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 20px;
}
@media (max-width: 900px) {
  .ss-dashboard .rpd-summary-grid { grid-template-columns: 1fr; }
}
.ss-dashboard .pay-icon.emerald { background: rgba(16,185,129,0.10); color: #059669; }
.ss-dashboard .pay-icon.red { background: rgba(239,68,68,0.10); color: #DC2626; }
.ss-dashboard .pay-icon.amber { background: rgba(245,158,11,0.10); color: #D97706; }
.ss-dashboard .pay-icon.purple { background: rgba(139,92,246,0.10); color: #7C3AED; }
.ss-dashboard .pay-icon.gray { background: rgba(107,114,128,0.10); color: #4B5563; }
.ss-dashboard .rpd-kategori-rowspan {
  vertical-align: middle;
}
</style>
<?php $__env->stopPush(); ?>

<div
    class="ss-dashboard ss-dash-page ss-wo-create"
    x-data="{ loading: false }"
    x-init="$nextTick(() => { requestAnimationFrame(() => $el.classList.add('is-ready')); })"
    :class="loading && 'is-loading'"
>
<div class="ss-dash-content space-y-5">
    <?php echo $__env->make('partials.flash', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <?php echo $__env->make('partials.speedshop.dashboard-filter-strip', [
        'filterAction' => route('speedshop.laporan.rekap-pemisahan-dana'),
        'dashboardFilterExports' => [],
        'dashboardFilterQueryKeys' => $laporanFilterQueryKeys,
        'dashboardSpeedshopOptions' => $laporanSpeedshopOptions ?? collect(),
        'selectedDashboardSpeedshopId' => $selectedLaporanSpeedshopId ?? null,
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="rpd-summary-grid ss-dash-enter" style="--ss-delay: 80ms">
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 0">
            <div class="pay-card-top">
                <span class="pay-card-label">Omset</span>
                <span class="pay-icon emerald">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="pay-amount text-[22px] sm:text-[26px]">Rp <?php echo e($fmt($cardOmset)); ?></div>
            <p class="pay-period"><?php echo e($monthLabel); ?></p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 1">
            <div class="pay-card-top">
                <span class="pay-card-label">Total Biaya &amp; Alokasi</span>
                <span class="pay-icon red">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                </span>
            </div>
            <div class="pay-amount text-[22px] sm:text-[26px]">Rp <?php echo e($fmt($cardTotalBiaya)); ?></div>
            <p class="pay-period">HPP <?php echo e($fmt($cardHpp)); ?> + alokasi <?php echo e($fmt($cardAlokasiBiaya)); ?></p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 2">
            <div class="pay-card-top">
                <span class="pay-card-label">Sisa Profit</span>
                <span class="pay-icon <?php echo e($cardSisaProfit >= 0 ? 'purple' : 'red'); ?>">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                </span>
            </div>
            <div class="pay-amount text-[22px] sm:text-[26px] <?php echo e($cardSisaProfit < 0 ? 'text-red-700' : ''); ?>">Rp <?php echo e($fmt($cardSisaProfit)); ?></div>
            <p class="pay-period"><?php echo e($speedshopLabel); ?> · <?php echo e($monthLabel); ?></p>
        </div>
    </div>

    <div class="panel-card panel-card-flush ss-dash-enter" style="--ss-delay: 120ms">
        <div class="border-b border-gray-100 px-5 py-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">Rekap pemisahan dana <?php echo e($speedshopLabel); ?></h2>
                    <p class="mt-1 text-[13px] text-gray-500">
                        Periode <?php echo e(($periodDari ?? $dari)->format('d M Y')); ?> – <?php echo e(($periodSampai ?? $sampai)->format('d M Y')); ?>

                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex shrink-0 items-center rounded-full bg-brand px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-brand-ink">
                        <?php echo e(strtoupper($monthLabel)); ?>

                    </span>
                    <a href="<?php echo e(route('speedshop.laporan.rekap-pemisahan-dana.export', request()->only($laporanFilterQueryKeys))); ?>" class="btn-export">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Export Excel
                    </a>
                    <a href="<?php echo e(route('speedshop.laporan.rekap-pemisahan-dana.export-pdf', request()->only($laporanFilterQueryKeys))); ?>" target="_blank" rel="noopener noreferrer" class="btn-export-pdf">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 13h6M9 17h4M9 9h1"/></svg>
                        Export PDF
                    </a>
                </div>
            </div>
        </div>

        <div class="max-h-[72vh] overflow-x-auto overflow-y-auto overscroll-x-contain">
            <table class="w-full min-w-[1200px] text-left text-sm">
                <thead class="sticky top-0 z-10 shadow-sm">
                    <tr style="background-color:#f7f3f4">
                        <th rowspan="2" class="border-b border-gray-200 px-4 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-500" style="width:48px">No</th>
                        <th rowspan="2" class="border-b border-gray-200 px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500" style="min-width:180px">Kategori</th>
                        <th rowspan="2" class="border-b border-gray-200 px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500" style="min-width:120px">Pembagian</th>
                        <?php $__currentLoopData = $weekColumns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $col): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <th colspan="2" class="border-b border-gray-200 px-4 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <div><?php echo e($col['title']); ?></div>
                            <div class="mt-0.5 text-[10px] font-medium normal-case tracking-normal text-gray-400">(<?php echo e($col['range_label']); ?>)</div>
                        </th>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <th colspan="2" class="border-b border-gray-200 px-4 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Total</th>
                    </tr>
                    <tr style="background-color:#f7f3f4">
                        <?php $__currentLoopData = $weekColumns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $col): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <th class="border-b border-gray-200 px-3 py-2 text-center text-[10px] font-semibold uppercase tracking-wider text-gray-500">Nominal</th>
                        <th class="border-b border-gray-200 px-3 py-2 text-center text-[10px] font-semibold uppercase tracking-wider text-gray-500">Tgl. Pembayaran</th>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <th class="border-b border-gray-200 px-3 py-2 text-center text-[10px] font-semibold uppercase tracking-wider text-gray-500">Nominal</th>
                        <th class="border-b border-gray-200 px-3 py-2 text-center text-[10px] font-semibold uppercase tracking-wider text-gray-500">Tgl. Pembayaran</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php $__empty_1 = true; $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $tone = $rowToneMap[$row['color'] ?? ''] ?? ['bg' => '', 'hover' => 'hover:bg-rose-50/50'];
                        $rowBg = $tone['bg'];
                        $rowHover = $tone['hover'];
                        $subRows = $row['sub_rows'] ?? [];
                    ?>
                    <?php if(count($subRows) > 0): ?>
                        <?php $__currentLoopData = $subRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $sub): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr class="transition <?php echo e($rowBg); ?> <?php echo e($rowHover); ?>">
                            <?php if($i === 0): ?>
                            <td rowspan="<?php echo e(count($subRows)); ?>" class="border-b border-gray-100 px-4 py-3.5 text-center text-sm font-medium tabular-nums text-gray-500"><?php echo e($row['no']); ?></td>
                            <td rowspan="<?php echo e(count($subRows)); ?>" class="rpd-kategori-rowspan border-b border-gray-100 px-4 py-3.5 text-sm font-semibold uppercase text-gray-900">
                                <?php echo e($row['label']); ?>

                                <?php if(!empty($row['rekening_suffix'])): ?>
                                <span class="ml-1 font-medium normal-case text-gray-600"><?php echo e($row['rekening_suffix']); ?></span>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                            <td class="border-b border-gray-100 px-4 py-3.5 text-sm font-semibold uppercase text-gray-800"><?php echo e($sub['label']); ?></td>
                            <?php $__currentLoopData = $weekColumns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $col): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php $cell = $sub['cells'][$col['key']] ?? ['nominal_fmt' => '—', 'tanggal' => '—']; ?>
                            <td class="border-b border-gray-100 px-3 py-3.5 text-right text-sm font-semibold tabular-nums text-gray-900"><?php echo e($cell['nominal_fmt']); ?></td>
                            <td class="border-b border-gray-100 px-3 py-3.5 text-center text-xs text-gray-500"><?php echo e($cell['tanggal']); ?></td>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php $subTotal = $sub['cells']['total'] ?? ['nominal_fmt' => '—', 'tanggal' => '—']; ?>
                            <td class="border-b border-gray-100 px-3 py-3.5 text-right text-sm font-bold italic tabular-nums text-red-600"><?php echo e($subTotal['nominal_fmt']); ?></td>
                            <td class="border-b border-gray-100 px-3 py-3.5 text-center text-xs text-gray-400"><?php echo e($subTotal['tanggal']); ?></td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php else: ?>
                    <tr class="transition <?php echo e($rowBg); ?> <?php echo e($rowHover); ?>">
                        <td class="border-b border-gray-100 px-4 py-3.5 text-center text-sm font-medium tabular-nums text-gray-500"><?php echo e($row['no']); ?></td>
                        <td class="border-b border-gray-100 px-4 py-3.5 text-sm font-semibold uppercase text-gray-900">
                            <?php echo e($row['label']); ?>

                            <?php if(!empty($row['rekening_suffix'])): ?>
                            <span class="ml-1 font-medium normal-case text-gray-600"><?php echo e($row['rekening_suffix']); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="border-b border-gray-100 px-4 py-3.5 text-center text-xs text-gray-400">—</td>
                        <?php $__currentLoopData = $weekColumns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $col): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php $cell = $row['cells'][$col['key']] ?? ['nominal_fmt' => '—', 'tanggal' => '—', 'is_empty' => true]; ?>
                        <td class="border-b border-gray-100 px-3 py-3.5 text-right text-sm font-semibold tabular-nums text-gray-900"><?php echo e($cell['nominal_fmt']); ?></td>
                        <td class="border-b border-gray-100 px-3 py-3.5 text-center text-xs text-gray-500"><?php echo e($cell['tanggal']); ?></td>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <?php $total = $row['cells']['total'] ?? ['nominal_fmt' => '—', 'tanggal' => '—']; ?>
                        <td class="border-b border-gray-100 px-3 py-3.5 text-right text-sm font-bold italic tabular-nums text-red-600"><?php echo e($total['nominal_fmt']); ?></td>
                        <td class="border-b border-gray-100 px-3 py-3.5 text-center text-xs text-gray-400"><?php echo e($total['tanggal']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="<?php echo e(3 + (count($weekColumns) * 2) + 2); ?>" class="px-5 py-12 text-center text-sm text-gray-500">
                            <p class="font-medium text-gray-700">Tidak ada data pada periode ini.</p>
                            <p class="mt-1 text-xs text-gray-400">Ubah filter bulan lalu terapkan lagi.</p>
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

<?php echo $__env->make('layouts.speedshop', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/wahyusafrizal/work/Github/77racingstore/resources/views/speedshop/laporan/rekap-pemisahan-dana.blade.php ENDPATH**/ ?>