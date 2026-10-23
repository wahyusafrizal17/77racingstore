<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerlengkapanProduksiStockMovement extends Model
{
    protected $fillable = [
        'perlengkapan_produksi_id',
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

    public function perlengkapanProduksi(): BelongsTo
    {
        return $this->belongsTo(PerlengkapanProduksi::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
