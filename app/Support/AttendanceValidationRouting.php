<?php

namespace App\Support;

use App\Models\User;

class AttendanceValidationRouting
{
    /**
     * Pejabat yang wajib memverifikasi selfie absensi karyawan.
     * Prioritas: penugasan di /admin/attendance-validators (attendance_validator_id).
     */
    public static function validatorFor(User $employee): ?User
    {
        if ($employee->attendance_validator_id) {
            $assigned = User::query()->find($employee->attendance_validator_id);
            if ($assigned && (int) $assigned->id !== (int) $employee->id) {
                return $assigned;
            }
        }

        // Fallback jika belum ditugaskan di Ringkasan Penugasan.
        $resolved = LeaveApprovalRouting::resolveFor($employee);
        if ($resolved['error']) {
            return null;
        }

        if ($employee->section === 'speedshop') {
            if ((string) $employee->role === 'Manager') {
                return $resolved['manager'] ?? $resolved['head'];
            }
            if ($employee->isSpeedshopHeadArea()) {
                return $resolved['head'] ?? $resolved['manager'];
            }

            return $resolved['manager'] ?? $resolved['head'];
        }

        return $resolved['head'] ?? $resolved['manager'];
    }

    public static function isSpeedshopAttendanceReviewer(User $reviewer): bool
    {
        if ($reviewer->section !== 'speedshop') {
            return false;
        }

        return $reviewer->isSpeedshopHeadArea()
            || (string) $reviewer->role === 'Manager';
    }

    /**
     * Karyawan bawahan validasi absensi = sama dengan Ringkasan Penugasan
     * (users.attendance_validator_id = reviewer).
     *
     * @return list<int>
     */
    public static function subordinateIdsFor(User $reviewer): array
    {
        return User::query()
            ->where('attendance_validator_id', $reviewer->id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    public static function isSubordinateOf(User $employee, User $reviewer): bool
    {
        if ((int) $employee->id === (int) $reviewer->id) {
            return false;
        }

        return (int) ($employee->attendance_validator_id ?? 0) === (int) $reviewer->id;
    }
}
