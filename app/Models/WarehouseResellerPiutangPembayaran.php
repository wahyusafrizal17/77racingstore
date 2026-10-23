<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseResellerPiutangPembayaran extends Model
{
    protected $table = 'warehouse_reseller_piutang_pembayarans';

    protected $fillable = [
        'piutang_id',
        'nominal',
        'speedshop_keuangan_rekening_id',
        'bukti_bayar_path',
        'catatan_pembayaran',
        'dibayar_at',
        'dibayar_by',
    ];

    protected function casts(): array
    {
        return [
            'nominal' => 'integer',
            'dibayar_at' => 'datetime',
        ];
    }

    public function piutang(): BelongsTo
    {
        return $this->belongsTo(WarehouseResellerPiutang::class, 'piutang_id');
    }

    public function sumberRekening(): BelongsTo
    {
        return $this->belongsTo(SpeedshopKeuanganRekening::class, 'speedshop_keuangan_rekening_id');
    }

    public function dibayarByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibayar_by');
    }
}
