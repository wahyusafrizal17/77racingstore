<div class="charts-row ss-dash-enter" style="--ss-delay: 380ms">
    <?php
        $fmtChart = fn ($v) => number_format((float) $v, 0, ',', '.');
        $chartPartTotal = array_sum($chartPenjualanPart ?? []);
        $chartJasaTotal = array_sum($chartPenjualanJasa ?? []);
        $chartComparePartTotal = array_sum($chartComparePenjualanPart ?? []);
        $chartCompareJasaTotal = array_sum($chartComparePenjualanJasa ?? []);
        $chartDelta = function (float $current, float $previous): array {
            $pct = $previous <= 0 ? ($current > 0 ? 100.0 : 0.0) : round((($current - $previous) / $previous) * 100, 1);
            return ['pct' => $pct, 'direction' => $pct >= 0 ? 'up' : 'down'];
        };
        $partChartDelta = $chartDelta((float) $chartPartTotal, (float) $chartComparePartTotal);
        $jasaChartDelta = $chartDelta((float) $chartJasaTotal, (float) $chartCompareJasaTotal);
        $chartOmsetTotal = array_sum($chartPendapatanOmset ?? []);
        $chartTagihanTotal = array_sum($chartTotalTagihan ?? []);
        $chartCompareOmsetTotal = array_sum($chartComparePendapatanOmset ?? []);
        $chartCompareTagihanTotal = array_sum($chartCompareTotalTagihan ?? []);
        $omsetChartDelta = $chartDelta((float) $chartOmsetTotal, (float) $chartCompareOmsetTotal);
        $tagihanChartDelta = $chartDelta((float) $chartTagihanTotal, (float) $chartCompareTagihanTotal);
        $chartPeriodSubtitle = $currentPeriodLabel ?? $dari->translatedFormat('F Y');
    ?>
    <div class="<?php echo \Illuminate\Support\Arr::toCssClasses(['chart-card', 'chart-card--penjualan', 'has-compare' => ! empty($compareEnabled)]); ?>">
        <div class="chart-hd">
            <div>
                <div class="chart-title">Grafik Penjualan</div>
                <div class="chart-sub">
                    <?php if(! empty($compareEnabled) && ! empty($comparePeriodLabel)): ?>
                        <?php echo e($chartPeriodSubtitle); ?> vs <?php echo e($comparePeriodLabel); ?>

                    <?php else: ?>
                        Part &amp; jasa · <?php echo e($chartPeriodSubtitle); ?>

                    <?php endif; ?>
                </div>
            </div>
            <?php if(! empty($compareEnabled)): ?>
            <div class="chart-legend chart-legend--compact">
                <div class="legend-item"><div class="legend-dot legend-dot--solid" style="background:var(--ss-blue)"></div>Periode ini</div>
                <div class="legend-item"><div class="legend-dot legend-dot--dash" style="background:var(--ss-muted)"></div>Periode lalu</div>
            </div>
            <?php endif; ?>
        </div>

        <div class="chart-penjualan-kpi">
            <div class="chart-kpi-cell chart-kpi-cell--part">
                <div class="chart-kpi-label">Total Part</div>
                <div class="chart-kpi-value">Rp <?php echo e($fmtChart($chartPartTotal)); ?></div>
                <?php if(! empty($compareEnabled)): ?>
                <div class="chart-kpi-compare">
                    <span class="chart-kpi-prev">vs Rp <?php echo e($fmtChart($chartComparePartTotal)); ?></span>
                    <span class="<?php echo \Illuminate\Support\Arr::toCssClasses(['dash-cmp-badge', 'sm', 'up' => $partChartDelta['direction'] === 'up', 'down' => $partChartDelta['direction'] === 'down']); ?>"><?php echo e($partChartDelta['pct'] >= 0 ? '+' : ''); ?><?php echo e(number_format($partChartDelta['pct'], 1, ',', '.')); ?>%</span>
                </div>
                <?php endif; ?>
            </div>
            <div class="chart-kpi-cell chart-kpi-cell--jasa">
                <div class="chart-kpi-label">Total Jasa</div>
                <div class="chart-kpi-value">Rp <?php echo e($fmtChart($chartJasaTotal)); ?></div>
                <?php if(! empty($compareEnabled)): ?>
                <div class="chart-kpi-compare">
                    <span class="chart-kpi-prev">vs Rp <?php echo e($fmtChart($chartCompareJasaTotal)); ?></span>
                    <span class="<?php echo \Illuminate\Support\Arr::toCssClasses(['dash-cmp-badge', 'sm', 'up' => $jasaChartDelta['direction'] === 'up', 'down' => $jasaChartDelta['direction'] === 'down']); ?>"><?php echo e($jasaChartDelta['pct'] >= 0 ? '+' : ''); ?><?php echo e(number_format($jasaChartDelta['pct'], 1, ',', '.')); ?>%</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="chart-split">
            <div class="chart-split-item">
                <div class="chart-split-hd">
                    <span class="chart-split-dot" style="background:var(--ss-blue)"></span>
                    <span class="chart-split-title">Penjualan Part</span>
                </div>
                <div class="chart-canvas chart-canvas--split"><canvas id="chartPenjualanPart"></canvas></div>
            </div>
            <div class="chart-split-item">
                <div class="chart-split-hd">
                    <span class="chart-split-dot" style="background:var(--ss-red)"></span>
                    <span class="chart-split-title">Penjualan Jasa</span>
                </div>
                <div class="chart-canvas chart-canvas--split"><canvas id="chartPenjualanJasa"></canvas></div>
            </div>
        </div>
    </div>

    <div class="<?php echo \Illuminate\Support\Arr::toCssClasses(['chart-card', 'chart-card--pendapatan', 'has-compare' => ! empty($compareEnabled)]); ?>">
        <div class="chart-hd">
            <div>
                <div class="chart-title">Grafik Pendapatan</div>
                <div class="chart-sub">
                    <?php if(! empty($compareEnabled) && ! empty($comparePeriodLabel)): ?>
                        <?php echo e($chartPeriodSubtitle); ?> vs <?php echo e($comparePeriodLabel); ?>

                    <?php else: ?>
                        Omset &amp; tagihan · <?php echo e($chartPeriodSubtitle); ?>

                    <?php endif; ?>
                </div>
            </div>
            <?php if(! empty($compareEnabled)): ?>
            <div class="chart-legend chart-legend--compact">
                <div class="legend-item"><div class="legend-dot legend-dot--solid" style="background:var(--ss-green)"></div>Periode ini</div>
                <div class="legend-item"><div class="legend-dot legend-dot--dash" style="background:var(--ss-muted)"></div>Periode lalu</div>
            </div>
            <?php endif; ?>
        </div>

        <div class="chart-penjualan-kpi">
            <div class="chart-kpi-cell chart-kpi-cell--omset">
                <div class="chart-kpi-label">Total Omset</div>
                <div class="chart-kpi-value">Rp <?php echo e($fmtChart($chartOmsetTotal)); ?></div>
                <?php if(! empty($compareEnabled)): ?>
                <div class="chart-kpi-compare">
                    <span class="chart-kpi-prev">vs Rp <?php echo e($fmtChart($chartCompareOmsetTotal)); ?></span>
                    <span class="<?php echo \Illuminate\Support\Arr::toCssClasses(['dash-cmp-badge', 'sm', 'up' => $omsetChartDelta['direction'] === 'up', 'down' => $omsetChartDelta['direction'] === 'down']); ?>"><?php echo e($omsetChartDelta['pct'] >= 0 ? '+' : ''); ?><?php echo e(number_format($omsetChartDelta['pct'], 1, ',', '.')); ?>%</span>
                </div>
                <?php endif; ?>
            </div>
            <div class="chart-kpi-cell chart-kpi-cell--tagihan">
                <div class="chart-kpi-label">Total Tagihan</div>
                <div class="chart-kpi-value">Rp <?php echo e($fmtChart($chartTagihanTotal)); ?></div>
                <?php if(! empty($compareEnabled)): ?>
                <div class="chart-kpi-compare">
                    <span class="chart-kpi-prev">vs Rp <?php echo e($fmtChart($chartCompareTagihanTotal)); ?></span>
                    <span class="<?php echo \Illuminate\Support\Arr::toCssClasses(['dash-cmp-badge', 'sm', 'up' => $tagihanChartDelta['direction'] === 'up', 'down' => $tagihanChartDelta['direction'] === 'down']); ?>"><?php echo e($tagihanChartDelta['pct'] >= 0 ? '+' : ''); ?><?php echo e(number_format($tagihanChartDelta['pct'], 1, ',', '.')); ?>%</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="chart-split">
            <div class="chart-split-item">
                <div class="chart-split-hd">
                    <span class="chart-split-dot" style="background:var(--ss-green)"></span>
                    <span class="chart-split-title">Pendapatan Omset</span>
                </div>
                <div class="chart-canvas chart-canvas--split"><canvas id="chartPendapatanOmset"></canvas></div>
            </div>
            <div class="chart-split-item">
                <div class="chart-split-hd">
                    <span class="chart-split-dot" style="background:var(--ss-purple)"></span>
                    <span class="chart-split-title">Total Tagihan</span>
                </div>
                <div class="chart-canvas chart-canvas--split"><canvas id="chartPendapatanTagihan"></canvas></div>
            </div>
        </div>
    </div>
</div>

<?php $__env->startPush('scripts'); ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    const labels = <?php echo json_encode($chartLabels ?? [], 15, 512) ?>;
    const penjualanPart = <?php echo json_encode($chartPenjualanPart ?? [], 15, 512) ?>;
    const penjualanJasa = <?php echo json_encode($chartPenjualanJasa ?? [], 15, 512) ?>;
    const pendapatanOmset = <?php echo json_encode($chartPendapatanOmset ?? [], 15, 512) ?>;
    const totalTagihan = <?php echo json_encode($chartTotalTagihan ?? [], 15, 512) ?>;
    const compareEnabled = <?php echo json_encode(! empty($compareEnabled), 15, 512) ?>;
    const comparePart = <?php echo json_encode($chartComparePenjualanPart ?? [], 15, 512) ?>;
    const compareJasa = <?php echo json_encode($chartComparePenjualanJasa ?? [], 15, 512) ?>;
    const compareOmset = <?php echo json_encode($chartComparePendapatanOmset ?? [], 15, 512) ?>;
    const compareTagihan = <?php echo json_encode($chartCompareTotalTagihan ?? [], 15, 512) ?>;
    const currentPeriodLabel = <?php echo json_encode($currentPeriodLabel ?? 'Periode ini', 15, 512) ?>;
    const comparePeriodLabel = <?php echo json_encode($comparePeriodLabel ?? 'Periode lalu', 15, 512) ?>;

    const rupiah = (v) => 'Rp ' + Number(v || 0).toLocaleString('id-ID');
    const fontFamily = 'Inter, ui-sans-serif, system-ui, sans-serif';

    Chart.defaults.font.family = fontFamily;
    Chart.defaults.color = '#8A8F98';
    Chart.defaults.borderColor = 'rgba(0,0,0,0.07)';

    const gridStyle = { color: 'rgba(244, 245, 247, 1)', drawBorder: false };
    const splitScaleOptions = (showXAxis = true) => ({
        x: {
            display: showXAxis,
            grid: { display: false },
            ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 10, font: { size: 9 } },
        },
        y: {
            beginAtZero: true,
            grid: gridStyle,
            ticks: {
                maxTicksLimit: 4,
                font: { size: 9 },
                callback: (v) => {
                    const n = Number(v);
                    if (n >= 1_000_000) return (n / 1_000_000).toFixed(1) + 'jt';
                    if (n >= 1_000) return (n / 1_000).toFixed(0) + 'rb';
                    return n;
                },
            },
        },
    });

    const hexToRgba = (hex, alpha) => {
        const h = hex.replace('#', '');
        const r = parseInt(h.substring(0, 2), 16);
        const g = parseInt(h.substring(2, 4), 16);
        const b = parseInt(h.substring(4, 6), 16);
        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    };

    const makeLineChart = (canvas, { data, color, label, showXAxis = true }) => {
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        const gradFill = ctx.createLinearGradient(0, 0, 0, 160);
        gradFill.addColorStop(0, hexToRgba(color, 0.22));
        gradFill.addColorStop(1, hexToRgba(color, 0.03));
        const pointRadius = labels.length <= 14 ? 3 : 0;

        new Chart(canvas, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label,
                    data,
                    borderColor: color,
                    backgroundColor: gradFill,
                    fill: true,
                    tension: 0.35,
                    pointRadius,
                    pointHoverRadius: 5,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: color,
                    pointBorderWidth: 2,
                    borderWidth: 2.5,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 700, easing: 'easeOutQuart' },
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0D0D0D',
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: { label: (ctx) => ctx.dataset.label + ': ' + rupiah(ctx.parsed.y) },
                    },
                },
                scales: splitScaleOptions(showXAxis),
            },
        });
    };

    const makeLineCompareChart = (canvas, { currentData, compareData, color, label, showXAxis = true }) => {
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        const gradFill = ctx.createLinearGradient(0, 0, 0, 160);
        gradFill.addColorStop(0, hexToRgba(color, 0.22));
        gradFill.addColorStop(1, hexToRgba(color, 0.03));

        const pointRadius = labels.length <= 14 ? 3 : 0;

        new Chart(canvas, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    {
                        label: label + ' · ' + currentPeriodLabel,
                        data: currentData,
                        borderColor: color,
                        backgroundColor: gradFill,
                        fill: true,
                        tension: 0.35,
                        pointRadius,
                        pointHoverRadius: 5,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: color,
                        pointBorderWidth: 2,
                        borderWidth: 2.5,
                        order: 1,
                    },
                    {
                        label: label + ' · ' + comparePeriodLabel,
                        data: compareData,
                        borderColor: 'rgba(138, 143, 152, 0.6)',
                        backgroundColor: 'transparent',
                        fill: false,
                        tension: 0.35,
                        borderDash: [5, 4],
                        pointRadius: 0,
                        pointHoverRadius: 4,
                        borderWidth: 1.75,
                        order: 2,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 700, easing: 'easeOutQuart' },
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0D0D0D',
                        padding: 12,
                        cornerRadius: 8,
                        filter: (item) => item.parsed.y !== null,
                        callbacks: {
                            label: (ctx) => ctx.dataset.label + ': ' + rupiah(ctx.parsed.y),
                            afterBody: (items) => {
                                const curItem = items.find((i) => i.datasetIndex === 0);
                                const prevItem = items.find((i) => i.datasetIndex === 1);
                                if (!curItem || !prevItem) return '';
                                const cur = Number(curItem.parsed.y || 0);
                                const prev = Number(prevItem.parsed.y || 0);
                                if (prev <= 0) return cur > 0 ? 'Selisih: +100%' : 'Selisih: 0%';
                                const pct = ((cur - prev) / prev) * 100;
                                const sign = pct >= 0 ? '+' : '';
                                return 'Selisih: ' + sign + pct.toFixed(1).replace('.', ',') + '%';
                            },
                        },
                    },
                },
                scales: splitScaleOptions(showXAxis),
            },
        });
    };

    const initSplitChart = (canvas, { currentData, compareData, color, label, showXAxis }) => {
        if (!canvas) return;
        if (compareEnabled) {
            makeLineCompareChart(canvas, { currentData, compareData, color, label, showXAxis });
            return;
        }
        makeLineChart(canvas, { data: currentData, color, label, showXAxis });
    };

    initSplitChart(document.getElementById('chartPenjualanPart'), {
        currentData: penjualanPart,
        compareData: comparePart,
        color: '#2E6BE6',
        label: 'Part',
        showXAxis: false,
    });
    initSplitChart(document.getElementById('chartPenjualanJasa'), {
        currentData: penjualanJasa,
        compareData: compareJasa,
        color: '#8CFA01',
        label: 'Jasa',
        showXAxis: true,
    });
    initSplitChart(document.getElementById('chartPendapatanOmset'), {
        currentData: pendapatanOmset,
        compareData: compareOmset,
        color: '#12B76A',
        label: 'Omset',
        showXAxis: false,
    });
    initSplitChart(document.getElementById('chartPendapatanTagihan'), {
        currentData: totalTagihan,
        compareData: compareTagihan,
        color: '#7C3AED',
        label: 'Tagihan',
        showXAxis: true,
    });
})();
</script>
<?php $__env->stopPush(); ?>
<?php /**PATH /Users/wahyusafrizal/work/Github/77racingstore/resources/views/partials/speedshop/dashboard-manager-charts.blade.php ENDPATH**/ ?>