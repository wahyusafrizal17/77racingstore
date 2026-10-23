<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseSupplierTagihanItem extends Model
{
    protected $table = 'warehouse_supplier_tagihan_items';

    protected $fillable = [
        'tagihan_id',
        'warehouse_transaksi_item_id',
        'stock_in_item_id',
        'item_type',
        'product_id',
        'perlengkapan_packing_id',
        'nama_item',
        'qty',
        'harga_satuan',
        'diskon',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'harga_satuan' => 'integer',
            'diskon' => 'integer',
            'subtotal' => 'integer',
        ];
    }

    public function tagihan(): BelongsTo
    {
        return $this->belongsTo(WarehouseSupplierTagihan::class, 'tagihan_id');
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
