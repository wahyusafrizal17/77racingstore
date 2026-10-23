<?php

namespace App\Http\Controllers;

use App\Exports\AbsensiRecapExport;
use App\Models\Speedshop;
use App\Models\User;
use App\Services\AttendanceScheduleService;
use App\Services\AttendanceService;
use App\Services\AttendanceValidationService;
use App\Support\Admin\AttendanceScheduleRoleGrouping;
use App\Support\AttendanceValidationRouting;
use App\Support\CalendarMonth;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class AbsensiRecapController extends Controller
{
    public function __construct(
        private readonly AttendanceValidationService $validation,
        private readonly AttendanceScheduleService $schedule,
        private readonly AttendanceService $attendance,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $denied = $this->denyUnlessCanReview($request);
        if ($denied) {
            return $denied;
        }

        $data = $this->resolveRecapData($request);

        return view('absensi.rekap', $data + [
            'layout' => $this->attendance->layoutForUser($request->user()),
        ]);
    }

    public function exportExcel(Request $request): BinaryFileResponse|RedirectResponse
    {
        $denied = $this->denyUnlessCanReview($request);
        if ($denied) {
            return $denied;
        }

        $data = $this->resolveRecapData($request);
        $filename = 'rekap-absensi-'.$data['calendarMonth']->format('Y-m').'-'.now()->format('Ymd-His').'.xlsx';

        return Excel::download(new AbsensiRecapExport(
            $data['calendarMonth'],
            $data['displayGroups'],
            $data['monthDays'],
            $data['cells'],
            $data['summary'],
            $data['scopeLabel'] ?? 'Bawahan di bawah tanggung jawab Anda',
        ), $filename);
    }

    public function exportPdf(Request $request): Response|RedirectResponse
    {
        $denied = $this->denyUnlessCanReview($request);
        if ($denied) {
            return $denied;
        }

        $data = $this->resolveRecapData($request);
        $filename = 'rekap-absensi-'.$data['calendarMonth']->format('Y-m').'.pdf';

        return Pdf::loadView('absensi.rekap-pdf', $data + [
            'printedAt' => now(),
        ])->setPaper('a4', 'landscape')->stream($filename);
    }

    private function denyUnlessCanReview(Request $request): ?RedirectResponse
    {
        $user = $request->user();
        if (! $user || ! $user->canSeeAttendanceRecap()) {
            return redirect()
                ->route('absensi.index')
                ->with('error', 'Anda tidak memiliki akses rekap absensi.');
        }

        return null;
    }

    /**
     * @return array{
     *     calendarMonth: Carbon,
     *     displayGroups: Collection,
     *     monthDays: list<array<string, mixed>>,
     *     cells: array<int, array<string, array{code: string, class: string, title: string}>>,
     *     summary: array{present: int, late: int, absent: int, off_days: int, pending: int},
     *     nameFilter: string,
     *     isOwner: bool,
     *     seesAllEmployees: bool
     * }
     */
    private function resolveRecapData(Request $request): array
    {
        $user = $request->user();
        $tz = (string) config('app.timezone');
        $calendarMonth = CalendarMonth::parse($request->query('bulan'), $tz);

        $nameFilter = trim((string) $request->query('name', ''));
        $seesAllEmployees = $user->isOwner() || $user->isModifikasiOriHrAdmin();
        $cabangOptions = $this->speedshopRecapCabangOptions($user);
        $cabangFilter = $this->speedshopRecapCabangFilter($request, $user, $cabangOptions);
        $employees = $this->employeesForReviewer($user, $nameFilter, $seesAllEmployees, $cabangFilter);
        $grid = $this->schedule->buildMonthlyCalendarRecap($calendarMonth, $employees);

        $scopeLabel = 'Bawahan di bawah tanggung jawab Anda';
        if ($seesAllEmployees) {
            $scopeLabel = 'Semua karyawan';
        } elseif ($cabangFilter !== null) {
            $scopeLabel = $cabangFilter === [] || count($cabangFilter) > 1
                ? 'Semua cabang Speedshop'
                : (Speedshop::query()->whereKey($cabangFilter[0] ?? 0)->value('nama') ?: 'Cabang Speedshop');
        }

        return [
            'calendarMonth' => $calendarMonth,
            'displayGroups' => collect(AttendanceScheduleRoleGrouping::grouped($employees)),
            'monthDays' => $grid['monthDays'],
            'cells' => $grid['cells'],
            'summary' => $grid['summary'],
            'nameFilter' => $nameFilter,
            'cabangFilter' => $cabangFilter === null
                ? null
                : (count($cabangFilter) === 1 ? (string) $cabangFilter[0] : 'all'),
            'cabangOptions' => $cabangOptions,
            'scopeLabel' => $scopeLabel,
            'isOwner' => $user->isOwner(),
            'seesAllEmployees' => $seesAllEmployees,
        ];
    }

    /**
     * @return Collection<int, Speedshop>
     */
    private function speedshopRecapCabangOptions(User $reviewer): Collection
    {
        if (! $this->usesSpeedshopRecapCabangScope($reviewer)) {
            return collect();
        }

        $ids = $reviewer->activeSpeedshopIds();
        if ($ids === []) {
            return collect();
        }

        return Speedshop::query()
            ->whereIn('id', $ids)
            ->orderBy('nama')
            ->get(['id', 'nama']);
    }

    /**
     * @param  Collection<int, Speedshop>  $options
     * @return list<int>|null
     */
    private function speedshopRecapCabangFilter(Request $request, User $reviewer, Collection $options): ?array
    {
        if ($options->isEmpty()) {
            return $this->usesSpeedshopRecapCabangScope($reviewer)
                ? $reviewer->reportSpeedshopFilterIds()
                : null;
        }

        $allowed = $options->pluck('id')->map(fn ($id) => (int) $id)->values()->all();

        if (! $request->query->has('cabang')) {
            $fromNavbar = array_values(array_intersect(
                array_map('intval', $reviewer->reportSpeedshopFilterIds()),
                $allowed,
            ));

            return $fromNavbar !== [] ? $fromNavbar : $allowed;
        }

        $raw = $request->query('cabang');
        if ($raw === null || $raw === '' || $raw === 'all') {
            return $allowed;
        }

        $id = (int) $raw;

        return in_array($id, $allowed, true) ? [$id] : $allowed;
    }

    private function usesSpeedshopRecapCabangScope(User $reviewer): bool
    {
        return $reviewer->isSpeedshop()
            && ($reviewer->isManager() || $reviewer->isSpeedshopAccounting());
    }

    /**
     * @param  list<int>|null  $speedshopIds
     * @return Collection<int, User>
     */
    private function employeesForReviewer(
        User $reviewer,
        string $nameFilter = '',
        ?bool $seesAllEmployees = null,
        ?array $speedshopIds = null,
    ): Collection {
        $query = User::query()
            ->adminKaryawan()
            ->with(['mekanik.mekanikLevel'])
            ->orderBy('name')
            ->select(['id', 'name', 'email', 'section', 'role', 'speedshop_id']);

        $seesAllEmployees ??= $reviewer->isOwner() || $reviewer->isModifikasiOriHrAdmin();

        if ($speedshopIds !== null) {
            if ($speedshopIds === []) {
                return collect();
            }

            $query->where('section', 'speedshop')
                ->where(function ($inner) use ($speedshopIds) {
                    $inner->whereIn('speedshop_id', $speedshopIds)
                        ->orWhere(function ($fallback) use ($speedshopIds) {
                            $fallback->whereNull('speedshop_id')
                                ->whereHas(
                                    'assignedSpeedshops',
                                    fn ($assigned) => $assigned->whereIn('speedshops.id', $speedshopIds)
                                );
                        });
                });
        } elseif (! $seesAllEmployees) {
            $ids = AttendanceValidationRouting::subordinateIdsFor($reviewer);
            if ($ids === []) {
                return collect();
            }
            $query->whereIn('id', $ids);
        }

        if ($nameFilter !== '') {
            $term = '%'.addcslashes($nameFilter, '%_\\').'%';
            $query->where('name', 'like', $term);
        }

        return $query->get();
    }
}
