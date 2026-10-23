<?php

namespace App\Support;

use App\Models\User;

class LeaveApprovalRouting
{
    /**
     * @return array{
     *     head: ?User,
     *     manager: ?User,
     *     head_role_label: ?string,
     *     manager_role_label: ?string,
     *     error: ?string
     * }
     */
    public static function resolveFor(User $applicant): array
    {
        $section = AdminUserPayload::sectionForAdminForm($applicant->section);

        if ($section === 'speedshop') {
            return self::resolveSpeedshop($applicant);
        }

        if (AdminUserPayload::isModifikasiOriSection($applicant->section) || $section === User::SECTION_MODIFIKASI_ORI) {
            return self::resolveModifikasiOri($applicant);
        }

        return [
            'head' => null,
            'manager' => null,
            'head_role_label' => null,
            'manager_role_label' => null,
            'error' => 'Divisi akun belum didukung untuk alur izin otomatis.',
        ];
    }

    /** @return array{head: ?User, manager: ?User, head_role_label: ?string, manager_role_label: ?string, error: ?string} */
    private static function resolveSpeedshop(User $applicant): array
    {
        $owner = self::findOwner();

        if (self::isApplicantManager($applicant)) {
            if (! $owner) {
                return self::errorResult('Owner belum ditemukan untuk persetujuan izin Manager.');
            }
            if ((int) $owner->id === (int) $applicant->id) {
                return self::errorResult('Owner tidak dapat mengajukan izin ke diri sendiri.');
            }

            return [
                'head' => null,
                'manager' => $owner,
                'head_role_label' => null,
                'manager_role_label' => User::ROLE_OWNER,
                'error' => null,
            ];
        }

        if (self::isApplicantHead($applicant)) {
            $manager = self::findSpeedshopRegionalManager();
            if (! $manager) {
                return self::errorResult('Manager Speedshop (lintas cabang) belum ditemukan.');
            }
            if (! $owner) {
                return self::errorResult('Owner belum ditemukan untuk persetujuan izin Head.');
            }
            if ((int) $manager->id === (int) $applicant->id) {
                return self::errorResult('Manager penyetuju tidak valid untuk pengajuan Head ini.');
            }

            return [
                'head' => $manager,
                'manager' => $owner,
                'head_role_label' => 'Manager Speedshop',
                'manager_role_label' => User::ROLE_OWNER,
                'error' => null,
            ];
        }

        $head = self::findSpeedshopHeadAreaFor($applicant);
        $manager = self::findSpeedshopManagerFor($applicant);

        if (! $manager) {
            return self::errorResult('Manager Speedshop belum ditemukan. Pastikan ada akun Manager di divisi Speedshop.');
        }

        if (! $head) {
            return self::errorResult('Head Area Speedshop belum ditemukan.');
        }

        if ((int) $head->id === (int) $applicant->id) {
            $head = null;
        }
        if ((int) $manager->id === (int) $applicant->id) {
            return self::errorResult('Manager tidak dapat mengajukan izin ke diri sendiri. Hubungi Head HR.');
        }

        return [
            'head' => $head,
            'manager' => $manager,
            'head_role_label' => User::ROLE_SPEEDSHOP_HEAD_AREA,
            'manager_role_label' => 'Manager Speedshop',
            'error' => null,
        ];
    }

    /** @return array{head: ?User, manager: ?User, head_role_label: ?string, manager_role_label: ?string, error: ?string} */
    private static function resolveModifikasiOri(User $applicant): array
    {
        $owner = self::findOwner();
        $moManager = self::findModifikasiOriManager();

        if (self::isApplicantManager($applicant)) {
            if (! $owner) {
                return self::errorResult('Owner belum ditemukan untuk persetujuan izin Manager.');
            }
            if ((int) $owner->id === (int) $applicant->id) {
                return self::errorResult('Owner tidak dapat mengajukan izin ke diri sendiri.');
            }

            return [
                'head' => null,
                'manager' => $owner,
                'head_role_label' => null,
                'manager_role_label' => User::ROLE_OWNER,
                'error' => null,
            ];
        }

        if (self::isApplicantHead($applicant)) {
            if (! $moManager) {
                return self::errorResult('Manager 77racing belum ditemukan.');
            }
            if (! $owner) {
                return self::errorResult('Owner belum ditemukan untuk persetujuan izin Head.');
            }
            if ((int) $moManager->id === (int) $applicant->id) {
                return self::errorResult('Manager penyetuju tidak valid untuk pengajuan Head ini.');
            }

            return [
                'head' => $moManager,
                'manager' => $owner,
                'head_role_label' => 'Manager 77racing',
                'manager_role_label' => User::ROLE_OWNER,
                'error' => null,
            ];
        }

        $headRole = self::headRoleForModifikasiOriStaf($applicant);
        $head = self::findModifikasiOriByRole($headRole);
        if (! $head) {
            return self::errorResult("Pejabat {$headRole} belum ditemukan untuk divisi 77racing.");
        }
        if ((int) $head->id === (int) $applicant->id) {
            return self::errorResult('Head penyetuju tidak valid untuk akun Anda.');
        }

        // Staf Marketing: cukup verifikasi Head Marketing (tanpa Manager & Owner).
        if (self::isHeadOnlyApprovalStaf($applicant)) {
            return [
                'head' => $head,
                'manager' => null,
                'head_role_label' => $headRole,
                'manager_role_label' => null,
                'error' => null,
            ];
        }

        if (! $moManager) {
            return self::errorResult('Manager 77racing belum ditemukan.');
        }

        if ((int) $moManager->id === (int) $applicant->id) {
            return self::errorResult('Manager tidak dapat mengajukan izin ke diri sendiri. Hubungi Head HR.');
        }

        if ((int) $head->id === (int) $moManager->id) {
            return self::errorResult('Konfigurasi Head dan Manager bentrok. Hubungi Head HR.');
        }

        return [
            'head' => $head,
            'manager' => $moManager,
            'head_role_label' => $headRole,
            'manager_role_label' => 'Manager 77racing',
            'error' => null,
        ];
    }

    public static function isHeadOnlyApprovalStaf(User $applicant): bool
    {
        return (string) $applicant->role === User::ROLE_MODIFIKASI_STAF_MARKETING;
    }

    private static function isApplicantHead(User $applicant): bool
    {
        $role = (string) $applicant->role;

        if ($applicant->section === 'speedshop' && $role === User::ROLE_SPEEDSHOP_HEAD_AREA) {
            return true;
        }

        return str_starts_with($role, 'Head ');
    }

    private static function isApplicantManager(User $applicant): bool
    {
        return (string) $applicant->role === 'Manager';
    }

    private static function headRoleForModifikasiOriStaf(User $applicant): string
    {
        $role = (string) $applicant->role;

        $map = [
            User::ROLE_MODIFIKASI_STAF_ACCOUNTING => User::ROLE_MODIFIKASI_HEAD_ACCOUNTING,
            User::ROLE_MODIFIKASI_STAF_HR => User::ROLE_MODIFIKASI_HEAD_HR,
            User::ROLE_MODIFIKASI_STAF_MARKETING => User::ROLE_MODIFIKASI_HEAD_MARKETING,
            User::ROLE_MODIFIKASI_STAF_PRODUKSI => User::ROLE_MODIFIKASI_HEAD_PRODUKSI,
            User::ROLE_MODIFIKASI_STAF_INVENTORI => User::ROLE_MODIFIKASI_HEAD_WAREHOUSE,
            User::ROLE_MODIFIKASI_STAF_PROCESSING_WAREHOUSE => User::ROLE_MODIFIKASI_HEAD_WAREHOUSE,
            User::ROLE_MODIFIKASI_STAF_PACKAGING => User::ROLE_MODIFIKASI_HEAD_WAREHOUSE,
            User::ROLE_WAREHOUSE_STAF_WAREHOUSE => User::ROLE_MODIFIKASI_HEAD_WAREHOUSE,
            User::ROLE_WAREHOUSE_STAF_PACKING => User::ROLE_MODIFIKASI_HEAD_WAREHOUSE,
            User::ROLE_WAREHOUSE_STAFF_ACCOUNTING_RESELLER => User::ROLE_MODIFIKASI_HEAD_ACCOUNTING,
        ];

        if (isset($map[$role])) {
            return $map[$role];
        }

        if ($applicant->section === 'produksi' && $role === 'Staf') {
            return User::ROLE_MODIFIKASI_HEAD_PRODUKSI;
        }

        if (in_array($applicant->section, ['warehouse', User::SECTION_MODIFIKASI_ORI], true) && $role === 'Staf') {
            return User::ROLE_MODIFIKASI_HEAD_WAREHOUSE;
        }

        if ($role === 'Accounting') {
            return User::ROLE_MODIFIKASI_HEAD_ACCOUNTING;
        }

        return User::ROLE_MODIFIKASI_HEAD_HR;
    }

    private static function findSpeedshopHeadAreaFor(User $applicant): ?User
    {
        $applicantIds = self::applicantSpeedshopIds($applicant);
        $heads = User::query()
            ->where('section', 'speedshop')
            ->where('role', User::ROLE_SPEEDSHOP_HEAD_AREA)
            ->with('assignedSpeedshops:id')
            ->orderBy('id')
            ->get();

        if ($heads->isEmpty()) {
            return null;
        }

        if ($applicantIds !== []) {
            $matched = $heads->first(function (User $head) use ($applicantIds): bool {
                $headIds = $head->assignedSpeedshopIds();

                return $headIds !== [] && array_intersect($applicantIds, $headIds) !== [];
            });
            if ($matched) {
                return $matched;
            }
        }

        return $heads->first(fn (User $head) => $head->assignedSpeedshopIds() === [])
            ?? $heads->first();
    }

    private static function findSpeedshopRegionalManager(): ?User
    {
        return User::query()
            ->where('section', 'speedshop')
            ->where('role', 'Manager')
            ->whereNull('speedshop_id')
            ->orderBy('id')
            ->first()
            ?? User::query()
                ->where('section', 'speedshop')
                ->where('role', 'Manager')
                ->orderBy('id')
                ->first();
    }

    private static function findSpeedshopManagerFor(User $applicant): ?User
    {
        $speedshopIds = self::applicantSpeedshopIds($applicant);

        foreach ($speedshopIds as $sid) {
            $local = User::query()
                ->where('section', 'speedshop')
                ->where('role', 'Manager')
                ->where('speedshop_id', $sid)
                ->orderBy('id')
                ->first();
            if ($local) {
                return $local;
            }
        }

        return self::findSpeedshopRegionalManager();
    }

    /** @return list<int> */
    private static function applicantSpeedshopIds(User $applicant): array
    {
        $ids = $applicant->assignedSpeedshopIds();
        if ($ids !== []) {
            return $ids;
        }
        if ($applicant->speedshop_id) {
            return [(int) $applicant->speedshop_id];
        }

        return [];
    }

    private static function findModifikasiOriManager(): ?User
    {
        return User::query()
            ->where('section', User::SECTION_MODIFIKASI_ORI)
            ->where('role', 'Manager')
            ->orderBy('id')
            ->first();
    }

    private static function findModifikasiOriByRole(string $role): ?User
    {
        return User::query()
            ->whereIn('section', [User::SECTION_MODIFIKASI_ORI, 'produksi', 'warehouse'])
            ->where('role', $role)
            ->orderByRaw('CASE WHEN section = ? THEN 0 ELSE 1 END', [User::SECTION_MODIFIKASI_ORI])
            ->first();
    }

    private static function findOwner(): ?User
    {
        return User::query()
            ->where('section', User::SECTION_OWNER)
            ->where('role', User::ROLE_OWNER)
            ->orderBy('id')
            ->first();
    }

    /** @return array{head: ?User, manager: ?User, head_role_label: ?string, manager_role_label: ?string, error: ?string} */
    private static function errorResult(string $message): array
    {
        return [
            'head' => null,
            'manager' => null,
            'head_role_label' => null,
            'manager_role_label' => null,
            'error' => $message,
        ];
    }

    /** @return list<string> */
    public static function describeFor(User $applicant): array
    {
        return self::describeResolved($applicant, self::resolveFor($applicant));
    }

    /**
     * @param  array{
     *     head: ?User,
     *     manager: ?User,
     *     head_role_label: ?string,
     *     manager_role_label: ?string,
     *     error: ?string
     * }  $resolved
     * @return list<string>
     */
    public static function describeResolved(User $applicant, array $resolved): array
    {
        if ($resolved['error']) {
            return [$resolved['error']];
        }

        $lines = [];
        if ($resolved['head']) {
            $lines[] = 'Langkah 1 — '.$resolved['head']->name.' ('.($resolved['head_role_label'] ?? 'Head').')';
        }
        if ($resolved['manager']) {
            $step = $resolved['head'] ? 'Langkah 2 — ' : 'Langkah 1 — ';
            $lines[] = $step.$resolved['manager']->name.' ('.($resolved['manager_role_label'] ?? 'Manager').')';
        }
        $owner = self::ownerForAcknowledgment($applicant, $resolved['manager'] ?? null, $resolved);
        if ($owner) {
            $lines[] = 'Langkah 3 — '.$owner->name.' ('.User::ROLE_OWNER.' · diketahui, tanpa TTD)';
        }

        return $lines;
    }

    /**
     * @param  array{
     *     head: ?User,
     *     manager: ?User,
     *     head_role_label: ?string,
     *     manager_role_label: ?string,
     *     error: ?string
     * }|null  $resolved
     */
    public static function ownerForAcknowledgment(User $applicant, ?User $manager, ?array $resolved = null): ?User
    {
        if (! $manager || $manager->isOwner()) {
            return null;
        }

        $resolved ??= self::resolveFor($applicant);
        if ($resolved['error'] !== null || ! $resolved['head']) {
            return null;
        }

        return self::findOwner();
    }
}
