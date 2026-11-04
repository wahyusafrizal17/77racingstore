<?php
    $user = auth()->user();
    $portalHrNavLink = $portalHrNavLink ?? 'flex w-full items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-medium transition';
?>
<?php if($user && $user->canSeeAttendanceRecap()): ?>
<a href="<?php echo e(route('absensi.rekap.index')); ?>"
   class="<?php echo e($portalHrNavLink); ?> <?php echo e(request()->routeIs('absensi.rekap*') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white'); ?>">
    <svg class="h-[18px] w-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
    Rekap Absensi
</a>
<?php endif; ?>
<?php /**PATH /Users/wahyusafrizal/work/Github/77racingstore/resources/views/partials/shared/absensi-rekap-menu-link.blade.php ENDPATH**/ ?>