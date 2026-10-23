@php
    $fmt = fn ($v) => number_format((float) $v, 0, ',', '.');
    $items = collect($list['items'] ?? []);
    $metric = $list['metric'] ?? 'product';
    $compareEnabled = ! empty($list['compare_enabled']);
    $comparePeriodLabel = $list['compare_period_label'] ?? 'Periode lalu';
    $qtyUnit = ($list['key'] ?? '') === 'jasa' ? 'x' : 'pcs';
    $listKey = (string) ($list['key'] ?? '');
    $accent = match (true) {
        str_starts_with($listKey, 'part') => 'blue',
        str_starts_with($listKey, 'oli') => 'amber',
        $listKey === 'jasa' => 'purple',
        $listKey === 'tipe_motor' => 'red',
        $listKey === 'sumber_kedatangan' => 'green',
        default => 'gray',
    };
@endphp

<div @class(['top-list-card', 'top-list-card--'.$accent, 'has-compare' => $compareEnabled, 'is-empty' => $items->isEmpty()])>
    <div class="top-list-card-hd">
        <h3>{{ $list['title'] ?? 'Terlaris' }}</h3>
        <p>{{ $list['subtitle'] ?? '' }}</p>
    </div>
    @if($items->isNotEmpty())
    <div class="top-list-card-body">
        @foreach($items as $index => $item)
        @php
            $deltaPct = isset($item['compare_delta_pct']) ? (float) $item['compare_delta_pct'] : null;
            $compareQty = (int) ($item['compare_qty'] ?? 0);
        @endphp
        <div class="top-list-row">
            <div class="top-list-rank">{{ $index + 1 }}</div>
            <div class="top-list-main">
                <div class="top-list-row-top">
                    <div class="top-list-info">
                        <div class="top-list-name">{{ $item['nama'] ?? '-' }}</div>
                        @if(($item['kode'] ?? '-') !== '-')
                        <div class="top-list-code">{{ $item['kode'] }}</div>
                        @endif
                    </div>
                    <div class="top-list-meta">
                        @if($metric === 'order')
                        <div class="top-list-qty">{{ $fmt($item['qty'] ?? 0) }} order</div>
                        @else
                        <div class="top-list-qty">{{ $fmt($item['qty'] ?? 0) }} {{ $qtyUnit }}</div>
                        <div class="top-list-value">Rp {{ $fmt($item['nilai'] ?? 0) }}</div>
                        @endif
                    </div>
                </div>
                @if($compareEnabled)
                <div class="top-list-compare">
                    <span class="top-list-compare-prev">
                        @if($metric === 'order')
                            {{ $comparePeriodLabel }} · {{ $fmt($compareQty) }} order
                        @else
                            {{ $comparePeriodLabel }} · {{ $fmt($compareQty) }} {{ $qtyUnit }}
                        @endif
                    </span>
                    @if($deltaPct !== null)
                    <span @class(['dash-cmp-badge', 'sm', 'up' => $deltaPct >= 0, 'down' => $deltaPct < 0])>{{ $deltaPct >= 0 ? '+' : '' }}{{ number_format($deltaPct, 1, ',', '.') }}%</span>
                    @endif
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @else
    <p class="top-list-empty">{{ $list['empty'] ?? 'Belum ada data pada periode ini.' }}</p>
    @endif
</div>
