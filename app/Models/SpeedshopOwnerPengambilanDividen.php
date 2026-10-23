<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class SpeedshopOwnerPengambilanDividen extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_REJECTED = 'rejected';

    protected $table = 'speedshop_owner_pengambilan_dividens';

    protected $fillable = [
        'owner_user_id',
        'speedshop_id',
        'speedshop_rekening_id',
        'owner_rekening_id',
        'tanggal',
        'nominal',
        'catatan',
        'bukti_pengajuan_path',
        'bukti_transfer_path',
        'status',
        'requested_by',
        'processed_by',
        'processed_at',
        'rejected_reason',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'nominal' => 'integer',
            'processed_at' => 'datetime',
        ];
    }

    public function ownerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function speedshop(): BelongsTo
    {
        return $this->belongsTo(Speedshop::class);
    }

    public function speedshopRekening(): BelongsTo
    {
        return $this->belongsTo(SpeedshopKeuanganRekening::class, 'speedshop_rekening_id');
    }

    public function ownerRekening(): BelongsTo
    {
        return $this->belongsTo(SpeedshopKeuanganRekening::class, 'owner_rekening_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function buktiPengajuanUrl(): ?string
    {
        return $this->bukti_pengajuan_path
            ? Storage::disk('public')->url($this->bukti_pengajuan_path)
            : null;
    }

    public function buktiTransferUrl(): ?string
    {
        return $this->bukti_transfer_path
            ? Storage::disk('public')->url($this->bukti_transfer_path)
            : null;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
