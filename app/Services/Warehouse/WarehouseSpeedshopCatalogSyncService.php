<?php

namespace App\Services\Warehouse;

use App\Models\Product;
use App\Models\Speedshop;
use App\Models\SpeedshopStock;
use App\Support\ProductMasterCatalog;

/**
 * Sinkron katalog shared warehouse (speedshop_scope_id = 0) ke outlet speedshop:
 * kode, nama, kategori, brand, dan harga mengikuti master; stok tetap per outlet.
 */
class WarehouseSpeedshopCatalogSyncService
{
    public function afterWarehouseSharedCatalogSaved(Product $product, ?string $previousKode = null): void
    {
        if ((int) ($product->speedshop_scope_id ?? 0) !== 0 || ! $product->warehouse_id) {
            return;
        }

        $kind = ProductMasterCatalog::kindForProduct($product);
        if (! ProductMasterCatalog::isWarehouseSharedKind($kind)) {
            return;
        }

        $warehouseId = (int) $product->warehouse_id;
        $kode = mb_strtoupper(trim((string) $product->kode_produk));
        $lookupKodes = array_values(array_unique(array_filter([
            $kode,
            $previousKode ? mb_strtoupper(trim($previousKode)) : null,
        ])));

        if ($lookupKodes === []) {
            return;
        }

        $eceran = $product->harga_eceran_terendah ?? $product->harga_jual;
        $syncPayload = [
            'kode_produk' => $kode,
            'kode_supplier' => $product->kode_supplier,
            'nama_produk' => $product->nama_produk,
            'category_id' => $product->category_id,
            'brand_id' => $product->brand_id,
            'harga_jual_speedshop' => $product->harga_jual_speedshop,
            'harga_eceran_terendah' => $product->harga_eceran_terendah,
            'harga_jual' => $eceran,
        ];

        Product::query()
            ->where('warehouse_id', $warehouseId)
            ->where('speedshop_scope_id', '>', 0)
            ->whereIn('kode_produk', $lookupKodes)
            ->forCatalogJenis($kind)
            ->update($syncPayload);

        $speedshopIds = $this->speedshopIdsForWarehouse($warehouseId);
        foreach ($speedshopIds as $speedshopId) {
            SpeedshopStock::query()->firstOrCreate(
                [
                    'speedshop_id' => $speedshopId,
                    'product_id' => (int) $product->id,
                ],
                ['jumlah' => 0],
            );
        }
    }

    /** @return list<int> */
    private function speedshopIdsForWarehouse(int $warehouseId): array
    {
        return Speedshop::query()
            ->where(function ($q) use ($warehouseId) {
                $q->where('mutasi_warehouse_id', $warehouseId)
                    ->orWhere('warehouse_id', $warehouseId);
            })
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
