<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Jam absensi mengikuti Asia/Jakarta.
 * Jika jam OS server miring (sering terjadi di VPS), selisih diukur dari header Date HTTPS
 * agar jendela 07:00–09:00 tidak tertutup padahal di Indonesia sudah pagi.
 */
final class AttendanceClock
{
    private static ?int $requestOffset = null;

    public static function timestamp(): int
    {
        return time() + self::offsetSeconds();
    }

    public static function offsetSeconds(): int
    {
        if (self::$requestOffset !== null) {
            return self::$requestOffset;
        }

        try {
            $cached = Cache::get('attendance.clock_offset_seconds');
            if (is_int($cached)) {
                return self::$requestOffset = $cached;
            }
        } catch (\Throwable) {
        }

        $offset = self::measureOffsetSeconds();
        if (abs($offset) < 90) {
            $offset = 0;
        }

        try {
            Cache::put('attendance.clock_offset_seconds', $offset, 300);
        } catch (\Throwable) {
        }

        if (abs($offset) >= 90) {
            Log::warning('Jam server miring; absensi memakai koreksi waktu.', [
                'offset_seconds' => $offset,
            ]);
        }

        return self::$requestOffset = $offset;
    }

    private static function measureOffsetSeconds(): int
    {
        foreach ([
            'https://1.1.1.1',
            'https://cloudflare.com',
            'https://www.google.com',
        ] as $url) {
            try {
                $date = Http::timeout(1.5)
                    ->connectTimeout(1)
                    ->withOptions(['verify' => true])
                    ->head($url)
                    ->header('Date');
                if (! is_string($date) || trim($date) === '') {
                    continue;
                }

                return Carbon::parse($date)->utc()->getTimestamp() - time();
            } catch (\Throwable) {
                continue;
            }
        }

        return 0;
    }
}
