@php
    $ringkasanTotalItem = $service_order->jasaServis->count() + $service_order->products->count();
    $ringkasanTotalQtySparepart = $service_order->products->sum(fn ($p) => (int) ($p->pivot->qty ?? 0));
    $subtotalSebelumDiskon = $service_order->subtotal_sebelum_diskon;
    $totalDiskon = $service_order->total_diskon;
    $grandTotal = $service_order->finalGrandTotal();

    $diskonSteps = [
        ['label' => 'Diajukan', 'done' => true],
        ['label' => 'Menunggu Verifikasi', 'done' => in_array($diskonVerifikasiStatus, ['approved', 'rejected'], true)],
        ['label' => $diskonVerifikasiStatus === 'rejected' ? 'Ditolak' : 'Disetujui', 'done' => in_array($diskonVerifikasiStatus, ['approved', 'rejected'], true)],
    ];
    $isRejectedDiskon = $diskonVerifikasiStatus === 'rejected';
    $diskonCurrentStep = match ($diskonVerifikasiStatus) {
        'pending' => 1,
        default => 2,
    };
    $diskonVerifier = match ($diskonVerifikasiStatus) {
        'approved' => $service_order->diskonVerifikasiVerifiedBy,
        'rejected' => $service_order->diskonVerifikasiRejectedBy,
        default => null,
    };
    $diskonVerifiedAt = match ($diskonVerifikasiStatus) {
        'approved' => $service_order->diskon_verifikasi_verified_at,
        'rejected' => $service_order->diskon_verifikasi_rejected_at,
        default => null,
    };
@endphp

<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white">
    <div class="border-b border-gray-100 px-5 py-4">
        <h2 class="text-[20px] font-semibold text-gray-900">Ringkasan</h2>
    </div>
    <div class="space-y-2 px-5 py-3">
        <div class="flex items-center justify-between rounded-lg border border-blue-100 bg-blue-50 px-4 py-3">
            <span class="text-sm font-medium text-gray-700">Total Item</span>
            <span class="text-sm font-bold text-gray-900">{{ $ringkasanTotalItem }}</span>
        </div>
    </div>
    <div class="space-y-2 border-t border-gray-100 px-5 py-4">
        <div class="flex items-center justify-between text-sm">
            <span class="text-gray-600">Total Qty Sparepart</span>
            <span class="font-semibold text-gray-900">{{ number_format($ringkasanTotalQtySparepart) }} unit</span>
        </div>
        @if($totalDiskon > 0)
        <div class="flex items-center justify-between text-sm">
            <span class="text-gray-600">Subtotal</span>
            <span class="font-semibold text-gray-900">Rp {{ number_format($subtotalSebelumDiskon, 0, ',', '.') }}</span>
        </div>
        <div class="flex items-center justify-between text-sm">
            <span class="text-gray-600">Total Diskon</span>
            <span class="font-semibold text-amber-700">− Rp {{ number_format($totalDiskon, 0, ',', '.') }}</span>
        </div>
        @endif
        <div class="flex items-center justify-between rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-600 px-4 py-3">
            <span class="text-sm font-medium text-emerald-100">Total Harga</span>
            <span class="text-sm font-bold text-white">Rp {{ number_format($grandTotal, 0, ',', '.') }}</span>
        </div>
    </div>

    <div class="border-t border-gray-100 px-5 py-4">
        <label class="mb-3 block text-[11px] font-semibold uppercase tracking-wider text-gray-500">Alur Verifikasi</label>
        <div class="flex items-start justify-between">
            @foreach($diskonSteps as $idx => $step)
            <div class="flex flex-col items-center" style="width: {{ 100 / count($diskonSteps) }}%">
                <div class="relative flex w-full items-center justify-center">
                    @if($idx > 0)
                    <div class="absolute right-1/2 h-0.5 w-full {{ $step['done'] ? ($isRejectedDiskon && $idx === count($diskonSteps) - 1 ? 'bg-red-400' : 'bg-green-400') : 'bg-gray-200' }}"></div>
                    @endif
                    @if($idx < count($diskonSteps) - 1)
                    <div class="absolute left-1/2 h-0.5 w-full {{ $diskonSteps[$idx + 1]['done'] ? ($isRejectedDiskon ? 'bg-red-400' : 'bg-green-400') : 'bg-gray-200' }}"></div>
                    @endif
                    @if($step['done'])
                        @if($isRejectedDiskon && $idx === count($diskonSteps) - 1)
                        <div class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full bg-brand shadow-sm"><svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></div>
                        @else
                        <div class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full bg-green-500 shadow-sm"><svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></div>
                        @endif
                    @elseif($idx === $diskonCurrentStep)
                        <div class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full border-2 border-amber-400 bg-amber-50"><div class="h-2.5 w-2.5 rounded-full bg-amber-400 animate-pulse"></div></div>
                    @else
                        <div class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full bg-gray-200"><svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></div>
                    @endif
                </div>
                <p class="mt-2 text-center text-xs font-medium {{ $step['done'] ? ($isRejectedDiskon && $idx === count($diskonSteps) - 1 ? 'text-red-600' : 'text-green-600') : ($idx === $diskonCurrentStep ? 'text-amber-600' : 'text-gray-400') }}">{{ $step['label'] }}</p>
            </div>
            @endforeach
        </div>
        @if($diskonVerifier)
        <p class="mt-3 text-center text-xs text-gray-500">oleh {{ $diskonVerifier->name }} · {{ $diskonVerifiedAt?->format('d M Y H:i') }}</p>
        @elseif($service_order->diskonVerifikasiRequestedBy && $diskonVerifikasiStatus === 'pending')
        <p class="mt-3 text-center text-xs text-gray-500">diajukan {{ $service_order->diskonVerifikasiRequestedBy->name }} · {{ $service_order->diskon_verifikasi_requested_at?->format('d M Y H:i') }}</p>
        @endif

        @if(!empty($service_order->diskon_verifikasi_rejected_reason))
        <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-3">
            <p class="text-xs font-semibold text-red-600">Alasan Penolakan</p>
            <p class="mt-0.5 text-sm text-red-700">{{ $service_order->diskon_verifikasi_rejected_reason }}</p>
        </div>
        @endif
    </div>

    @if($isPendingVerify && ($isDiskonVerifier ?? false))
    <div class="space-y-3 border-t border-gray-100 px-5 pb-5">
        <button type="button" @click="approveDiskon()" :disabled="loading" class="flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-600 px-5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/25 transition hover:brightness-105 disabled:cursor-not-allowed disabled:opacity-50">
            <svg x-show="!loading" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            <svg x-show="loading" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
            Approve - Setujui
        </button>

        <div x-show="showRejectForm" x-cloak class="space-y-3">
            <label class="block text-[11px] font-medium uppercase tracking-wide text-gray-500">Alasan Penolakan <span class="text-red-600">*</span></label>
            <textarea x-model="rejectedReason" rows="3" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm transition focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40" placeholder="Masukkan alasan penolakan..."></textarea>
            <p x-show="rejectError" class="text-xs text-red-600" x-text="rejectError"></p>
            <div class="flex gap-2">
                <button type="button" @click="confirmRejectDiskon()" :disabled="loading" class="flex-1 rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700 disabled:opacity-50">Konfirmasi Tolak</button>
                <button type="button" @click="showRejectForm = false; rejectedReason = ''; rejectError = ''" class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-600 transition hover:bg-gray-50">Batal</button>
            </div>
        </div>

        <button type="button" x-show="!showRejectForm" @click="showRejectForm = true" class="flex h-10 w-full items-center justify-center gap-2 rounded-xl border border-red-200 bg-red-50 px-5 text-sm font-medium text-red-600 transition hover:bg-red-100">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            Reject - Tolak
        </button>
    </div>
    @endif
</div>

@if($isPendingVerify && ($isDiskonVerifier ?? false))
<div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
    <div class="flex gap-3">
        <svg class="h-5 w-5 flex-shrink-0 text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <div>
            <p class="text-sm font-semibold text-amber-800">Menunggu Verifikasi</p>
            <p class="mt-1 text-xs text-amber-800">Periksa diskon yang diajukan, lalu approve atau reject pengajuan ini.</p>
        </div>
    </div>
</div>
@endif

<a href="{{ $diskonVerifikasiIndexUrl }}" class="flex h-10 w-full items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-5 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
    Kembali ke Daftar
</a>
