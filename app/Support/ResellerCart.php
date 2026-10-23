<?php

namespace App\Support;

use App\Models\MarketplacePromotion;
use App\Models\PortalCatalogBundle;
use App\Models\Product;
use Illuminate\Support\Collection;

final class ResellerCart
{
    public const SESSION_KEY = 'reseller_cart';

    public const CHECKOUT_SELECTION_KEY = 'reseller_checkout_product_ids';

    public const BUNDLE_SESSION_KEY = 'reseller_cart_bundles';

    public const PORTAL_BUNDLE_SESSION_KEY = 'reseller_cart_portal_bundles';

    /** @return array<int, int> product_id => qty */
    public static function items(): array
    {
        $items = session(self::SESSION_KEY, []);

        return is_array($items) ? array_map('intval', $items) : [];
    }

    public static function count(): int
    {
        return array_sum(self::items())
            + array_sum(self::bundles())
            + array_sum(self::portalBundles());
    }

    public static function countForWarehouse(int $warehouseId): int
    {
        return (int) self::linesForWarehouse($warehouseId)->sum(fn ($line) => (int) ($line->qty ?? 0));
    }

    /** Sinkronkan session keranjang dengan produk/bundle yang benar-benar tampil di katalog. */
    public static function syncSessionToCatalog(int $warehouseId): void
    {
        $lines = self::linesForWarehouse($warehouseId);

        $products = [];
        $bundles = [];
        $portalBundles = [];

        foreach ($lines as $line) {
            $key = (string) ($line->key ?? '');
            $qty = max(1, (int) ($line->qty ?? 0));
            if (str_starts_with($key, 'product:')) {
                $products[(int) str($key)->after('product:')->toString()] = $qty;
            } elseif (str_starts_with($key, 'bundle:')) {
                $bundles[(int) str($key)->after('bundle:')->toString()] = $qty;
            } elseif (str_starts_with($key, 'portal-bundle:') || str_starts_with($key, 'custom-bundle:')) {
                $portalBundles[(int) str($key)->afterLast(':')->toString()] = $qty;
            }
        }

        session([
            self::SESSION_KEY => $products,
            self::BUNDLE_SESSION_KEY => $bundles,
            self::PORTAL_BUNDLE_SESSION_KEY => $portalBundles,
        ]);

        $validKeys = $lines->pluck('key')->map(fn ($k) => (string) $k)->all();
        if ($validKeys === []) {
            self::clearCheckoutSelection();

            return;
        }

        $selected = array_values(array_intersect(self::checkoutSelection(), $validKeys));
        self::setCheckoutSelection($selected);
    }

    /** @return array<int, int> promotion_id => qty */
    public static function bundles(): array
    {
        $items = session(self::BUNDLE_SESSION_KEY, []);

        return is_array($items) ? array_map('intval', $items) : [];
    }

    public static function add(int $productId, int $qty = 1): void
    {
        $items = self::items();
        $items[$productId] = max(0, (int) ($items[$productId] ?? 0) + max(1, $qty));
        session([self::SESSION_KEY => $items]);
    }

    /** @return array<int, int> portal_catalog_bundle_id => qty */
    public static function portalBundles(): array
    {
        $items = session(self::PORTAL_BUNDLE_SESSION_KEY, []);

        return is_array($items) ? array_map('intval', $items) : [];
    }

    public static function addPortalBundle(int $bundleId, int $qty = 1): void
    {
        $items = self::portalBundles();
        $items[$bundleId] = max(0, (int) ($items[$bundleId] ?? 0) + max(1, $qty));
        session([self::PORTAL_BUNDLE_SESSION_KEY => $items]);
    }

    public static function updatePortalBundle(int $bundleId, int $qty): void
    {
        $items = self::portalBundles();
        if ($qty < 1) {
            unset($items[$bundleId]);
            self::forgetCheckoutSelection(['portal-bundle:'.$bundleId]);
        } else {
            $items[$bundleId] = $qty;
        }
        session([self::PORTAL_BUNDLE_SESSION_KEY => $items]);
    }

    public static function removePortalBundle(int $bundleId): void
    {
        $items = self::portalBundles();
        unset($items[$bundleId]);
        session([self::PORTAL_BUNDLE_SESSION_KEY => $items]);
        self::forgetCheckoutSelection(['portal-bundle:'.$bundleId]);
    }

    public static function addBundle(int $promotionId, int $qty = 1): void
    {
        $items = self::bundles();
        $items[$promotionId] = max(0, (int) ($items[$promotionId] ?? 0) + max(1, $qty));
        session([self::BUNDLE_SESSION_KEY => $items]);
    }

    public static function update(int $productId, int $qty): void
    {
        $items = self::items();
        if ($qty < 1) {
            unset($items[$productId]);
            self::forgetCheckoutSelection([$productId]);
        } else {
            $items[$productId] = $qty;
        }
        session([self::SESSION_KEY => $items]);
    }

    public static function updateBundle(int $promotionId, int $qty): void
    {
        $items = self::bundles();
        if ($qty < 1) {
            unset($items[$promotionId]);
            self::forgetCheckoutSelection(['bundle:'.$promotionId]);
        } else {
            $items[$promotionId] = $qty;
        }
        session([self::BUNDLE_SESSION_KEY => $items]);
    }

    public static function remove(int $productId): void
    {
        $items = self::items();
        unset($items[$productId]);
        session([self::SESSION_KEY => $items]);
        self::forgetCheckoutSelection([$productId]);
    }

    public static function removeBundle(int $promotionId): void
    {
        $items = self::bundles();
        unset($items[$promotionId]);
        session([self::BUNDLE_SESSION_KEY => $items]);
        self::forgetCheckoutSelection(['bundle:'.$promotionId]);
    }

    /** @param list<int> $productIds */
    public static function removeMany(array $productIds): void
    {
        $items = self::items();
        foreach ($productIds as $productId) {
            unset($items[(int) $productId]);
        }
        session([self::SESSION_KEY => $items]);
        self::forgetCheckoutSelection($productIds);
    }

    /** @param list<string> $keys */
    public static function removeKeys(array $keys): void
    {
        $products = self::items();
        $bundles = self::bundles();
        $portalBundles = self::portalBundles();

        foreach ($keys as $key) {
            if (str_starts_with($key, 'product:')) {
                unset($products[(int) str($key)->after('product:')->toString()]);
            } elseif (str_starts_with($key, 'bundle:')) {
                unset($bundles[(int) str($key)->after('bundle:')->toString()]);
            } elseif (str_starts_with($key, 'portal-bundle:')) {
                unset($portalBundles[(int) str($key)->after('portal-bundle:')->toString()]);
            } elseif (str_starts_with($key, 'custom-bundle:')) {
                unset($portalBundles[(int) str($key)->after('custom-bundle:')->toString()]);
            }
        }

        session([
            self::SESSION_KEY => $products,
            self::BUNDLE_SESSION_KEY => $bundles,
            self::PORTAL_BUNDLE_SESSION_KEY => $portalBundles,
        ]);
        self::forgetCheckoutSelection($keys);
    }

    public static function clear(): void
    {
        session()->forget(self::SESSION_KEY);
        session()->forget(self::BUNDLE_SESSION_KEY);
        session()->forget(self::PORTAL_BUNDLE_SESSION_KEY);
        self::clearCheckoutSelection();
    }

    /** @param array<int, int> $productQtyById */
    public static function replaceProductsForCheckout(array $productQtyById): void
    {
        $items = [];
        foreach ($productQtyById as $productId => $qty) {
            $qty = (int) $qty;
            if ($qty < 1) {
                continue;
            }
            $items[(int) $productId] = $qty;
        }

        session([
            self::SESSION_KEY => $items,
            self::BUNDLE_SESSION_KEY => [],
            self::PORTAL_BUNDLE_SESSION_KEY => [],
        ]);

        $keys = array_map(fn (int $id) => 'product:'.$id, array_keys($items));
        self::setCheckoutSelection($keys);
    }

    /** @return list<string> */
    public static function checkoutSelection(): array
    {
        $ids = session(self::CHECKOUT_SELECTION_KEY, []);
        if (! is_array($ids)) {
            return [];
        }

        $cartIds = self::cartKeys();
        $ids = array_map(
            fn ($id) => str_contains((string) $id, ':') ? (string) $id : 'product:'.(int) $id,
            $ids
        );

        return array_values(array_intersect($ids, $cartIds));
    }

    /** @param list<string> $keys */
    public static function setCheckoutSelection(array $keys): void
    {
        $ids = array_values(array_unique(array_intersect(array_map('strval', $keys), self::cartKeys())));
        session([self::CHECKOUT_SELECTION_KEY => $ids]);
    }

    public static function clearCheckoutSelection(): void
    {
        session()->forget(self::CHECKOUT_SELECTION_KEY);
    }

    /** @param list<int|string> $keys */
    public static function forgetCheckoutSelection(array $keys): void
    {
        $normalized = array_map(
            fn ($key) => str_contains((string) $key, ':') ? (string) $key : 'product:'.(int) $key,
            $keys
        );
        $remaining = array_values(array_diff(self::checkoutSelection(), $normalized));
        if ($remaining === []) {
            self::clearCheckoutSelection();

            return;
        }

        session([self::CHECKOUT_SELECTION_KEY => $remaining]);
    }

    /**
     * @return Collection<int, object>
     */
    public static function linesForWarehouse(int $warehouseId): Collection
    {
        return self::buildLines($warehouseId, null);
    }

    /**
     * @return Collection<int, object>
     */
    public static function linesForCheckout(int $warehouseId): Collection
    {
        $selected = self::checkoutSelection();
        if ($selected === []) {
            return collect();
        }

        return self::buildLines($warehouseId, $selected);
    }

    /**
     * @param  list<string>|null  $onlyKeys
     * @return Collection<int, object>
     */
    private static function buildLines(int $warehouseId, ?array $onlyKeys): Collection
    {
        $items = self::items();
        $bundles = self::bundles();
        $portalBundles = self::portalBundles();
        if ($items === [] && $bundles === [] && $portalBundles === []) {
            return collect();
        }

        if ($onlyKeys !== null) {
            $productIds = collect($onlyKeys)
                ->filter(fn ($key) => str_starts_with((string) $key, 'product:'))
                ->map(fn ($key) => (int) str($key)->after('product:')->toString())
                ->all();
            $bundleIds = collect($onlyKeys)
                ->filter(fn ($key) => str_starts_with((string) $key, 'bundle:'))
                ->map(fn ($key) => (int) str($key)->after('bundle:')->toString())
                ->all();
            $portalBundleIds = collect($onlyKeys)
                ->filter(fn ($key) => str_starts_with((string) $key, 'portal-bundle:') || str_starts_with((string) $key, 'custom-bundle:'))
                ->map(fn ($key) => (int) str($key)->afterLast(':')->toString())
                ->all();
            $items = array_intersect_key($items, array_flip($productIds));
            $bundles = array_intersect_key($bundles, array_flip($bundleIds));
            $portalBundles = array_intersect_key($portalBundles, array_flip($portalBundleIds));
        }

        if ($items === [] && $bundles === [] && $portalBundles === []) {
            return collect();
        }

        $portalBundleModels = PortalCatalogBundle::query()
            ->with(['items.product.brand', 'items.product.category'])
            ->where('warehouse_id', $warehouseId)
            ->whereIn('id', array_keys($portalBundles) ?: [-1])
            ->get()
            ->keyBy('id');

        $allProductIds = array_unique(array_merge(
            array_keys($items),
            $portalBundleModels->flatMap(fn (PortalCatalogBundle $bundle) => $bundle->items->pluck('product_id'))->map(fn ($id) => (int) $id)->all(),
        ));

        $promotions = ResellerPromotionPricing::activePromotions($warehouseId);
        $products = Product::query()
            ->with(['brand', 'category'])
            ->where('warehouse_id', $warehouseId)
            ->where('speedshop_scope_id', 0)
            ->whereNull('hidden_from_warehouse_master_at')
            ->where('harga_jual_reseler', '>', 0)
            ->whereIn('id', $allProductIds ?: [-1])
            ->get()
            ->keyBy('id');

        $productLines = collect($items)
            ->map(function (int $qty, int $productId) use ($products, $promotions) {
                $product = $products->get($productId);
                if (! $product) {
                    return null;
                }

                $stok = max(0, (int) $product->jumlah);
                if ($stok < 1) {
                    return null;
                }

                $qty = min(max(1, $qty), $stok);

                return ResellerPromotionPricing::productLine($product, $qty, $promotions);
            })
            ->filter(fn ($line) => $line && $line->stok_tersedia > 0);

        $bundlePromotions = $promotions
            ->where('type', MarketplacePromotion::TYPE_BUNDLE)
            ->keyBy('id');
        $bundleLines = collect($bundles)->map(function (int $qty, int $promotionId) use ($bundlePromotions) {
            $promotion = $bundlePromotions->get($promotionId);

            return $promotion
                ? ResellerPromotionPricing::bundleLine($promotion, $qty)
                : null;
        })->filter(fn ($line) => $line && $line->stok_tersedia > 0);

        $portalBundleLines = collect($portalBundles)->map(function (int $qty, int $bundleId) use ($portalBundleModels) {
            $bundle = $portalBundleModels->get($bundleId);

            return $bundle
                ? PortalCatalogBundlePricing::line($bundle, $qty, fn (Product $product) => self::resellerPrice($product))
                : null;
        })->filter(fn ($line) => $line && $line->stok_tersedia > 0);

        return $productLines->concat($bundleLines)->concat($portalBundleLines)->values();
    }

    public static function totalForWarehouse(int $warehouseId): int
    {
        return (int) self::linesForWarehouse($warehouseId)->sum(fn ($line) => (int) $line->subtotal);
    }

    public static function resellerPrice(Product $product): int
    {
        return max(0, (int) ($product->harga_jual_reseler ?? 0));
    }

    /** @return list<string> */
    private static function cartKeys(): array
    {
        return [
            ...array_map(fn ($id) => 'product:'.$id, array_keys(self::items())),
            ...array_map(fn ($id) => 'bundle:'.$id, array_keys(self::bundles())),
            ...array_map(fn ($id) => 'portal-bundle:'.$id, array_keys(self::portalBundles())),
        ];
    }
}
