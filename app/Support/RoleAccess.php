<?php

namespace App\Support;

use App\Models\User;

/**
 * Pemetaan role baru ↔ legacy untuk middleware & helper otorisasi.
 */
class RoleAccess
{
    /** @return list<string> */
    public static function modifikasiProduksiRoles(): array
    {
        return [
            'Manager',
            User::ROLE_MODIFIKASI_HEAD_PRODUKSI,
            User::ROLE_MODIFIKASI_STAF_PRODUKSI,
            User::ROLE_MODIFIKASI_HEAD_MARKETING,
            User::ROLE_MODIFIKASI_STAF_MARKETING,
            User::ROLE_MODIFIKASI_STAF_HR,
        ];
    }

    /** @return list<string> */
    public static function modifikasiWarehouseRoles(): array
    {
        return [
            'Manager',
            User::ROLE_MODIFIKASI_HEAD_WAREHOUSE,
            User::ROLE_MODIFIKASI_STAF_PROCESSING_WAREHOUSE,
            User::ROLE_WAREHOUSE_STAF_WAREHOUSE,
            User::ROLE_MODIFIKASI_STAF_INVENTORI,
            User::ROLE_MODIFIKASI_STAF_PACKAGING,
            User::ROLE_MODIFIKASI_HEAD_ACCOUNTING,
            User::ROLE_MODIFIKASI_STAF_ACCOUNTING,
            User::ROLE_MODIFIKASI_HEAD_AUDIT,
            User::ROLE_WAREHOUSE_STAFF_ACCOUNTING_RESELLER,
        ];
    }

    /** @return list<string> */
    public static function modifikasiManagerRoles(): array
    {
        return [
            'Manager',
            User::ROLE_MODIFIKASI_HEAD_PRODUKSI,
            User::ROLE_MODIFIKASI_HEAD_WAREHOUSE,
            User::ROLE_MODIFIKASI_HEAD_ACCOUNTING,
            User::ROLE_MODIFIKASI_HEAD_MARKETING,
        ];
    }

    public static function canAccessOperationalSection(User $user, string $section): bool
    {
        if ($user->section === $section) {
            return true;
        }

        if ($user->section !== User::SECTION_MODIFIKASI_ORI) {
            return false;
        }

        return match ($section) {
            'produksi' => in_array($user->role, self::modifikasiProduksiRoles(), true),
            'warehouse' => in_array($user->role, self::modifikasiWarehouseRoles(), true),
            default => false,
        };
    }

    /** @return list<string> */
    public static function routeRoleAliases(User $user): array
    {
        $aliases = [$user->role];

        if ($user->section === 'speedshop') {
            return array_values(array_unique(array_merge($aliases, match ($user->role) {
                User::ROLE_SPEEDSHOP_HEAD_AREA => ['Manager'],
                'Staf', 'Outlet' => ['Staf', 'Outlet'],
                User::ROLE_SPEEDSHOP_MEKANIK, User::ROLE_SPEEDSHOP_FLP => ['Outlet', 'Staf'],
                default => [],
            })));
        }

        if (self::isModifikasiOriStaff($user) || $user->section === 'produksi' || $user->section === 'warehouse') {
            $aliases = array_merge($aliases, match ($user->role) {
                'Manager',
                User::ROLE_MODIFIKASI_HEAD_PRODUKSI,
                User::ROLE_MODIFIKASI_HEAD_WAREHOUSE,
                User::ROLE_MODIFIKASI_HEAD_MARKETING => ['Manager'],
                User::ROLE_MODIFIKASI_HEAD_ACCOUNTING => ['Manager', 'Accounting'],
                User::ROLE_MODIFIKASI_HEAD_AUDIT => ['Manager', 'Accounting'],
                User::ROLE_MODIFIKASI_STAF_PRODUKSI,
                User::ROLE_MODIFIKASI_STAF_MARKETING,
                User::ROLE_MODIFIKASI_STAF_HR,
                'Staf' => ['Staf'],
                User::ROLE_MODIFIKASI_STAF_PACKAGING => [
                    User::ROLE_WAREHOUSE_STAF_PACKING,
                    'Staf',
                ],
                User::ROLE_MODIFIKASI_STAF_PROCESSING_WAREHOUSE,
                User::ROLE_WAREHOUSE_STAF_WAREHOUSE,
                User::ROLE_MODIFIKASI_STAF_INVENTORI => [
                    User::ROLE_MODIFIKASI_STAF_PROCESSING_WAREHOUSE,
                    User::ROLE_WAREHOUSE_STAF_WAREHOUSE,
                    'Staf Inventori',
                    'Staf',
                ],
                'Accounting' => ['Accounting'],
                User::ROLE_MODIFIKASI_STAF_ACCOUNTING,
                User::ROLE_WAREHOUSE_STAFF_ACCOUNTING_RESELLER => [
                    User::ROLE_WAREHOUSE_STAFF_ACCOUNTING_RESELLER,
                    'Accounting',
                ],
                User::ROLE_WAREHOUSE_STAF_PACKING => [User::ROLE_WAREHOUSE_STAF_PACKING],
                'Outlet' => ['Outlet', 'Staf'],
                default => [],
            });
        }

        return array_values(array_unique($aliases));
    }

    private static function isModifikasiOriStaff(User $user): bool
    {
        return $user->section === User::SECTION_MODIFIKASI_ORI;
    }
}
