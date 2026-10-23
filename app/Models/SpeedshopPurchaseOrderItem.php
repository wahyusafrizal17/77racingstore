<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpeedshopPurchaseOrderItem extends Model
{
    protected $table = 'speedshop_purchase_order_items';

    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'qty',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(SpeedshopPurchaseOrder::class, 'purchase_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
