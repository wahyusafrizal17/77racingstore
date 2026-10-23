<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AttendanceScheduleService;
use App\Support\AdminUserPayload;
use App\Support\CalendarMonth;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceRecapController extends Controller
{
    public function __construct(
        private readonly AttendanceScheduleService $schedule,
    ) {}

    public function index(Request $request): View
    {
        $tz = (string) config('app.timezone');
        $calendarMonth = CalendarMonth::parse($request->query('bulan'), $tz);

        $filters = $request->only(['name', 'section']);
        $rows = $this->schedule->buildMonthlyRecap($calendarMonth, $filters);
        $summary = $this->schedule->summarizeRecapRows($rows);
        $settings = $this->schedule->settings();

        return view('admin.attendance-recap.index', [
            'calendarMonth' => $calendarMonth,
            'rows' => $rows,
            'summary' => $summary,
            'filters' => $filters,
            'settings' => $settings,
            'adminDivisiOptions' => AdminUserPayload::divisiOptions(),
        ]);
    }
}
