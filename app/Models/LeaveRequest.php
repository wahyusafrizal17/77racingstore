<?php

namespace App\Models;

use App\Support\AdminUserPayload;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class LeaveRequest extends Model
{
    public const TYPE_IZIN = 'izin';

    public const TYPE_SAKIT = 'sakit';

    public const TYPE_CUTI = 'cuti';

    public const TYPE_MASUK_LIBUR = 'masuk_libur';

    public const TYPE_SETENGAH_MASUK = 'setengah_masuk';

    public const TYPE_SETENGAH_PULANG = 'setengah_pulang';

    public const TYPE_WFH = 'wfh';

    public const HALF_DAY_CLOCK = '12:00';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'attendance_validator_id',
        'head_approver_id',
        'manager_approver_id',
        'type',
        'start_date',
        'end_date',
        'reason',
        'surat_dokter_path',
        'status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
        'head_signed_at',
        'head_signature_token',
        'manager_signed_at',
        'manager_signature_token',
        'owner_approver_id',
        'owner_signed_at',
        'owner_signature_token',
        'pemohon_signed_at',
        'pemohon_signature_token',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'reviewed_at' => 'datetime',
            'head_signed_at' => 'datetime',
            'manager_signed_at' => 'datetime',
            'owner_signed_at' => 'datetime',
            'pemohon_signed_at' => 'datetime',
        ];
    }

    /** Semua label jenis (termasuk yang hanya untuk divisi tertentu). */
    public static function allTypeLabels(): array
    {
        return [
            self::TYPE_IZIN => 'Izin',
            self::TYPE_SAKIT => 'Sakit',
            self::TYPE_CUTI => 'Cuti',
            self::TYPE_WFH => 'WFH',
            self::TYPE_MASUK_LIBUR => 'Masuk Hari Libur',
            self::TYPE_SETENGAH_MASUK => 'Izin 1/2 Hari (Masuk 12.00)',
            self::TYPE_SETENGAH_PULANG => 'Izin 1/2 Hari (Pulang 12.00)',
        ];
    }

    /** @return array<string, string> Opsi speedshop (lengkap). */
    public static function speedshopTypeOptions(): array
    {
        return [
            self::TYPE_IZIN => 'Izin',
            self::TYPE_SAKIT => 'Sakit',
            self::TYPE_CUTI => 'Cuti',
            self::TYPE_MASUK_LIBUR => 'Masuk Hari Libur',
            self::TYPE_SETENGAH_MASUK => 'Izin 1/2 Hari (Masuk 12.00)',
            self::TYPE_SETENGAH_PULANG => 'Izin 1/2 Hari (Pulang 12.00)',
        ];
    }

    /** @return array<string, string> Opsi 77racing. */
    public static function modifikasiOriTypeOptions(): array
    {
        return [
            self::TYPE_IZIN => 'Izin',
            self::TYPE_SAKIT => 'Sakit',
            self::TYPE_CUTI => 'Cuti',
            self::TYPE_WFH => 'WFH',
            self::TYPE_MASUK_LIBUR => 'Masuk Hari Libur',
            self::TYPE_SETENGAH_MASUK => 'Izin 1/2 Hari (Masuk 12.00)',
            self::TYPE_SETENGAH_PULANG => 'Izin 1/2 Hari (Pulang 12.00)',
        ];
    }

    /** @return array<string, string> */
    public static function typeOptions(): array
    {
        return self::speedshopTypeOptions();
    }

    public static function isModifikasiOriApplicant(User $user): bool
    {
        return $user->section === User::SECTION_MODIFIKASI_ORI
            || AdminUserPayload::isModifikasiOriSection($user->section);
    }

    public static function canSubmitWfh(User $user): bool
    {
        return self::isModifikasiOriApplicant($user);
    }

    /** @return array<string, string> Jenis pengajuan sesuai divisi & role. */
    public static function typeOptionsForUser(User $user): array
    {
        if ($user->section === 'speedshop') {
            return self::speedshopTypeOptions();
        }

        if (self::isModifikasiOriApplicant($user)) {
            return self::modifikasiOriTypeOptions();
        }

        return [
            self::TYPE_SAKIT => 'Sakit',
            self::TYPE_CUTI => 'Cuti',
        ];
    }

    /** @return list<string> */
    public static function halfDayTypes(): array
    {
        return [self::TYPE_SETENGAH_MASUK, self::TYPE_SETENGAH_PULANG];
    }

    public static function isHalfDayType(?string $type): bool
    {
        return in_array((string) $type, self::halfDayTypes(), true);
    }

    /** Satu tanggal (bukan rentang mulai–selesai). */
    public static function requiresSingleLeaveDate(?string $type): bool
    {
        return self::isHalfDayType($type)
            || $type === self::TYPE_MASUK_LIBUR
            || $type === self::TYPE_WFH;
    }

    /** Surat dokter wajib untuk sakit lebih dari 1 hari kalender. */
    public static function requiresSuratDokter(?string $type, mixed $start = null, mixed $end = null): bool
    {
        if ($type !== self::TYPE_SAKIT || $start === null || $end === null || $start === '' || $end === '') {
            return false;
        }

        try {
            $from = Carbon::parse($start)->toDateString();
            $to = Carbon::parse($end)->toDateString();
        } catch (\Throwable) {
            return false;
        }

        return $to > $from;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function assignedValidator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'attendance_validator_id');
    }

    public function headApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'head_approver_id');
    }

    public function managerApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_approver_id');
    }

    public function ownerApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_approver_id');
    }

    public function requiresHeadSignature(): bool
    {
        return $this->head_approver_id !== null;
    }

    public function headSigned(): bool
    {
        return $this->head_signed_at !== null;
    }

    public function managerSigned(): bool
    {
        return $this->manager_signed_at !== null;
    }

    public function ownerSigned(): bool
    {
        return $this->owner_signed_at !== null;
    }

    public function pemohonSigned(): bool
    {
        return $this->pemohon_signed_at !== null && filled($this->pemohon_signature_token);
    }

    public function requiresOwnerAcknowledgment(): bool
    {
        return $this->owner_approver_id !== null;
    }

    /** TTD "Diketahui" Owner tidak menahan persetujuan. */
    public function requiredSignaturesComplete(): bool
    {
        if ($this->requiresHeadSignature() && ! $this->headSigned()) {
            return false;
        }

        return ! $this->manager_approver_id || $this->managerSigned();
    }

    public function approverUserId(): ?int
    {
        if ($this->isPending()) {
            if ($this->requiresHeadSignature() && ! $this->headSigned()) {
                return $this->head_approver_id ? (int) $this->head_approver_id : null;
            }

            if ($this->manager_approver_id && ! $this->managerSigned()) {
                return (int) $this->manager_approver_id;
            }
        }

        if ($this->attendance_validator_id) {
            return (int) $this->attendance_validator_id;
        }

        return $this->user?->attendance_validator_id
            ? (int) $this->user->attendance_validator_id
            : null;
    }

    public function pendingStageLabel(): string
    {
        if (! $this->isPending()) {
            return $this->statusLabel();
        }

        if ($this->requiresHeadSignature() && ! $this->headSigned()) {
            $head = $this->headApprover;
            if ($head && (string) $head->role === 'Manager') {
                return 'Menunggu Manager';
            }

            return 'Menunggu Head';
        }

        if ($this->manager_approver_id && ! $this->managerSigned()) {
            if ($this->managerApprover?->isOwner()) {
                return 'Menunggu Owner';
            }

            return 'Menunggu Manager';
        }

        return 'Menunggu';
    }

    public function typeLabel(): string
    {
        return self::allTypeLabels()[$this->type] ?? ucfirst((string) $this->type);
    }

    public function typeIcon(): string
    {
        return match ((string) $this->type) {
            self::TYPE_IZIN => '📋',
            self::TYPE_SAKIT => '🤒',
            self::TYPE_CUTI => '🏖️',
            self::TYPE_WFH => '🏠',
            self::TYPE_MASUK_LIBUR => '🏢',
            self::TYPE_SETENGAH_MASUK => '🕛',
            self::TYPE_SETENGAH_PULANG => '🕛',
            default => '📋',
        };
    }

    public function statusCssClass(): string
    {
        return match ((string) $this->status) {
            self::STATUS_APPROVED => 'approved',
            self::STATUS_REJECTED => 'rejected',
            self::STATUS_CANCELLED => 'cancelled',
            default => 'pending',
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'Disetujui',
            self::STATUS_REJECTED => 'Ditolak',
            self::STATUS_CANCELLED => 'Dibatalkan',
            default => $this->pendingStageLabel(),
        };
    }

    public function dateRangeLabel(): string
    {
        $start = $this->start_date->translatedFormat('d M Y');
        $end = $this->end_date->translatedFormat('d M Y');

        return $start === $end ? $start : $start.' – '.$end;
    }

    public function hasSuratDokter(): bool
    {
        return filled($this->surat_dokter_path);
    }

    public function suratDokterUrl(): ?string
    {
        if (! $this->hasSuratDokter()) {
            return null;
        }

        return Storage::disk('public')->url($this->surat_dokter_path);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
