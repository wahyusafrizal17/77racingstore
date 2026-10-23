<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class SpeedshopRincianBiayaAdjustment extends Model
{
    protected $table = 'speedshop_rincian_biaya_adjustments';

    protected $fillable = [
        'speedshop_id',
        'rincian_biaya_entry_id',
        'tanggal_pengembalian',
        'nominal_pengurangan',
        'biaya_admin',
        'catatan',
        'bukti_path',
        'finance_ledger_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_pengembalian' => 'date',
            'nominal_pengurangan' => 'integer',
            'biaya_admin' => 'integer',
        ];
    }

    public function speedshop(): BelongsTo
    {
        return $this->belongsTo(Speedshop::class);
    }

    public function rincianBiayaEntry(): BelongsTo
    {
        return $this->belongsTo(SpeedshopRincianBiayaEntry::class, 'rincian_biaya_entry_id');
    }

    public function financeLedger(): BelongsTo
    {
        return $this->belongsTo(SpeedshopFinanceLedger::class, 'finance_ledger_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function totalPenguranganEfektif(): int
    {
        return (int) $this->nominal_pengurangan + (int) $this->biaya_admin;
    }

    public function buktiPublicUrl(): ?string
    {
        $path = $this->bukti_path;
        if (! is_string($path) || $path === '') {
            return null;
        }

        return Storage::disk((string) config('filesystems.upload_disk', 'public'))->url($path);
    }
}
