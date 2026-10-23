<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PerlengkapanProduksi extends Model
{
    use HasFactory;

    protected $table = 'perlengkapan_produksis';

    protected $fillable = ['kode', 'nama', 'harga', 'stok', 'supplier_id'];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(PerlengkapanProduksiStockMovement::class);
    }

    public static function generateKode(): string
    {
        $last = static::where('kode', 'like', 'PP-%')->latest('id')->first();
        $seq = $last ? (int) substr($last->kode, 3) + 1 : 1;
        return 'PP-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
