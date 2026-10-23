@php
    $dashboardFilterQueryKeys = $dashboardFilterQueryKeys ?? ['mode', 'tanggal', 'bulan', 'tahun', 'dari', 'sampai', 'compare', 'compare_dari', 'compare_sampai'];
    if (auth()->user()?->isOwner() && ! in_array('scope', $dashboardFilterQueryKeys, true)) {
        $dashboardFilterQueryKeys[] = 'scope';
    }
    if (! auth()->user()?->isOwner()
        && empty($hideSpeedshopFilter)
        && ! empty($dashboardSpeedshopOptions)
        && $dashboardSpeedshopOptions->count() > 1
        && ! in_array('speedshop_id', $dashboardFilterQueryKeys, true)) {
        $dashboardFilterQueryKeys[] = 'speedshop_id';
    }
    if (! in_array('compare', $dashboardFilterQueryKeys, true)) {
        $dashboardFilterQueryKeys[] = 'compare';
    }
    foreach (['compare_dari', 'compare_sampai'] as $compareRangeKey) {
        if (! in_array($compareRangeKey, $dashboardFilterQueryKeys, true)) {
            $dashboardFilterQueryKeys[] = $compareRangeKey;
        }
    }
    $filterAction = $filterAction ?? (auth()->user()?->isOwner() ? route('owner.dashboard') : route('speedshop.dashboard'));
    $dashboardExportRoutePrefix = $dashboardExportRoutePrefix ?? (auth()->user()?->isOwner() ? 'owner.dashboard' : 'speedshop.dashboard');
@endphp

<form method="GET" action="{{ $filterAction }}" class="filter-strip ss-dash-enter" style="--ss-delay: 0ms" @submit="loading = true">
    <span class="filter-label">Filter:</span>
    @foreach($filterHiddenInputs ?? [] as $hiddenName => $hiddenValue)
    <input type="hidden" name="{{ $hiddenName }}" value="{{ $hiddenValue }}">
    @endforeach
    @if(auth()->user()?->isOwner() && ! empty($ownerScopeOptions))
    <select name="scope" class="filter-select" @change="loading = true; $el.form.submit()">
        @foreach($ownerScopeOptions as $scopeOption)
        <option value="{{ $scopeOption['value'] }}" @selected(($selectedOwnerScope ?? 'all') === $scopeOption['value'])>{{ $scopeOption['label'] }}</option>
        @endforeach
    </select>
    @elseif(empty($hideSpeedshopFilter) && ! empty($dashboardSpeedshopOptions) && $dashboardSpeedshopOptions->count() > 1)
    <select name="speedshop_id" class="filter-select" @change="loading = true; $el.form.submit()">
        <option value="" @selected(empty($selectedDashboardSpeedshopId))>Semua Speedshop</option>
        @foreach($dashboardSpeedshopOptions as $speedshopOption)
        <option value="{{ $speedshopOption->id }}" @selected((int) ($selectedDashboardSpeedshopId ?? 0) === (int) $speedshopOption->id)>{{ $speedshopOption->nama }}</option>
        @endforeach
    </select>
    @endif
    <select name="mode" class="filter-select" @change="loading = true; $el.form.submit()">
        <option value="harian" @selected($mode === 'harian')>Harian</option>
        <option value="bulanan" @selected($mode === 'bulanan')>Bulanan</option>
        <option value="tahunan" @selected($mode === 'tahunan')>Tahunan</option>
        <option value="range" @selected($mode === 'range')>Range tanggal</option>
    </select>
    @if($mode === 'harian')
    <input type="date" name="tanggal" value="{{ $selectedDate }}" class="filter-date">
    @elseif($mode === 'bulanan')
    <input type="month" name="bulan" value="{{ $selectedMonth }}" class="filter-date">
    @elseif($mode === 'tahunan')
    <input type="number" name="tahun" min="2000" max="2100" value="{{ $selectedYear }}" class="filter-date filter-date-year">
    @elseif($mode === 'range')
    <span class="filter-range-label">Periode A</span>
    <input type="date" name="dari" value="{{ $selectedDari ?? $dari->format('Y-m-d') }}" class="filter-date">
    <input type="date" name="sampai" value="{{ $selectedSampai ?? $sampai->format('Y-m-d') }}" class="filter-date">
    @endif
    @unless(! empty($hideCompare))
    <label class="filter-compare">
        <input
            type="checkbox"
            name="compare"
            value="1"
            @checked(! empty($compareEnabled))
            @change="loading = true; $el.form.submit()"
        >
        <span>{{ $mode === 'range' ? 'Bandingkan rentang lain' : 'Bandingkan periode sebelumnya' }}</span>
    </label>
    @endunless
    @if($mode === 'range' && ! empty($compareEnabled) && empty($hideCompare))
    <span class="filter-range-vs">vs</span>
    <span class="filter-range-label">Periode B</span>
    <input type="date" name="compare_dari" value="{{ $selectedCompareDari ?? '' }}" class="filter-date">
    <input type="date" name="compare_sampai" value="{{ $selectedCompareSampai ?? '' }}" class="filter-date">
    @endif
    <button type="submit" class="btn-apply">Terapkan</button>
    @if(!empty($dashboardFilterExports))
    <div class="filter-export-group">
        @if(!empty($dashboardFilterExports['excel']))
        <a href="{{ route($dashboardExportRoutePrefix.'.export-excel', request()->only($dashboardFilterQueryKeys)) }}" class="btn-export">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            Export Invoice
        </a>
        @endif
        @if(!empty($dashboardFilterExports['excel_detail']))
        <a href="{{ route($dashboardExportRoutePrefix.'.export-excel-detail', request()->only($dashboardFilterQueryKeys)) }}" class="btn-export">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            Export Rincian
        </a>
        @endif
        @if(!empty($dashboardImageExport))
        <button
            type="button"
            class="btn-export-image"
            :disabled="exportingImage || loading"
            @click="exportDashboardImage()"
        >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
            <span x-text="exportingImage ? 'Memproses…' : 'Cetak Gambar'"></span>
        </button>
        @endif
    </div>
    @endif
</form>
