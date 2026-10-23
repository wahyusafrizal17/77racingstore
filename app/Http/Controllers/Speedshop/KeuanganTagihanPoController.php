<?php

namespace App\Http\Controllers\Speedshop;

use App\Exports\Speedshop\TagihanPoSpeedshopExport;
use App\Http\Controllers\Concerns\ResolvesKeuanganContext;
use App\Http\Controllers\Controller;
use App\Models\Speedshop;
use App\Models\SpeedshopPoTagihan;
use App\Services\SpeedshopPoTagihanService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use League\Flysystem\UnableToWriteFile;

class KeuanganTagihanPoController extends Controller
{
    use ResolvesKeuanganContext;

    public function index(Request $request, SpeedshopPoTagihanService $tagihanService): View|RedirectResponse
    {
        if (! Schema::hasTable('speedshop_po_tagihans')) {
            abort(503, 'Migrasi tagihan belum dijalankan.');
        }

        $scope = $this->resolveKeuanganScope();
        if (! $scope) {
            return redirect()->route($this->keuanganDashboardRoute())
                ->with('error', $this->keuanganScopeErrorMessage());
        }

        if ($scope->isWarehouse()) {
            $tagihanService->syncMissingForScope($scope->warehouseId, null);
        } else {
            $tagihanService->syncMissingForScope(null, $scope->speedshopId);
        }

        $ctx = $this->buildTagihanListContext($request, $scope);

        $tagihans = (clone $ctx['listQuery'])
            ->orderByRaw("CASE status WHEN 'belum_lunas' THEN 0 WHEN 'dibayar' THEN 1 ELSE 2 END")
            ->orderByDesc('tanggal_tagihan')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return $this->keuanganView('tagihan.index', [
            'tagihans' => $tagihans,
            'filters' => $ctx['filters'],
            'stats' => $ctx['stats'],
            'exportQuery' => $ctx['exportQuery'],
            'isReceivable' => $scope->isWarehouse(),
            'mode' => $ctx['mode'],
            'selectedDate' => $ctx['selectedDate'],
            'selectedMonth' => $ctx['selectedMonth'],
            'selectedYear' => $ctx['selectedYear'],
            'selectedDari' => $ctx['selectedDari'],
            'selectedSampai' => $ctx['selectedSampai'],
            'dari' => $ctx['dari'],
            'sampai' => $ctx['sampai'],
        ]);
    }

    public function exportExcel(Request $request): BinaryFileResponse|RedirectResponse
    {
        if (! Schema::hasTable('speedshop_po_tagihans')) {
            abort(503, 'Migrasi tagihan belum dijalankan.');
        }

        $scope = $this->resolveKeuanganScope();
        if (! $scope) {
            return redirect()->route($this->keuanganDashboardRoute())
                ->with('error', $this->keuanganScopeErrorMessage());
        }

        $tagihanService = app(SpeedshopPoTagihanService::class);
        if ($scope->isWarehouse()) {
            $tagihanService->syncMissingForScope($scope->warehouseId, null);
        } else {
            $tagihanService->syncMissingForScope(null, $scope->speedshopId);
        }

        $ctx = $this->buildTagihanListContext($request, $scope);
        $rows = (clone $ctx['listQuery'])
            ->orderByRaw("CASE status WHEN 'belum_lunas' THEN 0 WHEN 'dibayar' THEN 1 ELSE 2 END")
            ->orderByDesc('tanggal_tagihan')
            ->orderByDesc('id')
            ->get();

        $cabangLabel = $scope->isWarehouse()
            ? 'Gudang'
            : (Speedshop::query()->whereKey($scope->speedshopId)->value('nama') ?? 'Speedshop');

        return Excel::download(
            new TagihanPoSpeedshopExport($rows, $ctx['periodeLabel'], $ctx['stats'], (string) $cabangLabel),
            'tagihan-po-speedshop-'.now()->format('Y-m-d-His').'.xlsx'
        );
    }

    public function exportPdf(Request $request): StreamedResponse|RedirectResponse
    {
        if (! Schema::hasTable('speedshop_po_tagihans')) {
            abort(503, 'Migrasi tagihan belum dijalankan.');
        }

        $scope = $this->resolveKeuanganScope();
        if (! $scope) {
            return redirect()->route($this->keuanganDashboardRoute())
                ->with('error', $this->keuanganScopeErrorMessage());
        }

        $tagihanService = app(SpeedshopPoTagihanService::class);
        if ($scope->isWarehouse()) {
            $tagihanService->syncMissingForScope($scope->warehouseId, null);
        } else {
            $tagihanService->syncMissingForScope(null, $scope->speedshopId);
        }

        $ctx = $this->buildTagihanListContext($request, $scope);
        $rows = (clone $ctx['listQuery'])
            ->orderByRaw("CASE status WHEN 'belum_lunas' THEN 0 WHEN 'dibayar' THEN 1 ELSE 2 END")
            ->orderByDesc('tanggal_tagihan')
            ->orderByDesc('id')
            ->get();
        $filename = 'tagihan-po-speedshop-'.now()->format('Y-m-d-His').'.pdf';

        return Pdf::loadView($this->keuanganViewPrefix().'.tagihan.pdf', [
            'rows' => $rows,
            'periodeLabel' => $ctx['periodeLabel'],
            'stats' => $ctx['stats'],
            'filters' => $ctx['filters'],
            'printedAt' => now(),
        ])->setPaper('a4', 'landscape')->stream($filename);
    }

    public function show(SpeedshopPoTagihan $tagihan): View|RedirectResponse
    {
        if (! Schema::hasTable('speedshop_po_tagihans')) {
            abort(503, 'Migrasi tagihan belum dijalankan.');
        }

        $scope = $this->requireKeuanganScope();
        $this->authorizeTagihanScope($tagihan, $scope);

        $tagihan->load([
            'items.product',
            'speedshop',
            'warehouse',
            'purchaseOrder',
            'transaksiOffline',
            'dibayarByUser',
            'lunasByUser',
        ]);

        return $this->keuanganView('tagihan.show', [
            'tagihan' => $tagihan,
            'isReceivable' => $scope->isWarehouse(),
        ]);
    }

    public function markPaid(Request $request, SpeedshopPoTagihan $tagihan): RedirectResponse
    {
        if ($this->isWarehouseKeuanganContext()) {
            abort(403, 'Konfirmasi pembayaran dilakukan dari akun Speedshop.');
        }

        $scope = $this->requireKeuanganScope();
        $this->authorizeTagihanScope($tagihan, $scope);

        if (! $tagihan->isBelumLunas()) {
            return back()->with('error', 'Tagihan ini tidak dapat ditandai dibayar.');
        }

        if (! (auth()->user()->isAccounting() || auth()->user()->isManager())) {
            abort(403, 'Hanya Accounting atau Manager Speedshop yang dapat menandai pembayaran.');
        }

        $data = $request->validate([
            'catatan_pembayaran' => ['nullable', 'string', 'max:1000'],
            'bukti_bayar' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $path = $tagihan->bukti_bayar_path;
        if ($request->hasFile('bukti_bayar')) {
            try {
                $path = $request->file('bukti_bayar')->store('tagihan-po/bukti', 'public');
            } catch (UnableToWriteFile $e) {
                return back()->with('error', 'Gagal mengunggah bukti pembayaran.')->withInput();
            }
        }

        $tagihan->update([
            'status' => SpeedshopPoTagihan::STATUS_DIBAYAR,
            'dibayar_at' => now(),
            'dibayar_by' => auth()->id(),
            'catatan_pembayaran' => $data['catatan_pembayaran'] ?? null,
            'bukti_bayar_path' => $path,
        ]);

        return redirect()
            ->route($this->keuanganRoute('tagihan.show', $tagihan))
            ->with('success', 'Pembayaran dicatat. Menunggu konfirmasi dari Warehouse.');
    }

    public function confirmReceived(Request $request, SpeedshopPoTagihan $tagihan): RedirectResponse
    {
        if (! $this->isWarehouseKeuanganContext()) {
            abort(403, 'Konfirmasi penerimaan hanya dari akun Warehouse.');
        }

        $scope = $this->requireKeuanganScope();
        $this->authorizeTagihanScope($tagihan, $scope);

        if (! $tagihan->isDibayar()) {
            return back()->with('error', 'Tagihan harus dalam status menunggu konfirmasi (Speedshop sudah bayar).');
        }

        $data = $request->validate([
            'catatan_konfirmasi' => ['nullable', 'string', 'max:1000'],
        ]);

        $tagihan->update([
            'status' => SpeedshopPoTagihan::STATUS_LUNAS,
            'lunas_at' => now(),
            'lunas_by' => auth()->id(),
            'catatan_konfirmasi' => $data['catatan_konfirmasi'] ?? null,
        ]);

        return redirect()
            ->route($this->keuanganRoute('tagihan.show', $tagihan))
            ->with('success', 'Pembayaran dikonfirmasi diterima. Tagihan lunas.');
    }

    protected function authorizeTagihanScope(SpeedshopPoTagihan $tagihan, \App\Support\KeuanganScope $scope): void
    {
        if ($scope->isWarehouse()) {
            if ((int) $tagihan->warehouse_id !== (int) $scope->warehouseId) {
                abort(403);
            }
        } elseif ((int) $tagihan->speedshop_id !== (int) $scope->speedshopId) {
            abort(403);
        }
    }

    /**
     * @return array{
     *     filters: array<string, mixed>,
     *     stats: array{total: int, belum_lunas: int, menunggu_konfirmasi: int, lunas: int, nominal_lunas: int},
     *     listQuery: \Illuminate\Database\Eloquent\Builder,
     *     periodeLabel: string,
     *     exportQuery: array<string, mixed>,
     *     mode: string,
     *     selectedDate: string,
     *     selectedMonth: string,
     *     selectedYear: int,
     *     selectedDari: string,
     *     selectedSampai: string,
     *     dari: Carbon,
     *     sampai: Carbon,
     * }
     */
    protected function buildTagihanListContext(Request $request, \App\Support\KeuanganScope $scope): array
    {
        $period = $this->resolveTagihanPeriod($request);
        $periodStart = $period['dari']->toDateString();
        $periodEnd = $period['sampai']->toDateString();

        $filters = Validator::make($request->only(['status', 'tanggal_tagihan', 'kode', 'counterparty']), [
            'status' => ['nullable', 'string', 'in:belum_lunas,dibayar,lunas'],
            'tanggal_tagihan' => ['nullable', 'date'],
            'kode' => ['nullable', 'string', 'max:80'],
            'counterparty' => ['nullable', 'string', 'max:120'],
        ])->validate();

        $q = SpeedshopPoTagihan::query()
            ->with(['speedshop', 'warehouse', 'purchaseOrder']);

        if ($scope->isWarehouse()) {
            $q->where('warehouse_id', $scope->warehouseId);
        } else {
            $q->where('speedshop_id', $scope->speedshopId);
        }

        $q->whereDate('tanggal_tagihan', '>=', $periodStart)
            ->whereDate('tanggal_tagihan', '<=', $periodEnd);

        if (! empty($filters['tanggal_tagihan'])) {
            $q->whereDate('tanggal_tagihan', Carbon::parse($filters['tanggal_tagihan'])->toDateString());
        }
        if (! empty($filters['kode'])) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $filters['kode']).'%';
            $q->where('kode', 'like', $term);
        }
        if (! empty($filters['counterparty'])) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $filters['counterparty']).'%';
            if ($scope->isWarehouse()) {
                $q->whereHas('speedshop', fn ($sq) => $sq->where('nama', 'like', $term));
            } else {
                $q->whereHas('warehouse', fn ($wq) => $wq->where('nama', 'like', $term));
            }
        }

        $statsQuery = clone $q;
        $stats = [
            'total' => (int) (clone $statsQuery)->count(),
            'belum_lunas' => (int) (clone $statsQuery)->where('status', SpeedshopPoTagihan::STATUS_BELUM_LUNAS)->count(),
            'menunggu_konfirmasi' => (int) (clone $statsQuery)->where('status', SpeedshopPoTagihan::STATUS_DIBAYAR)->count(),
            'lunas' => (int) (clone $statsQuery)->where('status', SpeedshopPoTagihan::STATUS_LUNAS)->count(),
            'nominal_lunas' => (int) (clone $statsQuery)->where('status', SpeedshopPoTagihan::STATUS_LUNAS)->sum('total_nominal'),
        ];

        if (! empty($filters['status'])) {
            $q->where('status', $filters['status']);
        }

        $periodeLabel = match ($period['mode']) {
            'harian' => Carbon::parse($period['selectedDate'])->translatedFormat('d M Y'),
            'bulanan' => Carbon::parse($period['selectedMonth'].'-01')->translatedFormat('F Y'),
            'tahunan' => (string) $period['selectedYear'],
            'range' => $period['dari']->translatedFormat('d M Y').' – '.$period['sampai']->translatedFormat('d M Y'),
            default => now()->translatedFormat('F Y'),
        };

        $exportQuery = array_filter([
            'mode' => $period['mode'],
            'tanggal' => $period['mode'] === 'harian' ? $period['selectedDate'] : null,
            'bulan' => $period['mode'] === 'bulanan' ? $period['selectedMonth'] : null,
            'tahun' => $period['mode'] === 'tahunan' ? $period['selectedYear'] : null,
            'dari' => $period['mode'] === 'range' ? $period['selectedDari'] : null,
            'sampai' => $period['mode'] === 'range' ? $period['selectedSampai'] : null,
            'status' => $filters['status'] ?? null,
            'tanggal_tagihan' => $filters['tanggal_tagihan'] ?? null,
            'kode' => $filters['kode'] ?? null,
            'counterparty' => $filters['counterparty'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        return [
            'filters' => $filters,
            'stats' => $stats,
            'listQuery' => $q,
            'periodeLabel' => $periodeLabel,
            'exportQuery' => $exportQuery,
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

    /**
     * @return array{dari: Carbon, sampai: Carbon, mode: string, selectedDate: string, selectedMonth: string, selectedYear: int, selectedDari: string, selectedSampai: string}
     */
    protected function resolveTagihanPeriod(Request $request): array
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
            } catch (\Throwable) {
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
            } catch (\Throwable) {
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
            } catch (\Throwable) {
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
