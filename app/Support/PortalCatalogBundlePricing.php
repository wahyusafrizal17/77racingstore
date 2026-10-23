<?php

namespace App\Support;

use App\Models\PortalCatalogBundle;
use App\Models\Product;
use Illuminate\Support\Collection;

final class PortalCatalogBundlePricing
{
    /**
     * @param  callable(Product): int  $priceOf
     */
    public static function line(PortalCatalogBundle $bundle, int $qty, callable $priceOf): ?object
    {
        $bundle->loadMissing(['items.product.brand', 'items.product.category']);

        $items = $bundle->itemPayload();
        if (count($items) < 2) {
            return null;
        }

        $products = $bundle->items
            ->map(fn ($item) => $item->product)
            ->filter()
            ->keyBy('id');

        return PortalCustomBundlePricing::line(
            (string) $bundle->id,
            [
                'name' => $bundle->name,
                'qty' => max(1, $qty),
                'items' => $items,
            ],
            $products,
            $priceOf,
            'portal-bundle',
            'Paket Bundle'
        );
    }

    /**
     * @param  callable(Product): int  $priceOf
     * @return Collection<int, object>
     */
    public static function linesForOwner(
        int $warehouseId,
        int $warehouseResellerId,
        string $ownerPortal,
        callable $priceOf,
    ): Collection {
        $bundles = PortalCatalogBundle::query()
            ->with(['items.product.brand', 'items.product.category'])
            ->where('warehouse_id', $warehouseId)
            ->where('warehouse_reseller_id', $warehouseResellerId)
            ->where('owner_portal', $ownerPortal)
            ->where('is_active', true)
            ->orderByDesc('created_at')
            ->get();

        return $bundles
            ->map(fn (PortalCatalogBundle $bundle) => self::line($bundle, 1, $priceOf))
            ->filter()
            ->values();
    }
}
