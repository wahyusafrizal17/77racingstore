<?php

namespace App\Support\Speedshop;

use App\Models\Mutasi;
use App\Models\Product;
use App\Models\Speedshop;
use App\Models\SpeedshopStock;
use App\Models\StockMovement;
use App\Support\ProductMasterCatalog;

final class SpeedshopMutasiStockReceive
{
    /**
     * Produk baris stok di cabang tujuan — selaras inventori (Produk Speedshop outlet dulu, lalu MO).
     */
    public static function resolveProduct(Product $source, int $destinationSpeedshopId): Product
    {
        $kodeNorm = mb_strtoupper(trim((string) $source->kode_produk));
        if ($kodeNorm === '') {
            return $source;
        }

        $warehouseId = (int) Speedshop::query()->whereKey($destinationSpeedshopId)->value('warehouse_id');

        $supplier = self::findByKodeInCatalog($destinationSpeedshopId, $kodeNorm, ProductMasterCatalog::SPEEDSHOP_SUPPLIER);
        if ($supplier) {
            return $supplier;
        }

        $mo = self::findByKodeInCatalog($destinationSpeedshopId, $kodeNorm, ProductMasterCatalog::MO);
        if ($mo) {
            return $mo;
        }

        if ($warehouseId > 0) {
            $byWarehouse = Product::findByKodeForWarehouseAndSpeedshopOutlet(
                $warehouseId,
                (string) $source->kode_produk,
                $destinationSpeedshopId
            );
            if ($byWarehouse) {
                return $byWarehouse;
            }
        }

        return $source;
    }

    public static function apply(Mutasi $mutasi, int $destinationSpeedshopId): void
    {
        if (($mutasi->destination_type ?? '') !== 'speedshop' || $destinationSpeedshopId <= 0) {
            return;
        }

        $mutasi->loadMissing('items.product');

        foreach ($mutasi->items as $item) {
            $sourceProduct = $item->product;
            if (! $sourceProduct) {
                continue;
            }

            $qty = (int) $item->quantity;
            if ($qty <= 0) {
                continue;
            }

            $productTujuan = self::resolveProduct($sourceProduct, $destinationSpeedshopId);
            $mutasiId = (int) $mutasi->id;
            $reference = self::movementReference($mutasiId, (int) $productTujuan->id);
            $legacyReference = self::legacyMovementReference($mutasiId, (int) $sourceProduct->id);

            if (StockMovement::query()->where('reference', $reference)->exists()) {
                continue;
            }

            $legacyMovement = StockMovement::query()->where('reference', $legacyReference)->first();
            if ($legacyMovement && (int) $legacyMovement->product_id === (int) $productTujuan->id) {
                continue;
            }

            $row = SpeedshopStock::firstOrCreate(
                ['speedshop_id' => $destinationSpeedshopId, 'product_id' => $productTujuan->id],
                ['jumlah' => 0]
            );
            $row->increment('jumlah', $qty);

            StockMovement::create([
                'product_id' => $productTujuan->id,
                'type' => 'masuk',
                'qty' => $qty,
                'keterangan' => 'Mutasi antar Speedshop — '.$mutasi->kode,
                'reference' => $reference,
            ]);
        }
    }

    public static function movementReference(int $mutasiId, int $targetProductId): string
    {
        return 'mutasi:ss-in:'.$mutasiId.':p'.$targetProductId;
    }

    /** @deprecated Format lama (product_id baris mutasi); dipakai hanya untuk idempotensi. */
    public static function legacyMovementReference(int $mutasiId, int $sourceProductId): string
    {
        return 'mutasi:ss-in:'.$mutasiId.':'.$sourceProductId;
    }

    private static function findByKodeInCatalog(int $speedshopId, string $kodeNorm, string $catalogKind): ?Product
    {
        $query = Product::query()
            ->whereRaw('UPPER(TRIM(kode_produk)) = ?', [$kodeNorm]);

        $query = (clone $query)->forSpeedshopStokCatalog($speedshopId, $catalogKind);

        /** @var Product|null $product */
        $product = $query->first();

        return $product;
    }
}
