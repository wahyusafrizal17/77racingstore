<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseTaker extends Model
{
    protected $table = 'warehouse_takers';

    protected $fillable = [
        'warehouse_id',
        'nama',
        'no_hp',
        'alamat',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function transaksiOnline(): HasMany
    {
        return $this->hasMany(TransaksiOnline::class, 'warehouse_taker_id');
    }

    public function scopeForWarehouse($query, ?int $warehouseId)
    {
        if ($warehouseId === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('warehouse_id', $warehouseId);
    }
}
