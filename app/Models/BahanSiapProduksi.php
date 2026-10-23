<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BahanSiapProduksi extends Model
{
    protected $table = 'bahan_siap_produksis';

    protected $fillable = ['kode', 'nama', 'stok'];

    public function bahanGagalProduksi(): HasOne
    {
        return $this->hasOne(BahanGagalProduksi::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(BahanSiapProduksiStockMovement::class);
    }

    /**
     * Inventori Bahan Setengah Jadi = stok lulus. Jika stok lulus 0 dan stok gagal masih ada di Bahan Gagal Produksi, baris tidak ditampilkan di sini (hanya di Bahan Gagal Produksi).
     */
    public function scopeForInventoriSetengahJadi($query)
    {
        return $query->where(function ($q) {
            $q->where('stok', '>', 0)
                ->orWhereDoesntHave('bahanGagalProduksi', function ($q) {
                    $q->where('stok', '>', 0);
                });
        });
    }

    public static function generateKode(): string
    {
        $last = static::where('kode', 'like', 'BSP-%')->latest('id')->first();
        $seq = $last ? (int) substr($last->kode, -4) + 1 : 1;

        return 'BSP-'.str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
