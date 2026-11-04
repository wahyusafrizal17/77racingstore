<?php if (! (auth()->user()?->isOwner())): ?>
<?php
    $portalHrNavLink = 'flex w-full items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-medium transition';
?>
<div class="space-y-1">
    <a href="<?php echo e(route('absensi.index')); ?>"
       class="<?php echo e($portalHrNavLink); ?> <?php echo e(request()->routeIs('absensi.index') || request()->routeIs('absensi.store') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white'); ?>">
        <svg class="h-[18px] w-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        Absensi
    </a>

    <?php echo $__env->make('partials.shared.izin-menu-link', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('partials.shared.izin-validasi-menu-link', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('partials.shared.absensi-validasi-menu-link', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('partials.shared.absensi-rekap-menu-link', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</div>
<?php endif; ?>
<?php /**PATH /Users/wahyusafrizal/work/Github/77racingstore/resources/views/partials/shared/absensi-menu-link.blade.php ENDPATH**/ ?>