<?php

namespace App\Support;

use App\Models\Product;

/**
 * Aturan harga saat stock-in Speedshop (pembelian luar & stok in supplier):
 * master produk hanya di-update jika harga masuk lebih besar dari harga sekarang.
 */
final class SpeedshopStockInPricing
{
    public static function applyHigherPricesOnly(Product $product, float $incomingHpp, float $incomingHargaJual): void
    {
        $currentHpp = (float) ($product->hpp ?? $product->harga_pembelian ?? 0);
        $currentHargaJual = (float) ($product->harga_jual_speedshop ?? $product->harga_jual ?? 0);

        $updates = [];

        if ($incomingHpp > $currentHpp) {
            $updates['hpp'] = $incomingHpp;
            $updates['harga_pembelian'] = $incomingHpp;
        }

        if ($incomingHargaJual > $currentHargaJual) {
            $updates['harga_jual_speedshop'] = $incomingHargaJual;
            $updates['harga_jual'] = $incomingHargaJual;
        }

        if ($updates !== []) {
            $product->update($updates);
        }
    }

    public static function applyHigherHppOnly(Product $product, float $incomingHpp): void
    {
        self::applyHigherPricesOnly(
            $product,
            $incomingHpp,
            (float) ($product->harga_jual_speedshop ?? $product->harga_jual ?? 0)
        );
    }
}
