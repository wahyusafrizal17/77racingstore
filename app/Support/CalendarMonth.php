<?php

namespace App\Support;

use Carbon\Carbon;

final class CalendarMonth
{
    /**
     * Parse a Y-m value as the first day of that month.
     *
     * Carbon::createFromFormat('Y-m') fills the current day-of-month, so
     * "2026-09" on 31 August overflows to 1 October. Always pin the day to 01.
     */
    public static function parse(?string $yearMonth, ?string $timezone = null): Carbon
    {
        $tz = $timezone ?? (string) config('app.timezone');
        $yearMonth = trim((string) $yearMonth);

        if ($yearMonth === '') {
            return now($tz)->startOfMonth();
        }

        try {
            if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $yearMonth)) {
                throw new \InvalidArgumentException('Invalid year-month.');
            }

            return Carbon::createFromFormat('Y-m-d', $yearMonth.'-01', $tz)->startOfMonth();
        } catch (\Throwable) {
            return now($tz)->startOfMonth();
        }
    }
}
