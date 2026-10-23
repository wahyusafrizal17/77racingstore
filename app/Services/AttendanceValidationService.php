<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\User;
use App\Support\AttendanceValidationRouting;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AttendanceValidationService
{
    public function canReview(User $reviewer): bool
    {
        if ($reviewer->isOwner()) {
            return true;
        }

        if ($reviewer->isModifikasiOriHrAdmin()) {
            return true;
        }

        if ($reviewer->isSpeedshopHeadArea()) {
            return true;
        }

        if (str_starts_with((string) $reviewer->role, 'Head ')) {
            return true;
        }

        if ($reviewer->isModifikasiOriManager()) {
            return true;
        }

        if ($reviewer->isSpeedshop() && $reviewer->isManager()) {
            return true;
        }

        return User::query()
            ->where('attendance_validator_id', $reviewer->id)
            ->exists();
    }

    public function canValidate(User $reviewer, AttendanceRecord $record): bool
    {
        if ($record->status !== AttendanceRecord::STATUS_PENDING) {
            return false;
        }

        if ($reviewer->isOwner()) {
            return true;
        }

        if ((int) $record->validator_user_id === (int) $reviewer->id) {
            return true;
        }

        $employee = $record->relationLoaded('user')
            ? $record->user
            : $record->user()->first();

        return $employee instanceof User
            && AttendanceValidationRouting::isSubordinateOf($employee, $reviewer);
    }

    public function pendingCountForReviewer(User $reviewer): int
    {
        return (int) $this->pendingQuery($reviewer, null)->count();
    }

    public function pendingForReviewer(User $reviewer, ?Carbon $date = null, int $perPage = 30): LengthAwarePaginator
    {
        return $this->pendingQuery($reviewer, $date)
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Antrian dikelompokkan per karyawan + tanggal (masuk & pulang dalam satu detail).
     *
     * @return LengthAwarePaginator<int, array{
     *     key: string,
     *     user_id: int,
     *     date: string,
     *     user: ?User,
     *     check_in: ?AttendanceRecord,
     *     check_out: ?AttendanceRecord,
     *     pending_count: int,
     *     latest_at: ?Carbon
     * }>
     */
    public function pendingDayGroupsForReviewer(User $reviewer, int $perPage = 20): LengthAwarePaginator
    {
        $tz = (string) config('app.timezone');
        $pending = $this->pendingQuery($reviewer, null)->get();

        $pairs = $pending
            ->map(fn (AttendanceRecord $record) => [
                'user_id' => (int) $record->user_id,
                'date' => $record->checked_at->timezone($tz)->toDateString(),
            ])
            ->unique(fn (array $row) => $row['user_id'].'|'.$row['date'])
            ->values();

        $dayRecords = collect();
        if ($pairs->isNotEmpty()) {
            $dayRecords = AttendanceRecord::query()
                ->with(['user:id,name,section,role,foto_profil', 'location:id,code,name,locatable_type,locatable_id'])
                ->whereIn('status', [
                    AttendanceRecord::STATUS_PENDING,
                    AttendanceRecord::STATUS_VALID,
                ])
                ->where(function ($query) use ($pairs) {
                    foreach ($pairs as $pair) {
                        $query->orWhere(function ($inner) use ($pair) {
                            $inner->where('user_id', $pair['user_id'])
                                ->whereDate('checked_at', $pair['date']);
                        });
                    }
                })
                ->orderBy('checked_at')
                ->get();
        }

        $groups = $pairs->map(function (array $pair) use ($dayRecords, $tz, $pending) {
            $key = $pair['user_id'].'|'.$pair['date'];
            $forDay = $dayRecords
                ->where('user_id', $pair['user_id'])
                ->filter(fn (AttendanceRecord $record) => $record->checked_at->timezone($tz)->toDateString() === $pair['date'])
                ->values();

            $pendingForDay = $pending->filter(
                fn (AttendanceRecord $record) => (int) $record->user_id === $pair['user_id']
                    && $record->checked_at->timezone($tz)->toDateString() === $pair['date']
            );

            $user = $forDay->first()?->user ?? $pendingForDay->first()?->user;

            return [
                'key' => $key,
                'user_id' => $pair['user_id'],
                'date' => $pair['date'],
                'user' => $user,
                'check_in' => $forDay->firstWhere('type', AttendanceRecord::TYPE_CHECK_IN),
                'check_out' => $forDay->firstWhere('type', AttendanceRecord::TYPE_CHECK_OUT),
                'pending_count' => $pendingForDay->count(),
                'latest_at' => $pendingForDay->max('checked_at'),
            ];
        })
            ->sortByDesc(fn (array $group) => optional($group['latest_at'])->timestamp ?? 0)
            ->values();

        $page = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
        $slice = $groups->forPage($page, $perPage)->values();

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $slice,
            $groups->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );
    }

    /** @return \Illuminate\Database\Eloquent\Builder<AttendanceRecord> */
    private function pendingQuery(User $reviewer, ?Carbon $date)
    {
        $query = AttendanceRecord::query()
            ->with(['user:id,name,section,role,foto_profil,attendance_validator_id', 'location:id,code,name,locatable_type,locatable_id'])
            ->where('status', AttendanceRecord::STATUS_PENDING)
            ->orderByDesc('checked_at');

        if ($date) {
            $query->whereDate('checked_at', $date->toDateString());
        }

        if (! $reviewer->isOwner()) {
            $subordinateIds = AttendanceValidationRouting::subordinateIdsFor($reviewer);

            $query->where(function ($builder) use ($reviewer, $subordinateIds) {
                // Absensi yang ditunjuk ke reviewer saat check-in, atau karyawan bawahan sesuai Ringkasan Penugasan.
                $builder->where('validator_user_id', $reviewer->id);

                if ($subordinateIds !== []) {
                    $builder->orWhereIn('user_id', $subordinateIds);
                }
            });
        }

        return $query;
    }

    public function approve(AttendanceRecord $record, User $reviewer): void
    {
        if (! $this->canValidate($reviewer, $record)) {
            throw new \InvalidArgumentException('Anda tidak berwenang memvalidasi absensi ini.');
        }

        $record->update([
            'status' => AttendanceRecord::STATUS_VALID,
            'validated_by' => $reviewer->id,
            'validated_at' => now(),
            'rejection_reason' => null,
        ]);
    }

    /** @param  list<int|string>  $recordIds */
    public function approveMany(User $reviewer, array $recordIds): int
    {
        $ids = array_values(array_unique(array_map('intval', $recordIds)));
        $records = AttendanceRecord::query()->whereIn('id', $ids)->get();

        if ($records->isEmpty()) {
            throw new \InvalidArgumentException('Tidak ada foto pending untuk disetujui.');
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($reviewer, $records) {
            foreach ($records as $record) {
                $this->approve($record, $reviewer);
            }

            return $records->count();
        });
    }

    public function reject(AttendanceRecord $record, User $reviewer, string $reason): void
    {
        if (! $this->canValidate($reviewer, $record)) {
            throw new \InvalidArgumentException('Anda tidak berwenang memvalidasi absensi ini.');
        }

        $normalized = trim($reason);
        if ($normalized === '') {
            throw new \InvalidArgumentException('Alasan penolakan wajib diisi.');
        }

        $record->update([
            'status' => AttendanceRecord::STATUS_REJECTED,
            'validated_by' => $reviewer->id,
            'validated_at' => now(),
            'rejection_reason' => $normalized,
        ]);
    }

    /** @param  list<int|string>  $recordIds */
    public function rejectMany(User $reviewer, array $recordIds, string $reason): int
    {
        $ids = array_values(array_unique(array_map('intval', $recordIds)));
        $records = AttendanceRecord::query()->whereIn('id', $ids)->get();

        if ($records->isEmpty()) {
            throw new \InvalidArgumentException('Tidak ada foto pending untuk ditolak.');
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($reviewer, $records, $reason) {
            foreach ($records as $record) {
                $this->reject($record, $reviewer, $reason);
            }

            return $records->count();
        });
    }

    public function resolveValidatorForCheckIn(User $employee): User
    {
        $validator = AttendanceValidationRouting::validatorFor($employee);
        if (! $validator) {
            throw new \InvalidArgumentException(
                'Penanggung jawab validasi absensi belum lengkap. Hubungi Head HR.'
            );
        }

        if ((int) $validator->id === (int) $employee->id) {
            throw new \InvalidArgumentException(
                'Penanggung jawab validasi absensi tidak valid untuk akun Anda. Hubungi Head HR.'
            );
        }

        return $validator;
    }
}
