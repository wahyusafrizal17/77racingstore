<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplacePromotionProduct extends Model
{
    public const ROLE_TARGET = 'target';

    public const ROLE_BUY = 'buy';

    public const ROLE_GET = 'get';

    public const ROLE_BUNDLE = 'bundle';

    protected $fillable = [
        'marketplace_promotion_id',
        'product_id',
        'role',
        'qty',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(MarketplacePromotion::class, 'marketplace_promotion_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
