@extends('layouts.speedshop')

@section('title', 'Dashboard Mekanik')

@section('content')
@push('styles')
@include('partials.speedshop.dashboard-load-animations')
@include('partials.speedshop.dashboard-manager-template-styles')
<style>
.ss-dashboard .mk-badge {
  display: inline-flex; align-items: center; border-radius: 999px; padding: 2px 10px;
  font-size: 11px; font-weight: 600;
}
.ss-dashboard .mk-badge.proses { background: rgba(46,107,230,0.12); color: #1d4ed8; }
.ss-dashboard .mk-badge.selesai { background: rgba(18,183,106,0.12); color: #047857; }
.ss-dashboard .mk-empty {
  padding: 28px 18px; text-align: center; color: var(--ss-muted); font-size: 13px;
}
.ss-dashboard .mk-warn {
  margin-bottom: 18px; padding: 14px 16px; border-radius: 10px;
  background: rgba(245,158,11,0.08); border: 1px solid rgba(245,158,11,0.25);
  color: #92400e; font-size: 13px;
}
.ss-dashboard .mk-chart-canvas { position: relative; height: 220px; }
.ss-dashboard .mk-chart-canvas--donut { height: 240px; max-width: 280px; margin: 0 auto; }
.ss-dashboard .charts-row.mk-charts { margin-bottom: 20px; }
@media (max-width: 900px) {
  .ss-dashboard .payment-grid.mk-payment-grid { grid-template-columns: 1fr !important; }
}
</style>
@endpush

@php
    $fmt = fn ($v) => number_format((float) $v, 0, ',', '.');
    $mekanikNama = $mekanik?->nama ?? auth()->user()->name;
    $mekanikLevel = $mekanik?->levelName();
@endphp

<div
    x-data="{ loading: false }"
    x-init="$nextTick(() => { requestAnimationFrame(() => $el.classList.add('is-ready')); })"
    :class="loading && 'is-loading'"
    class="ss-dashboard ss-dash-page"
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
            <p class="ss-dash-loading-sub">Menyiapkan kinerja mekanik</p>
        </div>
    </div>

    <div class="ss-dash-content">
        @include('partials.speedshop.dashboard-filter-strip', [
            'dashboardFilterExports' => [],
            'hideCompare' => true,
            'hideSpeedshopFilter' => true,
        ])

        @unless($mekanik)
        <div class="mk-warn ss-dash-enter" style="--ss-delay: 40ms">
            Akun Mekanik belum terhubung ke data master mekanik. Hubungi admin agar kinerja WO dapat ditampilkan.
        </div>
        @endunless

        <div class="hero-strip ss-dash-enter" style="--ss-delay: 80ms">
            <div class="hero-main">
                <div class="hero-eyebrow">Kinerja · {{ $periodLabel }}</div>
                <div class="hero-number">{{ $fmt($motorSelesaiPeriode) }}</div>
                <div class="hero-sub">
                    Motor selesai · {{ $mekanikNama }}
                    @if($mekanikLevel)
                        · {{ $mekanikLevel }}
                    @endif
                </div>
            </div>
            <div class="hero-divider"></div>
            <div class="hero-stat" style="min-width:90px">
                <div class="hero-stat-val">{{ $fmt($motorSelesaiHariIni) }}</div>
                <div class="hero-stat-lbl">Selesai hari ini</div>
            </div>
            <div class="hero-divider"></div>
            <div class="hero-stat" style="min-width:90px">
                <div class="hero-stat-val green">{{ $fmt($motorMasukPeriode) }}</div>
                <div class="hero-stat-lbl">WO masuk periode</div>
            </div>
        </div>

        <div class="payment-grid mk-payment-grid ss-dash-enter" style="--ss-delay: 140ms; grid-template-columns: repeat(2, 1fr);">
            <div class="pay-card">
                <div class="pay-card-top">
                    <span class="pay-card-label">Selesai hari ini</span>
                    <div class="pay-icon red">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <div class="pay-amount" style="color:var(--ss-red-ink)">{{ $fmt($motorSelesaiHariIni) }}</div>
                <div class="pay-period">{{ now()->translatedFormat('l, j F Y') }}</div>
            </div>
            <div class="pay-card">
                <div class="pay-card-top">
                    <span class="pay-card-label">Selesai periode</span>
                    <div class="pay-icon blue">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    </div>
                </div>
                <div class="pay-amount" style="color:var(--ss-blue)">{{ $fmt($motorSelesaiPeriode) }}</div>
                <div class="pay-period">{{ $periodLabel }}</div>
            </div>
        </div>

        <div class="charts-row mk-charts ss-dash-enter" style="--ss-delay: 220ms">
            <div class="chart-card chart-card--penjualan">
                <div class="chart-hd">
                    <div>
                        <div class="chart-title">Grafik Motor Selesai</div>
                        <div class="chart-sub">{{ $chartHint }}</div>
                    </div>
                </div>
                <div class="chart-penjualan-kpi" style="grid-template-columns: 1fr;">
                    <div class="chart-kpi-cell chart-kpi-cell--part">
                        <div class="chart-kpi-label">Total di grafik</div>
                        <div class="chart-kpi-value">{{ $fmt(array_sum($chartMotorSelesai ?? [])) }} motor</div>
                    </div>
                </div>
                <div class="mk-chart-canvas"><canvas id="chartMekanikMotor"></canvas></div>
            </div>

            <div class="chart-card chart-card--pendapatan">
                <div class="chart-hd">
                    <div>
                        <div class="chart-title">Kategori Service</div>
                        <div class="chart-sub">Komposisi motor selesai · {{ $periodLabel }}</div>
                    </div>
                </div>
                @if(empty($chartKategoriValues))
                <div class="mk-empty">Belum ada data kategori pada periode ini.</div>
                @else
                <div class="mk-chart-canvas mk-chart-canvas--donut"><canvas id="chartMekanikKategori"></canvas></div>
                @endif
            </div>
        </div>

        <div class="panel-card ss-dash-enter" style="--ss-delay: 300ms">
            <div class="panel-card-hd">
                <div>
                    <h2 class="panel-card-title">Selesai di periode</h2>
                    <p class="panel-card-sub">Motor yang Anda selesaikan pada {{ $periodLabel }}.</p>
                </div>
            </div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Kode Servis</th>
                            <th>Pelanggan</th>
                            <th>No. Polisi</th>
                            <th>Tanggal selesai</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($woSelesaiPeriode as $wo)
                        <tr>
                            <td class="font-semibold">{{ $wo->kode_servis }}</td>
                            <td>{{ $wo->pelanggan?->nama ?? '-' }}</td>
                            <td>{{ $wo->kendaraan?->nomor_polisi ?? '-' }}</td>
                            <td>
                                @php $tgl = $wo->tanggal_selesai ?? $wo->tanggal_masuk; @endphp
                                {{ $tgl ? \Carbon\Carbon::parse($tgl)->format('d/m/Y') : '-' }}
                            </td>
                            <td><span class="mk-badge selesai">Selesai</span></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="mk-empty">Belum ada motor selesai pada periode ini.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    const labels = @json($chartLabels ?? []);
    const motorSelesai = @json($chartMotorSelesai ?? []);
    const kategoriLabels = @json($chartKategoriLabels ?? []);
    const kategoriValues = @json($chartKategoriValues ?? []);
    const fontFamily = 'Inter, ui-sans-serif, system-ui, sans-serif';

    Chart.defaults.font.family = fontFamily;
    Chart.defaults.color = '#8A8F98';
    Chart.defaults.borderColor = 'rgba(0,0,0,0.07)';

    const hexToRgba = (hex, alpha) => {
        const h = hex.replace('#', '');
        const r = parseInt(h.substring(0, 2), 16);
        const g = parseInt(h.substring(2, 4), 16);
        const b = parseInt(h.substring(4, 6), 16);
        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    };

    const lineCanvas = document.getElementById('chartMekanikMotor');
    if (lineCanvas) {
        const ctx = lineCanvas.getContext('2d');
        const color = '#8CFA01';
        const gradFill = ctx.createLinearGradient(0, 0, 0, 220);
        gradFill.addColorStop(0, hexToRgba(color, 0.22));
        gradFill.addColorStop(1, hexToRgba(color, 0.03));
        const pointRadius = labels.length <= 14 ? 3 : 0;

        new Chart(lineCanvas, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: 'Motor selesai',
                    data: motorSelesai,
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
                        callbacks: {
                            label: (c) => 'Motor selesai: ' + Number(c.parsed.y || 0).toLocaleString('id-ID'),
                        },
                    },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 12, font: { size: 9 } },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                            maxTicksLimit: 5,
                            font: { size: 9 },
                        },
                        grid: { color: 'rgba(244, 245, 247, 1)', drawBorder: false },
                    },
                },
            },
        });
    }

    const donutCanvas = document.getElementById('chartMekanikKategori');
    if (donutCanvas && kategoriValues.length) {
        new Chart(donutCanvas, {
            type: 'doughnut',
            data: {
                labels: kategoriLabels,
                datasets: [{
                    data: kategoriValues,
                    backgroundColor: ['#8CFA01', '#2E6BE6', '#12B76A', '#F59E0B', '#7C3AED'],
                    borderWidth: 0,
                    hoverOffset: 6,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                animation: { duration: 700, easing: 'easeOutQuart' },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 10, boxHeight: 10, padding: 14, font: { size: 11 } },
                    },
                    tooltip: {
                        backgroundColor: '#0D0D0D',
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: (c) => {
                                const total = kategoriValues.reduce((a, b) => a + Number(b), 0) || 1;
                                const val = Number(c.parsed || 0);
                                const pct = ((val / total) * 100).toFixed(0);
                                return ' ' + c.label + ': ' + val.toLocaleString('id-ID') + ' (' + pct + '%)';
                            },
                        },
                    },
                },
            },
        });
    }
})();
</script>
@endpush
@endsection
