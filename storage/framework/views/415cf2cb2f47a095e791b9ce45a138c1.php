<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <link rel="icon" href="<?php echo e(asset('images/logo.png')); ?>" type="image/png">
    <title><?php echo $__env->yieldContent('title', 'Dashboard'); ?> - <?php echo e(config('app.name')); ?></title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800" rel="stylesheet" />
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    <?php echo $__env->make('partials.export-button-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('partials.shared.ss-pagination-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
    <?php echo $__env->yieldPushContent('styles'); ?>
    <style>
        [x-cloak] { display: none !important; }
        .ts-wrapper { font-family: inherit; }
        .ts-wrapper.single .ts-control { border: 1px solid #e5e7eb; border-radius: 0.75rem; background: #fff; font-size: 0.875rem; height: 2.5rem; padding: 0 1rem; display: flex; align-items: center; box-shadow: none; cursor: pointer; }
        .ts-wrapper.single .ts-control::after { border-color: #9ca3af transparent transparent; margin-top: 1px; }
        .ts-wrapper.single .ts-control input { font-size: 0.875rem; }
        .ts-wrapper.single .ts-control .item { font-size: 0.875rem; color: #111827; }
        .ts-wrapper.single.focus .ts-control { border-color: #8CFA01; box-shadow: 0 0 0 2px rgba(140,250,1,0.35); }
        .ts-wrapper .ts-control > input::placeholder { color: #9ca3af; }
        .ts-dropdown { border: 1px solid #e5e7eb; border-radius: 0.75rem; margin-top: 4px; font-size: 0.875rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -2px rgba(0,0,0,0.1); overflow: hidden; }
        .ts-dropdown .ts-dropdown-content { padding: 0.25rem 0; max-height: min(220px, calc(100dvh - 96px)); overflow-y: auto; overscroll-behavior: contain; -webkit-overflow-scrolling: touch; }
        .ts-dropdown .option { padding: 0.5rem 1rem; font-size: 0.875rem; color: #374151; cursor: pointer; }
        .ts-dropdown .option:hover, .ts-dropdown .option.active { background: #f3fde0; color: #3d6b00; }
        .ts-dropdown .option.selected { background: #8CFA01; color: #142000; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php echo $__env->make('partials.notification-dropdown-head', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <?php if(auth()->user()?->isModifikasiOriHeadAudit()): ?>
    <style>
        .finance-audit-view-only .wo-icon-btn,
        .finance-audit-view-only .finance-mutate { display: none !important; }
    </style>
    <?php endif; ?>
</head>
<body class="h-screen overflow-hidden bg-gray-100 antialiased<?php echo e(auth()->user()?->isModifikasiOriHeadAudit() ? ' finance-audit-view-only' : ''); ?>" style="font-family: 'Inter', sans-serif;">
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
    <?php echo $__env->make('partials.shared.floating-direct-chat', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('partials.finance-audit-readonly-ui', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->make('partials.birthday-wish-modal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php echo $__env->yieldPushContent('scripts'); ?>
    <?php echo $__env->make('partials.csrf-keepalive', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</body>
</html>
<?php /**PATH /Users/wahyusafrizal/work/Github/77racingstore/resources/views/layouts/speedshop.blade.php ENDPATH**/ ?>