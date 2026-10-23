<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\Rule;

class Category extends Model
{
    use HasFactory;

    public const SECTION_SPEEDSHOP = 'speedshop';

    public const SECTION_PRODUKSI = 'produksi';

    public const SECTION_WAREHOUSE = 'warehouse';

    protected $fillable = ['nama', 'section'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function produksiProducts(): HasMany
    {
        return $this->hasMany(Product::class)->whereNull('warehouse_id');
    }

    public function warehouseProducts(): HasMany
    {
        return $this->hasMany(Product::class)->whereNotNull('warehouse_id');
    }

    public function scopeForProduksi(Builder $query): Builder
    {
        return $query->where('section', self::SECTION_PRODUKSI);
    }

    /**
     * Master kategori speedshop (1 daftar untuk semua cabang).
     */
    public function scopeForSpeedshop(Builder $query): Builder
    {
        return $query->where('section', self::SECTION_SPEEDSHOP);
    }

    /**
     * @deprecated Use forSpeedshop(). Signature kept for call-site compatibility; $speedshopId ignored.
     */
    public function scopeForSpeedshopOutlet(Builder $query, ?int $speedshopId = null): Builder
    {
        return $query->forSpeedshop();
    }

    /**
     * @deprecated Use forSpeedshop(). Warehouse group no longer scopes categories per cabang.
     */
    public function scopeForSpeedshopWarehouseGroup(Builder $query, ?int $warehouseId = null): Builder
    {
        return $query->forSpeedshop();
    }

    /**
     * Kategori untuk dropdown produk / stok speedshop (master global section speedshop).
     *
     * @return \Illuminate\Support\Collection<int, Category>
     */
    public static function categoriesForSpeedshopProductUi(?int $warehouseId = null, ?int $speedshopId = null): \Illuminate\Support\Collection
    {
        return static::query()
            ->forSpeedshop()
            ->orderBy('nama')
            ->get();
    }

    /**
     * @return list<string|\Illuminate\Contracts\Validation\ValidationRule>
     */
    public static function validationRulesCategoryIdForSpeedshopProduct(?int $warehouseId = null, ?int $speedshopId = null): array
    {
        return [
            'required',
            Rule::exists('categories', 'id')->where(fn ($q) => $q->where('section', self::SECTION_SPEEDSHOP)),
        ];
    }

    /**
     * Validasi baris item (integer id) untuk stok masuk / pembelian luar.
     *
     * @return list<string|\Illuminate\Contracts\Validation\ValidationRule>
     */
    public static function validationRulesCategoryIdLineForSpeedshopProduct(?int $warehouseId = null, ?int $speedshopId = null): array
    {
        $rules = self::validationRulesCategoryIdForSpeedshopProduct($warehouseId, $speedshopId);

        return ['required', 'integer', end($rules)];
    }

    public function scopeForWarehouse(Builder $query): Builder
    {
        return $query->where('section', self::SECTION_WAREHOUSE);
    }
}
