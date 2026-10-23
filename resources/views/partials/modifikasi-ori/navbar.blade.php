<header class="flex h-16 items-center justify-between border-b border-gray-200 bg-white px-4">
    <div class="flex items-center gap-3">
        <button type="button" @click="sidebarOpen = !sidebarOpen" class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-600 transition hover:bg-gray-50 hover:text-gray-900" :title="sidebarOpen ? 'Tutup menu sidebar' : 'Buka menu sidebar'">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        @include('partials.global-product-stock-search')
    </div>

    <div class="flex items-center gap-3">
        @include('partials.notification-category-dropdowns')

        @include('partials.shared.nav-user-menu')
    </div>
</header>
