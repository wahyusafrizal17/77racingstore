@unless(auth()->user()?->isOwner())
@php
    $portalHrNavLink = 'flex w-full items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-medium transition';
@endphp
<div class="space-y-1">
    <a href="{{ route('absensi.index') }}"
       class="{{ $portalHrNavLink }} {{ request()->routeIs('absensi.index') || request()->routeIs('absensi.store') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
        <svg class="h-[18px] w-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        Absensi
    </a>

    @include('partials.shared.izin-menu-link')
    @include('partials.shared.izin-validasi-menu-link')
    @include('partials.shared.absensi-validasi-menu-link')
    @include('partials.shared.absensi-rekap-menu-link')
</div>
@endunless
