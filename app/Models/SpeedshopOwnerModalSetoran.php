<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class SpeedshopOwnerModalSetoran extends Model
{
    protected $table = 'speedshop_owner_modal_setorans';

    protected $fillable = [
        'owner_user_id',
        'speedshop_id',
        'sumber_owner_rekening_id',
        'tujuan_speedshop_rekening_id',
        'tanggal',
        'nominal',
        'catatan',
        'bukti_path',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'nominal' => 'integer',
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

    public function sumberOwnerRekening(): BelongsTo
    {
        return $this->belongsTo(SpeedshopKeuanganRekening::class, 'sumber_owner_rekening_id');
    }

    public function tujuanSpeedshopRekening(): BelongsTo
    {
        return $this->belongsTo(SpeedshopKeuanganRekening::class, 'tujuan_speedshop_rekening_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function buktiUrl(): ?string
    {
        if (! $this->bukti_path) {
            return null;
        }

        return Storage::disk('public')->url($this->bukti_path);
    }
}
