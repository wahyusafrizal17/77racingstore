<?php

namespace App\Services;

use App\Models\AttendanceHoliday;
use App\Models\AttendanceLocation;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSetting;
use App\Models\AttendanceWorkDayOverride;
use App\Models\LeaveRequest;
use App\Models\Speedshop;
use App\Models\User;
use App\Support\AdminUserPayload;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class AttendanceScheduleService
{
    public function settings(): AttendanceSetting
    {
        if (! Schema::hasTable('attendance_settings')) {
            return new AttendanceSetting([
                'check_in_time' => AttendanceSetting::DEFAULT_CHECK_IN,
                'check_out_time' => AttendanceSetting::DEFAULT_CHECK_OUT,
                'check_in_window_start' => AttendanceSetting::DEFAULT_CHECK_IN_WINDOW_START,
                'check_in_window_end' => AttendanceSetting::DEFAULT_CHECK_IN_WINDOW_END,
                'check_out_window_start' => AttendanceSetting::DEFAULT_CHECK_OUT_WINDOW_START,
                'check_out_window_end' => AttendanceSetting::DEFAULT_CHECK_OUT_WINDOW_END,
                'work_days' => AttendanceSetting::DEFAULT_WORK_DAYS,
            ]);
        }

        $row = AttendanceSetting::query()->first();
        if ($row) {
            return $row;
        }

        return AttendanceSetting::query()->create([
            'check_in_time' => AttendanceSetting::DEFAULT_CHECK_IN,
            'check_out_time' => AttendanceSetting::DEFAULT_CHECK_OUT,
            'work_days' => AttendanceSetting::DEFAULT_WORK_DAYS,
        ]);
    }

    public function updateSettings(array $data): AttendanceSetting
    {
        $settings = $this->settings();
        $settings->update($data);

        return $settings->fresh();
    }

    public function resolveLocationForUser(User $user): ?AttendanceLocation
    {
        if (! Schema::hasTable('attendance_locations')) {
            return null;
        }

        if ($user->attendance_location_id) {
            $assigned = AttendanceLocation::query()->active()->find($user->attendance_location_id);
            if ($assigned) {
                return $assigned;
            }
        }

        $speedshopId = $user->activeSpeedshopId();
        if ($speedshopId) {
            $byOutlet = AttendanceLocation::query()
                ->active()
                ->where('locatable_type', Speedshop::class)
                ->where('locatable_id', $speedshopId)
                ->orderBy('id')
                ->first();
            if ($byOutlet) {
                return $byOutlet;
            }
        }

        if (
            $user->isWarehouse()
            || $user->warehouse_id
            || $user->isProduksi()
            || $user->usesModifikasiOriWelcomePortal()
            || AdminUserPayload::isModifikasiOriSection($user->section)
        ) {
            return AttendanceLocation::query()
                ->active()
                ->where('code', AttendanceLocation::CODE_WAREHOUSE)
                ->orderBy('id')
                ->first();
        }

        return null;
    }

    public function settingsForLocation(?AttendanceLocation $location, ?Carbon $at = null): AttendanceSetting
    {
        $global = $this->settings();

        if (! $location) {
            return $this->withAttendanceWindows($global);
        }

        $at = AttendanceSetting::at($at);
        $checkIn = $location->check_in_time ?? $global->check_in_time;
        $checkOut = $location->check_out_time ?? $global->check_out_time;
        $checkInWindowStart = $this->filledClock($global->check_in_window_start) ?? AttendanceSetting::DEFAULT_CHECK_IN_WINDOW_START;
        $checkInWindowEnd = $this->filledClock($global->check_in_window_end) ?? AttendanceSetting::DEFAULT_CHECK_IN_WINDOW_END;
        $checkOutWindowStart = $this->filledClock($global->check_out_window_start) ?? $checkOut ?? AttendanceSetting::DEFAULT_CHECK_OUT_WINDOW_START;
        $checkOutWindowEnd = $this->filledClock($global->check_out_window_end) ?? AttendanceSetting::DEFAULT_CHECK_OUT_WINDOW_END;

        $day = (int) $at->dayOfWeekIso;
        $weekday = $location->relationLoaded('weekdayHours')
            ? $location->weekdayHours->firstWhere('day_of_week', $day)
            : $location->weekdayHours()->where('day_of_week', $day)->first();

        if ($weekday) {
            $checkIn = $weekday->check_in_time ?? $checkIn;
            $checkOut = $weekday->check_out_time ?? $checkOut;
            $checkInWindowStart = $this->filledClock($weekday->check_in_window_start) ?? $checkInWindowStart;
            $checkInWindowEnd = $this->filledClock($weekday->check_in_window_end) ?? $checkInWindowEnd;
            $checkOutWindowStart = $this->filledClock($weekday->check_out_window_start) ?? $checkOut ?? $checkOutWindowStart;
            $checkOutWindowEnd = $this->filledClock($weekday->check_out_window_end) ?? $checkOutWindowEnd;
        }

        return new AttendanceSetting([
            'check_in_time' => $checkIn,
            'check_out_time' => $checkOut,
            'check_in_window_start' => $checkInWindowStart,
            'check_in_window_end' => $checkInWindowEnd,
            'check_out_window_start' => $checkOutWindowStart,
            'check_out_window_end' => $checkOutWindowEnd,
            'work_days' => $global->work_days,
        ]);
    }

    public function settingsForUser(?User $user, ?Carbon $at = null): AttendanceSetting
    {
        if (! $user) {
            return $this->withAttendanceWindows($this->settings());
        }

        return $this->settingsForLocation($this->resolveLocationForUser($user), $at);
    }

    private function withAttendanceWindows(AttendanceSetting $settings): AttendanceSetting
    {
        return new AttendanceSetting([
            'check_in_time' => $settings->check_in_time ?? AttendanceSetting::DEFAULT_CHECK_IN,
            'check_out_time' => $settings->check_out_time ?? AttendanceSetting::DEFAULT_CHECK_OUT,
            'check_in_window_start' => $this->filledClock($settings->check_in_window_start) ?? AttendanceSetting::DEFAULT_CHECK_IN_WINDOW_START,
            'check_in_window_end' => $this->filledClock($settings->check_in_window_end) ?? AttendanceSetting::DEFAULT_CHECK_IN_WINDOW_END,
            'check_out_window_start' => $this->filledClock($settings->check_out_window_start) ?? AttendanceSetting::DEFAULT_CHECK_OUT_WINDOW_START,
            'check_out_window_end' => $this->filledClock($settings->check_out_window_end) ?? AttendanceSetting::DEFAULT_CHECK_OUT_WINDOW_END,
            'work_days' => $settings->work_days,
        ]);
    }

    private function filledClock(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    /**
     * Jam kerja Senin–Minggu untuk satu lokasi (fallback ke jam default lokasi/global).
     *
     * @return list<array{
     *     day_of_week: int,
     *     label: string,
     *     check_in: string,
     *     check_out: string,
     *     check_in_window_start: string,
     *     check_in_window_end: string,
     *     check_out_window_start: string,
     *     check_out_window_end: string
     * }>
     */
    public function weekdayHoursPayload(?AttendanceLocation $location): array
    {
        $global = $this->settings();
        $fallbackIn = \Illuminate\Support\Str::of((string) ($location?->check_in_time ?? $global->check_in_time ?? AttendanceSetting::DEFAULT_CHECK_IN))->substr(0, 5);
        $fallbackOut = \Illuminate\Support\Str::of((string) ($location?->check_out_time ?? $global->check_out_time ?? AttendanceSetting::DEFAULT_CHECK_OUT))->substr(0, 5);

        $existing = collect();
        if ($location) {
            $existing = ($location->relationLoaded('weekdayHours')
                ? $location->weekdayHours
                : $location->weekdayHours()->get()
            )->keyBy('day_of_week');
        }

        $rows = [];
        foreach (\App\Models\AttendanceLocationWeekdayHour::DAY_LABELS as $day => $label) {
            $row = $existing->get($day);
            $checkIn = $row ? $row->checkInHi() : (string) $fallbackIn;
            $checkOut = $row ? $row->checkOutHi() : (string) $fallbackOut;
            $rows[] = [
                'day_of_week' => $day,
                'label' => $label,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'check_in_window_start' => $row ? $row->checkInWindowStartHi() : '07:00',
                'check_in_window_end' => $row ? $row->checkInWindowEndHi() : '09:00',
                'check_out_window_start' => $row ? $row->checkOutWindowStartHi() : $checkOut,
                'check_out_window_end' => $row ? $row->checkOutWindowEndHi() : '23:59',
            ];
        }

        return $rows;
    }

    /**
     * @param  array<int, array{
     *     check_in_time: string,
     *     check_out_time: string,
     *     check_in_window_start: string,
     *     check_in_window_end: string,
     *     check_out_window_start: string,
     *     check_out_window_end: string
     * }>  $days keyed by day_of_week
     */
    public function syncLocationWeekdayHours(AttendanceLocation $location, array $days): void
    {
        foreach ($days as $day => $times) {
            $day = (int) $day;
            if ($day < 1 || $day > 7) {
                continue;
            }

            $normalize = static function (string $value): string {
                return $value.(strlen($value) === 5 ? ':00' : '');
            };

            \App\Models\AttendanceLocationWeekdayHour::query()->updateOrCreate(
                [
                    'attendance_location_id' => $location->id,
                    'day_of_week' => $day,
                ],
                [
                    'check_in_time' => $normalize($times['check_in_time']),
                    'check_out_time' => $normalize($times['check_out_time']),
                    'check_in_window_start' => $normalize($times['check_in_window_start']),
                    'check_in_window_end' => $normalize($times['check_in_window_end']),
                    'check_out_window_start' => $normalize($times['check_out_window_start']),
                    'check_out_window_end' => $normalize($times['check_out_window_end']),
                ],
            );
        }

        // Simpan juga jam Senin sebagai default ringkas di lokasi
        $monday = $days[1] ?? reset($days) ?: null;
        if (is_array($monday)) {
            $location->update([
                'check_in_time' => $monday['check_in_time'].(strlen($monday['check_in_time']) === 5 ? ':00' : ''),
                'check_out_time' => $monday['check_out_time'].(strlen($monday['check_out_time']) === 5 ? ':00' : ''),
                'break_start_time' => null,
                'break_end_time' => null,
            ]);
        }
    }

    /**
     * @return array{start: string, end: string}|null H:i:s
     */
    public function breakWindowForLocation(?AttendanceLocation $location): ?array
    {
        if (! $location?->break_start_time || ! $location?->break_end_time) {
            return null;
        }

        return [
            'start' => (string) $location->break_start_time,
            'end' => (string) $location->break_end_time,
        ];
    }

    /** @return array{start: string, end: string}|null */
    public function breakWindowForUser(?User $user): ?array
    {
        return $this->breakWindowForLocation($this->resolveLocationForUser($user));
    }

    /** @return Collection<string, string> date => label */
    public function workDayOverrideMapBetween(Carbon $from, Carbon $to, ?int $userId = null): Collection
    {
        if (! Schema::hasTable('attendance_work_day_overrides') || ! $userId) {
            return collect();
        }

        return AttendanceWorkDayOverride::query()
            ->where('user_id', $userId)
            ->whereBetween('work_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('work_date')
            ->get()
            ->mapWithKeys(fn (AttendanceWorkDayOverride $row) => [
                $row->work_date->toDateString() => 'Masuk hari libur',
            ]);
    }

    public function hasWorkDayOverride(Carbon $date, ?int $userId = null): bool
    {
        if (! $userId || ! Schema::hasTable('attendance_work_day_overrides')) {
            return false;
        }

        return AttendanceWorkDayOverride::query()
            ->where('user_id', $userId)
            ->whereDate('work_date', $date->toDateString())
            ->exists();
    }

    /** Hari libur terjadwal (libur HR / hari off mingguan), bukan dinas. */
    public function isEmployeeScheduledOffDay(Carbon $date, User $user): bool
    {
        if ($this->hasWorkDayOverride($date, $user->id)) {
            return false;
        }

        $settings = $this->settingsForUser($user);
        $holidayMap = $this->holidayMapBetween($date->copy(), $date->copy(), $user->id);
        $dinasMap = $this->dinasMapBetween($date->copy(), $date->copy(), $user->id);

        if ($this->isOnDinas($date, $dinasMap)) {
            return false;
        }

        return $this->isHoliday($date, $holidayMap)
            || ! $this->isConfiguredWorkDay($date, $settings, $user);
    }

    /**
     * @return list<array{date: string, label: string}>
     */
    public function scheduledOffDatesForUser(User $user, Carbon $from, Carbon $to): array
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->startOfDay();
        if ($to->lt($from)) {
            return [];
        }

        // Load holiday/dinas maps once for the whole range — never call
        // isEmployeeScheduledOffDay() here (that helper re-queries per day).
        $holidayMap = $this->holidayMapBetween($from, $to, $user->id);
        $dinasMap = $this->dinasMapBetween($from, $to, $user->id);
        $overrideMap = $this->workDayOverrideMapBetween($from, $to, $user->id);
        $settings = $this->settingsForUser($user);
        $rows = [];

        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            if ($this->isOnDinas($d, $dinasMap) || $overrideMap->has($d->toDateString())) {
                continue;
            }

            $isHoliday = $this->isHoliday($d, $holidayMap);
            $isWeeklyOff = ! $this->isConfiguredWorkDay($d, $settings, $user);
            if (! $isHoliday && ! $isWeeklyOff) {
                continue;
            }

            $key = $d->toDateString();
            $label = (string) $holidayMap->get($key, '');
            if ($label === '') {
                $label = $isWeeklyOff ? 'Libur mingguan' : 'Libur';
            }

            $rows[] = [
                'date' => $key,
                'label' => $label,
            ];
        }

        return $rows;
    }

    /** Izin 1/2 hari disetujui: setengah_masuk | setengah_pulang */
    public function approvedHalfDayLeaveType(Carbon $date, ?int $userId = null): ?string
    {
        if (! $userId || ! Schema::hasTable('leave_requests')) {
            return null;
        }

        $key = $date->toDateString();

        $leave = LeaveRequest::query()
            ->where('user_id', $userId)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereIn('type', LeaveRequest::halfDayTypes())
            ->whereDate('start_date', '<=', $key)
            ->whereDate('end_date', '>=', $key)
            ->orderByDesc('id')
            ->first(['type']);

        return $leave ? (string) $leave->type : null;
    }

    /** WFH disetujui: tetap hari kerja (wajib absen), bukan libur. */
    public function hasApprovedWfh(Carbon $date, ?int $userId = null): bool
    {
        if (! $userId || ! Schema::hasTable('leave_requests')) {
            return false;
        }

        $key = $date->toDateString();

        return LeaveRequest::query()
            ->where('user_id', $userId)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->where('type', LeaveRequest::TYPE_WFH)
            ->whereDate('start_date', '<=', $key)
            ->whereDate('end_date', '>=', $key)
            ->exists();
    }

    /** @return Collection<string, string> date => label */
    public function holidayMapBetween(Carbon $from, Carbon $to, ?int $userId = null): Collection
    {
        if (! Schema::hasTable('attendance_holidays') || ! $userId) {
            return collect();
        }

        return AttendanceHoliday::query()
            ->where('user_id', $userId)
            ->whereBetween('holiday_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('holiday_date')
            ->get()
            ->mapWithKeys(fn (AttendanceHoliday $row) => [
                $row->holiday_date->toDateString() => (string) ($row->name ?: 'Libur'),
            ]);
    }

    /**
     * Peta izin/sakit/cuti/WFH disetujui per user per tanggal.
     *
     * @param  list<int>  $userIds
     * @return Collection<int, Collection<string, array{type: string, label: string}>>
     */
    public function approvedLeaveMapsForUsers(Carbon $from, Carbon $to, array $userIds): Collection
    {
        if (! Schema::hasTable('leave_requests') || $userIds === []) {
            return collect();
        }

        $leaves = LeaveRequest::query()
            ->whereIn('user_id', $userIds)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereIn('type', [
                LeaveRequest::TYPE_IZIN,
                LeaveRequest::TYPE_SAKIT,
                LeaveRequest::TYPE_CUTI,
                LeaveRequest::TYPE_WFH,
            ])
            ->whereDate('start_date', '<=', $to->toDateString())
            ->whereDate('end_date', '>=', $from->toDateString())
            ->get(['user_id', 'type', 'reason', 'start_date', 'end_date']);

        $maps = collect();
        foreach ($leaves as $leave) {
            $uid = (int) $leave->user_id;
            if (! $maps->has($uid)) {
                $maps->put($uid, collect());
            }

            $type = (string) $leave->type;
            $label = $leave->typeLabel();
            $reason = trim((string) $leave->reason);
            if ($reason !== '') {
                $label .= ': '.mb_substr($reason, 0, 80);
            }

            $cursor = $leave->start_date->copy()->startOfDay();
            $end = $leave->end_date->copy()->startOfDay();
            if ($cursor->lt($from)) {
                $cursor = $from->copy()->startOfDay();
            }
            if ($end->gt($to)) {
                $end = $to->copy()->startOfDay();
            }

            for (; $cursor->lte($end); $cursor->addDay()) {
                $maps->get($uid)->put($cursor->toDateString(), [
                    'type' => $type,
                    'label' => $label,
                ]);
            }
        }

        return $maps;
    }

    /**
     * @param  list<int>  $userIds
     * @return Collection<int, Collection<string, string>> user_id => (date => label)
     */
    public function holidayMapsForUsers(Carbon $from, Carbon $to, array $userIds): Collection
    {
        if (! Schema::hasTable('attendance_holidays') || $userIds === []) {
            return collect();
        }

        return AttendanceHoliday::query()
            ->whereIn('user_id', $userIds)
            ->whereBetween('holiday_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('holiday_date')
            ->get()
            ->groupBy('user_id')
            ->map(fn (Collection $rows) => $rows->mapWithKeys(
                fn (AttendanceHoliday $row) => [
                    $row->holiday_date->toDateString() => (string) ($row->name ?: 'Libur'),
                ]
            ));
    }

    /** @return list<int> */
    public function workDayNumbersForUser(?User $user): array
    {
        if ($user?->section === 'speedshop') {
            return [1, 2, 3, 4, 5, 6, 7];
        }

        return [1, 2, 3, 4, 5, 6];
    }

    public function isConfiguredWorkDay(
        Carbon $date,
        ?AttendanceSetting $settings = null,
        ?User $user = null,
    ): bool {
        return in_array($date->dayOfWeekIso, $this->workDayNumbersForUser($user), true);
    }

    /** Batas jam masuk (hari ini / tanggal terkait) berdasarkan setting. */
    public function scheduledCheckInAt(
        ?Carbon $at = null,
        ?AttendanceSetting $settings = null,
        ?User $user = null,
        ?AttendanceLocation $location = null,
    ): Carbon {
        $at = AttendanceSetting::at($at);
        if ($location) {
            $settings = $this->settingsForLocation($location, $at);
        } elseif ($user) {
            $settings = $this->settingsForUser($user, $at);
        } else {
            $settings ??= $this->settings();
        }
        $time = (string) ($settings->check_in_time ?? AttendanceSetting::DEFAULT_CHECK_IN);

        return AttendanceSetting::applyClock($at, $time, AttendanceSetting::DEFAULT_CHECK_IN);
    }

    public function isLateAt(
        ?Carbon $at = null,
        ?AttendanceSetting $settings = null,
        ?User $user = null,
        ?AttendanceLocation $location = null,
    ): bool {
        $at = AttendanceSetting::at($at);

        return $at->gt($this->scheduledCheckInAt($at, $settings, $user, $location));
    }

    /** Sudah lewat batas akhir absen masuk (bukan jam masuk / acuan telat). */
    public function isPastCheckInWindowEnd(
        ?Carbon $at = null,
        ?User $user = null,
        ?AttendanceLocation $location = null,
    ): bool {
        if ($user?->hasFlexibleAttendanceSchedule()) {
            return false;
        }

        $at = AttendanceSetting::at($at);
        if ($location) {
            $settings = $this->settingsForLocation($location, $at);
        } elseif ($user) {
            $settings = $this->settingsForUser($user, $at);
        } else {
            $settings = $this->withAttendanceWindows($this->settings());
        }

        $time = (string) ($settings->check_in_window_end ?? AttendanceSetting::DEFAULT_CHECK_IN_WINDOW_END);
        $deadline = AttendanceSetting::applyClock($at, $time, AttendanceSetting::DEFAULT_CHECK_IN_WINDOW_END);

        return $at->gt($deadline);
    }

    public function isHoliday(Carbon $date, ?Collection $holidayMap = null): bool
    {
        return ($holidayMap ?? collect())->has($date->toDateString());
    }

    public function isOnDinas(Carbon $date, ?Collection $dinasMap = null): bool
    {
        return ($dinasMap ?? collect())->has($date->toDateString());
    }

    public function isOffDay(
        Carbon $date,
        ?AttendanceSetting $settings = null,
        ?Collection $holidayMap = null,
        ?Collection $dinasMap = null,
        ?User $user = null,
    ): bool {
        return $this->isHoliday($date, $holidayMap)
            || $this->isOnDinas($date, $dinasMap)
            || ! $this->isConfiguredWorkDay($date, $settings, $user);
    }

    /** @return Collection<string, string> date => label */
    public function dinasMapBetween(Carbon $from, Carbon $to, ?int $userId = null): Collection
    {
        if (! $userId) {
            return collect();
        }

        return $this->dinasMapsForUsers($from, $to, [$userId])->get($userId, collect());
    }

    /**
     * @param  list<int>  $userIds
     * @return Collection<int, Collection<string, string>> user_id => (date => label)
     */
    public function dinasMapsForUsers(Carbon $from, Carbon $to, array $userIds): Collection
    {
        $userIdSet = collect($userIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();
        if ($userIdSet->isEmpty()) {
            return collect();
        }

        return $userIdSet->mapWithKeys(fn (int $id) => [$id => collect()]);
    }

    /**
     * @param  list<int>  $userIds
     * @return Collection<int, Collection<string, string>> user_id => (date => label)
     */
    public function workDayOverrideMapsForUsers(Carbon $from, Carbon $to, array $userIds): Collection
    {
        if (! Schema::hasTable('attendance_work_day_overrides') || $userIds === []) {
            return collect();
        }

        return AttendanceWorkDayOverride::query()
            ->whereIn('user_id', $userIds)
            ->whereBetween('work_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('work_date')
            ->get()
            ->groupBy('user_id')
            ->map(fn (Collection $rows) => $rows->mapWithKeys(
                fn (AttendanceWorkDayOverride $row) => [
                    $row->work_date->toDateString() => 'Masuk hari libur',
                ]
            ));
    }

    public function requiresAttendanceToday(?Carbon $today = null, ?int $userId = null): bool
    {
        if ($userId) {
            $user = User::query()->find($userId);
            if ($user?->isOwner()) {
                return false;
            }
        }

        $today ??= AttendanceSetting::now()->startOfDay();
        $settings = $this->settings();
        $user = $userId ? User::query()->find($userId) : null;
        $holidayMap = $this->holidayMapBetween($today->copy(), $today->copy(), $userId);
        $dinasMap = $this->dinasMapBetween($today->copy(), $today->copy(), $userId);

        if ($this->hasWorkDayOverride($today, $userId)) {
            return true;
        }

        return $this->isConfiguredWorkDay($today, $settings, $user ?? null)
            && ! $this->isHoliday($today, $holidayMap)
            && ! $this->isOnDinas($today, $dinasMap);
    }

    /**
     * @param  list<string>  $attendedDates
     * @param  list<string>  $lateDates
     * @return list<array{key: string, day: int, in_month: bool, is_today: bool, status: string, label: ?string}>
     */
    public function buildCalendarDays(
        Carbon $month,
        array $attendedDates,
        ?string $tz = null,
        ?int $userId = null,
        array $lateDates = [],
    ): array {
        $tz = $tz ?: (string) config('app.timezone');
        $calendarMonth = $month->copy()->timezone($tz)->startOfMonth();
        $monthStart = $calendarMonth->copy()->startOfMonth();
        $monthEnd = $calendarMonth->copy()->endOfMonth();
        $calStart = $calendarMonth->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $calEnd = $calendarMonth->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $settings = $this->settings();
        $user = $userId ? User::query()->find($userId) : null;
        $holidayMap = $this->holidayMapBetween($monthStart, $monthEnd, $userId);
        $dinasMap = $this->dinasMapBetween($monthStart, $monthEnd, $userId);
        $workOverrideMap = $this->workDayOverrideMapBetween($monthStart, $monthEnd, $userId);
        $attendedSet = collect($attendedDates)->flip();
        $lateSet = collect($lateDates)->flip();
        $todayKey = AttendanceSetting::now()->toDateString();

        $days = [];
        for ($d = $calStart->copy(); $d->lte($calEnd); $d->addDay()) {
            $key = $d->toDateString();
            $inMonth = $d->month === $calendarMonth->month;
            $isFuture = $key > $todayKey;
            $isToday = $key === $todayKey;
            $hadCheckIn = $attendedSet->has($key);
            $wasLate = $lateSet->has($key);
            $isHoliday = $this->isHoliday($d, $holidayMap);
            $isDinas = $this->isOnDinas($d, $dinasMap);
            $isWeeklyOff = ! $this->isConfiguredWorkDay($d, $settings, $user);
            $isExtraWorkDay = $workOverrideMap->has($key);

            $status = 'muted';
            $label = null;

            if ($inMonth) {
                if ($isExtraWorkDay) {
                    $label = (string) $workOverrideMap->get($key, 'Masuk hari libur');
                    if ($hadCheckIn && $wasLate) {
                        $status = 'late';
                        $label = 'Telat';
                    } elseif ($hadCheckIn) {
                        $status = 'present';
                    } elseif ($isToday) {
                        if ($this->isPastCheckInWindowEnd(AttendanceSetting::now(), $user)) {
                            $status = 'absent';
                            $label = 'Tidak hadir';
                        } else {
                            $status = 'today';
                        }
                    } elseif (! $isFuture) {
                        $status = 'absent';
                    }
                } elseif ($isHoliday) {
                    $status = 'holiday';
                    $label = (string) $holidayMap->get($key, 'Libur');
                } elseif ($isDinas) {
                    $status = 'dinas';
                    $label = (string) $dinasMap->get($key, 'Perjalanan dinas');
                } elseif ($isWeeklyOff) {
                    $status = 'off';
                    $label = 'Hari libur';
                } elseif ($hadCheckIn && $wasLate) {
                    $status = 'late';
                    $label = 'Telat';
                } elseif ($hadCheckIn) {
                    $status = 'present';
                } elseif ($isToday) {
                    if ($this->isPastCheckInWindowEnd(AttendanceSetting::now(), $user)) {
                        $status = 'absent';
                        $label = 'Tidak hadir';
                    } else {
                        $status = 'today';
                    }
                } elseif (! $isFuture) {
                    $status = 'absent';
                }
            }

            $days[] = [
                'key' => $key,
                'day' => $d->day,
                'in_month' => $inMonth,
                'is_today' => $isToday,
                'status' => $status,
                'label' => $label,
            ];
        }

        return $days;
    }

    /**
     * @return list<array{
     *     user: User,
     *     work_days: int,
     *     present: int,
     *     absent: int,
     *     off_days: int
     * }>
     */
    public function buildMonthlyRecap(Carbon $month, array $filters = []): array
    {
        $tz = (string) config('app.timezone');
        $calendarMonth = $month->copy()->timezone($tz)->startOfMonth();
        $monthStart = $calendarMonth->copy()->startOfMonth();
        $monthEnd = $calendarMonth->copy()->endOfMonth();
        $todayKey = AttendanceSetting::now()->toDateString();

        $settings = $this->settings();

        $usersQuery = User::query()->adminKaryawan()->orderBy('name');
        if (! empty($filters['name'])) {
            $term = '%'.addcslashes((string) $filters['name'], '%_\\').'%';
            $usersQuery->where('name', 'like', $term);
        }
        if (! empty($filters['section'])) {
            $section = (string) $filters['section'];
            if ($section === User::SECTION_MODIFIKASI_ORI) {
                $usersQuery->whereIn('section', [User::SECTION_MODIFIKASI_ORI, 'produksi', 'warehouse']);
            } else {
                $usersQuery->where('section', $section);
            }
        }

        $users = $usersQuery->get(['id', 'name', 'email', 'section', 'role']);
        if ($users->isEmpty()) {
            return [];
        }

        $holidayMaps = $this->holidayMapsForUsers($monthStart, $monthEnd, $users->pluck('id')->all());
        $dinasMaps = $this->dinasMapsForUsers($monthStart, $monthEnd, $users->pluck('id')->all());
        $workOverrideMaps = $this->workDayOverrideMapsForUsers($monthStart, $monthEnd, $users->pluck('id')->all());

        $attendedByUser = AttendanceRecord::query()
            ->whereIn('user_id', $users->pluck('id'))
            ->whereIn('status', AttendanceRecord::activeStatuses())
            ->where('type', AttendanceRecord::TYPE_CHECK_IN)
            ->whereBetween('checked_at', [
                $monthStart->copy()->startOfDay(),
                $monthEnd->copy()->endOfDay(),
            ])
            ->get(['user_id', 'checked_at'])
            ->groupBy('user_id')
            ->map(fn (Collection $rows) => $rows
                ->map(fn (AttendanceRecord $row) => $row->checked_at->timezone($tz)->toDateString())
                ->unique()
                ->flip());

        $rows = [];
        foreach ($users as $user) {
            $attended = $attendedByUser->get($user->id, collect());
            $holidayMap = $holidayMaps->get($user->id, collect());
            $dinasMap = $dinasMaps->get($user->id, collect());
            $workOverrideMap = $workOverrideMaps->get($user->id, collect());
            $workDays = 0;
            $present = 0;
            $absent = 0;
            $offDays = 0;

            for ($d = $monthStart->copy(); $d->lte($monthEnd); $d->addDay()) {
                $key = $d->toDateString();
                if ($key > $todayKey) {
                    continue;
                }

                $isHoliday = $this->isHoliday($d, $holidayMap);
                $isDinas = $this->isOnDinas($d, $dinasMap);
                $isWeeklyOff = ! $this->isConfiguredWorkDay($d, $settings, $user);
                $isExtraWorkDay = $workOverrideMap->has($key);

                if ($isExtraWorkDay) {
                    $workDays++;
                    if ($attended->has($key)) {
                        $present++;
                    } else {
                        $absent++;
                    }

                    continue;
                }

                if ($isHoliday || $isDinas || $isWeeklyOff) {
                    $offDays++;

                    continue;
                }

                $workDays++;
                if ($attended->has($key)) {
                    $present++;
                } else {
                    $absent++;
                }
            }

            $rows[] = [
                'user' => $user,
                'work_days' => $workDays,
                'present' => $present,
                'absent' => $absent,
                'off_days' => $offDays,
            ];
        }

        return $rows;
    }

    /** @return array{present: int, absent: int, off_days: int, work_days: int} */
    public function summarizeRecapRows(array $rows): array
    {
        return [
            'present' => (int) collect($rows)->sum('present'),
            'absent' => (int) collect($rows)->sum('absent'),
            'off_days' => (int) collect($rows)->sum('off_days'),
            'work_days' => (int) collect($rows)->sum('work_days'),
        ];
    }

    /**
     * Grid kalender rekap absensi (gaya Jadwal Libur) untuk karyawan yang di-scope.
     *
     * @param  Collection<int, User>  $users
     * @return array{
     *     monthDays: list<array{day: int, key: string, dow: string, iso: int, is_sunday: bool}>,
     *     cells: array<int, array<string, array{code: string, class: string, title: string}>>,
     *     employeeWorkDays: array<int, list<int>>,
     *     summary: array{present: int, late: int, absent: int, off_days: int, pending: int}
     * }
     */
    public function buildMonthlyCalendarRecap(Carbon $month, Collection $users): array
    {
        $tz = (string) config('app.timezone');
        $calendarMonth = $month->copy()->timezone($tz)->startOfMonth();
        $monthStart = $calendarMonth->copy()->startOfMonth();
        $monthEnd = $calendarMonth->copy()->endOfMonth();
        $todayKey = AttendanceSetting::now()->toDateString();

        $monthDays = [];
        for ($day = 1; $day <= $calendarMonth->daysInMonth; $day++) {
            $date = $calendarMonth->copy()->day($day);
            $monthDays[] = [
                'day' => $day,
                'key' => $date->toDateString(),
                'dow' => $date->locale('id')->isoFormat('ddd'),
                'iso' => $date->dayOfWeekIso,
                'is_sunday' => $date->dayOfWeekIso === 7,
            ];
        }

        if ($users->isEmpty()) {
            return [
                'monthDays' => $monthDays,
                'cells' => [],
                'employeeWorkDays' => [],
                'summary' => [
                    'present' => 0,
                    'late' => 0,
                    'absent' => 0,
                    'off_days' => 0,
                    'pending' => 0,
                ],
            ];
        }

        $userIds = $users->pluck('id')->map(fn ($id) => (int) $id)->all();
        $holidayMaps = $this->holidayMapsForUsers($monthStart, $monthEnd, $userIds);
        $dinasMaps = $this->dinasMapsForUsers($monthStart, $monthEnd, $userIds);
        $workOverrideMaps = $this->workDayOverrideMapsForUsers($monthStart, $monthEnd, $userIds);
        $leaveMaps = $this->approvedLeaveMapsForUsers($monthStart, $monthEnd, $userIds);

        $attendanceRows = AttendanceRecord::query()
            ->whereIn('user_id', $userIds)
            ->whereIn('status', [
                AttendanceRecord::STATUS_VALID,
                AttendanceRecord::STATUS_PENDING,
            ])
            ->whereIn('type', [
                AttendanceRecord::TYPE_CHECK_IN,
                AttendanceRecord::TYPE_CHECK_OUT,
            ])
            ->whereBetween('checked_at', [
                $monthStart->copy()->startOfDay(),
                $monthEnd->copy()->endOfDay(),
            ])
            ->orderBy('checked_at')
            ->get(['user_id', 'type', 'checked_at', 'is_late', 'status']);

        /** @var Collection<int, Collection<string, AttendanceRecord>> $checkInByUser */
        $checkInByUser = $attendanceRows
            ->where('type', AttendanceRecord::TYPE_CHECK_IN)
            ->groupBy('user_id')
            ->map(function (Collection $rows) use ($tz) {
                return $rows->mapWithKeys(function (AttendanceRecord $row) use ($tz) {
                    $key = $row->checked_at->timezone($tz)->toDateString();

                    return [$key => $row];
                });
            });

        /** @var Collection<int, Collection<string, AttendanceRecord>> $checkOutByUser */
        $checkOutByUser = $attendanceRows
            ->where('type', AttendanceRecord::TYPE_CHECK_OUT)
            ->groupBy('user_id')
            ->map(function (Collection $rows) use ($tz) {
                return $rows->mapWithKeys(function (AttendanceRecord $row) use ($tz) {
                    $key = $row->checked_at->timezone($tz)->toDateString();

                    return [$key => $row];
                });
            });

        $employeeWorkDays = [];
        $cells = [];
        $summary = [
            'present' => 0,
            'late' => 0,
            'absent' => 0,
            'off_days' => 0,
            'pending' => 0,
        ];

        foreach ($users as $user) {
            $uid = (int) $user->id;
            $workDays = $this->workDayNumbersForUser($user);
            $employeeWorkDays[$uid] = $workDays;
            $holidayMap = $holidayMaps->get($uid, collect());
            $dinasMap = $dinasMaps->get($uid, collect());
            $workOverrideMap = $workOverrideMaps->get($uid, collect());
            $leaveMap = $leaveMaps->get($uid, collect());
            $attended = $checkInByUser->get($uid, collect());
            $checkedOut = $checkOutByUser->get($uid, collect());
            $cells[$uid] = [];

            foreach ($monthDays as $d) {
                $key = $d['key'];
                $date = Carbon::createFromFormat('Y-m-d', $key, $tz)->startOfDay();
                $isSunday = (bool) $d['is_sunday'];
                $isWork = in_array($d['iso'], $workDays, true);
                $isExtraWorkDay = $workOverrideMap->has($key);
                $isHoliday = $this->isHoliday($date, $holidayMap);
                $isDinas = $this->isOnDinas($date, $dinasMap);
                $leaveInfo = $leaveMap->get($key);
                $leaveType = is_array($leaveInfo) ? (string) ($leaveInfo['type'] ?? '') : '';
                $leaveTitle = is_array($leaveInfo) ? (string) ($leaveInfo['label'] ?? '') : '';
                $isWfh = $leaveType === LeaveRequest::TYPE_WFH;
                /** @var AttendanceRecord|null $checkIn */
                $checkIn = $attended->get($key);
                /** @var AttendanceRecord|null $checkOut */
                $checkOut = $checkedOut->get($key);

                if ($key > $todayKey) {
                    $cells[$uid][$key] = $this->calendarRecapEmptyCell(
                        (! $isWork && ! $isExtraWorkDay)
                            ? ($isSunday ? 'cell-sunday' : 'cell-off')
                            : 'cell-future',
                        'Belum lewat',
                    );

                    continue;
                }

                if ($isDinas && ! $isExtraWorkDay) {
                    $cells[$uid][$key] = $this->calendarRecapEmptyCell(
                        'cell-dinas',
                        (string) ($dinasMap->get($key) ?: 'Perjalanan dinas'),
                        'DINAS',
                    );
                    $summary['off_days']++;

                    continue;
                }

                // Izin / sakit / cuti disetujui → tampilkan label jenis, bukan "LIBUR".
                if (
                    ! $isExtraWorkDay
                    && in_array($leaveType, [
                        LeaveRequest::TYPE_IZIN,
                        LeaveRequest::TYPE_SAKIT,
                        LeaveRequest::TYPE_CUTI,
                    ], true)
                ) {
                    $cells[$uid][$key] = $this->calendarRecapLeaveTypeCell($leaveType, $leaveTitle);
                    $summary['off_days']++;

                    continue;
                }

                if ($isHoliday && ! $isExtraWorkDay) {
                    $holidayName = (string) ($holidayMap->get($key) ?: 'Libur');
                    $inferred = $this->inferLeaveTypeFromHolidayName($holidayName);
                    if ($inferred !== null) {
                        $cells[$uid][$key] = $this->calendarRecapLeaveTypeCell($inferred, $holidayName);
                    } else {
                        $cells[$uid][$key] = $this->calendarRecapEmptyCell(
                            'cell-libur',
                            $holidayName,
                            'LIBUR',
                        );
                    }
                    $summary['off_days']++;

                    continue;
                }

                if (! $isWork && ! $isExtraWorkDay) {
                    $cells[$uid][$key] = $this->calendarRecapEmptyCell(
                        $isSunday ? 'cell-sunday' : 'cell-off',
                        $isSunday ? 'Libur mingguan' : 'Hari non-kerja',
                    );
                    $summary['off_days']++;

                    continue;
                }

                if ($checkIn) {
                    $inTime = $checkIn->checked_at->timezone($tz)->format('H:i');
                    $outTime = $checkOut?->checked_at->timezone($tz)->format('H:i');
                    $timeLabel = $outTime ? $inTime.'–'.$outTime : $inTime;

                    if ($checkIn->status === AttendanceRecord::STATUS_PENDING) {
                        $cells[$uid][$key] = $this->calendarRecapAttendanceCell(
                            'P',
                            'cell-pending',
                            'Menunggu validasi'.($checkIn->is_late ? ' · Telat' : '').' · '.$timeLabel,
                            $inTime,
                            $outTime,
                        );
                        $summary['pending']++;
                    } elseif ($isWfh) {
                        $cells[$uid][$key] = $this->calendarRecapAttendanceCell(
                            'WFH',
                            'cell-wfh',
                            ($leaveTitle !== '' ? $leaveTitle.' · ' : 'WFH · ').$timeLabel,
                            $inTime,
                            $outTime,
                        );
                        $summary['present']++;
                        if ($checkIn->is_late && ! $user->hasFlexibleAttendanceSchedule()) {
                            $summary['late']++;
                        }
                    } elseif ($checkIn->is_late && ! $user->hasFlexibleAttendanceSchedule()) {
                        $cells[$uid][$key] = $this->calendarRecapAttendanceCell(
                            'T',
                            'cell-late',
                            'Hadir telat · '.$timeLabel,
                            $inTime,
                            $outTime,
                        );
                        $summary['late']++;
                        $summary['present']++;
                    } else {
                        $cells[$uid][$key] = $this->calendarRecapAttendanceCell(
                            'H',
                            'cell-hadir',
                            'Hadir · '.$timeLabel,
                            $inTime,
                            $outTime,
                        );
                        $summary['present']++;
                    }

                    continue;
                }

                // Role fleksibel (Marketing 2 shift): hari ini belum absen ≠ alpha sampai hari berganti.
                if ($key === $todayKey && $user->hasFlexibleAttendanceSchedule()) {
                    $cells[$uid][$key] = $this->calendarRecapEmptyCell(
                        'cell-future',
                        $isWfh ? 'WFH · Belum absen (jadwal fleksibel)' : 'Belum absen (jadwal fleksibel)',
                    );

                    continue;
                }

                $cells[$uid][$key] = $this->calendarRecapEmptyCell(
                    'cell-alpha',
                    $isWfh
                        ? (($leaveTitle !== '' ? $leaveTitle.' · ' : 'WFH · ').'Tidak hadir')
                        : 'Tidak hadir',
                    'A',
                );
                $summary['absent']++;
            }
        }

        return [
            'monthDays' => $monthDays,
            'cells' => $cells,
            'employeeWorkDays' => $employeeWorkDays,
            'summary' => $summary,
        ];
    }

    /**
     * @return array{code: string, class: string, title: string, in_time: ?string, out_time: ?string, label: string}
     */
    private function calendarRecapEmptyCell(string $class, string $title, string $code = ''): array
    {
        return [
            'code' => $code,
            'class' => $class,
            'title' => $title,
            'in_time' => null,
            'out_time' => null,
            'label' => $code,
        ];
    }

    /**
     * @return array{code: string, class: string, title: string, in_time: ?string, out_time: ?string, label: string}
     */
    private function calendarRecapLeaveTypeCell(string $type, string $title = ''): array
    {
        [$class, $code, $fallback] = match ($type) {
            LeaveRequest::TYPE_IZIN => ['cell-izin', 'IZIN', 'Izin'],
            LeaveRequest::TYPE_SAKIT => ['cell-sakit', 'SAKIT', 'Sakit'],
            LeaveRequest::TYPE_CUTI => ['cell-cuti', 'CUTI', 'Cuti'],
            LeaveRequest::TYPE_WFH => ['cell-wfh', 'WFH', 'WFH'],
            default => ['cell-libur', 'LIBUR', 'Libur'],
        };

        return $this->calendarRecapEmptyCell($class, $title !== '' ? $title : $fallback, $code);
    }

    private function inferLeaveTypeFromHolidayName(string $name): ?string
    {
        $normalized = mb_strtolower(trim($name));
        if ($normalized === '') {
            return null;
        }

        foreach ([
            LeaveRequest::TYPE_SAKIT => ['sakit'],
            LeaveRequest::TYPE_IZIN => ['izin'],
            LeaveRequest::TYPE_CUTI => ['cuti'],
            LeaveRequest::TYPE_WFH => ['wfh'],
        ] as $type => $needles) {
            foreach ($needles as $needle) {
                if (str_starts_with($normalized, $needle)) {
                    return $type;
                }
            }
        }

        return null;
    }

    /**
     * @return array{code: string, class: string, title: string, in_time: ?string, out_time: ?string, label: string}
     */
    private function calendarRecapAttendanceCell(
        string $code,
        string $class,
        string $title,
        ?string $inTime,
        ?string $outTime,
    ): array {
        $label = $code;
        if ($inTime) {
            $label .= ' '.$inTime.($outTime ? '-'.$outTime : '');
        }

        return [
            'code' => $code,
            'class' => $class,
            'title' => $title,
            'in_time' => $inTime,
            'out_time' => $outTime,
            'label' => $label,
        ];
    }

    /** @var list<string> */
    public const RECAP_TOTAL_CODES = ['H', 'T', 'A', 'IZIN', 'SAKIT', 'CUTI', 'WFH', 'DINAS', 'P'];

    /**
     * @param  array<string, array{code?: string}>  $dayCells
     * @return array<string, int>
     */
    public static function recapStatusTotals(array $dayCells): array
    {
        $totals = array_fill_keys(self::RECAP_TOTAL_CODES, 0);
        foreach ($dayCells as $cell) {
            $code = strtoupper(trim((string) ($cell['code'] ?? '')));
            if (isset($totals[$code])) {
                $totals[$code]++;
            }
        }

        return $totals;
    }
}
