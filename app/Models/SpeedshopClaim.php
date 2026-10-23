<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpeedshopClaim extends Model
{
    protected $fillable = [
        'claim_number',
        'speedshop_id',
        'warehouse_id',
        'product_id',
        'qty',
        'mutasi_id',
        'catatan',
        'status',
    ];

    public function speedshop(): BelongsTo
    {
        return $this->belongsTo(Speedshop::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function mutasi(): BelongsTo
    {
        return $this->belongsTo(Mutasi::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
