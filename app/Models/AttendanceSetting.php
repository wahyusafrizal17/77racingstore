<?php

namespace App\Models;

use App\Support\AttendanceClock;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class AttendanceSetting extends Model
{
    public const TIMEZONE = 'Asia/Jakarta';

    public const DEFAULT_CHECK_IN = '08:00:00';

    public const DEFAULT_CHECK_OUT = '17:00:00';

    public const DEFAULT_CHECK_IN_WINDOW_START = '07:00:00';

    public const DEFAULT_CHECK_IN_WINDOW_END = '09:00:00';

    public const DEFAULT_CHECK_OUT_WINDOW_START = '17:00:00';

    public const DEFAULT_CHECK_OUT_WINDOW_END = '23:59:00';

    /** @var list<int> Senin–Jumat */
    public const DEFAULT_WORK_DAYS = [1, 2, 3, 4, 5];

    protected $fillable = [
        'check_in_time',
        'check_out_time',
        'check_in_window_start',
        'check_in_window_end',
        'check_out_window_start',
        'check_out_window_end',
        'work_days',
    ];

    protected function casts(): array
    {
        return [
            'work_days' => 'array',
        ];
    }

    /** @return list<int> */
    public function workDayNumbers(): array
    {
        $days = $this->work_days;
        if (! is_array($days) || $days === []) {
            return self::DEFAULT_WORK_DAYS;
        }

        return collect($days)
            ->map(fn ($d) => (int) $d)
            ->filter(fn (int $d) => $d >= 1 && $d <= 7)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public function workMinutes(): int
    {
        [$inH, $inM] = self::parseClock($this->check_in_time, self::DEFAULT_CHECK_IN);
        [$outH, $outM] = self::parseClock($this->check_out_time, self::DEFAULT_CHECK_OUT);

        return max(1, ($outH * 60 + $outM) - ($inH * 60 + $inM));
    }

    public static function now(): Carbon
    {
        return Carbon::createFromTimestamp(AttendanceClock::timestamp(), self::TIMEZONE);
    }

    public static function at(?Carbon $at = null): Carbon
    {
        if ($at === null) {
            return self::now();
        }

        return $at->copy()->timezone(self::TIMEZONE);
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    public static function parseClock(mixed $time, string $fallback): array
    {
        foreach ([$time, $fallback] as $raw) {
            if ($raw instanceof \DateTimeInterface) {
                return [(int) $raw->format('H'), (int) $raw->format('i'), (int) $raw->format('s')];
            }
            $raw = trim((string) $raw);
            if ($raw === '') {
                continue;
            }
            if (preg_match('/(?:^|[\sT])(\d{1,2}):(\d{2})(?::(\d{2}))?/', $raw, $m)) {
                $hour = (int) $m[1];
                if ($hour >= 0 && $hour <= 23) {
                    return [$hour, (int) $m[2], (int) ($m[3] ?? 0)];
                }
            }
        }

        return [8, 0, 0];
    }

    public static function clockHi(mixed $time, string $fallback): string
    {
        [$h, $m] = self::parseClock($time, $fallback);

        return sprintf('%02d:%02d', $h, $m);
    }

    public static function applyClock(Carbon $at, mixed $time, string $fallback): Carbon
    {
        [$h, $m, $s] = self::parseClock($time, $fallback);

        return self::at($at)->setTime($h, $m, $s);
    }
}
