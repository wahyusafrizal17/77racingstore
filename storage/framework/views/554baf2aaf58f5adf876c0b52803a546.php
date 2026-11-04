<?php
    $speedshopOptions = collect();
    $selectedScope = null;
    if (auth()->check() && auth()->user()->section === 'speedshop' && ! auth()->user()->isOwner()) {
        $speedshopIds = auth()->user()->activeSpeedshopIds();
        if (count($speedshopIds) > 1) {
            $speedshopOptions = \App\Models\Speedshop::query()
                ->whereIn('id', $speedshopIds)
                ->orderBy('nama')
                ->get(['id', 'nama']);
            $selectedScope = auth()->user()->speedshopScopeId();
        }
    }
?>
<header class="flex h-14 items-center justify-between gap-2 border-b border-gray-200 bg-white px-3 sm:h-16 sm:gap-3 sm:px-4">
    <div class="flex min-w-0 flex-1 items-center gap-2 sm:gap-3">
        <button type="button" @click="sidebarOpen = !sidebarOpen" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-600 transition hover:bg-gray-50 hover:text-gray-900" :title="sidebarOpen ? 'Tutup menu sidebar' : 'Buka menu sidebar'">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <div class="min-w-0 sm:w-52 sm:shrink-0">
            <?php echo $__env->make('partials.global-product-stock-search', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
    </div>

    <div class="flex shrink-0 items-center justify-end gap-2 sm:gap-3">
        <?php if(auth()->user()?->isModifikasiOriHeadAudit()): ?>
            <?php echo $__env->make('partials.head-audit-scope-filter', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php elseif($speedshopOptions->isNotEmpty()): ?>
            <form method="POST" action="<?php echo e(route('speedshop.scope.update')); ?>" class="min-w-0 max-w-[8.5rem] sm:max-w-none">
                <?php echo csrf_field(); ?>
                <label for="speedshop-scope" class="sr-only">Filter Speedshop</label>
                <select id="speedshop-scope" name="scope" onchange="this.form.submit()"
                    class="h-10 w-full rounded-xl border border-gray-200 bg-white px-2 text-xs font-medium text-gray-700 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40 sm:px-3 sm:text-sm">
                    <?php if(auth()->user()->isManager() || auth()->user()->isSpeedshopAccounting()): ?>
                        <option value="all" <?php echo e($selectedScope === null ? 'selected' : ''); ?>>Semua Speedshop</option>
                    <?php endif; ?>
                    <?php $__currentLoopData = $speedshopOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $speedshopOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($speedshopOption->id); ?>" <?php echo e((int) $selectedScope === (int) $speedshopOption->id ? 'selected' : ''); ?>>
                            <?php echo e($speedshopOption->nama); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </form>
        <?php endif; ?>

        <?php echo $__env->make('partials.notification-category-dropdowns', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <?php echo $__env->make('partials.shared.nav-user-menu', ['navUserCompact' => true], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>
</header>
<?php /**PATH /Users/wahyusafrizal/work/Github/77racingstore/resources/views/partials/speedshop/navbar.blade.php ENDPATH**/ ?>