<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <link rel="icon" href="<?php echo e(asset('images/logo.png')); ?>" type="image/png">
    <title><?php echo $__env->yieldContent('title', 'Admin'); ?> - <?php echo e(config('app.name')); ?></title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800" rel="stylesheet" />
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    <?php echo $__env->make('partials.export-button-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('partials.shared.ss-pagination-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <style>[x-cloak] { display: none !important; }</style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php echo $__env->make('partials.notification-dropdown-head', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <?php echo $__env->yieldPushContent('styles'); ?>
</head>
<?php
    $hrFromSpeedshop = auth()->user()?->isSpeedshopOutletManager() ?? false;
?>
<body class="h-screen overflow-hidden bg-gray-100 antialiased" style="font-family: 'Inter', sans-serif;">
    <?php if($hrFromSpeedshop): ?>
    <div class="flex h-screen overflow-hidden" x-data="{
            sidebarOpen: window.matchMedia('(min-width: 1024px)').matches && localStorage.getItem('sidebarOpen') !== 'false',
        }" x-init="$watch('sidebarOpen', v => { if (window.matchMedia('(min-width: 1024px)').matches) localStorage.setItem('sidebarOpen', v); })">
        <div x-show="sidebarOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @click="sidebarOpen = false" class="fixed inset-0 z-40 bg-black/50 lg:hidden" x-cloak></div>
        <div class="fixed inset-y-0 left-0 z-50 flex h-screen flex-shrink-0 overflow-hidden transition-all duration-300 ease-in-out lg:relative lg:z-auto" :class="sidebarOpen ? 'w-[270px]' : 'w-0'">
            <?php echo $__env->make('partials.speedshop.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
        <div class="flex min-h-0 min-w-0 flex-1 flex-col overflow-y-auto">
            <?php echo $__env->make('partials.speedshop.navbar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                <?php echo $__env->make('partials.flash', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php echo $__env->yieldContent('content'); ?>
            </main>
        </div>
    </div>
    <?php else: ?>
    <div class="flex h-screen overflow-hidden" x-data="{ sidebarOpen: localStorage.getItem('sidebarOpen') !== 'false' }" x-init="$watch('sidebarOpen', v => localStorage.setItem('sidebarOpen', v))">
        <div class="flex h-screen flex-shrink-0 overflow-hidden transition-all duration-300 ease-in-out" :class="sidebarOpen ? 'w-[270px]' : 'w-0'">
            <?php echo $__env->make('partials.admin.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
        <div class="flex min-h-0 min-w-0 flex-1 flex-col overflow-y-auto">
            <?php echo $__env->make('partials.admin.navbar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

            <main class="flex-1 p-8">
                <?php echo $__env->make('partials.flash', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php echo $__env->yieldContent('content'); ?>
            </main>
        </div>
    </div>
    <?php endif; ?>
    <?php echo $__env->make('partials.shared.floating-direct-chat', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('partials.birthday-wish-modal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->yieldPushContent('scripts'); ?>
    <?php echo $__env->make('partials.csrf-keepalive', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</body>
</html>
<?php /**PATH /Users/wahyusafrizal/work/Github/77racingstore/resources/views/layouts/admin.blade.php ENDPATH**/ ?>