<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceVoucherRedemption extends Model
{
    protected $fillable = [
        'marketplace_voucher_id',
        'marketplace_order_id',
        'user_id',
        'subtotal_bruto',
        'diskon_nominal',
    ];

    protected function casts(): array
    {
        return [
            'subtotal_bruto' => 'integer',
            'diskon_nominal' => 'integer',
        ];
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(MarketplaceVoucher::class, 'marketplace_voucher_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(MarketplaceOrder::class, 'marketplace_order_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
