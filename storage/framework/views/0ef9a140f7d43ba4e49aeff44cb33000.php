<?php
    $user = auth()->user();
    $pendingCount = $user?->pendingLeaveValidationCount() ?? 0;
    $portalHrNavLink = $portalHrNavLink ?? 'flex w-full items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-medium transition';
?>
<?php if($user && $user->isAttendanceValidator()): ?>
<a href="<?php echo e(route('izin.validasi.index')); ?>"
   class="<?php echo e($portalHrNavLink); ?> <?php echo e(request()->routeIs('izin.validasi*') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white'); ?>">
    <svg class="h-[18px] w-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
    Validasi Izin
    <?php if($pendingCount > 0): ?>
    <span class="ml-auto rounded-full bg-amber-400 px-2 py-0.5 text-[10px] font-bold leading-none text-amber-950"><?php echo e($pendingCount); ?></span>
    <?php endif; ?>
</a>
<?php endif; ?>
<?php /**PATH /Users/wahyusafrizal/work/Github/77racingstore/resources/views/partials/shared/izin-validasi-menu-link.blade.php ENDPATH**/ ?>