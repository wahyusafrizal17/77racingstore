<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseAsetTetap extends Model
{
    public const KATEGORI_RAK = 'rak';

    public const KATEGORI_INVENTARIS = 'inventaris';

    protected $fillable = [
        'warehouse_id',
        'kategori',
        'kode',
        'nama',
        'tanggal_perolehan',
        'nilai_perolehan',
        'nilai_buku',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_perolehan' => 'date',
            'nilai_perolehan' => 'decimal:2',
            'nilai_buku' => 'decimal:2',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function kategoriLabel(): string
    {
        return match ($this->kategori) {
            self::KATEGORI_RAK => 'Rak / Etalase',
            self::KATEGORI_INVENTARIS => 'Inventaris / Aset Peralatan',
            default => (string) $this->kategori,
        };
    }

    /** @return list<string> */
    public static function kategoriOptions(): array
    {
        return [self::KATEGORI_RAK, self::KATEGORI_INVENTARIS];
    }
}
