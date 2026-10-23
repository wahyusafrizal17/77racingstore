<?php

namespace App\Support\Warehouse;

use App\Models\Product;
use App\Support\ProductMasterCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Query inventori produk warehouse — satu sumber untuk list, export, dan PDF.
 */
final class WarehouseInventoriProductQuery
{
    /**
     * @param  list<int>  $outletIds
     */
    public static function applyCatalogScope(
        Builder $query,
        string $catalogKind,
        ?int $catalogOutletId,
        array $outletIds = [],
        bool $collapseDuplicateKode = true,
    ): Builder {
        if (in_array($catalogKind, [ProductMasterCatalog::SPEEDSHOP_SUPPLIER, ProductMasterCatalog::PART_LUAR], true)) {
            $ids = self::normalizedOutletIds($outletIds);
            if (count($ids) > 1) {
                $query->forSpeedshopStokCatalogAny($ids, $catalogKind);
                if ($collapseDuplicateKode) {
                    $query->collapseToCanonicalKode();
                }

                return $query;
            }

            $single = $ids[0] ?? $catalogOutletId;

            return $single
                ? $query->forSpeedshopStokCatalog((int) $single, $catalogKind)
                : $query->whereRaw('0 = 1');
        }

        return $query->forWarehouseSharedCatalogBase($catalogKind);
    }

    /**
     * @param  list<int>  $speedshopIds
     */
    public static function applyOutletStockJoin(Builder $query, array $speedshopIds, ?string $catalogKind = null): Builder
    {
        if ($speedshopIds === []) {
            return $query;
        }

        if (
            $catalogKind
            && ! ProductMasterCatalog::isWarehouseSharedKind($catalogKind)
            && count($speedshopIds) > 1
        ) {
            $query->select('products.*')
                ->selectRaw(self::combinedOutletStockSql($speedshopIds, $catalogKind).' as speedshop_jumlah');

            return $query;
        }

        $stockSub = DB::table('speedshop_stock')
            ->selectRaw('product_id, SUM(jumlah) as speedshop_jumlah')
            ->whereIn('speedshop_id', $speedshopIds)
            ->groupBy('product_id');

        return $query
            ->leftJoinSub($stockSub, 'ss', 'products.id', '=', 'ss.product_id')
            ->select([
                'products.*',
                DB::raw('COALESCE(ss.speedshop_jumlah, 0) as speedshop_jumlah'),
            ]);
    }

    /**
     * Stok gabungan per kode produk di beberapa cabang (filter Semua Speedshop).
     *
     * @param  list<int>  $speedshopIds
     */
    public static function combinedOutletStockSql(array $speedshopIds, string $catalogKind): string
    {
        $ids = self::normalizedOutletIds($speedshopIds);
        if ($ids === []) {
            return '0';
        }

        $idList = implode(',', $ids);
        $jenisSql = $catalogKind === ProductMasterCatalog::PART_LUAR
            ? "sp.jenis_produk = 'part_luar'"
            : "(sp.jenis_produk = 'ori' OR sp.jenis_produk IS NULL)";

        return '(SELECT COALESCE(SUM(ss.jumlah), 0) FROM speedshop_stock ss'
            .' INNER JOIN products sp ON sp.id = ss.product_id AND sp.deleted_at IS NULL'
            .' WHERE ss.speedshop_id IN ('.$idList.')'
            .' AND sp.speedshop_scope_id IN ('.$idList.')'
            .' AND '.$jenisSql
            .' AND UPPER(TRIM(sp.kode_produk)) = UPPER(TRIM(products.kode_produk)))';
    }

    public static function applyStableListOrder(Builder $query): Builder
    {
        return $query
            ->orderByDesc('products.created_at')
            ->orderByDesc('products.id');
    }

    public static function applyExportOrder(Builder $query): Builder
    {
        return $query
            ->orderBy('products.nama_produk')
            ->orderBy('products.id');
    }

    public static function stockValue(Product $product, bool $usesOutletStock): int
    {
        if ($usesOutletStock) {
            return (int) ($product->speedshop_jumlah ?? 0);
        }

        return (int) ($product->jumlah ?? 0);
    }

    /** Class Tailwind badge stok inventori (warehouse & speedshop — MO, Produk Speedshop, Part Luar). */
    public static function stockBadgeClasses(int $qty): string
    {
        if ($qty <= 0) {
            return 'bg-red-100 text-red-700 hover:ring-red-400';
        }

        if ($qty < 10) {
            return 'bg-yellow-100 text-yellow-800 hover:ring-yellow-400';
        }

        return 'bg-green-100 text-green-700 hover:ring-green-400';
    }

    /** Katalog inventori yang selaras UI warehouse (urutan & badge stok). */
    public static function usesWarehouseInventoriStockUi(string $catalogKind): bool
    {
        return in_array($catalogKind, ProductMasterCatalog::kinds(), true);
    }

    /**
     * @param  list<int>  $ids
     * @return list<int>
     */
    private static function normalizedOutletIds(array $ids): array
    {
        return array_values(array_unique(array_filter(
            array_map('intval', $ids),
            fn (int $id) => $id > 0,
        )));
    }
}
