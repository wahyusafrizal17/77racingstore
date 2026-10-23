<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLocation;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSetting;
use App\Services\AttendanceScheduleService;
use App\Services\AttendanceService;
use App\Support\CalendarMonth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use League\Flysystem\UnableToWriteFile;

class AbsensiController extends Controller
{
    public function __construct(
        private readonly AttendanceService $attendance,
        private readonly AttendanceScheduleService $schedule,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        if ($user?->isOwner()) {
            return redirect()
                ->route('owner.dashboard')
                ->with('info', 'Akun Owner tidak memerlukan absensi.');
        }

        $locations = $this->attendance->locationsForUser($user);
        $checkIn = $this->attendance->todayCheckIn($user);
        $checkOut = $this->attendance->todayCheckOut($user);

        $tz = AttendanceSetting::TIMEZONE;
        $now = AttendanceSetting::now();
        $calendarMonth = CalendarMonth::parse($request->query('bulan'), $tz);

        $monthStart = $calendarMonth->copy()->startOfMonth();
        $monthEnd = $calendarMonth->copy()->endOfMonth();

        $monthCheckIns = AttendanceRecord::query()
            ->where('user_id', $user->id)
            ->whereIn('status', AttendanceRecord::activeStatuses())
            ->where('type', AttendanceRecord::TYPE_CHECK_IN)
            ->whereBetween('checked_at', [
                $monthStart->copy()->startOfDay(),
                $monthEnd->copy()->endOfDay(),
            ])
            ->get(['checked_at', 'is_late']);

        $attendedDates = $monthCheckIns
            ->map(fn (AttendanceRecord $row) => $row->checked_at->timezone($tz)->format('Y-m-d'))
            ->unique()
            ->values()
            ->all();

        $lateDates = $monthCheckIns
            ->filter(fn (AttendanceRecord $row) => (bool) $row->is_late)
            ->map(fn (AttendanceRecord $row) => $row->checked_at->timezone($tz)->format('Y-m-d'))
            ->unique()
            ->values()
            ->all();

        $settings = $this->schedule->settingsForUser($user, $now);
        $calendarDays = $this->schedule->buildCalendarDays($calendarMonth, $attendedDates, $tz, $user->id, $lateDates);

        $today = $now->copy()->startOfDay();
        $selectedDate = $today->copy();
        $tanggalInput = (string) $request->query('tanggal', '');
        if ($tanggalInput !== '') {
            try {
                $parsed = Carbon::createFromFormat('Y-m-d', $tanggalInput, $tz)->startOfDay();
                if ($parsed->lte($today) && $parsed->between($monthStart->copy()->startOfDay(), $monthEnd->copy()->endOfDay())) {
                    $selectedDate = $parsed;
                }
            } catch (\Throwable) {
                // keep today
            }
        }
        $isSelectedToday = $selectedDate->isSameDay($today);
        $selectedCheckIn = $this->attendance->checkInOnDate($user, $selectedDate);
        $selectedCheckOut = $this->attendance->checkOutOnDate($user, $selectedDate);
        $selectedDayCalendar = collect($calendarDays)->firstWhere('key', $selectedDate->toDateString());

        $requiresAttendanceToday = $this->schedule->requiresAttendanceToday($today, $user->id);
        $scheduledCheckInLabel = $this->schedule->scheduledCheckInAt($now, null, $user)->format('H:i');
        $breakWindow = $this->schedule->breakWindowForUser($user);
        $pastCheckInDeadline = $requiresAttendanceToday
            && ! $checkIn
            && $this->attendance->isPastCheckInDeadline($now, $user);
        $beforeCheckInWindow = $requiresAttendanceToday
            && ! $checkIn
            && ! $pastCheckInDeadline
            && ! $this->attendance->isWithinCheckInWindow($now, $user);

        $attendancePhase = 'off';
        if ($requiresAttendanceToday) {
            if ($checkIn && $checkOut) {
                $attendancePhase = 'done';
            } elseif ($checkIn) {
                $attendancePhase = 'check_out';
            } else {
                $attendancePhase = 'check_in';
            }
        }

        $nextType = null;
        if ($attendancePhase === 'check_in' && $this->attendance->isWithinCheckInWindow($now, $user)) {
            $nextType = AttendanceRecord::TYPE_CHECK_IN;
        } elseif ($attendancePhase === 'check_out' && $this->attendance->isWithinCheckOutWindow($now, $user)) {
            $nextType = AttendanceRecord::TYPE_CHECK_OUT;
        }

        // Tampilkan detail (termasuk foto) untuk hari yang dipilih jika sudah ada absensi,
        // atau hari ini setelah absen masuk / selesai.
        $showDayDetail = $request->has('tanggal')
            || $selectedCheckIn !== null
            || $selectedCheckOut !== null
            || ($isSelectedToday && $attendancePhase === 'done');

        $checkInWindowOpen = $this->attendance->isWithinCheckInWindow($now, $user);
        $checkOutWindowOpen = $this->attendance->isWithinCheckOutWindow($now, $user);

        $defaultLocationId = $this->resolveDefaultLocationId($user, $locations);
        $autoPickLocation = $user->isSpeedshopAccounting();

        return view('absensi.index', [
            'layout' => $this->attendance->layoutForUser($user),
            'locations' => $locations,
            'checkIn' => $checkIn,
            'checkOut' => $checkOut,
            'nextType' => $nextType,
            'attendedDates' => $attendedDates,
            'lateDates' => $lateDates,
            'calendarDays' => $calendarDays,
            'calendarMonth' => $calendarMonth,
            'selectedDate' => $selectedDate,
            'isSelectedToday' => $isSelectedToday,
            'selectedCheckIn' => $selectedCheckIn,
            'selectedCheckOut' => $selectedCheckOut,
            'selectedDayCalendar' => $selectedDayCalendar,
            'showDayDetail' => $showDayDetail,
            'attendanceSettings' => $settings,
            'breakWindow' => $breakWindow,
            'requiresAttendanceToday' => $requiresAttendanceToday,
            'pastCheckInDeadline' => $pastCheckInDeadline,
            'beforeCheckInWindow' => $beforeCheckInWindow,
            'scheduledCheckInLabel' => $scheduledCheckInLabel,
            'todayOffLabel' => $this->resolveTodayOffLabel($today, $user),
            'defaultLocationId' => $defaultLocationId,
            'autoPickLocation' => $autoPickLocation,
            'attendancePhase' => $attendancePhase,
            'checkInWindowLabel' => $this->attendance->checkInWindowLabel($user),
            'checkOutWindowLabel' => $this->attendance->checkOutWindowLabel($user),
            'checkInWindowOpen' => $checkInWindowOpen,
            'checkOutWindowOpen' => $checkOutWindowOpen,
            'flexibleAttendance' => $user->hasFlexibleAttendanceSchedule(),
            'isWfhToday' => $this->schedule->hasApprovedWfh($today, $user->id),
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, AttendanceLocation>  $locations
     */
    private function resolveDefaultLocationId($user, $locations): ?int
    {
        if ($locations->isEmpty()) {
            return null;
        }

        if ($user->attendance_location_id && $locations->contains('id', (int) $user->attendance_location_id)) {
            return (int) $user->attendance_location_id;
        }

        $activeSpeedshopId = $user->activeSpeedshopId();
        if ($activeSpeedshopId) {
            $matched = $locations->first(
                fn (AttendanceLocation $loc) => $loc->locatable_type === \App\Models\Speedshop::class
                    && (int) $loc->locatable_id === (int) $activeSpeedshopId
            );
            if ($matched) {
                return (int) $matched->id;
            }
        }

        return (int) $locations->first()->id;
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if ($user?->isOwner()) {
            return redirect()
                ->route('owner.dashboard')
                ->with('info', 'Akun Owner tidak memerlukan absensi.');
        }

        $validated = $request->validate([
            'attendance_location_id' => ['required', 'exists:attendance_locations,id'],
            'type' => ['required', 'in:check_in,check_out'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy_meters' => ['nullable', 'numeric', 'min:0', 'max:5000'],
            'selfie' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'late_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $location = AttendanceLocation::query()->findOrFail((int) $validated['attendance_location_id']);
        $allowed = $this->attendance->locationsForUser($user)->pluck('id');
        if (! $allowed->contains($location->id)) {
            return back()->with('error', 'Lokasi absensi tidak diizinkan untuk akun Anda.');
        }

        try {
            $result = $this->attendance->checkIn(
                $user,
                $location,
                $request->file('selfie'),
                (float) $validated['latitude'],
                (float) $validated['longitude'],
                isset($validated['accuracy_meters']) ? (float) $validated['accuracy_meters'] : null,
                $validated['type'],
                $request->ip(),
                $validated['late_reason'] ?? null,
            );
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        } catch (UnableToWriteFile $e) {
            return back()->with('error', 'Gagal menyimpan foto absensi. Coba lagi beberapa saat.')->withInput();
        }

        $label = $result['record']->typeLabel();
        $message = $label.' tercatat · jarak '.number_format($result['distance'], 0, ',', '.').' m. Menunggu validasi Head.';
        if (! empty($result['is_late'])) {
            $message .= ' · tercatat telat';
        }

        return back()->with('success', $message);
    }

    private function resolveTodayOffLabel(Carbon $today, \App\Models\User $user): ?string
    {
        if ($this->schedule->hasWorkDayOverride($today, $user->id)) {
            return null;
        }

        $settings = $this->schedule->settings();
        $holidayMap = $this->schedule->holidayMapBetween($today->copy(), $today->copy(), $user->id);

        if ($this->schedule->isHoliday($today, $holidayMap)) {
            return (string) $holidayMap->get($today->toDateString(), 'Hari libur');
        }

        $dinasMap = $this->schedule->dinasMapBetween($today->copy(), $today->copy(), $user->id);
        if ($this->schedule->isOnDinas($today, $dinasMap)) {
            return (string) $dinasMap->get($today->toDateString(), 'Perjalanan dinas');
        }

        if (! $this->schedule->isConfiguredWorkDay($today, $settings, $user)) {
            return 'Bukan hari kerja';
        }

        return null;
    }
}
