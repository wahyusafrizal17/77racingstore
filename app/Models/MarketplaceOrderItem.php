<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceOrderItem extends Model
{
    protected $fillable = [
        'marketplace_order_id',
        'product_id',
        'marketplace_promotion_id',
        'promotion_name',
        'promotion_type',
        'qty',
        'qty_scanned',
        'harga_normal',
        'harga_satuan',
        'diskon_promo',
        'is_bonus',
        'subtotal',
        'scanned_at',
        'scanned_by',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'qty_scanned' => 'integer',
            'harga_normal' => 'integer',
            'harga_satuan' => 'integer',
            'diskon_promo' => 'integer',
            'is_bonus' => 'boolean',
            'subtotal' => 'integer',
            'scanned_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(MarketplaceOrder::class, 'marketplace_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(MarketplacePromotion::class, 'marketplace_promotion_id');
    }

    public function scannedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scanned_by');
    }

    public function remainingToScan(): int
    {
        return max(0, (int) $this->qty - (int) $this->qty_scanned);
    }

    public function isScanComplete(): bool
    {
        return (int) $this->qty_scanned >= (int) $this->qty;
    }
}
