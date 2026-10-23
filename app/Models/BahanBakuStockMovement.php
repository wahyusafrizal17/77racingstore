<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BahanBakuStockMovement extends Model
{
    protected $fillable = [
        'bahan_baku_id',
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

    public function bahanBaku(): BelongsTo
    {
        return $this->belongsTo(BahanBaku::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
