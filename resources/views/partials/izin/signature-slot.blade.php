@php
    /** @var array{key:string,title:string,subtitle?:string,user:?\App\Models\User,at:?\Illuminate\Support\Carbon,token:?string,can:bool,ready:bool} $slot */
    $slotClass = $slotClass ?? 'rounded-xl border border-gray-200 bg-white p-4 text-center';
    $letterLayout = $letterLayout ?? false;
    $subtitle = $slot['subtitle'] ?? $slot['title'];
@endphp
<div class="{{ $slotClass }}">
    @if($letterLayout)
    <p class="izin-surat-sign-role">{{ $slot['title'] }}</p>
    @else
    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $slot['title'] }}</p>
    @endif
    <p class="{{ $letterLayout ? 'izin-surat-sign-name' : 'mt-2 text-sm font-semibold text-gray-900' }}">{{ $slot['user']?->name ?? '—' }}</p>
    <p class="{{ $letterLayout ? 'izin-surat-sign-meta' : 'text-xs text-gray-500' }}">{{ $slot['user']?->role ?? '' }}</p>

    @if($slot['at'] && $slot['token'])
        <div class="mx-auto mt-3 flex justify-center" id="qr-izin-{{ $slot['key'] }}-{{ $leave->id }}"></div>
        <p class="{{ $letterLayout ? 'izin-surat-sign-meta' : 'mt-2 text-[11px] text-gray-500' }}">{{ $slot['at']->timezone(config('app.timezone'))->format('d M Y H:i') }}</p>
        <a href="{{ $verifyBase.'/'.$slot['token'] }}" target="_blank" class="no-print mt-1 inline-block text-[11px] font-medium text-red-600 hover:underline">Verifikasi QR</a>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (typeof window.renderDinasQr === 'function') {
                    window.renderDinasQr('qr-izin-{{ $slot['key'] }}-{{ $leave->id }}', @json($verifyBase.'/'.$slot['token']));
                }
            });
        </script>
    @elseif($slot['can'] && $slot['ready'] && ! $slot['at'])
        @if($letterLayout)
        <div class="izin-surat-sign-line" aria-hidden="true"></div>
        @endif
        <form method="POST" action="{{ $signRoute }}" class="no-print {{ $letterLayout ? 'mt-3' : 'mt-4' }}">
            @csrf
            <input type="hidden" name="aksi" value="{{ $slot['key'] }}">
            <button type="submit" class="rounded-lg bg-brand px-3 py-2 text-xs font-semibold text-brand-ink hover:bg-brand-hover">
                Tanda Tangani (QR)
            </button>
        </form>
        @if($letterLayout)
        <p class="no-print izin-surat-sign-meta">{{ $subtitle }}</p>
        @endif
    @elseif(! $slot['ready'])
        @if($letterLayout)<div class="izin-surat-sign-line" aria-hidden="true"></div>@endif
        <p class="no-print mt-4 text-xs text-amber-600">Menunggu tanda tangan sebelumnya</p>
    @else
        @if($letterLayout)<div class="izin-surat-sign-line" aria-hidden="true"></div>@endif
        <p class="no-print mt-4 text-xs text-gray-400">Menunggu tanda tangan</p>
    @endif
</div>
