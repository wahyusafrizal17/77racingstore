<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BahanSiapProduksiStockMovement extends Model
{
    protected $fillable = [
        'bahan_siap_produksi_id',
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

    public function bahanSiapProduksi(): BelongsTo
    {
        return $this->belongsTo(BahanSiapProduksi::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
