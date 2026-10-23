@php
    $user = auth()->user();
    $pendingAbsensi = $user?->pendingAttendanceValidationCount() ?? 0;
    $portalHrNavLink = $portalHrNavLink ?? 'flex w-full items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-medium transition';
@endphp
@if($user && $user->canValidateAttendance())
<a href="{{ route('absensi.validasi.index') }}"
   class="{{ $portalHrNavLink }} {{ request()->routeIs('absensi.validasi*') ? 'bg-brand text-brand-ink shadow-lg shadow-brand/35' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
    <svg class="h-[18px] w-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
    Validasi Absensi
    @if($pendingAbsensi > 0)
    <span class="ml-auto rounded-full bg-amber-400 px-2 py-0.5 text-[10px] font-bold leading-none text-amber-950">{{ $pendingAbsensi }}</span>
    @endif
</a>
@endif
