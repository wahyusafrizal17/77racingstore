<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransaksiPenjualanItem extends Model
{
    protected $table = 'transaksi_penjualan_items';

    protected $fillable = [
        'transaksi_penjualan_id',
        'product_id',
        'qty',
        'harga_satuan',
    ];

    protected $casts = [
        'harga_satuan' => 'decimal:2',
    ];

    public function transaksiPenjualan(): BelongsTo
    {
        return $this->belongsTo(TransaksiPenjualan::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getSubtotalAttribute(): float
    {
        return (float) ($this->harga_satuan * $this->qty);
    }
}
