<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransaksiOnlineItem extends Model
{
    protected $table = 'transaksi_online_items';

    protected $fillable = ['transaksi_online_id', 'product_id', 'qty', 'harga'];

    public function transaksiOnline()
    {
        return $this->belongsTo(TransaksiOnline::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
