<?php

namespace App\Http\Controllers\Speedshop;

use App\Exports\Speedshop\BopPengembalianSpeedshopExport;
use App\Http\Controllers\Concerns\ResolvesKeuanganContext;
use App\Http\Controllers\Controller;
use App\Models\Speedshop;
use App\Models\SpeedshopRincianBiayaAdjustment;
use App\Models\SpeedshopRincianBiayaEntry;
use App\Services\SpeedshopFinanceService;
use App\Services\SpeedshopRincianBiayaAdjustmentService;
use App\Support\DashboardPeriodResolver;
use App\Support\KeuanganScope;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class KeuanganBopPengembalianController extends Controller
{
    use ResolvesKeuanganContext;

    protected function keuanganRoutePrefix(): string
    {
        return 'speedshop.keuangan';
    }

    protected function keuanganDashboardRoute(): string
    {
        return 'speedshop.dashboard';
    }

    protected function keuanganViewPrefix(): string
    {
        return 'speedshop.keuangan';
    }

    public function index(Request $request): View|RedirectResponse
    {
        if (! auth()->user()?->isSpeedshopAccounting()) {
            abort(403, 'Halaman Pengembalian Dana BOP hanya untuk Accounting speedshop.');
        }

        if (! Schema::hasTable('speedshop_rincian_biaya_adjustments')) {
            abort(503, 'Migrasi pengembalian dana BOP belum dijalankan.');
        }

        $scope = $this->resolveKeuanganScope();
        if (! $scope || $scope->isWarehouse()) {
            return redirect()->route($this->keuanganDashboardRoute())
                ->with('error', 'Pilih cabang speedshop terlebih dahulu.');
        }

        $ctx = $this->buildBopPengembalianListContext($request, $scope);

        $adjustments = (clone $ctx['listQuery'])
            ->orderByDesc('tanggal_pengembalian')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view($this->keuanganViewPrefix().'.bop-pengembalian.index', [
            'adjustments' => $adjustments,
            'stats' => $ctx['stats'],
            'exportQuery' => $ctx['exportQuery'],
            'mode' => $ctx['mode'],
            'selectedDate' => $ctx['selectedDate'],
            'selectedMonth' => $ctx['selectedMonth'],
            'selectedYear' => $ctx['selectedYear'],
            'selectedDari' => $ctx['selectedDari'],
            'selectedSampai' => $ctx['selectedSampai'],
            'dari' => $ctx['dari'],
            'sampai' => $ctx['sampai'],
            'filters' => $ctx['filters'],
        ]);
    }

    public function exportExcel(Request $request): BinaryFileResponse|RedirectResponse
    {
        if (! auth()->user()?->isSpeedshopAccounting()) {
            abort(403, 'Halaman Pengembalian Dana BOP hanya untuk Accounting speedshop.');
        }

        if (! Schema::hasTable('speedshop_rincian_biaya_adjustments')) {
            abort(503, 'Migrasi pengembalian dana BOP belum dijalankan.');
        }

        $scope = $this->resolveKeuanganScope();
        if (! $scope || $scope->isWarehouse()) {
            return redirect()->route($this->keuanganDashboardRoute())
                ->with('error', 'Pilih cabang speedshop terlebih dahulu.');
        }

        $ctx = $this->buildBopPengembalianListContext($request, $scope);
        $rows = (clone $ctx['listQuery'])
            ->orderByDesc('tanggal_pengembalian')
            ->orderByDesc('id')
            ->get();

        $cabangLabel = Speedshop::query()->whereKey($scope->speedshopId)->value('nama') ?? 'Speedshop';

        return Excel::download(
            new BopPengembalianSpeedshopExport($rows, $ctx['periodeLabel'], $ctx['stats'], (string) $cabangLabel),
            'bop-pengembalian-speedshop-'.now()->format('Y-m-d-His').'.xlsx'
        );
    }

    public function exportPdf(Request $request): Response|RedirectResponse
    {
        if (! auth()->user()?->isSpeedshopAccounting()) {
            abort(403, 'Halaman Pengembalian Dana BOP hanya untuk Accounting speedshop.');
        }

        if (! Schema::hasTable('speedshop_rincian_biaya_adjustments')) {
            abort(503, 'Migrasi pengembalian dana BOP belum dijalankan.');
        }

        $scope = $this->resolveKeuanganScope();
        if (! $scope || $scope->isWarehouse()) {
            return redirect()->route($this->keuanganDashboardRoute())
                ->with('error', 'Pilih cabang speedshop terlebih dahulu.');
        }

        $ctx = $this->buildBopPengembalianListContext($request, $scope);
        $rows = (clone $ctx['listQuery'])
            ->orderByDesc('tanggal_pengembalian')
            ->orderByDesc('id')
            ->get();
        $filename = 'bop-pengembalian-speedshop-'.now()->format('Y-m-d-His').'.pdf';

        return Pdf::loadView($this->keuanganViewPrefix().'.bop-pengembalian.pdf', [
            'rows' => $rows,
            'periodeLabel' => $ctx['periodeLabel'],
            'stats' => $ctx['stats'],
            'printedAt' => now(),
        ])->setPaper('a4', 'landscape')->stream($filename);
    }

    public function entries(Request $request): JsonResponse
    {
        if (! auth()->user()?->isSpeedshopAccounting()) {
            abort(403, 'Halaman Pengembalian Dana BOP hanya untuk Accounting speedshop.');
        }

        if (! Schema::hasTable('speedshop_rincian_biaya_adjustments')) {
            abort(503, 'Migrasi pengembalian dana BOP belum dijalankan.');
        }

        $scope = $this->resolveKeuanganScope();
        if (! $scope || $scope->isWarehouse()) {
            return response()->json(['message' => 'Pilih cabang speedshop terlebih dahulu.'], 422);
        }

        $validated = $request->validate([
            'tanggal_bop' => ['required', 'date'],
        ]);

        $entries = $this->bopOptionsForDate((int) $scope->speedshopId, $validated['tanggal_bop'])
            ->map(fn (SpeedshopRincianBiayaEntry $e) => [
                'id' => $e->id,
                'label' => $this->formatBopOptionLabel($e),
                'nominal_asli' => $e->nominalAsli(),
                'sisa_dapat_dikurangi' => $e->sisaDapatDikurangi(),
            ])
            ->values();

        return response()->json(['entries' => $entries]);
    }

    public function store(
        Request $request,
        SpeedshopRincianBiayaAdjustmentService $service,
        SpeedshopFinanceService $finance,
    ): RedirectResponse {
        if (! auth()->user()?->isSpeedshopAccounting()) {
            abort(403, 'Halaman Pengembalian Dana BOP hanya untuk Accounting speedshop.');
        }

        $scope = $this->resolveKeuanganScope();
        if (! $scope || $scope->isWarehouse()) {
            return back()->with('error', 'Pilih cabang speedshop terlebih dahulu.');
        }

        $validated = $request->validate([
            'tanggal_bop' => ['required', 'date'],
            'rincian_biaya_entry_id' => ['required', 'integer'],
            'tanggal_pengembalian' => ['required', 'date'],
            'nominal_pengurangan' => ['required', 'integer', 'min:1'],
            'biaya_admin' => ['nullable', 'integer', 'min:0'],
            'catatan' => ['nullable', 'string', 'max:1000'],
            'bukti' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);

        $entry = SpeedshopRincianBiayaEntry::query()
            ->where('speedshop_id', $scope->speedshopId)
            ->whereNull('warehouse_id')
            ->whereKey((int) $validated['rincian_biaya_entry_id'])
            ->whereDate('tanggal', $validated['tanggal_bop'])
            ->firstOrFail();

        try {
            $adjustment = $service->apply($request, $entry, $scope, $finance, $validated);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['nominal_pengurangan' => $e->getMessage()])->withInput();
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Gagal mencatat pengembalian dana: '.$e->getMessage())->withInput();
        }

        return redirect()
            ->route($this->keuanganRoutePrefix().'.bop-pengembalian.index', $request->only(array_merge(
                ['mode', 'tanggal', 'bulan', 'tahun', 'dari', 'sampai'],
                $this->bopPengembalianColumnFilterKeys(),
            )))
            ->with('success', 'Penyesuaian BOP Rp '.number_format(
                (int) $adjustment->nominal_pengurangan + (int) $adjustment->biaya_admin,
                0,
                ',',
                '.'
            ).' berhasil dicatat. Nominal efektif BOP: Rp '.number_format($entry->fresh()->nominalEfektif(), 0, ',', '.'));
    }

    /**
     * @return array{
     *     listQuery: \Illuminate\Database\Eloquent\Builder,
     *     stats: array{count: int, total_pengurangan: int},
     *     periodeLabel: string,
     *     exportQuery: array<string, mixed>,
     *     filters: array<string, mixed>,
     *     mode: string,
     *     selectedDate: string,
     *     selectedMonth: string,
     *     selectedYear: int,
     *     selectedDari: string,
     *     selectedSampai: string,
     *     dari: \Carbon\Carbon,
     *     sampai: \Carbon\Carbon,
     * }
     */
    /** @return list<string> */
    protected function bopPengembalianColumnFilterKeys(): array
    {
        return [
            'filter_tgl_refund',
            'filter_tgl_bop',
            'filter_bop',
            'filter_pengurangan',
            'filter_admin',
            'filter_bop_efektif',
            'filter_oleh',
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function applyBopPengembalianColumnFilters(
        \Illuminate\Database\Eloquent\Builder $query,
        array $filters,
    ): void {
        if (! empty($filters['filter_tgl_refund'])) {
            $query->whereDate(
                'tanggal_pengembalian',
                Carbon::parse($filters['filter_tgl_refund'])->toDateString()
            );
        }

        if (! empty($filters['filter_tgl_bop'])) {
            $tglBop = Carbon::parse($filters['filter_tgl_bop'])->toDateString();
            $query->whereHas('rincianBiayaEntry', fn ($q) => $q->whereDate('tanggal', $tglBop));
        }

        if (! empty($filters['filter_bop'])) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], (string) $filters['filter_bop']).'%';
            $query->whereHas('rincianBiayaEntry', function ($q) use ($term) {
                $q->where(function ($inner) use ($term) {
                    $inner->where('no_akun', 'like', $term)
                        ->orWhere('nama_akun', 'like', $term)
                        ->orWhere('keterangan', 'like', $term);
                });
            });
        }

        if (! empty($filters['filter_pengurangan'])) {
            $digits = preg_replace('/\D+/', '', (string) $filters['filter_pengurangan']) ?? '';
            if ($digits !== '') {
                $query->whereRaw('CAST(nominal_pengurangan AS CHAR) LIKE ?', ['%'.$digits.'%']);
            }
        }

        if (! empty($filters['filter_admin'])) {
            $digits = preg_replace('/\D+/', '', (string) $filters['filter_admin']) ?? '';
            if ($digits !== '') {
                $query->whereRaw('CAST(biaya_admin AS CHAR) LIKE ?', ['%'.$digits.'%']);
            }
        }

        if (! empty($filters['filter_bop_efektif'])) {
            $digits = preg_replace('/\D+/', '', (string) $filters['filter_bop_efektif']) ?? '';
            if ($digits !== '') {
                $efektifSql = SpeedshopRincianBiayaEntry::sqlNominalEfektif('speedshop_rincian_biaya_entries');
                $query->whereHas('rincianBiayaEntry', function ($q) use ($digits, $efektifSql) {
                    $q->whereRaw('CAST(('.$efektifSql.') AS CHAR) LIKE ?', ['%'.$digits.'%']);
                });
            }
        }

        if (! empty($filters['filter_oleh'])) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], (string) $filters['filter_oleh']).'%';
            $query->whereHas('createdByUser', fn ($q) => $q->where('name', 'like', $term));
        }
    }

    protected function buildBopPengembalianListContext(Request $request, KeuanganScope $scope): array
    {
        $period = DashboardPeriodResolver::fromRequest($request, 'bulanan');
        $speedshopId = (int) $scope->speedshopId;

        $clean = $request->only($this->bopPengembalianColumnFilterKeys());
        foreach ($this->bopPengembalianColumnFilterKeys() as $key) {
            if (($clean[$key] ?? '') === '') {
                unset($clean[$key]);
            }
        }
        $filters = Validator::make($clean, [
            'filter_tgl_refund' => ['nullable', 'date'],
            'filter_tgl_bop' => ['nullable', 'date'],
            'filter_bop' => ['nullable', 'string', 'max:120'],
            'filter_pengurangan' => ['nullable', 'string', 'max:30'],
            'filter_admin' => ['nullable', 'string', 'max:30'],
            'filter_bop_efektif' => ['nullable', 'string', 'max:30'],
            'filter_oleh' => ['nullable', 'string', 'max:120'],
        ])->validate();

        $listQuery = SpeedshopRincianBiayaAdjustment::query()
            ->where('speedshop_id', $speedshopId)
            ->with(['rincianBiayaEntry', 'createdByUser'])
            ->whereBetween('tanggal_pengembalian', [
                $period['dari']->toDateString(),
                $period['sampai']->toDateString(),
            ]);

        $this->applyBopPengembalianColumnFilters($listQuery, $filters);

        $statsQuery = clone $listQuery;
        $stats = [
            'count' => (int) (clone $statsQuery)->count(),
            'total_pengurangan' => (int) (clone $statsQuery)
                ->selectRaw('COALESCE(SUM(nominal_pengurangan + biaya_admin), 0) as total')
                ->value('total'),
        ];

        $periodeLabel = match ($period['mode']) {
            'harian' => Carbon::parse($period['selectedDate'])->translatedFormat('d M Y'),
            'bulanan' => Carbon::parse($period['selectedMonth'].'-01')->translatedFormat('F Y'),
            'tahunan' => (string) $period['selectedYear'],
            'range' => $period['dari']->translatedFormat('d M Y').' – '.$period['sampai']->translatedFormat('d M Y'),
            default => now()->translatedFormat('F Y'),
        };

        $exportQuery = array_filter(array_merge([
            'mode' => $period['mode'],
            'tanggal' => $period['mode'] === 'harian' ? $period['selectedDate'] : null,
            'bulan' => $period['mode'] === 'bulanan' ? $period['selectedMonth'] : null,
            'tahun' => $period['mode'] === 'tahunan' ? $period['selectedYear'] : null,
            'dari' => $period['mode'] === 'range' ? $period['selectedDari'] : null,
            'sampai' => $period['mode'] === 'range' ? $period['selectedSampai'] : null,
        ], $filters), fn ($v) => $v !== null && $v !== '');

        return [
            'listQuery' => $listQuery,
            'stats' => $stats,
            'periodeLabel' => $periodeLabel,
            'exportQuery' => $exportQuery,
            'filters' => $filters,
            'mode' => $period['mode'],
            'selectedDate' => $period['selectedDate'],
            'selectedMonth' => $period['selectedMonth'],
            'selectedYear' => $period['selectedYear'],
            'selectedDari' => $period['selectedDari'],
            'selectedSampai' => $period['selectedSampai'],
            'dari' => $period['dari'],
            'sampai' => $period['sampai'],
        ];
    }

    /** @return \Illuminate\Support\Collection<int, SpeedshopRincianBiayaEntry> */
    protected function bopOptionsForDate(int $speedshopId, string $tanggalBop)
    {
        return SpeedshopRincianBiayaEntry::query()
            ->where('speedshop_id', $speedshopId)
            ->whereNull('warehouse_id')
            ->whereDate('tanggal', $tanggalBop)
            ->where(function ($q) {
                $q->whereNull('approval_status')
                    ->orWhere('approval_status', SpeedshopRincianBiayaEntry::STATUS_APPROVED);
            })
            ->where('finance_ledger_id', '>', 0)
            ->with('adjustments')
            ->orderBy('id')
            ->get()
            ->filter(fn (SpeedshopRincianBiayaEntry $e) => $e->sisaDapatDikurangi() > 0)
            ->values();
    }

    protected function formatBopOptionLabel(SpeedshopRincianBiayaEntry $entry): string
    {
        $label = $entry->no_akun.' · '.$entry->nama_akun.' · Rp '.number_format($entry->nominalAsli(), 0, ',', '.');
        if ($entry->keterangan) {
            $label .= ' · '.$entry->keterangan;
        }

        return $label.' (sisa Rp '.number_format($entry->sisaDapatDikurangi(), 0, ',', '.').')';
    }
}
