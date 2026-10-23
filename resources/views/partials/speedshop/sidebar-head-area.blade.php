@php
    use App\Support\ProductMasterCatalog;

    $persetujuanPending = $speedshopPersetujuanPending ?? [
        'diskon_work_order' => 0,
        'pembelian_part_luar' => 0,
        'biaya_operasional' => 0,
        'total' => 0,
    ];
    $verifikasiDiskonWoActive = request()->routeIs('speedshop.verifikasi.diskon-work-order.*');
    $verifikasiPembelianLuarActive = request()->routeIs('speedshop.verifikasi.pembelian-luar.*');
    $ssPersetujuanOpen = $verifikasiDiskonWoActive || $verifikasiPembelianLuarActive;
    $ssPersetujuanTotal = (int) ($persetujuanPending['diskon_work_order'] ?? 0)
        + (int) ($persetujuanPending['pembelian_part_luar'] ?? 0);
    $ssMasterDataRoutes = [
        'speedshop.pelanggans*',
        'speedshop.suppliers*',
        'speedshop.categories*',
        'speedshop.brands*',
        'speedshop.jasa-servis*',
        'speedshop.mekaniks*',
        'speedshop.mekanik-levels*',
    ];
    $ssMasterDataOpen = request()->routeIs(...$ssMasterDataRoutes);
    $ssOperasionalOpen = request()->routeIs(
        'speedshop.history',
        'speedshop.service-record*',
        'speedshop.mekanik-record',
        'speedshop.wip.show',
    );
    $stokPatterns = ProductMasterCatalog::stokRoutePatterns();
    $ssInventoriOpen = request()->routeIs(...$stokPatterns);
    $ssLaporanOpen = request()->routeIs(
        'speedshop.laporan.laba-rugi*',
        'speedshop.laporan.biaya*',
        'speedshop.laporan.summary*',
        'speedshop.laporan.rekap-pemisahan-dana*',
    );
@endphp

{{-- Operasional --}}
<div class="relative" x-data="{ open: {{ $ssOperasionalOpen ? 'true' : 'false' }} }">
    <button @click="open = !open" type="button" class="flex w-full items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-medium transition {{ $ssOperasionalOpen ? 'bg-white/10 text-white hover:bg-white/15' : 'text-white/75 hover:bg-white/10 hover:text-white' }}">
        <svg class="h-[18px] w-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
        Operasional
        <svg class="ml-auto h-4 w-4 shrink-0 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="open" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="mt-1 space-y-0.5 pl-4">
        <a href="{{ route('speedshop.history') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('speedshop.history') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
            <svg class="h-[16px] w-[16px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg>
            History
        </a>
        <a href="{{ route('speedshop.service-record') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('speedshop.service-record*') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
            <svg class="h-[16px] w-[16px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Service Record
        </a>
        <a href="{{ route('speedshop.mekanik-record') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('speedshop.mekanik-record', 'speedshop.wip.show') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
            <svg class="h-[16px] w-[16px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            Mekanik Record
        </a>
    </div>
</div>

{{-- Persetujuan: Diskon WO + Pembelian Part Luar, hanya cabang yang dipegang --}}
<div class="relative" x-data="{ open: {{ $ssPersetujuanOpen ? 'true' : 'false' }} }">
    <button @click="open = !open" type="button" class="flex w-full items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-medium transition {{ $ssPersetujuanOpen ? 'bg-white/10 text-white hover:bg-white/15' : 'text-white/75 hover:bg-white/10 hover:text-white' }}">
        <svg class="h-[18px] w-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span class="min-w-0 flex-1 text-left">Persetujuan</span>
        @include('partials.shared.sidebar-count-badge', ['count' => $ssPersetujuanTotal])
        <svg class="h-4 w-4 shrink-0 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="open" x-transition class="mt-1 space-y-0.5 pl-4">
        <a href="{{ route('speedshop.verifikasi.diskon-work-order.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ $verifikasiDiskonWoActive ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
            <svg class="h-[16px] w-[16px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14l6-6m-5.5.5h-.01m4.99 5h.01M12 19l-7-3.5v-6L12 5l7 3.5v6L12 19z"/></svg>
            <span class="min-w-0 flex-1">Diskon Work Order</span>
            @include('partials.shared.sidebar-count-badge', [
                'count' => $persetujuanPending['diskon_work_order'],
                'tone' => $verifikasiDiskonWoActive ? 'on-active' : 'default',
            ])
        </a>
    </div>
</div>

{{-- Inventory --}}
@include('partials.shared.speedshop-stok-menu', [
    'groupLabel' => 'Inventory',
])

{{-- Master Data --}}
<div class="relative" x-data="{ open: {{ $ssMasterDataOpen ? 'true' : 'false' }} }">
    <button @click="open = !open" type="button" class="flex w-full items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-medium transition {{ $ssMasterDataOpen ? 'bg-white/10 text-white hover:bg-white/15' : 'text-white/75 hover:bg-white/10 hover:text-white' }}">
        <svg class="h-[18px] w-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
        Master Data
        <svg class="ml-auto h-4 w-4 shrink-0 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="open" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="mt-1 space-y-0.5 pl-4">
        <a href="{{ route('speedshop.pelanggans.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('speedshop.pelanggans*') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
            <svg class="h-[16px] w-[16px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            Pelanggan
        </a>
        <a href="{{ route('speedshop.suppliers.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('speedshop.suppliers*') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
            <svg class="h-[16px] w-[16px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            Supplier
        </a>
        <a href="{{ route('speedshop.categories.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('speedshop.categories*') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
            <svg class="h-[16px] w-[16px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h10M7 12h6m-6 5h10M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z"/></svg>
            Kategori
        </a>
        <a href="{{ route('speedshop.brands.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('speedshop.brands*') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
            <svg class="h-[16px] w-[16px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.59 13.41L12 22l-8.59-8.59a2 2 0 010-2.82L9.17 4.83a2 2 0 012.82 0l8.6 8.58a2 2 0 010 2.82z"/></svg>
            Brand
        </a>
        <a href="{{ route('speedshop.jasa-servis.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('speedshop.jasa-servis*') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
            <svg class="h-[16px] w-[16px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Jasa Service
        </a>
        <a href="{{ route('speedshop.mekaniks.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('speedshop.mekaniks*', 'speedshop.mekanik-levels*') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
            <svg class="h-[16px] w-[16px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            Mekanik
        </a>
    </div>
</div>

{{-- Laporan --}}
<div class="relative" x-data="{ open: {{ $ssLaporanOpen ? 'true' : 'false' }} }">
    <button @click="open = !open" type="button" class="flex w-full items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-medium transition {{ $ssLaporanOpen ? 'bg-white/10 text-white hover:bg-white/15' : 'text-white/75 hover:bg-white/10 hover:text-white' }}">
        <svg class="h-[18px] w-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
        Laporan
        <svg class="ml-auto h-4 w-4 shrink-0 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="open" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="mt-1 space-y-0.5 pl-4">
        <a href="{{ route('speedshop.laporan.laba-rugi') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('speedshop.laporan.laba-rugi*') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
            <svg class="h-[16px] w-[16px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            Laba Rugi
        </a>
        <a href="{{ route('speedshop.laporan.biaya') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('speedshop.laporan.biaya*') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
            <svg class="h-[16px] w-[16px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Pendapatan & Biaya
        </a>
        <a href="{{ route('speedshop.laporan.summary') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('speedshop.laporan.summary*') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
            <svg class="h-[16px] w-[16px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            Rekap Pendapatan
        </a>
        <a href="{{ route('speedshop.laporan.rekap-pemisahan-dana') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('speedshop.laporan.rekap-pemisahan-dana*') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
            <svg class="h-[16px] w-[16px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 14h18M7 6v12M17 6v12"/></svg>
            Rekap Pemisahan Dana
        </a>
    </div>
</div>
