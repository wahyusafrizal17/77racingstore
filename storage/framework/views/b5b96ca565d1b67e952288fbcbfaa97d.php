<?php
    use App\Models\SpeedshopOwnerPengambilanDividen;
    $tz = config('app.timezone', 'Asia/Jakarta');
    $indexRoute = 'speedshop.verifikasi.pengambilan-dividen.index';
    $filters = $filters ?? [];
    $dividenStats = $dividenStats ?? ['total' => 0, 'pending' => 0, 'completed' => 0, 'rejected' => 0];
    $fv = fn (string $k) => old($k, $filters[$k] ?? '');
    $filterBaseClass = 'h-8 w-full rounded-lg border border-gray-200 bg-white px-2.5 text-[13px] font-normal text-gray-700 placeholder-gray-400 outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/40';
    $filterQueryKeys = ['mode', 'tanggal', 'bulan', 'tahun', 'dari', 'sampai', 'status', 'tanggal_pengajuan', 'owner', 'nominal'];
    $dividenPeriodLabel = match ($mode ?? 'bulanan') {
        'harian' => \Carbon\Carbon::parse($selectedDate ?? now())->translatedFormat('d M Y'),
        'bulanan' => \Carbon\Carbon::parse(($selectedMonth ?? now()->format('Y-m')).'-01')->translatedFormat('F Y'),
        'tahunan' => (string) ($selectedYear ?? now()->year),
        'range' => ($dari ?? now())->translatedFormat('d M Y').' – '.($sampai ?? now())->translatedFormat('d M Y'),
        default => now()->translatedFormat('F Y'),
    };
    $hasActiveFilters = collect(request()->only($filterQueryKeys))->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
?>

<?php $__env->startSection('title', 'Verifikasi Pengambilan Dividen'); ?>

<?php $__env->startSection('content'); ?>
<?php $__env->startPush('styles'); ?>
<?php echo $__env->make('partials.speedshop.dashboard-load-animations', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('partials.speedshop.dashboard-manager-template-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('partials.speedshop.work-order-create-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<style>
.ss-dashboard .dividen-summary-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 20px;
}
@media (max-width: 1024px) {
  .ss-dashboard .dividen-summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 640px) {
  .ss-dashboard .dividen-summary-grid { grid-template-columns: 1fr; }
}
.ss-dashboard .pay-icon.amber { background: rgba(245,158,11,0.10); color: #D97706; }
.ss-dashboard .pay-icon.teal { background: rgba(20,184,166,0.10); color: #14B8A6; }
.ss-dashboard .pay-icon.green { background: rgba(16,185,129,0.10); color: #10B981; }
.ss-dashboard .pay-icon.red { background: rgba(239,68,68,0.10); color: #EF4444; }
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
            'tanggal_pengajuan' => request('tanggal_pengajuan'),
            'owner' => request('owner'),
            'nominal' => request('nominal'),
        ], fn ($v) => $v !== null && $v !== ''),
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>


    <div class="dividen-summary-grid ss-dash-enter" style="--ss-delay: 80ms">
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 0">
            <div class="pay-card-top">
                <span class="pay-card-label">Total Pengajuan</span>
                <span class="pay-icon purple">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </span>
            </div>
            <div class="pay-amount"><?php echo e(number_format($dividenStats['total'], 0, ',', '.')); ?></div>
            <p class="pay-period">Pengajuan dividen periode terpilih</p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 1">
            <div class="pay-card-top">
                <span class="pay-card-label">Menunggu</span>
                <span class="pay-icon amber">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="pay-amount"><?php echo e(number_format($dividenStats['pending'], 0, ',', '.')); ?></div>
            <p class="pay-period">Belum diverifikasi Accounting</p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 2">
            <div class="pay-card-top">
                <span class="pay-card-label">Disetujui</span>
                <span class="pay-icon green">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </span>
            </div>
            <div class="pay-amount"><?php echo e(number_format($dividenStats['completed'], 0, ',', '.')); ?></div>
            <p class="pay-period">Transfer selesai · <?php echo e($dividenPeriodLabel); ?></p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 3">
            <div class="pay-card-top">
                <span class="pay-card-label">Ditolak</span>
                <span class="pay-icon red">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </span>
            </div>
            <div class="pay-amount"><?php echo e(number_format($dividenStats['rejected'], 0, ',', '.')); ?></div>
            <p class="pay-period">Pengajuan ditolak</p>
        </div>
    </div>

    <div class="panel-card panel-card-flush ss-dash-enter" style="--ss-delay: 120ms">
        <div class="border-b border-gray-100 px-5 py-4">
            <h2 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">Verifikasi Pengambilan Dividen</h2>
            <p class="mt-1 text-[13px] text-gray-500">
                Setujui pengajuan dividen owner setelah transfer · <?php echo e($dividenPeriodLabel); ?>

                <?php if($hasActiveFilters): ?>
                · <a href="<?php echo e(route($indexRoute)); ?>" class="font-medium text-red-600 transition hover:text-red-700">Hapus filter</a>
                <?php endif; ?>
            </p>
        </div>

        <div class="overflow-x-auto">
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
                <table class="w-full min-w-[980px] text-left text-sm">
                    <thead>
                        <tr class="bg-[#f7f3f4]">
                            <th class="w-16 px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No.</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Tanggal</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Status</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Owner</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Rek. speedshop</th>
                            <th class="px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-gray-500">Nominal</th>
                            <th class="w-32 px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500"></th>
                        </tr>
                        <tr class="border-t border-gray-200 bg-[#fbf8f9]">
                            <th class="px-5 py-2"></th>
                            <th class="px-5 py-2">
                                <input type="date" name="tanggal_pengajuan" value="<?php echo e($fv('tanggal_pengajuan')); ?>" class="<?php echo e($filterBaseClass); ?>" onchange="this.form.submit()">
                            </th>
                            <th class="px-5 py-2">
                                <select name="status" class="<?php echo e($filterBaseClass); ?>" onchange="this.form.submit()">
                                    <option value="" <?php if($fv('status') === ''): echo 'selected'; endif; ?>>Semua status</option>
                                    <option value="pending" <?php if($fv('status') === 'pending'): echo 'selected'; endif; ?>>Menunggu</option>
                                    <option value="completed" <?php if($fv('status') === 'completed'): echo 'selected'; endif; ?>>Disetujui</option>
                                    <option value="rejected" <?php if($fv('status') === 'rejected'): echo 'selected'; endif; ?>>Ditolak</option>
                                </select>
                            </th>
                            <th class="px-5 py-2">
                                <input type="text" name="owner" value="<?php echo e($fv('owner')); ?>" placeholder="Cari owner…" class="<?php echo e($filterBaseClass); ?>" onchange="this.form.submit()">
                            </th>
                            <th class="px-5 py-2"></th>
                            <th class="px-5 py-2">
                                <input type="number" min="0" step="1" name="nominal" value="<?php echo e($fv('nominal')); ?>" placeholder="Cari nominal…" class="<?php echo e($filterBaseClass); ?>" onchange="this.form.submit()">
                            </th>
                            <th class="px-5 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php $__empty_1 = true; $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
                            $statusClass = match ($row->status) {
                                SpeedshopOwnerPengambilanDividen::STATUS_COMPLETED => 'border-emerald-200 bg-emerald-50 text-emerald-800',
                                SpeedshopOwnerPengambilanDividen::STATUS_REJECTED => 'border-red-200 bg-red-50 text-red-800',
                                default => 'border-amber-200 bg-amber-50 text-amber-800',
                            };
                            $statusLabel = match ($row->status) {
                                SpeedshopOwnerPengambilanDividen::STATUS_COMPLETED => 'Disetujui',
                                SpeedshopOwnerPengambilanDividen::STATUS_REJECTED => 'Ditolak',
                                default => 'Menunggu',
                            };
                        ?>
                        <tr class="transition hover:bg-[#fcfafb]">
                            <td class="px-5 py-4 tabular-nums text-gray-500"><?php echo e($items->firstItem() + $i); ?></td>
                            <td class="px-5 py-4 text-gray-800"><?php echo e($row->tanggal->timezone($tz)->translatedFormat('j M Y')); ?></td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center rounded-lg border px-2.5 py-1 text-xs font-semibold <?php echo e($statusClass); ?>"><?php echo e($statusLabel); ?></span>
                            </td>
                            <td class="px-5 py-4 text-gray-800"><?php echo e($row->ownerUser?->name ?? '—'); ?></td>
                            <td class="px-5 py-4 text-gray-700"><?php echo e($row->speedshopRekening?->nama ?? '—'); ?></td>
                            <td class="px-5 py-4 text-right font-semibold tabular-nums text-red-700">Rp <?php echo e(number_format($row->nominal, 0, ',', '.')); ?></td>
                            <td class="px-5 py-4 text-right">
                                <a href="<?php echo e(route('speedshop.verifikasi.pengambilan-dividen.show', $row)); ?>"
                                   class="btn-detail">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    <?php echo e($row->isPending() ? 'Verifikasi' : 'Detail'); ?>

                                </a>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-gray-400">
                                <?php if($hasActiveFilters): ?>
                                    Tidak ada pengajuan sesuai filter.
                                <?php else: ?>
                                    Belum ada pengajuan dividen untuk periode ini.
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </form>
        </div>

        <?php if($items->hasPages()): ?>
        <div class="border-t border-gray-100 px-5 py-4"><?php echo e($items->links()); ?></div>
        <?php endif; ?>
    </div>
</div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.speedshop', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/wahyusafrizal/work/Github/77racingstore/resources/views/speedshop/verifikasi/pengambilan-dividen/index.blade.php ENDPATH**/ ?>