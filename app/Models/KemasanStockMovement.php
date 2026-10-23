<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KemasanStockMovement extends Model
{
    protected $fillable = [
        'kemasan_id',
        'type',
        'qty',
        'keterangan',
        'reference',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
        ];
    }

    public function kemasan(): BelongsTo
    {
        return $this->belongsTo(Kemasan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
