<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory;

    public const HEAD_OFFICE_NAME = 'Head Office';

    /** @var list<string> */
    private const LEGACY_HEAD_OFFICE_NAMES = ['Gudang Bandung'];

    public static function headOffice(): ?self
    {
        return static::query()
            ->where(function ($query) {
                $query->where('nama', self::HEAD_OFFICE_NAME)
                    ->orWhereIn('nama', self::LEGACY_HEAD_OFFICE_NAMES);
            })
            ->orderByRaw('CASE WHEN nama = ? THEN 0 ELSE 1 END', [self::HEAD_OFFICE_NAME])
            ->first();
    }

    protected $fillable = ['nama', 'no_hp', 'alamat', 'kota_id', 'stok_audit_percent'];

    protected function casts(): array
    {
        return [
            'stok_audit_percent' => 'decimal:2',
        ];
    }

    public function kota()
    {
        return $this->belongsTo(Kota::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function jasaServis()
    {
        return $this->hasMany(JasaServis::class);
    }
}
