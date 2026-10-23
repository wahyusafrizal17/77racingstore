<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\AttendanceHoliday;
use App\Models\AttendanceWorkDayOverride;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Support\LeaveApprovalRouting;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LeaveRequestService
{
    public function __construct(
        private readonly AttendanceScheduleService $schedule,
    ) {}

    public function canSubmitLeave(User $user): bool
    {
        return $user->isVisibleInAdminKaryawan() && ! $user->isOwner();
    }

    public function hasAssignedValidator(User $user): bool
    {
        $resolved = LeaveApprovalRouting::resolveFor($user);

        return $resolved['error'] === null && ($resolved['manager'] !== null || $resolved['head'] !== null);
    }

    /** @return list<string> */
    public function approverSummaryFor(User $user): array
    {
        return LeaveApprovalRouting::describeFor($user);
    }

    /**
     * Resolve approval routing once for the izin index page.
     *
     * @return array{hasValidator: bool, approverSummary: list<string>}
     */
    public function approvalMetaFor(User $user): array
    {
        $resolved = LeaveApprovalRouting::resolveFor($user);

        return [
            'hasValidator' => $resolved['error'] === null && ($resolved['manager'] !== null || $resolved['head'] !== null),
            'approverSummary' => LeaveApprovalRouting::describeResolved($user, $resolved),
        ];
    }

    /**
     * @return LengthAwarePaginator<LeaveRequest>
     */
    public function requestsForUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return LeaveRequest::query()
            ->where('user_id', $user->id)
            ->with(['reviewer:id,name', 'assignedValidator:id,name', 'headApprover:id,name', 'managerApprover:id,name', 'ownerApprover:id,name'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * @return LengthAwarePaginator<LeaveRequest>
     */
    public function pendingForReviewer(User $reviewer, int $perPage = 15): LengthAwarePaginator
    {
        $this->healPendingHeadOnlyMarketingLeaves();
        $this->healPendingLeavesWaitingOnlyForOwner();

        return $this->validationQueueQuery($reviewer)
            ->paginate($perPage);
    }

    public function pendingCountForReviewer(User $reviewer): int
    {
        $this->healPendingHeadOnlyMarketingLeaves();
        $this->healPendingLeavesWaitingOnlyForOwner();

        return (int) $this->pendingActionQuery($reviewer)->count();
    }

    /** Selesaikan / potong alur pengajuan Staf Marketing yang masih memakai rantai lama. */
    private function healPendingHeadOnlyMarketingLeaves(): void
    {
        LeaveRequest::query()
            ->where('status', LeaveRequest::STATUS_PENDING)
            ->whereHas('user', fn (Builder $q) => $q->where('role', User::ROLE_MODIFIKASI_STAF_MARKETING))
            ->with(['user', 'headApprover', 'managerApprover', 'ownerApprover'])
            ->orderBy('id')
            ->each(fn (LeaveRequest $leave) => $this->healHeadOnlyMarketingLeave($leave));
    }

    /** Selesaikan pengajuan yang hanya menggantung di TTD "Diketahui" Owner. */
    private function healPendingLeavesWaitingOnlyForOwner(): void
    {
        LeaveRequest::query()
            ->where('status', LeaveRequest::STATUS_PENDING)
            ->whereNotNull('owner_approver_id')
            ->whereNull('owner_signed_at')
            ->where(function (Builder $q) {
                $q->whereNull('head_approver_id')->orWhereNotNull('head_signed_at');
            })
            ->where(function (Builder $q) {
                $q->whereNull('manager_approver_id')->orWhereNotNull('manager_signed_at');
            })
            ->with(['user', 'headApprover', 'managerApprover', 'ownerApprover'])
            ->orderBy('id')
            ->each(fn (LeaveRequest $leave) => $this->healOptionalOwnerAcknowledgment($leave));
    }

    public function healOptionalOwnerAcknowledgment(LeaveRequest $leave): LeaveRequest
    {
        if (! $leave->isPending() || ! $leave->requiredSignaturesComplete()) {
            return $leave;
        }

        $reviewer = $leave->managerApprover ?? $leave->headApprover ?? $leave->user;
        if (! $reviewer) {
            return $leave;
        }

        $this->finalizeApproval($leave, $reviewer);

        return $leave->fresh(['user', 'reviewer', 'headApprover', 'managerApprover', 'ownerApprover']);
    }

    public function needsReviewerAction(LeaveRequest $leave, User $reviewer): bool
    {
        return $leave->isPending() && $this->canReview($leave, $reviewer);
    }

    public function canViewLeave(LeaveRequest $leave, User $user): bool
    {
        if ((int) $leave->user_id === (int) $user->id) {
            return true;
        }

        if ($this->canReview($leave, $user)
            || $this->canSignHead($leave, $user)
            || $this->canSignManager($leave, $user)
            || $this->canSignOwner($leave, $user)) {
            return true;
        }

        return $this->reviewerInvolvedInLeave($leave, $user);
    }

    public function reviewerInvolvedInLeave(LeaveRequest $leave, User $reviewer): bool
    {
        $reviewerId = (int) $reviewer->id;

        if ((int) $leave->head_approver_id === $reviewerId) {
            return true;
        }

        if ((int) $leave->manager_approver_id === $reviewerId) {
            return true;
        }

        if ((int) $leave->owner_approver_id === $reviewerId) {
            return true;
        }

        if ((int) $leave->reviewed_by === $reviewerId) {
            return true;
        }

        if ($leave->head_approver_id === null && $leave->manager_approver_id === null) {
            return $this->legacyCanReview($leave, $reviewer);
        }

        return false;
    }

    public function create(User $user, array $data, ?\Illuminate\Http\UploadedFile $suratDokter = null): LeaveRequest
    {
        if (! $this->canSubmitLeave($user)) {
            throw new \InvalidArgumentException('Akun Anda tidak dapat mengajukan izin.');
        }

        $routing = LeaveApprovalRouting::resolveFor($user);
        if ($routing['error'] !== null) {
            throw new \InvalidArgumentException($routing['error']);
        }

        $manager = $routing['manager'];
        $head = $routing['head'];
        if (! $manager && ! $head) {
            throw new \InvalidArgumentException('Penyetuju izin belum ditemukan. Hubungi Head HR.');
        }

        $start = Carbon::parse($data['start_date'])->startOfDay();
        $end = Carbon::parse($data['end_date'])->startOfDay();

        if ($end->lt($start)) {
            throw new \InvalidArgumentException('Tanggal selesai tidak boleh sebelum tanggal mulai.');
        }

        if ($start->lt(now()->startOfDay()->subDays(7))) {
            throw new \InvalidArgumentException('Pengajuan izin tidak boleh lebih dari 7 hari ke belakang.');
        }

        $reason = trim((string) ($data['reason'] ?? ''));
        if ($reason === '') {
            throw new \InvalidArgumentException('Alasan izin wajib diisi.');
        }

        $type = (string) ($data['type'] ?? LeaveRequest::TYPE_CUTI);
        $allowedTypes = LeaveRequest::typeOptionsForUser($user);
        if (! array_key_exists($type, $allowedTypes)) {
            throw new \InvalidArgumentException('Jenis izin tidak valid.');
        }

        if ($this->hasOverlap($user, $start, $end)) {
            throw new \InvalidArgumentException('Tanggal izin bentrok dengan pengajuan lain yang masih pending atau sudah disetujui.');
        }

        if ($type === LeaveRequest::TYPE_MASUK_LIBUR) {
            $this->assertMasukLiburDates($user, $start, $end);
        }

        if (in_array($type, [
            LeaveRequest::TYPE_IZIN,
            LeaveRequest::TYPE_SAKIT,
            LeaveRequest::TYPE_CUTI,
            LeaveRequest::TYPE_WFH,
            LeaveRequest::TYPE_SETENGAH_MASUK,
            LeaveRequest::TYPE_SETENGAH_PULANG,
        ], true)) {
            $this->assertNoOffDays($user, $start, $end, $type);
        }

        if (LeaveRequest::requiresSingleLeaveDate($type)) {
            if (! $start->equalTo($end)) {
                throw new \InvalidArgumentException('Pengajuan ini hanya untuk satu tanggal (tanggal mulai dan selesai harus sama).');
            }
        }

        $this->assertAdvanceNotice($type, $start);

        $suratDokterPath = null;
        if ($type === LeaveRequest::TYPE_SAKIT && $suratDokter instanceof \Illuminate\Http\UploadedFile) {
            $suratDokterPath = $suratDokter->store('leave-requests/surat-dokter', 'public');
            if (! is_string($suratDokterPath) || $suratDokterPath === '') {
                throw new \InvalidArgumentException('Gagal mengunggah surat keterangan dokter. Coba lagi.');
            }
        } elseif (LeaveRequest::requiresSuratDokter($type, $start, $end)) {
            throw new \InvalidArgumentException('Untuk izin sakit lebih dari 1 hari, surat keterangan dokter wajib diunggah.');
        }

        $owner = $manager
            ? LeaveApprovalRouting::ownerForAcknowledgment($user, $manager, $routing)
            : null;

        $leave = LeaveRequest::query()->create([
            'user_id' => $user->id,
            'attendance_validator_id' => (int) ($manager?->id ?? $head->id),
            'head_approver_id' => $head?->id,
            'manager_approver_id' => $manager?->id,
            'owner_approver_id' => $owner?->id,
            'type' => $type,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'reason' => $reason,
            'surat_dokter_path' => $suratDokterPath,
            'status' => LeaveRequest::STATUS_PENDING,
            'pemohon_signed_at' => now(),
            'pemohon_signature_token' => (string) Str::uuid(),
        ]);

        $leave = $leave->fresh(['user', 'assignedValidator', 'headApprover', 'managerApprover', 'ownerApprover']);
        $this->notifyNextApprover($leave);

        return $leave;
    }

    public function ensurePemohonSignature(LeaveRequest $leave): LeaveRequest
    {
        if ($leave->pemohonSigned()) {
            return $leave;
        }

        $leave->pemohon_signed_at = $leave->created_at ?? now();
        $leave->pemohon_signature_token = (string) Str::uuid();
        $leave->save();

        return $leave;
    }

    public function cancel(LeaveRequest $leave, User $user): LeaveRequest
    {
        if ((int) $leave->user_id !== (int) $user->id) {
            throw new \InvalidArgumentException('Pengajuan izin tidak ditemukan.');
        }

        if (! $leave->isPending()) {
            throw new \InvalidArgumentException('Hanya pengajuan menunggu yang dapat dibatalkan.');
        }

        $leave->update(['status' => LeaveRequest::STATUS_CANCELLED]);

        return $leave->fresh();
    }

    public function sign(LeaveRequest $leave, User $reviewer, string $aksi): LeaveRequest
    {
        $this->healHeadOnlyMarketingLeave($leave);
        $leave = $leave->fresh(['user', 'reviewer', 'headApprover', 'managerApprover', 'ownerApprover']);

        if (! $leave->isPending()) {
            return $leave;
        }

        $aksi = strtolower(trim($aksi));
        if (! in_array($aksi, ['head', 'manager', 'owner'], true)) {
            throw new \InvalidArgumentException('Tindakan tanda tangan tidak valid.');
        }

        if ($aksi === 'head') {
            $this->signHead($leave, $reviewer);
        } elseif ($aksi === 'manager') {
            $this->signManager($leave, $reviewer);
        } else {
            $this->signOwner($leave, $reviewer);
        }

        return $leave->fresh(['user', 'reviewer', 'headApprover', 'managerApprover', 'ownerApprover']);
    }

    /**
     * Pengajuan Staf Marketing lama masih memakai alur Head→Manager→Owner.
     * Setelah Head (atau sudah lewat Head) menandatangani, langsung disetujui.
     */
    public function healHeadOnlyMarketingLeave(LeaveRequest $leave): LeaveRequest
    {
        if (! $leave->isPending()) {
            return $leave;
        }

        $applicant = $leave->relationLoaded('user') ? $leave->user : $leave->user()->first();
        if (! $applicant || ! LeaveApprovalRouting::isHeadOnlyApprovalStaf($applicant)) {
            return $leave;
        }

        // Belum ada TTD Head: potong rantai ke Head saja (pengajuan baru / masih menunggu Head).
        if ($leave->requiresHeadSignature() && ! $leave->headSigned()) {
            if ($leave->manager_approver_id !== null || $leave->owner_approver_id !== null) {
                $leave->manager_approver_id = null;
                $leave->manager_signed_at = null;
                $leave->manager_signature_token = null;
                $leave->owner_approver_id = null;
                $leave->owner_signed_at = null;
                $leave->owner_signature_token = null;
                if ($leave->head_approver_id) {
                    $leave->attendance_validator_id = $leave->head_approver_id;
                }
                $leave->save();
            }

            return $leave->fresh(['user', 'headApprover', 'managerApprover', 'ownerApprover']);
        }

        // Head sudah TTD (atau tidak perlu Head): selesaikan tanpa menunggu Manager/Owner.
        if (! $leave->requiresHeadSignature() || $leave->headSigned()) {
            $reviewer = $leave->headApprover
                ?? $leave->managerApprover
                ?? $applicant;
            $this->finalizeApproval($leave, $reviewer);
        }

        return $leave->fresh(['user', 'reviewer', 'headApprover', 'managerApprover', 'ownerApprover']);
    }

    public function approve(LeaveRequest $leave, User $reviewer): LeaveRequest
    {
        $this->healHeadOnlyMarketingLeave($leave);
        $leave = $leave->fresh(['user', 'reviewer', 'headApprover', 'managerApprover', 'ownerApprover']);

        if (! $leave->isPending()) {
            return $leave;
        }

        if ($this->canSignHead($leave, $reviewer)) {
            return $this->sign($leave, $reviewer, 'head');
        }

        if ($this->canSignManager($leave, $reviewer)) {
            return $this->sign($leave, $reviewer, 'manager');
        }

        if ($this->canSignOwner($leave, $reviewer)) {
            return $this->sign($leave, $reviewer, 'owner');
        }

        throw new \InvalidArgumentException('Anda tidak berwenang menyetujui pengajuan ini pada tahap saat ini.');
    }

    public function reject(LeaveRequest $leave, User $reviewer, string $reason): LeaveRequest
    {
        if (! $leave->isPending()) {
            throw new \InvalidArgumentException('Pengajuan izin ini sudah diproses.');
        }

        if (! $this->canReview($leave, $reviewer)) {
            throw new \InvalidArgumentException('Anda tidak berwenang menolak pengajuan ini.');
        }

        $normalizedReason = trim($reason);
        if ($normalizedReason === '') {
            throw new \InvalidArgumentException('Alasan penolakan wajib diisi.');
        }

        $leave->update([
            'status' => LeaveRequest::STATUS_REJECTED,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'rejection_reason' => $normalizedReason,
        ]);

        $this->notifyEmployeeOfDecision($leave->fresh(['user', 'reviewer', 'headApprover', 'managerApprover']), approved: false);

        return $leave->fresh(['user', 'reviewer', 'headApprover', 'managerApprover']);
    }

    public function canReview(LeaveRequest $leave, User $reviewer): bool
    {
        if (! $leave->isPending()) {
            return false;
        }

        if ($leave->requiresHeadSignature() && ! $leave->headSigned()) {
            return (int) $leave->head_approver_id === (int) $reviewer->id || $reviewer->isOwner();
        }

        if ($leave->manager_approver_id && ! $leave->managerSigned()) {
            return (int) $leave->manager_approver_id === (int) $reviewer->id || $reviewer->isOwner();
        }

        return $this->legacyCanReview($leave, $reviewer);
    }

    public function canSignHead(LeaveRequest $leave, User $reviewer): bool
    {
        return $leave->isPending()
            && $leave->requiresHeadSignature()
            && ! $leave->headSigned()
            && ((int) $leave->head_approver_id === (int) $reviewer->id || $reviewer->isOwner());
    }

    public function canSignManager(LeaveRequest $leave, User $reviewer): bool
    {
        if (! $leave->isPending() || ! $leave->manager_approver_id || $leave->managerSigned()) {
            return false;
        }

        if ($leave->requiresHeadSignature() && ! $leave->headSigned()) {
            return false;
        }

        return (int) $leave->manager_approver_id === (int) $reviewer->id || $reviewer->isOwner();
    }

    public function canSignOwner(LeaveRequest $leave, User $reviewer): bool
    {
        return false;
    }

    /** @return array<string, mixed>|null */
    public function findByVerifyToken(string $token): ?array
    {
        foreach ([
            ['pemohon_signature_token', 'pemohon_signed_at', 'user', 'Pemohon', 'pemohon'],
            ['head_signature_token', 'head_signed_at', 'headApprover', 'Verifikasi 1 · Head', 'head'],
            ['manager_signature_token', 'manager_signed_at', 'managerApprover', 'Verifikasi 2 · Manager', 'manager'],
            ['owner_signature_token', 'owner_signed_at', 'ownerApprover', 'Diketahui · Owner', 'owner'],
        ] as [$tokenCol, $atCol, $relation, $label, $slot]) {
            $leave = LeaveRequest::query()
                ->where($tokenCol, $token)
                ->with([$relation, 'user'])
                ->first();

            if ($leave && $leave->{$atCol}) {
                $signer = $leave->{$relation};

                return [
                    'type' => 'izin',
                    'slot' => $slot,
                    'label' => $label,
                    'doc_title' => 'Pengajuan '.$leave->typeLabel(),
                    'nomor' => '#'.$leave->id,
                    'signed_at' => $leave->{$atCol},
                    'signer' => $signer,
                    'employee' => $leave->user,
                    'date_range' => $leave->dateRangeLabel(),
                ];
            }
        }

        return null;
    }

    private function signHead(LeaveRequest $leave, User $reviewer): void
    {
        if (! $this->canSignHead($leave, $reviewer)) {
            throw new \InvalidArgumentException('Anda bukan Head yang ditunjuk untuk pengajuan ini.');
        }

        if ($leave->headSigned()) {
            throw new \InvalidArgumentException('Sudah ditandatangani Head.');
        }

        $leave->head_signed_at = now();
        $leave->head_signature_token = $leave->head_signature_token ?: (string) Str::uuid();
        $leave->save();

        // Alur head-only (mis. Staf Marketing): selesai setelah Head menandatangani.
        if ($leave->manager_approver_id === null) {
            $this->finalizeApproval($leave->fresh(), $reviewer);

            return;
        }

        $this->notifyNextApprover($leave->fresh(['user', 'headApprover', 'managerApprover']));
    }

    private function signManager(LeaveRequest $leave, User $reviewer): void
    {
        if (! $this->canSignManager($leave, $reviewer)) {
            throw new \InvalidArgumentException('Anda bukan Manager yang ditunjuk atau Head belum menandatangani.');
        }

        if ($leave->managerSigned()) {
            throw new \InvalidArgumentException('Sudah ditandatangani Manager.');
        }

        DB::transaction(function () use ($leave) {
            $leave->manager_signed_at = now();
            $leave->manager_signature_token = $leave->manager_signature_token ?: (string) Str::uuid();
            $leave->rejection_reason = null;
            $leave->save();
        });

        $this->finalizeApproval($leave->fresh(), $reviewer);
    }

    private function signOwner(LeaveRequest $leave, User $reviewer): void
    {
        if (! $this->canSignOwner($leave, $reviewer)) {
            throw new \InvalidArgumentException('Anda bukan Owner yang ditunjuk atau Manager belum menandatangani.');
        }

        if ($leave->ownerSigned()) {
            throw new \InvalidArgumentException('Sudah ditandatangani Owner.');
        }

        DB::transaction(function () use ($leave) {
            $leave->owner_signed_at = now();
            $leave->owner_signature_token = $leave->owner_signature_token ?: (string) Str::uuid();
            $leave->save();
        });

        $this->finalizeApproval($leave, $reviewer);
    }

    private function finalizeApproval(LeaveRequest $leave, User $reviewer): void
    {
        DB::transaction(function () use ($leave, $reviewer) {
            $leave->status = LeaveRequest::STATUS_APPROVED;
            $leave->reviewed_by = $reviewer->id;
            $leave->reviewed_at = now();
            $leave->rejection_reason = null;
            $leave->save();

            if ($leave->type === LeaveRequest::TYPE_MASUK_LIBUR) {
                $this->syncApprovedWorkDayOverrides($leave, $reviewer);
            } elseif (! LeaveRequest::isHalfDayType($leave->type) && $leave->type !== LeaveRequest::TYPE_WFH) {
                // WFH sengaja tidak membuat hari libur: karyawan tetap wajib absen.
                $this->syncApprovedHolidays($leave, $reviewer);
            }
        });

        $this->notifyEmployeeOfDecision($leave->fresh(['user', 'reviewer', 'headApprover', 'managerApprover', 'ownerApprover']), approved: true);
    }

    private function legacyCanReview(LeaveRequest $leave, User $reviewer): bool
    {
        $approverId = $leave->attendance_validator_id
            ?: $leave->user?->attendance_validator_id;

        return $approverId !== null && (int) $approverId === (int) $reviewer->id;
    }

    private function syncApprovedHolidays(LeaveRequest $leave, User $reviewer): void
    {
        $employee = $leave->user;
        if (! $employee) {
            return;
        }

        $settings = $this->schedule->settings();
        $start = $leave->start_date->copy()->startOfDay();
        $end = $leave->end_date->copy()->startOfDay();
        $label = $leave->typeLabel().': '.mb_substr($leave->reason, 0, 80);

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            if (! $this->schedule->isConfiguredWorkDay($date, $settings, $employee)) {
                continue;
            }

            AttendanceHoliday::query()->updateOrCreate(
                [
                    'user_id' => $employee->id,
                    'holiday_date' => $date->toDateString(),
                ],
                [
                    'name' => $label,
                    'created_by' => $reviewer->id,
                ],
            );
        }
    }

    private function syncApprovedWorkDayOverrides(LeaveRequest $leave, User $reviewer): void
    {
        $employee = $leave->user;
        if (! $employee) {
            return;
        }

        $start = $leave->start_date->copy()->startOfDay();
        $end = $leave->end_date->copy()->startOfDay();

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            if (! $this->schedule->isEmployeeScheduledOffDay($date, $employee)) {
                continue;
            }

            AttendanceHoliday::query()
                ->where('user_id', $employee->id)
                ->whereDate('holiday_date', $date->toDateString())
                ->delete();

            AttendanceWorkDayOverride::query()->updateOrCreate(
                [
                    'user_id' => $employee->id,
                    'work_date' => $date->toDateString(),
                ],
                [
                    'leave_request_id' => $leave->id,
                    'created_by' => $reviewer->id,
                ],
            );
        }
    }

    private function assertMasukLiburDates(User $user, Carbon $start, Carbon $end): void
    {
        $invalid = [];
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            if (! $this->schedule->isEmployeeScheduledOffDay($date, $user)) {
                $invalid[] = $date->translatedFormat('d M Y');
            }
        }

        if ($invalid !== []) {
            $sample = implode(', ', array_slice($invalid, 0, 3));
            $suffix = count($invalid) > 3 ? '…' : '';
            throw new \InvalidArgumentException(
                'Pengajuan masuk hari libur hanya untuk tanggal libur Anda. Tanggal tidak valid: '.$sample.$suffix
            );
        }
    }

    private function assertNoOffDays(User $user, Carbon $start, Carbon $end, string $type): void
    {
        $invalid = [];
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            if ($this->schedule->isEmployeeScheduledOffDay($date, $user)) {
                $invalid[] = $date->translatedFormat('d M Y');
            }
        }

        if ($invalid !== []) {
            $sample = implode(', ', array_slice($invalid, 0, 3));
            $suffix = count($invalid) > 3 ? '…' : '';
            $label = match ($type) {
                LeaveRequest::TYPE_IZIN => 'Izin',
                LeaveRequest::TYPE_SAKIT => 'Sakit',
                LeaveRequest::TYPE_CUTI => 'Cuti',
                LeaveRequest::TYPE_WFH => 'WFH',
                LeaveRequest::TYPE_SETENGAH_MASUK, LeaveRequest::TYPE_SETENGAH_PULANG => 'Izin 1/2 hari',
                default => 'Pengajuan ini',
            };
            throw new \InvalidArgumentException(
                $label.' tidak dapat diajukan pada hari libur. Tanggal tidak valid: '.$sample.$suffix
            );
        }
    }

    private function assertAdvanceNotice(string $type, Carbon $start): void
    {
        $minDays = match ($type) {
            LeaveRequest::TYPE_SAKIT => 0,
            LeaveRequest::TYPE_MASUK_LIBUR => 1,
            LeaveRequest::TYPE_WFH, LeaveRequest::TYPE_SETENGAH_MASUK, LeaveRequest::TYPE_SETENGAH_PULANG => 0,
            default => 3,
        };

        $earliest = now()->startOfDay()->addDays($minDays);
        if ($start->lt($earliest)) {
            $rule = match ($type) {
                LeaveRequest::TYPE_SAKIT => 'Izin sakit dapat diajukan pada hari H (sakit mendadak).',
                LeaveRequest::TYPE_MASUK_LIBUR => 'Masuk hari libur minimal diajukan H-1.',
                LeaveRequest::TYPE_WFH => 'WFH dapat diajukan pada hari H (tanpa batas H-). Tetap wajib absen masuk & pulang.',
                LeaveRequest::TYPE_SETENGAH_MASUK, LeaveRequest::TYPE_SETENGAH_PULANG => 'Izin 1/2 hari dapat diajukan pada hari H.',
                default => 'Izin / acara keluarga / cuti minimal diajukan H-3 (3 hari sebelum tanggal izin).',
            };
            throw new \InvalidArgumentException($rule.' Tanggal paling awal: '.$earliest->translatedFormat('d M Y').'.');
        }
    }

    private function hasOverlap(User $user, Carbon $start, Carbon $end, ?int $excludeId = null): bool
    {
        return LeaveRequest::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [LeaveRequest::STATUS_PENDING, LeaveRequest::STATUS_APPROVED])
            ->when($excludeId, fn (Builder $query) => $query->where('id', '!=', $excludeId))
            ->where(function (Builder $query) use ($start, $end) {
                $query->whereBetween('start_date', [$start->toDateString(), $end->toDateString()])
                    ->orWhereBetween('end_date', [$start->toDateString(), $end->toDateString()])
                    ->orWhere(function (Builder $inner) use ($start, $end) {
                        $inner->where('start_date', '<=', $start->toDateString())
                            ->where('end_date', '>=', $end->toDateString());
                    });
            })
            ->exists();
    }

    /**
     * Antrian validasi: semua pengajuan yang reviewer terlibat (termasuk setelah TTD sendiri),
     * masih pending atau sudah disetujui lewat alur ini.
     *
     * @return Builder<LeaveRequest>
     */
    private function validationQueueQuery(User $reviewer): Builder
    {
        $reviewerId = (int) $reviewer->id;

        return LeaveRequest::query()
            ->with(['user:id,name,section,role', 'headApprover:id,name', 'managerApprover:id,name', 'ownerApprover:id,name'])
            ->where(function (Builder $outer) use ($reviewerId) {
                $outer->where(function (Builder $pending) use ($reviewerId) {
                    $pending->where('status', LeaveRequest::STATUS_PENDING)
                        ->where(fn (Builder $q) => $this->applyReviewerInvolvedConstraint($q, $reviewerId));
                })->orWhere(function (Builder $approved) use ($reviewerId) {
                    $approved->where('status', LeaveRequest::STATUS_APPROVED)
                        ->where(fn (Builder $q) => $this->applyReviewerInvolvedConstraint($q, $reviewerId))
                        ->where('reviewed_at', '>=', now()->subDays(90));
                });
            })
            ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [LeaveRequest::STATUS_PENDING])
            ->orderBy('start_date')
            ->orderByDesc('created_at');
    }

    /** Pengajuan yang masih perlu tindakan reviewer (TTD / tolak). */
    private function pendingActionQuery(User $reviewer): Builder
    {
        $reviewerId = (int) $reviewer->id;

        return LeaveRequest::query()
            ->where('status', LeaveRequest::STATUS_PENDING)
            ->where(fn (Builder $q) => $this->applyReviewerInvolvedConstraint($q, $reviewerId))
            ->where(function (Builder $query) use ($reviewer) {
                $query->where(function (Builder $head) use ($reviewer) {
                    $head->whereNotNull('head_approver_id')
                        ->whereNull('head_signed_at')
                        ->where('head_approver_id', $reviewer->id);
                })->orWhere(function (Builder $manager) use ($reviewer) {
                    $manager->where('manager_approver_id', $reviewer->id)
                        ->whereNull('manager_signed_at')
                        ->where(function (Builder $ready) {
                            $ready->whereNull('head_approver_id')
                                ->orWhereNotNull('head_signed_at');
                        });
                })->orWhere(function (Builder $legacy) use ($reviewer) {
                    $legacy->whereNull('head_approver_id')
                        ->whereNull('manager_approver_id')
                        ->where(function (Builder $inner) use ($reviewer) {
                            $inner->where('attendance_validator_id', $reviewer->id)
                                ->orWhereHas('user', fn (Builder $userQuery) => $userQuery->where('attendance_validator_id', $reviewer->id));
                        });
                });
            });
    }

    private function applyReviewerInvolvedConstraint(Builder $query, int $reviewerId): void
    {
        $query->where(function (Builder $q) use ($reviewerId) {
            $q->where('head_approver_id', $reviewerId)
                ->orWhere('manager_approver_id', $reviewerId)
                ->orWhere('owner_approver_id', $reviewerId)
                ->orWhere(function (Builder $legacy) use ($reviewerId) {
                    $legacy->whereNull('head_approver_id')
                        ->whereNull('manager_approver_id')
                        ->where(function (Builder $inner) use ($reviewerId) {
                            $inner->where('attendance_validator_id', $reviewerId)
                                ->orWhereHas('user', fn (Builder $userQuery) => $userQuery->where('attendance_validator_id', $reviewerId));
                        });
                });
        });
    }

    private function notifyNextApprover(LeaveRequest $leave): void
    {
        $targetId = null;
        if ($leave->requiresHeadSignature() && ! $leave->headSigned()) {
            $targetId = (int) $leave->head_approver_id;
        } elseif ($leave->manager_approver_id && ! $leave->managerSigned()) {
            $targetId = (int) $leave->manager_approver_id;
        }

        if ($targetId < 1) {
            return;
        }

        $employee = $leave->user;

        AppNotification::query()->create([
            'user_id' => $targetId,
            'type' => 'leave_request_pending',
            'title' => 'Pengajuan Izin Baru',
            'message' => ($employee?->name ?? 'Karyawan').' mengajukan '.$leave->typeLabel().' '.$leave->dateRangeLabel(),
            'data' => ['leave_request_id' => $leave->id],
            'link' => route('izin.show', $leave),
        ]);
    }

    private function notifyEmployeeOfDecision(LeaveRequest $leave, bool $approved): void
    {
        AppNotification::query()->create([
            'user_id' => $leave->user_id,
            'type' => $approved ? 'leave_request_approved' : 'leave_request_rejected',
            'title' => $approved ? 'Pengajuan Izin Disetujui' : 'Pengajuan Izin Ditolak',
            'message' => $approved
                ? 'Pengajuan '.$leave->typeLabel().' '.$leave->dateRangeLabel().' telah disetujui.'
                : 'Pengajuan '.$leave->typeLabel().' '.$leave->dateRangeLabel().' ditolak: '.$leave->rejection_reason,
            'data' => ['leave_request_id' => $leave->id],
            'link' => route('izin.show', $leave),
        ]);
    }
}
