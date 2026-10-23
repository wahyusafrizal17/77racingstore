<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpeedshopFinancePermintaanKasKecil extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_REJECTED = 'rejected';

    protected $table = 'speedshop_finance_permintaan_kas_kecil';

    protected $fillable = [
        'speedshop_id',
        'warehouse_id',
        'tanggal',
        'nominal',
        'keuangan_rekening_id',
        'catatan',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
        'rejected_at',
        'rejected_reason',
        'ledger_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'nominal' => 'integer',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function speedshop(): BelongsTo
    {
        return $this->belongsTo(Speedshop::class);
    }

    public function rekening(): BelongsTo
    {
        return $this->belongsTo(SpeedshopKeuanganRekening::class, 'keuangan_rekening_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function ledger(): BelongsTo
    {
        return $this->belongsTo(SpeedshopFinanceLedger::class, 'ledger_id');
    }
}
