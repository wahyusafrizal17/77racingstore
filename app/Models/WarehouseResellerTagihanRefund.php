<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseResellerTagihanRefund extends Model
{
    protected $table = 'warehouse_reseller_tagihan_refunds';

    protected $fillable = [
        'tagihan_id',
        'nominal',
        'speedshop_keuangan_rekening_id',
        'bukti_refund_path',
        'catatan',
        'direfund_at',
        'direfund_by',
    ];

    protected function casts(): array
    {
        return [
            'nominal' => 'integer',
            'direfund_at' => 'datetime',
        ];
    }

    public function tagihan(): BelongsTo
    {
        return $this->belongsTo(WarehouseResellerTagihan::class, 'tagihan_id');
    }

    public function sumberRekening(): BelongsTo
    {
        return $this->belongsTo(SpeedshopKeuanganRekening::class, 'speedshop_keuangan_rekening_id');
    }

    public function direfundByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'direfund_by');
    }
}
