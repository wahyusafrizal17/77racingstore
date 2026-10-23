@php
    $kotaNama = $supplier->kota?->nama;
    $kotaKey = (string) ($supplier->kota_id ?? $kotaNama ?? '');
    $kotaBadgeIdx = $kotaNama !== null && $kotaNama !== '' ? (abs(crc32('kota|' . $kotaKey)) % 12) : null;
@endphp
@if($kotaNama)
    @switch($kotaBadgeIdx)
        @case(0)
            <span class="inline-flex max-w-[220px] items-center truncate rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-semibold text-indigo-800" title="{{ $kotaNama }}">{{ $kotaNama }}</span>
            @break
        @case(1)
            <span class="inline-flex max-w-[220px] items-center truncate rounded-full bg-purple-100 px-2.5 py-1 text-xs font-semibold text-purple-800" title="{{ $kotaNama }}">{{ $kotaNama }}</span>
            @break
        @case(2)
            <span class="inline-flex max-w-[220px] items-center truncate rounded-full bg-cyan-100 px-2.5 py-1 text-xs font-semibold text-cyan-800" title="{{ $kotaNama }}">{{ $kotaNama }}</span>
            @break
        @case(3)
            <span class="inline-flex max-w-[220px] items-center truncate rounded-full bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-800" title="{{ $kotaNama }}">{{ $kotaNama }}</span>
            @break
        @case(4)
            <span class="inline-flex max-w-[220px] items-center truncate rounded-full bg-teal-100 px-2.5 py-1 text-xs font-semibold text-teal-800" title="{{ $kotaNama }}">{{ $kotaNama }}</span>
            @break
        @case(5)
            <span class="inline-flex max-w-[220px] items-center truncate rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-800" title="{{ $kotaNama }}">{{ $kotaNama }}</span>
            @break
        @case(6)
            <span class="inline-flex max-w-[220px] items-center truncate rounded-full bg-fuchsia-100 px-2.5 py-1 text-xs font-semibold text-fuchsia-800" title="{{ $kotaNama }}">{{ $kotaNama }}</span>
            @break
        @case(7)
            <span class="inline-flex max-w-[220px] items-center truncate rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800" title="{{ $kotaNama }}">{{ $kotaNama }}</span>
            @break
        @case(8)
            <span class="inline-flex max-w-[220px] items-center truncate rounded-full bg-orange-100 px-2.5 py-1 text-xs font-semibold text-orange-800" title="{{ $kotaNama }}">{{ $kotaNama }}</span>
            @break
        @case(9)
            <span class="inline-flex max-w-[220px] items-center truncate rounded-full bg-sky-100 px-2.5 py-1 text-xs font-semibold text-sky-800" title="{{ $kotaNama }}">{{ $kotaNama }}</span>
            @break
        @case(10)
            <span class="inline-flex max-w-[220px] items-center truncate rounded-full bg-violet-100 px-2.5 py-1 text-xs font-semibold text-violet-800" title="{{ $kotaNama }}">{{ $kotaNama }}</span>
            @break
        @default
            <span class="inline-flex max-w-[220px] items-center truncate rounded-full bg-lime-100 px-2.5 py-1 text-xs font-semibold text-lime-800" title="{{ $kotaNama }}">{{ $kotaNama }}</span>
    @endswitch
@else
    <span class="text-gray-500">-</span>
@endif
