<?php

namespace App\Services\ModifikasiOri;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\SpeedshopPurchaseOrder;
use App\Models\SpeedshopPurchaseOrderItem;
use App\Models\SpeedshopStock;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Alur Purchase Order 77 Racing → warehouse Modifikasi Ori.
 *
 * PO disimpan lokal (speedshop_purchase_orders, kode = external_ref di Modifikasi Ori) lalu dikirim ke API.
 * Warehouse Modifikasi Ori memproses (terima → kirim dengan qty disetujui). Setelah dikirim,
 * 77 Racing menekan "Terima Barang" → API mengonfirmasi & membuat tagihan, stok masuk ke speedshop_stock lokal.
 */
class MoPurchaseOrderService
{
    public function __construct(private readonly ModifikasiOriPartnerClient $client) {}

    public function client(): ModifikasiOriPartnerClient
    {
        return $this->client;
    }

    /**
     * Buat PO lokal + kirim ke Modifikasi Ori.
     *
     * @param  list<array{sku: string, qty: int}>  $items
     */
    public function create(int $speedshopId, User $actor, ?string $catatan, array $items): SpeedshopPurchaseOrder
    {
        $qtyBySku = [];
        foreach ($items as $index => $item) {
            $sku = mb_strtoupper(trim((string) ($item['sku'] ?? '')));
            if ($sku === '') {
                throw ValidationException::withMessages(["items.{$index}.sku" => 'SKU wajib diisi.']);
            }
            $qtyBySku[$sku] = ($qtyBySku[$sku] ?? 0) + max(1, (int) ($item['qty'] ?? 0));
        }

        // Katalog segar: pastikan SKU memang ada di warehouse Modifikasi Ori sebelum disimpan.
        $catalog = collect($this->client->catalog(fresh: true))
            ->keyBy(fn (array $row) => mb_strtoupper(trim((string) ($row['sku'] ?? ''))));

        $missing = array_values(array_diff(array_keys($qtyBySku), $catalog->keys()->all()));
        if ($missing !== []) {
            throw ValidationException::withMessages([
                'items' => 'Produk tidak tersedia di katalog warehouse Modifikasi Ori: '.implode(', ', $missing),
            ]);
        }

        $po = DB::transaction(function () use ($speedshopId, $actor, $catatan, $qtyBySku, $catalog) {
            $kode = SpeedshopPurchaseOrder::generateKodeSpeedshop($speedshopId, now());

            $po = SpeedshopPurchaseOrder::create([
                'kode' => $kode,
                'invoice_no' => $kode,
                'speedshop_id' => $speedshopId,
                'warehouse_id' => null,
                'tipe' => 'modifikasi_ori',
                'status' => 'pending',
                'mo_status' => SpeedshopPurchaseOrder::MO_STATUS_SEND_FAILED, // diganti setelah API sukses
                'created_by' => $actor->id,
                'catatan' => $catatan !== null && trim($catatan) !== '' ? trim($catatan) : null,
            ]);

            foreach ($qtyBySku as $sku => $qty) {
                $row = $catalog->get($sku);
                SpeedshopPurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'product_id' => $this->localProductFor($row)->id,
                    'qty' => $qty,
                    'harga_satuan' => (int) ($row['harga'] ?? 0),
                ]);
            }

            return $po;
        });

        $this->submit($po);

        return $po->refresh();
    }

    /**
     * Kirim (atau kirim ulang) PO ke Modifikasi Ori. Aman diulang: server memakai kode PO sebagai kunci idempoten.
     */
    public function submit(SpeedshopPurchaseOrder $po): void
    {
        $po->loadMissing('items.product');

        try {
            $payload = $this->client->createPurchaseOrder(
                (string) $po->kode,
                $po->catatan,
                $po->items->map(fn (SpeedshopPurchaseOrderItem $item) => [
                    'sku' => mb_strtoupper(trim((string) $item->product?->kode_produk)),
                    'qty' => (int) $item->qty,
                ])->values()->all(),
            );
        } catch (ModifikasiOriApiException $e) {
            $po->update([
                'mo_status' => SpeedshopPurchaseOrder::MO_STATUS_SEND_FAILED,
                'mo_sync_error' => $e->getMessage(),
            ]);
            throw $e;
        }

        $this->apply($po, $payload);
    }

    /** Ambil status terbaru dari Modifikasi Ori untuk PO yang belum final. */
    public function sync(SpeedshopPurchaseOrder $po): void
    {
        if (! $po->isModifikasiOriPo() || $po->isMoFinal()) {
            return;
        }

        $this->syncMany(collect([$po]));
    }

    /**
     * @param  Collection<int, SpeedshopPurchaseOrder>  $pos
     * @return int jumlah PO yang diperbarui
     */
    public function syncMany(Collection $pos): int
    {
        if (! $this->client->isConfigured()) {
            return 0;
        }

        $updated = 0;
        foreach ($pos->filter(fn (SpeedshopPurchaseOrder $po) => $po->isModifikasiOriPo())->chunk(100) as $chunk) {
            $byRef = $chunk->keyBy(fn (SpeedshopPurchaseOrder $po) => (string) $po->kode);
            $remote = $this->client->purchaseOrders($byRef->keys()->all());

            foreach ($remote as $payload) {
                $po = $byRef->get((string) ($payload['external_ref'] ?? ''));
                if ($po) {
                    $this->apply($po, $payload);
                    $updated++;
                }
            }

            // PO "gagal terkirim" yang tidak ada di server memang belum pernah masuk — biarkan untuk kirim ulang.
        }

        return $updated;
    }

    /** Batalkan PO (hanya sebelum warehouse menerima pengajuan). */
    public function cancel(SpeedshopPurchaseOrder $po, User $actor, string $reason): void
    {
        if ($po->mo_status === SpeedshopPurchaseOrder::MO_STATUS_SEND_FAILED) {
            // Pastikan memang belum ada di server sebelum dibatalkan secara lokal.
            $this->syncMany(collect([$po]));
            $po->refresh();
        }

        if ($po->mo_status === SpeedshopPurchaseOrder::MO_STATUS_SEND_FAILED) {
            $po->update([
                'status' => 'rejected',
                'mo_status' => SpeedshopPurchaseOrder::MO_STATUS_CANCELLED,
                'mo_reason' => $reason,
                'rejected_by' => $actor->id,
                'rejected_at' => now(),
                'rejected_reason' => $reason,
            ]);

            return;
        }

        $this->apply($po, $this->client->cancel((string) $po->kode, $reason));
        $po->update(['rejected_by' => $actor->id, 'rejected_reason' => $reason]);
    }

    /**
     * Konfirmasi barang diterima: API Modifikasi Ori (verifikasi + tagihan), lalu stok masuk ke speedshop_stock.
     * Dikunci per PO agar klik ganda tidak menambah stok dua kali.
     */
    public function receive(SpeedshopPurchaseOrder $po, User $actor): void
    {
        DB::transaction(function () use ($po, $actor) {
            /** @var SpeedshopPurchaseOrder $locked */
            $locked = SpeedshopPurchaseOrder::query()->whereKey($po->id)->lockForUpdate()->firstOrFail();
            if ($locked->received_at !== null) {
                return;
            }
            if (! in_array($locked->mo_status, [SpeedshopPurchaseOrder::MO_STATUS_SHIPPED, SpeedshopPurchaseOrder::MO_STATUS_RECEIVED], true)) {
                throw ValidationException::withMessages(['po' => 'Barang belum dikirim warehouse Modifikasi Ori.']);
            }

            // Idempoten di server: bila sudah diterima sebelumnya, payload terakhir dikembalikan.
            $payload = $this->client->receive((string) $locked->kode);
            $this->apply($locked, $payload);
            if ($locked->mo_status !== SpeedshopPurchaseOrder::MO_STATUS_RECEIVED) {
                throw ValidationException::withMessages(['po' => 'Server Modifikasi Ori belum mengonfirmasi penerimaan.']);
            }

            $locked->load('items.product');
            foreach ($locked->items as $item) {
                $qty = (int) ($item->qty_approved ?? 0);
                if ($qty <= 0) {
                    continue;
                }
                $stock = SpeedshopStock::query()->firstOrCreate(
                    ['speedshop_id' => (int) $locked->speedshop_id, 'product_id' => (int) $item->product_id],
                    ['jumlah' => 0]
                );
                // Sama dengan verifikasi PO lama: stok outlet di speedshop_stock (bukan products.jumlah).
                $stock->increment('jumlah', $qty);
            }

            $locked->update([
                'status' => 'approved',
                'approved_by' => $actor->id,
                'approved_at' => now(),
                'received_at' => now(),
                'received_by' => $actor->id,
            ]);
        });

        $po->refresh();
    }

    /** Tolak barang kiriman: stok dikembalikan ke warehouse Modifikasi Ori, stok lokal tidak berubah. */
    public function reject(SpeedshopPurchaseOrder $po, User $actor, ?string $reason): void
    {
        if ($po->mo_status !== SpeedshopPurchaseOrder::MO_STATUS_SHIPPED) {
            throw ValidationException::withMessages(['po' => 'Penolakan hanya bisa dilakukan setelah barang dikirim warehouse.']);
        }

        $this->apply($po, $this->client->reject((string) $po->kode, $reason));
        $po->update(['rejected_by' => $actor->id, 'rejected_reason' => $reason]);
    }

    /** Terapkan payload API ke PO lokal (status, qty disetujui, harga, tagihan). */
    public function apply(SpeedshopPurchaseOrder $po, array $payload): void
    {
        $moStatus = (string) ($payload['status'] ?? '');
        if ($moStatus === '') {
            return;
        }

        DB::transaction(function () use ($po, $payload, $moStatus) {
            $po->loadMissing('items.product');

            $remoteBySku = collect((array) ($payload['items'] ?? []))
                ->keyBy(fn (array $row) => mb_strtoupper(trim((string) ($row['sku'] ?? ''))));

            foreach ($po->items as $item) {
                $row = $remoteBySku->get(mb_strtoupper(trim((string) $item->product?->kode_produk)));
                if (! $row) {
                    continue;
                }
                $item->update([
                    'qty_approved' => array_key_exists('qty_approved', $row) && $row['qty_approved'] !== null ? (int) $row['qty_approved'] : null,
                    'harga_satuan' => isset($row['harga']) ? (int) $row['harga'] : $item->harga_satuan,
                ]);
            }

            $localStatus = match ($moStatus) {
                SpeedshopPurchaseOrder::MO_STATUS_REJECTED_BY_WAREHOUSE,
                SpeedshopPurchaseOrder::MO_STATUS_CANCELLED,
                SpeedshopPurchaseOrder::MO_STATUS_REJECTED_BY_PARTNER => 'rejected',
                // "approved" lokal hanya setelah stok benar-benar masuk (lihat receive()).
                default => $po->received_at !== null ? 'approved' : 'pending',
            };

            $po->update([
                'status' => $localStatus,
                'rejected_at' => $localStatus === 'rejected' ? ($po->rejected_at ?? now()) : $po->rejected_at,
                'mo_status' => $moStatus,
                'mo_kode' => $payload['kode'] ?? $po->mo_kode,
                'mo_no_transaksi' => $payload['no_transaksi'] ?? $po->mo_no_transaksi,
                'mo_reason' => $payload['reason'] ?? null,
                'mo_tagihan' => $payload['tagihan'] ?? null,
                'mo_synced_at' => now(),
                'mo_sync_error' => null,
            ]);
        });
    }

    /**
     * Produk lokal 77 Racing untuk SKU katalog Modifikasi Ori (katalog shared, speedshop_scope_id = 0).
     * Dibuat otomatis bila belum ada agar stok bisa dicatat saat barang diterima.
     *
     * @param  array<string, mixed>  $row
     */
    public function localProductFor(array $row): Product
    {
        $sku = mb_strtoupper(trim((string) ($row['sku'] ?? '')));

        $existing = Product::query()
            ->where('kode_produk', $sku)
            ->where('speedshop_scope_id', 0)
            ->orderByRaw("CASE WHEN jenis_produk IS NULL OR jenis_produk = ? THEN 0 ELSE 1 END", [Product::JENIS_ORI])
            ->orderBy('id')
            ->first();
        if ($existing) {
            return $existing;
        }

        $kategoriNama = trim((string) ($row['kategori'] ?? '')) ?: 'Modifikasi Ori';
        $category = Category::query()->firstOrCreate(
            ['nama' => $kategoriNama, 'section' => Category::SECTION_SPEEDSHOP],
        );

        $brandId = null;
        $brandNama = trim((string) ($row['brand'] ?? ''));
        if ($brandNama !== '') {
            $brandId = (Brand::query()->where('nama', $brandNama)->orderBy('id')->first()
                ?? Brand::create(['nama' => $brandNama]))->id;
        }

        $harga = (int) ($row['harga'] ?? 0);

        Log::info('MO partner PO: produk lokal dibuat dari katalog Modifikasi Ori', ['sku' => $sku]);

        return Product::create([
            'kode_produk' => $sku,
            'kode_supplier' => $row['kode_supplier'] ?? null,
            'nama_produk' => (string) ($row['nama'] ?? $sku),
            'category_id' => $category->id,
            'brand_id' => $brandId,
            'jumlah' => 0,
            'harga_pembelian' => $harga,
            'hpp' => $harga,
            'harga_jual_speedshop' => $harga,
            'harga_jual' => (int) ($row['harga_jual'] ?? 0),
            'harga_eceran_terendah' => (int) ($row['harga_eceran_terendah'] ?? 0),
            'warehouse_id' => null,
            'speedshop_scope_id' => 0,
            'jenis_produk' => Product::JENIS_ORI,
        ]);
    }
}
