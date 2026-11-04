<?php
    $fmt = fn ($v) => number_format((float) $v, 0, ',', '.');
    $items = collect($list['items'] ?? []);
    $metric = $list['metric'] ?? 'product';
    $compareEnabled = ! empty($list['compare_enabled']);
    $comparePeriodLabel = $list['compare_period_label'] ?? 'Periode lalu';
    $qtyUnit = ($list['key'] ?? '') === 'jasa' ? 'x' : 'pcs';
    $listKey = (string) ($list['key'] ?? '');
    $accent = match (true) {
        str_starts_with($listKey, 'part') => 'blue',
        str_starts_with($listKey, 'oli') => 'amber',
        $listKey === 'jasa' => 'purple',
        $listKey === 'tipe_motor' => 'red',
        $listKey === 'sumber_kedatangan' => 'green',
        default => 'gray',
    };
?>

<div class="<?php echo \Illuminate\Support\Arr::toCssClasses(['top-list-card', 'top-list-card--'.$accent, 'has-compare' => $compareEnabled, 'is-empty' => $items->isEmpty()]); ?>">
    <div class="top-list-card-hd">
        <h3><?php echo e($list['title'] ?? 'Terlaris'); ?></h3>
        <p><?php echo e($list['subtitle'] ?? ''); ?></p>
    </div>
    <?php if($items->isNotEmpty()): ?>
    <div class="top-list-card-body">
        <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php
            $deltaPct = isset($item['compare_delta_pct']) ? (float) $item['compare_delta_pct'] : null;
            $compareQty = (int) ($item['compare_qty'] ?? 0);
        ?>
        <div class="top-list-row">
            <div class="top-list-rank"><?php echo e($index + 1); ?></div>
            <div class="top-list-main">
                <div class="top-list-row-top">
                    <div class="top-list-info">
                        <div class="top-list-name"><?php echo e($item['nama'] ?? '-'); ?></div>
                        <?php if(($item['kode'] ?? '-') !== '-'): ?>
                        <div class="top-list-code"><?php echo e($item['kode']); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="top-list-meta">
                        <?php if($metric === 'order'): ?>
                        <div class="top-list-qty"><?php echo e($fmt($item['qty'] ?? 0)); ?> order</div>
                        <?php else: ?>
                        <div class="top-list-qty"><?php echo e($fmt($item['qty'] ?? 0)); ?> <?php echo e($qtyUnit); ?></div>
                        <div class="top-list-value">Rp <?php echo e($fmt($item['nilai'] ?? 0)); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if($compareEnabled): ?>
                <div class="top-list-compare">
                    <span class="top-list-compare-prev">
                        <?php if($metric === 'order'): ?>
                            <?php echo e($comparePeriodLabel); ?> · <?php echo e($fmt($compareQty)); ?> order
                        <?php else: ?>
                            <?php echo e($comparePeriodLabel); ?> · <?php echo e($fmt($compareQty)); ?> <?php echo e($qtyUnit); ?>

                        <?php endif; ?>
                    </span>
                    <?php if($deltaPct !== null): ?>
                    <span class="<?php echo \Illuminate\Support\Arr::toCssClasses(['dash-cmp-badge', 'sm', 'up' => $deltaPct >= 0, 'down' => $deltaPct < 0]); ?>"><?php echo e($deltaPct >= 0 ? '+' : ''); ?><?php echo e(number_format($deltaPct, 1, ',', '.')); ?>%</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
    <?php else: ?>
    <p class="top-list-empty"><?php echo e($list['empty'] ?? 'Belum ada data pada periode ini.'); ?></p>
    <?php endif; ?>
</div>
<?php /**PATH /Users/wahyusafrizal/work/Github/77racingstore/resources/views/partials/speedshop/dashboard-top-list-card.blade.php ENDPATH**/ ?>