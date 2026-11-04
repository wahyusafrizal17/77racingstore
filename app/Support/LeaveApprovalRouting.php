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
        // Semua pengajuan izin cukup disetujui satu Manager (tanpa Head & Owner).
        $manager = self::findManagerApproverFor($applicant);
        if (! $manager) {
            return self::errorResult('Manager penyetuju izin belum ditemukan. Hubungi Head HR.');
        }

        return [
            'head' => null,
            'manager' => $manager,
            'head_role_label' => null,
            'manager_role_label' => $manager->isOwner() ? User::ROLE_OWNER : 'Manager',
            'error' => null,
        ];
    }

    /**
     * Prioritas: validator absensi (bila Manager/Owner) → Manager speedshop cabang pemohon
     * → Manager 77racing → Manager mana pun → Owner. Pemohon tidak pernah menyetujui dirinya sendiri.
     */
    private static function findManagerApproverFor(User $applicant): ?User
    {
        $applicantId = (int) $applicant->id;

        if ($applicant->attendance_validator_id) {
            $validator = User::query()->find($applicant->attendance_validator_id);
            if ($validator
                && (int) $validator->id !== $applicantId
                && ((string) $validator->role === 'Manager' || $validator->isOwner())) {
                return $validator;
            }
        }

        $managers = User::query()
            ->where('role', 'Manager')
            ->whereKeyNot($applicantId)
            ->orderBy('id');

        if ($applicant->section === 'speedshop') {
            foreach (self::applicantSpeedshopIds($applicant) as $sid) {
                $local = (clone $managers)->where('section', 'speedshop')->where('speedshop_id', $sid)->first();
                if ($local) {
                    return $local;
                }
            }
        }

        $preferredSection = $applicant->section === 'speedshop' ? 'speedshop' : User::SECTION_MODIFIKASI_ORI;
        $owner = self::findOwner();

        return (clone $managers)->where('section', $preferredSection)->first()
            ?? (clone $managers)->orderByRaw('CASE WHEN section = ? THEN 0 ELSE 1 END', ['speedshop'])->first()
            ?? ($owner && (int) $owner->id !== $applicantId ? $owner : null);
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
