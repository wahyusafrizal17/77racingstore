<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransaksiOfflineItem extends Model
{
    protected $table = 'transaksi_offline_items';

    protected $fillable = [
        'transaksi_offline_id',
        'product_id',
        'qty',
        'harga',
    ];

    public function transaksiOffline(): BelongsTo
    {
        return $this->belongsTo(TransaksiOffline::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
