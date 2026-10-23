<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Services\AttendanceService;
use App\Services\LeaveRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveValidationController extends Controller
{
    public function __construct(
        private readonly LeaveRequestService $leaves,
        private readonly AttendanceService $attendance,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if (! $user->isAttendanceValidator()) {
            return redirect()
                ->route('izin.index')
                ->with('error', 'Anda tidak memiliki antrian validasi izin.');
        }

        return view('izin.validasi', [
            'layout' => $this->attendance->layoutForUser($user),
            'requests' => $this->leaves->pendingForReviewer($user),
            'pendingCount' => $this->leaves->pendingCountForReviewer($user),
        ]);
    }

    public function approve(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        try {
            $this->leaves->approve($leaveRequest, $request->user());
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Pengajuan izin '.$leaveRequest->user?->name.' disetujui.');
    }

    public function reject(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $this->leaves->reject($leaveRequest, $request->user(), $data['rejection_reason']);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Pengajuan izin '.$leaveRequest->user?->name.' ditolak.');
    }
}
