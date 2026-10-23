<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AttendanceLocation extends Model
{
    public const CODE_WAREHOUSE = 'warehouse';

    public const CODE_BANDUNG = 'speedshop_bandung';

    public const CODE_BEKASI = 'speedshop_bekasi';

    public const CODE_BOGOR = 'speedshop_bogor';

    public const CODE_DEPOK = 'speedshop_depok';

    protected $fillable = [
        'code',
        'name',
        'locatable_type',
        'locatable_id',
        'latitude',
        'longitude',
        'radius_meters',
        'is_active',
        'check_in_time',
        'check_out_time',
        'break_start_time',
        'break_end_time',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'radius_meters' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function locatable(): MorphTo
    {
        return $this->morphTo();
    }

    public function records(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function weekdayHours(): HasMany
    {
        return $this->hasMany(AttendanceLocationWeekdayHour::class)->orderBy('day_of_week');
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    public function displayName(): string
    {
        if ($this->code === self::CODE_WAREHOUSE) {
            return Warehouse::HEAD_OFFICE_NAME;
        }

        if ($this->locatable_type === Warehouse::class) {
            $nama = $this->locatable?->nama;

            return $nama !== null && $nama !== ''
                ? (string) $nama
                : Warehouse::HEAD_OFFICE_NAME;
        }

        if ($this->name === 'Gudang Bandung') {
            return Warehouse::HEAD_OFFICE_NAME;
        }

        return (string) $this->name;
    }

    public function isHeadOffice(): bool
    {
        return $this->code === self::CODE_WAREHOUSE;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Jarak Haversine dalam meter.
     */
    public static function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6371000.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $earth * asin(min(1, sqrt($a)));
    }
}
