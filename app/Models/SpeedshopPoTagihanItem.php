<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpeedshopPoTagihanItem extends Model
{
    protected $table = 'speedshop_po_tagihan_items';

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
        return $this->belongsTo(SpeedshopPoTagihan::class, 'tagihan_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
