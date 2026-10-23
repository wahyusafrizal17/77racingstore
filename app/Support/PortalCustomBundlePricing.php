<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Collection;

final class PortalCustomBundlePricing
{
    /**
     * @param  array{name?: string, qty?: int, items?: list<array{product_id: int, qty: int}>}  $bundleData
     * @param  Collection<int, Product>  $products
     * @param  callable(Product): int  $priceOf
     */
    public static function line(
        string $bundleId,
        array $bundleData,
        Collection $products,
        callable $priceOf,
        string $keyPrefix = 'custom-bundle',
        string $promoBadge = 'Paket Custom',
    ): ?object {
        $items = $bundleData['items'] ?? [];
        if (count($items) < 2) {
            return null;
        }

        $bundleQty = max(1, (int) ($bundleData['qty'] ?? 1));
        $components = collect();
        $normalPerBundle = 0;

        foreach ($items as $item) {
            $product = $products->get((int) ($item['product_id'] ?? 0));
            if (! $product) {
                return null;
            }

            $componentQtyPerBundle = max(1, (int) ($item['qty'] ?? 1));
            $unitPrice = max(0, (int) $priceOf($product));
            $normalPerBundle += $unitPrice * $componentQtyPerBundle;
        }

        if ($normalPerBundle < 1) {
            return null;
        }

        $stockAvailable = collect($items)->map(function (array $item) use ($products) {
            $product = $products->get((int) ($item['product_id'] ?? 0));
            if (! $product) {
                return 0;
            }

            return intdiv(max(0, (int) $product->jumlah), max(1, (int) ($item['qty'] ?? 1)));
        })->min() ?? 0;

        $effectiveQty = $bundleQty;
        if ($stockAvailable > 0 && $stockAvailable < $bundleQty) {
            $effectiveQty = $stockAvailable;
        } elseif ($stockAvailable < 1) {
            $effectiveQty = 1; // tampilkan komposisi 1 paket meskipun stok 0
        }

        foreach ($items as $item) {
            $product = $products->get((int) $item['product_id']);
            $componentQtyPerBundle = max(1, (int) ($item['qty'] ?? 1));
            $totalComponentQty = $componentQtyPerBundle * $effectiveQty;
            $unitPrice = max(0, (int) $priceOf($product));
            $componentSubtotal = $unitPrice * $totalComponentQty;
            $productStock = max(0, (int) $product->jumlah);

            $components->push((object) [
                'product' => $product,
                'qty' => $totalComponentQty,
                'qty_per_bundle' => $componentQtyPerBundle,
                'harga_normal' => $unitPrice,
                'harga_satuan' => $unitPrice,
                'diskon_promo' => 0,
                'subtotal' => $componentSubtotal,
                'promotion' => null,
                'is_bonus' => false,
                'stok_produk' => $productStock,
                'in_stock' => $productStock >= $componentQtyPerBundle,
            ]);
        }

        $displayQty = $stockAvailable > 0 ? min($bundleQty, $stockAvailable) : 0;
        $subtotal = $normalPerBundle * max(1, $displayQty ?: 1);
        $name = trim((string) ($bundleData['name'] ?? ''));
        if ($name === '') {
            $name = 'Paket Custom';
        }

        return (object) [
            'key' => $keyPrefix.':'.$bundleId,
            'kind' => $keyPrefix,
            'custom_bundle_id' => $keyPrefix === 'custom-bundle' ? $bundleId : null,
            'portal_bundle_id' => $keyPrefix === 'portal-bundle' ? (int) $bundleId : null,
            'product' => $components->first()->product,
            'name' => $name,
            'code' => 'PAKET-'.str_pad(substr((string) $bundleId, -6), 6, '0', STR_PAD_LEFT),
            'qty' => max(1, $displayQty ?: 1),
            'harga_normal' => $normalPerBundle,
            'harga_satuan' => $normalPerBundle,
            'subtotal_normal' => $normalPerBundle * max(1, $displayQty ?: 1),
            'subtotal' => $subtotal,
            'diskon_promo' => 0,
            'stok_tersedia' => $stockAvailable,
            'promotion' => null,
            'promo_badge' => $promoBadge,
            'components' => $components,
        ];
    }

    /**
     * @param  list<array{product_id: int, qty: int}>  $items
     * @return list<int>
     */
    public static function productIdsFromItems(array $items): array
    {
        return collect($items)
            ->pluck('product_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }
}
