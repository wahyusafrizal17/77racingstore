<?php

namespace App\Services\Speedshop;

use App\Models\Product;
use App\Models\SpeedshopMasterProductArchive;
use App\Support\SpeedshopMasterCleanableProductQuery;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SpeedshopMasterProductCleanupService
{
    public function hasSpeedshopOutletOperationalUsage(int $productId, int $speedshopId): bool
    {
        $query = Product::query()->whereKey($productId);
        SpeedshopMasterCleanableProductQuery::applyNotUsedBySpeedshopOutletScope($query, $speedshopId);

        return ! $query->exists();
    }

    public function isEligibleForCleanup(Product $product, int $warehouseId, int $speedshopId): bool
    {
        if ($product->warehouse_id !== $warehouseId) {
            return false;
        }

        $archiveQuery = SpeedshopMasterProductArchive::query()
            ->where('speedshop_id', $speedshopId)
            ->where('product_id', (int) $product->id);

        if (SpeedshopMasterProductArchive::supportsPurgedAtColumn()) {
            $archiveQuery->whereNull('purged_at');
        }

        if (! $archiveQuery->exists()) {
            return false;
        }

        if ($this->hasSpeedshopOutletOperationalUsage((int) $product->id, $speedshopId)) {
            return false;
        }

        return true;
    }

    public function purgeSpeedshopOutletOperationalData(Product $product, int $speedshopId): void
    {
        $this->purgeSpeedshopOutletOperationalDataForProducts([(int) $product->id], $speedshopId);
    }

    /**
     * @param  list<int>  $productIds
     */
    public function purgeSpeedshopOutletOperationalDataForProducts(array $productIds, int $speedshopId): void
    {
        $productIds = array_values(array_unique(array_filter(array_map('intval', $productIds))));
        if ($productIds === []) {
            return;
        }

        if (Schema::hasTable('transaksi_penjualan_items') && Schema::hasTable('transaksi_penjualan') && Schema::hasTable('users')) {
            DB::table('transaksi_penjualan_items')
                ->whereIn('product_id', $productIds)
                ->whereIn('transaksi_penjualan_id', function ($q) use ($speedshopId) {
                    $q->select('transaksi_penjualan.id')
                        ->from('transaksi_penjualan')
                        ->join('users', 'users.id', '=', 'transaksi_penjualan.user_id')
                        ->where('users.speedshop_id', $speedshopId);
                })
                ->delete();
        }

        if (Schema::hasTable('service_order_products') && Schema::hasTable('service_orders')) {
            DB::table('service_order_products')
                ->whereIn('product_id', $productIds)
                ->whereIn('service_order_id', function ($q) use ($speedshopId) {
                    $q->select('id')->from('service_orders')->where('speedshop_id', $speedshopId);
                })
                ->delete();
        }

        if (Schema::hasTable('speedshop_purchase_order_items') && Schema::hasTable('speedshop_purchase_orders')) {
            DB::table('speedshop_purchase_order_items')
                ->whereIn('product_id', $productIds)
                ->whereIn('purchase_order_id', function ($q) use ($speedshopId) {
                    $q->select('id')->from('speedshop_purchase_orders')->where('speedshop_id', $speedshopId);
                })
                ->delete();
        }

        if (Schema::hasTable('speedshop_po_tagihan_items') && Schema::hasTable('speedshop_po_tagihans')) {
            DB::table('speedshop_po_tagihan_items')
                ->whereIn('product_id', $productIds)
                ->whereIn('tagihan_id', function ($q) use ($speedshopId) {
                    $q->select('id')->from('speedshop_po_tagihans')->where('speedshop_id', $speedshopId);
                })
                ->delete();
        }

        if (Schema::hasTable('speedshop_claims')) {
            DB::table('speedshop_claims')
                ->whereIn('product_id', $productIds)
                ->where('speedshop_id', $speedshopId)
                ->delete();
        }

        if (Schema::hasTable('transaksi_offline_items') && Schema::hasTable('transaksi_offline')) {
            DB::table('transaksi_offline_items')
                ->whereIn('product_id', $productIds)
                ->whereIn('transaksi_offline_id', function ($q) use ($speedshopId) {
                    $q->select('id')->from('transaksi_offline')->where('speedshop_id', $speedshopId);
                })
                ->delete();
        }

        if (Schema::hasTable('mutasi_items') && Schema::hasTable('mutasis')) {
            DB::table('mutasi_items')
                ->whereIn('product_id', $productIds)
                ->whereIn('mutasi_id', function ($q) use ($speedshopId) {
                    $q->select('id')->from('mutasis')
                        ->where('speedshop_id', $speedshopId)
                        ->orWhere('destination_speedshop_id', $speedshopId);
                })
                ->delete();
        }

        if (Schema::hasTable('speedshop_stock')) {
            DB::table('speedshop_stock')
                ->whereIn('product_id', $productIds)
                ->where('speedshop_id', $speedshopId)
                ->delete();
        }
    }

    /**
     * Hapus permanen dari sisi outlet: milik outlet di-forceDelete; katalog shared tetap terarsip (purged).
     */
    public function cleanupArchivedProduct(Product $product, int $warehouseId, int $speedshopId): void
    {
        if (! $this->isEligibleForCleanup($product, $warehouseId, $speedshopId)) {
            throw new \RuntimeException('Produk tidak memenuhi syarat pembersihan.');
        }

        $this->cleanupArchivedProductsBatch(collect([$product]), $warehouseId, $speedshopId);
    }

    /**
     * Bersihkan batch produk arsip.
     * - Milik outlet (scope = cabang): hapus permanen dari DB.
     * - Katalog gudang (shared): tetap di luar master outlet (arsip + purged_at), TIDAK diaktifkan kembali.
     *
     * @param  Collection<int, Product>  $products
     * @return array{deleted: int, skipped: int, skip_notes: list<string>}
     */
    public function cleanupArchivedProductsBatch(Collection $products, int $warehouseId, int $speedshopId): array
    {
        $eligible = [];
        $skipped = 0;
        $skipNotes = [];

        foreach ($products as $product) {
            if ((int) $product->warehouse_id !== $warehouseId) {
                $skipped++;
                $skipNotes[] = 'Bukan gudang aktif: '.$product->kode_produk;

                continue;
            }

            $eligible[] = $product;
        }

        if ($eligible === []) {
            return [
                'deleted' => 0,
                'skipped' => $skipped,
                'skip_notes' => array_values(array_unique($skipNotes)),
            ];
        }

        $ids = array_map(fn (Product $p) => (int) $p->id, $eligible);
        $outletOwnedIds = [];
        $sharedIds = [];

        foreach ($eligible as $product) {
            if ((int) $product->speedshop_scope_id === $speedshopId) {
                $outletOwnedIds[] = (int) $product->id;
            } else {
                $sharedIds[] = (int) $product->id;
            }
        }

        DB::transaction(function () use ($ids, $outletOwnedIds, $sharedIds, $speedshopId) {
            $this->purgeSpeedshopOutletOperationalDataForProducts($ids, $speedshopId);

            // Shared: jangan hapus baris arsip (itu mengaktifkan produk di master).
            // Tandai purged agar hilang dari Arsip / daftar "tidak terpakai".
            if ($sharedIds !== []) {
                if (SpeedshopMasterProductArchive::supportsPurgedAtColumn()) {
                    SpeedshopMasterProductArchive::query()
                        ->where('speedshop_id', $speedshopId)
                        ->whereIn('product_id', $sharedIds)
                        ->update(['purged_at' => now()]);
                } else {
                    // Fallback tanpa kolom: biarkan tetap terarsip (tetap di list arsip, tapi tidak aktif).
                }
            }

            if ($outletOwnedIds !== []) {
                SpeedshopMasterProductArchive::query()
                    ->where('speedshop_id', $speedshopId)
                    ->whereIn('product_id', $outletOwnedIds)
                    ->delete();

                Product::query()->whereIn('id', $outletOwnedIds)->forceDelete();
            }
        });

        return [
            'deleted' => count($ids),
            'skipped' => $skipped,
            'skip_notes' => array_values(array_unique($skipNotes)),
        ];
    }
}
