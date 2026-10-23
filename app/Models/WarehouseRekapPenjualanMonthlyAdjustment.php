<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseRekapPenjualanMonthlyAdjustment extends Model
{
    protected $fillable = [
        'warehouse_id',
        'warehouse_reseller_id',
        'periode_bulan',
        'diskon',
        'ongkir',
        'iklan_bop',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'diskon' => 'integer',
            'ongkir' => 'integer',
            'iklan_bop' => 'integer',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function warehouseReseller(): BelongsTo
    {
        return $this->belongsTo(WarehouseReseller::class, 'warehouse_reseller_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
