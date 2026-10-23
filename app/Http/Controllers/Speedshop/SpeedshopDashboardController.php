<?php

namespace App\Http\Controllers\Speedshop;

use App\Exports\DashboardTransaksiDetailExport;
use App\Exports\DashboardTransaksiExport;
use App\Http\Controllers\Controller;
use App\Models\Mekanik;
use App\Models\Product;
use App\Models\ServiceOrder;
use App\Models\Speedshop;
use App\Models\SpeedshopBranchTarget;
use App\Models\SpeedshopMekanikTarget;
use App\Support\Speedshop\SpeedshopTargetMath;
use App\Services\SpeedshopFinanceService;
use App\Support\DashboardPeriodResolver;
use App\Support\ProductMasterCatalog;
use App\Support\SpeedshopLabaRugiFinancials;
use App\Support\SpeedshopPendapatanBreakdown;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class SpeedshopDashboardController extends Controller
{
    /** @var list<string> */
    private const DASHBOARD_CABANG_CITY_ORDER = [
        'bandung',
        'bogor',
        'depok',
        'bekasi',
    ];

    public function index(Request $request)
    {
        if (auth()->user()?->isOwner()) {
            return redirect()->route('owner.dashboard', $request->query());
        }

        if (auth()->user()?->isSpeedshopMekanik()) {
            return $this->mekanikDashboard($request);
        }

        return view('speedshop.dashboard', $this->dashboardViewData($request));
    }

    /**
     * Dashboard kinerja pribadi untuk role Mekanik (jumlah motor dikerjakan).
     */
    private function mekanikDashboard(Request $request): View
    {
        $user = auth()->user();
        $mekanik = $user?->mekanik;
        $period = DashboardPeriodResolver::fromRequest($request, 'harian');
        $dari = $period['dari'];
        $sampai = $period['sampai'];
        $mode = $period['mode'];
        $today = now()->format('Y-m-d');

        $motorSelesaiHariIni = 0;
        $motorSelesaiPeriode = 0;
        $motorSedangProses = 0;
        $motorMasukPeriode = 0;
        $woProses = collect();
        $woSelesaiPeriode = collect();
        $chartSeries = [
            'chartLabels' => [],
            'chartMotorSelesai' => [],
            'chartHint' => '',
        ];
        $kategoriChart = [
            'labels' => [],
            'values' => [],
        ];

        if ($mekanik) {
            $base = ServiceOrder::query()
                ->where('mekanik_id', $mekanik->id)
                ->where(function ($q) {
                    $q->whereNull('tipe_pemesanan')
                        ->orWhere('tipe_pemesanan', '!=', ServiceOrder::TIPE_PEMBELIAN_PART);
                });

            $motorSelesaiHariIni = (clone $base)
                ->where('status', 'selesai')
                ->whereTransaksiDateBetween($today, $today)
                ->count();

            $motorSelesaiPeriode = (clone $base)
                ->where('status', 'selesai')
                ->whereTransaksiDateBetween($dari->format('Y-m-d'), $sampai->format('Y-m-d'))
                ->count();

            $motorSedangProses = (clone $base)
                ->where('status', 'proses')
                ->count();

            $motorMasukPeriode = (clone $base)
                ->whereBetween('tanggal_masuk', [$dari->toDateString(), $sampai->toDateString()])
                ->count();

            $woProses = (clone $base)
                ->where('status', 'proses')
                ->with(['pelanggan', 'kendaraan'])
                ->orderByDesc('tanggal_masuk')
                ->limit(20)
                ->get();

            $woSelesaiPeriode = (clone $base)
                ->where('status', 'selesai')
                ->whereTransaksiDateBetween($dari->format('Y-m-d'), $sampai->format('Y-m-d'))
                ->with(['pelanggan', 'kendaraan'])
                ->orderByRaw(ServiceOrder::transaksiDateSql().' desc')
                ->orderByDesc('id')
                ->limit(30)
                ->get();

            $chartSeries = $this->buildMekanikChartSeries(
                $mekanik->id,
                $mode,
                $dari,
                $sampai,
                $period['selectedDate'],
                $period['selectedYear'],
            );
            $kategoriChart = $this->buildMekanikKategoriChart(
                $mekanik->id,
                $dari->format('Y-m-d'),
                $sampai->format('Y-m-d'),
            );
        }

        $periodLabel = $this->dashboardCurrentPeriodLabel(
            $mode,
            $dari,
            $sampai,
            $period['selectedMonth'],
            $period['selectedYear'],
        );

        return view('speedshop.dashboard-mekanik', [
            'mekanik' => $mekanik,
            'mode' => $mode,
            'dari' => $dari,
            'sampai' => $sampai,
            'selectedDate' => $period['selectedDate'],
            'selectedMonth' => $period['selectedMonth'],
            'selectedYear' => $period['selectedYear'],
            'selectedDari' => $period['selectedDari'],
            'selectedSampai' => $period['selectedSampai'],
            'periodLabel' => $periodLabel,
            'currentPeriodLabel' => $periodLabel,
            'motorSelesaiHariIni' => $motorSelesaiHariIni,
            'motorSelesaiPeriode' => $motorSelesaiPeriode,
            'motorSedangProses' => $motorSedangProses,
            'motorMasukPeriode' => $motorMasukPeriode,
            'woProses' => $woProses,
            'woSelesaiPeriode' => $woSelesaiPeriode,
            'chartLabels' => $chartSeries['chartLabels'],
            'chartMotorSelesai' => $chartSeries['chartMotorSelesai'],
            'chartHint' => $chartSeries['chartHint'],
            'chartKategoriLabels' => $kategoriChart['labels'],
            'chartKategoriValues' => $kategoriChart['values'],
        ]);
    }

    /**
     * @return array{chartLabels: list<string>, chartMotorSelesai: list<int>, chartHint: string}
     */
    private function buildMekanikChartSeries(
        int $mekanikId,
        string $mode,
        \Carbon\Carbon $dari,
        \Carbon\Carbon $sampai,
        string $selectedDate,
        int $selectedYear,
    ): array {
        $driver = DB::connection()->getDriverName();
        $transaksiDate = ServiceOrder::transaksiDateSql('service_orders');
        $baseSelesai = ServiceOrder::query()
            ->where('mekanik_id', $mekanikId)
            ->where('status', 'selesai')
            ->where(function ($q) {
                $q->whereNull('tipe_pemesanan')
                    ->orWhere('tipe_pemesanan', '!=', ServiceOrder::TIPE_PEMBELIAN_PART);
            });

        if ($mode === 'tahunan') {
            $chartLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            $monthExpr = $driver === 'sqlite'
                ? "CAST(strftime('%m', {$transaksiDate}) AS INTEGER)"
                : "MONTH({$transaksiDate})";

            $rows = (clone $baseSelesai)
                ->whereTransaksiDateBetween("{$selectedYear}-01-01", "{$selectedYear}-12-31")
                ->selectRaw("{$monthExpr} as m, COUNT(*) as total")
                ->groupBy('m')
                ->pluck('total', 'm');

            $chartMotorSelesai = [];
            for ($i = 1; $i <= 12; $i++) {
                $chartMotorSelesai[] = (int) ($rows[$i] ?? 0);
            }

            return [
                'chartLabels' => $chartLabels,
                'chartMotorSelesai' => $chartMotorSelesai,
                'chartHint' => 'Per bulan pada tahun '.$selectedYear,
            ];
        }

        // Harian: tampilkan tren 14 hari terakhir berakhir di tanggal terpilih
        if ($mode === 'harian') {
            $end = \Carbon\Carbon::parse($selectedDate)->startOfDay();
            $start = $end->copy()->subDays(13);
        } else {
            $start = $dari->copy()->startOfDay();
            $end = $sampai->copy()->startOfDay();
        }

        $dateExpr = $driver === 'sqlite'
            ? "strftime('%Y-%m-%d', {$transaksiDate})"
            : $transaksiDate;

        $rows = (clone $baseSelesai)
            ->whereTransaksiDateBetween($start->toDateString(), $end->toDateString())
            ->selectRaw("{$dateExpr} as d, COUNT(*) as total")
            ->groupBy('d')
            ->pluck('total', 'd');

        $chartLabels = [];
        $chartMotorSelesai = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $key = $cursor->toDateString();
            $chartLabels[] = $cursor->format('d/m');
            $chartMotorSelesai[] = (int) ($rows[$key] ?? 0);
            $cursor->addDay();
        }

        $hint = match ($mode) {
            'harian' => 'Tren 14 hari sampai '.$end->translatedFormat('j M Y'),
            'bulanan' => 'Per hari pada '.$dari->translatedFormat('F Y'),
            'range' => 'Per hari ('.$dari->format('d/m/Y').' – '.$sampai->format('d/m/Y').')',
            default => 'Per hari',
        };

        return [
            'chartLabels' => $chartLabels,
            'chartMotorSelesai' => $chartMotorSelesai,
            'chartHint' => $hint,
        ];
    }

    /**
     * @return array{labels: list<string>, values: list<int>}
     */
    private function buildMekanikKategoriChart(int $mekanikId, string $startYmd, string $endYmd): array
    {
        $rows = ServiceOrder::query()
            ->where('mekanik_id', $mekanikId)
            ->where('status', 'selesai')
            ->where(function ($q) {
                $q->whereNull('tipe_pemesanan')
                    ->orWhere('tipe_pemesanan', '!=', ServiceOrder::TIPE_PEMBELIAN_PART);
            })
            ->whereTransaksiDateBetween($startYmd, $endYmd)
            ->selectRaw('COALESCE(kategori_service, ?) as kategori, COUNT(*) as total', ['lainnya'])
            ->groupBy('kategori')
            ->orderByDesc('total')
            ->get();

        $labels = [];
        $values = [];
        foreach ($rows as $row) {
            $key = (string) $row->kategori;
            $labels[] = ServiceOrder::KATEGORI_SERVICE[$key] ?? ucfirst(str_replace('_', ' ', $key));
            $values[] = (int) $row->total;
        }

        return compact('labels', 'values');
    }

    /**
     * @return array<string, mixed>
     */
    public function dashboardViewData(Request $request): array
    {
        $period = $this->resolveDashboardPeriod($request);
        $dari = $period['dari'];
        $sampai = $period['sampai'];
        $mode = $period['mode'];
        $selectedDate = $period['selectedDate'];
        $selectedMonth = $period['selectedMonth'];
        $selectedYear = $period['selectedYear'];

        $baseQuery = $this->dashboardCompletedOrdersBaseQuery($request, $dari, $sampai);
        $serviceOrderIds = (clone $baseQuery)->pluck('id');
        extract($this->collectDashboardSummaryMetrics($serviceOrderIds));

        $compareEnabled = $request->boolean('compare');
        $comparePeriodLabel = null;
        $compareMetrics = null;
        $comparisonPeriod = null;
        if ($compareEnabled) {
            $comparisonPeriod = $this->resolveComparisonPeriod($period, $request);
            if ($comparisonPeriod !== null) {
                $comparePeriodLabel = $comparisonPeriod['label'];
                $compareOrderIds = (clone $this->dashboardCompletedOrdersBaseQuery(
                    $request,
                    $comparisonPeriod['dari'],
                    $comparisonPeriod['sampai'],
                ))->pluck('id');
                $compareMetrics = $this->collectDashboardSummaryMetrics($compareOrderIds);
            } else {
                $compareEnabled = false;
            }
        }

        $currentPeriodLabel = $this->dashboardCurrentPeriodLabel($mode, $dari, $sampai, $selectedMonth, $selectedYear);
        $selectedCompareDari = (string) $request->input('compare_dari', '');
        $selectedCompareSampai = (string) $request->input('compare_sampai', '');
        if ($compareEnabled && $comparisonPeriod !== null) {
            $selectedCompareDari = $selectedCompareDari !== ''
                ? $selectedCompareDari
                : $comparisonPeriod['dari']->format('Y-m-d');
            $selectedCompareSampai = $selectedCompareSampai !== ''
                ? $selectedCompareSampai
                : $comparisonPeriod['sampai']->format('Y-m-d');
        }

        $dashboardSpeedshopIds = $this->resolveDashboardSpeedshopIds($request);
        $cabangPendapatan = $this->collectDashboardCabangPendapatan($request, $dari, $sampai, $dashboardSpeedshopIds);
        if ($compareEnabled && $comparisonPeriod !== null) {
            $cabangPendapatan = $this->mergeCabangCompareMetrics(
                $cabangPendapatan,
                $this->collectDashboardCabangPendapatan(
                    $request,
                    $comparisonPeriod['dari'],
                    $comparisonPeriod['sampai'],
                    $dashboardSpeedshopIds,
                ),
            );
        }
        $speedshopSummary = $this->collectDashboardSpeedshopSummary($request, $dari, $sampai, $dashboardSpeedshopIds);

        $managerDashboardCharts = $this->usesManagerDashboardCharts();
        $dashboardTopProducts = $managerDashboardCharts
            ? []
            : $this->collectDashboardTopProducts($request, $dari, $sampai);
        $dashboardManagerTopLists = $managerDashboardCharts
            ? $this->collectDashboardManagerTopLists(
                $request,
                $dari,
                $sampai,
                10,
                $compareEnabled && $comparisonPeriod !== null ? $comparisonPeriod['dari'] : null,
                $compareEnabled && $comparisonPeriod !== null ? $comparisonPeriod['sampai'] : null,
                $comparePeriodLabel,
                $currentPeriodLabel,
            )
            : [];
        $dashboardMekanikPerformance = $managerDashboardCharts
            ? $this->collectDashboardMekanikPerformance($request, $dari, $sampai)
            : ['all' => [], 'by_cabang' => []];
        $dashboardTargetAchievement = $managerDashboardCharts
            ? $this->collectDashboardTargetAchievement($request, $dari, $sampai, $dashboardSpeedshopIds)
            : [];

        extract($this->resolveDashboardSpeedshopFilterContext($request));

        $dashboardAllSpeedshops = auth()->user()?->isOwner()
            ? empty($selectedDashboardSpeedshopId) && count(auth()->user()->activeSpeedshopIds()) > 1
            : count($dashboardSpeedshopIds) > 1;

        $serviceOrders = null;
        $chartPayload = [];
        if ($managerDashboardCharts) {
            $chartPayload = $this->buildDashboardChartSeries(
                $request,
                $dari,
                $sampai,
                $mode,
                $selectedDate,
                $selectedMonth,
                $selectedYear,
            );
            $chartPayload['chartPeriodHint'] = $this->dashboardChartPeriodHint(
                $mode,
                $dari,
                $sampai,
                $selectedDate,
                $selectedMonth,
                $selectedYear,
            );
            if ($compareEnabled && $comparisonPeriod !== null) {
                $chartPayload = array_merge(
                    $chartPayload,
                    $this->buildDashboardCompareChartOverlay($request, $period, $comparisonPeriod, $chartPayload),
                );
            }
        } else {
            $serviceOrders = (clone $baseQuery)
                ->with([
                    'invoice',
                    'pelanggan',
                    'kendaraan',
                    'mekanik',
                    'jasaServis',
                    'products',
                    'speedshop',
                ])
                ->orderByDesc(DB::raw('COALESCE(tanggal_selesai, tanggal_masuk)'))
                ->orderByDesc('id')
                ->paginate(15)
                ->withQueryString();
        }

        $kasTunaiRingkasan = $this->resolveDashboardKasTunaiRingkasan(
            $request,
            $dari,
            $sampai,
            $currentPeriodLabel,
            $dashboardSpeedshopIds,
            $selectedDashboardSpeedshopId,
        );

        return compact(
            'managerDashboardCharts',
            'mode',
            'selectedDate',
            'selectedMonth',
            'selectedYear',
            'dari',
            'sampai',
            'dashboardAllSpeedshops',
            'dashboardSpeedshopOptions',
            'selectedDashboardSpeedshopId',
            'selectedDashboardSpeedshopName',
            'unitEntry',
            'penjualanPart',
            'pendapatanJasa',
            'totalOmset',
            'pendapatanOmset',
            'totalCharge',
            'totalPpn',
            'pendapatanTunai',
            'pendapatanQris',
            'pendapatanTransfer',
            'pendapatanEdc',
            'jumlahItemPart',
            'jumlahItemJasa',
            'serviceOrders',
            'compareEnabled',
            'comparePeriodLabel',
            'compareMetrics',
            'currentPeriodLabel',
            'selectedCompareDari',
            'selectedCompareSampai',
            'kasTunaiRingkasan',
        ) + [
            'selectedDari' => $period['selectedDari'],
            'selectedSampai' => $period['selectedSampai'],
        ] + $cabangPendapatan + $speedshopSummary + [
            'dashboardTopProducts' => $dashboardTopProducts,
            'dashboardManagerTopLists' => $dashboardManagerTopLists,
            'dashboardMekanikPerformance' => $dashboardMekanikPerformance,
            'dashboardTargetAchievement' => $dashboardTargetAchievement,
        ] + $chartPayload;
    }

    /**
     * Ringkasan kas tunai gabungan (transaksi + permintaan − BOP) untuk Outlet/FLP.
     *
     * @param  list<int>  $dashboardSpeedshopIds
     * @return array<string, mixed>|null
     */
    private function resolveDashboardKasTunaiRingkasan(
        Request $request,
        \Carbon\Carbon $dari,
        \Carbon\Carbon $sampai,
        string $currentPeriodLabel,
        array $dashboardSpeedshopIds,
        ?int $selectedDashboardSpeedshopId,
    ): ?array {
        $user = auth()->user();
        if (! $user?->isSpeedshop()) {
            return null;
        }

        // Tampilkan untuk FLP / Outlet / Staf (bukan Manager regional multi-cabang tanpa filter).
        if ($user->isManager() && $user->isSpeedshopRegionalManager() && empty($selectedDashboardSpeedshopId)) {
            return null;
        }

        if (! $user->isOutlet() && ! $user->isManager() && ! $user->isAccounting()) {
            return null;
        }

        $speedshopId = $selectedDashboardSpeedshopId;
        if (! $speedshopId && count($dashboardSpeedshopIds) === 1) {
            $speedshopId = (int) $dashboardSpeedshopIds[0];
        }
        if (! $speedshopId) {
            $speedshopId = $user->speedshopScopeId() ?? $user->activeSpeedshopId();
        }
        if (! $speedshopId) {
            return null;
        }

        return app(SpeedshopFinanceService::class)->computeKasTunaiRingkasanForSpeedshop(
            (int) $speedshopId,
            $dari,
            $sampai,
            $currentPeriodLabel,
        );
    }

    private function usesManagerDashboardCharts(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->isOwner()) {
            return true;
        }

        if ($user->isModifikasiOriHeadWarehouse() || $user->isModifikasiOriManager()) {
            return true;
        }

        return $user->section === 'speedshop' && $user->isManager();
    }

    public function omsetDetail(Request $request)
    {
        $type = (string) $request->input('type', '');
        if (! in_array($type, ['part', 'jasa', 'omset', 'charge'], true)) {
            return response()->json(['message' => 'Tipe rincian tidak valid.'], 422);
        }

        $period = $this->resolveDashboardPeriod($request);
        $baseQuery = $this->dashboardCompletedOrdersBaseQuery($request, $period['dari'], $period['sampai']);
        $orderIdsQuery = (clone $baseQuery)->select('service_orders.id');
        $periodHint = $this->dashboardChartPeriodHint(
            $period['mode'],
            $period['dari'],
            $period['sampai'],
            $period['selectedDate'],
            $period['selectedMonth'],
            $period['selectedYear'],
        );

        $payload = match ($type) {
            'part' => $this->buildOmsetDetailPart($orderIdsQuery),
            'jasa' => $this->buildOmsetDetailJasa($orderIdsQuery),
            'omset' => $this->buildOmsetDetailOmset($baseQuery),
            'charge' => $this->buildOmsetDetailCharge($baseQuery),
        };

        $compareEnabled = $request->boolean('compare');
        $comparePeriodLabel = null;
        $compareTotal = null;
        $compareDeltaPercent = null;

        if ($compareEnabled) {
            $comparisonPeriod = $this->resolveComparisonPeriod($period, $request);
            if ($comparisonPeriod !== null) {
                $comparePeriodLabel = $comparisonPeriod['label'];
                $compareBaseQuery = $this->dashboardCompletedOrdersBaseQuery(
                    $request,
                    $comparisonPeriod['dari'],
                    $comparisonPeriod['sampai'],
                );
                $compareOrderIdsQuery = (clone $compareBaseQuery)->select('service_orders.id');
                $comparePayload = match ($type) {
                    'part' => $this->buildOmsetDetailPart($compareOrderIdsQuery),
                    'jasa' => $this->buildOmsetDetailJasa($compareOrderIdsQuery),
                    'omset' => $this->buildOmsetDetailOmset($compareBaseQuery),
                    'charge' => $this->buildOmsetDetailCharge($compareBaseQuery),
                };
                $compareTotal = (float) ($comparePayload['total'] ?? 0);
                $compareDeltaPercent = $this->dashboardDeltaPercent((float) $payload['total'], $compareTotal);

                if (in_array($type, ['part', 'jasa'], true)) {
                    $payload = $this->buildOmsetDetailComparePayload(
                        $type,
                        $payload,
                        $comparePayload,
                        'Periode '.$periodHint,
                        $comparePeriodLabel,
                    );
                }
            } else {
                $compareEnabled = false;
            }
        }

        return response()->json([
            'type' => $type,
            'title' => $payload['title'],
            'subtitle' => 'Periode '.$periodHint,
            'columns' => $payload['columns'],
            'rows' => $payload['rows'],
            'total' => $payload['total'],
            'total_label' => $payload['total_label'],
            'compare_enabled' => $compareEnabled && $comparePeriodLabel !== null,
            'compare_period_label' => $comparePeriodLabel,
            'compare_total' => $compareTotal,
            'compare_delta_percent' => $compareDeltaPercent,
        ]);
    }

    public function exportExcel(Request $request)
    {
        $period = $this->resolveDashboardPeriod($request);
        $rows = $this->dashboardExportRows($request, $period['dari'], $period['sampai']);
        $filename = 'dashboard-transaksi-'.$period['dari']->format('Ymd').'-'.$period['sampai']->format('Ymd').'-'.now()->format('His').'.xlsx';

        return Excel::download(new DashboardTransaksiExport($rows), $filename);
    }

    public function exportExcelDetail(Request $request)
    {
        $period = $this->resolveDashboardPeriod($request);
        $orders = $this->dashboardCompletedOrdersBaseQuery($request, $period['dari'], $period['sampai'])
            ->with([
                'invoice',
                'pelanggan',
                'kendaraan',
                'mekanik',
                'jasaServis',
                'products',
            ])
            ->orderByDesc(DB::raw('COALESCE(tanggal_selesai, tanggal_masuk)'))
            ->orderByDesc('id')
            ->get();

        $rows = DashboardTransaksiDetailExport::rowsFromOrders($orders);
        $filename = 'dashboard-transaksi-rinci-'.$period['dari']->format('Ymd').'-'.$period['sampai']->format('Ymd').'-'.now()->format('His').'.xlsx';

        return Excel::download(new DashboardTransaksiDetailExport($rows), $filename);
    }

    public function exportPdf(Request $request)
    {
        $period = $this->resolveDashboardPeriod($request);
        $rows = $this->dashboardExportRows($request, $period['dari'], $period['sampai']);
        $filename = 'dashboard-transaksi-'.$period['dari']->format('Ymd').'-'.$period['sampai']->format('Ymd').'-'.now()->format('His').'.pdf';

        return Pdf::loadView('speedshop.dashboard-transaksi-pdf', [
            'rows' => $rows,
            'dari' => $period['dari'],
            'sampai' => $period['sampai'],
            'mode' => $period['mode'],
            'printedAt' => now(),
        ])->setPaper('a4', 'landscape')->download($filename);
    }

    /**
     * @return array{dari: \Carbon\Carbon, sampai: \Carbon\Carbon, mode: string, selectedDate: string, selectedMonth: string, selectedYear: int, selectedDari: string, selectedSampai: string}
     */
    private function resolveDashboardPeriod(Request $request): array
    {
        $mode = in_array($request->input('mode'), ['harian', 'bulanan', 'tahunan', 'range'], true)
            ? $request->input('mode')
            : 'bulanan';

        if ($request->filled('dari') && $request->filled('sampai') && ! $request->filled('mode')) {
            $mode = 'range';
        }

        $selectedDate = $request->input('tanggal') ?: now()->format('Y-m-d');
        $selectedMonth = $request->input('bulan') ?: now()->format('Y-m');
        $selectedYear = (int) ($request->input('tahun') ?: now()->year);
        $selectedDari = $request->input('dari') ?: now()->startOfMonth()->format('Y-m-d');
        $selectedSampai = $request->input('sampai') ?: now()->format('Y-m-d');

        if ($mode === 'range') {
            try {
                $dari = \Carbon\Carbon::parse($selectedDari)->startOfDay();
                $sampai = \Carbon\Carbon::parse($selectedSampai)->endOfDay();
            } catch (\Throwable $e) {
                $selectedDari = now()->startOfMonth()->format('Y-m-d');
                $selectedSampai = now()->format('Y-m-d');
                $dari = now()->startOfMonth()->startOfDay();
                $sampai = now()->endOfDay();
            }

            if ($dari->gt($sampai)) {
                [$dari, $sampai] = [$sampai->copy()->startOfDay(), $dari->copy()->endOfDay()];
                $selectedDari = $dari->format('Y-m-d');
                $selectedSampai = $sampai->format('Y-m-d');
            }

            return [
                'dari' => $dari,
                'sampai' => $sampai,
                'mode' => 'range',
                'selectedDate' => $selectedDari,
                'selectedMonth' => $dari->format('Y-m'),
                'selectedYear' => (int) $dari->format('Y'),
                'selectedDari' => $selectedDari,
                'selectedSampai' => $selectedSampai,
            ];
        }

        if ($mode === 'harian') {
            try {
                $dari = \Carbon\Carbon::parse($selectedDate)->startOfDay();
                $sampai = \Carbon\Carbon::parse($selectedDate)->endOfDay();
            } catch (\Throwable $e) {
                $selectedDate = now()->format('Y-m-d');
                $dari = now()->startOfDay();
                $sampai = now()->endOfDay();
            }
        } elseif ($mode === 'tahunan') {
            $selectedYear = max(2000, min(2100, $selectedYear));
            $dari = now()->setYear($selectedYear)->startOfYear();
            $sampai = now()->setYear($selectedYear)->endOfYear();
        } else {
            try {
                $monthBase = \Carbon\Carbon::parse($selectedMonth.'-01');
            } catch (\Throwable $e) {
                $selectedMonth = now()->format('Y-m');
                $monthBase = now()->startOfMonth();
            }
            $dari = $monthBase->copy()->startOfMonth();
            $sampai = $monthBase->copy()->endOfMonth();
        }

        return [
            'dari' => $dari,
            'sampai' => $sampai,
            'mode' => $mode,
            'selectedDate' => $selectedDate,
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
            'selectedDari' => $dari->format('Y-m-d'),
            'selectedSampai' => $sampai->format('Y-m-d'),
        ];
    }

    /**
     * @param  array{dari: \Carbon\Carbon, sampai: \Carbon\Carbon, mode: string, selectedDate?: string, selectedMonth?: string, selectedYear?: int}  $period
     * @return array{dari: \Carbon\Carbon, sampai: \Carbon\Carbon, label: string}|null
     */
    private function resolveComparisonPeriod(array $period, Request $request): ?array
    {
        $dari = $period['dari'];
        $sampai = $period['sampai'];
        $mode = $period['mode'];

        if ($mode === 'range') {
            $custom = DashboardPeriodResolver::compareRangeFromRequest($request);
            if ($custom !== null) {
                return $custom;
            }
        }

        return match ($mode) {
            'harian' => [
                'dari' => $dari->copy()->subDay()->startOfDay(),
                'sampai' => $sampai->copy()->subDay()->endOfDay(),
                'label' => $dari->copy()->subDay()->translatedFormat('j M Y'),
            ],
            'bulanan' => [
                'dari' => $dari->copy()->subMonthNoOverflow()->startOfMonth(),
                'sampai' => $dari->copy()->subMonthNoOverflow()->endOfMonth(),
                'label' => $dari->copy()->subMonthNoOverflow()->translatedFormat('F Y'),
            ],
            'tahunan' => [
                'dari' => $dari->copy()->subYear()->startOfYear(),
                'sampai' => $dari->copy()->subYear()->endOfYear(),
                'label' => (string) $dari->copy()->subYear()->year,
            ],
            'range' => (function () use ($dari, $sampai) {
                $days = $dari->diffInDays($sampai) + 1;
                $prevSampai = $dari->copy()->subDay()->endOfDay();
                $prevDari = $prevSampai->copy()->subDays($days - 1)->startOfDay();

                return [
                    'dari' => $prevDari,
                    'sampai' => $prevSampai,
                    'label' => $prevDari->translatedFormat('j M').' – '.$prevSampai->translatedFormat('j M Y'),
                ];
            })(),
            default => null,
        };
    }

    private function dashboardCurrentPeriodLabel(
        string $mode,
        \Carbon\Carbon $dari,
        \Carbon\Carbon $sampai,
        string $selectedMonth,
        int $selectedYear,
    ): string {
        return match ($mode) {
            'tahunan' => (string) $selectedYear,
            'bulanan' => \Carbon\Carbon::parse($selectedMonth.'-01')->translatedFormat('F Y'),
            'range' => $dari->translatedFormat('j M').' – '.$sampai->translatedFormat('j M Y'),
            default => $dari->translatedFormat('j M Y'),
        };
    }

    /**
     * @param  array<string, mixed>  $cabangPendapatan
     * @param  array<string, mixed>  $compareCabangPendapatan
     * @return array<string, mixed>
     */
    private function mergeCabangCompareMetrics(array $cabangPendapatan, array $compareCabangPendapatan): array
    {
        $compareByKey = collect($compareCabangPendapatan['dashboardCabangPendapatan'] ?? [])
            ->keyBy('key');

        $cabangPendapatan['dashboardCabangPendapatan'] = collect($cabangPendapatan['dashboardCabangPendapatan'] ?? [])
            ->map(function (array $card) use ($compareByKey) {
                $compareAmount = (float) ($compareByKey->get($card['key'])['amount'] ?? 0);
                $card['compareAmount'] = $compareAmount;
                $card['compareDelta'] = $this->dashboardDeltaPercent((float) ($card['amount'] ?? 0), $compareAmount);

                return $card;
            })
            ->values()
            ->all();

        $cabangPendapatan['compareTotalPendapatanCabang'] = (float) ($compareCabangPendapatan['totalPendapatanCabang'] ?? 0);

        return $cabangPendapatan;
    }

    /**
     * @param  array{dari: \Carbon\Carbon, sampai: \Carbon\Carbon, mode: string}  $period
     * @param  array{dari: \Carbon\Carbon, sampai: \Carbon\Carbon, label: string}  $comparisonPeriod
     * @param  array<string, mixed>  $currentCharts
     * @return array<string, list<float>>
     */
    private function buildDashboardCompareChartOverlay(
        Request $request,
        array $period,
        array $comparisonPeriod,
        array $currentCharts,
    ): array {
        $mode = $period['mode'];
        $compareCharts = $this->buildDashboardChartSeries(
            $request,
            $comparisonPeriod['dari'],
            $comparisonPeriod['sampai'],
            $mode,
            $comparisonPeriod['dari']->format('Y-m-d'),
            $comparisonPeriod['dari']->format('Y-m'),
            (int) $comparisonPeriod['dari']->format('Y'),
        );

        $align = function (array $compareSeries) use ($mode, $period, $currentCharts): array {
            $targetLen = count($currentCharts['chartLabels'] ?? []);
            if ($targetLen === 0) {
                return [];
            }

            if (in_array($mode, ['tahunan', 'harian'], true)) {
                $aligned = array_slice(array_values($compareSeries), 0, $targetLen);
                while (count($aligned) < $targetLen) {
                    $aligned[] = 0.0;
                }

                return $aligned;
            }

            $aligned = [];
            $cursor = $period['dari']->copy()->startOfDay();
            $end = $period['sampai']->copy()->startOfDay();
            while ($cursor->lte($end) && count($aligned) < $targetLen) {
                if ($mode === 'bulanan') {
                    $index = $cursor->day - 1;
                    $aligned[] = (float) ($compareSeries[$index] ?? 0);
                } else {
                    $index = $period['dari']->copy()->startOfDay()->diffInDays($cursor);
                    $aligned[] = (float) ($compareSeries[$index] ?? 0);
                }
                $cursor->addDay();
            }

            while (count($aligned) < $targetLen) {
                $aligned[] = 0.0;
            }

            return $aligned;
        };

        return [
            'chartComparePenjualanPart' => $align($compareCharts['chartPenjualanPart'] ?? []),
            'chartComparePenjualanJasa' => $align($compareCharts['chartPenjualanJasa'] ?? []),
            'chartComparePendapatanOmset' => $align($compareCharts['chartPendapatanOmset'] ?? []),
            'chartCompareTotalTagihan' => $align($compareCharts['chartTotalTagihan'] ?? []),
        ];
    }

    private function dashboardDeltaPercent(float $current, float $previous): float
    {
        if ($previous <= 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    /**
     * @param  array{title: string, columns: list<array<string, mixed>>, rows: list<array<string, mixed>>, total: float, total_label: string}  $current
     * @param  array{title: string, columns: list<array<string, mixed>>, rows: list<array<string, mixed>>, total: float, total_label: string}  $previous
     * @return array{title: string, columns: list<array<string, mixed>>, rows: list<array<string, mixed>>, total: float, total_label: string}
     */
    private function buildOmsetDetailComparePayload(
        string $type,
        array $current,
        array $previous,
        string $currentLabel,
        string $previousLabel,
    ): array {
        $rowKey = fn (array $row): string => $type === 'part'
            ? (($row['kode'] ?? '-').'|'.($row['nama'] ?? ''))
            : (string) ($row['nama'] ?? '');

        $currentMap = collect($current['rows'])->keyBy($rowKey);
        $previousMap = collect($previous['rows'])->keyBy($rowKey);
        $keys = $currentMap->keys()->merge($previousMap->keys())->unique()->values();

        $rows = $keys->map(function (string $key) use ($currentMap, $previousMap, $type) {
            $cur = $currentMap->get($key, []);
            $prev = $previousMap->get($key, []);
            $nilaiIni = (float) ($cur['nilai'] ?? 0);
            $nilaiLalu = (float) ($prev['nilai'] ?? 0);

            $row = [
                'qty_ini' => (int) ($cur['qty'] ?? 0),
                'nilai_ini' => $nilaiIni,
                'qty_lalu' => (int) ($prev['qty'] ?? 0),
                'nilai_lalu' => $nilaiLalu,
                'delta_pct' => $this->dashboardDeltaPercent($nilaiIni, $nilaiLalu),
            ];

            if ($type === 'part') {
                $row['kode'] = $cur['kode'] ?? $prev['kode'] ?? '-';
                $row['nama'] = $cur['nama'] ?? $prev['nama'] ?? '-';
            } else {
                $row['nama'] = $cur['nama'] ?? $prev['nama'] ?? '-';
            }

            return $row;
        })
            ->sortByDesc('nilai_ini')
            ->values()
            ->all();

        $columns = $type === 'part'
            ? [
                ['key' => 'kode', 'label' => 'Kode'],
                ['key' => 'nama', 'label' => 'Nama Produk'],
                ['key' => 'qty_ini', 'label' => 'Qty ('.$currentLabel.')', 'align' => 'right'],
                ['key' => 'nilai_ini', 'label' => $currentLabel, 'align' => 'right', 'format' => 'rupiah'],
                ['key' => 'qty_lalu', 'label' => 'Qty ('.$previousLabel.')', 'align' => 'right'],
                ['key' => 'nilai_lalu', 'label' => $previousLabel, 'align' => 'right', 'format' => 'rupiah'],
                ['key' => 'delta_pct', 'label' => 'Δ%', 'align' => 'right', 'format' => 'percent'],
            ]
            : [
                ['key' => 'nama', 'label' => 'Nama Jasa'],
                ['key' => 'qty_ini', 'label' => 'Qty ('.$currentLabel.')', 'align' => 'right'],
                ['key' => 'nilai_ini', 'label' => $currentLabel, 'align' => 'right', 'format' => 'rupiah'],
                ['key' => 'qty_lalu', 'label' => 'Qty ('.$previousLabel.')', 'align' => 'right'],
                ['key' => 'nilai_lalu', 'label' => $previousLabel, 'align' => 'right', 'format' => 'rupiah'],
                ['key' => 'delta_pct', 'label' => 'Δ%', 'align' => 'right', 'format' => 'percent'],
            ];

        return [
            'title' => $current['title'].' — Perbandingan',
            'columns' => $columns,
            'rows' => $rows,
            'total' => (float) ($current['total'] ?? 0),
            'total_label' => $current['total_label'],
        ];
    }

    /**
     * @return list<int>
     */
    /**
     * @return array{
     *     dashboardSpeedshopOptions: \Illuminate\Support\Collection<int, \App\Models\Speedshop>,
     *     selectedDashboardSpeedshopId: ?int,
     *     selectedDashboardSpeedshopName: ?string
     * }
     */
    private function resolveDashboardSpeedshopFilterContext(Request $request): array
    {
        $user = auth()->user();
        $dashboardSpeedshopOptions = collect();
        $selectedDashboardSpeedshopId = null;
        $selectedDashboardSpeedshopName = null;

        if (! $user) {
            return compact('dashboardSpeedshopOptions', 'selectedDashboardSpeedshopId', 'selectedDashboardSpeedshopName');
        }

        $allowedIds = $user->activeSpeedshopIds();
        if (count($allowedIds) <= 1) {
            return compact('dashboardSpeedshopOptions', 'selectedDashboardSpeedshopId', 'selectedDashboardSpeedshopName');
        }

        if ($user->isOwner()) {
            $dashboardSpeedshopOptions = Speedshop::query()->orderBy('nama')->get(['id', 'nama']);
        } elseif ($user->canSelectSpeedshopScope()) {
            $dashboardSpeedshopOptions = Speedshop::query()
                ->whereIn('id', $allowedIds)
                ->orderBy('nama')
                ->get(['id', 'nama']);
        } else {
            return compact('dashboardSpeedshopOptions', 'selectedDashboardSpeedshopId', 'selectedDashboardSpeedshopName');
        }

        if ($user->speedshopScopeId() !== null) {
            $scopeId = (int) $user->speedshopScopeId();
            $match = $dashboardSpeedshopOptions->firstWhere('id', $scopeId);
            if ($match) {
                $selectedDashboardSpeedshopId = $scopeId;
                $selectedDashboardSpeedshopName = $match->nama;
            }
        }

        return compact('dashboardSpeedshopOptions', 'selectedDashboardSpeedshopId', 'selectedDashboardSpeedshopName');
    }

    private function resolveDashboardSpeedshopIds(Request $request): array
    {
        $user = auth()->user();
        if (! $user) {
            return [];
        }

        $allowedIds = $user->activeSpeedshopIds();

        if ($user->isOwner()) {
            return $allowedIds;
        }

        if ($user->isModifikasiOriHeadWarehouse() || $user->isModifikasiOriManager()) {
            $scopeId = $user->speedshopScopeId();
            if ($scopeId !== null) {
                return [$scopeId];
            }

            return $allowedIds;
        }

        return $user->reportSpeedshopFilterIds();
    }

    private function dashboardCompletedOrdersBaseQuery(Request $request, \Carbon\Carbon $dari, \Carbon\Carbon $sampai)
    {
        $user = auth()->user();
        $speedshopIds = $this->resolveDashboardSpeedshopIds($request);
        $warehouseId = $user?->activeWarehouseId();

        return ServiceOrder::query()
            ->where('status', 'selesai')
            ->whereTransaksiDateBetween($dari->toDateString(), $sampai->toDateString())
            ->when(
                Schema::hasColumn('service_orders', 'speedshop_id') && $speedshopIds !== [],
                function ($q) use ($speedshopIds) {
                    if (count($speedshopIds) === 1) {
                        $q->where('speedshop_id', (int) $speedshopIds[0]);
                    } else {
                        $q->whereIn('speedshop_id', $speedshopIds);
                    }
                },
                fn ($q) => $q->when($warehouseId, fn ($q2) => $q2->where('warehouse_id', $warehouseId)),
            );
    }

    private function dashboardCompletedOrdersForSpeedshop(Request $request, \Carbon\Carbon $dari, \Carbon\Carbon $sampai, int $speedshopId)
    {
        $user = auth()->user();
        $allowedIds = $user?->activeSpeedshopIds() ?? [];
        if (! in_array($speedshopId, $allowedIds, true)) {
            return ServiceOrder::query()->whereRaw('1 = 0');
        }

        $warehouseId = $user?->activeWarehouseId();

        return ServiceOrder::query()
            ->where('status', 'selesai')
            ->whereTransaksiDateBetween($dari->toDateString(), $sampai->toDateString())
            ->when(
                Schema::hasColumn('service_orders', 'speedshop_id'),
                fn ($q) => $q->where('speedshop_id', $speedshopId),
                fn ($q) => $q->when($warehouseId, fn ($q2) => $q2->where('warehouse_id', $warehouseId)),
            );
    }

    /**
     * @param  list<int>  $filterSpeedshopIds
     * @return array{
     *     showDashboardCabangPendapatan: bool,
     *     dashboardCabangPendapatan: list<array{key: string, label: string, amount: float, visible: bool}>,
     *     totalPendapatanCabang: float
     * }
     */
    private function collectDashboardCabangPendapatan(Request $request, \Carbon\Carbon $dari, \Carbon\Carbon $sampai, array $filterSpeedshopIds): array
    {
        $user = auth()->user();
        $managedIds = $user?->activeSpeedshopIds() ?? [];

        if (count($managedIds) <= 1) {
            return [
                'showDashboardCabangPendapatan' => false,
                'dashboardCabangPendapatan' => [],
                'totalPendapatanCabang' => 0.0,
            ];
        }

        $speedshops = Speedshop::query()
            ->whereIn('id', $managedIds)
            ->orderBy('nama')
            ->get(['id', 'nama']);

        $showAllCabang = count($filterSpeedshopIds) !== 1;
        $activeScopeId = count($filterSpeedshopIds) === 1 ? (int) $filterSpeedshopIds[0] : null;
        $omsetBySpeedshop = $this->dashboardOmsetBySpeedshopIds(
            $request,
            $dari,
            $sampai,
            $speedshops->pluck('id')->map(fn ($id) => (int) $id)->all(),
        );

        $cards = [];
        $totalPendapatanCabang = 0.0;

        foreach ($speedshops as $speedshop) {
            $speedshopId = (int) $speedshop->id;
            $visible = $showAllCabang
                || ($activeScopeId !== null && $speedshopId === $activeScopeId);

            $amount = 0.0;
            if ($visible) {
                $amount = (float) ($omsetBySpeedshop[$speedshopId] ?? 0);
                $totalPendapatanCabang += $amount;
            }

            $key = $this->resolveDashboardCabangKey($speedshop);
            $cards[] = [
                'key' => $key,
                'name' => $this->resolveDashboardCabangDisplayName($speedshop, $key),
                'label' => 'Pendapatan '.$speedshop->nama,
                'amount' => $amount,
                'visible' => $visible,
            ];
        }

        usort($cards, fn (array $a, array $b) => $b['amount'] <=> $a['amount'] ?: strcmp($a['key'], $b['key']));

        if (! $showAllCabang && $activeScopeId !== null && $totalPendapatanCabang === 0.0) {
            $totalPendapatanCabang = (float) ($omsetBySpeedshop[$activeScopeId] ?? 0);
        }

        return [
            'showDashboardCabangPendapatan' => true,
            'dashboardCabangPendapatan' => $cards,
            'totalPendapatanCabang' => $totalPendapatanCabang,
        ];
    }

    private function resolveDashboardCabangKey(Speedshop $speedshop): string
    {
        $nama = mb_strtolower((string) ($speedshop->nama ?? ''));
        foreach (self::DASHBOARD_CABANG_CITY_ORDER as $city) {
            if (str_contains($nama, $city)) {
                return $city;
            }
        }

        $speedshop->loadMissing('kota');
        $kota = mb_strtolower((string) ($speedshop->kota?->nama ?? ''));
        foreach (self::DASHBOARD_CABANG_CITY_ORDER as $city) {
            if (str_contains($kota, $city)) {
                return $city;
            }
        }

        return 'outlet-'.$speedshop->id;
    }

    private function resolveDashboardCabangDisplayName(Speedshop $speedshop, string $key): string
    {
        if (! str_starts_with($key, 'outlet-')) {
            return ucfirst($key);
        }

        return (string) ($speedshop->nama ?? 'Outlet');
    }

    /**
     * @param  list<int>  $filterSpeedshopIds
     * @return array{
     *     showDashboardSpeedshopSummary: bool,
     *     dashboardSpeedshopSummary: array{nama: string}|null
     * }
     */
    private function collectDashboardSpeedshopSummary(
        Request $request,
        \Carbon\Carbon $dari,
        \Carbon\Carbon $sampai,
        array $filterSpeedshopIds,
    ): array {
        $user = auth()->user();
        $managedIds = $user?->activeSpeedshopIds() ?? [];

        if (count($managedIds) > 1) {
            return [
                'showDashboardSpeedshopSummary' => false,
                'dashboardSpeedshopSummary' => null,
            ];
        }

        $speedshopId = count($filterSpeedshopIds) === 1
            ? (int) $filterSpeedshopIds[0]
            : (int) ($managedIds[0] ?? 0);

        if ($speedshopId <= 0) {
            return [
                'showDashboardSpeedshopSummary' => false,
                'dashboardSpeedshopSummary' => null,
            ];
        }

        $speedshop = Speedshop::query()->find($speedshopId, ['id', 'nama']);
        if (! $speedshop) {
            return [
                'showDashboardSpeedshopSummary' => false,
                'dashboardSpeedshopSummary' => null,
            ];
        }

        return [
            'showDashboardSpeedshopSummary' => true,
            'dashboardSpeedshopSummary' => [
                'nama' => $speedshop->nama,
            ],
        ];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $orderIdsQuery
     * @return array{title: string, columns: list<array{key: string, label: string, align?: string}>, rows: list<array<string, mixed>>, total: float, total_label: string}
     */
    private function buildOmsetDetailPart($orderIdsQuery): array
    {
        $rows = DB::table('service_order_products')
            ->join('products', 'products.id', '=', 'service_order_products.product_id')
            ->whereIn('service_order_products.service_order_id', $orderIdsQuery)
            ->selectRaw(
                'products.kode_produk, products.nama_produk, '
                .'SUM(service_order_products.qty) as total_qty, '
                .'SUM((service_order_products.qty * service_order_products.harga) - COALESCE(service_order_products.diskon, 0)) as total_nilai',
            )
            ->groupBy('products.id', 'products.kode_produk', 'products.nama_produk')
            ->orderByDesc('total_nilai')
            ->orderByDesc('total_qty')
            ->get();

        $mapped = $rows->map(fn ($row) => [
            'kode' => ($row->kode_produk !== null && $row->kode_produk !== '') ? (string) $row->kode_produk : '-',
            'nama' => (string) $row->nama_produk,
            'qty' => (int) $row->total_qty,
            'nilai' => (float) $row->total_nilai,
        ])->values()->all();

        return [
            'title' => 'Rincian Penjualan Part',
            'columns' => [
                ['key' => 'kode', 'label' => 'Kode'],
                ['key' => 'nama', 'label' => 'Nama Produk'],
                ['key' => 'qty', 'label' => 'Qty', 'align' => 'right'],
                ['key' => 'nilai', 'label' => 'Nilai (Rp)', 'align' => 'right', 'format' => 'rupiah'],
            ],
            'rows' => $mapped,
            'total' => (float) collect($mapped)->sum('nilai'),
            'total_label' => 'Total Penjualan Part',
        ];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $orderIdsQuery
     * @return array{title: string, columns: list<array{key: string, label: string, align?: string}>, rows: list<array<string, mixed>>, total: float, total_label: string}
     */
    private function buildOmsetDetailJasa($orderIdsQuery): array
    {
        $qtyExpr = Schema::hasColumn('service_order_jasa', 'qty')
            ? 'SUM(COALESCE(service_order_jasa.qty, 1))'
            : 'COUNT(*)';
        $nilaiExpr = Schema::hasColumn('service_order_jasa', 'qty')
            ? 'SUM((COALESCE(service_order_jasa.qty, 1) * service_order_jasa.biaya) - COALESCE(service_order_jasa.diskon, 0))'
            : 'SUM(service_order_jasa.biaya - COALESCE(service_order_jasa.diskon, 0))';

        $rows = DB::table('service_order_jasa')
            ->join('jasa_servis', 'jasa_servis.id', '=', 'service_order_jasa.jasa_servis_id')
            ->whereIn('service_order_jasa.service_order_id', $orderIdsQuery)
            ->selectRaw(
                "jasa_servis.nama as nama_jasa, {$qtyExpr} as total_qty, {$nilaiExpr} as total_nilai",
            )
            ->groupBy('jasa_servis.id', 'jasa_servis.nama')
            ->orderByDesc('total_nilai')
            ->orderByDesc('total_qty')
            ->get();

        $mapped = $rows->map(fn ($row) => [
            'nama' => (string) $row->nama_jasa,
            'qty' => (int) $row->total_qty,
            'nilai' => (float) $row->total_nilai,
        ])->values()->all();

        $lineTotal = (float) collect($mapped)->sum('nilai');
        $invoiceTotal = (float) (DB::table('invoices')->whereIn('service_order_id', $orderIdsQuery)->sum('total_jasa') ?? 0);
        $gap = round($invoiceTotal - $lineTotal);
        if (abs($gap) > 0) {
            $mapped[] = [
                'nama' => 'Jasa tanpa rincian baris (nilai di invoice)',
                'qty' => 0,
                'nilai' => $gap,
            ];
        }

        return [
            'title' => 'Rincian Pendapatan Jasa',
            'columns' => [
                ['key' => 'nama', 'label' => 'Nama Jasa'],
                ['key' => 'qty', 'label' => 'Qty', 'align' => 'right'],
                ['key' => 'nilai', 'label' => 'Nilai (Rp)', 'align' => 'right', 'format' => 'rupiah'],
            ],
            'rows' => $mapped,
            'total' => $invoiceTotal,
            'total_label' => 'Total Pendapatan Jasa',
        ];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $baseQuery
     * @return array{title: string, columns: list<array{key: string, label: string, align?: string}>, rows: list<array<string, mixed>>, total: float, total_label: string}
     */
    private function buildOmsetDetailOmset($baseQuery): array
    {
        $orders = (clone $baseQuery)
            ->with(['invoice', 'pelanggan', 'products', 'jasaServis'])
            ->orderByDesc(DB::raw('COALESCE(tanggal_selesai, tanggal_masuk)'))
            ->orderByDesc('id')
            ->get();

        $mapped = $orders->map(function (ServiceOrder $so) {
            $tgl = optional($so->tanggal_selesai ?? $so->invoice?->tanggal ?? $so->tanggal_masuk);
            $breakdown = $so->invoiceOmsetBreakdown();

            return [
                'invoice' => $so->invoice?->kode_invoice ?? ('WO-'.$so->kode_servis),
                'tanggal' => $tgl ? $tgl->format('d/m/Y') : '-',
                'customer' => $so->pelanggan?->nama ?? '-',
                'part' => (float) ($so->invoice?->total_sparepart ?? $so->total_sparepart),
                'jasa' => (float) ($so->invoice?->total_jasa ?? $so->total_jasa),
                'omset' => (float) $breakdown['pendapatan_omset'],
            ];
        })->values()->all();

        return [
            'title' => 'Rincian Pendapatan Omset',
            'columns' => [
                ['key' => 'invoice', 'label' => 'No. Invoice'],
                ['key' => 'tanggal', 'label' => 'Tanggal'],
                ['key' => 'customer', 'label' => 'Customer'],
                ['key' => 'part', 'label' => 'Part (Rp)', 'align' => 'right', 'format' => 'rupiah'],
                ['key' => 'jasa', 'label' => 'Jasa (Rp)', 'align' => 'right', 'format' => 'rupiah'],
                ['key' => 'omset', 'label' => 'Omset (Rp)', 'align' => 'right', 'format' => 'rupiah'],
            ],
            'rows' => $mapped,
            'total' => (float) collect($mapped)->sum('omset'),
            'total_label' => 'Total Pendapatan Omset',
        ];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $baseQuery
     * @return array{title: string, columns: list<array{key: string, label: string, align?: string}>, rows: list<array<string, mixed>>, total: float, total_label: string}
     */
    private function buildOmsetDetailCharge($baseQuery): array
    {
        $orders = (clone $baseQuery)
            ->with(['invoice', 'pelanggan'])
            ->orderByDesc(DB::raw('COALESCE(tanggal_selesai, tanggal_masuk)'))
            ->orderByDesc('id')
            ->get();

        $mapped = $orders
            ->map(function (ServiceOrder $so) {
                $tgl = optional($so->tanggal_selesai ?? $so->invoice?->tanggal ?? $so->tanggal_masuk);
                $breakdown = $so->invoiceOmsetBreakdown();
                $charge = (float) $breakdown['charge'];

                if ($charge <= 0) {
                    return null;
                }

                return [
                    'invoice' => $so->invoice?->kode_invoice ?? ('WO-'.$so->kode_servis),
                    'tanggal' => $tgl ? $tgl->format('d/m/Y') : '-',
                    'customer' => $so->pelanggan?->nama ?? '-',
                    'charge' => $charge,
                    'total_tagihan' => (float) $breakdown['total_tagihan'],
                ];
            })
            ->filter()
            ->values()
            ->all();

        return [
            'title' => 'Rincian Charge Invoice',
            'columns' => [
                ['key' => 'invoice', 'label' => 'No. Invoice'],
                ['key' => 'tanggal', 'label' => 'Tanggal'],
                ['key' => 'customer', 'label' => 'Customer'],
                ['key' => 'charge', 'label' => 'Charge (Rp)', 'align' => 'right', 'format' => 'rupiah'],
                ['key' => 'total_tagihan', 'label' => 'Total Tagihan (Rp)', 'align' => 'right', 'format' => 'rupiah'],
            ],
            'rows' => $mapped,
            'total' => (float) collect($mapped)->sum('charge'),
            'total_label' => 'Total Charge Invoice',
        ];
    }

    /**
     * @return list<array{kode: string, nama: string, qty: int, nilai: float}>
     */
    private function collectDashboardTopProducts(
        Request $request,
        \Carbon\Carbon $dari,
        \Carbon\Carbon $sampai,
        int $limit = 3,
    ): array {
        $orderIdsQuery = $this->dashboardCompletedOrdersBaseQuery($request, $dari, $sampai)->select('service_orders.id');

        $rows = DB::table('service_order_products')
            ->join('service_orders', 'service_orders.id', '=', 'service_order_products.service_order_id')
            ->join('products', 'products.id', '=', 'service_order_products.product_id')
            ->whereIn('service_order_products.service_order_id', $orderIdsQuery)
            ->selectRaw(
                'products.id, products.kode_produk, products.nama_produk, '
                .'SUM(service_order_products.qty) as total_qty, '
                .'SUM((service_order_products.qty * service_order_products.harga) - COALESCE(service_order_products.diskon, 0)) as total_nilai',
            )
            ->groupBy('products.id', 'products.kode_produk', 'products.nama_produk')
            ->orderByDesc('total_qty')
            ->orderByDesc('total_nilai')
            ->limit($limit)
            ->get();

        return $rows->map(fn ($row) => [
            'kode' => ($row->kode_produk !== null && $row->kode_produk !== '') ? (string) $row->kode_produk : '-',
            'nama' => (string) $row->nama_produk,
            'qty' => (int) $row->total_qty,
            'nilai' => (float) $row->total_nilai,
        ])->all();
    }

    /**
     * @return array<string, array{key: string, title: string, subtitle: string, items: list<array<string, mixed>>, empty: string}>
     */
    private function collectDashboardManagerTopLists(
        Request $request,
        \Carbon\Carbon $dari,
        \Carbon\Carbon $sampai,
        int $limit = 10,
        ?\Carbon\Carbon $compareDari = null,
        ?\Carbon\Carbon $compareSampai = null,
        ?string $comparePeriodLabel = null,
        ?string $currentPeriodLabel = null,
    ): array {
        $compareEnabled = $compareDari !== null && $compareSampai !== null && $comparePeriodLabel !== null;
        $periodLabel = $currentPeriodLabel ?? match ($request->input('mode', 'bulanan')) {
            'harian' => 'Hari ini',
            'bulanan' => 'Bulan ini',
            'tahunan' => 'Tahun ini',
            default => 'Periode terpilih',
        };
        $subtitleSuffix = $compareEnabled
            ? $periodLabel.' vs '.$comparePeriodLabel
            : $periodLabel;

        $buildList = function (
            string $key,
            string $title,
            string $typeLabel,
            string $metric,
            string $empty,
            array $currentItems,
            ?array $compareItems,
        ) use ($limit, $subtitleSuffix, $compareEnabled, $comparePeriodLabel): array {
            return [
                'key' => $key,
                'title' => $title,
                'subtitle' => 'Top '.$limit.' '.$typeLabel.' · '.$subtitleSuffix,
                'metric' => $metric,
                'compare_enabled' => $compareEnabled,
                'compare_period_label' => $comparePeriodLabel,
                'items' => $compareEnabled && $compareItems !== null
                    ? $this->mergeTopListCompareItems($currentItems, $compareItems)
                    : $currentItems,
                'empty' => $empty,
            ];
        };

        $catalogCards = [];
        foreach (ProductMasterCatalog::kinds() as $kind) {
            $catalogCards[$kind] = ProductMasterCatalog::pickerBadge($kind);
        }
        $lists = [];
        foreach ($catalogCards as $catalog => $catalogLabel) {
            foreach ([
                ['suffix' => 'part', 'oliOnly' => false, 'type' => 'part', 'title' => 'Part Terlaris '.$catalogLabel],
                ['suffix' => 'oli', 'oliOnly' => true, 'type' => 'oli', 'title' => 'Oli Terlaris '.$catalogLabel],
            ] as $spec) {
                $key = $spec['suffix'].'_'.$catalog;
                $current = $this->collectDashboardTopSoldProducts(
                    $request,
                    $dari,
                    $sampai,
                    $spec['oliOnly'],
                    $limit,
                    $catalog,
                );
                $compare = $compareEnabled
                    ? $this->collectDashboardTopSoldProducts(
                        $request,
                        $compareDari,
                        $compareSampai,
                        $spec['oliOnly'],
                        null,
                        $catalog,
                    )
                    : null;
                $lists[$key] = $buildList(
                    $key,
                    $spec['title'],
                    $spec['type'],
                    'product',
                    'Belum ada penjualan '.$spec['type'].' '.$catalogLabel.' pada periode ini.',
                    $current,
                    $compare,
                );
            }
        }

        $jasaCurrent = $this->collectDashboardTopJasa($request, $dari, $sampai, $limit);
        $jasaCompare = $compareEnabled
            ? $this->collectDashboardTopJasa($request, $compareDari, $compareSampai, null)
            : null;

        $motorCurrent = $this->collectDashboardTopTipeMotor($request, $dari, $sampai, $limit);
        $motorCompare = $compareEnabled
            ? $this->collectDashboardTopTipeMotor($request, $compareDari, $compareSampai, null)
            : null;

        $sumberCurrent = $this->collectDashboardTopSumberKedatangan($request, $dari, $sampai, $limit);
        $sumberCompare = $compareEnabled
            ? $this->collectDashboardTopSumberKedatangan($request, $compareDari, $compareSampai, null)
            : null;

        return $lists + [
            'jasa' => $buildList(
                'jasa',
                'Jasa Terlaris',
                'jasa',
                'product',
                'Belum ada jasa terjual pada periode ini.',
                $jasaCurrent,
                $jasaCompare,
            ),
            'tipe_motor' => $buildList(
                'tipe_motor',
                'Tipe Motor',
                'kendaraan',
                'order',
                'Belum ada data kendaraan pada periode ini.',
                $motorCurrent,
                $motorCompare,
            ),
            'sumber_kedatangan' => $buildList(
                'sumber_kedatangan',
                'Sumber Kedatangan',
                'sumber',
                'order',
                'Belum ada data sumber kedatangan pada periode ini.',
                $sumberCurrent,
                $sumberCompare,
            ),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $currentItems
     * @param  list<array<string, mixed>>  $compareItems
     * @return list<array<string, mixed>>
     */
    private function mergeTopListCompareItems(array $currentItems, array $compareItems): array
    {
        $compareByKey = collect($compareItems)->keyBy('match_key');

        return collect($currentItems)
            ->map(function (array $item) use ($compareByKey) {
                $compare = $compareByKey->get($item['match_key'] ?? '');
                $compareQty = (int) ($compare['qty'] ?? 0);
                $compareNilai = (float) ($compare['nilai'] ?? 0);
                $item['compare_qty'] = $compareQty;
                $item['compare_nilai'] = $compareNilai;
                $item['compare_delta_pct'] = $this->dashboardDeltaPercent((float) ($item['qty'] ?? 0), (float) $compareQty);

                return $item;
            })
            ->all();
    }

    /**
     * Ranking mekanik dari WO selesai pada periode dashboard.
     *
     * @return array{
     *     all: list<array<string, mixed>>,
     *     by_cabang: list<array{cabang: string, rows: list<array<string, mixed>>}>
     * }
     */
    private function collectDashboardMekanikPerformance(
        Request $request,
        \Carbon\Carbon $dari,
        \Carbon\Carbon $sampai,
    ): array {
        $orderIdsQuery = $this->dashboardCompletedOrdersBaseQuery($request, $dari, $sampai)->select('service_orders.id');

        $woRows = DB::table('service_orders')
            ->whereIn('id', $orderIdsQuery)
            ->whereNotNull('mekanik_id')
            ->selectRaw('mekanik_id, COUNT(*) as wo_count')
            ->groupBy('mekanik_id')
            ->get()
            ->keyBy('mekanik_id');

        if ($woRows->isEmpty()) {
            return ['all' => [], 'by_cabang' => []];
        }

        $moneyRows = DB::table('invoices')
            ->join('service_orders', 'service_orders.id', '=', 'invoices.service_order_id')
            ->whereIn('invoices.service_order_id', $orderIdsQuery)
            ->whereNotNull('service_orders.mekanik_id')
            ->selectRaw('
                service_orders.mekanik_id,
                COALESCE(SUM(invoices.total_jasa), 0) as jasa,
                COALESCE(SUM(invoices.total_sparepart), 0) as sparepart
            ')
            ->groupBy('service_orders.mekanik_id')
            ->get()
            ->keyBy('mekanik_id');

        $mekaniks = Mekanik::query()
            ->with(['mekanikLevel', 'speedshop'])
            ->whereIn('id', $woRows->keys()->map(fn ($id) => (int) $id)->all())
            ->get()
            ->keyBy('id');

        $rows = [];
        foreach ($woRows as $mekanikId => $wo) {
            $mekanik = $mekaniks->get((int) $mekanikId);
            $speedshop = $mekanik?->speedshop;
            $cabangKey = $speedshop ? $this->resolveDashboardCabangKey($speedshop) : 'outlet-0';
            $money = $moneyRows->get($mekanikId);
            $jasa = (float) ($money->jasa ?? 0);
            $sparepart = (float) ($money->sparepart ?? 0);
            $rows[] = [
                'mekanik_id' => (int) $mekanikId,
                'nama' => (string) ($mekanik?->nama ?? '-'),
                'jabatan' => $mekanik?->levelName() ?: 'Mekanik',
                'cabang' => $speedshop
                    ? $this->resolveDashboardCabangDisplayName($speedshop, $cabangKey)
                    : '-',
                'cabang_sort' => $cabangKey,
                'wo_count' => (int) $wo->wo_count,
                'jasa' => $jasa,
                'sparepart' => $sparepart,
                'pencapaian' => $jasa + $sparepart,
            ];
        }

        $mekanikTargets = SpeedshopMekanikTarget::query()
            ->where('periode_bulan', $dari->format('Y-m'))
            ->whereIn('mekanik_id', array_column($rows, 'mekanik_id'))
            ->pluck('target_bulanan', 'mekanik_id');
        foreach ($rows as $i => $row) {
            $target = SpeedshopTargetMath::mekanikTarget(
                isset($mekanikTargets[$row['mekanik_id']]) ? (int) $mekanikTargets[$row['mekanik_id']] : null
            );
            $rows[$i]['target'] = $target;
            $rows[$i]['percent'] = SpeedshopTargetMath::percent((float) $row['pencapaian'], $target);
        }

        $rankRows = function (array $list): array {
            usort($list, fn (array $a, array $b) => $b['pencapaian'] <=> $a['pencapaian']);
            foreach ($list as $i => $row) {
                $list[$i]['rank'] = $i + 1;
            }

            return $list;
        };

        $all = $rankRows($rows);
        $cityOrder = array_flip(self::DASHBOARD_CABANG_CITY_ORDER);
        $byCabang = collect($rows)
            ->groupBy('cabang')
            ->map(fn ($group, $cabang) => [
                'cabang' => (string) $cabang,
                'sort' => $cityOrder[$group->first()['cabang_sort'] ?? ''] ?? 99,
                'rows' => $rankRows($group->values()->all()),
            ])
            ->sortBy(fn (array $group) => sprintf('%02d-%s', $group['sort'], $group['cabang']))
            ->values()
            ->all();

        return ['all' => $all, 'by_cabang' => $byCabang];
    }

    /**
     * @return list<array{match_key: string, kode: string, nama: string, qty: int, nilai: float}>
     */
    private function collectDashboardTopSoldProducts(
        Request $request,
        \Carbon\Carbon $dari,
        \Carbon\Carbon $sampai,
        ?bool $oliOnly,
        ?int $limit = 10,
        ?string $catalog = null,
    ): array {
        $orderIdsQuery = $this->dashboardCompletedOrdersBaseQuery($request, $dari, $sampai)->select('service_orders.id');

        $query = DB::table('service_order_products')
            ->join('service_orders', 'service_orders.id', '=', 'service_order_products.service_order_id')
            ->join('products', 'products.id', '=', 'service_order_products.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->whereIn('service_order_products.service_order_id', $orderIdsQuery);

        if ($catalog !== null) {
            $this->applyProductMasterCatalogScope($query, $catalog);
        }

        if ($oliOnly === true) {
            $query->whereRaw("LOWER(TRIM(categories.nama)) = 'oli'");
        } elseif ($oliOnly === false) {
            $query->where(function ($q) {
                $q->whereNull('categories.nama')
                    ->orWhereRaw("LOWER(TRIM(categories.nama)) != 'oli'");
            });
        }

        $rows = $query
            ->selectRaw(
                'products.id, products.kode_produk, products.nama_produk, '
                .'SUM(service_order_products.qty) as total_qty, '
                .'SUM((service_order_products.qty * service_order_products.harga) - COALESCE(service_order_products.diskon, 0)) as total_nilai',
            )
            ->groupBy('products.id', 'products.kode_produk', 'products.nama_produk')
            ->orderByDesc('total_qty')
            ->orderByDesc('total_nilai')
            ->when($limit !== null, fn ($q) => $q->limit($limit))
            ->get();

        return $rows->map(fn ($row) => [
            'match_key' => 'product:'.(int) $row->id,
            'kode' => ($row->kode_produk !== null && $row->kode_produk !== '') ? (string) $row->kode_produk : '-',
            'nama' => (string) $row->nama_produk,
            'qty' => (int) $row->total_qty,
            'nilai' => (float) $row->total_nilai,
        ])->all();
    }

    /**
     * Scope query produk ke salah satu dari 3 master catalog.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     */
    private function applyProductMasterCatalogScope($query, string $catalog): void
    {
        $jenis = ProductMasterCatalog::jenisProdukFor($catalog);
        if ($jenis === Product::JENIS_ORI) {
            $query->where(function ($productQuery) {
                $productQuery
                    ->whereNull('products.jenis_produk')
                    ->orWhere('products.jenis_produk', Product::JENIS_ORI);
            });
        } else {
            $query->where('products.jenis_produk', $jenis);
        }

        if ($catalog === ProductMasterCatalog::SPEEDSHOP_SUPPLIER) {
            $query->where('products.speedshop_scope_id', '>', 0);

            return;
        }

        if (ProductMasterCatalog::isWarehouseSharedKind($catalog)) {
            $query->where(function ($scopeQuery) {
                $scopeQuery
                    ->whereNull('products.speedshop_scope_id')
                    ->orWhere('products.speedshop_scope_id', 0);
            });
        }
    }

    /**
     * @return list<array{kode: string, nama: string, qty: int, nilai: float}>
     */
    private function collectDashboardTopJasa(
        Request $request,
        \Carbon\Carbon $dari,
        \Carbon\Carbon $sampai,
        ?int $limit = 10,
    ): array {
        $orderIdsQuery = $this->dashboardCompletedOrdersBaseQuery($request, $dari, $sampai)->select('service_orders.id');
        $qtyExpr = Schema::hasColumn('service_order_jasa', 'qty')
            ? 'COALESCE(service_order_jasa.qty, 1)'
            : '1';
        $nilaiExpr = Schema::hasColumn('service_order_jasa', 'qty')
            ? "SUM(({$qtyExpr} * service_order_jasa.biaya) - COALESCE(service_order_jasa.diskon, 0))"
            : 'SUM(service_order_jasa.biaya - COALESCE(service_order_jasa.diskon, 0))';

        $rows = DB::table('service_order_jasa')
            ->join('service_orders', 'service_orders.id', '=', 'service_order_jasa.service_order_id')
            ->join('jasa_servis', 'jasa_servis.id', '=', 'service_order_jasa.jasa_servis_id')
            ->whereIn('service_order_jasa.service_order_id', $orderIdsQuery)
            ->selectRaw(
                'jasa_servis.id, jasa_servis.nama, '
                ."SUM({$qtyExpr}) as total_qty, "
                ."{$nilaiExpr} as total_nilai",
            )
            ->groupBy('jasa_servis.id', 'jasa_servis.nama')
            ->orderByDesc('total_qty')
            ->orderByDesc('total_nilai')
            ->when($limit !== null, fn ($q) => $q->limit($limit))
            ->get();

        return $rows->map(fn ($row) => [
            'match_key' => 'jasa:'.(int) $row->id,
            'kode' => '-',
            'nama' => (string) $row->nama,
            'qty' => (int) $row->total_qty,
            'nilai' => (float) $row->total_nilai,
        ])->all();
    }

    /**
     * @return list<array{kode: string, nama: string, qty: int, nilai: float}>
     */
    private function collectDashboardTopTipeMotor(
        Request $request,
        \Carbon\Carbon $dari,
        \Carbon\Carbon $sampai,
        ?int $limit = 10,
    ): array {
        $orderIdsQuery = $this->dashboardCompletedOrdersBaseQuery($request, $dari, $sampai)->select('service_orders.id');

        $rows = DB::table('service_orders')
            ->join('kendaraans', 'kendaraans.id', '=', 'service_orders.kendaraan_id')
            ->whereIn('service_orders.id', $orderIdsQuery)
            ->selectRaw(
                'kendaraans.merk as merk, kendaraans.tipe as tipe, '
                ."TRIM(CONCAT(COALESCE(kendaraans.merk, ''), ' ', COALESCE(kendaraans.tipe, ''))) as label, "
                .'COUNT(*) as total_qty',
            )
            ->groupBy('kendaraans.merk', 'kendaraans.tipe')
            ->orderByDesc('total_qty')
            ->when($limit !== null, fn ($q) => $q->limit($limit))
            ->get();

        return $rows
            ->map(function ($row) {
                $label = trim((string) ($row->label ?? ''));
                if ($label === '') {
                    $label = 'Tidak diketahui';
                }
                $merk = strtolower(trim((string) ($row->merk ?? '')));
                $tipe = strtolower(trim((string) ($row->tipe ?? '')));

                return [
                    'match_key' => 'motor:'.$merk.'|'.$tipe,
                    'kode' => '-',
                    'nama' => $label,
                    'qty' => (int) $row->total_qty,
                    'nilai' => 0.0,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array{kode: string, nama: string, qty: int, nilai: float}>
     */
    private function collectDashboardTopSumberKedatangan(
        Request $request,
        \Carbon\Carbon $dari,
        \Carbon\Carbon $sampai,
        ?int $limit = 10,
    ): array {
        $orderIdsQuery = $this->dashboardCompletedOrdersBaseQuery($request, $dari, $sampai)->select('service_orders.id');

        $rows = DB::table('service_orders')
            ->whereIn('service_orders.id', $orderIdsQuery)
            ->whereNotNull('service_orders.sumber_kedatangan')
            ->where('service_orders.sumber_kedatangan', '!=', '')
            ->selectRaw('service_orders.sumber_kedatangan as sumber_key, COUNT(*) as total_qty')
            ->groupBy('service_orders.sumber_kedatangan')
            ->orderByDesc('total_qty')
            ->when($limit !== null, fn ($q) => $q->limit($limit))
            ->get();

        return $rows->map(function ($row) {
            $key = (string) ($row->sumber_key ?? '');
            $label = ServiceOrder::SUMBER_KEDATANGAN[$key] ?? $key;

            return [
                'match_key' => 'sumber:'.$key,
                'kode' => '-',
                'nama' => $label !== '' ? $label : 'Tidak diketahui',
                'qty' => (int) $row->total_qty,
                'nilai' => 0.0,
            ];
        })->all();
    }

    /**
     * @return array<int, array<int, int|string|float>>
     */
    private function dashboardExportRows(Request $request, \Carbon\Carbon $dari, \Carbon\Carbon $sampai): array
    {
        $orders = $this->dashboardCompletedOrdersBaseQuery($request, $dari, $sampai)
            ->with([
                'invoice',
                'pelanggan',
                'kendaraan',
                'mekanik',
                'jasaServis',
                'products',
            ])
            ->orderByDesc(DB::raw('COALESCE(tanggal_selesai, tanggal_masuk)'))
            ->orderByDesc('id')
            ->get();

        return $orders->values()->map(fn (ServiceOrder $so, int $idx) => DashboardTransaksiExport::rowFromOrder($so, $idx + 1))->all();
    }

    /**
     * @param  list<int>  $filterSpeedshopIds
     * @return list<array<string, mixed>>
     */
    private function collectDashboardTargetAchievement(
        Request $request,
        \Carbon\Carbon $dari,
        \Carbon\Carbon $sampai,
        array $filterSpeedshopIds,
    ): array {
        if ($filterSpeedshopIds === []) {
            return [];
        }

        $speedshops = Speedshop::query()
            ->whereIn('id', $filterSpeedshopIds)
            ->orderBy('nama')
            ->get(['id', 'nama']);
        $current = $this->dashboardPartJasaBySpeedshopIds($request, $dari, $sampai, $filterSpeedshopIds);
        $previous = $this->dashboardPartJasaBySpeedshopIds(
            $request,
            $dari->copy()->subMonth(),
            $sampai->copy()->subMonth(),
            $filterSpeedshopIds,
        );
        $targets = SpeedshopBranchTarget::query()
            ->where('periode_bulan', $dari->format('Y-m'))
            ->whereIn('speedshop_id', $filterSpeedshopIds)
            ->pluck('target_bulanan', 'speedshop_id');
        $daysInMonth = $dari->daysInMonth;
        $cityOrder = array_flip(self::DASHBOARD_CABANG_CITY_ORDER);

        $rows = [];
        foreach ($speedshops as $speedshop) {
            $id = (int) $speedshop->id;
            $part = (float) ($current[$id]['sparepart'] ?? 0);
            $jasa = (float) ($current[$id]['jasa'] ?? 0);
            $total = $part + $jasa;
            $prev = (float) (($previous[$id]['sparepart'] ?? 0) + ($previous[$id]['jasa'] ?? 0));
            $target = SpeedshopTargetMath::cabangTarget(
                isset($targets[$id]) ? (int) $targets[$id] : null
            );
            $split = SpeedshopTargetMath::split($target);
            $key = $this->resolveDashboardCabangKey($speedshop);
            $rows[] = [
                'nama' => $this->resolveDashboardCabangDisplayName($speedshop, $key),
                'sort' => $cityOrder[$key] ?? 99,
                'sparepart_actual' => $part,
                'sparepart_target' => $split['sparepart'],
                'sparepart_percent' => SpeedshopTargetMath::percent($part, $split['sparepart']),
                'jasa_actual' => $jasa,
                'jasa_target' => $split['jasa'],
                'jasa_percent' => SpeedshopTargetMath::percent($jasa, $split['jasa']),
                'total_revenue' => $total,
                'prev_revenue' => $prev,
                'growth' => $total - $prev,
                'target_bulanan' => $target,
                'target_harian' => SpeedshopTargetMath::daily($target, $daysInMonth),
                'percent' => SpeedshopTargetMath::percent($total, $target),
            ];
        }

        usort($rows, fn (array $a, array $b) => ($a['sort'] <=> $b['sort']) ?: strcmp((string) $a['nama'], (string) $b['nama']));

        return $rows;
    }

    /**
     * @param  list<int>  $speedshopIds
     * @return array<int, array{sparepart: float, jasa: float}>
     */
    private function dashboardPartJasaBySpeedshopIds(
        Request $request,
        \Carbon\Carbon $dari,
        \Carbon\Carbon $sampai,
        array $speedshopIds,
    ): array {
        $empty = [];
        foreach ($speedshopIds as $id) {
            $empty[(int) $id] = ['sparepart' => 0.0, 'jasa' => 0.0];
        }

        if ($speedshopIds === [] || ! Schema::hasColumn('service_orders', 'speedshop_id')) {
            return $empty;
        }

        $orderIdsQuery = $this->dashboardCompletedOrdersBaseQuery($request, $dari, $sampai)
            ->select('service_orders.id');

        $rows = DB::table('invoices')
            ->join('service_orders', 'service_orders.id', '=', 'invoices.service_order_id')
            ->whereIn('invoices.service_order_id', $orderIdsQuery)
            ->selectRaw('
                service_orders.speedshop_id,
                COALESCE(SUM(invoices.total_sparepart), 0) as sparepart,
                COALESCE(SUM(invoices.total_jasa), 0) as jasa
            ')
            ->groupBy('service_orders.speedshop_id')
            ->get()
            ->keyBy('speedshop_id');

        foreach ($empty as $id => $_) {
            $row = $rows->get($id);
            $empty[$id] = [
                'sparepart' => (float) ($row->sparepart ?? 0),
                'jasa' => (float) ($row->jasa ?? 0),
            ];
        }

        return $empty;
    }

    /**
     * @param  list<int>  $speedshopIds
     * @return array<int, float>
     */
    private function dashboardOmsetBySpeedshopIds(
        Request $request,
        \Carbon\Carbon $dari,
        \Carbon\Carbon $sampai,
        array $speedshopIds,
    ): array {
        if ($speedshopIds === [] || ! Schema::hasColumn('service_orders', 'speedshop_id')) {
            return [];
        }

        $hasChargePpn = Schema::hasColumn('invoices', 'charge_amount') && Schema::hasColumn('invoices', 'ppn_amount');
        $omsetExpr = $hasChargePpn
            ? 'SUM(COALESCE(invoices.grand_total, 0) - COALESCE(invoices.charge_amount, 0) - COALESCE(invoices.ppn_amount, 0))'
            : 'SUM(COALESCE(invoices.grand_total, 0))';
        $transaksiDate = ServiceOrder::transaksiDateSql('service_orders');
        $user = auth()->user();
        $warehouseId = $user?->activeWarehouseId();

        $rows = DB::table('invoices')
            ->join('service_orders', 'service_orders.id', '=', 'invoices.service_order_id')
            ->where('service_orders.status', 'selesai')
            ->whereRaw("{$transaksiDate} BETWEEN ? AND ?", [$dari->toDateString(), $sampai->toDateString()])
            ->whereIn('service_orders.speedshop_id', $speedshopIds)
            ->when($warehouseId, fn ($q) => $q->where('service_orders.warehouse_id', $warehouseId))
            ->selectRaw("service_orders.speedshop_id as speedshop_id, {$omsetExpr} as total")
            ->groupBy('service_orders.speedshop_id')
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row->speedshop_id] = (float) ($row->total ?? 0);
        }

        return $map;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, int|string>  $serviceOrderIds
     * @return array<string, float|int>
     */
    private function collectDashboardSummaryMetrics($serviceOrderIds): array
    {
        $ids = $serviceOrderIds->all();
        $unitEntry = count($ids);

        if ($ids === []) {
            return [
                'unitEntry' => 0,
                'jumlahItemPart' => 0,
                'jumlahItemJasa' => 0,
                'penjualanPart' => 0.0,
                'pendapatanJasa' => 0.0,
                'totalOmset' => 0.0,
                'pendapatanOmset' => 0.0,
                'totalCharge' => 0.0,
                'totalPpn' => 0.0,
                'pendapatanTunai' => 0.0,
                'pendapatanQris' => 0.0,
                'pendapatanTransfer' => 0.0,
                'pendapatanEdc' => 0.0,
            ];
        }

        // Qty item tetap dari baris WO; nilai uang dari snapshot invoice
        // (baris jasa bisa hilang setelah edit, invoice.total_* adalah yang dibayar).
        $jumlahItemPart = (int) (DB::table('service_order_products')->whereIn('service_order_id', $ids)->sum('qty') ?? 0);
        $jumlahItemJasaQuery = DB::table('service_order_jasa')->whereIn('service_order_id', $ids);
        $jumlahItemJasa = Schema::hasColumn('service_order_jasa', 'qty')
            ? (int) $jumlahItemJasaQuery->sum('qty')
            : (int) $jumlahItemJasaQuery->count();

        $invoiceAgg = DB::table('invoices')->whereIn('service_order_id', $ids);
        if (Schema::hasColumn('invoices', 'charge_amount') && Schema::hasColumn('invoices', 'ppn_amount')) {
            $row = $invoiceAgg->selectRaw(
                'COALESCE(SUM(grand_total), 0) as grand_total,
                COALESCE(SUM(charge_amount), 0) as charge_amount,
                COALESCE(SUM(ppn_amount), 0) as ppn_amount,
                COALESCE(SUM(total_jasa), 0) as total_jasa,
                COALESCE(SUM(total_sparepart), 0) as total_sparepart'
            )->first();
            $totalOmset = (float) ($row->grand_total ?? 0);
            $totalCharge = (float) ($row->charge_amount ?? 0);
            $totalPpn = (float) ($row->ppn_amount ?? 0);
            $penjualanPart = (float) ($row->total_sparepart ?? 0);
            $pendapatanJasa = (float) ($row->total_jasa ?? 0);
            $pendapatanOmset = max(0, $totalOmset - $totalCharge - $totalPpn);
        } else {
            $row = $invoiceAgg->selectRaw(
                'COALESCE(SUM(grand_total), 0) as grand_total,
                COALESCE(SUM(total_jasa), 0) as total_jasa,
                COALESCE(SUM(total_sparepart), 0) as total_sparepart'
            )->first();
            $totalOmset = (float) ($row->grand_total ?? 0);
            $totalCharge = 0.0;
            $totalPpn = 0.0;
            $penjualanPart = (float) ($row->total_sparepart ?? 0);
            $pendapatanJasa = (float) ($row->total_jasa ?? 0);
            $pendapatanOmset = $penjualanPart + $pendapatanJasa;
        }

        $pendapatanByMetode = SpeedshopPendapatanBreakdown::byMetodeFromServiceOrderIds($ids);

        return [
            'unitEntry' => $unitEntry,
            'jumlahItemPart' => $jumlahItemPart,
            'jumlahItemJasa' => $jumlahItemJasa,
            'penjualanPart' => $penjualanPart,
            'pendapatanJasa' => $pendapatanJasa,
            'totalOmset' => $totalOmset,
            'pendapatanOmset' => $pendapatanOmset,
            'totalCharge' => $totalCharge,
            'totalPpn' => $totalPpn,
            'pendapatanTunai' => (float) ($pendapatanByMetode['tunai'] ?? 0),
            'pendapatanQris' => (float) ($pendapatanByMetode['qris'] ?? 0),
            'pendapatanTransfer' => (float) ($pendapatanByMetode['transfer'] ?? 0),
            'pendapatanEdc' => (float) ($pendapatanByMetode['edc'] ?? 0),
        ];
    }

    /**
     * @return array{
     *   chartLabels: list<string>,
     *   chartPenjualanPart: list<float>,
     *   chartPenjualanJasa: list<float>,
     *   chartPendapatanOmset: list<float>,
     *   chartTotalTagihan: list<float>,
     *   chartPengeluaranHppSparepart: list<float>,
     *   chartPengeluaranHppOli: list<float>,
     *   chartPengeluaranOperasional: list<float>,
     *   chartPengeluaranNonOperasional: list<float>
     * }
     */
    private function buildDashboardChartSeries(
        Request $request,
        \Carbon\Carbon $dari,
        \Carbon\Carbon $sampai,
        string $mode,
        string $selectedDate,
        string $selectedMonth,
        int $selectedYear,
    ): array {
        $orderIdsQuery = $this->dashboardCompletedOrdersBaseQuery($request, $dari, $sampai)->select('service_orders.id');
        $driver = DB::connection()->getDriverName();
        $transaksiDate = ServiceOrder::transaksiDateSql('service_orders');
        $hasChargePpn = Schema::hasColumn('invoices', 'charge_amount') && Schema::hasColumn('invoices', 'ppn_amount');
        $omsetExpr = $hasChargePpn
            ? 'SUM(COALESCE(invoices.grand_total, 0) - COALESCE(invoices.charge_amount, 0) - COALESCE(invoices.ppn_amount, 0))'
            : 'SUM(COALESCE(invoices.grand_total, 0))';
        $partExpr = 'SUM(COALESCE(invoices.total_sparepart, 0))';
        $jasaExpr = 'SUM(COALESCE(invoices.total_jasa, 0))';

        $speedshopIds = $this->resolveDashboardSpeedshopIds($request);
        $warehouseId = auth()->user()?->activeWarehouseId();
        $speedshopId = count($speedshopIds) === 1 ? (int) $speedshopIds[0] : null;
        $expenseForRange = function (\Carbon\Carbon $start, \Carbon\Carbon $end) use ($speedshopId, $warehouseId, $speedshopIds): array {
            return SpeedshopLabaRugiFinancials::biayaBreakdown($start, $end, $speedshopId, $warehouseId, $speedshopIds);
        };

        if ($mode === 'tahunan') {
            $chartLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            $monthExpr = $driver === 'sqlite'
                ? "CAST(strftime('%m', {$transaksiDate}) AS INTEGER)"
                : "MONTH({$transaksiDate})";

            $invBase = DB::table('invoices')
                ->join('service_orders', 'service_orders.id', '=', 'invoices.service_order_id')
                ->whereIn('invoices.service_order_id', $orderIdsQuery);
            $partMap = $this->chartBucketTotals(clone $invBase, $monthExpr, $partExpr);
            $jasaMap = $this->chartBucketTotals(clone $invBase, $monthExpr, $jasaExpr);
            $omsetMap = $this->chartBucketTotals(clone $invBase, $monthExpr, $omsetExpr);
            $tagihanMap = $this->chartBucketTotals(clone $invBase, $monthExpr, 'SUM(COALESCE(invoices.grand_total, 0))');

            $chartPenjualanPart = [];
            $chartPenjualanJasa = [];
            $chartPendapatanOmset = [];
            $chartTotalTagihan = [];
            $chartPengeluaranHppSparepart = [];
            $chartPengeluaranHppOli = [];
            $chartPengeluaranOperasional = [];
            $chartPengeluaranNonOperasional = [];
            for ($i = 1; $i <= 12; $i++) {
                $chartPenjualanPart[] = (float) ($partMap[$i] ?? 0);
                $chartPenjualanJasa[] = (float) ($jasaMap[$i] ?? 0);
                $chartPendapatanOmset[] = (float) ($omsetMap[$i] ?? 0);
                $chartTotalTagihan[] = (float) ($tagihanMap[$i] ?? 0);
                $start = \Carbon\Carbon::create($selectedYear, $i, 1)->startOfMonth();
                $expense = $expenseForRange($start, $start->copy()->endOfMonth());
                $chartPengeluaranHppSparepart[] = (float) $expense['hpp_sparepart'];
                $chartPengeluaranHppOli[] = (float) $expense['hpp_oli'];
                $chartPengeluaranOperasional[] = (float) $expense['biaya_operasional'];
                $chartPengeluaranNonOperasional[] = (float) $expense['biaya_non_operasional'];
            }

            return compact(
                'chartLabels',
                'chartPenjualanPart',
                'chartPenjualanJasa',
                'chartPendapatanOmset',
                'chartTotalTagihan',
                'chartPengeluaranHppSparepart',
                'chartPengeluaranHppOli',
                'chartPengeluaranOperasional',
                'chartPengeluaranNonOperasional',
            );
        }

        if ($mode === 'bulanan' || $mode === 'range') {
            $dateExpr = $driver === 'sqlite'
                ? "strftime('%Y-%m-%d', {$transaksiDate})"
                : $transaksiDate;

            $invBase = DB::table('invoices')
                ->join('service_orders', 'service_orders.id', '=', 'invoices.service_order_id')
                ->whereIn('invoices.service_order_id', $orderIdsQuery);
            $partMap = $this->chartBucketTotals(clone $invBase, $dateExpr, $partExpr, 'd');
            $jasaMap = $this->chartBucketTotals(clone $invBase, $dateExpr, $jasaExpr, 'd');
            $omsetMap = $this->chartBucketTotals(clone $invBase, $dateExpr, $omsetExpr, 'd');
            $tagihanMap = $this->chartBucketTotals(clone $invBase, $dateExpr, 'SUM(COALESCE(invoices.grand_total, 0))', 'd');

            $chartLabels = [];
            $chartPenjualanPart = [];
            $chartPenjualanJasa = [];
            $chartPendapatanOmset = [];
            $chartTotalTagihan = [];
            $chartPengeluaranHppSparepart = [];
            $chartPengeluaranHppOli = [];
            $chartPengeluaranOperasional = [];
            $chartPengeluaranNonOperasional = [];
            $cursor = $dari->copy()->startOfDay();
            $end = $sampai->copy()->startOfDay();
            while ($cursor->lte($end)) {
                $key = $cursor->toDateString();
                $chartLabels[] = $cursor->format('d/m');
                $chartPenjualanPart[] = (float) ($partMap[$key] ?? 0);
                $chartPenjualanJasa[] = (float) ($jasaMap[$key] ?? 0);
                $chartPendapatanOmset[] = (float) ($omsetMap[$key] ?? 0);
                $chartTotalTagihan[] = (float) ($tagihanMap[$key] ?? 0);
                $expense = $expenseForRange($cursor->copy()->startOfDay(), $cursor->copy()->endOfDay());
                $chartPengeluaranHppSparepart[] = (float) $expense['hpp_sparepart'];
                $chartPengeluaranHppOli[] = (float) $expense['hpp_oli'];
                $chartPengeluaranOperasional[] = (float) $expense['biaya_operasional'];
                $chartPengeluaranNonOperasional[] = (float) $expense['biaya_non_operasional'];
                $cursor->addDay();
            }

            return compact(
                'chartLabels',
                'chartPenjualanPart',
                'chartPenjualanJasa',
                'chartPendapatanOmset',
                'chartTotalTagihan',
                'chartPengeluaranHppSparepart',
                'chartPengeluaranHppOli',
                'chartPengeluaranOperasional',
                'chartPengeluaranNonOperasional',
            );
        }

        $metrics = $this->collectDashboardSummaryMetrics((clone $orderIdsQuery)->pluck('id'));
        $day = \Carbon\Carbon::parse($selectedDate);
        $expense = $expenseForRange($day->copy()->startOfDay(), $day->copy()->endOfDay());

        return [
            'chartLabels' => [$day->format('d/m')],
            'chartPenjualanPart' => [(float) $metrics['penjualanPart']],
            'chartPenjualanJasa' => [(float) $metrics['pendapatanJasa']],
            'chartPendapatanOmset' => [(float) $metrics['pendapatanOmset']],
            'chartTotalTagihan' => [(float) $metrics['totalOmset']],
            'chartPengeluaranHppSparepart' => [(float) $expense['hpp_sparepart']],
            'chartPengeluaranHppOli' => [(float) $expense['hpp_oli']],
            'chartPengeluaranOperasional' => [(float) $expense['biaya_operasional']],
            'chartPengeluaranNonOperasional' => [(float) $expense['biaya_non_operasional']],
        ];
    }

    /**
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return array<int|string, float>
     */
    private function chartBucketTotals($query, string $bucketExpr, string $sumExpr, string $alias = 'm'): array
    {
        $rows = (clone $query)
            ->selectRaw("{$bucketExpr} as {$alias}, {$sumExpr} as total")
            ->groupBy($alias)
            ->orderBy($alias)
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[$row->{$alias}] = (float) $row->total;
        }

        return $map;
    }

    private function dashboardChartPeriodHint(
        string $mode,
        \Carbon\Carbon $dari,
        \Carbon\Carbon $sampai,
        string $selectedDate,
        string $selectedMonth,
        int $selectedYear,
    ): string {
        return match ($mode) {
            'tahunan' => 'Per bulan pada tahun '.$selectedYear,
            'bulanan' => 'Per hari pada '.\Carbon\Carbon::parse($selectedMonth.'-01')->translatedFormat('F Y'),
            'range' => 'Per hari ('.$dari->format('d/m/Y').' – '.$sampai->format('d/m/Y').')',
            default => 'Tanggal '.\Carbon\Carbon::parse($selectedDate)->format('d/m/Y'),
        };
    }
}
