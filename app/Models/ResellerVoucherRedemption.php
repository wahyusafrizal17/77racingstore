<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResellerVoucherRedemption extends Model
{
    protected $fillable = [
        'reseller_voucher_id',
        'reseller_order_id',
        'warehouse_reseller_id',
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
        return $this->belongsTo(ResellerVoucher::class, 'reseller_voucher_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(ResellerOrder::class, 'reseller_order_id');
    }

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(WarehouseReseller::class, 'warehouse_reseller_id');
    }
}
