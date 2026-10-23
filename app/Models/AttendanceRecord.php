<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    public const TYPE_CHECK_IN = 'check_in';

    public const TYPE_CHECK_OUT = 'check_out';

    public const STATUS_VALID = 'valid';

    public const STATUS_PENDING = 'pending';

    public const STATUS_REJECTED = 'rejected';

    /** @return list<string> */
    public static function activeStatuses(): array
    {
        return [self::STATUS_VALID, self::STATUS_PENDING];
    }

    protected $fillable = [
        'user_id',
        'validator_user_id',
        'attendance_location_id',
        'type',
        'checked_at',
        'latitude',
        'longitude',
        'accuracy_meters',
        'distance_meters',
        'selfie_path',
        'status',
        'validated_by',
        'validated_at',
        'rejection_reason',
        'is_late',
        'late_reason',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'checked_at' => 'datetime',
            'latitude' => 'float',
            'longitude' => 'float',
            'accuracy_meters' => 'float',
            'distance_meters' => 'float',
            'is_late' => 'boolean',
            'validated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function validatorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validator_user_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(AttendanceLocation::class, 'attendance_location_id');
    }

    public function validatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Menunggu validasi',
            self::STATUS_REJECTED => 'Ditolak',
            default => 'Valid',
        };
    }

    public function profilePhotoUrl(): ?string
    {
        $path = $this->user?->foto_profil;
        if (! $path) {
            return null;
        }

        return \App\Support\UploadStorage::url($path);
    }

    public function selfieUrl(): string
    {
        return (string) \App\Support\UploadStorage::url($this->selfie_path);
    }

    public function typeLabel(): string
    {
        return $this->type === self::TYPE_CHECK_OUT ? 'Absen Pulang' : 'Absen Masuk';
    }
}
