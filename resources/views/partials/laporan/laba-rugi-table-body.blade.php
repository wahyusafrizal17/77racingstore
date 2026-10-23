@php
    use App\Support\SpeedshopLabaRugiFinancials;
@endphp
@foreach($rows as $row)
    @php
        $kind = $row['kind'] ?? 'line';
        $isFooter = $kind === 'footer';
        $showRp = in_array($kind, ['line', 'subtotal', 'grand', 'footer'], true) && ! ($row['is_percent'] ?? false);
        $amount = $row['amount'] ?? null;
        $indentClass = SpeedshopLabaRugiFinancials::labaRugiRowPaddingClass($row);
        $amountTone = match ($kind) {
            'subtotal', 'grand' => 'text-red-600',
            'footer' => 'text-white',
            default => 'text-gray-900',
        };
        $sectionNo = (string) ($row['no'] ?? '');
        $showSectionSort = ($labaRugiSectionSortEnabled ?? false)
            && $kind === 'sub'
            && isset(($labaRugiSortableSections ?? [])[$sectionNo])
            && isset($labaRugiSortUrl);
        $sectionSort = ($labaRugiSectionSort ?? [])[$sectionNo] ?? null;
    @endphp
    <tr class="{{ $rowClass($kind) }}">
        <td class="px-5 py-2.5 font-mono text-[13px] tabular-nums {{ $indentClass }} {{ $isFooter ? 'text-white/95' : 'text-gray-600' }}">
            {{ ($row['no'] ?? '') !== '' ? $row['no'] : '' }}
        </td>
        <td class="px-5 py-2.5 {{ $indentClass }} {{ $isFooter ? 'text-white' : 'text-gray-800' }}">
            {{ $row['label'] }}
        </td>
        <td class="px-5 py-2.5 text-right font-medium tabular-nums {{ $isFooter ? 'text-white' : '' }}">
            @if($showSectionSort)
                <div class="inline-flex items-center justify-end gap-0.5">
                    <a
                        href="{{ $labaRugiSortUrl($sectionNo, 'asc') }}"
                        class="lr-sort-btn {{ $sectionSort === 'asc' ? 'is-active' : '' }}"
                        title="Urutkan terendah ke tertinggi"
                        aria-label="Urutkan {{ $row['label'] }} terendah ke tertinggi"
                        @click="loading = true"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/>
                        </svg>
                    </a>
                    <a
                        href="{{ $labaRugiSortUrl($sectionNo, 'desc') }}"
                        class="lr-sort-btn {{ $sectionSort === 'desc' ? 'is-active' : '' }}"
                        title="Urutkan tertinggi ke terendah"
                        aria-label="Urutkan {{ $row['label'] }} tertinggi ke terendah"
                        @click="loading = true"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </a>
                </div>
            @elseif($amount !== null)
                @if($row['is_percent'] ?? false)
                    <span class="{{ $isFooter ? 'text-white' : 'text-gray-900' }}">{{ $fmtPct($amount) }}</span>
                @elseif($showRp)
                    <span class="inline-flex w-full items-baseline justify-end gap-2">
                        <span class="{{ $isFooter ? 'text-white/90' : ($amountTone === 'text-red-600' ? 'text-red-600' : 'text-rose-600/80') }} text-xs font-medium">Rp</span>
                        <span class="{{ $amountTone }}">{{ $fmt($amount) }}</span>
                    </span>
                @endif
            @endif
        </td>
    </tr>
@endforeach
