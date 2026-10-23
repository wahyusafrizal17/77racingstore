<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseTransaksiItem extends Model
{
    protected $table = 'warehouse_transaksi_items';

    protected $fillable = [
        'warehouse_transaksi_id',
        'supplier_id',
        'item_type',
        'product_id',
        'perlengkapan_packing_id',
        'harga_pembelian',
        'diskon',
        'qty',
        'bonus_qty',
    ];

    public function bonusQty(): int
    {
        return max(0, (int) ($this->bonus_qty ?? 0));
    }

    /** Qty yang ditagih ke supplier (tanpa bonus). */
    public function billableQty(): int
    {
        return max(0, (int) $this->qty);
    }

    /** Qty yang masuk stok (pembelian + bonus). */
    public function stockQty(): int
    {
        return $this->billableQty() + $this->bonusQty();
    }

    public function subtotalBruto(): int
    {
        return (int) $this->harga_pembelian * $this->billableQty();
    }

    public function subtotalNet(): int
    {
        return max(0, $this->subtotalBruto() - (int) ($this->diskon ?? 0));
    }

    public function warehouseTransaksi(): BelongsTo
    {
        return $this->belongsTo(WarehouseTransaksi::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function perlengkapanPacking(): BelongsTo
    {
        return $this->belongsTo(PerlengkapanPacking::class);
    }
}
