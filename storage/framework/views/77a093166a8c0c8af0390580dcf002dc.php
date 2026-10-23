<?php
    use App\Support\ProductMasterCatalog;

    $masterSection = $masterSection ?? 'warehouse';
    $productMasterRoutePatterns = $masterSection === 'speedshop'
        ? ProductMasterCatalog::speedshopMasterRoutePatterns()
        : ProductMasterCatalog::warehouseMasterRoutePatterns();
    $productMasterRoutesCsv = implode(', ', $productMasterRoutePatterns);
    $masterRouteFor = fn (string $kind) => $masterSection === 'speedshop'
        ? ProductMasterCatalog::speedshopMasterRouteBase($kind)
        : ProductMasterCatalog::warehouseMasterRouteBase($kind);
?>
<div class="relative" x-data="{ open: <?php echo e(request()->routeIs($productMasterRoutePatterns) ? 'true' : 'false'); ?> }">
    <button @click="open = !open" type="button" class="flex w-full items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-medium transition <?php echo e(request()->routeIs($productMasterRoutePatterns) ? 'bg-white/10 text-white hover:bg-white/15' : 'text-white/75 hover:bg-white/10 hover:text-white'); ?>">
        <svg class="h-[18px] w-[18px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/></svg>
        <?php echo e($groupLabel ?? 'Master Produk'); ?>

        <svg class="ml-auto h-4 w-4 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="open" x-transition class="mt-1 space-y-0.5 pl-4">
        <?php $__currentLoopData = ProductMasterCatalog::kinds(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $kind): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <a href="<?php echo e(route($masterRouteFor($kind))); ?>" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition <?php echo e(request()->routeIs($masterRouteFor($kind).'*') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white'); ?>">
            <svg class="h-[16px] w-[16px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="<?php echo e(ProductMasterCatalog::menuIconPath($kind)); ?>"/></svg>
            <?php echo e(ProductMasterCatalog::label($kind)); ?>

        </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</div>
<?php /**PATH /Users/wahyusafrizal/work/Github/77racingstore/resources/views/partials/shared/product-master-menu.blade.php ENDPATH**/ ?>