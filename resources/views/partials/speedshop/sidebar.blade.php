<aside class="sticky top-0 flex h-screen w-[270px] flex-shrink-0 flex-col overflow-hidden border-r border-white/10 bg-[#0b0d12] text-white">
    <div class="flex flex-shrink-0 items-center gap-3 border-b border-white/10 px-5 py-4">
        <img src="{{ asset('images/logo-w.png') }}" alt="77 Racing Store" class="h-9 w-9 object-contain">
        <div class="min-w-0">
            <p class="truncate text-sm font-semibold tracking-wide text-white">77 Racing Store</p>
            <p class="text-[10px] uppercase tracking-[0.16em] text-white/45">{{ auth()->user()?->isOwner() ? 'Owner Portal' : 'Management System' }}</p>
        </div>
    </div>

    @php
        $isAccounting = auth()->user()?->isAccounting();
        $isOwner = auth()->user()?->isOwner();
        $ssMekanik = auth()->user()?->isSpeedshopMekanik() ?? false;
        $ssStafOnly = auth()->user()?->isSpeedshop() && auth()->user()?->role === 'Staf';
        $ssHeadArea = auth()->user()?->isSpeedshopHeadArea() ?? false;
        $ssManager = auth()->user()?->isSpeedshop() && auth()->user()?->isManager() && ! $ssHeadArea;
        $moPortalOnly = auth()->user()?->usesModifikasiOriWelcomePortal() ?? false;
        $dashboardRoute = auth()->user()?->dashboardRoute() ?? 'speedshop.dashboard';
        $ssOperasionalRoutes = [
            'speedshop.work-orders*',
            'speedshop.wip*',
            'speedshop.selesaikan-transaksi*',
            'speedshop.history',
            'speedshop.service-record',
            'speedshop.mekanik-record',
            'speedshop.estimasi',
        ];
        if ($ssManager) {
            $ssOperasionalRoutes[] = 'speedshop.pelanggans*';
            $ssOperasionalRoutes[] = 'speedshop.suppliers*';
        }
        $keuanganSetoranActive = request()->routeIs('speedshop.keuangan.setoran*');
        $keuanganTagihanActive = request()->routeIs('speedshop.keuangan.tagihan*');
        $keuanganHutangActive = request()->routeIs('speedshop.keuangan.hutang*');
        $verifikasiSetoranActive = request()->routeIs('speedshop.verifikasi.setoran*');
        $keuanganPermintaanKasActive = request()->routeIs('speedshop.keuangan.permintaan-kas-kecil*');
        $verifikasiPermintaanKasActive = request()->routeIs('speedshop.verifikasi.permintaan-kas-kecil*');
        $verifikasiPengambilanDividenActive = request()->routeIs('speedshop.verifikasi.pengambilan-dividen*');
        $verifikasiBiayaOperasionalActive = request()->routeIs('speedshop.verifikasi.biaya-operasional*');
        $verifikasiDiskonWoActive = request()->routeIs('speedshop.verifikasi.diskon-work-order.*');
        $verifikasiBatalWoActive = request()->routeIs('speedshop.verifikasi.batal-work-order*');
        $verifikasiPembelianLuarPendingActive = request()->routeIs('speedshop.verifikasi.pembelian-luar.*');
        $verifikasiStokInSupplierActive = request()->routeIs('speedshop.verifikasi.stok-in-supplier*');
        $verifikasiPending = $speedshopVerifikasiPending ?? [
            'diskon_work_order' => 0,
            'pembelian_part_luar' => 0,
            'stok_in_supplier' => 0,
            'batal_wo' => 0,
            'setoran' => 0,
            'kas_kecil' => 0,
            'pengambilan_dividen' => 0,
            'biaya_operasional' => 0,
            'total' => 0,
        ];
        $verifikasiBadgeTitle = 'menunggu verifikasi';
    @endphp
    <nav class="min-h-0 flex-1 space-y-1 overflow-y-auto px-4 pb-4 pt-3">
        @unless($moPortalOnly)
        <a href="{{ route($dashboardRoute) }}"
           class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-medium transition {{ request()->routeIs('owner.dashboard*') || (! $isOwner && request()->routeIs('speedshop.dashboard')) ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
            <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
            Dashboard
        </a>
        @endunless
        @if($ssManager && ! $isOwner)
        <a href="{{ route('speedshop.targets.index') }}"
           class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-medium transition {{ request()->routeIs('speedshop.targets*') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
            <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            Target
        </a>
        @endif

        @include('partials.shared.absensi-menu-link')

        @if($ssMekanik)
            <a href="{{ route('speedshop.history') }}" class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-medium transition {{ request()->routeIs('speedshop.history') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
                <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg>
                Riwayat
            </a>
        @elseif($ssStafOnly)
            {{-- Staf Speedshop: Absensi (+ izin) saja --}}
        @elseif($moPortalOnly)
            @if(auth()->user()?->canUploadMoProductImages())
            <a href="{{ route('speedshop.master.produk-mo') }}"
               class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-medium transition {{ request()->routeIs('speedshop.master.produk-mo*') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
                <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                Produk 77racing
            </a>
            @endif
        @elseif($ssHeadArea)
            @include('partials.speedshop.sidebar-head-area')
        @elseif($ssManager && ! $isOwner)
            @include('partials.speedshop.sidebar-manager')
        @else

        @if(! $isAccounting && ! $isOwner)
        {{-- FLP/Outlet/Staf: Proses --}}
        <div class="relative" x-data="{ open: {{ request()->routeIs($ssOperasionalRoutes) ? 'true' : 'false' }} }">
            <button @click="open = !open" class="flex w-full items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-medium transition {{ request()->routeIs($ssOperasionalRoutes) ? 'bg-white/10 text-white hover:bg-white/15' : 'text-white/75 hover:bg-white/10 hover:text-white' }}">
                <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                Proses
                <svg class="ml-auto h-4 w-4 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="mt-1 space-y-0.5 pl-4">
                @unless(auth()->user()->isManager())
                <a href="{{ route('speedshop.work-orders.create') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('speedshop.work-orders*') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
                    <svg class="h-[16px] w-[16px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    Work Order
                </a>
                <a href="{{ route('speedshop.wip') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('speedshop.wip*') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
                    <svg class="h-[16px] w-[16px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Work In Progress
                </a>
                <a href="{{ route('speedshop.selesaikan-transaksi.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('speedshop.selesaikan-transaksi*') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
                    <svg class="h-[16px] w-[16px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Selesaikan Transaksi
                </a>
                @endunless
                <a href="{{ route('speedshop.history') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('speedshop.history') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
                    <svg class="h-[16px] w-[16px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg>
                    History
                </a>
                <a href="{{ route('speedshop.service-record') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('speedshop.service-record') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
                    <svg class="h-[16px] w-[16px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Data Servis
                </a>
                <a href="{{ route('speedshop.mekanik-record') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('speedshop.mekanik-record') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
                    <svg class="h-[16px] w-[16px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    Data Mekanik
                </a>
                @if($ssManager)
                <a href="{{ route('speedshop.pelanggans.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('speedshop.pelanggans*') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
                    <svg class="h-[16px] w-[16px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    Pelanggan
                </a>
                <a href="{{ route('speedshop.suppliers.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('speedshop.suppliers*') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
                    <svg class="h-[16px] w-[16px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    Supplier
                </a>
                @endif
                @unless(auth()->user()->isManager())
                <a href="{{ route('speedshop.estimasi') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('speedshop.estimasi') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
                    <svg class="h-[16px] w-[16px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    Estimasi Service
                </a>
                @endunless
            </div>
        </div>
        @endif

        @unless(auth()->user()->isManager() || auth()->user()->isAccounting())
        {{-- Transaksi (bukan Manager / Accounting) --}}
        <div class="relative" x-data="{ open: {{ request()->routeIs('speedshop.mutasi*', 'speedshop.stock-in*', 'speedshop.verifikasi.pembelian-luar*', 'speedshop.claim*') ? 'true' : 'false' }} }">
            <button @click="open = !open" class="flex w-full items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-medium transition {{ request()->routeIs('speedshop.mutasi*', 'speedshop.stock-in*', 'speedshop.verifikasi.pembelian-luar*', 'speedshop.claim*') ? 'bg-white/10 text-white hover:bg-white/15' : 'text-white/75 hover:bg-white/10 hover:text-white' }}">
                <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
                Transaksi
                <svg class="ml-auto h-4 w-4 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="mt-1 space-y-0.5 pl-4">
                <a href="{{ route('speedshop.mutasi.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('speedshop.mutasi*') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
                    <svg class="h-[16px] w-[16px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    Mutasi
                </a>
                <a href="{{ route('speedshop.stock-in.pembelian-mo.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('speedshop.stock-in.pembelian-mo*', 'speedshop.stock-in.transaksi-offline*') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
                    <svg class="h-[16px] w-[16px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    Purchase Order
                </a>
                <a href="{{ route('speedshop.claim.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('speedshop.claim*') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
                    <svg class="h-[16px] w-[16px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Claim
                </a>
            </div>
        </div>
        @endunless

        {{-- Inventori Produk: FLP/Outlet/Staf (bukan Manager — Manager punya menu di bawah dekat Master Produk) --}}
        @unless($isAccounting || $isOwner || $ssManager)
        @include('partials.shared.speedshop-stok-menu')
        @endunless

        {{-- Head Audit: lihat inventori (mutasi diblokir middleware finance-readonly) --}}
        @if(auth()->user()?->isModifikasiOriHeadAudit())
        @include('partials.shared.speedshop-stok-menu')
        @endif

        {{-- Keuangan --}}
        <div class="relative" x-data="{ open: {{ request()->routeIs('speedshop.keuangan*') ? 'true' : 'false' }} }">
            <button @click="open = !open" type="button" class="flex w-full items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-medium transition {{ request()->routeIs('speedshop.keuangan*') ? 'bg-white/10 text-white hover:bg-white/15' : 'text-white/75 hover:bg-white/10 hover:text-white' }}">
                <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Keuangan
                <svg class="ml-auto h-4 w-4 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open" x-transition class="mt-1 space-y-0.5 pl-4">
                @unless(auth()->user()->isOutlet())
                <a href="{{ route('speedshop.keuangan.rekening') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('speedshop.keuangan.rekening*') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
                    <svg class="h-[16px] w-[16px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    Rekening
                </a>
                @if($isOwner)
                <a href="{{ route('speedshop.keuangan.setoran-modal.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('speedshop.keuangan.setoran-modal*') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
                    <svg class="h-[16px] w-[16px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2h-1"/></svg>
                    Setoran Modal
                </a>
                <a href="{{ route('speedshop.keuangan.pengambilan-dividen.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('speedshop.keuangan.pengambilan-dividen*') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
                    <svg class="h-[16px] w-[16px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Pengambilan Dividen
                </a>
                @endif
                @endunless
                @unless($isOwner)
                @if(auth()->user()->isManager() || auth()->user()->isAccounting())
                <a href="{{ route('speedshop.keuangan.akun-operasional.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('speedshop.keuangan.akun-operasional*') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
                    <svg class="h-[16px] w-[16px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Nomor Akun
                </a>
                <a href="{{ route('speedshop.keuangan.aset.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('speedshop.keuangan.aset*') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
                    <svg class="h-[16px] w-[16px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    Aset
                </a>
                @endif
                <a href="{{ route('speedshop.keuangan.setoran.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ $keuanganSetoranActive ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
                    <svg class="h-[16px] w-[16px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2h-1"/></svg>
                    Setoran
                </a>
                @if(auth()->user()->isManager() || auth()->user()->isAccounting())
                <a href="{{ route('speedshop.keuangan.tagihan.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ $keuanganTagihanActive ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
                    <svg class="h-[16px] w-[16px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Tagihan
                </a>
                @endif
                <a href="{{ route('speedshop.keuangan.permintaan-kas-kecil.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ $keuanganPermintaanKasActive ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
                    <svg class="h-[16px] w-[16px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Permintaan Kas Kecil
                </a>
                @if(auth()->user()->isManager() || auth()->user()->isAccounting() || auth()->user()->isOutlet() || auth()->user()->isSpeedshopStaf())
                <a href="{{ route('speedshop.keuangan.biaya-operasional') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('speedshop.keuangan.biaya-operasional*') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
                    <svg class="h-[16px] w-[16px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Biaya Operasional
                </a>
                @endif
                @if(auth()->user()->isAccounting())
                <a href="{{ route('speedshop.keuangan.bop-pengembalian.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('speedshop.keuangan.bop-pengembalian*') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
                    <svg class="h-[16px] w-[16px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                    Pengembalian Dana BOP
                </a>
                @endif
                @endunless
            </div>
        </div>

        @if(! $isOwner && (auth()->user()->isAccounting() || auth()->user()->isManager()))
        {{-- Verifikasi: Manager (pembelian luar, diskon WO) + Accounting (setoran, permintaan kas, stok in) + Manager/Accounting (biaya operasional) --}}
        <div class="relative" x-data="{ open: {{ (request()->routeIs('speedshop.verifikasi*') || $verifikasiDiskonWoActive || $verifikasiBatalWoActive || $verifikasiPembelianLuarPendingActive || $verifikasiStokInSupplierActive || $verifikasiPengambilanDividenActive) ? 'true' : 'false' }} }">
            <button @click="open = !open" class="flex w-full items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-medium transition {{ (request()->routeIs('speedshop.verifikasi*') || $verifikasiDiskonWoActive || $verifikasiBatalWoActive || $verifikasiPembelianLuarPendingActive || $verifikasiStokInSupplierActive || $verifikasiPengambilanDividenActive) ? 'bg-white/10 text-white hover:bg-white/15' : 'text-white/75 hover:bg-white/10 hover:text-white' }}">
                <svg class="h-[18px] w-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="min-w-0 flex-1 text-left">Verifikasi</span>
                @include('partials.shared.sidebar-count-badge', ['count' => $verifikasiPending['total'], 'title' => $verifikasiBadgeTitle])
                <svg class="h-4 w-4 shrink-0 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open" x-transition class="mt-1 space-y-0.5 pl-4">
                @if(auth()->user()->isManager())
                <a href="{{ route('speedshop.verifikasi.diskon-work-order.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ $verifikasiDiskonWoActive ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
                    <svg class="h-[16px] w-[16px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14l6-6m-5.5.5h-.01m4.99 5h.01M12 19l-7-3.5v-6L12 5l7 3.5v6L12 19z"/></svg>
                    <span class="min-w-0 flex-1">Verifikasi Diskon WO</span>
                    @include('partials.shared.sidebar-count-badge', [
                        'count' => $verifikasiPending['diskon_work_order'],
                        'tone' => $verifikasiDiskonWoActive ? 'on-active' : 'default',
                        'title' => $verifikasiBadgeTitle,
                    ])
                </a>
                @endif
                @if(auth()->user()->isAccounting())
                <a href="{{ route('speedshop.verifikasi.batal-work-order.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ $verifikasiBatalWoActive ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
                    <svg class="h-[16px] w-[16px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    <span class="min-w-0 flex-1">Verifikasi Batal WO</span>
                    @include('partials.shared.sidebar-count-badge', [
                        'count' => $verifikasiPending['batal_wo'],
                        'tone' => $verifikasiBatalWoActive ? 'on-active' : 'default',
                        'title' => $verifikasiBadgeTitle,
                    ])
                </a>
                <a href="{{ route('speedshop.verifikasi.setoran.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ $verifikasiSetoranActive ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
                    <svg class="h-[16px] w-[16px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2h-1"/></svg>
                    <span class="min-w-0 flex-1">Verifikasi Setoran</span>
                    @include('partials.shared.sidebar-count-badge', [
                        'count' => $verifikasiPending['setoran'],
                        'tone' => $verifikasiSetoranActive ? 'on-active' : 'default',
                        'title' => $verifikasiBadgeTitle,
                    ])
                </a>
                <a href="{{ route('speedshop.verifikasi.permintaan-kas-kecil.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ $verifikasiPermintaanKasActive ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
                    <svg class="h-[16px] w-[16px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span class="min-w-0 flex-1">Verifikasi Kas Kecil</span>
                    @include('partials.shared.sidebar-count-badge', [
                        'count' => $verifikasiPending['kas_kecil'],
                        'tone' => $verifikasiPermintaanKasActive ? 'on-active' : 'default',
                        'title' => $verifikasiBadgeTitle,
                    ])
                </a>
                <a href="{{ route('speedshop.verifikasi.pengambilan-dividen.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ $verifikasiPengambilanDividenActive ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
                    <svg class="h-[16px] w-[16px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span class="min-w-0 flex-1">Verifikasi Pengambilan Dividen</span>
                    @include('partials.shared.sidebar-count-badge', [
                        'count' => $verifikasiPending['pengambilan_dividen'],
                        'tone' => $verifikasiPengambilanDividenActive ? 'on-active' : 'default',
                        'title' => $verifikasiBadgeTitle,
                    ])
                </a>
                @endif
                @if(auth()->user()->isAccounting() || auth()->user()->isManager())
                <a href="{{ route('speedshop.verifikasi.biaya-operasional.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ $verifikasiBiayaOperasionalActive ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
                    <svg class="h-[16px] w-[16px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span class="min-w-0 flex-1">Verifikasi Biaya Operasional</span>
                    @include('partials.shared.sidebar-count-badge', [
                        'count' => $verifikasiPending['biaya_operasional'],
                        'tone' => $verifikasiBiayaOperasionalActive ? 'on-active' : 'default',
                        'title' => $verifikasiBadgeTitle,
                    ])
                </a>
                @endif
            </div>
        </div>
        @endif

        {{-- Inventori Produk + Master Produk (Manager Speedshop) --}}
        @if(! $isOwner && auth()->user()?->canManageProductMasterCatalog() && auth()->user()?->isSpeedshop())
            @include('partials.shared.speedshop-stok-menu')
            @include('partials.shared.product-master-menu', [
                'groupLabel' => 'Master Produk',
                'masterSection' => 'speedshop',
            ])
        @endif

        {{-- Master Data (Accounting cabang) --}}
        @if(! $isOwner && auth()->user()->isAccounting())
        @php
            $ssMekanikMasterRoutes = 'speedshop.mekaniks*, speedshop.mekanik-levels*, ';
            $ssMasterDataOpenRoutes = $ssMekanikMasterRoutes.'speedshop.pelanggans*, speedshop.suppliers*';
        @endphp
        <div class="relative" x-data="{ open: {{ request()->routeIs(explode(', ', rtrim($ssMasterDataOpenRoutes, ', '))) ? 'true' : 'false' }} }">
            <button @click="open = !open" class="flex w-full items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-medium transition {{ request()->routeIs(explode(', ', rtrim($ssMasterDataOpenRoutes, ', '))) ? 'bg-white/10 text-white hover:bg-white/15' : 'text-white/75 hover:bg-white/10 hover:text-white' }}">
                <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                Master Data
                <svg class="ml-auto h-4 w-4 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="mt-1 space-y-0.5 pl-4">
                <a href="{{ route('speedshop.mekaniks.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('speedshop.mekaniks*') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
                    <svg class="h-[16px] w-[16px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Mekanik
                </a>
                <a href="{{ route('speedshop.pelanggans.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('speedshop.pelanggans*') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
                    <svg class="h-[16px] w-[16px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    Pelanggan
                </a>
                <a href="{{ route('speedshop.suppliers.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('speedshop.suppliers*') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
                    <svg class="h-[16px] w-[16px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    Supplier
                </a>
            </div>
        </div>
        @endif

        {{-- Laporan: Manager & Accounting (bukan Staf) --}}
        @if(auth()->user()->isManager() || auth()->user()->isAccounting())
        <div class="relative" x-data="{ open: {{ request()->routeIs('speedshop.laporan*') ? 'true' : 'false' }} }">
            <button @click="open = !open" class="flex w-full items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-medium transition {{ request()->routeIs('speedshop.laporan*') ? 'bg-white/10 text-white hover:bg-white/15' : 'text-white/75 hover:bg-white/10 hover:text-white' }}">
                <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                Laporan
                <svg class="ml-auto h-4 w-4 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="mt-1 space-y-0.5 pl-4">
                <a href="{{ route('speedshop.laporan.laba-rugi') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('speedshop.laporan.laba-rugi*') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
                    <svg class="h-[16px] w-[16px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                    Laba Rugi
                </a>
                <a href="{{ route('speedshop.laporan.biaya') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('speedshop.laporan.biaya*') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
                    <svg class="h-[16px] w-[16px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Pendapatan & Biaya
                </a>
                <a href="{{ route('speedshop.laporan.summary') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('speedshop.laporan.summary*') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
                    <svg class="h-[16px] w-[16px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    Rekap Pendapatan
                </a>
                <a href="{{ route('speedshop.laporan.rekap-pemisahan-dana') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('speedshop.laporan.rekap-pemisahan-dana*') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
                    <svg class="h-[16px] w-[16px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 14h18M7 6v12M17 6v12"/></svg>
                    Rekap Pemisahan Dana
                </a>
                <a href="{{ route('speedshop.laporan.jurnal') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('speedshop.laporan.jurnal*') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
                    <svg class="h-[16px] w-[16px] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Jurnal
                </a>
            </div>
        </div>
        @endif

        @endif {{-- end: not (ssManager && !owner) --}}

        @if(auth()->user()->isManager() && ! $isOwner && ! $ssHeadArea)
            <div>
                <a href="{{ route('speedshop.settings.index') }}"
                   class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-medium transition {{ request()->routeIs('speedshop.settings.*') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
                    <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Pengaturan
                </a>
            </div>

            @if(auth()->user()?->canAccessAdminPanel())
            <div class="space-y-1 border-t border-white/10 pt-4">
                <p class="mb-2 px-1 text-[10px] font-semibold uppercase tracking-[0.16em] text-white/40">Panel HR</p>
                @include('partials.admin.sidebar-nav-items')
            </div>
            @endif
        @endif
    </nav>
</aside>
