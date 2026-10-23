<?php
    $perf = $dashboardMekanikPerformance ?? ['all' => [], 'by_cabang' => []];
    $allRows = $perf['all'] ?? [];
    $cabangTables = $perf['by_cabang'] ?? [];
    $showAllCabang = ! empty($dashboardAllSpeedshops) && count($cabangTables) > 1;
    $periodHint = $currentPeriodLabel ?? null;
    $fmt = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.');
?>
<?php if($allRows !== [] || $cabangTables !== []): ?>
<section class="mk-perf-section ss-dash-enter" style="--ss-delay: 500ms">
    <div class="section-hd">
        <h2>Performance Mekanik</h2>
        <p>
            Ranking pencapaian jasa + sparepart
            <?php if(! empty($compareEnabled) && ! empty($comparePeriodLabel)): ?>
                · <?php echo e($currentPeriodLabel ?? $periodHint); ?> vs <?php echo e($comparePeriodLabel); ?>

            <?php elseif($periodHint): ?>
                · <?php echo e($periodHint); ?>

            <?php endif; ?>
        </p>
    </div>

    <?php if($showAllCabang): ?>
        <?php echo $__env->make('partials.speedshop.dashboard-manager-mekanik-performance-table', [
            'title' => 'Semua Cabang',
            'rows' => $allRows,
            'showCabang' => true,
            'fmt' => $fmt,
        ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php else: ?>
        <?php echo $__env->make('partials.speedshop.dashboard-manager-mekanik-performance-table', [
            'title' => isset($cabangTables[0]['cabang']) ? 'Cabang '.$cabangTables[0]['cabang'] : 'Cabang',
            'rows' => $cabangTables[0]['rows'] ?? $allRows,
            'showCabang' => false,
            'fmt' => $fmt,
        ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php endif; ?>
</section>
<?php endif; ?>
<?php /**PATH /Users/wahyusafrizal/work/Github/77racingstore/resources/views/partials/speedshop/dashboard-manager-mekanik-performance.blade.php ENDPATH**/ ?>