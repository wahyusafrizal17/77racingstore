<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BahanGagalProduksi extends Model
{
    use HasFactory;

    protected $table = 'bahan_gagal_produksis';

    protected $fillable = ['kode', 'nama', 'stok', 'bahan_siap_produksi_id'];

    public function bahanSiapProduksi()
    {
        return $this->belongsTo(BahanSiapProduksi::class);
    }

    public static function generateKode(): string
    {
        $last = static::where('kode', 'like', 'BGP-%')->latest('id')->first();
        $seq = $last ? (int) substr($last->kode, 4) + 1 : 1;
        return 'BGP-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
