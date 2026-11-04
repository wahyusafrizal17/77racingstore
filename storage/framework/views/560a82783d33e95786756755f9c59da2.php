<?php if(! empty($dashboardManagerTopLists)): ?>
<?php
    $topListCards = collect($dashboardManagerTopLists)->values();
    $periodHint = $currentPeriodLabel ?? null;
    $trioKeys = ['jasa', 'tipe_motor', 'sumber_kedatangan'];
    $catalogCards = $topListCards->reject(fn ($list) => in_array($list['key'] ?? '', $trioKeys, true));
    $trioCards = $topListCards->filter(fn ($list) => in_array($list['key'] ?? '', $trioKeys, true));
?>
<section class="top-lists-section ss-dash-enter" style="--ss-delay: 460ms; margin-top: 20px;">
    <div class="section-hd">
        <h2>Insight Penjualan</h2>
        <p>
            Produk, jasa, kendaraan, dan sumber kedatangan terbanyak
            <?php if(! empty($compareEnabled) && ! empty($comparePeriodLabel)): ?>
                · <?php echo e($currentPeriodLabel ?? $periodHint); ?> vs <?php echo e($comparePeriodLabel); ?>

            <?php elseif($periodHint): ?>
                · <?php echo e($periodHint); ?>

            <?php endif; ?>
        </p>
    </div>
    <?php if($catalogCards->isNotEmpty()): ?>
    <div class="top-lists-grid">
        <?php $__currentLoopData = $catalogCards; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $list): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php echo $__env->make('partials.speedshop.dashboard-top-list-card', ['list' => $list], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
    <?php endif; ?>
    <?php if($trioCards->isNotEmpty()): ?>
    <div class="top-lists-grid top-lists-grid--trio">
        <?php $__currentLoopData = $trioCards; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $list): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php echo $__env->make('partials.speedshop.dashboard-top-list-card', ['list' => $list], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
    <?php endif; ?>
</section>
<?php endif; ?>
<?php /**PATH /Users/wahyusafrizal/work/Github/77racingstore/resources/views/partials/speedshop/dashboard-manager-top-lists.blade.php ENDPATH**/ ?>