<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;

class AdminRoleCatalog
{
    /**
     * @return array<string, int> key: "{section}|{role}"
     */
    public static function karyawanCountsBySectionRole(): array
    {
        $counts = [];

        User::query()
            ->adminKaryawan()
            ->select(['id', 'section', 'role'])
            ->orderBy('id')
            ->chunkById(500, function ($users) use (&$counts) {
                foreach ($users as $user) {
                    $section = AdminUserPayload::sectionForAdminForm($user->section);
                    $role = AdminUserPayload::roleForAdminForm($user->section, $user->role);
                    $key = self::key($section, $role);
                    $counts[$key] = ($counts[$key] ?? 0) + 1;
                }
            });

        return $counts;
    }

    /** @return Collection<int, array{section: string, section_label: string, role: string, karyawan_count: int, in_catalog: bool}> */
    public static function allRows(): Collection
    {
        $counts = self::karyawanCountsBySectionRole();
        $catalogKeys = [];
        $rows = [];

        foreach (AdminUserPayload::rolesBySectionForAdmin() as $section => $roles) {
            foreach ($roles as $role) {
                $key = self::key($section, $role);
                $catalogKeys[$key] = true;
                $rows[] = self::row($section, $role, $counts[$key] ?? 0, true);
            }
        }

        foreach ($counts as $key => $count) {
            if ($count < 1 || isset($catalogKeys[$key])) {
                continue;
            }
            [$section, $role] = explode('|', $key, 2);
            $rows[] = self::row($section, $role, $count, false);
        }

        return collect($rows)->sortBy([
            fn (array $r) => array_search($r['section'], AdminUserPayload::divisiValues(), true) ?: 99,
            fn (array $r) => $r['role'],
        ])->values();
    }

    /**
     * @param  array{section?: string, role?: string}  $filters
     * @return Collection<int, array{section: string, section_label: string, role: string, karyawan_count: int, in_catalog: bool}>
     */
    public static function filtered(array $filters): Collection
    {
        $section = isset($filters['section']) ? trim((string) $filters['section']) : '';
        $role = isset($filters['role']) ? trim((string) $filters['role']) : '';

        return self::allRows()->filter(function (array $row) use ($section, $role) {
            if ($section !== '' && $row['section'] !== $section) {
                return false;
            }
            if ($role !== '' && $row['role'] !== $role) {
                return false;
            }

            return true;
        })->values();
    }

    /** @return list<string> */
    public static function filterRoleOptions(?string $section = null): array
    {
        $rows = $section
            ? self::allRows()->where('section', $section)
            : self::allRows();

        return $rows->pluck('role')->unique()->sort()->values()->all();
    }

    /**
     * @return array{section: string, section_label: string, role: string, karyawan_count: int, in_catalog: bool}
     */
    private static function row(string $section, string $role, int $count, bool $inCatalog): array
    {
        return [
            'section' => $section,
            'section_label' => AdminUserPayload::sectionLabel($section),
            'role' => $role,
            'karyawan_count' => $count,
            'in_catalog' => $inCatalog,
        ];
    }

    private static function key(string $section, string $role): string
    {
        return $section.'|'.$role;
    }
}
