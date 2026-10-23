<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceHoliday;
use App\Models\AttendanceLocation;
use App\Models\Speedshop;
use App\Models\User;
use App\Services\AttendanceScheduleService;
use App\Support\Admin\AttendanceScheduleRoleGrouping;
use App\Support\CalendarMonth;
use Carbon\Carbon;
use Database\Seeders\AttendanceLocationSeeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AttendanceScheduleController extends Controller
{
    public function __construct(
        private readonly AttendanceScheduleService $schedule,
    ) {}

    public function index(Request $request): View
    {
        $tz = (string) config('app.timezone');
        $calendarMonth = CalendarMonth::parse($request->query('bulan'), $tz);

        if (AttendanceLocation::query()->count() === 0) {
            (new AttendanceLocationSeeder)->run();
        }

        $codeOrder = [
            AttendanceLocation::CODE_WAREHOUSE,
            AttendanceLocation::CODE_BANDUNG,
            AttendanceLocation::CODE_BEKASI,
            AttendanceLocation::CODE_DEPOK,
            AttendanceLocation::CODE_BOGOR,
        ];

        $scheduleLocations = AttendanceLocation::query()->with('weekdayHours')->get()->sortBy(function (AttendanceLocation $loc) use ($codeOrder) {
            $idx = array_search($loc->code, $codeOrder, true);

            return $idx === false ? 999 : $idx;
        })->values();

        $allEmployees = User::query()
            ->adminKaryawan()
            ->get(['id', 'name', 'section', 'role', 'attendance_location_id', 'speedshop_id', 'warehouse_id']);

        $locationRows = $scheduleLocations->map(function (AttendanceLocation $loc) use ($allEmployees) {
            $employees = $this->employeesForLocation($allEmployees, $loc);
            $weekdayHours = $this->schedule->weekdayHoursPayload($loc);
            $uniquePairs = collect($weekdayHours)
                ->map(fn (array $row) => $row['check_in'].'–'.$row['check_out'])
                ->unique()
                ->values();

            return [
                'location' => $loc,
                'employee_count' => $employees->count(),
                'weekday_hours' => $weekdayHours,
                'hours_summary' => $uniquePairs->count() === 1
                    ? $uniquePairs->first()
                    : 'Berbeda per hari',
            ];
        });

        return view('admin.attendance-schedule.index', [
            'calendarMonth' => $calendarMonth,
            'locationRows' => $locationRows,
            'canEdit' => auth()->user()?->canManageHrAdminSettings() ?? false,
        ]);
    }

    public function workHours(Request $request): View
    {
        abort_unless($request->integer('location_id'), 404, 'Lokasi PSA wajib dipilih.');

        $data = $this->scheduleLocationViewData($request);
        abort_unless($data['selectedLocation'], 404);

        return view('admin.attendance-schedule.work-hours', $data);
    }

    public function holidays(Request $request): View
    {
        abort_unless($request->integer('location_id'), 404, 'Lokasi PSA wajib dipilih.');

        $data = $this->scheduleLocationViewData($request);
        $data['focusUserId'] = $request->integer('user_id') ?: null;

        $data['displayGroups'] = collect($data['employeeGroups'])
            ->map(function (array $group) use ($data) {
                $employees = $group['employees'];
                if ($data['focusUserId']) {
                    $employees = $employees->filter(
                        fn (User $u) => (int) $u->id === (int) $data['focusUserId']
                    )->values();
                }

                return [
                    'label' => $group['label'],
                    'employees' => $employees,
                ];
            })
            ->filter(fn (array $group) => $group['employees']->isNotEmpty())
            ->values();

        $savedHolidayIndex = [];
        foreach ($data['existingHolidaysPayload'] as $h) {
            $savedHolidayIndex[(int) $h['user_id']][$h['date']] = $h;
        }
        $data['savedHolidayIndex'] = $savedHolidayIndex;

        $monthStart = $data['calendarMonth']->copy()->startOfMonth();
        $monthEnd = $data['calendarMonth']->copy()->endOfMonth();
        $employeeIds = $data['locationEmployees']->pluck('id')->map(fn ($id) => (int) $id)->all();
        $dinasMaps = $this->schedule->dinasMapsForUsers($monthStart, $monthEnd, $employeeIds);

        $savedDinasIndex = [];
        $existingDinasPayload = [];
        foreach ($dinasMaps as $userId => $dateLabels) {
            foreach ($dateLabels as $date => $label) {
                $uid = (int) $userId;
                $savedDinasIndex[$uid][$date] = (string) $label;
                $existingDinasPayload[] = [
                    'user_id' => $uid,
                    'date' => $date,
                    'label' => (string) $label,
                ];
            }
        }
        $data['savedDinasIndex'] = $savedDinasIndex;
        $data['existingDinasPayload'] = $existingDinasPayload;

        return view('admin.attendance-schedule.holidays', $data);
    }

    /** @return array<string, mixed> */
    private function scheduleLocationViewData(Request $request): array
    {
        $tz = (string) config('app.timezone');
        $calendarMonth = CalendarMonth::parse($request->query('bulan'), $tz);

        $settings = $this->schedule->settings();

        if (AttendanceLocation::query()->count() === 0) {
            (new AttendanceLocationSeeder)->run();
        }

        $scheduleLocations = AttendanceLocation::query()->orderBy('id')->get();
        $locationId = $request->integer('location_id') ?: (int) ($scheduleLocations->first()?->id ?? 0);
        $selectedLocation = $scheduleLocations->firstWhere('id', $locationId) ?? $scheduleLocations->first();
        $locationId = (int) ($selectedLocation?->id ?? 0);

        $monthStart = $calendarMonth->copy()->startOfMonth()->toDateString();
        $monthEnd = $calendarMonth->copy()->endOfMonth()->toDateString();

        $allEmployees = User::query()
            ->adminKaryawan()
            ->with(['mekanik.mekanikLevel', 'attendanceLocation:id,name'])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'section', 'role', 'attendance_location_id', 'speedshop_id', 'warehouse_id']);

        $locationEmployees = $this->employeesForLocation($allEmployees, $selectedLocation);
        $locationEmployeeIds = $locationEmployees->pluck('id')->all();

        $holidays = AttendanceHoliday::query()
            ->with('user:id,name,email,section,role')
            ->whereBetween('holiday_date', [$monthStart, $monthEnd])
            ->whereNotNull('user_id')
            ->when($locationEmployeeIds !== [], fn ($q) => $q->whereIn('user_id', $locationEmployeeIds))
            ->when($locationEmployeeIds === [], fn ($q) => $q->whereRaw('1 = 0'))
            ->orderBy('holiday_date')
            ->orderBy('user_id')
            ->get();

        $holidayCounts = AttendanceHoliday::query()
            ->whereBetween('holiday_date', [$monthStart, $monthEnd])
            ->whereNotNull('user_id')
            ->when($locationEmployeeIds !== [], fn ($q) => $q->whereIn('user_id', $locationEmployeeIds))
            ->selectRaw('user_id, COUNT(*) as total')
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

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

        $existingHolidaysPayload = $holidays->map(fn (AttendanceHoliday $h) => [
            'id' => $h->id,
            'user_id' => (int) $h->user_id,
            'date' => $h->holiday_date->toDateString(),
            'name' => (string) ($h->name ?: 'Libur'),
            'user_name' => (string) ($h->user?->name ?? '—'),
        ])->values()->all();

        return [
            'settings' => $settings,
            'scheduleLocations' => $scheduleLocations,
            'selectedLocation' => $selectedLocation,
            'locationId' => $locationId,
            'holidays' => $holidays,
            'locationEmployees' => $locationEmployees,
            'employeeGroups' => AttendanceScheduleRoleGrouping::grouped($locationEmployees),
            'employeeWorkDays' => $locationEmployees->mapWithKeys(
                fn (User $employee) => [
                    $employee->id => $this->schedule->workDayNumbersForUser($employee),
                ]
            ),
            'holidayCounts' => $holidayCounts,
            'calendarMonth' => $calendarMonth,
            'monthDays' => $monthDays,
            'existingHolidaysPayload' => $existingHolidaysPayload,
            'canEdit' => auth()->user()?->canManageHrAdminSettings() ?? false,
            'locationWeekdayHours' => $this->schedule->weekdayHoursPayload($selectedLocation),
        ];
    }

    public function updateLocation(Request $request, AttendanceLocation $attendanceLocation): RedirectResponse
    {
        abort_unless(auth()->user()?->canManageHrAdminSettings(), 403, 'Hanya pengelola panel HR yang dapat mengatur jadwal absensi.');

        $validated = $request->validate([
            'days' => ['required', 'array'],
            'days.*.check_in_time' => ['required', 'date_format:H:i'],
            'days.*.check_out_time' => ['required', 'date_format:H:i'],
            'days.*.check_in_window_start' => ['required', 'date_format:H:i'],
            'days.*.check_in_window_end' => ['required', 'date_format:H:i'],
            'days.*.check_out_window_start' => ['required', 'date_format:H:i'],
            'days.*.check_out_window_end' => ['required', 'date_format:H:i'],
        ]);

        $days = [];
        foreach ($validated['days'] as $day => $times) {
            $day = (int) $day;
            if ($day < 1 || $day > 7) {
                continue;
            }
            $label = \App\Models\AttendanceLocationWeekdayHour::DAY_LABELS[$day] ?? 'Hari '.$day;
            if (strcmp($times['check_out_time'], $times['check_in_time']) <= 0) {
                return back()
                    ->withInput()
                    ->withErrors([
                        "days.$day.check_out_time" => 'Jam pulang '.$label.' harus setelah jam masuk.',
                    ]);
            }
            if (strcmp($times['check_in_window_end'], $times['check_in_window_start']) <= 0) {
                return back()
                    ->withInput()
                    ->withErrors([
                        "days.$day.check_in_window_end" => 'Batas absen masuk '.$label.' tidak valid (akhir harus setelah awal).',
                    ]);
            }
            if (strcmp($times['check_out_window_end'], $times['check_out_window_start']) <= 0) {
                return back()
                    ->withInput()
                    ->withErrors([
                        "days.$day.check_out_window_end" => 'Batas absen pulang '.$label.' tidak valid (akhir harus setelah awal).',
                    ]);
            }
            $days[$day] = [
                'check_in_time' => $times['check_in_time'],
                'check_out_time' => $times['check_out_time'],
                'check_in_window_start' => $times['check_in_window_start'],
                'check_in_window_end' => $times['check_in_window_end'],
                'check_out_window_start' => $times['check_out_window_start'],
                'check_out_window_end' => $times['check_out_window_end'],
            ];
        }

        if (count($days) !== 7) {
            return back()->withInput()->withErrors(['days' => 'Lengkapi jam kerja untuk Senin–Minggu.']);
        }

        $this->schedule->syncLocationWeekdayHours($attendanceLocation, $days);

        $locationId = (int) $attendanceLocation->id;
        $returnTo = (string) $request->input('return_to', 'index');

        $redirectRoute = $returnTo === 'work-hours'
            ? route('admin.attendance-schedule.work-hours', array_filter([
                'location_id' => $locationId,
                'bulan' => $request->input('bulan'),
            ]))
            : route('admin.attendance-schedule.index', array_filter([
                'bulan' => $request->input('bulan'),
            ]));

        return redirect($redirectRoute)
            ->with('success', 'Jam kerja per hari «'.$attendanceLocation->displayName().'» berhasil disimpan.');
    }

    /**
     * @param  Collection<int, User>  $employees
     * @return Collection<int, User>
     */
    private function employeesForLocation(Collection $employees, ?AttendanceLocation $location): Collection
    {
        if (! $location) {
            return collect();
        }

        $locationId = (int) $location->id;
        $outletSpeedshopId = ($location->locatable_type === Speedshop::class && $location->locatable_id)
            ? (int) $location->locatable_id
            : null;

        return $employees->filter(function (User $user) use ($locationId, $outletSpeedshopId) {
            if ((int) ($user->attendance_location_id ?? 0) === $locationId) {
                return true;
            }

            if ($outletSpeedshopId !== null && $user->section === 'speedshop') {
                $userOutlet = (int) ($user->activeSpeedshopId() ?? $user->speedshop_id ?? 0);
                if ($userOutlet === $outletSpeedshopId) {
                    return true;
                }
            }

            $resolved = $this->schedule->resolveLocationForUser($user);

            return $resolved && (int) $resolved->id === $locationId;
        })->values();
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->canManageHrAdminSettings(), 403, 'Hanya pengelola panel HR yang dapat mengatur jadwal absensi.');

        $validated = $request->validate([
            'check_in_time' => ['required', 'date_format:H:i'],
            'check_out_time' => ['required', 'date_format:H:i', 'after:check_in_time'],
        ]);

        $this->schedule->updateSettings([
            'check_in_time' => $validated['check_in_time'].':00',
            'check_out_time' => $validated['check_out_time'].':00',
        ]);

        return back()->with('success', 'Jam kerja default (fallback) berhasil disimpan.');
    }

    public function storeHoliday(Request $request): RedirectResponse|JsonResponse
    {
        abort_unless(auth()->user()?->canManageHrAdminSettings(), 403, 'Hanya pengelola panel HR yang dapat menambah hari libur.');

        $validated = $request->validate([
            'user_ids' => ['nullable', 'array', 'min:1'],
            'user_ids.*' => ['integer', Rule::exists('users', 'id')],
            'holiday_dates' => ['nullable', 'array', 'min:1'],
            'holiday_dates.*' => ['date'],
            'holiday_pairs' => ['nullable', 'string'],
            'name' => ['nullable', 'string', 'max:120'],
            'bulan' => ['nullable', 'date_format:Y-m'],
            'location_id' => ['nullable', 'integer', Rule::exists('attendance_locations', 'id')],
            'user_id' => ['nullable', 'integer'],
        ]);

        $pairs = [];
        if (! empty($validated['holiday_pairs'])) {
            $decoded = json_decode((string) $validated['holiday_pairs'], true);
            if (is_array($decoded)) {
                foreach ($decoded as $row) {
                    if (! is_array($row)) {
                        continue;
                    }
                    $uid = (int) ($row['user_id'] ?? 0);
                    $date = isset($row['date']) ? Carbon::parse($row['date'])->toDateString() : null;
                    if ($uid > 0 && $date) {
                        $pairs[] = ['user_id' => $uid, 'date' => $date];
                    }
                }
            }
        }

        if ($pairs === []) {
            $request->validate([
                'user_ids' => ['required', 'array', 'min:1'],
                'holiday_dates' => ['required', 'array', 'min:1'],
            ]);
            $userIds = collect($validated['user_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
            foreach ($userIds as $userId) {
                foreach ($validated['holiday_dates'] ?? [] as $dateRaw) {
                    $pairs[] = [
                        'user_id' => $userId,
                        'date' => Carbon::parse($dateRaw)->toDateString(),
                    ];
                }
            }
        }

        $userIds = collect($pairs)->pluck('user_id')->unique()->values();
        $users = User::query()->whereIn('id', $userIds)->get()->keyBy('id');
        $created = 0;
        $savedRows = [];

        foreach ($pairs as $pair) {
            $userId = (int) $pair['user_id'];
            $date = (string) $pair['date'];
            $employee = $users->get($userId);
            if (! $employee || ! $this->schedule->isConfiguredWorkDay(Carbon::parse($date), null, $employee)) {
                continue;
            }

            $row = AttendanceHoliday::query()->updateOrCreate(
                [
                    'user_id' => $userId,
                    'holiday_date' => $date,
                ],
                [
                    'name' => $validated['name'] ?? null,
                    'created_by' => $request->user()->id,
                ],
            );
            $created++;
            $savedRows[] = [
                'id' => (int) $row->id,
                'user_id' => $userId,
                'date' => $date,
                'name' => (string) ($row->name ?: 'Libur'),
                'user_name' => (string) $employee->name,
            ];
        }

        $query = array_filter([
            'bulan' => $validated['bulan'] ?? null,
            'location_id' => $request->integer('location_id') ?: null,
            'user_id' => $request->integer('user_id') ?: null,
        ]);

        if ($request->expectsJson()) {
            if ($created === 0) {
                return response()->json(['message' => 'Tanggal bukan hari kerja atau tidak valid.'], 422);
            }

            return response()->json([
                'success' => true,
                'holidays' => $savedRows,
            ]);
        }

        return redirect()
            ->route('admin.attendance-schedule.holidays', $query)
            ->with('success', $created === 1
                ? 'Hari libur karyawan berhasil disimpan.'
                : "Berhasil menyimpan {$created} jadwal libur.");
    }

    public function destroyHoliday(Request $request, AttendanceHoliday $attendanceHoliday): RedirectResponse|JsonResponse
    {
        abort_unless(auth()->user()?->canManageHrAdminSettings(), 403, 'Hanya pengelola panel HR yang dapat menghapus hari libur.');

        $bulan = $request->input('bulan', $request->query('bulan'));
        $holidayId = (int) $attendanceHoliday->id;
        $attendanceHoliday->delete();

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'id' => $holidayId]);
        }

        return redirect()
            ->route('admin.attendance-schedule.holidays', array_filter([
                'bulan' => $bulan,
                'location_id' => $request->integer('location_id') ?: null,
                'user_id' => $request->integer('user_id') ?: null,
            ]))
            ->with('success', 'Hari libur dihapus.');
    }
}
