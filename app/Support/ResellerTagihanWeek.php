<?php

namespace App\Support;

use Carbon\Carbon;

/** Periode tagihan reseller: Senin–Sabtu (6 hari). Minggu = istirahat / rollover piutang. */
class ResellerTagihanWeek
{
    /** @return array{senin: Carbon, sabtu: Carbon} */
    public static function boundsForDate(?Carbon $date = null): array
    {
        $date = ($date ?? now())->copy()->startOfDay();

        if ($date->isSunday()) {
            $senin = $date->copy()->addDay();
        } else {
            $senin = $date->copy()->startOfWeek(Carbon::MONDAY);
        }

        return [
            'senin' => $senin,
            'sabtu' => $senin->copy()->addDays(5),
        ];
    }

    /** @return array{senin: Carbon, sabtu: Carbon} */
    public static function previousBounds(?Carbon $date = null): array
    {
        $current = self::boundsForDate($date);

        return [
            'senin' => $current['senin']->copy()->subWeek(),
            'sabtu' => $current['sabtu']->copy()->subWeek(),
        ];
    }

    public static function label(array $bounds): string
    {
        return $bounds['senin']->translatedFormat('d M Y').' – '.$bounds['sabtu']->translatedFormat('d M Y');
    }

    /** Minggu sebelumnya sudah lewat Sabtu dan perlu di-close ke piutang. */
    public static function previousWeekNeedsRollover(?Carbon $date = null): bool
    {
        $date = ($date ?? now())->copy()->startOfDay();
        $previous = self::previousBounds($date);

        return $date->greaterThan($previous['sabtu']);
    }
}
