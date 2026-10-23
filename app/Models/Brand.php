<?php

namespace App\Models;

use App\Support\UploadStorage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    use HasFactory;

    /** @deprecated Brand is global; kept for backward-compatible call sites. */
    public const SECTION_SPEEDSHOP = 'speedshop';

    /** @deprecated Brand is global; kept for backward-compatible call sites. */
    public const SECTION_WAREHOUSE = 'warehouse';

    /** @deprecated Brand is global; kept for backward-compatible call sites. */
    public const SECTION_PRODUKSI = 'produksi';

    protected $fillable = ['nama', 'logo_path'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function logoUrl(): ?string
    {
        return UploadStorage::url($this->logo_path);
    }

    public function produksiProducts(): HasMany
    {
        return $this->hasMany(Product::class)->whereNull('warehouse_id');
    }

    public function warehouseProducts(): HasMany
    {
        return $this->hasMany(Product::class)->whereNotNull('warehouse_id');
    }

    /** Brand master is global — scope kept as no-op for call-site compatibility. */
    public function scopeForProduksi(Builder $query): Builder
    {
        return $query;
    }

    /** Brand master is global — scope kept as no-op for call-site compatibility. */
    public function scopeForSpeedshop(Builder $query): Builder
    {
        return $query;
    }

    /** Brand master is global — scope kept as no-op for call-site compatibility. */
    public function scopeForWarehouse(Builder $query): Builder
    {
        return $query;
    }

    /** @return list<string> */
    public static function speedshopPartLuarBrandNames(): array
    {
        return ['Part Luar', 'LAIN-LAIN'];
    }

    public static function isSpeedshopPartLuarBrandName(?string $name): bool
    {
        if ($name === null || trim($name) === '') {
            return false;
        }

        $normalized = mb_strtolower(trim($name));

        return in_array($normalized, array_map(
            fn (string $label) => mb_strtolower($label),
            self::speedshopPartLuarBrandNames(),
        ), true);
    }

    public function isSpeedshopPartLuarCatalog(): bool
    {
        return self::isSpeedshopPartLuarBrandName($this->nama);
    }

    public function scopeExcludeSpeedshopPartLuarBrands(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            foreach (self::speedshopPartLuarBrandNames() as $name) {
                $q->whereRaw('LOWER(TRIM(nama)) != ?', [mb_strtolower(trim($name))]);
            }
        });
    }
}
