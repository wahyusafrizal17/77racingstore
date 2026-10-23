<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    public const SECTION_PRODUKSI = 'produksi';

    public const SECTION_WAREHOUSE = 'warehouse';

    public const SECTION_SPEEDSHOP = 'speedshop';

    /** @return list<string> */
    public static function sections(): array
    {
        return [
            self::SECTION_PRODUKSI,
            self::SECTION_WAREHOUSE,
            self::SECTION_SPEEDSHOP,
        ];
    }

    protected $fillable = [
        'section',
        'speedshop_id',
        'supplier_scope',
        'kode_supplier',
        'nama',
        'no_hp',
        'email',
        'alamat',
        'kota_id',
        'jenis_rekening',
        'nama_rekening',
        'nomor_rekening',
    ];

    public function scopeForSection($query, string $section)
    {
        return $query->where('section', $section);
    }

    public function scopeForSpeedshop($query, int $speedshopId, bool $includeShared = true)
    {
        $scopes = ['speedshop:'.$speedshopId];
        if ($includeShared) {
            $scopes[] = 'speedshop:0';
        }

        return $query
            ->where('section', self::SECTION_SPEEDSHOP)
            ->whereIn('supplier_scope', $scopes);
    }

    public function kota()
    {
        return $this->belongsTo(Kota::class);
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }
}
