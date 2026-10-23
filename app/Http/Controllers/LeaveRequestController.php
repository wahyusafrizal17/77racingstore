<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Services\AttendanceScheduleService;
use App\Services\AttendanceService;
use App\Services\LeaveRequestService;
use App\Support\LeaveApprovalRouting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LeaveRequestController extends Controller
{
    public function __construct(
        private readonly LeaveRequestService $leaves,
        private readonly AttendanceService $attendance,
        private readonly AttendanceScheduleService $schedule,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if (! $this->leaves->canSubmitLeave($user)) {
            return redirect()->route('absensi.index')->with('error', 'Akun Anda tidak dapat mengajukan izin.');
        }

        $tz = (string) config('app.timezone');
        $rangeStart = now($tz)->startOfDay();
        $rangeEnd = $rangeStart->copy()->addMonths(14);
        $approval = $this->leaves->approvalMetaFor($user);
        $statusCounts = LeaveRequest::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [
                LeaveRequest::STATUS_PENDING,
                LeaveRequest::STATUS_APPROVED,
                LeaveRequest::STATUS_REJECTED,
            ])
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return view('izin.index', [
            'layout' => $this->attendance->layoutForUser($user),
            'user' => $user,
            'hasValidator' => $approval['hasValidator'],
            'approverSummary' => $approval['approverSummary'],
            'requests' => $this->leaves->requestsForUser($user),
            'typeOptions' => LeaveRequest::typeOptionsForUser($user),
            'leaveApplicantGroup' => $user->section === 'speedshop' ? 'speedshop' : (
                LeaveRequest::isModifikasiOriApplicant($user) ? 'modifikasi_ori' : 'other'
            ),
            'leaveDatePicker' => [
                'offDays' => $this->schedule->scheduledOffDatesForUser($user, $rangeStart, $rangeEnd),
                'minAdvance' => [
                    LeaveRequest::TYPE_IZIN => 3,
                    LeaveRequest::TYPE_SAKIT => 0,
                    LeaveRequest::TYPE_CUTI => 3,
                    LeaveRequest::TYPE_WFH => 0,
                    LeaveRequest::TYPE_MASUK_LIBUR => 1,
                    LeaveRequest::TYPE_SETENGAH_MASUK => 0,
                    LeaveRequest::TYPE_SETENGAH_PULANG => 0,
                ],
                'halfDayTypes' => LeaveRequest::halfDayTypes(),
                'masukLiburType' => LeaveRequest::TYPE_MASUK_LIBUR,
                'wfhType' => LeaveRequest::TYPE_WFH,
            ],
            'stats' => [
                'pending' => (int) ($statusCounts[LeaveRequest::STATUS_PENDING] ?? 0),
                'approved' => (int) ($statusCounts[LeaveRequest::STATUS_APPROVED] ?? 0),
                'rejected' => (int) ($statusCounts[LeaveRequest::STATUS_REJECTED] ?? 0),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'type' => ['required', 'in:'.implode(',', array_keys(LeaveRequest::typeOptionsForUser($user)))],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'max:1000'],
            'surat_dokter' => [
                Rule::requiredIf(fn () => LeaveRequest::requiresSuratDokter(
                    $request->input('type'),
                    $request->input('start_date'),
                    $request->input('end_date'),
                )),
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp,pdf',
                'max:5120',
            ],
        ], [
            'surat_dokter.required' => 'Untuk izin sakit lebih dari 1 hari, surat keterangan dokter wajib diunggah.',
            'surat_dokter.mimes' => 'Surat dokter harus berupa JPG, PNG, WEBP, atau PDF.',
            'surat_dokter.max' => 'Ukuran surat dokter maksimal 5MB.',
        ]);

        try {
            $this->leaves->create($user, $data, $request->file('surat_dokter'));
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        $success = LeaveApprovalRouting::isHeadOnlyApprovalStaf($user)
            ? 'Pengajuan izin berhasil dikirim. Menunggu tanda tangan Head Marketing.'
            : 'Pengajuan izin berhasil dikirim. Menunggu tanda tangan Head & Manager.';

        return redirect()
            ->route('izin.index')
            ->with('success', $success);
    }

    public function show(Request $request, LeaveRequest $leaveRequest): View|RedirectResponse
    {
        $user = $request->user();

        abort_unless($this->leaves->canViewLeave($leaveRequest, $user), 404);

        $leave = $this->leaves->ensurePemohonSignature(
            $this->leaves->healOptionalOwnerAcknowledgment(
                $this->leaves->healHeadOnlyMarketingLeave(
                    $leaveRequest->load(['user', 'reviewer', 'assignedValidator', 'headApprover', 'managerApprover', 'ownerApprover'])
                )
            )
        );

        return view('izin.show', [
            'layout' => $this->attendance->layoutForUser($user),
            'leave' => $leave,
            'canReview' => $this->leaves->canReview($leave, $user),
            'canSignHead' => $this->leaves->canSignHead($leave, $user),
            'canSignManager' => $this->leaves->canSignManager($leave, $user),
            'canSignOwner' => $this->leaves->canSignOwner($leave, $user),
        ]);
    }

    public function sign(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $validated = $request->validate([
            'aksi' => ['required', 'in:head,manager,owner'],
        ]);

        try {
            $this->leaves->sign($leaveRequest, $request->user(), $validated['aksi']);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Tanda tangan digital (QR) berhasil dicatat.');
    }

    public function cancel(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        try {
            $this->leaves->cancel($leaveRequest, $request->user());
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('izin.index')
            ->with('success', 'Pengajuan izin dibatalkan.');
    }
}
