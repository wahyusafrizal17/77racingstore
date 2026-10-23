<?php

namespace App\Models;

use App\Support\Warehouse\ProduksiMutasiTipe;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MutasiItem extends Model
{
    protected $fillable = ['mutasi_id', 'product_id', 'quantity', 'produksi_tipe'];

    public function mutasi(): BelongsTo
    {
        return $this->belongsTo(Mutasi::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function produksiTipeLabel(): string
    {
        return ProduksiMutasiTipe::label($this->produksi_tipe);
    }
}
