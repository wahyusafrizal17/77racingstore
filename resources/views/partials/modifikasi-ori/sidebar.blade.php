<aside class="sticky top-0 flex h-screen w-[270px] flex-shrink-0 flex-col overflow-hidden border-r border-white/10 bg-[#0b0d12] text-white">
    <div class="flex flex-shrink-0 items-center gap-3 border-b border-white/10 px-5 py-4">
        <img src="{{ asset('images/logo-w.png') }}" alt="77racing" class="h-9 w-9 object-contain">
        <div class="min-w-0">
            <p class="truncate text-sm font-semibold tracking-wide text-white">77racing</p>
            <p class="text-[10px] uppercase tracking-[0.16em] text-white/45">{{ auth()->user()?->role ?? 'Karyawan' }}</p>
        </div>
    </div>

    <nav class="min-h-0 flex-1 space-y-1 overflow-y-auto px-4 pb-4 pt-3">
        @if(auth()->user()?->canAccessMarketingOmsetDashboard() || auth()->user()?->canAccessMarketingStaffResellerDashboard())
        <a href="{{ route('modifikasi-ori.dashboard') }}"
           class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-medium transition {{ request()->routeIs('modifikasi-ori.dashboard') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
            <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
            Dashboard
        </a>
        @endif

        @include('partials.shared.absensi-menu-link')

        @if(auth()->user()?->canUploadMoProductImages())
        <a href="{{ route('warehouse.master.produk') }}"
           class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-medium transition {{ request()->routeIs('warehouse.master.produk*') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
            <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            Produk 77racing
        </a>
        @endif

        @if(auth()->user()?->canViewWebsiteMonitoring())
        <a href="{{ route('modifikasi-ori.website-monitoring.index') }}"
           class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-medium transition {{ request()->routeIs('modifikasi-ori.website-monitoring.*') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
            <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18M7 16l4-5 4 3 5-7"/>
            </svg>
            Monitoring Website
        </a>
        @endif

        @if(auth()->user()?->canViewMarketplaceSales())
        @php
            $mpLinkActive = 'bg-brand text-brand-ink shadow-lg shadow-brand/35';
            $mpLinkIdle = 'text-white/65 hover:bg-white/10 hover:text-white';
            $mpGroupActive = 'bg-white/10 text-white hover:bg-white/15';
            $mpGroupIdle = 'text-white/75 hover:bg-white/10 hover:text-white';
            $mpSubIcon = 'h-[16px] w-[16px] flex-shrink-0';
            $mpOpen = request()->routeIs('modifikasi-ori.marketplace.*');
            $mpResellerActive = request()->routeIs('modifikasi-ori.marketplace.orders.reseller.*');
            $mpCustomerActive = request()->routeIs('modifikasi-ori.marketplace.orders.customer.*');
            $mpReportActive = request()->routeIs('modifikasi-ori.marketplace.report');
            $mpTargetsActive = request()->routeIs('modifikasi-ori.marketplace.targets');
            $mpRankingActive = request()->routeIs('modifikasi-ori.marketplace.ranking');
            $mpPromotionsActive = request()->routeIs('modifikasi-ori.marketplace.promotions.*');
            $mpBannersActive = request()->routeIs('modifikasi-ori.marketplace.banners.*');
            $mpVouchersActive = request()->routeIs('modifikasi-ori.marketplace.vouchers.*');
            $mpReturnsActive = request()->routeIs('modifikasi-ori.marketplace.returns*');
        @endphp
        <div class="relative" x-data="{ open: {{ $mpOpen ? 'true' : 'false' }} }">
            <button @click="open = !open" type="button" class="flex w-full items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-medium transition {{ $mpOpen ? $mpGroupActive : $mpGroupIdle }}">
                <svg class="h-[18px] w-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h2l2.2 9.2a2 2 0 002 1.5h7.7a2 2 0 001.9-1.4L21 7H6m4 12a1 1 0 100-2 1 1 0 000 2zm7 0a1 1 0 100-2 1 1 0 000 2z"/>
                </svg>
                <span class="min-w-0 flex-1 text-left">Marketplace</span>
                <svg class="h-4 w-4 shrink-0 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open" x-transition class="mt-1 space-y-0.5 pl-4">
                <a href="{{ route('modifikasi-ori.marketplace.orders.customer.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ $mpCustomerActive ? $mpLinkActive : $mpLinkIdle }}">
                    <svg class="{{ $mpSubIcon }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    <span class="min-w-0 flex-1">Pesanan Customer</span>
                </a>
                <a href="{{ route('modifikasi-ori.marketplace.orders.reseller.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ $mpResellerActive ? $mpLinkActive : $mpLinkIdle }}">
                    <svg class="{{ $mpSubIcon }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6-4a3 3 0 11-3-3"/></svg>
                    <span class="min-w-0 flex-1">Pesanan Reseller</span>
                </a>
                <a href="{{ route('modifikasi-ori.marketplace.returns') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ $mpReturnsActive ? $mpLinkActive : $mpLinkIdle }}">
                    <svg class="{{ $mpSubIcon }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                    <span class="min-w-0 flex-1">Return</span>
                </a>
                <a href="{{ route('modifikasi-ori.marketplace.promotions.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ $mpPromotionsActive ? $mpLinkActive : $mpLinkIdle }}">
                    <svg class="{{ $mpSubIcon }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v8m-4-4h8M5 4h14a2 2 0 012 2v3a3 3 0 000 6v3a2 2 0 01-2 2H5a2 2 0 01-2-2v-3a3 3 0 000-6V6a2 2 0 012-2z"/></svg>
                    <span class="min-w-0 flex-1">Promosi</span>
                </a>
                <a href="{{ route('modifikasi-ori.marketplace.banners.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ $mpBannersActive ? $mpLinkActive : $mpLinkIdle }}">
                    <svg class="{{ $mpSubIcon }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 5h16a1 1 0 011 1v4H3V6a1 1 0 011-1zm-1 8h18v5a1 1 0 01-1 1H4a1 1 0 01-1-1v-5zm4-5.5h2m3 0h6"/></svg>
                    <span class="min-w-0 flex-1">Slider Beranda</span>
                </a>
                <a href="{{ route('modifikasi-ori.marketplace.vouchers.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ $mpVouchersActive ? $mpLinkActive : $mpLinkIdle }}">
                    <svg class="{{ $mpSubIcon }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                    <span class="min-w-0 flex-1">Voucher</span>
                </a>
                <a href="{{ route('modifikasi-ori.marketplace.report') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ $mpReportActive ? $mpLinkActive : $mpLinkIdle }}">
                    <svg class="{{ $mpSubIcon }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-6m4 6V7m4 10v-3M5 21h14a2 2 0 002-2V5a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    <span class="min-w-0 flex-1">Laporan Reseller</span>
                </a>
                <a href="{{ route('modifikasi-ori.marketplace.targets') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ $mpTargetsActive ? $mpLinkActive : $mpLinkIdle }}">
                    <svg class="{{ $mpSubIcon }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3a9 9 0 109 9h-9V3z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 3.5A7.5 7.5 0 0120.5 9H15V3.5z"/></svg>
                    <span class="min-w-0 flex-1">Target Reseller</span>
                </a>
                <a href="{{ route('modifikasi-ori.marketplace.ranking') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ $mpRankingActive ? $mpLinkActive : $mpLinkIdle }}">
                    <svg class="{{ $mpSubIcon }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 21h8M12 17v4M7 4h10v5a5 5 0 01-10 0V4zM7 6H4v2a4 4 0 004 4m9-6h3v2a4 4 0 01-4 4"/></svg>
                    <span class="min-w-0 flex-1">Ranking Reseller</span>
                </a>
            </div>
        </div>
        @endif

        @if(auth()->user()?->canManageMarketingResellers())
        @php
            $marketingMasterOpen = request()->routeIs('warehouse.resellers.*', 'warehouse.mo-operators.*');
            $marketingMasterActive = $marketingMasterOpen;
            $marketingMasterLinkActive = 'bg-brand text-brand-ink shadow-lg shadow-brand/35';
            $marketingMasterLinkIdle = 'text-white/65 hover:bg-white/10 hover:text-white';
            $marketingMasterGroupActive = 'bg-white/10 text-white hover:bg-white/15';
            $marketingMasterGroupIdle = 'text-white/75 hover:bg-white/10 hover:text-white';
        @endphp
        <div class="relative" x-data="{ open: {{ $marketingMasterOpen ? 'true' : 'false' }} }">
            <button @click="open = !open" type="button" class="flex w-full items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-medium transition {{ $marketingMasterActive ? $marketingMasterGroupActive : $marketingMasterGroupIdle }}">
                <svg class="h-[18px] w-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <span class="min-w-0 flex-1 text-left">Channel & Reseller</span>
                <svg class="h-4 w-4 shrink-0 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open" x-transition class="mt-1 space-y-0.5 pl-4">
                <a href="{{ route('warehouse.resellers.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('warehouse.resellers.*') ? $marketingMasterLinkActive : $marketingMasterLinkIdle }}">
                    <svg class="h-[16px] w-[16px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    <span class="min-w-0 flex-1">Reseller</span>
                </a>
                <a href="{{ route('warehouse.mo-operators.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('warehouse.mo-operators.*') ? $marketingMasterLinkActive : $marketingMasterLinkIdle }}">
                    <svg class="h-[16px] w-[16px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    <span class="min-w-0 flex-1">Operator MO</span>
                </a>
            </div>
        </div>
        @endif

    </nav>
</aside>
