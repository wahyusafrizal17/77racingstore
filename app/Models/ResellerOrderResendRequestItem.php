<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResellerOrderResendRequestItem extends Model
{
    protected $fillable = [
        'reseller_order_resend_request_id',
        'reseller_order_item_id',
        'product_id',
        'qty_pesanan',
        'qty_diterima',
        'qty_kirim_ulang',
        'qty_scanned',
        'scanned_at',
        'scanned_by',
    ];

    protected function casts(): array
    {
        return [
            'qty_scanned' => 'integer',
            'scanned_at' => 'datetime',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(ResellerOrderResendRequest::class, 'reseller_order_resend_request_id');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(ResellerOrderItem::class, 'reseller_order_item_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scannedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scanned_by');
    }

    public function remainingToScan(): int
    {
        return max(0, (int) $this->qty_kirim_ulang - (int) $this->qty_scanned);
    }

    public function isScanComplete(): bool
    {
        return (int) $this->qty_scanned >= (int) $this->qty_kirim_ulang;
    }
}
