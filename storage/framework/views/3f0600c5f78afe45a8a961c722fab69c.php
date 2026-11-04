<?php
    $rows = $dashboardTargetAchievement ?? [];
    $fmt = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.');
    $pct = fn ($v) => number_format((int) $v, 0, ',', '.').'%';
    $signed = function (float $v) use ($fmt): string {
        $sign = $v > 0 ? '+' : '';

        return $sign.$fmt($v);
    };
?>
<?php if($rows !== []): ?>
<section class="mk-perf-section ss-dash-enter" style="--ss-delay: 420ms">
    <div class="section-hd">
        <h2>Pencapaian Target Speedshop</h2>
        <p>
            Sparepart 65% · Jasa 35%
            <?php if(! empty($currentPeriodLabel)): ?>
                · <?php echo e($currentPeriodLabel); ?>

            <?php endif; ?>
        </p>
    </div>
    <div class="panel-card mk-perf-card">
        <div class="table-wrap">
            <table class="mk-perf-table ss-target-table">
                <thead>
                    <tr>
                        <th class="text-center">No</th>
                        <th>Cabang</th>
                        <th class="text-right">Sparepart</th>
                        <th class="text-right">Target 65%</th>
                        <th class="text-center">%</th>
                        <th class="text-right">Jasa</th>
                        <th class="text-right">Target 35%</th>
                        <th class="text-center">%</th>
                        <th class="text-right">Total revenue</th>
                        <th class="text-right">M-1</th>
                        <th class="text-right">Growth</th>
                        <th class="text-right">Target bulan</th>
                        <th class="text-right">Target harian</th>
                        <th class="text-center">% target</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td class="text-center"><?php echo e($i + 1); ?></td>
                        <td><strong><?php echo e($row['nama']); ?></strong></td>
                        <td class="text-right"><?php echo e($fmt($row['sparepart_actual'])); ?></td>
                        <td class="text-right"><?php echo e($fmt($row['sparepart_target'])); ?></td>
                        <td class="text-center"><?php echo e($pct($row['sparepart_percent'])); ?></td>
                        <td class="text-right"><?php echo e($fmt($row['jasa_actual'])); ?></td>
                        <td class="text-right"><?php echo e($fmt($row['jasa_target'])); ?></td>
                        <td class="text-center"><?php echo e($pct($row['jasa_percent'])); ?></td>
                        <td class="text-right amount-total"><?php echo e($fmt($row['total_revenue'])); ?></td>
                        <td class="text-right"><?php echo e($fmt($row['prev_revenue'])); ?></td>
                        <td class="<?php echo \Illuminate\Support\Arr::toCssClasses(['text-right', 'text-emerald-600' => ($row['growth'] ?? 0) >= 0, 'text-red-600' => ($row['growth'] ?? 0) < 0]); ?>"><?php echo e($signed((float) ($row['growth'] ?? 0))); ?></td>
                        <td class="text-right"><?php echo e($fmt($row['target_bulanan'])); ?></td>
                        <td class="text-right"><?php echo e($fmt($row['target_harian'])); ?></td>
                        <td class="text-center"><strong><?php echo e($pct($row['percent'])); ?></strong></td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
<?php endif; ?>
<?php /**PATH /Users/wahyusafrizal/work/Github/77racingstore/resources/views/partials/speedshop/dashboard-manager-target-achievement.blade.php ENDPATH**/ ?>