@php
    $perf = $dashboardMekanikPerformance ?? ['all' => [], 'by_cabang' => []];
    $allRows = $perf['all'] ?? [];
    $cabangTables = $perf['by_cabang'] ?? [];
    $showAllCabang = ! empty($dashboardAllSpeedshops) && count($cabangTables) > 1;
    $periodHint = $currentPeriodLabel ?? null;
    $fmt = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.');
@endphp
@if($allRows !== [] || $cabangTables !== [])
<section class="mk-perf-section ss-dash-enter" style="--ss-delay: 500ms">
    <div class="section-hd">
        <h2>Performance Mekanik</h2>
        <p>
            Ranking pencapaian jasa + sparepart
            @if(! empty($compareEnabled) && ! empty($comparePeriodLabel))
                · {{ $currentPeriodLabel ?? $periodHint }} vs {{ $comparePeriodLabel }}
            @elseif($periodHint)
                · {{ $periodHint }}
            @endif
        </p>
    </div>

    @if($showAllCabang)
        @include('partials.speedshop.dashboard-manager-mekanik-performance-table', [
            'title' => 'Semua Cabang',
            'rows' => $allRows,
            'showCabang' => true,
            'fmt' => $fmt,
        ])
    @else
        @include('partials.speedshop.dashboard-manager-mekanik-performance-table', [
            'title' => isset($cabangTables[0]['cabang']) ? 'Cabang '.$cabangTables[0]['cabang'] : 'Cabang',
            'rows' => $cabangTables[0]['rows'] ?? $allRows,
            'showCabang' => false,
            'fmt' => $fmt,
        ])
    @endif
</section>
@endif
