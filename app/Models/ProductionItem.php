<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionItem extends Model
{
    protected $fillable = ['production_output_id', 'bahan_baku_id', 'jumlah', 'jumlah_cacat'];

    protected function casts(): array
    {
        return [
            'jumlah_cacat' => 'integer',
        ];
    }

    public function productionOutput(): BelongsTo
    {
        return $this->belongsTo(ProductionOutput::class);
    }

    public function bahanBaku(): BelongsTo
    {
        return $this->belongsTo(BahanBaku::class);
    }
}
