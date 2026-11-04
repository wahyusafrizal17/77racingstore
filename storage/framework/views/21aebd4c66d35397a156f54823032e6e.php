<?php
    use App\Support\ProductMasterCatalog;

    $stokRoutePatterns = $stokRoutePatterns ?? ProductMasterCatalog::stokRoutePatterns();
    $groupLabel = $groupLabel ?? 'Inventori Produk';
?>
<div class="relative" x-data="{ open: <?php echo e(request()->routeIs($stokRoutePatterns) ? 'true' : 'false'); ?> }">
    <button @click="open = !open" type="button" class="flex w-full items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-medium transition <?php echo e(request()->routeIs($stokRoutePatterns) ? 'bg-white/10 text-white hover:bg-white/15' : 'text-white/75 hover:bg-white/10 hover:text-white'); ?>">
        <svg class="h-[18px] w-[18px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
        <?php echo e($groupLabel); ?>

        <svg class="ml-auto h-4 w-4 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="open" x-transition class="mt-1 space-y-0.5 pl-4">
        <?php $__currentLoopData = ProductMasterCatalog::kinds(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $kind): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php $stokRoute = ProductMasterCatalog::stokRouteBase($kind); ?>
        <a href="<?php echo e(route($stokRoute)); ?>" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition <?php echo e(request()->routeIs($stokRoute.'*') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white'); ?>">
            <svg class="h-[16px] w-[16px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="<?php echo e(ProductMasterCatalog::menuIconPath($kind)); ?>"/></svg>
            <?php echo e(ProductMasterCatalog::label($kind)); ?>

        </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</div>
<?php /**PATH /Users/wahyusafrizal/work/Github/77racingstore/resources/views/partials/shared/speedshop-stok-menu.blade.php ENDPATH**/ ?>