<?php
    $filterBaseClass = 'h-8 w-full rounded-lg border border-gray-200 bg-white px-2.5 text-[13px] font-normal text-gray-700 placeholder-gray-400 outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/40';
    $mekanikRecordPeriodLabel = match ($mode ?? 'harian') {
        'harian' => \Carbon\Carbon::parse($selectedDate ?? now())->translatedFormat('d M Y'),
        'bulanan' => \Carbon\Carbon::parse(($selectedMonth ?? now()->format('Y-m')).'-01')->translatedFormat('F Y'),
        'tahunan' => (string) ($selectedYear ?? now()->year),
        'range' => ($dari ?? now())->translatedFormat('d M Y').' – '.($sampai ?? now())->translatedFormat('d M Y'),
        default => ($dari ?? now())->translatedFormat('d M Y'),
    };

    $mekanikDetails = [];
    foreach ($byMekanik as $row) {
        $id = (int) ($row->mekanik?->id ?? 0);
        if ($id <= 0) {
            continue;
        }
        $mekanikDetails[$id] = [
            'nama' => $row->mekanik->nama ?? '-',
            'no_hp' => $row->mekanik->no_hp ?? '-',
            'total_work_order' => (int) $row->total_work_order,
            'total_pendapatan' => (int) $row->total_pendapatan,
            'orders' => $row->orders->map(function ($order) {
                $tanggal = $order->tanggal_selesai ?? $order->tanggal_masuk;

                return [
                    'kode_servis' => $order->kode_servis,
                    'tanggal' => $tanggal?->format('d M Y') ?? '—',
                    'pelanggan' => $order->pelanggan->nama ?? '—',
                    'polisi' => $order->kendaraan?->nomor_polisi ?? '—',
                    'speedshop' => $order->speedshop?->nama,
                    'total' => $order->finalGrandTotal(),
                    'url' => route('speedshop.wip.show', $order),
                ];
            })->values()->all(),
        ];
    }
?>

<?php $__env->startSection('title', 'Mekanik Record'); ?>

<?php $__env->startSection('content'); ?>
<?php $__env->startPush('styles'); ?>
<?php echo $__env->make('partials.speedshop.dashboard-load-animations', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('partials.speedshop.dashboard-manager-template-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('partials.speedshop.work-order-create-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<style>
.ss-dashboard .mekanik-record-summary-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 20px;
}
@media (max-width: 640px) {
  .ss-dashboard .mekanik-record-summary-grid { grid-template-columns: 1fr; }
}
.ss-dashboard .pay-icon.blue { background: rgba(46,107,230,0.10); color: #2E6BE6; }
</style>
<?php $__env->stopPush(); ?>

<div
    class="ss-dashboard ss-dash-page ss-wo-create"
    x-data="{
        loading: false,
        showDetail: false,
        detail: null,
        records: <?php echo \Illuminate\Support\Js::from($mekanikDetails)->toHtml() ?>,
        allSpeedshops: <?php echo \Illuminate\Support\Js::from(!empty($mekanikRecordAllSpeedshops))->toHtml() ?>,
        openDetail(id) {
            this.detail = this.records[id] ?? null;
            this.showDetail = !!this.detail;
            if (this.showDetail) {
                document.body.classList.add('overflow-hidden');
            }
        },
        closeDetail() {
            this.showDetail = false;
            this.detail = null;
            document.body.classList.remove('overflow-hidden');
        },
        formatRp(n) {
            return new Intl.NumberFormat('id-ID').format(n ?? 0);
        }
    }"
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
            <p class="ss-dash-loading-text">Memuat mekanik record…</p>
            <p class="ss-dash-loading-sub">Menyiapkan data periode terpilih</p>
        </div>
    </div>

    <div class="ss-dash-content space-y-5">
    <?php echo $__env->make('partials.speedshop.dashboard-filter-strip', [
        'filterAction' => route('speedshop.mekanik-record'),
        'dashboardFilterExports' => [],
        'dashboardFilterQueryKeys' => ['mode', 'tanggal', 'bulan', 'tahun', 'dari', 'sampai', 'mekanik', 'no_hp', 'sort'],
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="mekanik-record-summary-grid ss-dash-enter" style="--ss-delay: 80ms">
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 0">
            <div class="pay-card-top">
                <span class="pay-card-label">Total Mekanik</span>
                <span class="pay-icon blue">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </span>
            </div>
            <div class="pay-amount"><?php echo e(number_format((int) ($summaryTotalMekanik ?? 0))); ?></div>
            <p class="pay-period"><?php echo e(number_format((int) ($summaryTotalWorkOrder ?? 0))); ?> work order selesai</p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 1">
            <div class="pay-card-top">
                <span class="pay-card-label">Total Pendapatan WO</span>
                <span class="pay-icon purple">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="pay-amount"><span class="curr">Rp</span><?php echo e(number_format((int) ($summaryTotalPendapatan ?? 0), 0, ',', '.')); ?></div>
            <p class="pay-period">Akumulasi nilai WO periode terpilih</p>
        </div>
    </div>

    <div class="panel-card panel-card-flush ss-dash-enter" style="--ss-delay: 120ms">
        <div class="flex flex-col gap-3 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">Mekanik Record</h2>
                <p class="mt-1 text-[13px] text-gray-500">
                    Mekanik yang paling sering menyelesaikan work order · <?php echo e($mekanikRecordPeriodLabel); ?>

                    <?php if(!empty($mekanikRecordAllSpeedshops)): ?>
                        · Semua speedshop
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <form method="GET" action="<?php echo e(route('speedshop.mekanik-record')); ?>" @submit="loading = true">
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
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Mekanik</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No HP</th>
                            <th class="px-5 py-3.5 text-center text-[11px] font-semibold uppercase tracking-wider text-gray-500">Total Work Order</th>
                            <th class="px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-gray-500">Total Pendapatan WO</th>
                            <th class="px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-gray-500">Action</th>
                        </tr>
                        <tr class="border-t border-gray-200 bg-[#fbf8f9]">
                            <th class="px-5 py-2"></th>
                            <th class="px-5 py-2">
                                <input name="mekanik" value="<?php echo e(request('mekanik')); ?>" placeholder="Cari mekanik..." class="<?php echo e($filterBaseClass); ?>" onchange="this.form.submit()">
                            </th>
                            <th class="px-5 py-2">
                                <input name="no_hp" value="<?php echo e(request('no_hp')); ?>" placeholder="Cari no HP..." class="<?php echo e($filterBaseClass); ?>" onchange="this.form.submit()">
                            </th>
                            <th class="px-5 py-2">
                                <?php $sortWorkOrder = str_starts_with((string) ($sort ?? ''), 'work_order'); ?>
                                <input type="hidden" name="sort" value="<?php echo e($sort ?? 'work_order_desc'); ?>">
                                <select
                                    class="<?php echo e($filterBaseClass); ?>"
                                    aria-label="Urutkan total work order"
                                    onchange="if(this.value){ this.form.querySelector('input[name=sort]').value=this.value; this.form.submit(); }"
                                >
                                    <option value="" <?php echo e(! $sortWorkOrder ? 'selected' : ''); ?>>—</option>
                                    <option value="work_order_desc" <?php echo e(($sort ?? 'work_order_desc') === 'work_order_desc' ? 'selected' : ''); ?>>WO terbanyak</option>
                                    <option value="work_order_asc" <?php echo e(($sort ?? '') === 'work_order_asc' ? 'selected' : ''); ?>>WO tersedikit</option>
                                </select>
                            </th>
                            <th class="px-5 py-2">
                                <?php $sortPendapatan = str_starts_with((string) ($sort ?? ''), 'pendapatan'); ?>
                                <select
                                    class="<?php echo e($filterBaseClass); ?>"
                                    aria-label="Urutkan total pendapatan"
                                    onchange="if(this.value){ this.form.querySelector('input[name=sort]').value=this.value; this.form.submit(); }"
                                >
                                    <option value="" <?php echo e(! $sortPendapatan ? 'selected' : ''); ?>>—</option>
                                    <option value="pendapatan_desc" <?php echo e(($sort ?? '') === 'pendapatan_desc' ? 'selected' : ''); ?>>Pendapatan tertinggi</option>
                                    <option value="pendapatan_asc" <?php echo e(($sort ?? '') === 'pendapatan_asc' ? 'selected' : ''); ?>>Pendapatan terendah</option>
                                </select>
                            </th>
                            <th class="px-5 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    <?php $__empty_1 = true; $__currentLoopData = $byMekanik; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $globalNo = (int) $byMekanik->firstItem() + (int) $index;
                        $mekanikId = (int) ($row->mekanik?->id ?? 0);
                    ?>
                    <tr class="transition hover:bg-[#fcfafb]">
                        <td class="px-5 py-4">
                            <?php if($globalNo <= 3): ?>
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold
                                <?php echo e($globalNo === 1 ? 'bg-amber-100 text-amber-800' : ($globalNo === 2 ? 'bg-gray-200 text-gray-800' : 'bg-amber-50 text-amber-800')); ?>">
                                <?php echo e($globalNo); ?>

                            </span>
                            <?php else: ?>
                            <span class="text-gray-500"><?php echo e($globalNo); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="px-5 py-4 font-semibold text-gray-900"><?php echo e($row->mekanik->nama ?? '-'); ?></td>
                        <td class="px-5 py-4 text-gray-600"><?php echo e($row->mekanik->no_hp ?? '-'); ?></td>
                        <td class="px-5 py-4 text-center">
                            <span class="wo-badge"><?php echo e(number_format($row->total_work_order)); ?>×</span>
                        </td>
                        <td class="px-5 py-4 text-right font-semibold tabular-nums text-gray-900">
                            Rp <?php echo e(number_format($row->total_pendapatan, 0, ',', '.')); ?>

                        </td>
                        <td class="px-5 py-4 text-right">
                            <?php if($row->orders->isNotEmpty() && $mekanikId > 0): ?>
                            <button type="button"
                                    @click="openDetail(<?php echo e($mekanikId); ?>)"
                                    class="btn-detail">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                Detail
                            </button>
                            <?php else: ?>
                            <span class="text-xs text-gray-400">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center text-gray-400">
                            <?php if(request()->hasAny(['mekanik', 'no_hp', 'search'])): ?>
                                Tidak ada mekanik sesuai pencarian.
                            <?php else: ?>
                                Belum ada data mekanik record untuk periode ini.
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </form>
        </div>

        <?php if($byMekanik->hasPages()): ?>
        <div class="border-t border-gray-100 px-5 py-4"><?php echo e($byMekanik->links()); ?></div>
        <?php endif; ?>
    </div>
    </div>

    
    <div
        x-show="showDetail"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
        @keydown.escape.window="closeDetail()"
    >
        <div x-show="showDetail" x-transition.opacity class="fixed inset-0 bg-black/50" @click="closeDetail()"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div
                x-show="showDetail"
                x-transition
                class="wo-modal-panel relative flex w-full max-w-5xl max-h-[90vh] flex-col !p-0"
                @click.stop
            >
                <div class="flex shrink-0 items-start justify-between gap-4 border-b border-gray-100 px-6 py-5">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900" x-text="detail?.nama ?? 'Mekanik'"></h3>
                        <p class="mt-1 text-sm text-gray-500">
                            <span x-text="detail?.no_hp ?? '—'"></span>
                            <span class="mx-2 text-gray-300">·</span>
                            <span x-text="(detail?.total_work_order ?? 0) + ' work order'"></span>
                            <span class="mx-2 text-gray-300">·</span>
                            <span>Rp <span x-text="formatRp(detail?.total_pendapatan)"></span></span>
                        </p>
                    </div>
                    <button type="button" @click="closeDetail()" class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Tutup">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="overflow-y-auto px-6 py-4">
                    <p class="mb-3 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Work order yang dikerjakan</p>
                    <div class="overflow-hidden rounded-xl border border-gray-200">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="bg-[#f7f3f4] text-[11px] font-semibold uppercase tracking-wider text-gray-500">
                                    <th class="px-4 py-2.5">Kode WO</th>
                                    <th class="px-4 py-2.5">Tanggal</th>
                                    <th class="px-4 py-2.5">Pelanggan</th>
                                    <th class="px-4 py-2.5">No. Polisi</th>
                                    <th class="px-4 py-2.5" x-show="allSpeedshops">Speedshop</th>
                                    <th class="px-4 py-2.5 text-right">Total</th>
                                    <th class="px-4 py-2.5 text-right"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="(order, idx) in (detail?.orders ?? [])" :key="idx">
                                    <tr class="hover:bg-[#fcfafb]">
                                        <td class="px-4 py-3 font-semibold text-gray-900" x-text="order.kode_servis"></td>
                                        <td class="px-4 py-3 text-gray-600" x-text="order.tanggal"></td>
                                        <td class="px-4 py-3 text-gray-700" x-text="order.pelanggan"></td>
                                        <td class="px-4 py-3 text-gray-600" x-text="order.polisi"></td>
                                        <td class="px-4 py-3 text-gray-700" x-show="allSpeedshops" x-text="order.speedshop ?? '—'"></td>
                                        <td class="px-4 py-3 text-right font-semibold tabular-nums text-gray-900">
                                            Rp <span x-text="formatRp(order.total)"></span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <a :href="order.url"
                                               class="inline-flex items-center rounded-lg border border-red-200 bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-600 transition hover:bg-brand-hover hover:text-white">
                                                Lihat WO
                                            </a>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex shrink-0 justify-end border-t border-gray-100 px-6 py-4">
                    <button type="button" @click="closeDetail()" class="btn-secondary">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.speedshop', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/wahyusafrizal/work/Github/77racingstore/resources/views/speedshop/mekanik-record/index.blade.php ENDPATH**/ ?>