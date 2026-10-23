<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class SpeedshopRincianBiayaEntry extends Model
{
    public const JENIS_BIAYA_OPERASIONAL = 'operasional';

    public const JENIS_BIAYA_NON_OPERASIONAL = 'non_operasional';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    /** Batas atas (inklusif) nominal: (1) Kas Kecil — Accounting vs Manager; (2) biaya dari Rekening (Accounting) — langsung posting vs wajib Manager. */
    public const KAS_KECIL_VERIFIKASI_ACCOUNTING_MAX_NOMINAL = 2_000_000;

    protected $fillable = [
        'speedshop_id',
        'warehouse_id',
        'tanggal',
        'jenis_operasional',
        'no_akun',
        'nama_akun',
        'keterangan',
        'bukti_lampiran_path',
        'pic',
        'metode',
        'speedshop_keuangan_rekening_id',
        'speedshop_keuangan_rekening_tujuan_id',
        'nominal',
        'approval_status',
        'submitted_by',
        'approved_by',
        'approved_at',
        'rejection_note',
        'finance_ledger_id',
        'finance_ledger_tujuan_id',
        'audit_has_temuan',
        'audit_deskripsi',
        'audit_penyelesaian',
        'audit_by',
        'audit_at',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'nominal' => 'float',
        'approved_at' => 'datetime',
        'audit_has_temuan' => 'boolean',
        'audit_at' => 'datetime',
    ];

    public function speedshop(): BelongsTo
    {
        return $this->belongsTo(Speedshop::class);
    }

    public function keuanganRekening(): BelongsTo
    {
        return $this->belongsTo(SpeedshopKeuanganRekening::class, 'speedshop_keuangan_rekening_id');
    }

    public function keuanganRekeningTujuan(): BelongsTo
    {
        return $this->belongsTo(SpeedshopKeuanganRekening::class, 'speedshop_keuangan_rekening_tujuan_id');
    }

    public function financeLedgerTujuan(): BelongsTo
    {
        return $this->belongsTo(SpeedshopFinanceLedger::class, 'finance_ledger_tujuan_id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function auditedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'audit_by');
    }

    public function financeLedger(): BelongsTo
    {
        return $this->belongsTo(SpeedshopFinanceLedger::class, 'finance_ledger_id');
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(SpeedshopRincianBiayaAdjustment::class, 'rincian_biaya_entry_id')
            ->orderByDesc('tanggal_pengembalian')
            ->orderByDesc('id');
    }

    public function nominalAsli(): int
    {
        return (int) round((float) ($this->nominal ?? 0));
    }

    public function totalPenyesuaian(): int
    {
        if (! Schema::hasTable('speedshop_rincian_biaya_adjustments')) {
            return 0;
        }

        if ($this->relationLoaded('adjustments')) {
            return (int) $this->adjustments->sum(
                fn (SpeedshopRincianBiayaAdjustment $a) => (int) $a->nominal_pengurangan + (int) $a->biaya_admin
            );
        }

        return (int) ($this->adjustments()
            ->selectRaw('COALESCE(SUM(nominal_pengurangan + biaya_admin), 0) as total')
            ->value('total') ?? 0);
    }

    public function nominalEfektif(): int
    {
        return max(0, $this->nominalAsli() - $this->totalPenyesuaian());
    }

    public function sisaDapatDikurangi(): int
    {
        return $this->nominalEfektif();
    }

    public static function sqlNominalEfektif(string $tableAlias = 'speedshop_rincian_biaya_entries'): string
    {
        if (! Schema::hasTable('speedshop_rincian_biaya_adjustments')) {
            return "CAST({$tableAlias}.nominal AS DECIMAL(18,2))";
        }

        return "GREATEST(CAST({$tableAlias}.nominal AS DECIMAL(18,2)) - COALESCE((SELECT SUM(a.nominal_pengurangan + a.biaya_admin) FROM speedshop_rincian_biaya_adjustments a WHERE a.rincian_biaya_entry_id = {$tableAlias}.id), 0), 0)";
    }

    public function buktiLampiranPublicUrl(): ?string
    {
        if (! Schema::hasColumn($this->getTable(), 'bukti_lampiran_path')) {
            return null;
        }
        $path = $this->bukti_lampiran_path;
        if (! is_string($path) || $path === '') {
            return null;
        }

        return Storage::disk((string) config('filesystems.upload_disk', 'public'))->url($path);
    }

    /** Data lama / sudah disetujui: masuk laporan & total. */
    public function isApprovedForReporting(): bool
    {
        $s = $this->approval_status;

        return $s === null || $s === self::STATUS_APPROVED;
    }

    public function isPending(): bool
    {
        return $this->approval_status === self::STATUS_PENDING;
    }

    public function isRejected(): bool
    {
        return $this->approval_status === self::STATUS_REJECTED;
    }

    /** Pengajuan biaya tunai Kas Kecil (bukan input langsung rekening) yang masih pending. */
    public function isPendingKasKecilPengajuan(): bool
    {
        if ($this->approval_status !== self::STATUS_PENDING) {
            return false;
        }
        if (($this->metode ?? 'tunai') !== 'tunai') {
            return false;
        }
        $hasRek = Schema::hasColumn($this->getTable(), 'speedshop_keuangan_rekening_id')
            && (int) ($this->speedshop_keuangan_rekening_id ?? 0) > 0;

        return ! $hasRek;
    }

    public function pendingKasKecilRequiresManagerVerification(): bool
    {
        if (! $this->isPendingKasKecilPengajuan()) {
            return false;
        }

        return (int) round((float) ($this->nominal ?? 0)) > self::KAS_KECIL_VERIFIKASI_ACCOUNTING_MAX_NOMINAL;
    }

    public function pendingKasKecilRequiresAccountingVerification(): bool
    {
        if (! $this->isPendingKasKecilPengajuan()) {
            return false;
        }

        return (int) round((float) ($this->nominal ?? 0)) <= self::KAS_KECIL_VERIFIKASI_ACCOUNTING_MAX_NOMINAL;
    }

    /** Biaya input Accounting dari rekening, belum diposting jurnal — menunggu Manager (nominal > batas). */
    public function isPendingAccountingRekeningAwaitingManager(): bool
    {
        if ($this->approval_status !== self::STATUS_PENDING) {
            return false;
        }
        if (($this->metode ?? '') !== 'transfer') {
            return false;
        }
        if (! Schema::hasColumn($this->getTable(), 'speedshop_keuangan_rekening_id')) {
            return false;
        }
        if ((int) ($this->speedshop_keuangan_rekening_id ?? 0) <= 0) {
            return false;
        }
        if ((int) ($this->finance_ledger_id ?? 0) > 0) {
            return false;
        }

        return (int) round((float) ($this->nominal ?? 0)) > self::KAS_KECIL_VERIFIKASI_ACCOUNTING_MAX_NOMINAL;
    }
}
