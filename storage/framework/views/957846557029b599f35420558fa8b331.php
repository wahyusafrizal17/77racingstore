<?php
    $fmt = fn ($v) => number_format((float) $v, 0, ',', '.');
    $topProducts = collect($dashboardTopProducts ?? []);
    $topProductsLabel = match ($mode ?? 'bulanan') {
        'harian' => 'hari ini',
        'bulanan' => 'bulan ini',
        'tahunan' => 'tahun ini',
        default => 'periode terpilih',
    };
?>

<div class="<?php echo \Illuminate\Support\Arr::toCssClasses(['top-products-block', 'top-products-empty' => $topProducts->isEmpty()]); ?>">
    <div class="top-products-hd">
        <h3>Produk Terlaris</h3>
        <p>Top 3 part <?php echo e($topProductsLabel); ?></p>
    </div>
    <?php if($topProducts->isNotEmpty()): ?>
    <div class="top-products-list">
        <?php $__currentLoopData = $topProducts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="top-product-row">
            <div class="top-product-rank"><?php echo e($index + 1); ?></div>
            <div class="top-product-info">
                <div class="top-product-name"><?php echo e($product['nama']); ?></div>
                <div class="top-product-code"><?php echo e($product['kode']); ?></div>
            </div>
            <div class="top-product-meta">
                <div class="top-product-qty"><?php echo e($fmt($product['qty'])); ?> pcs</div>
                <div class="top-product-value">Rp <?php echo e($fmt($product['nilai'])); ?></div>
            </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
    <?php else: ?>
    <p class="top-products-empty-text">Belum ada penjualan part pada periode ini.</p>
    <?php endif; ?>
</div>
<?php /**PATH /Users/wahyusafrizal/work/Github/77racingstore/resources/views/partials/speedshop/dashboard-top-products.blade.php ENDPATH**/ ?>