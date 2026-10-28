<?php

namespace App\Support;

use App\Models\AkunOperasional;
use App\Models\Invoice;
use App\Models\ServiceOrder;
use App\Models\SpeedshopRincianBiayaEntry;
use App\Models\TransaksiPenjualan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class SpeedshopLabaRugiFinancials
{
    /** @return list<int> */
    private static function effectiveSpeedshopIds(?int $speedshopId, array $speedshopIds = []): array
    {
        if ($speedshopIds !== []) {
            return array_values(array_unique(array_map('intval', $speedshopIds)));
        }

        return $speedshopId ? [(int) $speedshopId] : [];
    }

    /** @param  \Illuminate\Database\Eloquent\Builder<ServiceOrder>  $query */
    private static function applyServiceOrderScope($query, ?int $speedshopId, ?int $warehouseId, array $speedshopIds = []): void
    {
        $ids = self::effectiveSpeedshopIds($speedshopId, $speedshopIds);

        if ($ids !== [] && Schema::hasColumn('service_orders', 'speedshop_id')) {
            if (count($ids) === 1) {
                $query->where('speedshop_id', $ids[0]);
            } else {
                $query->whereIn('speedshop_id', $ids);
            }

            return;
        }

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }
    }

    /** @param  \Illuminate\Database\Query\Builder  $query */
    private static function applyServiceOrderTableScope($query, ?int $speedshopId, ?int $warehouseId, array $speedshopIds = []): void
    {
        $ids = self::effectiveSpeedshopIds($speedshopId, $speedshopIds);

        if ($ids !== [] && Schema::hasColumn('service_orders', 'speedshop_id')) {
            if (count($ids) === 1) {
                $query->where('service_orders.speedshop_id', $ids[0]);
            } else {
                $query->whereIn('service_orders.speedshop_id', $ids);
            }

            return;
        }

        if ($warehouseId) {
            $query->where('service_orders.warehouse_id', $warehouseId);
        }
    }

    /** @param  \Illuminate\Database\Eloquent\Builder<\App\Models\TransaksiPenjualan>  $query */
    private static function applyTransaksiPenjualanScope($query, ?int $speedshopId, ?int $warehouseId, array $speedshopIds = []): void
    {
        $ids = self::effectiveSpeedshopIds($speedshopId, $speedshopIds);

        if ($ids !== [] && Schema::hasColumn('users', 'speedshop_id')) {
            $query->whereHas('user', function ($u) use ($ids) {
                if (count($ids) === 1) {
                    $u->where('speedshop_id', $ids[0]);
                } else {
                    $u->whereIn('speedshop_id', $ids);
                }
            });

            return;
        }

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }
    }

    /** @param  \Illuminate\Database\Query\Builder  $query */
    private static function applyTransaksiPenjualanJoinScope($query, ?int $speedshopId, ?int $warehouseId, array $speedshopIds = []): void
    {
        $ids = self::effectiveSpeedshopIds($speedshopId, $speedshopIds);

        if ($ids !== [] && Schema::hasColumn('users', 'speedshop_id')) {
            if (count($ids) === 1) {
                $query->where('users.speedshop_id', $ids[0]);
            } else {
                $query->whereIn('users.speedshop_id', $ids);
            }

            return;
        }

        if ($warehouseId) {
            $query->where('transaksi_penjualan.warehouse_id', $warehouseId);
        }
    }

    /**
     * Hitung komponen laba rugi — selaras dashboard accounting & rekap pendapatan.
     *
     * @param  list<int>  $speedshopIds
     * @return array<string, float|int>
     */
    public static function compute(
        Carbon $dari,
        Carbon $sampai,
        ?int $speedshopId,
        ?int $warehouseId,
        array $speedshopIds = [],
    ): array {
        $dariYmd = $dari->toDateString();
        $sampaiYmd = $sampai->toDateString();

        $invoices = self::invoicesForPeriod($dari, $sampai, $speedshopId, $warehouseId, $speedshopIds);

        $pendapatanJasa = (float) $invoices->sum('total_jasa');
        $partOliPendapatan = self::partOliPendapatanBreakdown($dari, $sampai, $speedshopId, $warehouseId, $speedshopIds);
        $pendapatanSparepart = (float) $partOliPendapatan['sparepart'];
        $pendapatanOli = (float) $partOliPendapatan['oli'];
        $pendapatanTotal = (float) $invoices->sum('grand_total');
        $pendapatanCharge = self::sumChargeFromInvoices($invoices);
        $pendapatanPenjualanLangsung = (float) $partOliPendapatan['langsung_sparepart']
            + (float) $partOliPendapatan['langsung_oli'];

        $biayaBreakdown = self::biayaBreakdown($dari, $sampai, $speedshopId, $warehouseId, $speedshopIds);
        $biayaHppSparepart = (float) $biayaBreakdown['hpp_sparepart'];
        $biayaHppOli = (float) ($biayaBreakdown['hpp_oli'] ?? 0);
        $biayaOperasional = (float) $biayaBreakdown['biaya_operasional'];
        $biayaNonOperasional = (float) $biayaBreakdown['biaya_non_operasional'];

        return [
            'pendapatan_jasa' => $pendapatanJasa,
            'pendapatan_sparepart' => $pendapatanSparepart,
            'pendapatan_oli' => $pendapatanOli,
            'pendapatan_total_invoice' => $pendapatanTotal,
            'pendapatan_charge' => $pendapatanCharge,
            'pendapatan_penjualan_langsung' => $pendapatanPenjualanLangsung,
            'biaya_hpp_sparepart' => $biayaHppSparepart,
            'biaya_hpp_oli' => $biayaHppOli,
            'biaya_operasional' => $biayaOperasional,
            'biaya_non_operasional' => $biayaNonOperasional,
            'total_biaya' => $biayaHppSparepart + $biayaHppOli + $biayaOperasional + $biayaNonOperasional,
            'jumlah_invoice' => $invoices->count(),
        ];
    }

    /**
     * Pisahkan pendapatan sparepart vs oli (WO selesai + penjualan langsung) berdasarkan kategori produk.
     *
     * @param  list<int>  $speedshopIds
     * @return array{
     *     sparepart: float,
     *     oli: float,
     *     wo_sparepart: float,
     *     wo_oli: float,
     *     langsung_sparepart: float,
     *     langsung_oli: float,
     * }
     */
    public static function partOliPendapatanBreakdown(
        Carbon $dari,
        Carbon $sampai,
        ?int $speedshopId,
        ?int $warehouseId,
        array $speedshopIds = [],
    ): array {
        $dariYmd = $dari->toDateString();
        $sampaiYmd = $sampai->toDateString();
        $transaksiDate = ServiceOrder::transaksiDateSql('service_orders');
        $isOli = self::sqlCategoryIsOli('categories');
        $woLine = '(service_order_products.qty * service_order_products.harga) - COALESCE(service_order_products.diskon, 0)';

        $woQuery = DB::table('service_order_products')
            ->join('service_orders', 'service_order_products.service_order_id', '=', 'service_orders.id')
            ->join('products', 'service_order_products.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->where('service_orders.status', 'selesai')
            ->whereRaw("{$transaksiDate} BETWEEN ? AND ?", [$dariYmd, $sampaiYmd]);

        self::applyServiceOrderTableScope($woQuery, $speedshopId, $warehouseId, $speedshopIds);

        $woTotals = $woQuery->selectRaw("
            SUM(CASE WHEN {$isOli} THEN {$woLine} ELSE 0 END) as oli,
            SUM(CASE WHEN NOT ({$isOli}) THEN {$woLine} ELSE 0 END) as sparepart
        ")->first();

        $woOli = (float) ($woTotals->oli ?? 0);
        $woSparepart = (float) ($woTotals->sparepart ?? 0);

        $tpLine = 'transaksi_penjualan_items.qty * transaksi_penjualan_items.harga_satuan';
        $tpQuery = DB::table('transaksi_penjualan_items')
            ->join('transaksi_penjualan', 'transaksi_penjualan_items.transaksi_penjualan_id', '=', 'transaksi_penjualan.id')
            ->join('users', 'transaksi_penjualan.user_id', '=', 'users.id')
            ->join('products', 'transaksi_penjualan_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->whereBetween('transaksi_penjualan.created_at', [$dari, $sampai]);

        self::applyTransaksiPenjualanJoinScope($tpQuery, $speedshopId, $warehouseId, $speedshopIds);

        $tpTotals = $tpQuery->selectRaw("
            SUM(CASE WHEN {$isOli} THEN {$tpLine} ELSE 0 END) as oli,
            SUM(CASE WHEN NOT ({$isOli}) THEN {$tpLine} ELSE 0 END) as sparepart
        ")->first();

        $tpOli = (float) ($tpTotals->oli ?? 0);
        $tpSparepart = (float) ($tpTotals->sparepart ?? 0);

        return [
            'sparepart' => $woSparepart + $tpSparepart,
            'oli' => $woOli + $tpOli,
            'wo_sparepart' => $woSparepart,
            'wo_oli' => $woOli,
            'langsung_sparepart' => $tpSparepart,
            'langsung_oli' => $tpOli,
        ];
    }

    /**
     * @param  list<int>  $speedshopIds
     * @return array{sparepart: float, oli: float}
     */
    public static function partOliHppBreakdown(
        Carbon $dari,
        Carbon $sampai,
        ?int $speedshopId,
        ?int $warehouseId,
        array $speedshopIds = [],
    ): array {
        $dariYmd = $dari->toDateString();
        $sampaiYmd = $sampai->toDateString();
        $transaksiDate = ServiceOrder::transaksiDateSql('service_orders');
        $isOli = self::sqlCategoryIsOli('categories');
        $woHppLine = '(COALESCE(service_order_products.hpp, NULLIF(products.hpp, 0), products.harga_pembelian, 0)) * ';
        $hppLine = '(COALESCE(NULLIF(products.hpp, 0), products.harga_pembelian, 0)) * ';

        $woQuery = DB::table('service_order_products')
            ->join('service_orders', 'service_order_products.service_order_id', '=', 'service_orders.id')
            ->join('products', 'service_order_products.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->where('service_orders.status', 'selesai')
            ->whereRaw("{$transaksiDate} BETWEEN ? AND ?", [$dariYmd, $sampaiYmd]);

        self::applyServiceOrderTableScope($woQuery, $speedshopId, $warehouseId, $speedshopIds);

        $woTotals = $woQuery->selectRaw("
            SUM(CASE WHEN {$isOli} THEN {$woHppLine}service_order_products.qty ELSE 0 END) as oli,
            SUM(CASE WHEN NOT ({$isOli}) THEN {$woHppLine}service_order_products.qty ELSE 0 END) as sparepart
        ")->first();

        $tpQuery = DB::table('transaksi_penjualan_items')
            ->join('transaksi_penjualan', 'transaksi_penjualan_items.transaksi_penjualan_id', '=', 'transaksi_penjualan.id')
            ->join('users', 'transaksi_penjualan.user_id', '=', 'users.id')
            ->join('products', 'transaksi_penjualan_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->whereBetween('transaksi_penjualan.created_at', [$dari, $sampai]);

        self::applyTransaksiPenjualanJoinScope($tpQuery, $speedshopId, $warehouseId, $speedshopIds);

        $tpTotals = $tpQuery->selectRaw("
            SUM(CASE WHEN {$isOli} THEN {$hppLine}transaksi_penjualan_items.qty ELSE 0 END) as oli,
            SUM(CASE WHEN NOT ({$isOli}) THEN {$hppLine}transaksi_penjualan_items.qty ELSE 0 END) as sparepart
        ")->first();

        return [
            'sparepart' => (float) ($woTotals->sparepart ?? 0) + (float) ($tpTotals->sparepart ?? 0),
            'oli' => (float) ($woTotals->oli ?? 0) + (float) ($tpTotals->oli ?? 0),
        ];
    }

    private static function sqlCategoryIsOli(string $categoryAlias = 'categories'): string
    {
        return "LOWER(TRIM(COALESCE({$categoryAlias}.nama, ''))) LIKE 'oli%'";
    }

    /**
     * Total diskon WO selesai per periode — dipakai baris 5.3.3 (selaras verifikasi diskon / invoice).
     *
     * @param  list<int>  $speedshopIds
     * @return array{jasa: float, sparepart: float, oli: float, total: float}
     */
    public static function diskonBreakdownForPeriod(
        Carbon $dari,
        Carbon $sampai,
        ?int $speedshopId,
        ?int $warehouseId,
        array $speedshopIds = [],
    ): array {
        $dariYmd = $dari->toDateString();
        $sampaiYmd = $sampai->toDateString();
        $transaksiDate = ServiceOrder::transaksiDateSql('service_orders');
        $isOli = self::sqlCategoryIsOli('categories');

        $jasaQuery = DB::table('service_order_jasa')
            ->join('service_orders', 'service_order_jasa.service_order_id', '=', 'service_orders.id')
            ->where('service_orders.status', 'selesai')
            ->whereRaw("{$transaksiDate} BETWEEN ? AND ?", [$dariYmd, $sampaiYmd]);

        self::applyServiceOrderTableScope($jasaQuery, $speedshopId, $warehouseId, $speedshopIds);

        $jasaDiskon = (float) ($jasaQuery->selectRaw(
            'COALESCE(SUM(COALESCE(service_order_jasa.diskon, 0)), 0) as total'
        )->value('total') ?? 0);

        $prodQuery = DB::table('service_order_products')
            ->join('service_orders', 'service_order_products.service_order_id', '=', 'service_orders.id')
            ->join('products', 'service_order_products.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->where('service_orders.status', 'selesai')
            ->whereRaw("{$transaksiDate} BETWEEN ? AND ?", [$dariYmd, $sampaiYmd]);

        self::applyServiceOrderTableScope($prodQuery, $speedshopId, $warehouseId, $speedshopIds);

        $prodTotals = $prodQuery->selectRaw("
            SUM(CASE WHEN {$isOli} THEN COALESCE(service_order_products.diskon, 0) ELSE 0 END) as oli,
            SUM(CASE WHEN NOT ({$isOli}) THEN COALESCE(service_order_products.diskon, 0) ELSE 0 END) as sparepart
        ")->first();

        $oliDiskon = (float) ($prodTotals->oli ?? 0);
        $sparepartDiskon = (float) ($prodTotals->sparepart ?? 0);

        return [
            'jasa' => $jasaDiskon,
            'sparepart' => $sparepartDiskon,
            'oli' => $oliDiskon,
            'total' => $jasaDiskon + $sparepartDiskon + $oliDiskon,
        ];
    }

    /**
     * Invoice WO selesai dalam periode (tanggal transaksi).
     *
     * @param  list<int>  $speedshopIds
     * @return \Illuminate\Support\Collection<int, Invoice>
     */
    private static function invoicesForPeriod(
        Carbon $dari,
        Carbon $sampai,
        ?int $speedshopId,
        ?int $warehouseId,
        array $speedshopIds = [],
    ) {
        $dariYmd = $dari->toDateString();
        $sampaiYmd = $sampai->toDateString();

        $columns = ['total_jasa', 'total_sparepart', 'grand_total'];
        if (Schema::hasColumn('invoices', 'charge_amount')) {
            $columns = array_merge($columns, [
                'charge_amount',
                'charge_mode',
                'charge_percent',
                'charge_rupiah',
            ]);
        }
        if (Schema::hasColumn('invoices', 'diskon')) {
            $columns[] = 'diskon';
        }

        return Invoice::query()
            ->whereHas('serviceOrder', function ($q) use ($speedshopId, $warehouseId, $speedshopIds, $dariYmd, $sampaiYmd) {
                $q->where('status', 'selesai')
                    ->whereTransaksiDateBetween($dariYmd, $sampaiYmd);
                self::applyServiceOrderScope($q, $speedshopId, $warehouseId, $speedshopIds);
            })
            ->get($columns);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Invoice>  $invoices
     */
    private static function sumChargeFromInvoices($invoices): float
    {
        $sum = 0.0;

        foreach ($invoices as $invoice) {
            $sum += self::resolveInvoiceChargeAmount($invoice);
        }

        return $sum;
    }

    private static function resolveInvoiceChargeAmount(Invoice $invoice): float
    {
        $stored = (int) ($invoice->charge_amount ?? 0);
        if ($stored > 0) {
            return (float) $stored;
        }

        if (! Schema::hasColumn('invoices', 'charge_mode')) {
            return 0.0;
        }

        $base = max(0, (int) ($invoice->total_jasa ?? 0) + (int) ($invoice->total_sparepart ?? 0));
        if (Schema::hasColumn('invoices', 'diskon')) {
            $base = max(0, $base - (int) ($invoice->diskon ?? 0));
        }

        $mode = (string) ($invoice->charge_mode ?? 'percent');

        return (float) \App\Services\InvoiceSettingsService::resolveChargeAmountForInvoice($invoice, $base);
    }

    /**
     * @return array{hpp_sparepart: float, hpp_oli: float, biaya_operasional: float, biaya_non_operasional: float}
     */
    public static function biayaBreakdown(
        Carbon $dari,
        Carbon $sampai,
        ?int $speedshopId,
        ?int $warehouseId,
        array $speedshopIds = [],
    ): array {
        $hpp = self::partOliHppBreakdown($dari, $sampai, $speedshopId, $warehouseId, $speedshopIds);

        return [
            'hpp_sparepart' => (float) $hpp['sparepart'],
            'hpp_oli' => (float) $hpp['oli'],
            'biaya_operasional' => self::rincianBiayaTotal(
                $dari,
                $sampai,
                $speedshopId,
                $warehouseId,
                SpeedshopRincianBiayaEntry::JENIS_BIAYA_OPERASIONAL,
                $speedshopIds,
            ),
            'biaya_non_operasional' => self::rincianBiayaTotal(
                $dari,
                $sampai,
                $speedshopId,
                $warehouseId,
                SpeedshopRincianBiayaEntry::JENIS_BIAYA_NON_OPERASIONAL,
                $speedshopIds,
            ),
        ];
    }

    /**
     * Total biaya operasional / non operasional — selaras halaman Keuangan → Biaya operasional.
     */
    public static function rincianBiayaTotal(
        Carbon $dari,
        Carbon $sampai,
        ?int $speedshopId,
        ?int $warehouseId,
        string $jenis,
        array $speedshopIds = [],
    ): float {
        return array_sum(self::rincianBiayaByPaymentKey($dari, $sampai, $speedshopId, $warehouseId, $jenis, $speedshopIds));
    }

    /**
     * Rincian biaya per metode pembayaran (tunai / transfer) — selaras kolom Keuangan → Biaya operasional.
     *
     * @return array{cash: float, transfer: float, qris: float, edc: float}
     */
    public static function rincianBiayaByPaymentKey(
        Carbon $dari,
        Carbon $sampai,
        ?int $speedshopId,
        ?int $warehouseId,
        string $jenis,
        array $speedshopIds = [],
    ): array {
        $totals = ['cash' => 0.0, 'transfer' => 0.0, 'qris' => 0.0, 'edc' => 0.0];

        $query = self::rincianBiayaApprovedQuery($dari, $sampai, $speedshopId, $warehouseId, $jenis, $speedshopIds);
        if ($query === null) {
            return $totals;
        }

        $nominalSql = SpeedshopRincianBiayaEntry::sqlNominalEfektif();

        $rows = $query
            ->selectRaw(
                "CASE WHEN metode = 'transfer' THEN 'transfer' ELSE 'cash' END as payment_key, SUM({$nominalSql}) as total"
            )
            ->groupBy('payment_key')
            ->pluck('total', 'payment_key');

        foreach ($rows as $paymentKey => $total) {
            $key = $paymentKey === 'transfer' ? 'transfer' : 'cash';
            $totals[$key] += (float) $total;
        }

        return $totals;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<SpeedshopRincianBiayaEntry>|null
     */
    private static function rincianBiayaApprovedQuery(
        Carbon $dari,
        Carbon $sampai,
        ?int $speedshopId,
        ?int $warehouseId,
        string $jenis,
        array $speedshopIds = [],
    ) {
        if (! Schema::hasTable('speedshop_rincian_biaya_entries')) {
            return null;
        }

        $ids = self::effectiveSpeedshopIds($speedshopId, $speedshopIds);

        if ($ids === [] && $warehouseId) {
            $ids = \App\Models\Speedshop::query()
                ->where(function ($q) use ($warehouseId) {
                    $q->where('mutasi_warehouse_id', $warehouseId)
                        ->orWhere('warehouse_id', $warehouseId);
                })
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        if ($ids === []) {
            return null;
        }

        $query = SpeedshopRincianBiayaEntry::query()
            ->when(
                count($ids) === 1,
                fn ($q) => $q->where('speedshop_id', $ids[0]),
                fn ($q) => $q->whereIn('speedshop_id', $ids),
            )
            ->whereBetween('tanggal', [$dari->toDateString(), $sampai->toDateString()])
            ->where(function ($q) {
                $q->whereNull('approval_status')
                    ->orWhere('approval_status', SpeedshopRincianBiayaEntry::STATUS_APPROVED);
            });

        if ($jenis === SpeedshopRincianBiayaEntry::JENIS_BIAYA_NON_OPERASIONAL) {
            $query->where('jenis_operasional', SpeedshopRincianBiayaEntry::JENIS_BIAYA_NON_OPERASIONAL);
        } else {
            $query->where(function ($q) {
                $q->where('jenis_operasional', SpeedshopRincianBiayaEntry::JENIS_BIAYA_OPERASIONAL)
                    ->orWhereNull('jenis_operasional');
            });
        }

        return $query;
    }

    /**
     * HPP sparepart dari WO selesai (tanggal transaksi = tanggal selesai).
     * Stok WO tidak lewat transaksi_penjualan, hanya speedshop_stock.
     */
    public static function hppSparepartFromWorkOrders(
        Carbon $dari,
        Carbon $sampai,
        ?int $speedshopId,
        ?int $warehouseId,
        array $speedshopIds = [],
    ): float {
        $dariYmd = $dari->toDateString();
        $sampaiYmd = $sampai->toDateString();
        $transaksiDate = ServiceOrder::transaksiDateSql('service_orders');

        $query = DB::table('service_order_products')
            ->join('service_orders', 'service_order_products.service_order_id', '=', 'service_orders.id')
            ->join('products', 'service_order_products.product_id', '=', 'products.id')
            ->where('service_orders.status', 'selesai')
            ->whereRaw("{$transaksiDate} BETWEEN ? AND ?", [$dariYmd, $sampaiYmd]);

        self::applyServiceOrderTableScope($query, $speedshopId, $warehouseId, $speedshopIds);

        return (float) ($query->selectRaw(
            'SUM((COALESCE(service_order_products.hpp, NULLIF(products.hpp, 0), products.harga_pembelian, 0)) * service_order_products.qty) as total'
        )->value('total') ?? 0);
    }

    /**
     * Pendapatan penjualan part/oli langsung (bukan WO) — selaras partOliPendapatanBreakdown.
     *
     * @param  list<int>  $speedshopIds
     */
    public static function pendapatanPenjualanLangsung(
        Carbon $dari,
        Carbon $sampai,
        ?int $speedshopId,
        ?int $warehouseId,
        array $speedshopIds = [],
    ): float {
        $breakdown = self::partOliPendapatanBreakdown($dari, $sampai, $speedshopId, $warehouseId, $speedshopIds);

        return (float) ($breakdown['langsung_sparepart'] ?? 0) + (float) ($breakdown['langsung_oli'] ?? 0);
    }

    /**
     * HPP dari penjualan part langsung (bukan WO).
     */
    public static function hppSparepartFromPenjualanLangsung(
        Carbon $dari,
        Carbon $sampai,
        ?int $speedshopId,
        ?int $warehouseId,
        array $speedshopIds = [],
    ): float {
        $query = DB::table('transaksi_penjualan_items')
            ->join('transaksi_penjualan', 'transaksi_penjualan_items.transaksi_penjualan_id', '=', 'transaksi_penjualan.id')
            ->join('users', 'transaksi_penjualan.user_id', '=', 'users.id')
            ->join('products', 'transaksi_penjualan_items.product_id', '=', 'products.id');

        self::applyTransaksiPenjualanJoinScope($query, $speedshopId, $warehouseId, $speedshopIds);

        return (float) $query
            ->whereBetween('transaksi_penjualan.created_at', [$dari, $sampai])
            ->selectRaw('SUM((COALESCE(NULLIF(products.hpp, 0), products.harga_pembelian, 0)) * transaksi_penjualan_items.qty) as total')
            ->value('total') ?? 0;
    }

    /**
     * Baris metrik laporan Pendapatan & Biaya (selaras dashboard).
     *
     * @return array<string, float|int>
     */
    public static function pendapatanBiayaRow(
        Carbon $dari,
        Carbon $sampai,
        ?int $speedshopId,
        ?int $warehouseId,
        array $speedshopIds = [],
    ): array {
        $f = self::compute($dari, $sampai, $speedshopId, $warehouseId, $speedshopIds);

        $pendapatanJasa = (float) $f['pendapatan_jasa'];
        $pendapatanPart = (float) $f['pendapatan_sparepart'] + (float) ($f['pendapatan_oli'] ?? 0);
        $hppPart = (float) $f['biaya_hpp_sparepart'] + (float) ($f['biaya_hpp_oli'] ?? 0);
        $bopOperasional = (float) $f['biaya_operasional'];
        $bopNonOperasional = (float) $f['biaya_non_operasional'];
        $omzetKotor = (float) $f['pendapatan_total_invoice'] + (float) $f['pendapatan_penjualan_langsung'];

        $profitPart = $pendapatanPart - $hppPart;
        $diskon = 0.0;
        $profit = $pendapatanJasa + $profitPart - $diskon;
        $profitBersih = $omzetKotor - $hppPart - $bopOperasional - $bopNonOperasional;

        return [
            'omzet_kotor' => $omzetKotor,
            'unit_entry' => self::unitEntryCount($dari, $sampai, $speedshopId, $warehouseId, $speedshopIds),
            'jasa' => $pendapatanJasa,
            'part' => $pendapatanPart,
            'hpp_part' => $hppPart,
            'profit_part' => $profitPart,
            'diskon' => $diskon,
            'profit' => $profit,
            'bop_operasional' => $bopOperasional,
            'bop_non_operasional' => $bopNonOperasional,
            'profit_bersih' => $profitBersih,
        ];
    }

    public static function unitEntryCount(
        Carbon $dari,
        Carbon $sampai,
        ?int $speedshopId,
        ?int $warehouseId,
        array $speedshopIds = [],
    ): int {
        $dariYmd = $dari->toDateString();
        $sampaiYmd = $sampai->toDateString();

        $soQuery = ServiceOrder::query();
        self::applyServiceOrderScope($soQuery, $speedshopId, $warehouseId, $speedshopIds);
        $soCount = $soQuery
            ->where('status', 'selesai')
            ->whereTransaksiDateBetween($dariYmd, $sampaiYmd)
            ->count();

        $tpQuery = TransaksiPenjualan::query();
        self::applyTransaksiPenjualanScope($tpQuery, $speedshopId, $warehouseId, $speedshopIds);
        $tpCount = $tpQuery
            ->whereBetween('created_at', [$dari, $sampai])
            ->count();

        return $soCount + $tpCount;
    }

    /**
     * @return array{
     *     speedshop_id: ?int,
     *     speedshop_ids: list<int>,
     *     warehouse_id: ?int,
     *     all_speedshops: bool
     * }
     */
    /**
     * Kedalaman hierarki baris laba rugi dari nomor akun (4. → 4.1 → 4.1.1).
     */
    public static function labaRugiRowDepth(array $row): int
    {
        $no = trim((string) ($row['no'] ?? ''));
        if ($no !== '') {
            return max(0, substr_count(rtrim($no, '.'), '.'));
        }

        return match ($row['kind'] ?? 'line') {
            'section' => 0,
            'sub' => 1,
            'line' => 2,
            'subtotal' => 1,
            default => 0,
        };
    }

    /** Padding kiri sel No./Keterangan agar sub-level tampak menjorok ke dalam. */
    public static function labaRugiRowPaddingClass(array $row): string
    {
        return match (self::labaRugiRowDepth($row)) {
            0 => 'pl-3',
            1 => 'pl-9',
            2 => 'pl-[3.25rem]',
            3 => 'pl-[4.75rem]',
            default => 'pl-24',
        };
    }

    public static function resolveScopeIds(): array
    {
        $user = auth()->user();
        $warehouseId = $user?->activeWarehouseId();

        if ($user?->isWarehouseAccounting()) {
            return [
                'speedshop_id' => null,
                'speedshop_ids' => [],
                'warehouse_id' => $warehouseId ? (int) $warehouseId : null,
                'all_speedshops' => false,
            ];
        }

        $speedshopIds = $user?->reportSpeedshopFilterIds() ?? [];

        if ($user?->isOwner() && request()->filled('speedshop_id')) {
            $speedshopId = (int) request()->input('speedshop_id');
            $allowedIds = $user->activeSpeedshopIds();
            if ($speedshopId > 0 && in_array($speedshopId, $allowedIds, true)) {
                $speedshopIds = [$speedshopId];
            }
        }

        return [
            'speedshop_id' => count($speedshopIds) === 1 ? (int) $speedshopIds[0] : null,
            'speedshop_ids' => $speedshopIds,
            'warehouse_id' => $warehouseId ? (int) $warehouseId : null,
            'all_speedshops' => count($speedshopIds) > 1,
        ];
    }

    /**
     * Total rincian biaya per no_akun (hanya entri disetujui / tercatat).
     *
     * @return array<string, float>
     */
    public static function rincianBiayaTotalsByNoAkun(
        Carbon $dari,
        Carbon $sampai,
        ?int $speedshopId,
        ?int $warehouseId,
        string $noAkunPrefix,
        array $speedshopIds = [],
    ): array {
        $query = self::rincianBiayaApprovedQuery(
            $dari,
            $sampai,
            $speedshopId,
            $warehouseId,
            $noAkunPrefix === '5.3.'
                ? SpeedshopRincianBiayaEntry::JENIS_BIAYA_NON_OPERASIONAL
                : SpeedshopRincianBiayaEntry::JENIS_BIAYA_OPERASIONAL,
            $speedshopIds,
        );

        if ($query === null) {
            return [];
        }

        $nominalSql = SpeedshopRincianBiayaEntry::sqlNominalEfektif();

        return $query
            ->where('no_akun', 'like', $noAkunPrefix.'%')
            ->selectRaw("no_akun, SUM({$nominalSql}) as total")
            ->groupBy('no_akun')
            ->pluck('total', 'no_akun')
            ->map(fn ($v) => (float) $v)
            ->all();
    }

    /**
     * Urutkan nomor akun hierarkis secara numerik (5.2.9 sebelum 5.2.10, bukan sort string).
     */
    public static function compareAccountNumbers(string $a, string $b): int
    {
        $partsA = array_map('intval', explode('.', $a));
        $partsB = array_map('intval', explode('.', $b));
        $len = max(count($partsA), count($partsB));

        for ($i = 0; $i < $len; $i++) {
            $va = $partsA[$i] ?? 0;
            $vb = $partsB[$i] ?? 0;
            if ($va !== $vb) {
                return $va <=> $vb;
            }
        }

        return 0;
    }

    /**
     * Template baris biaya operasional speedshop (selaras Excel / rincian laba rugi).
     *
     * @return list{array{0: string, 1: string}}
     */
    public static function speedshopBiayaOperasionalTemplate(): array
    {
        return [
            ['5.2.1', 'Gaji Karyawan'],
            ['5.2.2', 'THR Karyawan'],
            ['5.2.3', 'Bonus Karyawan'],
            ['5.2.4', 'Gaji Management'],
            ['5.2.5', 'THR Management'],
            ['5.2.6', 'Bonus Management'],
            ['5.2.7', 'Biaya Operasional Kantor'],
            ['5.2.8', 'Promosi'],
            ['5.2.9', 'Merchandise'],
            ['5.2.10', 'Pembelian Tools & Perlengkapan'],
            ['5.2.11', 'Perawatan Tools & Perlengkapan'],
            ['5.2.12', 'Depresiasi Tools & Perlengkapan'],
            ['5.2.13', 'Renovasi Bangunan'],
            ['5.2.14', 'Sewa Bangunan'],
            ['5.2.15', 'Pembelian Part Luar'],
            ['5.2.16', 'Jasa Pekerjaan Part Luar'],
            ['5.2.17', 'Biaya Admin Bank'],
            ['5.2.18', 'Reimbursement'],
            ['5.2.19', 'Perjalanan Dinas'],
        ];
    }

    /**
     * @return list{array{0: string, 1: string}}
     */
    public static function speedshopBiayaNonOperasionalTemplate(): array
    {
        return [
            ['5.3.1', 'Biaya Kerugian'],
            ['5.3.2', 'Pengembalian Dana'],
            ['5.3.3', 'Diskon'],
        ];
    }

    /**
     * Pemetaan nomor akun lama (seeder awal) ke template laba rugi terbaru.
     *
     * @return array<string, string>
     */
    public static function legacyBiayaOperasionalAkunMap(): array
    {
        return [
            '5.2.3' => '5.2.7',
            '5.2.4' => '5.2.8',
            '5.2.5' => '5.2.9',
            '5.2.6' => '5.2.10',
            '5.2.7' => '5.2.11',
            '5.2.8' => '5.2.12',
            '5.2.9' => '5.2.13',
            '5.2.10' => '5.2.14',
            '5.2.11' => '5.2.15',
            '5.2.12' => '5.2.16',
            '5.2.13' => '5.2.17',
            '5.2.14' => '5.2.18',
        ];
    }

    /**
     * @param  array<string, float>  $byAkun
     * @return array<string, float>
     */
    public static function normalizeBiayaByAkunForTemplate(array $byAkun, array $legacyMap = []): array
    {
        $normalized = [];

        foreach ($byAkun as $noAkun => $amount) {
            $target = $legacyMap[(string) $noAkun] ?? (string) $noAkun;
            $normalized[$target] = ($normalized[$target] ?? 0.0) + (float) $amount;
        }

        return $normalized;
    }

    /**
     * Baris tabel laba rugi — kolom No. terpisah dari Keterangan (selaras template Excel).
     *
     * @return array{
     *     rows: list<array{no: string, label: string, kind: string, amount: ?float, is_percent: bool}>,
     *     summary: array<string, float>
     * }
     */
    public static function labaRugiPresentation(
        Carbon $dari,
        Carbon $sampai,
        ?int $speedshopId,
        ?int $warehouseId,
        array $speedshopIds = [],
    ): array {
        $f = self::compute($dari, $sampai, $speedshopId, $warehouseId, $speedshopIds);

        $diskonBreakdown = self::diskonBreakdownForPeriod($dari, $sampai, $speedshopId, $warehouseId, $speedshopIds);

        $biayaOpByAkun = self::normalizeBiayaByAkunForTemplate(
            self::rincianBiayaTotalsByNoAkun($dari, $sampai, $speedshopId, $warehouseId, '5.2.', $speedshopIds),
            self::legacyBiayaOperasionalAkunMap(),
        );
        $biayaNonOpByAkun = self::rincianBiayaTotalsByNoAkun($dari, $sampai, $speedshopId, $warehouseId, '5.3.', $speedshopIds);

        $diskonManual = (float) ($biayaNonOpByAkun['5.3.3'] ?? 0);
        $diskonOperasional = (float) $diskonBreakdown['total'];
        $diskonLine = $diskonOperasional + $diskonManual;

        // Pendapatan baris laba rugi ditampilkan gross; diskon WO dicatat terpisah di 5.3.3.
        $penjualanSparepart = (float) $f['pendapatan_sparepart'] + (float) $diskonBreakdown['sparepart'];
        $penjualanOli = (float) ($f['pendapatan_oli'] ?? 0) + (float) $diskonBreakdown['oli'];
        $jasaBengkel = (float) $f['pendapatan_jasa'] + (float) $diskonBreakdown['jasa'];
        $pendapatanCharge = (float) ($f['pendapatan_charge'] ?? 0);
        $biayaChargeKonsumen = $pendapatanCharge;
        $totalPendapatanNonOperasional = $biayaChargeKonsumen;
        $totalPendapatanOperasional = $penjualanSparepart + $penjualanOli + $jasaBengkel;
        $totalPendapatanAll = $totalPendapatanOperasional + $totalPendapatanNonOperasional;

        $hppSparepart = (float) $f['biaya_hpp_sparepart'];
        $hppOli = (float) ($f['biaya_hpp_oli'] ?? 0);
        $totalBiayaHppPartOli = $hppSparepart + $hppOli;

        $totalBiayaOperasional = (float) $f['biaya_operasional'];
        $totalBiayaNonOperasional = (float) ($biayaNonOpByAkun['5.3.1'] ?? 0)
            + (float) ($biayaNonOpByAkun['5.3.2'] ?? 0)
            + $diskonLine;
        $totalBiayaAll = $totalBiayaHppPartOli + $totalBiayaOperasional + $totalBiayaNonOperasional;

        $grossProfit = $totalPendapatanOperasional - $totalBiayaHppPartOli;
        $netProfitBeforeAdjustment = $totalPendapatanAll - $totalBiayaAll;

        $ppnPct = 10.0;
        $zakatPct = 2.5;
        $ppnNominal = max(0, $netProfitBeforeAdjustment) * ($ppnPct / 100);
        $zakat = max(0, $netProfitBeforeAdjustment) * ($zakatPct / 100);
        $netProfit = $netProfitBeforeAdjustment - $ppnNominal - $zakat;

        $pctHppToPendapatan = $totalPendapatanOperasional > 0
            ? ($totalBiayaHppPartOli / $totalPendapatanOperasional) * 100 : 0;
        $pctGrossToPendapatan = $totalPendapatanOperasional > 0
            ? ($grossProfit / $totalPendapatanOperasional) * 100 : 0;
        $pctBiayaToPendapatan = $totalPendapatanAll > 0
            ? ($totalBiayaAll / $totalPendapatanAll) * 100 : 0;
        $pctNetBeforeToPendapatan = $totalPendapatanAll > 0
            ? ($netProfitBeforeAdjustment / $totalPendapatanAll) * 100 : 0;
        $pctNetToPendapatan = $totalPendapatanAll > 0
            ? ($netProfit / $totalPendapatanAll) * 100 : 0;

        $rows = [];
        $push = function (
            string $no,
            string $label,
            string $kind,
            ?float $amount = null,
            bool $isPercent = false,
        ) use (&$rows) {
            $rows[] = [
                'no' => $no,
                'label' => $label,
                'kind' => $kind,
                'amount' => $amount,
                'is_percent' => $isPercent,
            ];
        };

        $push('4.', 'Pendapatan', 'section');
        $push('4.1', 'Pendapatan Operasional', 'sub');
        $push('4.1.1', 'Penjualan Sparepart', 'line', $penjualanSparepart);
        $push('4.1.2', 'Penjualan Oli', 'line', $penjualanOli);
        $push('4.1.3', 'Jasa Bengkel', 'line', $jasaBengkel);
        $push('', 'Total Pendapatan Operasional', 'subtotal', $totalPendapatanOperasional);

        $push('4.2', 'Pendapatan Non Operasional', 'sub');
        $push('4.2.1', 'Biaya Charge Konsumen', 'line', $biayaChargeKonsumen);
        $push('', 'Total Pendapatan Non Operasional', 'subtotal', $totalPendapatanNonOperasional);
        $push('', 'Total Pendapatan', 'grand', $totalPendapatanAll);

        $push('5.', 'Biaya', 'section');
        $push('5.1', 'Biaya HPP Part & Oli', 'sub');
        $push('5.1.1', 'Sparepart', 'line', $hppSparepart);
        $push('5.1.2', 'Oli', 'line', $hppOli);
        $push('', 'Total Biaya HPP Part & Oli', 'subtotal', $totalBiayaHppPartOli);
        $push('', 'Persentase HPP Part & Oli Dari Pendapatan Part & Oli', 'pct', $pctHppToPendapatan, true);

        $push('', 'Gross Profit', 'grand', $grossProfit);
        $push('', 'Persentase Gross Profit Dari Pendapatan', 'pct', $pctGrossToPendapatan, true);

        $push('5.2', 'Biaya Operasional', 'sub');
        foreach (self::speedshopBiayaOperasionalTemplate() as [$noAkun, $namaAkun]) {
            $push($noAkun, $namaAkun, 'line', (float) ($biayaOpByAkun[$noAkun] ?? 0));
        }
        $push('', 'Total Biaya Operasional', 'subtotal', $totalBiayaOperasional);

        $push('5.3', 'Biaya Non Operasional', 'sub');
        foreach (self::speedshopBiayaNonOperasionalTemplate() as [$noAkun, $namaAkun]) {
            $amount = $noAkun === '5.3.3'
                ? $diskonLine
                : (float) ($biayaNonOpByAkun[$noAkun] ?? 0);
            $push($noAkun, $namaAkun, 'line', $amount);
        }
        $push('', 'Total Biaya Non Operasional', 'subtotal', $totalBiayaNonOperasional);

        $push('', 'Total Biaya', 'grand', $totalBiayaAll);
        $push('', 'Persentase Biaya Dari Pendapatan', 'pct', $pctBiayaToPendapatan, true);

        $push('', 'Total Nett Profit Before PPN, Zakat, Etc', 'grand', $netProfitBeforeAdjustment);
        $push('', 'Persentase Nett Profit Dari Pendapatan', 'pct', $pctNetBeforeToPendapatan, true);

        $push('', 'Keterangan', 'ket');
        $push('', 'PPN ('.number_format($ppnPct, 2, ',', '.').'%)', 'line', $ppnNominal);
        $push('', 'Zakat ('.number_format($zakatPct, 2, ',', '.').'%)', 'line', $zakat);

        $push('', 'Nett Profit', 'footer', $netProfit);
        $push('', 'Persentase Nett Profit Dari Pendapatan', 'pct', $pctNetToPendapatan, true);

        return [
            'rows' => $rows,
            'summary' => [
                'total_pendapatan' => $totalPendapatanAll,
                'total_biaya' => $totalBiayaAll,
                'gross_profit' => $grossProfit,
                'nett_profit' => $netProfit,
                'pct_gross' => $pctGrossToPendapatan,
            ],
        ];
    }
}
