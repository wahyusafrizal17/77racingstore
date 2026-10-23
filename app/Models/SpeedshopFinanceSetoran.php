<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class SpeedshopFinanceSetoran extends Model
{
    public const SUBMITTED_BY_SPEEDSHOP = 'speedshop';

    public const SUBMITTED_BY_ACCOUNTING = 'accounting';

    protected $table = 'speedshop_finance_setorans';

    protected $fillable = [
        'speedshop_id',
        'warehouse_id',
        'speedshop_keuangan_rekening_id',
        'speedshop_keuangan_kas_besar_id',
        'tanggal_setor',
        'pendapatan_tunai_hari',
        'pendapatan_digital_hari',
        'modal_laci_snapshot',
        'wajib_setor_tunai',
        'wajib_setor_digital',
        'setor_tunai_actual',
        'setor_digital_actual',
        'setor_kk_ke_kb_actual',
        'status',
        'completed_at',
        'user_id',
        'submitted_by',
        'approved_by',
        'approved_at',
        'rejected_reason',
        'catatan',
        'bukti_kas_kecil_path',
        'bukti_kas_besar_path',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_setor' => 'date',
            'pendapatan_tunai_hari' => 'integer',
            'pendapatan_digital_hari' => 'integer',
            'modal_laci_snapshot' => 'integer',
            'wajib_setor_tunai' => 'integer',
            'wajib_setor_digital' => 'integer',
            'setor_tunai_actual' => 'integer',
            'setor_digital_actual' => 'integer',
            'setor_kk_ke_kb_actual' => 'integer',
            'completed_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isSubmittedByAccounting(): bool
    {
        return ($this->submitted_by ?? self::SUBMITTED_BY_SPEEDSHOP) === self::SUBMITTED_BY_ACCOUNTING;
    }

    public function submittedByLabel(): string
    {
        return $this->isSubmittedByAccounting() ? 'Accounting' : 'Speedshop';
    }

    public function speedshop(): BelongsTo
    {
        return $this->belongsTo(Speedshop::class);
    }

    public function keuanganRekening(): BelongsTo
    {
        return $this->belongsTo(SpeedshopKeuanganRekening::class, 'speedshop_keuangan_rekening_id');
    }

    public function keuanganKasBesar(): BelongsTo
    {
        return $this->belongsTo(SpeedshopKeuanganRekening::class, 'speedshop_keuangan_kas_besar_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function ledgers(): HasMany
    {
        return $this->hasMany(SpeedshopFinanceLedger::class, 'setoran_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SpeedshopFinanceSetoranItem::class, 'setoran_id');
    }

    public function totalNominalSetor(): int
    {
        return (int) $this->setor_tunai_actual
            + (int) $this->setor_digital_actual
            + (int) ($this->setor_kk_ke_kb_actual ?? 0);
    }

    public function buktiKasKecilPublicUrl(): ?string
    {
        if (! $this->bukti_kas_kecil_path) {
            return null;
        }

        return Storage::disk('public')->url($this->bukti_kas_kecil_path);
    }

    public function buktiKasBesarPublicUrl(): ?string
    {
        if (! $this->bukti_kas_besar_path) {
            return null;
        }

        return Storage::disk('public')->url($this->bukti_kas_besar_path);
    }

    /** @deprecated Gunakan buktiKasKecilPublicUrl() */
    public function buktiPublicUrl(): ?string
    {
        return $this->buktiKasKecilPublicUrl();
    }
}
