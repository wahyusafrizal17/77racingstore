<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpeedshopJurnalSaquEntry extends Model
{
    protected $fillable = [
        'speedshop_id',
        'warehouse_id',
        'speedshop_finance_ledger_id',
        'speedshop_keuangan_rekening_id',
        'tanggal',
        'akun',
        'keterangan',
        'pic',
        'kredit',
        'debet',
        'speedshop_rincian_biaya_entry_id',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'kredit' => 'float',
        'debet' => 'float',
    ];

    public function speedshop(): BelongsTo
    {
        return $this->belongsTo(Speedshop::class);
    }
}
