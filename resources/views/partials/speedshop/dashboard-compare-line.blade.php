@php
    /** @var float|int $current */
    /** @var float|int|null $compareValue */
    $compareValue = $compareValue ?? null;
    $showAmount = $showAmount ?? true;
    $suffix = $suffix ?? '';
@endphp
@if(! empty($compareEnabled) && $compareValue !== null && isset($cmpDelta))
@php $delta = $cmpDelta((float) $current, (float) $compareValue); @endphp
<div class="dash-cmp-line">
    <span class="dash-cmp-prev">
        @if($showAmount)
            {{ $comparePeriodLabel }} · Rp {{ $fmt($compareValue) }}{{ $suffix }}
        @else
            {{ $comparePeriodLabel }} · {{ $fmt($compareValue) }}{{ $suffix }}
        @endif
    </span>
    <span @class(['dash-cmp-badge', 'up' => $delta['direction'] === 'up', 'down' => $delta['direction'] === 'down'])>
        {{ $delta['pct'] >= 0 ? '+' : '' }}{{ number_format($delta['pct'], 1, ',', '.') }}%
    </span>
</div>
@endif
