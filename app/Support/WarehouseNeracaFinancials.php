<?php

namespace App\Support;

use App\Models\Product;
use App\Models\SpeedshopKeuanganRekening;
use App\Models\SpeedshopPoTagihan;
use App\Models\Warehouse;
use App\Models\WarehouseAsetTetap;
use App\Models\WarehouseNeracaMarketplaceInput;
use App\Models\WarehousePiutang;
use App\Models\WarehouseReseller;
use App\Models\WarehouseResellerPiutang;
use App\Models\WarehouseSupplierTagihan;
use App\Services\SpeedshopFinanceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

final class WarehouseNeracaFinancials
{
    /** @var list<array{key: string, label: string}> */
    private const MARKETPLACE_GROUPS = [
        ['key' => 'shopee_mod', 'label' => 'Shopee'],
        ['key' => 'tiktok', 'label' => 'TikTok'],
        ['key' => 'lazada', 'label' => 'Lazada'],
        ['key' => 'blibli_mod', 'label' => 'Blibli'],
    ];

    /**
     * @return array{
     *     rows: list<array{no: string, label: string, kind: string, amount: ?float, depth: int, is_percent: bool}>,
     *     summary: array<string, float>,
     *     meta: array<string, string>
     * }
     */
    public static function totalModalBersihAsOf(Carbon $asOf, ?int $warehouseId): float
    {
        return self::stokModalValue($warehouseId);
    }

    /** Nilai modal stok on hand (jumlah × HPP) — selaras kartu Modal di /warehouse/products. */
    public static function stokModalValue(?int $warehouseId): float
    {
        if (! $warehouseId) {
            return 0.0;
        }

        return (float) Product::query()
            ->where('warehouse_id', $warehouseId)
            ->where('speedshop_scope_id', 0)
            ->visibleInWarehouseMasterKatalog()
            ->selectRaw('COALESCE(SUM(COALESCE(jumlah, 0) * COALESCE(hpp, 0)), 0) as total')
            ->value('total');
    }

    /**
     * @return array<string, float>
     */
    public static function summaryAsOf(Carbon $asOf, ?int $warehouseId): array
    {
        $asOfYmd = $asOf->toDateString();
        $finance = app(SpeedshopFinanceService::class);
        $scope = $warehouseId ? KeuanganScope::forWarehouse($warehouseId) : null;

        $kasRows = self::kasSaldoRows($scope, $finance, $asOfYmd);
        $totalKas = array_sum(array_column($kasRows, 'amount'));

        $piutang = self::piutangTotals($warehouseId);
        $totalPiutang = array_sum($piutang);

        $persediaan = self::stokModalValue($warehouseId);
        $marketplace = self::marketplaceBalancesForPeriod($warehouseId, $asOf);
        $totalMarketplace = self::totalMarketplaceFromBalances($marketplace);
        $pemasukanLainnya = self::pemasukanLainnyaTotal($warehouseId);
        $asetTetap = self::asetTetapTotals($warehouseId, $asOf);
        $totalAsetTetap = (float) $asetTetap['rak'] + (float) $asetTetap['inventaris'];

        $totalAsetLancar = $totalKas + $totalPiutang + $persediaan + $totalMarketplace + $pemasukanLainnya;
        $totalAset = $totalAsetLancar + $totalAsetTetap;
        $totalHutang = self::totalHutangUsaha($warehouseId);
        $totalModalStok = $persediaan;

        return [
            'total_kas' => $totalKas,
            'total_piutang' => $totalPiutang,
            'persediaan' => $persediaan,
            'total_marketplace' => $totalMarketplace,
            'pemasukan_lainnya' => $pemasukanLainnya,
            'total_aset_lancar' => $totalAsetLancar,
            'total_aset_tetap' => $totalAsetTetap,
            'total_aset' => $totalAset,
            'total_kewajiban' => $totalHutang,
            'total_modal' => $totalModalStok,
        ];
    }

    /**
     * @return array{
     *     rows: list<array{no: string, label: string, kind: string, amount: ?float, depth: int, is_percent: bool}>,
     *     summary: array<string, float>,
     *     meta: array<string, string>
     * }
     */
    public static function presentation(Carbon $asOf, ?int $warehouseId): array
    {
        $asOfYmd = $asOf->toDateString();
        $finance = app(SpeedshopFinanceService::class);
        $scope = $warehouseId ? KeuanganScope::forWarehouse($warehouseId) : null;

        $summary = self::summaryAsOf($asOf, $warehouseId);
        $totalKas = $summary['total_kas'];
        $totalPiutang = $summary['total_piutang'];
        $persediaan = $summary['persediaan'];
        $zakatStok = $persediaan * 0.025;
        $totalMarketplace = $summary['total_marketplace'];
        $pemasukanLainnya = $summary['pemasukan_lainnya'];
        $totalAsetTetap = $summary['total_aset_tetap'];
        $totalAset = $summary['total_aset'];
        $totalHutang = $summary['total_kewajiban'];
        $totalModalStok = (float) ($summary['total_modal'] ?? self::stokModalValue($warehouseId));

        $kasRows = self::kasSaldoRows($scope, $finance, $asOfYmd);
        $piutang = self::piutangTotals($warehouseId);
        $marketplace = self::marketplaceBalancesForPeriod($warehouseId, $asOf);
        $totalMarketplace = self::totalMarketplaceFromBalances($marketplace);
        $asetTetap = self::asetTetapTotals($warehouseId, $asOf);

        $rows = [];
        $push = function (
            string $no,
            string $label,
            string $kind,
            ?float $amount = null,
            int $depth = 0,
            ?array $editable = null,
        ) use (&$rows): void {
            $row = [
                'no' => $no,
                'label' => WarehouseLabaRugiFinancials::formatLabaRugiLabel($label),
                'kind' => $kind,
                'amount' => $amount,
                'depth' => $depth,
                'is_percent' => false,
            ];
            if ($editable !== null) {
                $row['editable'] = $editable;
            }
            $rows[] = $row;
        };

        $push('', 'Aset Lancar', 'section');

        $push('1', 'Saldo Kas 77racing', 'sub', $totalKas, 0);
        foreach ($kasRows as $kasRow) {
            $push('', $kasRow['label'], 'line', (float) $kasRow['amount'], 1);
        }

        $push('2', 'Piutang Usaha', 'sub', $totalPiutang, 0);
        $push('', 'Piutang Online Reseller', 'line', (float) $piutang['online'], 1);
        $push('', 'Piutang Canvassing Reseller', 'line', (float) $piutang['canvassing'], 1);
        $push('', 'Piutang Reseller Offline', 'line', (float) $piutang['offline'], 1);
        $push('', 'Piutang Reseller X1 Racing', 'line', (float) $piutang['x1_racing'], 1);
        $push('', 'Piutang Speedshop', 'line', (float) $piutang['speedshop'], 1);

        $push('3', 'Persediaan Barang Stok On Hand', 'sub', $persediaan, 0);
        $push('', 'Zakat 2,5% Dari Stok On Hand (Tahunan)', 'line', $zakatStok, 1);

        $push('4', 'Pendapatan Marketplace Belum Cair', 'sub', $totalMarketplace, 0);
        foreach (self::MARKETPLACE_GROUPS as $group) {
            $key = $group['key'];
            $groupTotal = (float) ($marketplace[$key]['total'] ?? 0);
            $push('', $group['label'], 'line', $groupTotal, 1);
            $push('', 'Total Saldo', 'line', (float) ($marketplace[$key]['saldo'] ?? 0), 2, [
                'marketplace_key' => $key,
                'field' => WarehouseNeracaMarketplaceInput::FIELD_SALDO,
            ]);
            $push('', 'Dana Bisa Ditarik', 'line', (float) ($marketplace[$key]['bisa_ditarik'] ?? 0), 2, [
                'marketplace_key' => $key,
                'field' => WarehouseNeracaMarketplaceInput::FIELD_BISA_DITARIK,
            ]);
            $push('', 'Dana Pending', 'line', (float) ($marketplace[$key]['pending'] ?? 0), 2, [
                'marketplace_key' => $key,
                'field' => WarehouseNeracaMarketplaceInput::FIELD_PENDING,
            ]);
        }

        $push('5', 'Pemasukan Lainnya', 'line', $pemasukanLainnya, 0);

        $push('', 'Aset Tetap', 'section');
        $push('', 'Rak / Etalase', 'line', (float) $asetTetap['rak'], 0);
        $push('', 'Inventaris / Aset Peralatan', 'line', (float) $asetTetap['inventaris'], 0);

        $push('', 'Total Aset (Aset Lancar + Aset Tetap)', 'grand', $totalAset);

        $push('', 'Kewajiban', 'section');
        $push('', 'Utang Usaha', 'line', $totalHutang, 0);
        $push('', 'Total Kewajiban', 'subtotal', $totalHutang);

        $push('', 'Modal', 'section');
        $push('', 'Total Modal (Nilai HPP Stok)', 'grand', $totalModalStok);

        $push('', 'Valuasi 77racing Online', 'footer', $totalModalStok);

        return [
            'rows' => $rows,
            'summary' => $summary,
            'meta' => self::businessMeta($warehouseId),
        ];
    }

    /** @return array<string, string> */
    private static function businessMeta(?int $warehouseId): array
    {
        $warehouse = $warehouseId
            ? Warehouse::query()->with('kota')->find($warehouseId)
            : null;

        return [
            'nama_usaha' => $warehouse?->nama ?: '77racing Online',
            'jenis_usaha' => 'Sparepart Motor',
            'tahun_berdiri' => '2019',
            'pemilik' => 'Bpk. Wahyu Saimanuddin, S.T',
            'channel_penjualan' => 'Marketplace',
            'lokasi_usaha' => $warehouse?->kota?->nama ?: 'Kab. Bandung',
        ];
    }

    /**
     * @return list<array{label: string, amount: float}>
     */
    private static function kasSaldoRows(?KeuanganScope $scope, SpeedshopFinanceService $finance, string $asOfYmd): array
    {
        if ($scope === null || ! Schema::hasTable('speedshop_keuangan_rekenings')) {
            return [];
        }

        $rekenings = $scope->applyToRekening(SpeedshopKeuanganRekening::query())
            ->whereIn('jenis', [
                SpeedshopKeuanganRekening::JENIS_REKENING_UTAMA,
                SpeedshopKeuanganRekening::JENIS_REKENING_REGULER,
            ])
            ->orderBy('jenis')
            ->orderBy('nama')
            ->get();

        $rows = [];
        foreach ($rekenings as $rekening) {
            $pocket = $finance->pocketForRekeningJenis($rekening->jenis ?? null);
            $saldo = (float) $finance->balanceForBankPocketAsOfScope(
                $scope,
                $pocket,
                (int) $rekening->id,
                $asOfYmd,
            );

            $rows[] = [
                'label' => self::rekeningKasLabel($rekening),
                'amount' => $saldo,
            ];
        }

        $laci = (float) $finance->balancePocketAsOfScope($scope, SpeedshopFinanceService::POCKET_LACI, $asOfYmd);
        $rows[] = [
            'label' => 'Kas Tunai',
            'amount' => $laci,
        ];

        $orphanReguler = (float) $finance->balanceUnallocatedPocketAsOfScope(
            $scope,
            SpeedshopFinanceService::POCKET_CASH_BESAR,
            $asOfYmd,
        );
        if ($orphanReguler > 0) {
            $rows[] = [
                'label' => 'Saldo Rekening Reguler (Belum Dialokasi)',
                'amount' => $orphanReguler,
            ];
        }

        return $rows;
    }

    private static function rekeningKasLabel(SpeedshopKeuanganRekening $rekening): string
    {
        $bank = trim((string) ($rekening->nama_bank ?? ''));
        $nama = trim((string) ($rekening->nama ?? ''));
        $isReguler = SpeedshopKeuanganRekening::isSecondaryRekeningJenis($rekening->jenis ?? null);

        if ($bank !== '' && $isReguler) {
            return 'Kas '.$bank.' Reguler (BOP)';
        }

        if ($bank !== '') {
            return 'Kas '.$bank.' Utama';
        }

        if ($nama !== '') {
            return $isReguler ? 'Kas '.$nama.' Reguler (BOP)' : 'Kas '.$nama.' Utama';
        }

        return $isReguler ? 'Kas Reguler (BOP)' : 'Kas Utama';
    }

    /**
     * @return array{online: float, canvassing: float, offline: float, x1_racing: float, speedshop: float}
     */
    private static function piutangTotals(?int $warehouseId): array
    {
        $totals = [
            'online' => 0.0,
            'canvassing' => 0.0,
            'offline' => 0.0,
            'x1_racing' => 0.0,
            'speedshop' => 0.0,
        ];

        if ($warehouseId && Schema::hasTable('warehouse_reseller_piutangs')) {
            WarehouseResellerPiutang::query()
                ->aktifBelumLunas()
                ->where('warehouse_id', $warehouseId)
                ->with('warehouseReseller')
                ->get()
                ->each(function (WarehouseResellerPiutang $piutang) use (&$totals): void {
                    $bucket = self::piutangBucket($piutang->warehouseReseller);
                    $totals[$bucket] += (float) $piutang->sisaPiutang();
                });
        }

        if ($warehouseId && Schema::hasTable('speedshop_po_tagihans')) {
            $totals['speedshop'] += (float) SpeedshopPoTagihan::query()
                ->where('warehouse_id', $warehouseId)
                ->whereIn('status', [
                    SpeedshopPoTagihan::STATUS_BELUM_LUNAS,
                    SpeedshopPoTagihan::STATUS_DIBAYAR,
                ])
                ->sum('total_nominal');
        }

        return $totals;
    }

    private static function piutangBucket(?WarehouseReseller $reseller): string
    {
        if (! $reseller) {
            return 'online';
        }

        // Baris neraca khusus X1 Racing — prioritas di atas kategori canvassing/offline.
        if (self::isX1RacingReseller($reseller)) {
            return 'x1_racing';
        }

        $kategori = (string) ($reseller->kategori ?? '');

        if ($kategori === WarehouseReseller::KATEGORI_CANVASSING) {
            return 'canvassing';
        }

        if ($kategori === WarehouseReseller::KATEGORI_OFFLINE) {
            return 'offline';
        }

        return 'online';
    }

    private static function isX1RacingReseller(WarehouseReseller $reseller): bool
    {
        $nama = trim((string) $reseller->nama);
        if ($nama === '') {
            return false;
        }

        // Selaras filter piutang (nama LIKE '%X1 Racing%'), bukan reseller *RACING* lain.
        return (bool) preg_match('/x1\s*racing/iu', $nama);
    }

    private static function persediaanValue(?int $warehouseId): float
    {
        return self::stokModalValue($warehouseId);
    }

    private static function pemasukanLainnyaTotal(?int $warehouseId): float
    {
        if (! $warehouseId || ! Schema::hasTable('warehouse_piutangs')) {
            return 0.0;
        }

        return (float) WarehousePiutang::query()
            ->forWarehouse($warehouseId)
            ->where('status', WarehousePiutang::STATUS_BELUM_LUNAS)
            ->sum('debet');
    }

    private static function totalHutangUsaha(?int $warehouseId): float
    {
        if (! $warehouseId || ! Schema::hasTable('warehouse_supplier_tagihans')) {
            return 0.0;
        }

        return (float) WarehouseSupplierTagihan::query()
            ->where('warehouse_id', $warehouseId)
            ->where('status', WarehouseSupplierTagihan::STATUS_BELUM_LUNAS)
            ->whereColumn('total_dibayar', '<', 'total_nominal')
            ->selectRaw('COALESCE(SUM('.WarehouseSupplierTagihan::sqlSisaHutang().'), 0) as total')
            ->value('total');
    }

    /**
     * @return array<string, array{saldo: float, bisa_ditarik: float, pending: float, total: float}>
     */
    private static function marketplaceBalancesForPeriod(?int $warehouseId, Carbon $asOf): array
    {
        $balances = self::emptyMarketplaceBalances();

        if (! $warehouseId || ! Schema::hasTable('warehouse_neraca_marketplace_inputs')) {
            return $balances;
        }

        $periodeBulan = $asOf->format('Y-m');
        $rows = WarehouseNeracaMarketplaceInput::query()
            ->where('warehouse_id', $warehouseId)
            ->where('periode_bulan', $periodeBulan)
            ->get(['marketplace_key', 'field', 'nominal']);

        foreach ($rows as $row) {
            $key = (string) $row->marketplace_key;
            $field = (string) $row->field;
            if (! isset($balances[$key]) || ! in_array($field, WarehouseNeracaMarketplaceInput::FIELDS, true)) {
                continue;
            }
            $balances[$key][$field] = (float) $row->nominal;
        }

        foreach (self::MARKETPLACE_GROUPS as $group) {
            $key = $group['key'];
            $balances[$key]['total'] = (float) $balances[$key]['saldo']
                + (float) $balances[$key]['bisa_ditarik']
                + (float) $balances[$key]['pending'];
        }

        return $balances;
    }

    /** @param  array<string, array{saldo: float, bisa_ditarik: float, pending: float, total: float}>  $balances */
    private static function totalMarketplaceFromBalances(array $balances): float
    {
        return array_sum(array_map(
            static fn (array $item): float => (float) ($item['total'] ?? 0),
            $balances,
        ));
    }

    /**
     * @return array<string, array{saldo: float, bisa_ditarik: float, pending: float, total: float}>
     */
    private static function emptyMarketplaceBalances(): array
    {
        $empty = ['saldo' => 0.0, 'bisa_ditarik' => 0.0, 'pending' => 0.0, 'total' => 0.0];
        $result = [];
        foreach (self::MARKETPLACE_GROUPS as $group) {
            $result[$group['key']] = $empty;
        }

        return $result;
    }

    /** @return array{rak: float, inventaris: float} */
    private static function asetTetapTotals(?int $warehouseId, Carbon $asOf): array
    {
        if (! $warehouseId || ! Schema::hasTable('warehouse_aset_tetaps')) {
            return self::emptyAsetTetap();
        }

        $asOfYmd = $asOf->toDateString();

        $base = WarehouseAsetTetap::query()
            ->where('warehouse_id', $warehouseId)
            ->where(function ($q) use ($asOfYmd) {
                $q->whereNull('tanggal_perolehan')
                    ->orWhereDate('tanggal_perolehan', '<=', $asOfYmd);
            });

        return [
            'rak' => (float) (clone $base)->where('kategori', WarehouseAsetTetap::KATEGORI_RAK)->sum('nilai_buku'),
            'inventaris' => (float) (clone $base)->where('kategori', WarehouseAsetTetap::KATEGORI_INVENTARIS)->sum('nilai_buku'),
        ];
    }

    /** @return array{rak: float, inventaris: float} */
    private static function emptyAsetTetap(): array
    {
        return [
            'rak' => 0.0,
            'inventaris' => 0.0,
        ];
    }

    public static function rowPaddingClass(array $row): string
    {
        return match ((int) ($row['depth'] ?? 0)) {
            0 => 'pl-3',
            1 => 'pl-9',
            2 => 'pl-[3.25rem]',
            3 => 'pl-[4.75rem]',
            default => 'pl-24',
        };
    }
}
