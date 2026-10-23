<?php

namespace App\Support\Speedshop;

final class SpeedshopTargetMath
{
    public const SPAREPART_SHARE = 0.65;

    public const JASA_SHARE = 0.35;

    public const MEKANIK_DEFAULT = 55_000_000;

    public const CABANG_DEFAULT = 250_000_000;

    /**
     * @return array{sparepart: int, jasa: int}
     */
    public static function split(int $total): array
    {
        $sparepart = (int) round($total * self::SPAREPART_SHARE);

        return [
            'sparepart' => $sparepart,
            'jasa' => $total - $sparepart,
        ];
    }

    public static function daily(int $total, int $daysInMonth): int
    {
        if ($daysInMonth <= 0) {
            return 0;
        }

        return intdiv($total, $daysInMonth);
    }

    public static function percent(float $actual, int $target): int
    {
        if ($target <= 0) {
            return 0;
        }

        return (int) round(($actual / $target) * 100);
    }

    public static function mekanikTarget(?int $saved): int
    {
        return $saved === null ? self::MEKANIK_DEFAULT : $saved;
    }

    public static function cabangTarget(?int $saved): int
    {
        return $saved === null ? self::CABANG_DEFAULT : $saved;
    }
}
