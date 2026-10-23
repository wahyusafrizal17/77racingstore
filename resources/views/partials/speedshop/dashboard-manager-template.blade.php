@php
    $dashboardFilterQueryKeys = $dashboardFilterQueryKeys ?? ['mode', 'tanggal', 'bulan', 'tahun', 'dari', 'sampai', 'compare', 'compare_dari', 'compare_sampai'];
    if (auth()->user()?->isOwner()) {
        if (! in_array('scope', $dashboardFilterQueryKeys, true)) {
            $dashboardFilterQueryKeys[] = 'scope';
        }
    }
    if (! in_array('compare', $dashboardFilterQueryKeys, true)) {
        $dashboardFilterQueryKeys[] = 'compare';
    }
    $dashboardExportRoutePrefix = $dashboardExportRoutePrefix ?? (auth()->user()?->isOwner() ? 'owner.dashboard' : 'speedshop.dashboard');
    $dashboardOmsetDetailRoute = $dashboardOmsetDetailRoute ?? ($dashboardExportRoutePrefix.'.omset-detail');
    $filterAction = $filterAction ?? (auth()->user()?->isOwner() ? route('owner.dashboard') : route('speedshop.dashboard'));
    $omsetDetailQueryKeys = ['mode', 'tanggal', 'bulan', 'tahun', 'dari', 'sampai', 'compare', 'compare_dari', 'compare_sampai'];
    if (auth()->user()?->isOwner()) {
        $omsetDetailQueryKeys[] = 'scope';
    }
    $omsetDetailUrl = route($dashboardOmsetDetailRoute, request()->only($omsetDetailQueryKeys));
@endphp

<div
    class="ss-dashboard ss-dash-page"
    x-data="speedshopManagerDashboard(@js($omsetDetailUrl))"
    x-init="$nextTick(() => { requestAnimationFrame(() => $el.classList.add('is-ready')); })"
    :class="loading && 'is-loading'"
>
    <div
        x-show="loading"
        x-cloak
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="ss-dash-loading"
        aria-live="polite"
        aria-busy="true"
    >
        <div class="ss-dash-loading-panel">
            <div class="ss-dash-spinner" aria-hidden="true"></div>
            <p class="ss-dash-loading-text">Memuat dashboard…</p>
            <p class="ss-dash-loading-sub">Menyiapkan data periode terpilih</p>
        </div>
    </div>

    <div
        x-show="exportingImage"
        x-cloak
        class="ss-dash-export-overlay"
        aria-live="polite"
        aria-busy="true"
    >
        <div class="ss-dash-export-panel">
            <div class="ss-dash-spinner" aria-hidden="true"></div>
            <p class="ss-dash-loading-text">Menyiapkan gambar dashboard…</p>
            <p class="ss-dash-loading-sub">Mohon tunggu, layout sedang di-render</p>
        </div>
    </div>

    <div class="ss-dash-content">
    @include('partials.speedshop.dashboard-filter-strip', [
        'filterAction' => $filterAction,
        'dashboardExportRoutePrefix' => $dashboardExportRoutePrefix,
        'dashboardFilterQueryKeys' => $dashboardFilterQueryKeys,
        'dashboardFilterExports' => ['excel' => true, 'excel_detail' => true],
        'dashboardImageExport' => true,
        'hideSpeedshopFilter' => true,
    ])

    <div id="ss-dashboard-capture" class="ss-dashboard-capture">
    @include('partials.speedshop.dashboard-compare-banner')

    @include('partials.speedshop.dashboard-summary-section')

    @include('partials.speedshop.dashboard-manager-charts')

    @include('partials.speedshop.dashboard-manager-top-lists')

    @include('partials.speedshop.dashboard-manager-target-achievement')

    @include('partials.speedshop.dashboard-manager-mekanik-performance')
    </div>
    </div>

    @include('partials.speedshop.dashboard-omset-detail-modal')
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
@endpush
