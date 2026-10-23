<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MoOperatorChannel extends Model
{
    protected $fillable = [
        'user_id',
        'warehouse_reseller_id',
        'warehouse_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function warehouseReseller(): BelongsTo
    {
        return $this->belongsTo(WarehouseReseller::class, 'warehouse_reseller_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
