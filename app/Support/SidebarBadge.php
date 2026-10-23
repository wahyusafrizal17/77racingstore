<?php

namespace App\Support;

/**
 * Formatter badge angka untuk sidebar / menu ERP.
 * Reusable untuk Persetujuan dan menu lain di masa depan.
 */
final class SidebarBadge
{
    public static function label(int $count, int $max = 99): ?string
    {
        if ($count <= 0) {
            return null;
        }

        if ($count > $max) {
            return $max.'+';
        }

        return (string) $count;
    }
}
