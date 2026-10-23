<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionOutputPerlengkapan extends Model
{
    protected $table = 'production_output_perlengkapan';

    protected $fillable = ['production_output_id', 'perlengkapan_produksi_id', 'jumlah'];

    public function productionOutput(): BelongsTo
    {
        return $this->belongsTo(ProductionOutput::class);
    }

    public function perlengkapanProduksi(): BelongsTo
    {
        return $this->belongsTo(PerlengkapanProduksi::class);
    }
}
