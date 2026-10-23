<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Produk arsip master speedshop yang belum dipakai di operasional outlet tertentu.
 */
class SpeedshopMasterCleanableProductQuery
{
    public static function applyNotUsedBySpeedshopOutletScope(Builder $query, int $speedshopId): Builder
    {
        if (Schema::hasTable('transaksi_penjualan_items') && Schema::hasTable('transaksi_penjualan') && Schema::hasTable('users')) {
            $query->whereNotExists(function ($sub) use ($speedshopId) {
                $sub->select(DB::raw(1))
                    ->from('transaksi_penjualan_items')
                    ->join('transaksi_penjualan', 'transaksi_penjualan.id', '=', 'transaksi_penjualan_items.transaksi_penjualan_id')
                    ->join('users', 'users.id', '=', 'transaksi_penjualan.user_id')
                    ->whereColumn('transaksi_penjualan_items.product_id', 'products.id')
                    ->where('users.speedshop_id', (int) $speedshopId);
            });
        }

        if (Schema::hasTable('service_order_products') && Schema::hasTable('service_orders')) {
            $query->whereNotExists(function ($sub) use ($speedshopId) {
                $sub->select(DB::raw(1))
                    ->from('service_order_products')
                    ->join('service_orders', 'service_orders.id', '=', 'service_order_products.service_order_id')
                    ->whereColumn('service_order_products.product_id', 'products.id')
                    ->where('service_orders.speedshop_id', (int) $speedshopId);
            });
        }

        if (Schema::hasTable('speedshop_purchase_order_items') && Schema::hasTable('speedshop_purchase_orders')) {
            $query->whereNotExists(function ($sub) use ($speedshopId) {
                $sub->select(DB::raw(1))
                    ->from('speedshop_purchase_order_items')
                    ->join('speedshop_purchase_orders', 'speedshop_purchase_orders.id', '=', 'speedshop_purchase_order_items.purchase_order_id')
                    ->whereColumn('speedshop_purchase_order_items.product_id', 'products.id')
                    ->where('speedshop_purchase_orders.speedshop_id', (int) $speedshopId);
            });
        }

        if (Schema::hasTable('speedshop_po_tagihan_items') && Schema::hasTable('speedshop_po_tagihans')) {
            $query->whereNotExists(function ($sub) use ($speedshopId) {
                $sub->select(DB::raw(1))
                    ->from('speedshop_po_tagihan_items')
                    ->join('speedshop_po_tagihans', 'speedshop_po_tagihans.id', '=', 'speedshop_po_tagihan_items.tagihan_id')
                    ->whereColumn('speedshop_po_tagihan_items.product_id', 'products.id')
                    ->where('speedshop_po_tagihans.speedshop_id', (int) $speedshopId);
            });
        }

        if (Schema::hasTable('speedshop_claims') && Schema::hasColumn('speedshop_claims', 'speedshop_id')) {
            $query->whereNotExists(function ($sub) use ($speedshopId) {
                $sub->select(DB::raw(1))
                    ->from('speedshop_claims')
                    ->whereColumn('speedshop_claims.product_id', 'products.id')
                    ->where('speedshop_claims.speedshop_id', (int) $speedshopId);
            });
        }

        if (Schema::hasTable('speedshop_stock')) {
            $query->whereNotExists(function ($sub) use ($speedshopId) {
                $sub->select(DB::raw(1))
                    ->from('speedshop_stock')
                    ->whereColumn('speedshop_stock.product_id', 'products.id')
                    ->where('speedshop_stock.speedshop_id', (int) $speedshopId)
                    ->where('speedshop_stock.jumlah', '>', 0);
            });
        }

        if (Schema::hasTable('transaksi_offline_items') && Schema::hasTable('transaksi_offline')) {
            $query->whereNotExists(function ($sub) use ($speedshopId) {
                $sub->select(DB::raw(1))
                    ->from('transaksi_offline_items')
                    ->join('transaksi_offline', 'transaksi_offline.id', '=', 'transaksi_offline_items.transaksi_offline_id')
                    ->whereColumn('transaksi_offline_items.product_id', 'products.id')
                    ->where('transaksi_offline.speedshop_id', (int) $speedshopId);
            });
        }

        if (Schema::hasTable('mutasi_items') && Schema::hasTable('mutasis')) {
            $query->whereNotExists(function ($sub) use ($speedshopId) {
                $sub->select(DB::raw(1))
                    ->from('mutasi_items')
                    ->join('mutasis', 'mutasis.id', '=', 'mutasi_items.mutasi_id')
                    ->whereColumn('mutasi_items.product_id', 'products.id')
                    ->where(function ($q) use ($speedshopId) {
                        $q->where('mutasis.speedshop_id', (int) $speedshopId)
                            ->orWhere('mutasis.destination_speedshop_id', (int) $speedshopId);
                    });
            });
        }

        return $query;
    }
}
