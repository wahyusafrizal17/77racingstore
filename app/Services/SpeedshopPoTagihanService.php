<?php

namespace App\Services;

use App\Models\Product;
use App\Models\SpeedshopPoTagihan;
use App\Models\SpeedshopPoTagihanItem;
use App\Models\SpeedshopPurchaseOrder;
use App\Models\TransaksiOffline;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SpeedshopPoTagihanService
{
    public static function productHargaPoRp(Product $product): int
    {
        $speedshop = (int) ($product->harga_jual_speedshop ?? 0);
        if ($speedshop > 0) {
            return $speedshop;
        }
        $jual = (int) ($product->harga_jual ?? 0);
        if ($jual > 0) {
            return $jual;
        }

        return max(0, (int) ($product->hpp ?? 0));
    }

    public static function kodeFromPoKode(string $poKode): string
    {
        if (str_starts_with($poKode, 'PO/')) {
            return 'TAG/'.substr($poKode, 3);
        }
        if (str_starts_with($poKode, 'PO-')) {
            return 'TAG-'.substr($poKode, 3);
        }

        return 'TAG-'.$poKode;
    }

    /**
     * Buat tagihan dari transaksi offline PO yang sudah diverifikasi speedshop.
     */
    public function createFromVerifiedTransaksi(TransaksiOffline $transaksi): ?SpeedshopPoTagihan
    {
        if (! Schema::hasTable('speedshop_po_tagihans')) {
            return null;
        }

        if ($transaksi->speedshop_purchase_order_id === null || ! $transaksi->isVerified()) {
            return null;
        }

        $existing = SpeedshopPoTagihan::query()
            ->where('speedshop_purchase_order_id', $transaksi->speedshop_purchase_order_id)
            ->first();
        if ($existing) {
            return $existing;
        }

        $transaksi->loadMissing(['items.product', 'speedshopPurchaseOrder']);

        $po = $transaksi->speedshopPurchaseOrder;
        if (! $po) {
            return null;
        }

        return DB::transaction(function () use ($transaksi, $po) {
            $total = 0;
            $lineRows = [];

            foreach ($transaksi->items as $item) {
                $product = $item->product;
                if (! $product) {
                    continue;
                }
                $harga = self::productHargaPoRp($product);
                $subtotal = $harga * (int) $item->qty;
                $total += $subtotal;
                $lineRows[] = [
                    'product_id' => $product->id,
                    'qty' => (int) $item->qty,
                    'harga_satuan' => $harga,
                    'subtotal' => $subtotal,
                ];
            }

            $tagihan = SpeedshopPoTagihan::create([
                'kode' => self::kodeFromPoKode((string) $po->kode),
                'speedshop_purchase_order_id' => $po->id,
                'transaksi_offline_id' => $transaksi->id,
                'speedshop_id' => (int) $transaksi->speedshop_id,
                'warehouse_id' => (int) $transaksi->warehouse_id,
                'total_nominal' => $total,
                'tanggal_tagihan' => $transaksi->verified_at?->toDateString() ?? now()->toDateString(),
                'status' => SpeedshopPoTagihan::STATUS_BELUM_LUNAS,
            ]);

            foreach ($lineRows as $row) {
                SpeedshopPoTagihanItem::create(array_merge($row, ['tagihan_id' => $tagihan->id]));
            }

            return $tagihan->load(['items.product', 'speedshop', 'warehouse', 'purchaseOrder']);
        });
    }

    /**
     * Backfill tagihan untuk PO yang sudah diverifikasi tetapi belum punya tagihan.
     */
    public function syncMissingForScope(?int $warehouseId = null, ?int $speedshopId = null): int
    {
        if (! Schema::hasTable('speedshop_po_tagihans')) {
            return 0;
        }

        $q = TransaksiOffline::query()
            ->whereNotNull('speedshop_purchase_order_id')
            ->whereNotNull('verified_at')
            ->where('tujuan', 'speedshop');

        if ($warehouseId) {
            $q->where('warehouse_id', $warehouseId);
        }
        if ($speedshopId) {
            $q->where('speedshop_id', $speedshopId);
        }

        $created = 0;
        foreach ($q->with(['items.product', 'speedshopPurchaseOrder'])->cursor() as $transaksi) {
            $has = SpeedshopPoTagihan::query()
                ->where('speedshop_purchase_order_id', $transaksi->speedshop_purchase_order_id)
                ->exists();
            if ($has) {
                continue;
            }
            if ($this->createFromVerifiedTransaksi($transaksi)) {
                $created++;
            }
        }

        return $created;
    }
}
