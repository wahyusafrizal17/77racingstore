<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseResellerTagihanMidtransPayment extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SETTLEMENT = 'settlement';

    public const STATUS_CAPTURE = 'capture';

    public const STATUS_DENY = 'deny';

    public const STATUS_CANCEL = 'cancel';

    public const STATUS_EXPIRE = 'expire';

    public const STATUS_FAILURE = 'failure';

    protected $table = 'warehouse_reseller_tagihan_midtrans_payments';

    protected $fillable = [
        'tagihan_id',
        'tagihan_ids',
        'order_id',
        'snap_token',
        'gross_amount',
        'transaction_status',
        'fraud_status',
        'payment_type',
        'midtrans_transaction_id',
        'tagihan_pembayaran_id',
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
            'tagihan_ids' => 'array',
        ];
    }

    /** @return list<int> */
    public function resolvedTagihanIds(): array
    {
        $ids = collect($this->tagihan_ids ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($ids === [] && (int) $this->tagihan_id > 0) {
            return [(int) $this->tagihan_id];
        }

        return $ids;
    }

    public function isBulk(): bool
    {
        return count($this->resolvedTagihanIds()) > 1;
    }

    public function tagihan(): BelongsTo
    {
        return $this->belongsTo(WarehouseResellerTagihan::class, 'tagihan_id');
    }

    public function tagihanPembayaran(): BelongsTo
    {
        return $this->belongsTo(WarehouseResellerTagihanPembayaran::class, 'tagihan_pembayaran_id');
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

    public function isTerminalFailure(): bool
    {
        return in_array($this->transaction_status, [
            self::STATUS_DENY,
            self::STATUS_CANCEL,
            self::STATUS_EXPIRE,
            self::STATUS_FAILURE,
        ], true);
    }
}
