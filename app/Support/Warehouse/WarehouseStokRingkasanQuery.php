<?php

namespace App\Support\Warehouse;

use App\Models\TransaksiOnline;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

class WarehouseStokRingkasanQuery
{
    /**
     * Total stok keluar per produk warehouse:
     * - semua stock_movements (keluar)
     * - plus transaksi/mutasi yang mengurangi stok tapi belum punya baris movement (data lama)
     */
    public static function withStokKeluarTotal(Builder $query, ?int $warehouseId): Builder
    {
        $movement = self::movementKeluarSubquery();
        $online = self::onlineKeluarWithoutMovementSubquery($warehouseId);
        $offline = self::offlineKeluarWithoutMovementSubquery($warehouseId);
        $mutasi = self::mutasiWarehouseKeluarWithoutMovementSubquery();

        return $query
            ->select('products.*')
            ->selectRaw(
                '(('.$movement->toSql().') + ('.$online->toSql().') + ('.$offline->toSql().') + ('.$mutasi->toSql().')) as stok_keluar_total',
                array_merge(
                    $movement->getBindings(),
                    $online->getBindings(),
                    $offline->getBindings(),
                    $mutasi->getBindings(),
                )
            );
    }

    /** Semua pergerakan stok keluar tercatat (Stock Out, Offline, PO, Claim, arsip, import, dll.). */
    private static function movementKeluarSubquery(): QueryBuilder
    {
        return DB::table('stock_movements')
            ->whereColumn('stock_movements.product_id', 'products.id')
            ->where('stock_movements.type', 'keluar')
            ->selectRaw('COALESCE(SUM(stock_movements.qty), 0)');
    }

    /** Stock Out (transaksi online selesai) tanpa baris movement. */
    private static function onlineKeluarWithoutMovementSubquery(?int $warehouseId): QueryBuilder
    {
        return DB::table('transaksi_online_items')
            ->join('transaksi_online', 'transaksi_online.id', '=', 'transaksi_online_items.transaksi_online_id')
            ->whereColumn('transaksi_online_items.product_id', 'products.id')
            ->where(function ($q) {
                $q->where('transaksi_online.status', TransaksiOnline::STATUS_SELESAI)
                    ->orWhereNull('transaksi_online.status');
            })
            ->when($warehouseId !== null, fn ($q) => $q->where('transaksi_online.warehouse_id', $warehouseId))
            ->whereNotExists(function ($q) {
                self::applyMovementReferenceNotExists($q, 'transaksi_online', 'transaksi_online.id');
            })
            ->selectRaw('COALESCE(SUM(transaksi_online_items.qty), 0)');
    }

    /** Transaksi offline disetujui manager tanpa baris movement. */
    private static function offlineKeluarWithoutMovementSubquery(?int $warehouseId): QueryBuilder
    {
        return DB::table('transaksi_offline_items')
            ->join('transaksi_offline', 'transaksi_offline.id', '=', 'transaksi_offline_items.transaksi_offline_id')
            ->whereColumn('transaksi_offline_items.product_id', 'products.id')
            ->whereNotNull('transaksi_offline.manager_approval_at')
            ->when($warehouseId !== null, fn ($q) => $q->where('transaksi_offline.warehouse_id', $warehouseId))
            ->whereNotExists(function ($q) {
                self::applyMovementReferenceNotExists($q, 'transaksi_offline', 'transaksi_offline.id');
            })
            ->selectRaw('COALESCE(SUM(transaksi_offline_items.qty), 0)');
    }

    /**
     * Mutasi kirim dari warehouse ke speedshop (kurangi stok gudang sumber)
     * tanpa baris movement — mis. menu Mutasi ke Speedshop.
     */
    private static function mutasiWarehouseKeluarWithoutMovementSubquery(): QueryBuilder
    {
        return DB::table('mutasi_items')
            ->join('mutasis', 'mutasis.id', '=', 'mutasi_items.mutasi_id')
            ->whereColumn('mutasi_items.product_id', 'products.id')
            ->where('mutasis.sumber', 'warehouse')
            ->whereNull('mutasis.rejected_at')
            ->where('mutasis.status', '!=', 'ditolak')
            ->whereNotExists(function ($q) {
                self::applyMovementReferenceNotExists($q, 'mutasi', 'mutasis.id');
            })
            ->selectRaw('COALESCE(SUM(mutasi_items.quantity), 0)');
    }

    private static function applyMovementReferenceNotExists(QueryBuilder $query, string $prefix, string $idColumn): void
    {
        $query->select(DB::raw(1))
            ->from('stock_movements')
            ->whereColumn('stock_movements.product_id', 'products.id')
            ->where('stock_movements.type', 'keluar')
            ->whereRaw("stock_movements.reference = CONCAT('{$prefix}:', {$idColumn})");
    }

    public static function stokSekarangValue(mixed $product): int
    {
        $jumlah = $product instanceof \App\Models\Product
            ? $product->getAttribute('jumlah')
            : $product;

        if ($jumlah === null || $jumlah === '') {
            return 0;
        }

        return max(0, (int) $jumlah);
    }

    public static function stokKeluarValue(mixed $product): int
    {
        $total = $product instanceof \App\Models\Product
            ? $product->getAttribute('stok_keluar_total')
            : $product;

        if ($total === null || $total === '') {
            return 0;
        }

        return max(0, (int) $total);
    }
}
