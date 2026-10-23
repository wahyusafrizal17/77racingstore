@if(! empty($dashboardManagerTopLists))
@php
    $topListCards = collect($dashboardManagerTopLists)->values();
    $periodHint = $currentPeriodLabel ?? null;
    $trioKeys = ['jasa', 'tipe_motor', 'sumber_kedatangan'];
    $catalogCards = $topListCards->reject(fn ($list) => in_array($list['key'] ?? '', $trioKeys, true));
    $trioCards = $topListCards->filter(fn ($list) => in_array($list['key'] ?? '', $trioKeys, true));
@endphp
<section class="top-lists-section ss-dash-enter" style="--ss-delay: 460ms; margin-top: 20px;">
    <div class="section-hd">
        <h2>Insight Penjualan</h2>
        <p>
            Produk, jasa, kendaraan, dan sumber kedatangan terbanyak
            @if(! empty($compareEnabled) && ! empty($comparePeriodLabel))
                · {{ $currentPeriodLabel ?? $periodHint }} vs {{ $comparePeriodLabel }}
            @elseif($periodHint)
                · {{ $periodHint }}
            @endif
        </p>
    </div>
    @if($catalogCards->isNotEmpty())
    <div class="top-lists-grid">
        @foreach($catalogCards as $list)
            @include('partials.speedshop.dashboard-top-list-card', ['list' => $list])
        @endforeach
    </div>
    @endif
    @if($trioCards->isNotEmpty())
    <div class="top-lists-grid top-lists-grid--trio">
        @foreach($trioCards as $list)
            @include('partials.speedshop.dashboard-top-list-card', ['list' => $list])
        @endforeach
    </div>
    @endif
</section>
@endif
