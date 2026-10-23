<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerlengkapanPacking extends Model
{
    use HasFactory;

    protected $table = 'perlengkapan_packings';

    protected $fillable = ['kode', 'nama', 'harga', 'stok', 'supplier_id'];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public static function generateKode(): string
    {
        $last = static::where('kode', 'like', 'PPK-%')->latest('id')->first();
        $seq = $last ? (int) substr((string) $last->kode, 4) + 1 : 1;

        return 'PPK-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
