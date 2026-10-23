<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Produk katalog warehouse yang boleh dibersihkan (hard delete):
 * belum dipakai speedshop; riwayat warehouse boleh dihapus bersama produk.
 */
class WarehouseMasterCleanableProductQuery
{
    /** @return list<string> */
    public static function speedshopUsageTables(): array
    {
        return [
            'transaksi_penjualan_items',
            'speedshop_purchase_order_items',
            'speedshop_po_tagihan_items',
            'speedshop_claims',
            'service_order_products',
        ];
    }

    public static function applyNotUsedBySpeedshopScope(Builder $query): Builder
    {
        foreach (self::speedshopUsageTables() as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'product_id')) {
                continue;
            }

            $query->whereNotExists(function ($sub) use ($table) {
                $sub->select(DB::raw(1))
                    ->from($table)
                    ->whereColumn("{$table}.product_id", 'products.id');
            });
        }

        if (Schema::hasTable('speedshop_stock') && Schema::hasColumn('speedshop_stock', 'product_id')) {
            $query->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('speedshop_stock')
                    ->whereColumn('speedshop_stock.product_id', 'products.id')
                    ->where('speedshop_stock.jumlah', '>', 0);
            });
        }

        if (Schema::hasTable('transaksi_offline_items') && Schema::hasTable('transaksi_offline')) {
            $query->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('transaksi_offline_items')
                    ->join('transaksi_offline', 'transaksi_offline.id', '=', 'transaksi_offline_items.transaksi_offline_id')
                    ->whereColumn('transaksi_offline_items.product_id', 'products.id')
                    ->where(function ($q) {
                        $q->whereNotNull('transaksi_offline.speedshop_id')
                            ->orWhereNotNull('transaksi_offline.speedshop_purchase_order_id')
                            ->orWhere('transaksi_offline.tujuan', 'speedshop');
                    });
            });
        }

        if (Schema::hasTable('mutasi_items') && Schema::hasTable('mutasis')) {
            $query->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('mutasi_items')
                    ->join('mutasis', 'mutasis.id', '=', 'mutasi_items.mutasi_id')
                    ->whereColumn('mutasi_items.product_id', 'products.id')
                    ->where(function ($q) {
                        $q->whereNotNull('mutasis.destination_speedshop_id')
                            ->orWhereNotNull('mutasis.speedshop_id');
                    });
            });
        }

        return $query;
    }
}
