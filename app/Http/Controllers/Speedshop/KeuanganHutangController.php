<?php

namespace App\Http\Controllers\Speedshop;

use App\Http\Controllers\Concerns\ResolvesKeuanganContext;
use App\Http\Controllers\Controller;
use App\Models\Speedshop;
use App\Models\SpeedshopKeuanganRekening;
use App\Models\Supplier;
use App\Models\WarehouseSupplierTagihan;
use App\Models\WarehouseSupplierTagihanPembayaran;
use App\Services\SpeedshopFinanceService;
use App\Services\WarehouseSupplierTagihanService;
use App\Support\DashboardPeriodResolver;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use League\Flysystem\UnableToWriteFile;

class KeuanganHutangController extends Controller
{
    use ResolvesKeuanganContext;

    public function index(Request $request): View|RedirectResponse
    {
        $this->ensureHutangTablesReady();

        $scope = $this->resolveKeuanganScope();
        if (! $scope || ! $scope->isSpeedshop()) {
            return redirect()->route($this->keuanganDashboardRoute())
                ->with('error', $this->keuanganScopeErrorMessage());
        }

        app(WarehouseSupplierTagihanService::class)->syncMissingForSpeedshop((int) $scope->speedshopId);

        $ctx = $this->buildListContext($request, (int) $scope->speedshopId);
        $supplierGroups = $this->buildSupplierGroupsQuery((int) $scope->speedshopId, $ctx['filters'], $ctx['dari'], $ctx['sampai'])
            ->orderByRaw('CASE WHEN COALESCE(unpaid_agg.jumlah_belum_lunas, 0) > 0 THEN 0 ELSE 1 END')
            ->orderBy('unpaid_agg.jatuh_tempo_terdekat')
            ->orderByDesc('total_belum_lunas')
            ->orderBy('suppliers.nama')
            ->paginate(15)
            ->withQueryString();

        return view('speedshop.keuangan.hutang.index', [
            'supplierGroups' => $supplierGroups,
            'filters' => $ctx['filters'],
            'stats' => $ctx['stats'],
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

    public function show(WarehouseSupplierTagihan $tagihan, SpeedshopFinanceService $finance): View|RedirectResponse
    {
        $this->ensureHutangTablesReady();

        $scope = $this->requireKeuanganScope();
        $this->authorizeTagihan($tagihan, (int) $scope->speedshopId);

        $tagihan->load([
            'supplier',
            'warehouseTransaksi.items.product',
            'items.product',
            'pembayarans.dibayarByUser',
            'pembayarans.sumberRekening',
            'dibayarByUser',
        ]);

        $asOfYmd = now()->toDateString();
        $rekenings = $scope->applyToRekening(SpeedshopKeuanganRekening::query())
            ->whereIn('jenis', [
                SpeedshopKeuanganRekening::JENIS_REKENING_UTAMA,
                SpeedshopKeuanganRekening::JENIS_KAS_BESAR,
            ])
            ->orderBy('jenis')
            ->orderBy('nama')
            ->get();

        $saldoRekeningMap = [];
        foreach ($rekenings as $rek) {
            $pocket = $finance->pocketForRekeningJenis($rek->jenis ?? null);
            $saldoRekeningMap[(int) $rek->id] = $finance->balanceForBankPocketAsOfScope(
                $scope,
                $pocket,
                (int) $rek->id,
                $asOfYmd,
            );
        }

        return view('speedshop.keuangan.hutang.show', [
            'tagihan' => $tagihan,
            'rekenings' => $rekenings,
            'saldoRekeningMap' => $saldoRekeningMap,
            'canPay' => auth()->user()?->isAccounting() && $tagihan->isBelumLunas() && $tagihan->sisaHutang() > 0,
        ]);
    }

    public function showSupplier(Supplier $supplier): View|RedirectResponse
    {
        $this->ensureHutangTablesReady();

        $scope = $this->requireKeuanganScope();
        $speedshopId = (int) $scope->speedshopId;

        $hasTagihan = WarehouseSupplierTagihan::query()
            ->forSpeedshopHutang($speedshopId)
            ->where('supplier_id', $supplier->id)
            ->exists();
        if (! $hasTagihan) {
            abort(404);
        }

        $tagihans = WarehouseSupplierTagihan::query()
            ->with(['warehouseTransaksi', 'supplier'])
            ->forSpeedshopHutang($speedshopId)
            ->where('supplier_id', $supplier->id)
            ->orderByRaw("CASE status WHEN 'belum_lunas' THEN 0 ELSE 1 END")
            ->orderByDesc('tanggal_tagihan')
            ->orderByDesc('id')
            ->get();

        $summary = [
            'jumlah_invoice' => $tagihans->count(),
            'jumlah_belum_lunas' => $tagihans->where('status', WarehouseSupplierTagihan::STATUS_BELUM_LUNAS)->count(),
            'jumlah_lunas' => $tagihans->where('status', WarehouseSupplierTagihan::STATUS_LUNAS)->count(),
            'total_sisa' => (int) $tagihans->sum(fn (WarehouseSupplierTagihan $t) => $t->sisaHutang()),
            'total_nominal' => (int) $tagihans->sum('total_nominal'),
        ];

        return view('speedshop.keuangan.hutang.supplier', [
            'supplier' => $supplier,
            'tagihans' => $tagihans,
            'summary' => $summary,
        ]);
    }

    public function bayar(Request $request, WarehouseSupplierTagihan $tagihan, SpeedshopFinanceService $finance): RedirectResponse
    {
        $this->ensureHutangTablesReady();

        abort_unless(auth()->user()?->isAccounting(), 403, 'Hanya Accounting yang dapat mencatat pembayaran hutang.');

        $scope = $this->requireKeuanganScope();
        $this->authorizeTagihan($tagihan, (int) $scope->speedshopId);

        $locked = WarehouseSupplierTagihan::query()->whereKey($tagihan->id)->firstOrFail();
        if ($locked->isLunas() || $locked->sisaHutang() <= 0) {
            return back()->with('error', 'Invoice ini sudah lunas.');
        }

        $sisa = $locked->sisaHutang();
        $data = $request->validate([
            'nominal' => ['required', 'integer', 'min:1', 'max:'.$sisa],
            'keuangan_rekening_id' => ['required', 'integer'],
            'jenis_pembayaran' => ['required', 'string', 'in:tunai,transfer'],
            'catatan_pembayaran' => ['nullable', 'string', 'max:1000'],
            'bukti_bayar' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $rekening = SpeedshopKeuanganRekening::query()->findOrFail((int) $data['keuangan_rekening_id']);
        if (! $scope->ownsRekening($rekening)) {
            return back()->with('error', 'Rekening tidak termasuk outlet ini.');
        }

        $path = $this->storeBuktiBayar($request, (int) $scope->speedshopId);
        if ($path === false) {
            return back()->with('error', 'Gagal mengunggah bukti pembayaran.');
        }
        if (! is_string($path) || $path === '') {
            return back()->with('error', 'Bukti pembayaran wajib diunggah.');
        }

        $nominal = (int) $data['nominal'];
        $jenisPembayaran = strtolower(trim((string) $data['jenis_pembayaran']));
        $catatan = filled($data['catatan_pembayaran'] ?? null) ? trim((string) $data['catatan_pembayaran']) : null;
        $paidAt = now();

        try {
            DB::transaction(function () use ($scope, $finance, $locked, $rekening, $nominal, $jenisPembayaran, $path, $catatan, $paidAt) {
                $row = WarehouseSupplierTagihan::query()->whereKey($locked->id)->lockForUpdate()->firstOrFail();
                $sisaNow = $row->sisaHutang();
                if ($sisaNow <= 0) {
                    throw new \InvalidArgumentException('Invoice ini sudah lunas.');
                }
                if ($nominal > $sisaNow) {
                    throw new \InvalidArgumentException('Nominal melebihi sisa hutang.');
                }

                $pembayaranAttrs = [
                    'tagihan_id' => $row->id,
                    'nominal' => $nominal,
                    'speedshop_keuangan_rekening_id' => (int) $rekening->id,
                    'bukti_bayar_path' => $path,
                    'catatan_pembayaran' => $catatan,
                    'dibayar_at' => $paidAt,
                    'dibayar_by' => auth()->id(),
                ];
                if (Schema::hasColumn('warehouse_supplier_tagihan_pembayarans', 'jenis_pembayaran')) {
                    $pembayaranAttrs['jenis_pembayaran'] = $jenisPembayaran;
                }

                $pembayaran = WarehouseSupplierTagihanPembayaran::query()->create($pembayaranAttrs);

                $newTotalDibayar = $row->totalDibayar() + $nominal;
                $lunas = $newTotalDibayar >= (int) $row->total_nominal;

                $row->update([
                    'total_dibayar' => $newTotalDibayar,
                    'status' => $lunas ? WarehouseSupplierTagihan::STATUS_LUNAS : WarehouseSupplierTagihan::STATUS_BELUM_LUNAS,
                    'dibayar_at' => $lunas ? $paidAt : $row->dibayar_at,
                    'dibayar_by' => $lunas ? auth()->id() : $row->dibayar_by,
                    'catatan_pembayaran' => $lunas ? ($catatan ?? $row->catatan_pembayaran) : $row->catatan_pembayaran,
                    'bukti_bayar_path' => $lunas ? $path : $row->bukti_bayar_path,
                    'speedshop_keuangan_rekening_id' => $lunas ? (int) $rekening->id : $row->speedshop_keuangan_rekening_id,
                ]);

                $finance->recordHutangSupplierKeluarForScope(
                    $scope,
                    $row->fresh(),
                    $rekening,
                    $paidAt->toDateString(),
                    $nominal,
                    (int) auth()->id(),
                    $pembayaran,
                );
            });
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        $locked->refresh();
        $sisaBaru = $locked->sisaHutang();
        $label = $locked->warehouseTransaksi?->kode ?? $locked->kode;
        $message = $sisaBaru > 0
            ? 'Pembayaran cicilan Rp '.number_format($nominal, 0, ',', '.').' untuk '.$label.' berhasil. Sisa hutang Rp '.number_format($sisaBaru, 0, ',', '.').'.'
            : 'Invoice '.$label.' lunas. Pembayaran Rp '.number_format($nominal, 0, ',', '.').' berhasil dicatat.';

        return redirect()
            ->route('speedshop.keuangan.hutang.show', $locked)
            ->with('success', $message);
    }

    private function ensureHutangTablesReady(): void
    {
        if (! Schema::hasTable('warehouse_supplier_tagihans')
            || ! Schema::hasColumn('warehouse_supplier_tagihans', 'speedshop_id')) {
            abort(503, 'Migrasi hutang speedshop belum dijalankan.');
        }
    }

    private function authorizeTagihan(WarehouseSupplierTagihan $tagihan, int $speedshopId): void
    {
        if ((int) ($tagihan->speedshop_id ?? 0) !== $speedshopId) {
            abort(403);
        }
    }

    private function storeBuktiBayar(Request $request, int $speedshopId): string|false|null
    {
        if (! $request->hasFile('bukti_bayar')) {
            return null;
        }

        try {
            $disk = (string) config('filesystems.upload_disk', 'public');
            $sp = Speedshop::query()->find($speedshopId);
            $outletCode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $sp?->invoiceOutletCode() ?: 'UNK'));
            $prefix = 'speedshop/'.$outletCode.'-'.$speedshopId.'/hutang-supplier/bukti';

            return $request->file('bukti_bayar')->store($prefix, $disk);
        } catch (UnableToWriteFile) {
            return false;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildListContext(Request $request, int $speedshopId): array
    {
        $period = DashboardPeriodResolver::fromRequest($request, 'bulanan');
        $dari = $period['dari'];
        $sampai = $period['sampai'];

        $filters = Validator::make($request->only(['status', 'kode', 'counterparty']), [
            'status' => ['nullable', 'string', 'in:belum_lunas,lunas,semua'],
            'kode' => ['nullable', 'string', 'max:80'],
            'counterparty' => ['nullable', 'string', 'max:120'],
        ])->validate();

        $q = WarehouseSupplierTagihan::query()
            ->forSpeedshopHutang($speedshopId)
            ->whereDate('tanggal_tagihan', '>=', $dari->toDateString())
            ->whereDate('tanggal_tagihan', '<=', $sampai->toDateString());

        if (! empty($filters['kode'])) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $filters['kode']).'%';
            $q->where(function ($inner) use ($term) {
                $inner->where('kode', 'like', $term)
                    ->orWhereHas('warehouseTransaksi', fn ($t) => $t->where('kode', 'like', $term));
            });
        }
        if (! empty($filters['counterparty'])) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $filters['counterparty']).'%';
            $q->whereHas('supplier', fn ($s) => $s->where('nama', 'like', $term));
        }

        $statsQuery = clone $q;
        $stats = [
            'total' => (int) (clone $statsQuery)->distinct()->count('supplier_id'),
            'belum_lunas' => (int) (clone $statsQuery)->where('status', WarehouseSupplierTagihan::STATUS_BELUM_LUNAS)->distinct()->count('supplier_id'),
            'lunas' => (int) (clone $statsQuery)->where('status', WarehouseSupplierTagihan::STATUS_LUNAS)->count(),
            'nominal_belum_lunas' => (int) (clone $statsQuery)
                ->aktifBelumLunas()
                ->toBase()
                ->selectRaw('COALESCE(SUM('.WarehouseSupplierTagihan::sqlSisaHutang().'), 0) as sisa')
                ->value('sisa'),
        ];

        return [
            'filters' => $filters,
            'stats' => $stats,
            'mode' => $period['mode'],
            'selectedDate' => $period['selectedDate'],
            'selectedMonth' => $period['selectedMonth'],
            'selectedYear' => $period['selectedYear'],
            'selectedDari' => $period['selectedDari'],
            'selectedSampai' => $period['selectedSampai'],
            'dari' => $dari,
            'sampai' => $sampai,
        ];
    }

    /**
     * @param  array{status?: string, kode?: string, counterparty?: string}  $filters
     */
    private function buildSupplierGroupsQuery(int $speedshopId, array $filters, Carbon $dari, Carbon $sampai): \Illuminate\Database\Query\Builder
    {
        $periodFrom = $dari->toDateString();
        $periodTo = $sampai->toDateString();
        $sisaSql = 'GREATEST(CAST(wst.total_nominal AS SIGNED) - CAST(wst.total_dibayar AS SIGNED), 0)';
        $effectiveJt = WarehouseSupplierTagihan::sqlEffectiveJatuhTempo('wst');

        $periodTagihan = WarehouseSupplierTagihan::query()
            ->forSpeedshopHutang($speedshopId)
            ->whereDate('tanggal_tagihan', '>=', $periodFrom)
            ->whereDate('tanggal_tagihan', '<=', $periodTo);

        if (! empty($filters['kode'])) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $filters['kode']).'%';
            $periodTagihan->where(function ($inner) use ($term) {
                $inner->where('kode', 'like', $term)
                    ->orWhereHas('warehouseTransaksi', fn ($t) => $t->where('kode', 'like', $term));
            });
        }
        if (! empty($filters['counterparty'])) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $filters['counterparty']).'%';
            $periodTagihan->whereHas('supplier', fn ($s) => $s->where('nama', 'like', $term));
        }

        $periodSupplierQuery = (clone $periodTagihan)->select('warehouse_supplier_tagihans.supplier_id')->distinct();

        $globalAgg = DB::table('warehouse_supplier_tagihans as wst')
            ->select('wst.supplier_id')
            ->selectRaw('COALESCE(SUM('.$sisaSql.'), 0) as total_belum_lunas')
            ->selectRaw('COUNT(*) as jumlah_belum_lunas')
            ->selectRaw("MIN({$effectiveJt}) as jatuh_tempo_terdekat")
            ->where('wst.speedshop_id', $speedshopId)
            ->where('wst.status', WarehouseSupplierTagihan::STATUS_BELUM_LUNAS)
            ->where('wst.total_nominal', '>', 0)
            ->whereColumn('wst.total_dibayar', '<', 'wst.total_nominal')
            ->groupBy('wst.supplier_id');

        $groupsQuery = DB::query()
            ->fromSub($periodSupplierQuery->toBase(), 'period_suppliers')
            ->join('suppliers', 'suppliers.id', '=', 'period_suppliers.supplier_id')
            ->leftJoinSub($globalAgg, 'unpaid_agg', fn ($join) => $join->on('unpaid_agg.supplier_id', '=', 'suppliers.id'))
            ->select([
                'suppliers.id as supplier_id',
                'suppliers.nama as supplier_nama',
                'suppliers.kode_supplier as supplier_kode',
                DB::raw('COALESCE(unpaid_agg.total_belum_lunas, 0) as total_belum_lunas'),
                DB::raw('COALESCE(unpaid_agg.jumlah_belum_lunas, 0) as jumlah_belum_lunas'),
                DB::raw("(
                    SELECT COUNT(*)
                    FROM warehouse_supplier_tagihans wstc
                    WHERE wstc.supplier_id = suppliers.id
                    AND wstc.speedshop_id = {$speedshopId}
                    AND DATE(wstc.tanggal_tagihan) >= '{$periodFrom}'
                    AND DATE(wstc.tanggal_tagihan) <= '{$periodTo}'
                ) as jumlah_invoice"),
                'unpaid_agg.jatuh_tempo_terdekat',
            ]);

        $statusFilter = (string) ($filters['status'] ?? '');
        if ($statusFilter === 'lunas') {
            $groupsQuery->having('jumlah_belum_lunas', '=', 0)
                ->having('jumlah_invoice', '>', 0);
        } elseif ($statusFilter === 'belum_lunas') {
            $groupsQuery->having('jumlah_belum_lunas', '>', 0);
        }

        return $groupsQuery;
    }
}
