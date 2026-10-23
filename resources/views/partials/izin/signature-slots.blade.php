@php
    /** @var \App\Models\LeaveRequest $leave */
    $letterLayout = $letterLayout ?? false;
    $signRoute = $signRoute ?? '#';
    $verifyBase = url('/izin/verify');
    $slotClass = $letterLayout
        ? 'izin-surat-sign-slot'
        : 'rounded-xl border border-gray-200 bg-white p-4 text-center';
@endphp

<div class="{{ $letterLayout ? 'izin-surat-sign-grid' : 'grid gap-4 sm:grid-cols-2' }}">
    @if($leave->requiresHeadSignature())
    @php
        $headRoleLabel = $leave->headApprover?->role ?? 'Head';
        $headOnly = $leave->manager_approver_id === null && ! $leave->requiresOwnerAcknowledgment();
        $headSlot = [
            'key' => 'head',
            'title' => $headOnly ? 'Verifikasi' : 'Verifikasi 1',
            'subtitle' => $headRoleLabel.' · TTD QR',
            'user' => $leave->headApprover,
            'at' => $leave->head_signed_at,
            'token' => $leave->head_signature_token,
            'can' => $canSignHead ?? false,
            'ready' => true,
        ];
    @endphp
    @include('partials.izin.signature-slot', [
        'slot' => $headSlot,
        'leave' => $leave,
        'signRoute' => $signRoute,
        'verifyBase' => $verifyBase,
        'slotClass' => $slotClass,
        'letterLayout' => $letterLayout,
    ])
    @endif

    @if($leave->manager_approver_id)
    @php
        $managerTitle = 'Verifikasi 2';
        $managerSubtitle = 'Manager · TTD QR';
        if ($leave->managerApprover?->isOwner()) {
            $managerTitle = 'Verifikasi 2';
            $managerSubtitle = 'Owner · TTD QR';
        }
        if (! $leave->requiresHeadSignature()) {
            $managerTitle = 'Verifikasi';
        }
        $managerSlot = [
            'key' => 'manager',
            'title' => $managerTitle,
            'subtitle' => $managerSubtitle,
            'user' => $leave->managerApprover,
            'at' => $leave->manager_signed_at,
            'token' => $leave->manager_signature_token,
            'can' => $canSignManager ?? false,
            'ready' => ! $leave->requiresHeadSignature() || $leave->headSigned(),
        ];
    @endphp
    @include('partials.izin.signature-slot', [
        'slot' => $managerSlot,
        'leave' => $leave,
        'signRoute' => $signRoute,
        'verifyBase' => $verifyBase,
        'slotClass' => $slotClass,
        'letterLayout' => $letterLayout,
    ])
    @endif

</div>
@php
    $ownerNoteName = $leave->ownerApprover?->name;
    $showOwnerNote = (bool) $ownerNoteName
        || ($leave->head_approver_id && $leave->manager_approver_id && ! $leave->managerApprover?->isOwner());
    if ($showOwnerNote && ! $ownerNoteName) {
        $ownerNoteName = \App\Models\User::query()->where('role', \App\Models\User::ROLE_OWNER)->value('name') ?: 'Owner';
    }
@endphp
@if($showOwnerNote)
    @if($letterLayout)
    <div class="izin-surat-note" style="margin-top:16px">
        Pengajuan ini diketahui oleh <strong>{{ $ownerNoteName }}</strong> (Owner).
    </div>
    @else
    <p class="mt-3 text-xs text-gray-500">Pengajuan ini diketahui oleh {{ $ownerNoteName }} (Owner).</p>
    @endif
@endif
