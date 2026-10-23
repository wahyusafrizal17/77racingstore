<?php
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
?>

<form method="GET" action="<?php echo e($filterAction); ?>" class="filter-strip ss-dash-enter" style="--ss-delay: 0ms" @submit="loading = true">
    <span class="filter-label">Filter:</span>
    <?php $__currentLoopData = $filterHiddenInputs ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $hiddenName => $hiddenValue): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <input type="hidden" name="<?php echo e($hiddenName); ?>" value="<?php echo e($hiddenValue); ?>">
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <?php if(auth()->user()?->isOwner() && ! empty($ownerScopeOptions)): ?>
    <select name="scope" class="filter-select" @change="loading = true; $el.form.submit()">
        <?php $__currentLoopData = $ownerScopeOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $scopeOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <option value="<?php echo e($scopeOption['value']); ?>" <?php if(($selectedOwnerScope ?? 'all') === $scopeOption['value']): echo 'selected'; endif; ?>><?php echo e($scopeOption['label']); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
    <?php elseif(empty($hideSpeedshopFilter) && ! empty($dashboardSpeedshopOptions) && $dashboardSpeedshopOptions->count() > 1): ?>
    <select name="speedshop_id" class="filter-select" @change="loading = true; $el.form.submit()">
        <option value="" <?php if(empty($selectedDashboardSpeedshopId)): echo 'selected'; endif; ?>>Semua Speedshop</option>
        <?php $__currentLoopData = $dashboardSpeedshopOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $speedshopOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <option value="<?php echo e($speedshopOption->id); ?>" <?php if((int) ($selectedDashboardSpeedshopId ?? 0) === (int) $speedshopOption->id): echo 'selected'; endif; ?>><?php echo e($speedshopOption->nama); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
    <?php endif; ?>
    <select name="mode" class="filter-select" @change="loading = true; $el.form.submit()">
        <option value="harian" <?php if($mode === 'harian'): echo 'selected'; endif; ?>>Harian</option>
        <option value="bulanan" <?php if($mode === 'bulanan'): echo 'selected'; endif; ?>>Bulanan</option>
        <option value="tahunan" <?php if($mode === 'tahunan'): echo 'selected'; endif; ?>>Tahunan</option>
        <option value="range" <?php if($mode === 'range'): echo 'selected'; endif; ?>>Range tanggal</option>
    </select>
    <?php if($mode === 'harian'): ?>
    <input type="date" name="tanggal" value="<?php echo e($selectedDate); ?>" class="filter-date">
    <?php elseif($mode === 'bulanan'): ?>
    <input type="month" name="bulan" value="<?php echo e($selectedMonth); ?>" class="filter-date">
    <?php elseif($mode === 'tahunan'): ?>
    <input type="number" name="tahun" min="2000" max="2100" value="<?php echo e($selectedYear); ?>" class="filter-date filter-date-year">
    <?php elseif($mode === 'range'): ?>
    <span class="filter-range-label">Periode A</span>
    <input type="date" name="dari" value="<?php echo e($selectedDari ?? $dari->format('Y-m-d')); ?>" class="filter-date">
    <input type="date" name="sampai" value="<?php echo e($selectedSampai ?? $sampai->format('Y-m-d')); ?>" class="filter-date">
    <?php endif; ?>
    <?php if (! (! empty($hideCompare))): ?>
    <label class="filter-compare">
        <input
            type="checkbox"
            name="compare"
            value="1"
            <?php if(! empty($compareEnabled)): echo 'checked'; endif; ?>
            @change="loading = true; $el.form.submit()"
        >
        <span><?php echo e($mode === 'range' ? 'Bandingkan rentang lain' : 'Bandingkan periode sebelumnya'); ?></span>
    </label>
    <?php endif; ?>
    <?php if($mode === 'range' && ! empty($compareEnabled) && empty($hideCompare)): ?>
    <span class="filter-range-vs">vs</span>
    <span class="filter-range-label">Periode B</span>
    <input type="date" name="compare_dari" value="<?php echo e($selectedCompareDari ?? ''); ?>" class="filter-date">
    <input type="date" name="compare_sampai" value="<?php echo e($selectedCompareSampai ?? ''); ?>" class="filter-date">
    <?php endif; ?>
    <button type="submit" class="btn-apply">Terapkan</button>
    <?php if(!empty($dashboardFilterExports)): ?>
    <div class="filter-export-group">
        <?php if(!empty($dashboardFilterExports['excel'])): ?>
        <a href="<?php echo e(route($dashboardExportRoutePrefix.'.export-excel', request()->only($dashboardFilterQueryKeys))); ?>" class="btn-export">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            Export Invoice
        </a>
        <?php endif; ?>
        <?php if(!empty($dashboardFilterExports['excel_detail'])): ?>
        <a href="<?php echo e(route($dashboardExportRoutePrefix.'.export-excel-detail', request()->only($dashboardFilterQueryKeys))); ?>" class="btn-export">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            Export Rincian
        </a>
        <?php endif; ?>
        <?php if(!empty($dashboardImageExport)): ?>
        <button
            type="button"
            class="btn-export-image"
            :disabled="exportingImage || loading"
            @click="exportDashboardImage()"
        >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
            <span x-text="exportingImage ? 'Memproses…' : 'Cetak Gambar'"></span>
        </button>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</form>
<?php /**PATH /Users/wahyusafrizal/work/Github/77racingstore/resources/views/partials/speedshop/dashboard-filter-strip.blade.php ENDPATH**/ ?>