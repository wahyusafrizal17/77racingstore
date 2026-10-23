<?php

namespace App\Support;

use App\Models\MarketplacePromotion;
use App\Models\MarketplacePromotionProduct;
use App\Models\Product;
use Illuminate\Support\Collection;

final class ResellerPromotionPricing
{
    /** @return Collection<int, MarketplacePromotion> */
    public static function activePromotions(int $warehouseId): Collection
    {
        return MarketplacePromotionPricing::activePromotions(
            $warehouseId,
            MarketplacePromotion::CHANNEL_RESELLER
        );
    }

    public static function priceOf(Product $product): int
    {
        return ResellerCart::resellerPrice($product);
    }

    public static function productLine(
        Product $product,
        int $qty,
        ?Collection $promotions = null,
    ): object {
        return MarketplacePromotionPricing::productLine(
            $product,
            $qty,
            $promotions,
            self::priceOf(...)
        );
    }

    public static function bundleLine(MarketplacePromotion $promotion, int $qty): ?object
    {
        $scaledPrice = self::scaledBundlePrice($promotion);

        return MarketplacePromotionPricing::bundleLine(
            $promotion,
            $qty,
            self::priceOf(...),
            $scaledPrice
        );
    }

    /**
     * Samakan rasio diskon bundle marketplace ke harga reseller,
     * supaya paket tetap tampil meski harga absolut eceran lebih tinggi dari harga reseller.
     */
    private static function scaledBundlePrice(MarketplacePromotion $promotion): ?int
    {
        $rows = $promotion->products
            ->where('role', MarketplacePromotionProduct::ROLE_BUNDLE)
            ->filter(fn (MarketplacePromotionProduct $row) => $row->product !== null);

        if ($rows->isEmpty()) {
            return null;
        }

        $retailNormal = (int) $rows->sum(function (MarketplacePromotionProduct $row) {
            return MarketplaceCart::retailPrice($row->product) * max(1, (int) $row->qty);
        });
        $resellerNormal = (int) $rows->sum(function (MarketplacePromotionProduct $row) {
            return self::priceOf($row->product) * max(1, (int) $row->qty);
        });
        $configured = max(0, (int) $promotion->bundle_price);

        if ($resellerNormal < 1) {
            return null;
        }

        // Jika harga paket sudah lebih murah dari total reseller, pakai apa adanya.
        if ($configured > 0 && $configured < $resellerNormal) {
            return $configured;
        }

        // Skala proporsional dari diskon marketplace (eceran).
        if ($retailNormal < 1 || $configured < 1 || $configured >= $retailNormal) {
            return null;
        }

        $scaled = (int) floor($resellerNormal * ($configured / $retailNormal));

        return max(1, min($scaled, $resellerNormal - 1));
    }
}
