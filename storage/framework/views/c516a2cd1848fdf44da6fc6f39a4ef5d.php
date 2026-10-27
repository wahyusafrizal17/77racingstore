<?php
    $user = auth()->user();
    $portalHrNavLink = $portalHrNavLink ?? 'flex w-full items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-medium transition';
?>
<?php if($user && $user->isVisibleInAdminKaryawan() && ! $user->isOwner()): ?>
<a href="<?php echo e(route('izin.index')); ?>"
   class="<?php echo e($portalHrNavLink); ?> <?php echo e(request()->routeIs('izin.index') || request()->routeIs('izin.store') || request()->routeIs('izin.show') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white'); ?>">
    <svg class="h-[18px] w-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
    Pengajuan Izin
</a>
<?php endif; ?>
<?php /**PATH /Users/wahyusafrizal/work/Github/77racingstore/resources/views/partials/shared/izin-menu-link.blade.php ENDPATH**/ ?>