<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('images/logo.png') }}" type="image/png">
    <title>@yield('title', 'Dashboard') - {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.export-button-styles')
    @include('partials.shared.ss-pagination-styles')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
    @stack('styles')
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
    @include('partials.notification-dropdown-head')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @if(auth()->user()?->isModifikasiOriHeadAudit())
    <style>
        .finance-audit-view-only .wo-icon-btn,
        .finance-audit-view-only .finance-mutate { display: none !important; }
    </style>
    @endif
</head>
<body class="h-screen overflow-hidden bg-gray-100 antialiased{{ auth()->user()?->isModifikasiOriHeadAudit() ? ' finance-audit-view-only' : '' }}" style="font-family: 'Inter', sans-serif;">
    <div class="flex h-screen overflow-hidden" x-data="{
            sidebarOpen: window.matchMedia('(min-width: 1024px)').matches && localStorage.getItem('sidebarOpen') !== 'false',
        }" x-init="$watch('sidebarOpen', v => { if (window.matchMedia('(min-width: 1024px)').matches) localStorage.setItem('sidebarOpen', v); })">
        <div x-show="sidebarOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @click="sidebarOpen = false" class="fixed inset-0 z-40 bg-black/50 lg:hidden" x-cloak></div>
        <div class="fixed inset-y-0 left-0 z-50 flex h-screen flex-shrink-0 overflow-hidden transition-all duration-300 ease-in-out lg:relative lg:z-auto" :class="sidebarOpen ? 'w-[270px]' : 'w-0'">
            @include('partials.speedshop.sidebar')
        </div>
        <div class="flex min-h-0 min-w-0 flex-1 flex-col overflow-y-auto">
            @include('partials.speedshop.navbar')

            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                @include('partials.flash')
                @yield('content')
            </main>
        </div>
    </div>
    @include('partials.shared.floating-direct-chat')
    @include('partials.finance-audit-readonly-ui')
    @include('partials.birthday-wish-modal')
    @stack('scripts')
    @include('partials.csrf-keepalive')
</body>
</html>
