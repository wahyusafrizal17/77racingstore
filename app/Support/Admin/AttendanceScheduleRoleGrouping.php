<?php

namespace App\Support\Admin;

use App\Models\User;
use Illuminate\Support\Collection;

final class AttendanceScheduleRoleGrouping
{
    /** @var list<string> */
    private const SPEEDSHOP_GROUP_ORDER = [
        'FLP',
        'KEPALA MEKANIK',
        'MEKANIK',
        'MANAGER',
        'ACCOUNTING',
        'STAF',
        'HEAD AREA',
        'LAINNYA',
    ];

    public static function groupLabel(User $user): string
    {
        $role = trim((string) $user->role);

        if ($role === User::ROLE_SPEEDSHOP_FLP) {
            return 'FLP';
        }

        if ($role === User::ROLE_SPEEDSHOP_MEKANIK) {
            $level = mb_strtoupper(trim((string) ($user->mekanik?->mekanikLevel?->nama ?? '')));
            if ($level !== '' && (str_contains($level, 'KEPALA') || str_contains($level, 'HEAD'))) {
                return 'KEPALA MEKANIK';
            }

            return 'MEKANIK';
        }

        if ($role !== '') {
            return mb_strtoupper($role);
        }

        return 'LAINNYA';
    }

    /**
     * @param  Collection<int, User>  $employees
     * @return list<array{label: string, employees: Collection<int, User>}>
     */
    public static function grouped(Collection $employees): array
    {
        $buckets = $employees
            ->groupBy(fn (User $u) => self::groupLabel($u))
            ->sortBy(fn (Collection $_, string $label) => self::sortIndex($label));

        $out = [];
        foreach ($buckets as $label => $group) {
            $out[] = [
                'label' => (string) $label,
                'employees' => $group->sortBy('name')->values(),
            ];
        }

        return $out;
    }

    private static function sortIndex(string $label): int
    {
        $idx = array_search($label, self::SPEEDSHOP_GROUP_ORDER, true);

        return $idx === false ? 999 : $idx;
    }
}
