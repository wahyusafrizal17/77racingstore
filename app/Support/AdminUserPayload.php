<?php

namespace App\Support;

use App\Models\User;
class AdminUserPayload
{
    /** @return list<array{value: string, label: string}> */
    public static function divisiOptions(): array
    {
        return [
            ['value' => User::SECTION_OWNER, 'label' => 'Owner'],
            ['value' => 'speedshop', 'label' => 'Speedshop'],
            ['value' => User::SECTION_MODIFIKASI_ORI, 'label' => '77racing'],
        ];
    }

    /** @return list<string> */
    public static function divisiValues(): array
    {
        return array_column(self::divisiOptions(), 'value');
    }

    /** @return list<string> */
    public static function speedshopRoles(): array
    {
        return [
            'Manager',
            User::ROLE_SPEEDSHOP_HEAD_AREA,
            'Accounting',
            User::ROLE_SPEEDSHOP_MEKANIK,
            User::ROLE_SPEEDSHOP_FLP,
            'Staf',
        ];
    }

    /** @return list<string> */
    public static function modifikasiOriRoles(): array
    {
        return [
            'Manager',
            User::ROLE_MODIFIKASI_HEAD_PRODUKSI,
            User::ROLE_MODIFIKASI_STAF_PRODUKSI,
            User::ROLE_MODIFIKASI_STAF_INVENTORI,
            User::ROLE_MODIFIKASI_HEAD_WAREHOUSE,
            User::ROLE_MODIFIKASI_STAF_PROCESSING_WAREHOUSE,
            User::ROLE_WAREHOUSE_STAF_WAREHOUSE,
            User::ROLE_MODIFIKASI_HEAD_MARKETING,
            User::ROLE_MODIFIKASI_STAF_MARKETING,
            User::ROLE_MODIFIKASI_HEAD_ACCOUNTING,
            User::ROLE_MODIFIKASI_STAF_ACCOUNTING,
            User::ROLE_MODIFIKASI_HEAD_HR,
            User::ROLE_MODIFIKASI_STAF_HR,
            User::ROLE_MODIFIKASI_HEAD_AUDIT,
        ];
    }

    /** @return list<string> */
    public static function modifikasiOriWarehouseRoles(): array
    {
        return [
            User::ROLE_MODIFIKASI_HEAD_WAREHOUSE,
            User::ROLE_MODIFIKASI_STAF_PROCESSING_WAREHOUSE,
            User::ROLE_WAREHOUSE_STAF_WAREHOUSE,
            User::ROLE_MODIFIKASI_STAF_INVENTORI,
            User::ROLE_WAREHOUSE_STAF_PACKING,
            User::ROLE_MODIFIKASI_HEAD_ACCOUNTING,
            User::ROLE_MODIFIKASI_HEAD_AUDIT,
            User::ROLE_MODIFIKASI_STAF_ACCOUNTING,
            User::ROLE_WAREHOUSE_STAFF_ACCOUNTING_RESELLER,
        ];
    }

    /** Role speedshop yang wajib satu akun per cabang (Accounting, FLP, Staf, Mekanik). */
    public static function speedshopRolesPerCabang(): array
    {
        return self::speedshopRolesRequiringCabang();
    }

    /** @return list<string> */
    public static function speedshopRolesRequiringCabang(): array
    {
        return [
            'Accounting',
            User::ROLE_SPEEDSHOP_MEKANIK,
            User::ROLE_SPEEDSHOP_FLP,
            'Staf',
        ];
    }

    /** @return list<string> */
    public static function speedshopRolesWithoutCabang(): array
    {
        return [];
    }

    /** Role speedshop yang boleh/wajib multi-cabang via checkbox. */
    public static function speedshopRolesWithMultiCabang(): array
    {
        return [
            'Accounting',
            User::ROLE_SPEEDSHOP_HEAD_AREA,
        ];
    }

    /** Role speedshop dengan dropdown satu cabang (Manager cabang opsional kosong = lintas cabang). */
    public static function speedshopRolesWithSingleCabangSelect(): array
    {
        return array_values(array_unique(array_merge(
            self::speedshopRolesRequiringCabang(),
            ['Manager'],
        )));
    }

    /** @return array<string, list<string>> */
    public static function rolesBySectionForAdmin(): array
    {
        return [
            User::SECTION_OWNER => [User::ROLE_OWNER],
            'speedshop' => self::speedshopRoles(),
            User::SECTION_MODIFIKASI_ORI => self::modifikasiOriRoles(),
        ];
    }

    /** @return list<string> */
    public static function rolesForSection(string $section): array
    {
        return match ($section) {
            User::SECTION_OWNER => [User::ROLE_OWNER],
            'speedshop' => self::speedshopRoles(),
            User::SECTION_MODIFIKASI_ORI => self::modifikasiOriRoles(),
            'produksi' => ['Manager', 'Staf'],
            'warehouse' => ['Manager', 'Staf', 'Accounting', User::ROLE_WAREHOUSE_STAFF_ACCOUNTING_RESELLER, User::ROLE_WAREHOUSE_STAF_PACKING, User::ROLE_WAREHOUSE_STAF_WAREHOUSE],
            'reseller' => [User::ROLE_RESELLER],
            default => [],
        };
    }

    /** @return list<string> */
    public static function filterRoleOptions(?string $section = null): array
    {
        if ($section === null || $section === '') {
            return array_values(array_unique(array_merge(
                [User::ROLE_OWNER],
                self::speedshopRoles(),
                self::modifikasiOriRoles(),
                self::legacyFilterRoles(),
            )));
        }

        return self::filterRoleOptionsForSection($section);
    }

    /** @return list<string> */
    public static function filterRoleOptionsForSection(string $section): array
    {
        $roles = match ($section) {
            User::SECTION_OWNER => [User::ROLE_OWNER],
            'speedshop' => self::speedshopRoles(),
            User::SECTION_MODIFIKASI_ORI => self::modifikasiOriRoles(),
            default => [],
        };

        return array_values(array_unique($roles));
    }

    /** @return list<string> */
    private static function legacyModifikasiOriFilterRoles(): array
    {
        return [
            'Staf',
            'Accounting',
            User::ROLE_WAREHOUSE_STAF_PACKING,
            'Staf Inventori',
            User::ROLE_WAREHOUSE_STAFF_ACCOUNTING_RESELLER,
        ];
    }

    /** Terapkan filter role 77racing (termasuk pemetaan data legacy produksi/warehouse). */
    public static function applyModifikasiOriRoleFilter(\Illuminate\Database\Eloquent\Builder $query, string $role): void
    {
        match ($role) {
            User::ROLE_MODIFIKASI_STAF_PRODUKSI => $query->where(function ($q) {
                $q->where('role', User::ROLE_MODIFIKASI_STAF_PRODUKSI)
                    ->orWhere(function ($q2) {
                        $q2->where('section', 'produksi')->where('role', 'Staf');
                    });
            }),
            User::ROLE_MODIFIKASI_STAF_INVENTORI => $query->whereIn('role', [
                User::ROLE_MODIFIKASI_STAF_INVENTORI,
                User::ROLE_MODIFIKASI_STAF_PACKAGING,
                User::ROLE_WAREHOUSE_STAF_PACKING,
            ]),
            User::ROLE_WAREHOUSE_STAF_WAREHOUSE => $query->where(function ($q) {
                $q->whereIn('role', [User::ROLE_WAREHOUSE_STAF_WAREHOUSE, 'Staf Inventori'])
                    ->orWhere(function ($q2) {
                        $q2->where('section', 'warehouse')->where('role', 'Staf');
                    });
            }),
            User::ROLE_MODIFIKASI_STAF_ACCOUNTING => $query->whereIn('role', [
                User::ROLE_MODIFIKASI_STAF_ACCOUNTING,
                User::ROLE_WAREHOUSE_STAFF_ACCOUNTING_RESELLER,
            ]),
            User::ROLE_MODIFIKASI_HEAD_ACCOUNTING => $query->where(function ($q) {
                $q->whereIn('role', [User::ROLE_MODIFIKASI_HEAD_ACCOUNTING, 'Accounting'])
                    ->orWhere(function ($q2) {
                        $q2->where('section', 'warehouse')->where('role', 'Accounting');
                    });
            }),
            default => $query->where('role', $role),
        };
    }

    /** Terapkan filter role Speedshop (termasuk legacy Outlet untuk Staf). */
    public static function applySpeedshopRoleFilter(\Illuminate\Database\Eloquent\Builder $query, string $role): void
    {
        if ($role === 'Staf') {
            $query->whereIn('role', ['Staf', 'Outlet']);

            return;
        }

        $query->where('role', $role);
    }

    /** @return list<string> */
    private static function legacyFilterRoles(): array
    {
        return array_merge(['Outlet'], self::legacyModifikasiOriFilterRoles());
    }

    public static function sectionLabel(?string $section): string
    {
        return match ($section) {
            User::SECTION_OWNER => 'Owner',
            'speedshop' => 'Speedshop',
            User::SECTION_MODIFIKASI_ORI, 'produksi', 'warehouse' => '77racing',
            'admin' => '77racing',
            'reseller' => 'Reseller',
            default => (string) $section,
        };
    }

    public static function isModifikasiOriSection(?string $section): bool
    {
        return in_array($section, [User::SECTION_MODIFIKASI_ORI, 'produksi', 'warehouse'], true);
    }

    /** Normalisasi section legacy untuk form admin. */
    public static function sectionForAdminForm(?string $section): string
    {
        if (self::isModifikasiOriSection($section) && $section !== User::SECTION_MODIFIKASI_ORI) {
            return User::SECTION_MODIFIKASI_ORI;
        }

        return match ($section) {
            User::SECTION_OWNER, 'speedshop', User::SECTION_MODIFIKASI_ORI => (string) $section,
            default => User::SECTION_MODIFIKASI_ORI,
        };
    }

    /** Normalisasi role legacy untuk form admin. */
    public static function roleForAdminForm(?string $section, ?string $role): string
    {
        $role = (string) $role;

        if ($section === 'speedshop' && $role === 'Outlet') {
            return 'Staf';
        }

        if (! self::isModifikasiOriSection($section)) {
            return $role;
        }

        return match ($role) {
            'Staf' => $section === 'produksi'
                ? User::ROLE_MODIFIKASI_STAF_PRODUKSI
                : User::ROLE_WAREHOUSE_STAF_WAREHOUSE,
            'Accounting' => User::ROLE_MODIFIKASI_HEAD_ACCOUNTING,
            User::ROLE_WAREHOUSE_STAF_PACKING,
            User::ROLE_MODIFIKASI_STAF_PACKAGING => User::ROLE_MODIFIKASI_STAF_INVENTORI,
            User::ROLE_WAREHOUSE_STAFF_ACCOUNTING_RESELLER => User::ROLE_MODIFIKASI_STAF_ACCOUNTING,
            default => $role,
        };
    }

    public static function sectionAssignmentError(array $v): ?string
    {
        return match ($v['section']) {
            'admin' => ($v['warehouse_id'] ?? null) || ($v['speedshop_id'] ?? null)
                ? 'Akun admin tidak boleh memiliki gudang atau cabang.'
                : null,
            'produksi' => ($v['warehouse_id'] ?? null) || ($v['speedshop_id'] ?? null)
                ? 'Akun produksi tidak memakai gudang / cabang.'
                : null,
            'owner' => ($v['warehouse_id'] ?? null) || ($v['speedshop_id'] ?? null)
                ? 'Akun owner tidak terikat gudang atau cabang.'
                : null,
            'warehouse' => null,
            'reseller' => match (true) {
                empty($v['warehouse_reseller_id'] ?? null) => 'Akun reseller wajib terikat data reseller gudang.',
                ! empty($v['speedshop_id'] ?? null) => 'Akun reseller tidak memilih cabang speedshop.',
                default => null,
            },
            'speedshop' => match (true) {
                ($v['role'] ?? '') === 'Manager' => match (true) {
                    ! empty($v['warehouse_id'] ?? null) => 'Role speedshop tidak memilih gudang; pilih cabang saja.',
                    default => null,
                },
                in_array($v['role'] ?? '', self::speedshopRolesWithMultiCabang(), true) => match (true) {
                    empty($v['speedshop_ids'] ?? null) && empty($v['speedshop_id'] ?? null)
                        => ($v['role'] === User::ROLE_SPEEDSHOP_HEAD_AREA
                            ? 'Head Area wajib memilih minimal satu cabang speedshop.'
                            : 'Accounting wajib memilih minimal satu cabang speedshop.'),
                    ! empty($v['warehouse_id'] ?? null) => 'Role speedshop tidak memilih gudang; pilih cabang saja.',
                    default => null,
                },
                in_array($v['role'] ?? '', self::speedshopRolesRequiringCabang(), true) => match (true) {
                    empty($v['speedshop_id'] ?? null) => 'Role ini wajib memilih cabang speedshop.',
                    ! empty($v['warehouse_id'] ?? null) => 'Role speedshop tidak memilih gudang; pilih cabang saja.',
                    default => null,
                },
                default => 'Role speedshop tidak valid.',
            },
            User::SECTION_MODIFIKASI_ORI => match (true) {
                in_array($v['role'] ?? '', self::modifikasiOriWarehouseRoles(), true)
                    && empty($v['warehouse_id'] ?? null) => 'Role gudang/accounting 77racing memerlukan gudang (diisi otomatis jika kosong).',
                ! in_array($v['role'] ?? '', self::modifikasiOriWarehouseRoles(), true)
                    && ! empty($v['speedshop_id'] ?? null) => 'Role 77racing (non-gudang) tidak memilih cabang speedshop.',
                default => null,
            },
            default => 'Section tidak valid.',
        };
    }

    public static function normalize(array $v): array
    {
        switch ($v['section']) {
            case 'admin':
            case 'produksi':
            case 'owner':
                $v['warehouse_id'] = null;
                $v['speedshop_id'] = null;
                break;
            case 'warehouse':
                $v['speedshop_id'] = null;
                $v['warehouse_id'] = null;
                break;
            case 'speedshop':
                $v['warehouse_id'] = null;
                $ids = collect($v['speedshop_ids'] ?? [])
                    ->map(fn ($id) => (int) $id)
                    ->filter(fn (int $id) => $id > 0)
                    ->unique()
                    ->values()
                    ->all();
                if (in_array($v['role'] ?? '', self::speedshopRolesWithMultiCabang(), true)) {
                    if ($ids === [] && ! empty($v['speedshop_id'])) {
                        $ids = [(int) $v['speedshop_id']];
                    }
                    $v['speedshop_ids'] = $ids;
                    $v['speedshop_id'] = count($ids) === 1 ? $ids[0] : null;
                } else {
                    $v['speedshop_ids'] = ! empty($v['speedshop_id']) ? [(int) $v['speedshop_id']] : [];
                }
                break;
            case User::SECTION_MODIFIKASI_ORI:
                $v['speedshop_id'] = null;
                $v['warehouse_id'] = null;
                break;
            case 'reseller':
                $v['speedshop_id'] = null;
                $v['warehouse_id'] = null;
                break;
        }

        if (array_key_exists('attendance_location_id', $v)) {
            $v['attendance_location_id'] = ! empty($v['attendance_location_id'])
                ? (int) $v['attendance_location_id']
                : null;
        }

        return $v;
    }
}
