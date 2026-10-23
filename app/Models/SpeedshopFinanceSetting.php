<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpeedshopFinanceSetting extends Model
{
    protected $fillable = [
        'speedshop_id',
        'warehouse_id',
        'modal_laci_per_hari',
    ];

    protected function casts(): array
    {
        return [
            'modal_laci_per_hari' => 'integer',
        ];
    }

    public function speedshop(): BelongsTo
    {
        return $this->belongsTo(Speedshop::class);
    }
}
