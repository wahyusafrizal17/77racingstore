<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PortalCatalogBundle extends Model
{
    public const OWNER_RESELLER = 'reseller';

    public const OWNER_MO_OPERATOR = 'mo_operator';

    protected $fillable = [
        'warehouse_id',
        'warehouse_reseller_id',
        'owner_portal',
        'created_by',
        'name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<PortalCatalogBundleItem> */
    public function items(): HasMany
    {
        return $this->hasMany(PortalCatalogBundleItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(WarehouseReseller::class, 'warehouse_reseller_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return list<array{product_id: int, qty: int}> */
    public function itemPayload(): array
    {
        return $this->items
            ->map(fn (PortalCatalogBundleItem $item) => [
                'product_id' => (int) $item->product_id,
                'qty' => max(1, (int) $item->qty),
            ])
            ->values()
            ->all();
    }
}
