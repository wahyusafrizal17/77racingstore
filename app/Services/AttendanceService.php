<?php

namespace App\Services;

use App\Models\AttendanceLocation;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSetting;
use App\Models\LeaveRequest;
use App\Models\Speedshop;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\AttendanceScheduleService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use League\Flysystem\UnableToWriteFile;

class AttendanceService
{
    public const CHECK_IN_WINDOW_START = '07:00';

    public const CHECK_IN_WINDOW_END = '09:00';

    public const CHECK_OUT_WINDOW_START = '17:00';

    public const CHECK_OUT_WINDOW_END = '23:59';

    public function __construct(
        private readonly AttendanceScheduleService $schedule,
        private readonly AttendanceValidationService $validation,
    ) {}

    /**
     * @return Collection<int, AttendanceLocation>
     */
    public function locationsForUser(User $user): Collection
    {
        if ($user->attendance_location_id) {
            $assigned = AttendanceLocation::query()
                ->active()
                ->whereKey($user->attendance_location_id)
                ->get();
            if ($assigned->isNotEmpty()) {
                return $assigned;
            }
        }

        $all = AttendanceLocation::query()
            ->active()
            ->orderBy('id')
            ->get();

        if (
            $user->canAccessAdminPanel()
            || $user->isModifikasiOriManager()
            || $user->isModifikasiOriHeadWarehouse()
            || $user->isOwner()
            || ($user->isSpeedshop() && $user->isManager() && ! $user->speedshop_id)
        ) {
            return $all;
        }

        if ($user->speedshop_id || ($user->isSpeedshopAccounting() && $user->assignedSpeedshopIds() !== [])) {
            $allowedIds = $user->activeSpeedshopIds();
            $filtered = $all->filter(
                fn (AttendanceLocation $loc) => $loc->locatable_type === Speedshop::class
                    && in_array((int) $loc->locatable_id, $allowedIds, true)
            );
            if ($filtered->isNotEmpty()) {
                return $filtered->values();
            }
        }

        if ($user->isModifikasiOriHeadAudit() && $user->isHeadAuditSpeedshopContext()) {
            $allowedIds = $user->activeSpeedshopIds();
            $filtered = $all->filter(
                fn (AttendanceLocation $loc) => $loc->locatable_type === Speedshop::class
                    && in_array((int) $loc->locatable_id, $allowedIds, true)
            );
            if ($filtered->isNotEmpty()) {
                return $filtered->values();
            }
        }

        if (
            $user->isWarehouse()
            || $user->warehouse_id
            || $user->isProduksi()
            || $user->usesModifikasiOriWelcomePortal()
        ) {
            $filtered = $all->filter(
                fn (AttendanceLocation $loc) => $loc->locatable_type === Warehouse::class
                    || $loc->code === AttendanceLocation::CODE_WAREHOUSE
            );
            if ($filtered->isNotEmpty()) {
                return $filtered->values();
            }
        }

        return $all;
    }

    public function todayCheckIn(User $user): ?AttendanceRecord
    {
        return $this->checkInOnDate($user, AttendanceSetting::now());
    }

    public function todayCheckOut(User $user): ?AttendanceRecord
    {
        return $this->checkOutOnDate($user, AttendanceSetting::now());
    }

    public function checkInOnDate(User $user, Carbon $date): ?AttendanceRecord
    {
        $date = AttendanceSetting::at($date);

        return AttendanceRecord::query()
            ->where('user_id', $user->id)
            ->where('type', AttendanceRecord::TYPE_CHECK_IN)
            ->whereIn('status', AttendanceRecord::activeStatuses())
            ->whereDate('checked_at', $date->toDateString())
            ->with('location')
            ->orderByDesc('checked_at')
            ->first();
    }

    public function checkOutOnDate(User $user, Carbon $date): ?AttendanceRecord
    {
        $date = AttendanceSetting::at($date);

        return AttendanceRecord::query()
            ->where('user_id', $user->id)
            ->where('type', AttendanceRecord::TYPE_CHECK_OUT)
            ->whereIn('status', AttendanceRecord::activeStatuses())
            ->whereDate('checked_at', $date->toDateString())
            ->with('location')
            ->orderByDesc('checked_at')
            ->first();
    }

    public function hasFlexibleAttendanceSchedule(?User $user = null): bool
    {
        return (bool) $user?->hasFlexibleAttendanceSchedule();
    }

    /**
     * Boleh absen di luar radius kantor: jadwal fleksibel Marketing, atau hari WFH disetujui.
     * WFH tetap wajib absen (jam masuk/pulang mengikuti aturan biasa kecuali Marketing).
     */
    public function mayBypassAttendanceGeofence(?User $user = null, ?Carbon $at = null): bool
    {
        if ($this->hasFlexibleAttendanceSchedule($user)) {
            return true;
        }

        if (! $user) {
            return false;
        }

        return $this->schedule->hasApprovedWfh(AttendanceSetting::at($at), (int) $user->id);
    }

    public function checkInWindowLabel(?User $user = null, ?AttendanceLocation $location = null): string
    {
        if ($this->hasFlexibleAttendanceSchedule($user)) {
            return 'Bebas (kapan saja)';
        }

        $at = AttendanceSetting::now();
        if ($this->approvedHalfDayType($user, $at) === LeaveRequest::TYPE_SETENGAH_MASUK) {
            return LeaveRequest::HALF_DAY_CLOCK.'–'.$this->checkInDeadlineAt($at, $user, $location)->format('H:i');
        }

        return $this->checkInWindowStartAt($at, $user, $location)->format('H:i')
            .'–'.$this->checkInDeadlineAt($at, $user, $location)->format('H:i');
    }

    public function checkOutWindowLabel(?User $user = null): string
    {
        if ($this->hasFlexibleAttendanceSchedule($user)) {
            return 'Bebas (setelah absen masuk)';
        }

        $at = AttendanceSetting::now();
        $start = $this->checkOutWindowStartLabel($user, $at);
        if ($this->approvedHalfDayType($user, $at) === LeaveRequest::TYPE_SETENGAH_PULANG) {
            $start = LeaveRequest::HALF_DAY_CLOCK;
        }

        return $start.'–'.$this->checkOutWindowEndLabel($user, $at);
    }

    public function isWithinCheckInWindow(
        ?Carbon $at = null,
        ?User $user = null,
        ?AttendanceLocation $location = null,
    ): bool {
        if ($this->hasFlexibleAttendanceSchedule($user)) {
            return true;
        }

        $at = AttendanceSetting::at($at);

        return $at->gte($this->checkInWindowStartAt($at, $user, $location))
            && $at->lte($this->checkInDeadlineAt($at, $user, $location));
    }

    public function isPastCheckInDeadline(
        ?Carbon $at = null,
        ?User $user = null,
        ?AttendanceLocation $location = null,
    ): bool {
        if ($this->hasFlexibleAttendanceSchedule($user)) {
            return false;
        }

        $at = AttendanceSetting::at($at);

        return $at->gt($this->checkInDeadlineAt($at, $user, $location));
    }

    public function isWithinCheckOutWindow(?Carbon $at = null, ?User $user = null): bool
    {
        if ($this->hasFlexibleAttendanceSchedule($user)) {
            return true;
        }

        $at = AttendanceSetting::at($at);
        $start = $this->checkOutWindowStartLabel($user, $at);
        if ($this->approvedHalfDayType($user, $at) === LeaveRequest::TYPE_SETENGAH_PULANG) {
            $start = LeaveRequest::HALF_DAY_CLOCK;
        }

        return $this->minutesOfDay($at) >= $this->windowStartMinutes($start)
            && $this->minutesOfDay($at) <= $this->windowEndMinutes($this->checkOutWindowEndLabel($user, $at));
    }

    private function checkOutWindowStartLabel(?User $user, Carbon $at): string
    {
        $settings = $this->schedule->settingsForUser($user, $at);
        $time = (string) ($settings->check_out_window_start ?? $settings->check_out_time ?? self::CHECK_OUT_WINDOW_START);

        return AttendanceSetting::clockHi($time, self::CHECK_OUT_WINDOW_START);
    }

    private function checkOutWindowEndLabel(?User $user, Carbon $at): string
    {
        $settings = $this->schedule->settingsForUser($user, $at);
        $time = (string) ($settings->check_out_window_end ?? self::CHECK_OUT_WINDOW_END);

        return AttendanceSetting::clockHi($time, self::CHECK_OUT_WINDOW_END);
    }

    public function assertWithinAttendanceWindow(
        string $type,
        ?Carbon $at = null,
        ?User $user = null,
        ?AttendanceLocation $location = null,
    ): void {
        if ($this->hasFlexibleAttendanceSchedule($user)) {
            return;
        }

        $at = AttendanceSetting::at($at);

        if ($type === AttendanceRecord::TYPE_CHECK_IN && ! $this->isWithinCheckInWindow($at, $user, $location)) {
            if ($this->isPastCheckInDeadline($at, $user, $location)) {
                $deadline = $this->checkInDeadlineAt($at, $user, $location)->format('H:i');
                throw new \InvalidArgumentException(
                    'Batas absen masuk pukul '.$deadline.'. Anda dihitung tidak hadir.'
                );
            }

            throw new \InvalidArgumentException(
                'Absen masuk hanya dapat dilakukan pukul '.$this->checkInWindowLabel($user, $location).'.'
            );
        }

        if ($type === AttendanceRecord::TYPE_CHECK_OUT && ! $this->isWithinCheckOutWindow($at, $user)) {
            throw new \InvalidArgumentException(
                'Absen pulang hanya dapat dilakukan pukul '.$this->checkOutWindowLabel($user).'.'
            );
        }
    }

    private function approvedHalfDayType(?User $user, ?Carbon $at = null): ?string
    {
        if (! $user) {
            return null;
        }

        return $this->schedule->approvedHalfDayLeaveType(AttendanceSetting::at($at), $user->id);
    }

    private function checkInDeadlineAt(Carbon $at, ?User $user, ?AttendanceLocation $location): Carbon
    {
        if ($this->approvedHalfDayType($user, $at) === LeaveRequest::TYPE_SETENGAH_MASUK) {
            return $this->halfDayClockAt($at)->copy()->addHour();
        }

        $settings = $location
            ? $this->schedule->settingsForLocation($location, $at)
            : $this->schedule->settingsForUser($user, $at);
        $time = (string) ($settings->check_in_window_end ?? AttendanceSetting::DEFAULT_CHECK_IN_WINDOW_END);

        return AttendanceSetting::applyClock($at, $time, AttendanceSetting::DEFAULT_CHECK_IN_WINDOW_END);
    }

    private function halfDayClockAt(Carbon $date): Carbon
    {
        return AttendanceSetting::applyClock($date, LeaveRequest::HALF_DAY_CLOCK, LeaveRequest::HALF_DAY_CLOCK);
    }

    private function minutesOfDay(?Carbon $at): int
    {
        $at = AttendanceSetting::at($at);

        return ($at->hour * 60) + $at->minute;
    }

    private function windowStartMinutes(string $hi): int
    {
        [$h, $m] = AttendanceSetting::parseClock($hi, '00:00');

        return ($h * 60) + $m;
    }

    private function windowEndMinutes(string $hi): int
    {
        [$h, $m] = AttendanceSetting::parseClock($hi, '00:00');

        return ($h * 60) + $m;
    }

    private function checkInWindowStartAt(Carbon $date, ?User $user = null, ?AttendanceLocation $location = null): Carbon
    {
        if ($this->approvedHalfDayType($user, $date) === LeaveRequest::TYPE_SETENGAH_MASUK) {
            return $this->halfDayClockAt($date);
        }

        $settings = $location
            ? $this->schedule->settingsForLocation($location, $date)
            : $this->schedule->settingsForUser($user, $date);
        $time = (string) ($settings->check_in_window_start ?? self::CHECK_IN_WINDOW_START);

        return AttendanceSetting::applyClock($date, $time, self::CHECK_IN_WINDOW_START);
    }

    /**
     * @return array{record: AttendanceRecord, distance: float, is_late: bool}
     */
    public function checkIn(
        User $user,
        AttendanceLocation $location,
        UploadedFile $selfie,
        float $latitude,
        float $longitude,
        ?float $accuracy,
        string $type,
        ?string $ip = null,
        ?string $lateReason = null,
    ): array {
        if (! $location->hasCoordinates()) {
            throw new \InvalidArgumentException('Lokasi absensi belum memiliki koordinat. Hubungi Head HR.');
        }

        if (! $this->schedule->requiresAttendanceToday(null, $user->id)) {
            throw new \InvalidArgumentException('Hari ini tidak memerlukan absensi (libur, perjalanan dinas, atau bukan hari kerja).');
        }

        if (! in_array($type, [AttendanceRecord::TYPE_CHECK_IN, AttendanceRecord::TYPE_CHECK_OUT], true)) {
            throw new \InvalidArgumentException('Tipe absensi tidak valid.');
        }

        $checkIn = $this->todayCheckIn($user);
        $checkOut = $this->todayCheckOut($user);
        $now = AttendanceSetting::now();
        $isLate = false;
        $normalizedReason = null;

        if ($type === AttendanceRecord::TYPE_CHECK_IN && $checkIn) {
            throw new \InvalidArgumentException('Anda sudah absen masuk hari ini.');
        }
        if ($type === AttendanceRecord::TYPE_CHECK_OUT) {
            if (! $checkIn) {
                throw new \InvalidArgumentException('Absen masuk terlebih dahulu sebelum absen pulang.');
            }
            if ($checkOut) {
                throw new \InvalidArgumentException('Anda sudah absen pulang hari ini.');
            }
        }

        $this->assertWithinAttendanceWindow($type, $now, $user, $location);

        if ($type === AttendanceRecord::TYPE_CHECK_IN && ! $this->hasFlexibleAttendanceSchedule($user)) {
            $isLate = $this->schedule->isLateAt($now, null, $user, $location)
                && $this->approvedHalfDayType($user, $now) !== LeaveRequest::TYPE_SETENGAH_MASUK;
            if ($isLate) {
                $normalizedReason = trim((string) ($lateReason ?? '')) ?: null;
            }
        }

        $distance = AttendanceLocation::distanceMeters(
            (float) $location->latitude,
            (float) $location->longitude,
            $latitude,
            $longitude,
        );

        $radius = max(1, (int) $location->radius_meters);
        // Marketing (fleksibel) atau hari WFH disetujui: boleh absen di luar kantor; jarak tetap dicatat.
        if (! $this->mayBypassAttendanceGeofence($user, $now) && $distance > $radius) {
            throw new \InvalidArgumentException(
                'Anda di luar jangkauan lokasi. Jarak saat ini '
                .number_format($distance, 0, ',', '.').' m (maks. '
                .number_format($radius, 0, ',', '.').' m).'
            );
        }

        $path = $this->storeSelfie($user, $selfie);
        $validator = $this->validation->resolveValidatorForCheckIn($user);

        $record = AttendanceRecord::query()->create([
            'user_id' => $user->id,
            'validator_user_id' => $validator->id,
            'attendance_location_id' => $location->id,
            'type' => $type,
            'checked_at' => $now,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy_meters' => $accuracy,
            'distance_meters' => round($distance, 2),
            'selfie_path' => $path,
            'status' => AttendanceRecord::STATUS_PENDING,
            'is_late' => $isLate,
            'late_reason' => $isLate ? $normalizedReason : null,
            'ip_address' => $ip,
        ]);

        return ['record' => $record, 'distance' => $distance, 'is_late' => $isLate];
    }

    private function storeSelfie(User $user, UploadedFile $selfie): string
    {
        $dir = 'absensi/selfies/'.$user->id;
        $extension = strtolower((string) ($selfie->getClientOriginalExtension() ?: $selfie->extension() ?: 'jpg'));
        $name = AttendanceSetting::now()->format('Ymd_His').'_'.Str::random(8).'.'.$extension;

        try {
            return \App\Support\UploadStorage::storeAs($selfie, $dir, $name);
        } catch (UnableToWriteFile) {
            throw new \InvalidArgumentException(
                'Gagal menyimpan foto absensi. Coba lagi beberapa saat, atau hubungi Head jika berulang.'
            );
        }
    }

    public function layoutForUser(User $user): string
    {
        if ($user->canAccessAdminPanel()) {
            return 'layouts.admin';
        }

        // layouts.modifikasi-ori / warehouse / produksi sudah dihapus — semua portal pakai speedshop.
        return 'layouts.speedshop';
    }
}
