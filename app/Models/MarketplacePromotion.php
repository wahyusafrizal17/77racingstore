<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketplacePromotion extends Model
{
    public const TYPE_FLASH_SALE = 'flash_sale';

    public const TYPE_BOGO = 'bogo';

    public const TYPE_BUNDLE = 'bundle';

    public const CHANNEL_MARKETPLACE = 'marketplace';

    public const CHANNEL_RESELLER = 'reseller';

    public const CHANNEL_BOTH = 'both';

    public const DISCOUNT_PERCENT = 'percent';

    public const DISCOUNT_NOMINAL = 'nominal';

    protected $fillable = [
        'warehouse_id',
        'created_by',
        'name',
        'type',
        'channel',
        'discount_type',
        'discount_value',
        'bundle_price',
        'buy_qty',
        'get_qty',
        'max_uses',
        'used_count',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'integer',
            'bundle_price' => 'integer',
            'buy_qty' => 'integer',
            'get_qty' => 'integer',
            'max_uses' => 'integer',
            'used_count' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /** @return array<string, string> */
    public static function typeLabels(): array
    {
        return [
            self::TYPE_FLASH_SALE => 'Flash Sale',
            self::TYPE_BOGO => 'Beli X Gratis Y',
            self::TYPE_BUNDLE => 'Paket Bundle',
        ];
    }

    public function typeLabel(): string
    {
        return self::typeLabels()[$this->type] ?? ucfirst($this->type);
    }

    /** @return array<string, string> */
    public static function channelLabels(): array
    {
        return [
            self::CHANNEL_MARKETPLACE => 'Marketplace saja',
            self::CHANNEL_RESELLER => 'Reseller saja',
            self::CHANNEL_BOTH => 'Marketplace & Reseller',
        ];
    }

    public function channelLabel(): string
    {
        return self::channelLabels()[$this->channel] ?? self::channelLabels()[self::CHANNEL_BOTH];
    }

    public function appliesToChannel(string $channel): bool
    {
        return in_array($this->channel ?? self::CHANNEL_BOTH, [$channel, self::CHANNEL_BOTH], true);
    }

    public function scopeForChannel(Builder $query, string $channel): Builder
    {
        return $query->whereIn('channel', [$channel, self::CHANNEL_BOTH]);
    }

    public function scopeCurrentlyActive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->where(function (Builder $builder) {
                $builder->whereNull('max_uses')
                    ->orWhereColumn('used_count', '<', 'max_uses');
            });
    }

    public function products(): HasMany
    {
        return $this->hasMany(MarketplacePromotionProduct::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
