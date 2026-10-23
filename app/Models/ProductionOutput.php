<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionOutput extends Model
{
    protected $fillable = [
        'production_id', 'bahan_siap_produksi_id', 'anggota_id', 'perlu_kartel', 'kartel_anggota_id', 'jumlah_target',
        'jumlah_selesai', 'jumlah_gagal', 'jumlah_gagal_bahan', 'alasan_gagal', 'alasan_gagal_bahan',
    ];

    protected function casts(): array
    {
        return [
            'perlu_kartel' => 'boolean',
            'jumlah_gagal_bahan' => 'integer',
        ];
    }

    public function production(): BelongsTo
    {
        return $this->belongsTo(Production::class);
    }

    public function bahanSiapProduksi(): BelongsTo
    {
        return $this->belongsTo(BahanSiapProduksi::class);
    }

    public function anggota(): BelongsTo
    {
        return $this->belongsTo(Anggota::class);
    }

    public function kartelAnggota(): BelongsTo
    {
        return $this->belongsTo(Anggota::class, 'kartel_anggota_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProductionItem::class);
    }

    public function perlengkapanItems(): HasMany
    {
        return $this->hasMany(ProductionOutputPerlengkapan::class);
    }
}
