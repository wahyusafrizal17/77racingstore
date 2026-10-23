<?php

namespace App\Http\Controllers\Speedshop;

use App\Exports\Speedshop\PermintaanKasKecilSpeedshopExport;
use App\Http\Controllers\Concerns\ResolvesKeuanganContext;
use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\Speedshop;
use App\Models\SpeedshopFinancePermintaanKasKecil;
use App\Models\User;
use App\Services\SpeedshopFinanceService;
use App\Support\KeuanganScope;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KeuanganPermintaanKasKecilController extends Controller
{
    use ResolvesKeuanganContext;

    public function index(Request $request): View|RedirectResponse
    {
        if (! Schema::hasTable('speedshop_finance_permintaan_kas_kecil')) {
            abort(503, 'Migrasi keuangan belum dijalankan.');
        }

        $scope = $this->resolveKeuanganScope();
        if (! $scope) {
            return redirect()->route($this->keuanganDashboardRoute())
                ->with('error', $this->keuanganScopeErrorMessage());
        }

        $ctx = $this->buildPermintaanKasKecilListContext($request, $scope);
        $period = $ctx['period'];

        $riwayat = (clone $ctx['riwayatQuery'])
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return $this->keuanganView('permintaan-kas-kecil.index', [
            'asOfYmd' => now()->toDateString(),
            'riwayat' => $riwayat,
            'permintaanStats' => $ctx['permintaanStats'],
            'filters' => $ctx['filters'],
            'exportQuery' => $ctx['exportQuery'],
            'mode' => $period['mode'],
            'selectedDate' => $period['selectedDate'],
            'selectedMonth' => $period['selectedMonth'],
            'selectedYear' => $period['selectedYear'],
            'selectedDari' => $period['selectedDari'],
            'selectedSampai' => $period['selectedSampai'],
            'dari' => $period['dari'],
            'sampai' => $period['sampai'],
        ]);
    }

    public function exportExcel(Request $request): BinaryFileResponse|RedirectResponse
    {
        if (! Schema::hasTable('speedshop_finance_permintaan_kas_kecil')) {
            abort(503, 'Migrasi keuangan belum dijalankan.');
        }

        $scope = $this->resolveKeuanganScope();
        if (! $scope) {
            return redirect()->route($this->keuanganDashboardRoute())
                ->with('error', $this->keuanganScopeErrorMessage());
        }

        $ctx = $this->buildPermintaanKasKecilListContext($request, $scope);
        $rows = (clone $ctx['riwayatQuery'])->orderByDesc('id')->get();

        $cabangLabel = $scope->isWarehouse()
            ? 'Gudang'
            : (Speedshop::query()->whereKey($scope->speedshopId)->value('nama') ?? 'Speedshop');

        return Excel::download(
            new PermintaanKasKecilSpeedshopExport(
                $rows,
                $ctx['periodeLabel'],
                $ctx['permintaanStats'],
                $ctx['hasCatatanCol'],
                (string) $cabangLabel,
            ),
            'permintaan-kas-kecil-speedshop-'.now()->format('Y-m-d-His').'.xlsx'
        );
    }

    public function exportPdf(Request $request): StreamedResponse|RedirectResponse
    {
        if (! Schema::hasTable('speedshop_finance_permintaan_kas_kecil')) {
            abort(503, 'Migrasi keuangan belum dijalankan.');
        }

        $scope = $this->resolveKeuanganScope();
        if (! $scope) {
            return redirect()->route($this->keuanganDashboardRoute())
                ->with('error', $this->keuanganScopeErrorMessage());
        }

        $ctx = $this->buildPermintaanKasKecilListContext($request, $scope);
        $rows = (clone $ctx['riwayatQuery'])->orderByDesc('id')->get();
        $filename = 'permintaan-kas-kecil-speedshop-'.now()->format('Y-m-d-His').'.pdf';

        return Pdf::loadView($this->keuanganViewPrefix().'.permintaan-kas-kecil.pdf', [
            'rows' => $rows,
            'periodeLabel' => $ctx['periodeLabel'],
            'stats' => $ctx['permintaanStats'],
            'filters' => $ctx['filters'],
            'hasCatatanCol' => $ctx['hasCatatanCol'],
            'printedAt' => now(),
        ])->setPaper('a4', 'landscape')->stream($filename);
    }

    public function store(Request $request, SpeedshopFinanceService $finance): RedirectResponse
    {
        if (! Schema::hasTable('speedshop_finance_permintaan_kas_kecil')) {
            abort(503, 'Migrasi keuangan belum dijalankan.');
        }

        $scope = $this->resolveKeuanganScope();
        if (! $scope) {
            return redirect()->route($this->keuanganDashboardRoute())
                ->with('error', $this->keuanganScopeErrorMessage());
        }

        $user = auth()->user();
        $requiresCatatan = $user && $user->isSpeedshop() && ! $user->isManager();

        $rules = [
            'tanggal' => ['required', 'date'],
            'nominal' => ['required', 'integer', 'min:1', 'max:999999999'],
            'catatan' => $requiresCatatan
                ? ['required', 'string', 'min:5', 'max:2000']
                : ['nullable', 'string', 'max:2000'],
        ];

        $validated = $request->validate($rules);

        $tanggalYmd = Carbon::parse($validated['tanggal'])->toDateString();
        if ($tanggalYmd > now()->toDateString()) {
            return back()->withErrors(['tanggal' => 'Tanggal tidak boleh di masa depan.'])->withInput();
        }

        $finance->ensureSettingsForScope($scope);

        $catatan = isset($validated['catatan']) ? trim((string) $validated['catatan']) : '';
        $catatan = $catatan !== '' ? $catatan : null;

        $attrs = array_merge($scope->ownershipAttributes(), [
            'tanggal' => $tanggalYmd,
            'nominal' => (int) $validated['nominal'],
            'status' => SpeedshopFinancePermintaanKasKecil::STATUS_PENDING,
            'requested_by' => auth()->id(),
        ]);
        if (Schema::hasColumn('speedshop_finance_permintaan_kas_kecil', 'catatan')) {
            $attrs['catatan'] = $catatan;
        }

        $permintaan = SpeedshopFinancePermintaanKasKecil::query()->create($attrs);

        $scopeLabel = $scope->isWarehouse()
            ? 'Gudang'
            : (Speedshop::query()->whereKey($scope->speedshopId)->value('nama') ?? 'Cabang');
        $nominalFormatted = number_format((int) $permintaan->nominal, 0, ',', '.');
        $tanggalLabel = Carbon::parse($permintaan->tanggal)->format('d/m/Y');
        $pemohon = auth()->user()?->name ?? 'Pengguna';
        $link = $this->keuanganRoute('permintaan-kas-kecil.index', ['status' => 'pending']);

        $accountingUsers = $scope->isWarehouse()
            ? User::accountingUsersForWarehouse((int) $scope->warehouseId)
            : User::accountingUsersForSpeedshop((int) $scope->speedshopId);

        foreach ($accountingUsers as $acct) {
            $notifData = [
                'speedshop_finance_permintaan_kas_kecil_id' => $permintaan->id,
            ];
            if ($scope->isWarehouse()) {
                $notifData['warehouse_id'] = $scope->warehouseId;
            } else {
                $notifData['speedshop_id'] = $scope->speedshopId;
            }

            AppNotification::create([
                'user_id' => $acct->id,
                'type' => 'speedshop_permintaan_kas_kecil_pending',
                'title' => 'Permintaan Kas Kecil menunggu verifikasi',
                'message' => "{$pemohon} mengajukan penambahan Kas Kecil Rp {$nominalFormatted} (tanggal jurnal {$tanggalLabel}) — {$scopeLabel}.",
                'data' => $notifData,
                'link' => $link,
            ]);
        }

        return redirect()->to($this->keuanganRoute('permintaan-kas-kecil.index'))
            ->with('success', 'Permintaan Kas Kecil diajukan dan menunggu verifikasi Accounting.');
    }

    public function approve(SpeedshopFinancePermintaanKasKecil $permintaan, SpeedshopFinanceService $finance): RedirectResponse
    {
        if (! auth()->user()?->isAccounting()) {
            abort(403);
        }

        $scope = $this->resolveKeuanganScope();
        if (! $scope || ! $scope->ownsPermintaan($permintaan)) {
            abort(403);
        }

        if ($permintaan->status !== SpeedshopFinancePermintaanKasKecil::STATUS_PENDING) {
            return back()->with('error', 'Permintaan ini sudah diproses.');
        }

        DB::transaction(function () use ($permintaan, $finance, $scope): void {
            $locked = SpeedshopFinancePermintaanKasKecil::query()->lockForUpdate()->findOrFail($permintaan->id);
            if ($locked->status !== SpeedshopFinancePermintaanKasKecil::STATUS_PENDING) {
                return;
            }

            $ledger = $finance->recordPermintaanKasKecilForScope(
                $scope,
                Carbon::parse($locked->tanggal)->toDateString(),
                (int) $locked->nominal,
                (int) auth()->id(),
            );

            $locked->update([
                'status' => SpeedshopFinancePermintaanKasKecil::STATUS_COMPLETED,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'rejected_at' => null,
                'rejected_reason' => null,
                'ledger_id' => $ledger->id,
            ]);
        });

        return back()->with('success', 'Permintaan Kas Kecil disetujui dan jurnal berhasil diposting.');
    }

    public function reject(Request $request, SpeedshopFinancePermintaanKasKecil $permintaan): RedirectResponse
    {
        if (! auth()->user()?->isAccounting()) {
            abort(403);
        }

        $scope = $this->resolveKeuanganScope();
        if (! $scope || ! $scope->ownsPermintaan($permintaan)) {
            abort(403);
        }

        if ($permintaan->status !== SpeedshopFinancePermintaanKasKecil::STATUS_PENDING) {
            return back()->with('error', 'Permintaan ini sudah diproses.');
        }

        $validated = $request->validate([
            'rejected_reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $permintaan->update([
            'status' => SpeedshopFinancePermintaanKasKecil::STATUS_REJECTED,
            'approved_by' => auth()->id(),
            'rejected_at' => now(),
            'rejected_reason' => $validated['rejected_reason'],
        ]);

        return back()->with('success', 'Permintaan Kas Kecil ditolak.');
    }

    /**
     * @return array{
     *     period: array{dari: \Carbon\Carbon, sampai: \Carbon\Carbon, mode: string, selectedDate: string, selectedMonth: string, selectedYear: int, selectedDari: string, selectedSampai: string},
     *     riwayatQuery: Builder,
     *     permintaanStats: array{total: int, pending: int, completed: int, rejected: int},
     *     filters: array<string, mixed>,
     *     periodeLabel: string,
     *     hasRekeningCol: bool,
     *     hasCatatanCol: bool,
     *     exportQuery: array<string, mixed>,
     * }
     */
    protected function buildPermintaanKasKecilListContext(Request $request, KeuanganScope $scope): array
    {
        $period = $this->resolvePermintaanKasKecilPeriod($request);
        $periodStart = $period['dari']->toDateString();
        $periodEnd = $period['sampai']->toDateString();

        $with = ['requester', 'approver'];
        $hasRekeningCol = Schema::hasColumn('speedshop_finance_permintaan_kas_kecil', 'keuangan_rekening_id');
        $hasCatatanCol = Schema::hasColumn('speedshop_finance_permintaan_kas_kecil', 'catatan');
        if ($hasRekeningCol) {
            $with[] = 'rekening';
        }

        $riwayatQuery = $scope->applyToPermintaan(SpeedshopFinancePermintaanKasKecil::query())
            ->with($with)
            ->whereDate('tanggal', '>=', $periodStart)
            ->whereDate('tanggal', '<=', $periodEnd);

        if ($request->filled('tanggal_jurnal')) {
            try {
                $tanggalFilter = Carbon::parse((string) $request->input('tanggal_jurnal'))->toDateString();
                $riwayatQuery->whereDate('tanggal', $tanggalFilter);
            } catch (\Throwable $e) {
                // Abaikan filter tanggal yang tidak valid.
            }
        }

        if ($request->filled('nominal')) {
            $nominalFilter = (int) $request->input('nominal');
            if ($nominalFilter >= 0) {
                $riwayatQuery->where('nominal', $nominalFilter);
            }
        }

        if ($request->filled('dicatat_oleh')) {
            $term = '%'.addcslashes((string) $request->input('dicatat_oleh'), '%_\\').'%';
            $riwayatQuery->whereHas('requester', fn ($q) => $q->where('name', 'like', $term));
        }

        $statsQuery = clone $riwayatQuery;
        $permintaanStats = [
            'total' => (int) (clone $statsQuery)->count(),
            'pending' => (int) (clone $statsQuery)->where('status', SpeedshopFinancePermintaanKasKecil::STATUS_PENDING)->count(),
            'completed' => (int) (clone $statsQuery)->where('status', SpeedshopFinancePermintaanKasKecil::STATUS_COMPLETED)->count(),
            'rejected' => (int) (clone $statsQuery)->where('status', SpeedshopFinancePermintaanKasKecil::STATUS_REJECTED)->count(),
        ];

        if ($request->filled('status')) {
            $status = (string) $request->input('status');
            if (in_array($status, [
                SpeedshopFinancePermintaanKasKecil::STATUS_PENDING,
                SpeedshopFinancePermintaanKasKecil::STATUS_COMPLETED,
                SpeedshopFinancePermintaanKasKecil::STATUS_REJECTED,
            ], true)) {
                $riwayatQuery->where('status', $status);
            }
        }

        return [
            'period' => $period,
            'riwayatQuery' => $riwayatQuery,
            'permintaanStats' => $permintaanStats,
            'filters' => $request->only(['tanggal_jurnal', 'status', 'nominal', 'dicatat_oleh']),
            'periodeLabel' => $this->permintaanKasKecilPeriodeLabel($period),
            'hasRekeningCol' => $hasRekeningCol,
            'hasCatatanCol' => $hasCatatanCol,
            'exportQuery' => array_filter([
                'mode' => $period['mode'],
                'tanggal' => $period['mode'] === 'harian' ? $period['selectedDate'] : null,
                'bulan' => $period['mode'] === 'bulanan' ? $period['selectedMonth'] : null,
                'tahun' => $period['mode'] === 'tahunan' ? $period['selectedYear'] : null,
                'dari' => $period['mode'] === 'range' ? $period['selectedDari'] : null,
                'sampai' => $period['mode'] === 'range' ? $period['selectedSampai'] : null,
                'status' => $request->input('status'),
                'tanggal_jurnal' => $request->input('tanggal_jurnal'),
                'nominal' => $request->input('nominal'),
                'dicatat_oleh' => $request->input('dicatat_oleh'),
            ], fn ($v) => $v !== null && $v !== ''),
        ];
    }

    /**
     * @param array{dari: \Carbon\Carbon, sampai: \Carbon\Carbon, mode: string, selectedDate: string, selectedMonth: string, selectedYear: int, selectedDari: string, selectedSampai: string} $period
     */
    protected function permintaanKasKecilPeriodeLabel(array $period): string
    {
        return match ($period['mode']) {
            'harian' => Carbon::parse($period['selectedDate'])->translatedFormat('d M Y'),
            'bulanan' => Carbon::parse($period['selectedMonth'].'-01')->translatedFormat('F Y'),
            'tahunan' => (string) $period['selectedYear'],
            'range' => $period['dari']->translatedFormat('d M Y').' – '.$period['sampai']->translatedFormat('d M Y'),
            default => now()->translatedFormat('F Y'),
        };
    }

    /**
     * @return array{dari: \Carbon\Carbon, sampai: \Carbon\Carbon, mode: string, selectedDate: string, selectedMonth: string, selectedYear: int, selectedDari: string, selectedSampai: string}
     */
    protected function resolvePermintaanKasKecilPeriod(Request $request): array
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
                $dari = Carbon::parse($selectedDari)->startOfDay();
                $sampai = Carbon::parse($selectedSampai)->endOfDay();
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
                $dari = Carbon::parse($selectedDate)->startOfDay();
                $sampai = Carbon::parse($selectedDate)->endOfDay();
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
                $monthBase = Carbon::parse($selectedMonth.'-01');
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
}
