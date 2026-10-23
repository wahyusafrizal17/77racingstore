<?php

namespace App\Support;

use App\Models\MarketplacePromotion;
use App\Models\MarketplacePromotionProduct;
use App\Models\Product;
use Illuminate\Support\Collection;

final class MarketplacePromotionPricing
{
    /** @return Collection<int, MarketplacePromotion> */
    public static function activePromotions(
        int $warehouseId,
        string $channel = MarketplacePromotion::CHANNEL_MARKETPLACE,
    ): Collection {
        return MarketplacePromotion::query()
            ->currentlyActive()
            ->forChannel($channel)
            ->where('warehouse_id', $warehouseId)
            ->with(['products.product.brand', 'products.product.category'])
            ->orderByRaw("CASE WHEN type = 'flash_sale' THEN 0 WHEN type = 'bogo' THEN 1 ELSE 2 END")
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  callable(Product): int|null  $priceOf
     */
    public static function productLine(
        Product $product,
        int $qty,
        ?Collection $promotions = null,
        ?callable $priceOf = null,
    ): object {
        $priceOf ??= fn (Product $item) => MarketplaceCart::retailPrice($item);
        $qty = max(1, $qty);
        $normalPrice = max(0, (int) $priceOf($product));
        $promotions ??= self::activePromotions((int) $product->warehouse_id);

        $candidates = $promotions
            ->whereIn('type', [
                MarketplacePromotion::TYPE_FLASH_SALE,
                MarketplacePromotion::TYPE_BOGO,
            ])
            ->filter(function (MarketplacePromotion $promotion) use ($product) {
                $role = $promotion->type === MarketplacePromotion::TYPE_BOGO
                    ? MarketplacePromotionProduct::ROLE_BUY
                    : MarketplacePromotionProduct::ROLE_TARGET;

                return $promotion->products->contains(
                    fn (MarketplacePromotionProduct $row) => $row->role === $role
                        && (int) $row->product_id === (int) $product->id
                );
            });

        $promotion = $candidates
            ->sortByDesc(fn (MarketplacePromotion $candidate) => self::estimatedSaving(
                $candidate,
                $product,
                $qty,
                $normalPrice,
                $priceOf
            ))
            ->first();

        $components = collect();
        $unitPrice = $normalPrice;
        $badge = null;

        if ($promotion?->type === MarketplacePromotion::TYPE_FLASH_SALE) {
            $unitPrice = self::discountedUnitPrice($promotion, $normalPrice);
            $badge = 'Flash Sale';
        }

        $components->push(self::component(
            $product,
            $qty,
            $normalPrice,
            $unitPrice * $qty,
            $promotion,
            false,
        ));

        if ($promotion?->type === MarketplacePromotion::TYPE_BOGO) {
            $buyQty = max(1, (int) $promotion->buy_qty);
            $freeQty = intdiv($qty, $buyQty) * max(1, (int) $promotion->get_qty);
            $freeProduct = $promotion->products
                ->firstWhere('role', MarketplacePromotionProduct::ROLE_GET)
                ?->product;

            if ($freeQty > 0 && $freeProduct) {
                $components->push(self::component(
                    $freeProduct,
                    $freeQty,
                    max(0, (int) $priceOf($freeProduct)),
                    0,
                    $promotion,
                    true,
                ));
            } else {
                $components->first()->promotion = null;
                $promotion = null;
                $badge = null;
            }

            if ($promotion) {
                $badge = 'Beli '.$buyQty.' Gratis '.max(1, (int) $promotion->get_qty);
            }
        }

        $stockAvailable = self::availableLineQuantity($components, $qty);
        if ($stockAvailable > 0 && $stockAvailable < $qty) {
            return self::productLine($product, $stockAvailable, $promotions, $priceOf);
        }
        $normalSubtotal = (int) $components->sum(
            fn (object $component) => $component->harga_normal * $component->qty
        );
        $subtotal = (int) $components->sum('subtotal');

        return (object) [
            'key' => 'product:'.$product->id,
            'kind' => 'product',
            'product' => $product,
            'name' => $product->nama_produk,
            'code' => $product->kode_produk,
            'qty' => $stockAvailable > 0 ? min($qty, $stockAvailable) : 0,
            'harga_normal' => $normalPrice,
            'harga_satuan' => $unitPrice,
            'subtotal_normal' => $normalSubtotal,
            'subtotal' => $subtotal,
            'diskon_promo' => max(0, $normalSubtotal - $subtotal),
            'stok_tersedia' => $stockAvailable,
            'promotion' => $promotion,
            'promo_badge' => $badge,
            'components' => $components,
        ];
    }

    /**
     * @param  callable(Product): int|null  $priceOf
     */
    public static function bundleLine(
        MarketplacePromotion $promotion,
        int $qty,
        ?callable $priceOf = null,
        ?int $bundlePriceOverride = null,
    ): ?object {
        if ($promotion->type !== MarketplacePromotion::TYPE_BUNDLE) {
            return null;
        }

        $priceOf ??= fn (Product $item) => MarketplaceCart::retailPrice($item);

        $rows = $promotion->products
            ->where('role', MarketplacePromotionProduct::ROLE_BUNDLE)
            ->filter(fn (MarketplacePromotionProduct $row) => $row->product !== null);

        if ($rows->isEmpty()) {
            return null;
        }

        $qty = max(1, $qty);
        $normalPerBundle = (int) $rows->sum(function (MarketplacePromotionProduct $row) use ($priceOf) {
            return max(0, (int) $priceOf($row->product)) * max(1, (int) $row->qty);
        });
        $bundlePrice = max(0, $bundlePriceOverride ?? (int) $promotion->bundle_price);

        // Bundle hanya ditampilkan jika harga paket lebih hemat dari harga normal channel.
        if ($normalPerBundle < 1 || $bundlePrice >= $normalPerBundle) {
            return null;
        }

        $components = collect();
        $allocated = 0;

        foreach ($rows->values() as $index => $row) {
            $componentQty = max(1, (int) $row->qty) * $qty;
            $unitNormal = max(0, (int) $priceOf($row->product));
            $normalComponent = $unitNormal * $componentQty;
            $isLast = $index === $rows->count() - 1;
            $componentSubtotal = $isLast
                ? ($bundlePrice * $qty) - $allocated
                : (int) floor(($normalComponent / max(1, $normalPerBundle * $qty)) * ($bundlePrice * $qty));
            $allocated += $componentSubtotal;
            $productStock = max(0, (int) $row->product->jumlah);
            $qtyPerBundle = max(1, (int) $row->qty);

            $component = self::component(
                $row->product,
                $componentQty,
                $unitNormal,
                max(0, $componentSubtotal),
                $promotion,
                false,
            );
            $component->qty_per_bundle = $qtyPerBundle;
            $component->stok_produk = $productStock;
            $component->in_stock = $productStock >= $qtyPerBundle;

            $components->push($component);
        }

        $stockAvailable = $rows->map(function (MarketplacePromotionProduct $row) {
            return intdiv(
                max(0, (int) $row->product->jumlah),
                max(1, (int) $row->qty)
            );
        })->min() ?? 0;

        if ($stockAvailable > 0 && $stockAvailable < $qty) {
            return self::bundleLine($promotion, $stockAvailable, $priceOf, $bundlePriceOverride);
        }

        return (object) [
            'key' => 'bundle:'.$promotion->id,
            'kind' => 'bundle',
            'product' => $rows->first()->product,
            'name' => $promotion->name,
            'code' => 'BUNDLE-'.$promotion->id,
            'qty' => $stockAvailable > 0 ? min($qty, $stockAvailable) : 0,
            'harga_normal' => $normalPerBundle,
            'harga_satuan' => $bundlePrice,
            'subtotal_normal' => $normalPerBundle * $qty,
            'subtotal' => $bundlePrice * $qty,
            'diskon_promo' => max(0, ($normalPerBundle - $bundlePrice) * $qty),
            'stok_tersedia' => $stockAvailable,
            'promotion' => $promotion,
            'promo_badge' => 'Paket Bundle',
            'components' => $components,
        ];
    }

    private static function discountedUnitPrice(MarketplacePromotion $promotion, int $normalPrice): int
    {
        $discount = $promotion->discount_type === MarketplacePromotion::DISCOUNT_PERCENT
            ? (int) floor($normalPrice * min(100, (int) $promotion->discount_value) / 100)
            : min($normalPrice, (int) $promotion->discount_value);

        return max(0, $normalPrice - $discount);
    }

    /**
     * @param  callable(Product): int  $priceOf
     */
    private static function estimatedSaving(
        MarketplacePromotion $promotion,
        Product $product,
        int $qty,
        int $normalPrice,
        callable $priceOf,
    ): int {
        if ($promotion->type !== MarketplacePromotion::TYPE_BOGO) {
            return ($normalPrice - self::discountedUnitPrice($promotion, $normalPrice)) * $qty;
        }

        $freeProduct = $promotion->products
            ->firstWhere('role', MarketplacePromotionProduct::ROLE_GET)
            ?->product;

        return intdiv($qty, max(1, (int) $promotion->buy_qty))
            * max(1, (int) $promotion->get_qty)
            * ($freeProduct ? max(0, (int) $priceOf($freeProduct)) : 0);
    }

    private static function component(
        Product $product,
        int $qty,
        int $normalUnitPrice,
        int $subtotal,
        ?MarketplacePromotion $promotion,
        bool $isBonus,
    ): object {
        return (object) [
            'product' => $product,
            'qty' => $qty,
            'harga_normal' => $normalUnitPrice,
            'harga_satuan' => $qty > 0 ? (int) floor($subtotal / $qty) : 0,
            'subtotal' => $subtotal,
            'diskon_promo' => max(0, ($normalUnitPrice * $qty) - $subtotal),
            'promotion' => $promotion,
            'is_bonus' => $isBonus,
        ];
    }

    private static function availableLineQuantity(Collection $components, int $requestedQty): int
    {
        if ($components->isEmpty()) {
            return 0;
        }

        $ratios = $components
            ->groupBy(fn (object $component) => (int) $component->product->id)
            ->map(function (Collection $productComponents) use ($requestedQty) {
                $needed = (int) $productComponents->sum('qty');
                $perCartUnit = max(1, (int) ceil($needed / max(1, $requestedQty)));

                return intdiv(
                    max(0, (int) $productComponents->first()->product->jumlah),
                    $perCartUnit
                );
            });

        return (int) ($ratios->min() ?? 0);
    }
}
