<a href="<?php echo e(route('admin.dashboard')); ?>"
   class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-medium transition <?php echo e(request()->routeIs('admin.dashboard') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white'); ?>">
    <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
    Dashboard HR
</a>

<?php
    $absensiMenuOpen = request()->routeIs('admin.attendance-locations*')
        || request()->routeIs('admin.attendance-schedule*')
        || request()->routeIs('admin.attendance-validators*')
        || request()->routeIs('admin.attendance-recap*')
        || request()->routeIs('izin*')
        || request()->routeIs('absensi.validasi*')
        || request()->routeIs('absensi.rekap*');
?>
<div class="relative" x-data="{ open: <?php echo e($absensiMenuOpen ? 'true' : 'false'); ?> }">
    <button @click="open = !open" type="button"
            class="flex w-full items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-medium transition <?php echo e($absensiMenuOpen ? 'bg-white/10 text-white hover:bg-white/15' : 'text-white/65 hover:bg-white/10 hover:text-white'); ?>">
        <svg class="h-[18px] w-[18px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        Absensi
        <svg class="ml-auto h-4 w-4 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="open" x-cloak x-transition class="mt-1 space-y-0.5 pl-4">
        <a href="<?php echo e(route('admin.attendance-locations.index')); ?>"
           class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition <?php echo e(request()->routeIs('admin.attendance-locations*') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white'); ?>">
            <svg class="h-[16px] w-[16px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Lokasi Absensi
        </a>
        <a href="<?php echo e(route('admin.attendance-schedule.index')); ?>"
           class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition <?php echo e(request()->routeIs('admin.attendance-schedule*') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white'); ?>">
            <svg class="h-[16px] w-[16px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            Jadwal & Libur
        </a>
        <a href="<?php echo e(route('admin.attendance-validators.index')); ?>"
           class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition <?php echo e(request()->routeIs('admin.attendance-validators*') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white'); ?>">
            <svg class="h-[16px] w-[16px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            Penanggung Jawab Validasi
        </a>
        <?php $hrPendingLeave = auth()->user()?->isAttendanceValidator() ? auth()->user()->pendingLeaveValidationCount() : 0; ?>
        <a href="<?php echo e(route('izin.index')); ?>"
           class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition <?php echo e(request()->routeIs('izin.index') || request()->routeIs('izin.show') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white'); ?>">
            <svg class="h-[16px] w-[16px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Pengajuan Izin
        </a>
        <?php if(auth()->user()?->isAttendanceValidator()): ?>
        <a href="<?php echo e(route('izin.validasi.index')); ?>"
           class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition <?php echo e(request()->routeIs('izin.validasi*') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white'); ?>">
            <svg class="h-[16px] w-[16px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            Validasi Izin
            <?php if($hrPendingLeave > 0): ?>
            <span class="ml-auto rounded-full bg-amber-400 px-2 py-0.5 text-[10px] font-bold text-amber-950"><?php echo e($hrPendingLeave); ?></span>
            <?php endif; ?>
        </a>
        <?php endif; ?>
        <?php $hrPendingAbsensi = auth()->user()?->canValidateAttendance() ? auth()->user()->pendingAttendanceValidationCount() : 0; ?>
        <?php if(auth()->user()?->canValidateAttendance()): ?>
        <a href="<?php echo e(route('absensi.validasi.index')); ?>"
           class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition <?php echo e(request()->routeIs('absensi.validasi*') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white'); ?>">
            <svg class="h-[16px] w-[16px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            Validasi Absensi
            <?php if($hrPendingAbsensi > 0): ?>
            <span class="ml-auto rounded-full bg-amber-400 px-2 py-0.5 text-[10px] font-bold text-amber-950"><?php echo e($hrPendingAbsensi); ?></span>
            <?php endif; ?>
        </a>
        <?php endif; ?>
        <?php if(auth()->user()?->canValidateAttendance()): ?>
        <a href="<?php echo e(route('absensi.rekap.index')); ?>"
           class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition <?php echo e(request()->routeIs('absensi.rekap*') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white'); ?>">
            <svg class="h-[16px] w-[16px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            Rekap Absensi
        </a>
        <?php endif; ?>
    </div>
</div>

<a href="<?php echo e(route('admin.branches.index')); ?>"
   class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-medium transition <?php echo e(request()->routeIs('admin.branches*') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white'); ?>">
    <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
    Cabang Speedshop
</a>
<a href="<?php echo e(route('admin.users.index')); ?>"
   class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-medium transition <?php echo e(request()->routeIs('admin.users*') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white'); ?>">
    <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
    Karyawan
</a>
<a href="<?php echo e(route('admin.roles.index')); ?>"
   class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-medium transition <?php echo e(request()->routeIs('admin.roles*') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white'); ?>">
    <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
    Role
</a>
<?php /**PATH /Users/wahyusafrizal/work/Github/77racingstore/resources/views/partials/admin/sidebar-nav-items.blade.php ENDPATH**/ ?>