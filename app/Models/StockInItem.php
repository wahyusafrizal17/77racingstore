<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockInItem extends Model
{
    use HasFactory;

    protected $fillable = ['stock_in_id', 'itemable_type', 'itemable_id', 'jumlah', 'harga'];

    public function stockIn()
    {
        return $this->belongsTo(StockIn::class);
    }

    public function itemable()
    {
        return $this->morphTo();
    }
}
