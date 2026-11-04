<?php
    $fmt = $fmt ?? fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.');
    $showCabang = $showCabang ?? true;
    $rows = $rows ?? [];
    $colspan = $showCabang ? 10 : 9;
?>
<div class="panel-card mk-perf-card">
    <div class="panel-card-hd">
        <div>
            <div class="panel-card-title"><?php echo e($title); ?></div>
            <p class="panel-card-sub">Diurutkan dari pencapaian tertinggi</p>
        </div>
    </div>
    <div class="table-wrap">
        <table class="mk-perf-table">
            <thead>
                <tr>
                    <th class="text-center">Rank</th>
                    <th>Nama Mekanik</th>
                    <th>Jabatan</th>
                    <?php if($showCabang): ?>
                    <th>Cabang</th>
                    <?php endif; ?>
                    <th class="text-center">Jumlah WO</th>
                    <th class="text-right">Pendapatan Jasa</th>
                    <th class="text-right">Pendapatan Sparepart</th>
                    <th class="text-right">Pencapaian</th>
                    <th class="text-right">Target</th>
                    <th class="text-center">%</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td class="text-center"><span class="mk-perf-rank"><?php echo e($row['rank'] ?? ''); ?></span></td>
                    <td><strong><?php echo e($row['nama'] ?? '-'); ?></strong></td>
                    <td><?php echo e($row['jabatan'] ?? 'Mekanik'); ?></td>
                    <?php if($showCabang): ?>
                    <td><?php echo e($row['cabang'] ?? '-'); ?></td>
                    <?php endif; ?>
                    <td class="text-center"><strong><?php echo e(number_format((int) ($row['wo_count'] ?? 0), 0, ',', '.')); ?></strong></td>
                    <td class="text-right"><?php echo e($fmt($row['jasa'] ?? 0)); ?></td>
                    <td class="text-right"><?php echo e($fmt($row['sparepart'] ?? 0)); ?></td>
                    <td class="text-right amount-total"><?php echo e($fmt($row['pencapaian'] ?? 0)); ?></td>
                    <td class="text-right"><?php echo e($fmt($row['target'] ?? 0)); ?></td>
                    <td class="text-center"><strong><?php echo e(number_format((int) ($row['percent'] ?? 0), 0, ',', '.')); ?>%</strong></td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="<?php echo e($colspan); ?>" class="table-empty">Belum ada work order selesai pada periode ini.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php /**PATH /Users/wahyusafrizal/work/Github/77racingstore/resources/views/partials/speedshop/dashboard-manager-mekanik-performance-table.blade.php ENDPATH**/ ?>