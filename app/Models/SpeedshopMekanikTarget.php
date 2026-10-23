<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpeedshopMekanikTarget extends Model
{
    protected $fillable = [
        'mekanik_id',
        'periode_bulan',
        'target_bulanan',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'target_bulanan' => 'integer',
        ];
    }

    public function mekanik(): BelongsTo
    {
        return $this->belongsTo(Mekanik::class);
    }
}
