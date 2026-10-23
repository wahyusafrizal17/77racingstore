@php
    $badgeCount = (int) ($count ?? 0);
    $badgeLabel = \App\Support\SidebarBadge::label($badgeCount);
    $title = $title ?? 'menunggu persetujuan';
    $badgeTone = $tone ?? 'default';
@endphp
@if($badgeLabel)
<span
    @class([
        'inline-flex h-5 min-w-[1.25rem] shrink-0 items-center justify-center rounded-full px-1.5 text-[10px] font-bold leading-none tabular-nums',
        'bg-brand text-brand-ink ring-1 ring-red-400/40' => $badgeTone === 'default',
        'bg-white text-red-600' => $badgeTone === 'on-active',
    ])
    title="{{ $badgeCount }} {{ $title }}"
>{{ $badgeLabel }}</span>
@endif
