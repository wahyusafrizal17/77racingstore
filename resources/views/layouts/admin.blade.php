<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('images/logo.png') }}" type="image/png">
    <title>@yield('title', 'Admin') - {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.export-button-styles')
    @include('partials.shared.ss-pagination-styles')
    <style>[x-cloak] { display: none !important; }</style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @include('partials.notification-dropdown-head')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @stack('styles')
</head>
@php
    $hrFromSpeedshop = auth()->user()?->isSpeedshopOutletManager() ?? false;
@endphp
<body class="h-screen overflow-hidden bg-gray-100 antialiased" style="font-family: 'Inter', sans-serif;">
    @if($hrFromSpeedshop)
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
    @else
    <div class="flex h-screen overflow-hidden" x-data="{ sidebarOpen: localStorage.getItem('sidebarOpen') !== 'false' }" x-init="$watch('sidebarOpen', v => localStorage.setItem('sidebarOpen', v))">
        <div class="flex h-screen flex-shrink-0 overflow-hidden transition-all duration-300 ease-in-out" :class="sidebarOpen ? 'w-[270px]' : 'w-0'">
            @include('partials.admin.sidebar')
        </div>
        <div class="flex min-h-0 min-w-0 flex-1 flex-col overflow-y-auto">
            @include('partials.admin.navbar')

            <main class="flex-1 p-8">
                @include('partials.flash')
                @yield('content')
            </main>
        </div>
    </div>
    @endif
    @include('partials.shared.floating-direct-chat')
    @include('partials.birthday-wish-modal')
    @stack('scripts')
    @include('partials.csrf-keepalive')
</body>
</html>
