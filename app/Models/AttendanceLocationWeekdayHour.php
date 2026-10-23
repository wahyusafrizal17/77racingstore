<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceLocationWeekdayHour extends Model
{
    /** @var array<int, string> */
    public const DAY_LABELS = [
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
        7 => 'Minggu',
    ];

    protected $fillable = [
        'attendance_location_id',
        'day_of_week',
        'check_in_time',
        'check_out_time',
        'check_in_window_start',
        'check_in_window_end',
        'check_out_window_start',
        'check_out_window_end',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(AttendanceLocation::class, 'attendance_location_id');
    }

    public function dayLabel(): string
    {
        return self::DAY_LABELS[$this->day_of_week] ?? 'Hari '.$this->day_of_week;
    }

    public function checkInHi(): string
    {
        return $this->hi($this->check_in_time, AttendanceSetting::DEFAULT_CHECK_IN);
    }

    public function checkOutHi(): string
    {
        return $this->hi($this->check_out_time, AttendanceSetting::DEFAULT_CHECK_OUT);
    }

    public function checkInWindowStartHi(): string
    {
        return $this->hi($this->check_in_window_start, AttendanceSetting::DEFAULT_CHECK_IN_WINDOW_START);
    }

    public function checkInWindowEndHi(): string
    {
        return $this->hi($this->check_in_window_end, AttendanceSetting::DEFAULT_CHECK_IN_WINDOW_END);
    }

    public function checkOutWindowStartHi(): string
    {
        return $this->hi($this->check_out_window_start, $this->check_out_time ?? AttendanceSetting::DEFAULT_CHECK_OUT);
    }

    public function checkOutWindowEndHi(): string
    {
        return $this->hi($this->check_out_window_end, AttendanceSetting::DEFAULT_CHECK_OUT_WINDOW_END);
    }

    private function hi(?string $value, string $fallback): string
    {
        return substr((string) ($value ?? $fallback), 0, 5);
    }
}
