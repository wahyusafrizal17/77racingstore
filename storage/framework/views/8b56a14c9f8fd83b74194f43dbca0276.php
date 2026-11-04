<?php
    $navUser = Auth::user();
    $navFotoUrl = $navUser?->fotoProfilUrl() ?? asset('images/logo.png');
    $navHasFoto = (bool) ($navUser?->hasCustomFotoProfil());
    $navCompact = (bool) ($navUserCompact ?? false);
?>
<div class="relative shrink-0" x-data="{ open: false }">
    <button @click="open = !open" class="flex items-center gap-2 rounded-xl px-1.5 py-1.5 transition hover:bg-gray-50 sm:gap-3 sm:px-3 sm:py-2" title="<?php echo e($navUser?->name); ?> · <?php echo e($navUser?->email); ?>">
        <div class="<?php echo e($navCompact ? 'hidden text-right md:block' : 'text-right'); ?>">
            <p class="whitespace-nowrap text-sm font-semibold leading-tight text-gray-800"><?php echo e($navUser?->name); ?></p>
            <p class="whitespace-nowrap text-xs leading-tight text-gray-500"><?php echo e($navUser?->email); ?></p>
        </div>
        <img
            src="<?php echo e($navFotoUrl); ?>"
            alt="<?php echo e($navUser?->name); ?>"
            class="h-9 w-9 shrink-0 rounded-lg border border-gray-200 bg-white object-cover <?php echo e($navHasFoto ? '' : 'p-1 object-contain'); ?>"
        >
    </button>

    <div x-show="open" @click.away="open = false" x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-1"
         class="absolute right-0 z-50 mt-2 w-48 rounded-xl border border-gray-100 bg-white py-2 shadow-xl">
        <a href="<?php echo e(route('profil.index')); ?>" class="flex w-full items-center gap-3 rounded-lg px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
            <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            Profil Saya
        </a>
        <form method="POST" action="<?php echo e(route('logout')); ?>">
            <?php echo csrf_field(); ?>
            <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-4 py-2.5 text-sm font-medium text-red-500 transition hover:bg-red-50">
                <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                Logout
            </button>
        </form>
    </div>
</div>
<?php /**PATH /Users/wahyusafrizal/work/Github/77racingstore/resources/views/partials/shared/nav-user-menu.blade.php ENDPATH**/ ?>