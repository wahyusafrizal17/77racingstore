<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseResellerTagihanItem extends Model
{
    protected $table = 'warehouse_reseller_tagihan_items';

    protected $fillable = [
        'tagihan_id',
        'product_id',
        'qty',
        'harga_satuan',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'harga_satuan' => 'integer',
            'subtotal' => 'integer',
        ];
    }

    public function tagihan(): BelongsTo
    {
        return $this->belongsTo(WarehouseResellerTagihan::class, 'tagihan_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
