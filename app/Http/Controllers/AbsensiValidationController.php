<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Services\AttendanceService;
use App\Services\AttendanceValidationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AbsensiValidationController extends Controller
{
    public function __construct(
        private readonly AttendanceValidationService $validation,
        private readonly AttendanceService $attendance,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if (! $this->validation->canReview($user)) {
            return redirect()
                ->route('absensi.index')
                ->with('error', 'Anda tidak memiliki akses validasi absensi.');
        }

        return view('absensi.validasi', [
            'layout' => $this->attendance->layoutForUser($user),
            'groups' => $this->validation->pendingDayGroupsForReviewer($user),
            'pendingCount' => $this->validation->pendingCountForReviewer($user),
            'isOwner' => $user->isOwner(),
        ]);
    }

    public function approve(Request $request, AttendanceRecord $attendanceRecord): RedirectResponse
    {
        try {
            $this->validation->approve($attendanceRecord, $request->user());
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        $name = $attendanceRecord->user?->name ?? 'Karyawan';

        return back()->with('success', 'Absensi '.$name.' ('.$attendanceRecord->typeLabel().') disetujui.');
    }

    public function approveDay(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'record_ids' => ['required', 'array', 'min:1'],
            'record_ids.*' => ['integer'],
        ]);

        try {
            $count = $this->validation->approveMany($request->user(), $data['record_ids']);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $count > 1
            ? 'Absen masuk dan pulang disetujui.'
            : 'Absensi disetujui.');
    }

    public function reject(Request $request, AttendanceRecord $attendanceRecord): RedirectResponse
    {
        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $this->validation->reject($attendanceRecord, $request->user(), $data['rejection_reason']);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        $name = $attendanceRecord->user?->name ?? 'Karyawan';

        return back()->with('success', 'Absensi '.$name.' ditolak. Karyawan dapat absen ulang.');
    }

    public function rejectDay(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'record_ids' => ['required', 'array', 'min:1'],
            'record_ids.*' => ['integer'],
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $count = $this->validation->rejectMany(
                $request->user(),
                $data['record_ids'],
                $data['rejection_reason'],
            );
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $count > 1
            ? 'Absen masuk dan pulang ditolak. Karyawan dapat absen ulang.'
            : 'Absensi ditolak. Karyawan dapat absen ulang.');
    }
}
