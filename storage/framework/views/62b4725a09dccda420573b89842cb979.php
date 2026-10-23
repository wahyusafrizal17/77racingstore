<?php
    $fmt = fn ($v) => number_format((float) $v, 0, ',', '.');
    $periodHint = $currentPeriodLabel ?? $dari->translatedFormat('j M Y');
    $scopeLabel = $scopeLabel
        ?? $ownerScopeLabel
        ?? $selectedDashboardSpeedshopName
        ?? (! empty($dashboardAllSpeedshops) ? 'Semua Speedshop' : (\App\Models\Speedshop::find(auth()->user()?->activeSpeedshopId())?->nama ?? 'Speedshop'));
    $branchColors = [
        'bandung' => '#2E6BE6',
        'bogor' => '#12B76A',
        'depok' => '#8CFA01',
        'bekasi' => '#F59E0B',
    ];
    $visibleBranches = collect($dashboardCabangPendapatan ?? [])->where('visible', true)->values();
    $maxBranchAmount = max(1.0, (float) $visibleBranches->max(function ($c) {
        $current = (float) ($c['amount'] ?? 0);
        if (! empty($compareEnabled) && isset($c['compareAmount'])) {
            return max($current, (float) $c['compareAmount']);
        }

        return $current;
    }));
    $branchNames = $visibleBranches->map(fn ($c) => $c['name'] ?? ucfirst($c['key']))->all();
    $branchTotalLabel = count($branchNames) > 0
        ? 'Total Gabungan ('.implode(' + ', $branchNames).')'
        : 'Total Pendapatan';
    $hasBranchCard = ! empty($showDashboardCabangPendapatan) && $visibleBranches->isNotEmpty();
    $hasSpeedshopCard = ! empty($showDashboardSpeedshopSummary) && ! empty($dashboardSpeedshopSummary) && empty($managerDashboardCharts);
    $dualGridSingle = ! $hasBranchCard && ! $hasSpeedshopCard;
    $speedshopSummary = $dashboardSpeedshopSummary ?? null;
    $cmpDelta = function (float $current, float $previous): array {
        $pct = $previous <= 0
            ? ($current > 0 ? 100.0 : 0.0)
            : round((($current - $previous) / $previous) * 100, 1);

        return [
            'pct' => $pct,
            'direction' => $pct >= 0 ? 'up' : 'down',
        ];
    };
    $cmpBadge = function (float $pct): string {
        $dir = $pct >= 0 ? 'up' : 'down';
        $sign = $pct >= 0 ? '+' : '';

        return '<span class="dash-cmp-badge '.$dir.'">'.$sign.number_format($pct, 1, ',', '.').'%</span>';
    };
?>

<?php if (! (! empty($hideOwnerHeroStrip))): ?>
<div class="hero-strip ss-dash-enter" style="--ss-delay: 80ms">
    <div class="hero-main">
        <div class="hero-eyebrow">Total · <?php echo e($currentPeriodLabel ?? $periodHint); ?></div>
        <div class="hero-number"><span>Rp</span><?php echo e($fmt($totalOmset ?? 0)); ?></div>
        <?php if(! empty($compareEnabled) && ! empty($compareMetrics)): ?>
        <?php $heroDelta = $cmpDelta((float) ($totalOmset ?? 0), (float) ($compareMetrics['totalOmset'] ?? 0)); ?>
        <div class="hero-compare">
            <span class="hero-compare-prev">vs <?php echo e($comparePeriodLabel); ?> · Rp <?php echo e($fmt($compareMetrics['totalOmset'] ?? 0)); ?></span>
            <span class="<?php echo \Illuminate\Support\Arr::toCssClasses(['dash-cmp-badge', 'up' => $heroDelta['direction'] === 'up', 'down' => $heroDelta['direction'] === 'down']); ?>"><?php echo e($heroDelta['pct'] >= 0 ? '+' : ''); ?><?php echo e(number_format($heroDelta['pct'], 1, ',', '.')); ?>%</span>
        </div>
        <?php endif; ?>
        <div class="hero-sub">Pendapatan omset + charge + PPN · <?php echo e($scopeLabel); ?></div>
    </div>
    <?php if (! (! empty($hideHeroUnitEntry))): ?>
    <div class="hero-divider"></div>
    <div class="hero-stat" style="min-width:90px">
        <div class="hero-stat-val"><?php echo e($fmt($unitEntry ?? 0)); ?></div>
        <?php if(! empty($compareEnabled) && ! empty($compareMetrics)): ?>
        <?php $d = $cmpDelta((float) ($unitEntry ?? 0), (float) ($compareMetrics['unitEntry'] ?? 0)); ?>
        <div class="hero-stat-cmp"><?php echo $cmpBadge($d['pct']); ?></div>
        <?php endif; ?>
        <div class="hero-stat-lbl">Unit Entry</div>
    </div>
    <?php endif; ?>
    <div class="hero-divider"></div>
    <div class="hero-stat" style="min-width:110px">
        <div class="hero-stat-val green"><?php echo e($fmt($pendapatanOmset ?? 0)); ?></div>
        <?php if(! empty($compareEnabled) && ! empty($compareMetrics)): ?>
        <?php $d = $cmpDelta((float) ($pendapatanOmset ?? 0), (float) ($compareMetrics['pendapatanOmset'] ?? 0)); ?>
        <div class="hero-stat-cmp"><?php echo $cmpBadge($d['pct']); ?></div>
        <?php endif; ?>
        <div class="hero-stat-lbl">Pendapatan Omset</div>
    </div>
    <div class="hero-divider"></div>
    <div class="hero-stat" style="min-width:100px">
        <div class="hero-stat-val amber"><?php echo e($fmt($jumlahItemPart ?? 0)); ?> Item</div>
        <?php if(! empty($compareEnabled) && ! empty($compareMetrics)): ?>
        <?php $d = $cmpDelta((float) ($jumlahItemPart ?? 0), (float) ($compareMetrics['jumlahItemPart'] ?? 0)); ?>
        <div class="hero-stat-cmp"><?php echo $cmpBadge($d['pct']); ?></div>
        <?php endif; ?>
        <div class="hero-stat-lbl">Part Terjual</div>
    </div>
    <div class="hero-divider"></div>
    <div class="hero-stat" style="min-width:90px">
        <div class="hero-stat-val purple"><?php echo e($fmt($jumlahItemJasa ?? 0)); ?> Jasa</div>
        <?php if(! empty($compareEnabled) && ! empty($compareMetrics)): ?>
        <?php $d = $cmpDelta((float) ($jumlahItemJasa ?? 0), (float) ($compareMetrics['jumlahItemJasa'] ?? 0)); ?>
        <div class="hero-stat-cmp"><?php echo $cmpBadge($d['pct']); ?></div>
        <?php endif; ?>
        <div class="hero-stat-lbl">Item Jasa</div>
    </div>
</div>
<?php endif; ?>

<div class="section-hd ss-dash-enter" style="--ss-delay: 160ms">
    <h2>Pendapatan per Metode Bayar</h2>
    <p>
        <?php echo e($currentPeriodLabel ?? $periodHint); ?>

        <?php if(! empty($compareEnabled) && ! empty($comparePeriodLabel)): ?>
            · bandingkan dengan <?php echo e($comparePeriodLabel); ?>

        <?php endif; ?>
    </p>
</div>
<div class="<?php echo \Illuminate\Support\Arr::toCssClasses([
    'payment-grid',
    'payment-grid--quartet' => ($ownerDashboardMode ?? null) === 'speedshop' || ! auth()->user()?->isOwner(),
]); ?>">
    <?php $__currentLoopData = [
        ['label' => 'Tunai', 'key' => 'pendapatanTunai', 'icon' => 'teal', 'svg' => '<path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>'],
        ['label' => 'QRIS', 'key' => 'pendapatanQris', 'icon' => 'blue', 'svg' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3m0 4h4m-4 0v-4m-3 4v-7h3"/>'],
        ['label' => 'Transfer', 'key' => 'pendapatanTransfer', 'icon' => 'purple', 'svg' => '<path d="M7 17l9.2-9.2M17 17V7H7"/>'],
        ['label' => 'EDC', 'key' => 'pendapatanEdc', 'icon' => 'amber', 'svg' => '<rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/>'],
    ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $pay): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <div class="pay-card ss-dash-enter-child" style="--ss-delay: 180ms; --ss-stagger: <?php echo e($i); ?>">
        <div class="pay-card-top">
            <span class="pay-card-label"><?php echo e($pay['label']); ?></span>
            <div class="pay-icon <?php echo e($pay['icon']); ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><?php echo $pay['svg']; ?></svg>
            </div>
        </div>
        <div class="pay-amount"><span class="curr">Rp</span><?php echo e($fmt(${$pay['key']} ?? 0)); ?></div>
        <?php if(! empty($compareEnabled) && ! empty($compareMetrics)): ?>
            <?php echo $__env->make('partials.speedshop.dashboard-compare-line', [
                'current' => ${$pay['key']} ?? 0,
                'compareValue' => $compareMetrics[$pay['key']] ?? 0,
            ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php else: ?>
            <div class="pay-period">Periode terpilih</div>
        <?php endif; ?>
    </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>

<?php echo $__env->make('partials.speedshop.dashboard-kas-tunai-ringkasan', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<?php if (! (! empty($hideRingkasanSections))): ?>
<div class="<?php echo \Illuminate\Support\Arr::toCssClasses(['dual-grid', 'single' => $dualGridSingle, 'ss-dash-enter']); ?>" style="--ss-delay: 280ms">
    <?php if($hasBranchCard): ?>
    <div class="branch-card">
        <div class="section-hd">
            <h2>Ringkasan Speedshop</h2>
            <p>
                Pendapatan part + jasa per cabang
                <?php if(! empty($compareEnabled) && ! empty($comparePeriodLabel)): ?>
                    · vs <?php echo e($comparePeriodLabel); ?>

                <?php endif; ?>
            </p>
        </div>
        <?php if(! empty($compareEnabled)): ?>
        <?php
            $branchCurrentLabel = $currentPeriodLabel ?? $periodHint;
            $branchCompareLabel = $comparePeriodLabel ?? 'Periode lalu';
        ?>
        <div class="branch-bar-legend">
            <div class="branch-bar-legend-item"><span class="branch-bar-legend-dot branch-bar-legend-dot--current"></span><?php echo e($branchCurrentLabel); ?></div>
            <div class="branch-bar-legend-item"><span class="branch-bar-legend-dot branch-bar-legend-dot--prev"></span><?php echo e($branchCompareLabel); ?></div>
        </div>
        <?php endif; ?>
        <div class="branch-list">
            <?php $__currentLoopData = $visibleBranches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $card): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
                $color = $branchColors[$card['key']] ?? '#2E6BE6';
                $pct = round(((float) $card['amount'] / $maxBranchAmount) * 100, 1);
                $compareAmount = (float) ($card['compareAmount'] ?? 0);
                $comparePct = round(($compareAmount / $maxBranchAmount) * 100, 1);
                $branchDelta = isset($card['compareDelta']) ? (float) $card['compareDelta'] : null;
            ?>
            <div class="<?php echo \Illuminate\Support\Arr::toCssClasses(['branch-row', 'has-compare' => ! empty($compareEnabled) && isset($card['compareAmount'])]); ?>">
                <div class="branch-dot" style="background:<?php echo e($color); ?>"></div>
                <div class="branch-name"><?php echo e($card['name'] ?? ucfirst($card['key'])); ?></div>
                <?php if(! empty($compareEnabled) && isset($card['compareAmount'])): ?>
                <div class="branch-bars">
                    <div class="branch-bar-row">
                        <span class="branch-bar-tag" title="<?php echo e($branchCurrentLabel); ?>"><?php echo e($branchCurrentLabel); ?></span>
                        <div class="branch-bar-wrap">
                            <div class="branch-bar branch-bar--current" style="--bar-width: <?php echo e($pct); ?>%; background:<?php echo e($color); ?>"></div>
                        </div>
                    </div>
                    <div class="branch-bar-row">
                        <span class="branch-bar-tag" title="<?php echo e($branchCompareLabel); ?>"><?php echo e($branchCompareLabel); ?></span>
                        <div class="branch-bar-wrap">
                            <?php
                                $hex = ltrim($color, '#');
                                $cr = hexdec(substr($hex, 0, 2));
                                $cg = hexdec(substr($hex, 2, 2));
                                $cb = hexdec(substr($hex, 4, 2));
                            ?>
                            <div class="branch-bar branch-bar--prev" style="--bar-width: <?php echo e($comparePct); ?>%; background: rgba(<?php echo e($cr); ?>, <?php echo e($cg); ?>, <?php echo e($cb); ?>, 0.28); border: 1px dashed rgba(<?php echo e($cr); ?>, <?php echo e($cg); ?>, <?php echo e($cb); ?>, 0.5);"></div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="branch-bar-wrap">
                    <div class="branch-bar" style="--bar-width: <?php echo e($pct); ?>%; background:<?php echo e($color); ?>"></div>
                </div>
                <?php endif; ?>
                <div class="branch-amount-col">
                    <div class="branch-amount" style="color:<?php echo e($color); ?>"><?php echo e($fmt($card['amount'])); ?></div>
                    <?php if(! empty($compareEnabled) && isset($card['compareAmount'])): ?>
                    <div class="branch-compare-line">
                        <span class="branch-compare-prev"><?php echo e($fmt($card['compareAmount'])); ?></span>
                        <?php if($branchDelta !== null): ?>
                        <span class="<?php echo \Illuminate\Support\Arr::toCssClasses(['dash-cmp-badge', 'sm', 'up' => $branchDelta >= 0, 'down' => $branchDelta < 0]); ?>"><?php echo e($branchDelta >= 0 ? '+' : ''); ?><?php echo e(number_format($branchDelta, 1, ',', '.')); ?>%</span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <div class="branch-total-row">
            <span class="branch-total-lbl"><?php echo e($branchTotalLabel); ?></span>
            <div class="branch-total-right">
                <span class="branch-total-val">Rp <?php echo e($fmt($totalPendapatanCabang ?? 0)); ?></span>
                <?php if(! empty($compareEnabled) && isset($compareTotalPendapatanCabang)): ?>
                <?php $totalBranchDelta = $cmpDelta((float) ($totalPendapatanCabang ?? 0), (float) $compareTotalPendapatanCabang); ?>
                <div class="branch-total-compare">
                    <span class="branch-compare-prev">vs <?php echo e($comparePeriodLabel); ?> · Rp <?php echo e($fmt($compareTotalPendapatanCabang)); ?></span>
                    <span class="<?php echo \Illuminate\Support\Arr::toCssClasses(['dash-cmp-badge', 'sm', 'up' => $totalBranchDelta['direction'] === 'up', 'down' => $totalBranchDelta['direction'] === 'down']); ?>"><?php echo e($totalBranchDelta['pct'] >= 0 ? '+' : ''); ?><?php echo e(number_format($totalBranchDelta['pct'], 1, ',', '.')); ?>%</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php if(empty($managerDashboardCharts)): ?>
        <?php echo $__env->make('partials.speedshop.dashboard-top-products', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php endif; ?>
    </div>
    <?php elseif($hasSpeedshopCard): ?>
    <div class="branch-card speedshop-summary-card">
        <?php echo $__env->make('partials.speedshop.dashboard-top-products', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>
    <?php endif; ?>

    <div class="omset-card">
        <div class="section-hd">
            <h2>Ringkasan Omset</h2>
            <p>
                Part &amp; jasa terpisah dari charge dan PPN
                <?php if(! empty($compareEnabled) && ! empty($comparePeriodLabel)): ?>
                    · vs <?php echo e($comparePeriodLabel); ?>

                <?php endif; ?>
            </p>
        </div>
        <div class="omset-grid">
            <?php $__currentLoopData = [
                ['type' => 'part', 'class' => 'blue', 'label' => 'Penjualan Part', 'value' => $penjualanPart ?? 0, 'cmpKey' => 'penjualanPart', 'sub' => ($fmt($jumlahItemPart ?? 0)).' Item Terjual'],
                ['type' => 'jasa', 'class' => 'purple', 'label' => 'Pendapatan Jasa', 'value' => $pendapatanJasa ?? 0, 'cmpKey' => 'pendapatanJasa', 'sub' => ($fmt($jumlahItemJasa ?? 0)).' Item Jasa'],
                ['type' => 'omset', 'class' => 'green', 'label' => 'Pendapatan Omset', 'value' => $pendapatanOmset ?? 0, 'cmpKey' => 'pendapatanOmset', 'sub' => 'Part + jasa (tanpa charge & PPN)'],
                ['type' => 'charge', 'class' => 'amber', 'label' => 'Charge Invoice', 'value' => $totalCharge ?? 0, 'cmpKey' => 'totalCharge', 'sub' => 'Biaya charge invoice'],
            ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cell): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="omset-cell <?php echo e($cell['class']); ?>">
                <div class="omset-cell-top">
                    <div class="omset-lbl"><?php echo e($cell['label']); ?></div>
                    <button type="button" class="omset-view-btn" title="Lihat rincian" @click="openOmsetDetail('<?php echo e($cell['type']); ?>')">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                </div>
                <div class="omset-val"><span class="curr">Rp </span><?php echo e($fmt($cell['value'])); ?></div>
                <div class="omset-sub"><?php echo e($cell['sub']); ?></div>
                <?php if(! empty($compareEnabled) && ! empty($compareMetrics)): ?>
                    <?php echo $__env->make('partials.speedshop.dashboard-compare-line', [
                        'current' => $cell['value'],
                        'compareValue' => $compareMetrics[$cell['cmpKey']] ?? 0,
                    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php endif; ?>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
</div>
<?php endif; ?>
<?php /**PATH /Users/wahyusafrizal/work/Github/77racingstore/resources/views/partials/speedshop/dashboard-summary-section.blade.php ENDPATH**/ ?>