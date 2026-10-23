<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResellerOrderItem extends Model
{
    protected $fillable = [
        'reseller_order_id',
        'product_id',
        'marketplace_promotion_id',
        'promotion_name',
        'promotion_type',
        'qty',
        'qty_returned',
        'qty_scanned',
        'harga_normal',
        'harga_satuan',
        'harga_jual',
        'biaya_platform',
        'biaya_gratis_ongkir_xtra',
        'biaya_promosi',
        'biaya_lainnya',
        'ongkir',
        'komisi_dinamis',
        'komisi_platform',
        'komisi_affiliate',
        'diskon_promo',
        'is_bonus',
        'subtotal',
        'scanned_at',
        'scanned_by',
    ];

    protected function casts(): array
    {
        return [
            'scanned_at' => 'datetime',
            'is_bonus' => 'boolean',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(ResellerOrder::class, 'reseller_order_id');
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

    public function qtyReturned(): int
    {
        return max(0, (int) ($this->qty_returned ?? 0));
    }

    public function remainingQty(): int
    {
        return max(0, (int) $this->qty);
    }

    public function originalQty(): int
    {
        return $this->remainingQty() + $this->qtyReturned();
    }

    public function isFullyReturned(): bool
    {
        return $this->remainingQty() === 0 && $this->qtyReturned() > 0;
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
