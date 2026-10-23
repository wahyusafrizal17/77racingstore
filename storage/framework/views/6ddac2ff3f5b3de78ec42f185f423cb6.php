<?php $__env->startSection('title', 'Keuangan — Tagihan'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $filters = $filters ?? [];
    $stats = $stats ?? ['total' => 0, 'belum_lunas' => 0, 'menunggu_konfirmasi' => 0, 'lunas' => 0];
    $fv = fn (string $k) => old($k, $filters[$k] ?? '');
    $statusFilter = $fv('status');
    $tableFilterClass = 'h-8 w-full rounded-lg border border-gray-200 bg-white px-2.5 text-[13px] font-normal text-gray-700 placeholder-gray-400 outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/40';
    $indexRoute = 'speedshop.keuangan.tagihan.index';
    $mode = $mode ?? 'bulanan';
    $filterQueryKeys = ['mode', 'tanggal', 'bulan', 'tahun', 'dari', 'sampai', 'status', 'tanggal_tagihan', 'kode', 'counterparty'];
    $tagihanPeriodLabel = match ($mode) {
        'harian' => \Carbon\Carbon::parse($selectedDate ?? now())->translatedFormat('d M Y'),
        'bulanan' => \Carbon\Carbon::parse(($selectedMonth ?? now()->format('Y-m')).'-01')->translatedFormat('F Y'),
        'tahunan' => (string) ($selectedYear ?? now()->year),
        'range' => ($dari ?? now())->translatedFormat('d M Y').' – '.($sampai ?? now())->translatedFormat('d M Y'),
        default => now()->translatedFormat('F Y'),
    };
    $tagihanCardBaseQuery = array_filter([
        'mode' => $mode,
        'tanggal' => $mode === 'harian' ? ($selectedDate ?? now()->toDateString()) : null,
        'bulan' => $mode === 'bulanan' ? ($selectedMonth ?? now()->format('Y-m')) : null,
        'tahun' => $mode === 'tahunan' ? ($selectedYear ?? now()->year) : null,
        'dari' => $mode === 'range' ? ($selectedDari ?? ($dari ?? now())->format('Y-m-d')) : null,
        'sampai' => $mode === 'range' ? ($selectedSampai ?? ($sampai ?? now())->format('Y-m-d')) : null,
        'tanggal_tagihan' => request('tanggal_tagihan'),
        'kode' => request('kode'),
        'counterparty' => request('counterparty'),
    ], fn ($v) => $v !== null && $v !== '');
    $tagihanCardUrl = fn (?string $status = null) => route($indexRoute, $status
        ? array_merge($tagihanCardBaseQuery, ['status' => $status])
        : $tagihanCardBaseQuery);
    $hasActiveFilters = collect(request()->only($filterQueryKeys))->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
    $exportQuery = $exportQuery ?? $tagihanCardBaseQuery;
?>
<?php $__env->startPush('styles'); ?>
<?php echo $__env->make('partials.speedshop.dashboard-load-animations', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('partials.speedshop.dashboard-manager-template-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('partials.speedshop.work-order-create-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<style>
.ss-dashboard .tagihan-summary-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 20px;
}
@media (max-width: 1024px) {
  .ss-dashboard .tagihan-summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 640px) {
  .ss-dashboard .tagihan-summary-grid { grid-template-columns: 1fr; }
}
.ss-dashboard .pay-card.pay-card-filter {
  cursor: pointer;
  text-decoration: none;
  color: inherit;
}
.ss-dashboard .pay-icon.blue { background: rgba(46,107,230,0.10); color: #2E6BE6; }
.ss-dashboard .pay-icon.red { background: rgba(239,68,68,0.10); color: #EF4444; }
.ss-dashboard .pay-icon.amber { background: rgba(245,158,11,0.10); color: #D97706; }
.ss-dashboard .pay-icon.green { background: rgba(16,185,129,0.10); color: #10B981; }
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
        'filterAction' => route($indexRoute),
        'dashboardFilterExports' => [],
        'dashboardFilterQueryKeys' => $filterQueryKeys,
        'filterHiddenInputs' => array_filter([
            'status' => request('status'),
            'tanggal_tagihan' => request('tanggal_tagihan'),
            'kode' => request('kode'),
            'counterparty' => request('counterparty'),
        ], fn ($v) => $v !== null && $v !== ''),
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>


    <div class="tagihan-summary-grid ss-dash-enter" style="--ss-delay: 80ms">
        <a href="<?php echo e($tagihanCardUrl()); ?>" @click="loading = true" class="pay-card pay-card-filter ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 0">
            <div class="pay-card-top">
                <span class="pay-card-label">Total Tagihan</span>
                <span class="pay-icon blue">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </span>
            </div>
            <div class="pay-amount"><?php echo e(number_format($stats['total'] ?? 0, 0, ',', '.')); ?></div>
            <p class="pay-period">Semua tagihan · <?php echo e($tagihanPeriodLabel); ?></p>
        </a>
        <a href="<?php echo e($tagihanCardUrl('belum_lunas')); ?>" @click="loading = true" class="pay-card pay-card-filter ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 1">
            <div class="pay-card-top">
                <span class="pay-card-label">Belum lunas</span>
                <span class="pay-icon red">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="pay-amount"><?php echo e(number_format($stats['belum_lunas'] ?? 0, 0, ',', '.')); ?></div>
            <p class="pay-period">Perlu dibayar ke warehouse</p>
        </a>
        <a href="<?php echo e($tagihanCardUrl('dibayar')); ?>" @click="loading = true" class="pay-card pay-card-filter ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 2">
            <div class="pay-card-top">
                <span class="pay-card-label">Menunggu konfirmasi</span>
                <span class="pay-icon amber">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="pay-amount"><?php echo e(number_format($stats['menunggu_konfirmasi'] ?? 0, 0, ',', '.')); ?></div>
            <p class="pay-period">Sudah dibayar, menunggu warehouse</p>
        </a>
        <a href="<?php echo e($tagihanCardUrl('lunas')); ?>" @click="loading = true" class="pay-card pay-card-filter ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 3">
            <div class="pay-card-top">
                <span class="pay-card-label">Lunas</span>
                <span class="pay-icon green">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </span>
            </div>
            <div class="pay-amount"><?php echo e(number_format($stats['lunas'] ?? 0, 0, ',', '.')); ?></div>
            <p class="pay-period">Tagihan lunas · <?php echo e($tagihanPeriodLabel); ?></p>
        </a>
    </div>

    <div class="panel-card panel-card-flush ss-dash-enter" style="--ss-delay: 120ms">
        <div class="border-b border-gray-100 px-5 py-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">Data tagihan</h2>
                    <p class="mt-1 text-[13px] text-gray-500">
                        Tagihan PO ke warehouse · <?php echo e($tagihanPeriodLabel); ?>

                        <?php if($hasActiveFilters): ?>
                        · <a href="<?php echo e(route($indexRoute)); ?>" class="font-medium text-red-600 transition hover:text-red-700">Hapus filter</a>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="<?php echo e(route('speedshop.keuangan.tagihan.export-excel', $exportQuery)); ?>" class="btn-export">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Export Excel
                    </a>
                    <a href="<?php echo e(route('speedshop.keuangan.tagihan.export-pdf', $exportQuery)); ?>" target="_blank" rel="noopener noreferrer" class="btn-export-pdf">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/><path d="M9 13h6M9 17h4M9 9h1"/></svg>
                        Export PDF
                    </a>
                </div>
            </div>
        </div>

        <form method="GET" action="<?php echo e(route($indexRoute)); ?>" @submit="loading = true">
            <input type="hidden" name="mode" value="<?php echo e($mode ?? 'bulanan'); ?>">
            <?php if(($mode ?? 'bulanan') === 'harian'): ?>
            <input type="hidden" name="tanggal" value="<?php echo e($selectedDate ?? now()->toDateString()); ?>">
            <?php elseif(($mode ?? '') === 'bulanan'): ?>
            <input type="hidden" name="bulan" value="<?php echo e($selectedMonth ?? now()->format('Y-m')); ?>">
            <?php elseif(($mode ?? '') === 'tahunan'): ?>
            <input type="hidden" name="tahun" value="<?php echo e($selectedYear ?? now()->year); ?>">
            <?php elseif(($mode ?? '') === 'range'): ?>
            <input type="hidden" name="dari" value="<?php echo e($selectedDari ?? ($dari ?? now())->format('Y-m-d')); ?>">
            <input type="hidden" name="sampai" value="<?php echo e($selectedSampai ?? ($sampai ?? now())->format('Y-m-d')); ?>">
            <?php endif; ?>
            <div class="overflow-x-auto">
                <table class="w-full min-w-max text-left text-sm">
                    <thead>
                        <tr class="bg-[#f7f3f4]">
                            <th class="w-16 px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No.</th>
                            <th class="min-w-[150px] px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No. Tagihan</th>
                            <th class="min-w-[140px] px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">PO</th>
                            <th class="min-w-[160px] px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Warehouse</th>
                            <th class="min-w-[130px] px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Tanggal</th>
                            <th class="min-w-[140px] px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-gray-500">Total</th>
                            <th class="min-w-[150px] px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Status</th>
                            <th class="w-28 px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Aksi</th>
                        </tr>
                        <tr class="border-t border-gray-200 bg-[#fbf8f9]">
                            <th class="px-5 py-2"></th>
                            <th class="px-5 py-2">
                                <input type="text" name="kode" value="<?php echo e($fv('kode')); ?>" placeholder="Cari tagihan…" class="<?php echo e($tableFilterClass); ?>" onchange="this.form.submit()">
                            </th>
                            <th class="px-5 py-2"></th>
                            <th class="px-5 py-2">
                                <input type="text" name="counterparty" value="<?php echo e($fv('counterparty')); ?>" placeholder="Cari warehouse…" class="<?php echo e($tableFilterClass); ?>" onchange="this.form.submit()">
                            </th>
                            <th class="px-5 py-2">
                                <input type="date" name="tanggal_tagihan" value="<?php echo e($fv('tanggal_tagihan')); ?>" class="<?php echo e($tableFilterClass); ?> text-xs" onchange="this.form.submit()" title="Tanggal tagihan">
                            </th>
                            <th class="px-5 py-2"></th>
                            <th class="px-5 py-2">
                                <select name="status" class="<?php echo e($tableFilterClass); ?>" onchange="this.form.submit()">
                                    <option value="" <?php if($statusFilter === ''): echo 'selected'; endif; ?>>Semua status</option>
                                    <option value="belum_lunas" <?php if($statusFilter === 'belum_lunas'): echo 'selected'; endif; ?>>Belum lunas</option>
                                    <option value="dibayar" <?php if($statusFilter === 'dibayar'): echo 'selected'; endif; ?>>Menunggu konfirmasi</option>
                                    <option value="lunas" <?php if($statusFilter === 'lunas'): echo 'selected'; endif; ?>>Lunas</option>
                                </select>
                            </th>
                            <th class="px-5 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php $__empty_1 = true; $__currentLoopData = $tagihans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
                            $tgl = $t->tanggal_tagihan;
                            $statusLabel = \App\Models\SpeedshopPoTagihan::statusLabel($t->status);
                            $statusClass = match ($t->status) {
                                \App\Models\SpeedshopPoTagihan::STATUS_DIBAYAR => 'border-amber-200 bg-amber-50 text-amber-700',
                                \App\Models\SpeedshopPoTagihan::STATUS_LUNAS => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                                default => 'border-red-200 bg-red-50 text-red-700',
                            };
                            $rowNo = $tagihans->firstItem() + $i;
                        ?>
                        <tr class="transition hover:bg-[#fcfafb]">
                            <td class="px-5 py-4 tabular-nums text-gray-500"><?php echo e($rowNo); ?></td>
                            <td class="px-5 py-4 align-top font-semibold text-gray-900"><?php echo e($t->kode); ?></td>
                            <td class="px-5 py-4 align-top text-gray-600"><?php echo e($t->purchaseOrder?->kode ?? '—'); ?></td>
                            <td class="px-5 py-4 align-top text-gray-800"><?php echo e($t->warehouse?->nama ?? '—'); ?></td>
                            <td class="px-5 py-4 align-top">
                                <?php if($tgl): ?>
                                <span class="font-semibold text-gray-900"><?php echo e($tgl->translatedFormat('j M Y')); ?></span>
                                <p class="text-xs text-gray-500"><?php echo e($tgl->translatedFormat('l')); ?></p>
                                <?php else: ?>
                                <span class="text-gray-400">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-4 align-top text-right font-semibold tabular-nums text-gray-900">Rp <?php echo e(number_format($t->total_nominal, 0, ',', '.')); ?></td>
                            <td class="px-5 py-4 align-top">
                                <span class="inline-flex items-center rounded-lg border px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide <?php echo e($statusClass); ?>"><?php echo e($statusLabel); ?></span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end">
                                    <a href="<?php echo e(route('speedshop.keuangan.tagihan.show', $t)); ?>"
                                       class="btn-detail"
                                       title="Detail">
                                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        Detail
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="8" class="px-5 py-12 text-center text-sm text-gray-500">
                                <?php if($hasActiveFilters): ?>
                                    Tidak ada tagihan sesuai filter.
                                <?php else: ?>
                                    Belum ada tagihan untuk periode ini.
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </form>

        <?php if($tagihans->hasPages()): ?>
        <div class="border-t border-gray-100 px-5 py-4"><?php echo e($tagihans->links()); ?></div>
        <?php endif; ?>
    </div>
</div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.speedshop', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/wahyusafrizal/work/Github/77racingstore/resources/views/speedshop/keuangan/tagihan/index.blade.php ENDPATH**/ ?>