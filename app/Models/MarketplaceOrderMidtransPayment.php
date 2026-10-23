<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceOrderMidtransPayment extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SETTLEMENT = 'settlement';

    public const STATUS_CAPTURE = 'capture';

    public const STATUS_DENY = 'deny';

    public const STATUS_CANCEL = 'cancel';

    public const STATUS_EXPIRE = 'expire';

    public const STATUS_FAILURE = 'failure';

    protected $fillable = [
        'marketplace_order_id',
        'order_id',
        'snap_token',
        'gross_amount',
        'transaction_status',
        'fraud_status',
        'payment_type',
        'midtrans_transaction_id',
        'created_by',
        'settled_at',
        'raw_notification',
    ];

    protected function casts(): array
    {
        return [
            'gross_amount' => 'integer',
            'settled_at' => 'datetime',
            'raw_notification' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(MarketplaceOrder::class, 'marketplace_order_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isSettled(): bool
    {
        return $this->settled_at !== null;
    }

    public function isSuccessfulStatus(): bool
    {
        if ($this->transaction_status === self::STATUS_SETTLEMENT) {
            return true;
        }

        if ($this->transaction_status === self::STATUS_CAPTURE) {
            return ($this->fraud_status ?? 'accept') === 'accept';
        }

        return false;
    }
}
