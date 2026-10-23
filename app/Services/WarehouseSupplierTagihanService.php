<?php

namespace App\Services;

use App\Models\BahanBaku;
use App\Models\BahanSiapProduksi;
use App\Models\Kemasan;
use App\Models\PerlengkapanProduksi;
use App\Models\StockIn;
use App\Models\StockInItem;
use App\Models\Warehouse;
use App\Models\WarehouseSupplierTagihan;
use App\Models\WarehouseSupplierTagihanItem;
use App\Models\WarehouseTransaksi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WarehouseSupplierTagihanService
{
    public static function kodeFromTransaksiKode(string $transaksiKode): string
    {
        $kode = trim($transaksiKode);
        if (str_starts_with($kode, 'INV-')) {
            return 'TAG-SUP-'.substr($kode, 4);
        }

        return 'TAG-SUP-'.$kode;
    }

    public static function kodeFromStockInKode(string $stockInKode): string
    {
        $kode = trim($stockInKode);

        return str_starts_with($kode, 'TAG-SUP-') ? $kode : 'TAG-SUP-'.$kode;
    }

    public function createFromApprovedStockIn(StockIn $stockIn): ?WarehouseSupplierTagihan
    {
        if (! Schema::hasTable('warehouse_supplier_tagihans')
            || ! Schema::hasColumn('warehouse_supplier_tagihans', 'stock_in_id')) {
            return null;
        }

        if (! $stockIn->isApproved()) {
            return null;
        }

        $existing = WarehouseSupplierTagihan::query()
            ->where('stock_in_id', $stockIn->id)
            ->first();
        if ($existing) {
            return $existing;
        }

        $warehouseId = $this->resolveAccountingWarehouseId();
        if (! $warehouseId) {
            return null;
        }

        $stockIn->loadMissing(['items.itemable', 'supplier']);

        $supplierId = (int) ($stockIn->supplier_id ?? 0);
        if ($supplierId <= 0) {
            return null;
        }

        return DB::transaction(function () use ($stockIn, $warehouseId, $supplierId) {
            $total = 0;
            $lineRows = [];

            foreach ($stockIn->items as $item) {
                $qty = (int) ($item->jumlah ?? 0);
                $hargaSatuan = (int) round((float) ($item->harga ?? 0));
                $subtotal = max(0, $qty * $hargaSatuan);
                $total += $subtotal;

                $lineRows[] = [
                    'stock_in_item_id' => $item->id,
                    'item_type' => self::itemTypeFromStockInItem($item),
                    'product_id' => null,
                    'perlengkapan_packing_id' => null,
                    'nama_item' => self::namaFromStockInItem($item),
                    'qty' => $qty,
                    'harga_satuan' => $hargaSatuan,
                    'diskon' => 0,
                    'subtotal' => $subtotal,
                ];
            }

            $tagihan = WarehouseSupplierTagihan::create([
                'kode' => self::kodeFromStockInKode((string) $stockIn->kode),
                'warehouse_transaksi_id' => null,
                'stock_in_id' => $stockIn->id,
                'warehouse_id' => $warehouseId,
                'supplier_id' => $supplierId,
                'total_nominal' => $total,
                'total_dibayar' => 0,
                'tanggal_tagihan' => $stockIn->approved_at?->toDateString() ?? now()->toDateString(),
                'tanggal_jatuh_tempo' => null,
                'status' => $total > 0
                    ? WarehouseSupplierTagihan::STATUS_BELUM_LUNAS
                    : WarehouseSupplierTagihan::STATUS_LUNAS,
            ]);

            foreach ($lineRows as $row) {
                WarehouseSupplierTagihanItem::create(array_merge($row, ['tagihan_id' => $tagihan->id]));
            }

            return $tagihan->load(['items', 'supplier', 'stockIn']);
        });
    }

    public function resolveAccountingWarehouseId(): ?int
    {
        $id = 0;

        return $id > 0 ? $id : null;
    }

    private static function itemTypeFromStockInItem(StockInItem $item): string
    {
        return match ($item->itemable_type) {
            BahanBaku::class => 'produksi_bahan_baku',
            BahanSiapProduksi::class => 'produksi_bahan_siap',
            Kemasan::class => 'produksi_kemasan',
            PerlengkapanProduksi::class => 'produksi_perlengkapan',
            default => 'produksi_item',
        };
    }

    private static function namaFromStockInItem(StockInItem $item): string
    {
        $itemable = $item->itemable;
        if (! $itemable) {
            return 'Item produksi';
        }

        $kode = trim((string) ($itemable->kode ?? ''));
        $nama = trim((string) ($itemable->nama ?? ''));

        if ($kode !== '' && $nama !== '') {
            return $kode.' — '.$nama;
        }

        return $nama !== '' ? $nama : ($kode !== '' ? $kode : 'Item produksi');
    }

    public function createFromApprovedTransaksi(WarehouseTransaksi $transaksi): ?WarehouseSupplierTagihan
    {
        if (! Schema::hasTable('warehouse_supplier_tagihans')) {
            return null;
        }

        if (! $transaksi->isApproved()) {
            return null;
        }

        if ($transaksi->isPembayaranTunai()) {
            return null;
        }

        if (! $transaksi->isWarehousePembelian()) {
            return null;
        }

        $existing = WarehouseSupplierTagihan::query()
            ->where('warehouse_transaksi_id', $transaksi->id)
            ->first();
        if ($existing) {
            return $existing;
        }

        $transaksi->loadMissing(['items.product', 'items.perlengkapanPacking', 'supplier']);

        $supplierId = (int) ($transaksi->supplier_id ?? 0);
        if ($supplierId <= 0) {
            $supplierId = (int) ($transaksi->items->first()?->supplier_id ?? 0);
        }
        if ($supplierId <= 0) {
            return null;
        }

        return DB::transaction(function () use ($transaksi, $supplierId) {
            $total = 0;
            $lineRows = [];

            foreach ($transaksi->items as $item) {
                $subtotalBruto = (int) round((float) ($item->harga_pembelian ?? 0)) * $item->billableQty();
                $diskon = max(0, (int) ($item->diskon ?? 0));
                $subtotal = max(0, $subtotalBruto - $diskon);
                $total += $subtotal;

                $nama = 'Item';
                if (($item->item_type ?? 'product') === 'perlengkapan_packing') {
                    $nama = $item->perlengkapanPacking?->nama ?? 'Perlengkapan Packing';
                } else {
                    $nama = $item->product?->nama_produk ?? 'Produk';
                }

                $lineRows[] = [
                    'warehouse_transaksi_item_id' => $item->id,
                    'item_type' => $item->item_type ?? 'product',
                    'product_id' => $item->product_id,
                    'perlengkapan_packing_id' => $item->perlengkapan_packing_id,
                    'nama_item' => $nama,
                    'qty' => $item->billableQty(),
                    'harga_satuan' => (int) round((float) ($item->harga_pembelian ?? 0)),
                    'diskon' => $diskon,
                    'subtotal' => $subtotal,
                ];
            }

            $tagihan = WarehouseSupplierTagihan::create([
                'kode' => self::kodeFromTransaksiKode((string) $transaksi->kode),
                'warehouse_transaksi_id' => $transaksi->id,
                'warehouse_id' => (int) $transaksi->warehouse_id,
                'supplier_id' => $supplierId,
                'total_nominal' => $total,
                'total_dibayar' => 0,
                'tanggal_tagihan' => $transaksi->approved_at?->toDateString() ?? now()->toDateString(),
                'tanggal_jatuh_tempo' => $transaksi->tanggal_jatuh_tempo?->toDateString(),
                'status' => $total > 0
                    ? WarehouseSupplierTagihan::STATUS_BELUM_LUNAS
                    : WarehouseSupplierTagihan::STATUS_LUNAS,
            ]);

            foreach ($lineRows as $row) {
                WarehouseSupplierTagihanItem::create(array_merge($row, ['tagihan_id' => $tagihan->id]));
            }

            return $tagihan->load(['items', 'supplier', 'warehouseTransaksi']);
        });
    }

    public function createFromApprovedSpeedshopStokInSupplier(WarehouseTransaksi $transaksi, int $speedshopId): ?WarehouseSupplierTagihan
    {
        if (! Schema::hasTable('warehouse_supplier_tagihans')) {
            return null;
        }

        if (($transaksi->tipe ?? '') !== 'stok_in_supplier' || ! $transaksi->isApproved()) {
            return null;
        }

        if ($speedshopId <= 0) {
            return null;
        }

        $existing = WarehouseSupplierTagihan::query()
            ->where('warehouse_transaksi_id', $transaksi->id)
            ->first();
        if ($existing) {
            $updates = [];
            if (Schema::hasColumn('warehouse_supplier_tagihans', 'speedshop_id')
                && (int) ($existing->speedshop_id ?? 0) !== $speedshopId) {
                $updates['speedshop_id'] = $speedshopId;
            }
            if (! $existing->tanggal_jatuh_tempo && $transaksi->tanggal_jatuh_tempo) {
                $updates['tanggal_jatuh_tempo'] = $transaksi->tanggal_jatuh_tempo->toDateString();
            }
            if ($updates !== []) {
                $existing->update($updates);
            }

            return $existing;
        }

        $transaksi->loadMissing(['items.product', 'supplier']);

        $supplierId = (int) ($transaksi->supplier_id ?? 0);
        if ($supplierId <= 0) {
            $supplierId = (int) ($transaksi->items->first()?->supplier_id ?? 0);
        }
        if ($supplierId <= 0) {
            return null;
        }

        return DB::transaction(function () use ($transaksi, $supplierId, $speedshopId) {
            $total = 0;
            $lineRows = [];

            foreach ($transaksi->items as $item) {
                $subtotalBruto = (int) round((float) ($item->harga_pembelian ?? 0)) * $item->billableQty();
                $diskon = max(0, (int) ($item->diskon ?? 0));
                $subtotal = max(0, $subtotalBruto - $diskon);
                $total += $subtotal;

                $lineRows[] = [
                    'warehouse_transaksi_item_id' => $item->id,
                    'item_type' => $item->item_type ?? 'product',
                    'product_id' => $item->product_id,
                    'perlengkapan_packing_id' => $item->perlengkapan_packing_id,
                    'nama_item' => $item->product?->nama_produk ?? 'Produk',
                    'qty' => $item->billableQty(),
                    'harga_satuan' => (int) round((float) ($item->harga_pembelian ?? 0)),
                    'diskon' => $diskon,
                    'subtotal' => $subtotal,
                ];
            }

            $payload = [
                'kode' => self::kodeFromTransaksiKode((string) $transaksi->kode),
                'warehouse_transaksi_id' => $transaksi->id,
                'warehouse_id' => (int) $transaksi->warehouse_id,
                'supplier_id' => $supplierId,
                'total_nominal' => $total,
                'total_dibayar' => 0,
                'tanggal_tagihan' => $transaksi->approved_at?->toDateString() ?? now()->toDateString(),
                'tanggal_jatuh_tempo' => $transaksi->tanggal_jatuh_tempo?->toDateString(),
                'status' => $total > 0
                    ? WarehouseSupplierTagihan::STATUS_BELUM_LUNAS
                    : WarehouseSupplierTagihan::STATUS_LUNAS,
            ];
            if (Schema::hasColumn('warehouse_supplier_tagihans', 'speedshop_id')) {
                $payload['speedshop_id'] = $speedshopId;
            }

            $tagihan = WarehouseSupplierTagihan::create($payload);

            foreach ($lineRows as $row) {
                WarehouseSupplierTagihanItem::create(array_merge($row, ['tagihan_id' => $tagihan->id]));
            }

            return $tagihan->load(['items', 'supplier', 'warehouseTransaksi']);
        });
    }

    public function syncMissingForSpeedshop(int $speedshopId): int
    {
        if (! Schema::hasTable('warehouse_supplier_tagihans') || $speedshopId <= 0) {
            return 0;
        }

        $created = 0;
        $q = WarehouseTransaksi::query()
            ->where('tipe', 'stok_in_supplier')
            ->where('status', 'approved')
            ->whereHas('user', fn ($uq) => $uq->where('speedshop_id', $speedshopId));

        foreach ($q->with(['items.product', 'supplier', 'user'])->cursor() as $transaksi) {
            $has = WarehouseSupplierTagihan::query()
                ->where('warehouse_transaksi_id', $transaksi->id)
                ->exists();
            if ($has) {
                continue;
            }
            if ($this->createFromApprovedSpeedshopStokInSupplier($transaksi, $speedshopId)) {
                $created++;
            }
        }

        return $created;
    }

    public function resyncFromApprovedTransaksi(WarehouseTransaksi $transaksi): ?WarehouseSupplierTagihan
    {
        if (! Schema::hasTable('warehouse_supplier_tagihans')) {
            return null;
        }

        $existing = WarehouseSupplierTagihan::query()
            ->where('warehouse_transaksi_id', $transaksi->id)
            ->first();

        if ($existing) {
            WarehouseSupplierTagihanItem::query()
                ->where('tagihan_id', $existing->id)
                ->delete();
            $existing->delete();
        }

        if ($transaksi->isPembayaranTunai()) {
            return null;
        }

        return $this->createFromApprovedTransaksi($transaksi->fresh(['items.product', 'items.perlengkapanPacking', 'supplier', 'user']));
    }

    public function syncMissingForWarehouse(?int $warehouseId): int
    {
        if (! Schema::hasTable('warehouse_supplier_tagihans') || ! $warehouseId) {
            return 0;
        }

        $this->pruneNonWarehousePembelian($warehouseId);
        $this->reconcileStatusesForWarehouse($warehouseId);
        $this->backfillJatuhTempoFromTransaksi($warehouseId);

        $created = 0;
        $q = WarehouseTransaksi::query()
            ->warehousePembelian()
            ->where('status', 'approved')
            ->where('warehouse_id', $warehouseId);

        foreach ($q->with(['items.product', 'items.perlengkapanPacking', 'supplier', 'user'])->cursor() as $transaksi) {
            if ($transaksi->isPembayaranTunai()) {
                continue;
            }
            $has = WarehouseSupplierTagihan::query()
                ->where('warehouse_transaksi_id', $transaksi->id)
                ->exists();
            if ($has) {
                continue;
            }
            if ($this->createFromApprovedTransaksi($transaksi)) {
                $created++;
            }
        }

        if (Schema::hasTable('stock_ins') && Schema::hasColumn('warehouse_supplier_tagihans', 'stock_in_id')) {
            $stockIns = StockIn::query()
                ->where('status', 'approved')
                ->orderBy('id');

            foreach ($stockIns->with(['items.itemable', 'supplier'])->cursor() as $stockIn) {
                $has = WarehouseSupplierTagihan::query()
                    ->where('stock_in_id', $stockIn->id)
                    ->exists();
                if ($has) {
                    continue;
                }
                if ($this->createFromApprovedStockIn($stockIn)) {
                    $created++;
                }
            }
        }

        return $created;
    }

    /** Hapus hutang yang tersync dari transaksi non-pembelian warehouse (mis. stok-in speedshop). Entri manual/saldo awal tidak disentuh. */
    public function pruneNonWarehousePembelian(int $warehouseId): int
    {
        if (! Schema::hasTable('warehouse_supplier_tagihans')) {
            return 0;
        }

        $validTransaksiIds = WarehouseTransaksi::query()
            ->warehousePembelian()
            ->where('warehouse_id', $warehouseId)
            ->pluck('id');

        $ids = WarehouseSupplierTagihan::query()
            ->where('warehouse_id', $warehouseId)
            ->whereNull('stock_in_id')
            ->whereNotNull('warehouse_transaksi_id')
            ->whereNotIn('warehouse_transaksi_id', $validTransaksiIds)
            ->when(
                Schema::hasColumn('warehouse_supplier_tagihans', 'speedshop_id'),
                fn ($q) => $q->whereNull('speedshop_id'),
            )
            ->pluck('id');

        if ($ids->isEmpty()) {
            return 0;
        }

        WarehouseSupplierTagihanItem::query()
            ->whereIn('tagihan_id', $ids)
            ->delete();

        return WarehouseSupplierTagihan::query()->whereIn('id', $ids)->delete();
    }

    /** Tandai lunas otomatis bila nominal 0 atau sudah terbayar penuh. */
    public function reconcileStatusesForWarehouse(int $warehouseId): int
    {
        if (! Schema::hasTable('warehouse_supplier_tagihans')) {
            return 0;
        }

        return WarehouseSupplierTagihan::query()
            ->where('warehouse_id', $warehouseId)
            ->where('status', WarehouseSupplierTagihan::STATUS_BELUM_LUNAS)
            ->where(function ($q) {
                $q->where('total_nominal', '<=', 0)
                    ->orWhereColumn('total_dibayar', '>=', 'total_nominal');
            })
            ->update([
                'status' => WarehouseSupplierTagihan::STATUS_LUNAS,
                'total_dibayar' => DB::raw('total_nominal'),
            ]);
    }

    /** Salin tanggal jatuh tempo dari transaksi pembelian bila kolom tagihan masih kosong. */
    public function backfillJatuhTempoFromTransaksi(?int $warehouseId = null): int
    {
        if (! Schema::hasTable('warehouse_supplier_tagihans')) {
            return 0;
        }

        $query = WarehouseSupplierTagihan::query()
            ->whereNull('tanggal_jatuh_tempo')
            ->whereNotNull('warehouse_transaksi_id');

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $updated = 0;
        foreach ($query->with('warehouseTransaksi:id,tanggal_jatuh_tempo')->cursor() as $tagihan) {
            $fromTransaksi = $tagihan->warehouseTransaksi?->tanggal_jatuh_tempo;
            if (! $fromTransaksi) {
                continue;
            }

            WarehouseSupplierTagihan::query()
                ->whereKey($tagihan->id)
                ->update(['tanggal_jatuh_tempo' => $fromTransaksi->toDateString()]);
            $updated++;
        }

        return $updated;
    }
}
