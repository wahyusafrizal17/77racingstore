<?php if(! empty($compareEnabled) && ! empty($comparePeriodLabel)): ?>
<div class="dash-compare-banner ss-dash-enter" style="--ss-delay: 60ms">
    <div class="dash-compare-banner-inner">
        <div class="dash-compare-banner-icon" aria-hidden="true">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
        </div>
        <div class="dash-compare-banner-text">
            <p class="dash-compare-banner-title">Mode perbandingan aktif</p>
            <p class="dash-compare-banner-sub">
                <span class="dash-compare-pill current"><?php echo e($currentPeriodLabel ?? 'Periode ini'); ?></span>
                <span class="dash-compare-vs">vs</span>
                <span class="dash-compare-pill previous"><?php echo e($comparePeriodLabel); ?></span>
            </p>
        </div>
    </div>
</div>
<?php endif; ?>
<?php /**PATH /Users/wahyusafrizal/work/Github/77racingstore/resources/views/partials/speedshop/dashboard-compare-banner.blade.php ENDPATH**/ ?>