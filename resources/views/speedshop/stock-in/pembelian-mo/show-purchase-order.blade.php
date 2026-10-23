@extends('layouts.speedshop')

@section('title', 'Detail Purchase Order — ' . ($purchaseOrder->invoice_no ?: $purchaseOrder->kode))

@section('content')
@php
    $st = $purchaseOrder->status;
    $to = $purchaseOrder->mirroredTransaksiOffline;
    $whRej = $to && $to->isWarehousePoReviewRejected();
    $toRejected = $to && $to->isRejected();
    $toVerified = $to && $to->isVerified();

    $isWarehouseApproved = $to && $to->warehouse_po_reviewed_at !== null && ! $whRej;
    $isProcessed = $to && $to->petugas_id !== null;
    $isFinished = $to && ($toVerified || $toRejected || $whRej);

    // Tombol approve/reject Speedshop hanya setelah gudang memproses (Diproses) dan belum selesai.
    $canActSs = $to
        && $isProcessed
        && ! $toVerified
        && ! $toRejected
        && auth()->user()->isManager();

    // Badge header: utamakan alur TO/PO mirror
    if ($to) {
        if ($whRej) {
            $statusBadge = ['class' => 'bg-red-100 text-red-800', 'text' => 'Pengajuan ditolak gudang'];
        } elseif (! $isWarehouseApproved) {
            $statusBadge = ['class' => 'bg-amber-100 text-amber-800', 'text' => 'Menunggu diterima/ditolak gudang'];
        } elseif (! $isProcessed) {
            $statusBadge = ['class' => 'bg-amber-100 text-amber-800', 'text' => 'Menunggu diproses gudang'];
        } elseif (! $isFinished) {
            $statusBadge = ['class' => 'bg-yellow-100 text-yellow-800', 'text' => 'Menunggu verifikasi'];
        } elseif ($toVerified) {
            $statusBadge = ['class' => 'bg-emerald-100 text-emerald-800', 'text' => 'Stok diverifikasi'];
        } elseif ($toRejected) {
            $statusBadge = ['class' => 'bg-red-100 text-red-800', 'text' => 'Penerimaan ditolak'];
        } else {
            $statusBadge = ['class' => 'bg-amber-100 text-amber-800', 'text' => 'Menunggu proses'];
        }
    } elseif ($st === 'approved') {
        $statusBadge = ['class' => 'bg-emerald-100 text-emerald-800', 'text' => 'Disetujui'];
    } elseif ($st === 'rejected') {
        $statusBadge = ['class' => 'bg-red-100 text-red-800', 'text' => 'Ditolak'];
    } else {
        $statusBadge = ['class' => 'bg-amber-100 text-amber-800', 'text' => 'Menunggu proses'];
    }

    // 4 status: Diajukan -> Diterima/Ditolak -> Diproses -> Selesai
    $stepApprovedDone = $isWarehouseApproved || $whRej;
    $stepApprovedErr = $whRej;

    $stepProcessedDone = $isProcessed;
    $stepProcessedErr = false;

    $stepFinishedDone = $isFinished;
    $stepFinishedErr = $toRejected || $whRej;

    $currentApproved = $to && ! $whRej && ! $isWarehouseApproved;
    $currentProcessed = $to && ! $whRej && $isWarehouseApproved && ! $isProcessed;
    $currentFinished = $to && ! $whRej && $isProcessed && ! $toVerified && ! $toRejected;

    $step2Label = $whRej
        ? 'Ditolak'
        : ($isWarehouseApproved ? 'Diterima' : 'Diterima/Ditolak');
@endphp
<div class="space-y-6">
    <nav class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('speedshop.dashboard') }}" class="transition hover:text-gray-700">Home</a>
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('speedshop.stock-in.pembelian-mo.index') }}" class="transition hover:text-gray-700">Purchase Order</a>
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <span class="font-medium text-gray-900">{{ $purchaseOrder->kode }}</span>
    </nav>

    @if($to && $whRej)
    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
        <span class="font-semibold">Pengajuan ditolak oleh warehouse.</span>
        @if($to->warehouse_po_review_rejected_reason)
            <span class="mt-1 block text-red-800">{{ $to->warehouse_po_review_rejected_reason }}</span>
        @endif
    </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-6">
            <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">Purchase Order 77racing</h1>
                        <p class="mt-1 text-sm text-gray-500">Pengajuan ke warehouse untuk pengiriman stok ke Speedshop</p>
                    </div>
                    <div>
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $statusBadge['class'] }}">{{ $statusBadge['text'] }}</span>
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-gray-600">Kode PO</label>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm font-mono font-semibold text-gray-900">{{ $purchaseOrder->kode }}</div>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-gray-600">No. Invoice</label>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm font-medium text-gray-900">{{ $purchaseOrder->invoice_no ?: '—' }}</div>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-gray-600">Tanggal</label>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700">{{ $purchaseOrder->created_at->format('d M Y H:i') }}</div>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-gray-600">Warehouse</label>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700">{{ $purchaseOrder->warehouse?->nama ?? '—' }}</div>
                    </div>
                </div>

                @if($purchaseOrder->catatan)
                <div class="mt-4">
                    <label class="mb-1.5 block text-xs font-semibold text-gray-600">Catatan</label>
                    <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700 whitespace-pre-wrap">{{ $purchaseOrder->catatan }}</div>
                </div>
                @endif

                @if($st === 'approved' && $purchaseOrder->approved_at)
                <div class="mt-4 rounded-xl border border-emerald-100 bg-emerald-50/80 px-4 py-3 text-sm text-emerald-900">
                    Disetujui {{ $purchaseOrder->approved_at->format('d M Y H:i') }}
                    @if($purchaseOrder->approver) — {{ $purchaseOrder->approver->name }} @endif
                </div>
                @endif
                @if($st === 'rejected' && $purchaseOrder->rejected_at)
                <div class="mt-4 rounded-xl border border-red-100 bg-red-50/80 px-4 py-3 text-sm text-red-900">
                    <p class="font-semibold">Ditolak {{ $purchaseOrder->rejected_at->format('d M Y H:i') }}</p>
                    @if($purchaseOrder->rejected_reason)
                        <p class="mt-1">{{ $purchaseOrder->rejected_reason }}</p>
                    @endif
                </div>
                @endif
            </div>

            <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold text-gray-900">Daftar Produk</h2>
                    <span class="inline-flex items-center rounded-lg bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600">{{ $purchaseOrder->items->count() }} item</span>
                </div>

                <div class="mt-4 overflow-x-auto rounded-xl border border-gray-100">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-5 py-3.5 text-xs font-bold uppercase tracking-wider text-gray-500">No.</th>
                                <th class="px-5 py-3.5 text-xs font-bold uppercase tracking-wider text-gray-500">Kode</th>
                                <th class="px-5 py-3.5 text-xs font-bold uppercase tracking-wider text-gray-500">Nama Supplier</th>
                                <th class="px-5 py-3.5 text-xs font-bold uppercase tracking-wider text-gray-500">Nama Produk</th>
                                <th class="px-5 py-3.5 text-xs font-bold uppercase tracking-wider text-gray-500 text-center">Qty</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($purchaseOrder->items as $i => $item)
                            <tr class="transition hover:bg-gray-50">
                                <td class="px-5 py-4 text-gray-500">{{ $i + 1 }}</td>
                                <td class="px-5 py-4 font-medium text-gray-900">{{ $item->product->kode_produk ?? '—' }}</td>
                                <td class="px-5 py-4 text-gray-700">{{ $supplierByProduct[$item->product_id]['nama_supplier'] ?? '—' }}</td>
                                <td class="px-5 py-4 text-gray-700">{{ $item->product->nama_produk ?? '—' }}</td>
                                <td class="px-5 py-4 text-center font-semibold text-gray-900">{{ $item->qty }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-bold text-gray-900">Ringkasan</h2>
                <div class="mt-4 space-y-3">
                    <div class="flex items-center justify-between rounded-lg bg-gray-50 px-4 py-3">
                        <span class="text-sm font-medium text-gray-700">Total Item</span>
                        <span class="text-sm font-bold text-gray-900">{{ $purchaseOrder->items->count() }} item</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-gray-50 px-4 py-3">
                        <span class="text-sm font-medium text-gray-700">Total Qty</span>
                        <span class="text-sm font-bold text-gray-900">{{ (int) $purchaseOrder->items->sum('qty') }} unit</span>
                    </div>
                </div>

                @if($to)
                <div class="mt-5">
                    <label class="mb-3 block text-sm font-semibold text-gray-700">Status</label>
                    @php
                        $stepsPo = [
                            ['label' => 'Diajukan', 'done' => true, 'err' => false, 'current' => false],
                            ['label' => $step2Label, 'done' => $stepApprovedDone || $stepApprovedErr, 'err' => $stepApprovedErr, 'current' => $currentApproved],
                            ['label' => 'Diproses', 'done' => $stepProcessedDone || $stepProcessedErr, 'err' => $stepProcessedErr, 'current' => $currentProcessed],
                            ['label' => 'Selesai', 'done' => $stepFinishedDone, 'err' => $stepFinishedErr, 'current' => $currentFinished],
                        ];
                    @endphp
                    <div class="flex items-start justify-between">
                        @foreach($stepsPo as $idx => $step)
                        <div class="flex flex-col items-center" style="width: {{ 100 / count($stepsPo) }}%">
                            <div class="relative flex w-full items-center justify-center">
                                @if($idx > 0)
                                <div class="absolute right-1/2 h-0.5 w-full @if(($step['done'] ?? false) || ($step['err'] ?? false) || (!empty($step['current']))) {{ ($step['err'] ?? false) ? 'bg-red-400' : 'bg-green-400' }} @else bg-gray-200 @endif"></div>
                                @endif
                                @if($idx < count($stepsPo) - 1)
                                @php $nextPo = $stepsPo[$idx + 1]; @endphp
                                <div class="absolute left-1/2 h-0.5 w-full @if(($nextPo['done'] ?? false) || ($nextPo['err'] ?? false) || (!empty($nextPo['current']))) {{ ($nextPo['err'] ?? false) ? 'bg-red-400' : 'bg-green-400' }} @else bg-gray-200 @endif"></div>
                                @endif
                                @if(($step['done'] ?? false) || ($step['err'] ?? false))
                                    @if($step['err'] ?? false)
                                    <div class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full bg-brand shadow-sm"><svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></div>
                                    @else
                                    <div class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full bg-green-500 shadow-sm"><svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></div>
                                    @endif
                                @elseif(!empty($step['current']))
                                    <div class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full border-2 border-yellow-400 bg-yellow-50"><div class="h-2.5 w-2.5 rounded-full bg-yellow-400 animate-pulse"></div></div>
                                @else
                                    <div class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full bg-gray-200"><svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></div>
                                @endif
                            </div>
                            <p class="mt-2 text-center text-xs font-medium @if($step['done'] ?? false) {{ ($step['err'] ?? false) ? 'text-red-600' : 'text-green-600' }} @elseif(!empty($step['current'])) text-yellow-600 @else text-gray-400 @endif">{{ $step['label'] }}</p>
                        </div>
                        @endforeach
                    </div>
                    @if($toVerified)
                    <p class="mt-3 text-center text-xs text-gray-500">Diverifikasi {{ $to->verified_at->format('d M Y H:i') }}</p>
                    @elseif($toRejected)
                    <p class="mt-3 text-center text-xs text-gray-500">Ditolak {{ $to->rejected_at->format('d M Y H:i') }}@if($to->rejectedByUser) · {{ $to->rejectedByUser->name }}@endif</p>
                    @endif
                </div>

                @if($to && ! $isFinished && ! $isProcessed)
                    @if(! $isWarehouseApproved)
                    <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
                        <p class="text-sm font-semibold text-amber-900">Menunggu gudang</p>
                        <p class="mt-1 text-xs leading-relaxed text-amber-800/90">
                            Warehouse menunggu persetujuan pengajuan Anda. Setelah disetujui, gudang akan memproses pengiriman (mutasi).
                        </p>
                    </div>
                    @else
                    <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm">
                        <p class="text-sm font-semibold text-emerald-900">Pengajuan disetujui</p>
                        <p class="mt-1 text-xs leading-relaxed text-emerald-800/90">
                            @if($to->warehouse_po_reviewed_at)
                                Disetujui {{ $to->warehouse_po_reviewed_at->format('d M Y H:i') }}@if($to->warehousePoReviewedBy) · {{ $to->warehousePoReviewedBy->name }}@endif.
                            @endif
                            Menunggu gudang memproses pengiriman (mutasi).
                        </p>
                    </div>
                    @endif
                @endif

                @if($canActSs)
                <div class="mt-5 space-y-3">
                    <button type="button" onclick="poVerifyTransaksi()" id="po-btn-verify" class="flex w-full items-center justify-center gap-2 rounded-xl bg-green-500 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-green-600 disabled:cursor-not-allowed disabled:opacity-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        Approve - Setujui
                    </button>
                    <button type="button" onclick="poRejectTransaksi()" id="po-btn-reject" class="flex w-full items-center justify-center gap-2 rounded-xl border border-gray-200 px-5 py-3 text-sm font-medium text-gray-600 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        Reject - Tolak
                    </button>
                </div>

                <div class="mt-5 rounded-xl border border-yellow-200 bg-yellow-50 p-4">
                    <p class="text-sm font-semibold text-yellow-800">Menunggu Verifikasi</p>
                    <p class="mt-1 text-xs text-yellow-700">Periksa daftar produk, lalu approve atau reject. Setelah approve, stok masuk ke Speedshop Anda.</p>
                </div>
                @elseif($to && $isProcessed && ! $toVerified && ! $toRejected && ! auth()->user()->isManager())
                <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 p-3">
                    <p class="text-xs font-semibold text-amber-800">Menunggu konfirmasi</p>
                    <p class="mt-1 text-xs text-amber-700">Hubungi Manager Speedshop untuk verifikasi stok.</p>
                </div>
                @elseif($toVerified)
                <div class="mt-5 rounded-lg border border-emerald-200 bg-emerald-50 p-3">
                    <p class="text-xs font-semibold text-emerald-800">Sudah diverifikasi</p>
                    <p class="mt-1 text-xs text-emerald-700">Stok telah masuk pada {{ $to->verified_at->format('d M Y H:i') }}</p>
                </div>
                @elseif($toRejected)
                <div class="mt-5 rounded-lg border border-red-200 bg-red-50 p-3">
                    <p class="text-xs font-semibold text-red-800">Penerimaan ditolak</p>
                    <p class="mt-1 text-xs text-red-700">Stok dikembalikan ke warehouse.</p>
                    @if($to->rejected_reason)
                    <p class="mt-2 text-xs text-red-800"><span class="font-semibold">Alasan:</span> {{ $to->rejected_reason }}</p>
                    @endif
                </div>
                @endif

                @endif
            </div>

            <a href="{{ route('speedshop.stock-in.pembelian-mo.index') }}" class="flex w-full items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-5 py-3 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Kembali ke daftar
            </a>
        </div>
    </div>
</div>

@if($to && $canActSs)
@push('scripts')
<script>
const poVerifyUrl = '{{ route("speedshop.stock-in.transaksi-offline.verify", $to) }}';
const poRejectUrl = '{{ route("speedshop.stock-in.transaksi-offline.reject", $to) }}';

async function poVerifyTransaksi() {
    const result = await Swal.fire({
        title: 'Verifikasi penerimaan stok?',
        text: 'Stok produk akan ditambahkan ke stok Speedshop Anda. Tindakan ini tidak dapat dibatalkan.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Setujui',
        cancelButtonText: 'Batal'
    });
    if (!result.isConfirmed) return;

    const btn = document.getElementById('po-btn-verify');
    if (btn) { btn.disabled = true; btn.classList.add('opacity-70'); }

    try {
        const res = await fetch(poVerifyUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        });
        const data = await res.json();

        if (!res.ok) {
            Swal.fire('Gagal', data.message || 'Verifikasi gagal.', 'error');
            if (btn) { btn.disabled = false; btn.classList.remove('opacity-70'); }
            return;
        }

        await Swal.fire('Berhasil', data.message || 'Transaksi berhasil diverifikasi.', 'success');
        window.location.reload();
    } catch (e) {
        Swal.fire('Error', 'Terjadi kesalahan. Coba lagi.', 'error');
        if (btn) { btn.disabled = false; btn.classList.remove('opacity-70'); }
    }
}

async function poRejectTransaksi() {
    const result = await Swal.fire({
        title: 'Tolak penerimaan stok?',
        html: '<p class="text-sm text-gray-600 mb-3">Qty akan dikembalikan ke stok warehouse. Tindakan ini tidak dapat dibatalkan.</p>',
        input: 'textarea',
        inputLabel: 'Alasan (opsional)',
        inputPlaceholder: 'Contoh: Barang tidak sesuai pesanan…',
        inputAttributes: { 'aria-label': 'Alasan penolakan' },
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, tolak',
        cancelButtonText: 'Batal',
        focusCancel: true,
    });
    if (!result.isConfirmed) return;

    const btn = document.getElementById('po-btn-reject');
    const btnV = document.getElementById('po-btn-verify');
    if (btn) { btn.disabled = true; btn.classList.add('opacity-70'); }
    if (btnV) { btnV.disabled = true; btnV.classList.add('opacity-70'); }

    try {
        const res = await fetch(poRejectUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ rejected_reason: result.value || '' }),
        });
        const data = await res.json();

        if (!res.ok) {
            Swal.fire('Gagal', data.message || 'Penolakan gagal.', 'error');
            if (btn) { btn.disabled = false; btn.classList.remove('opacity-70'); }
            if (btnV) { btnV.disabled = false; btnV.classList.remove('opacity-70'); }
            return;
        }

        await Swal.fire('Berhasil', data.message || 'Transaksi ditolak.', 'success');
        window.location.reload();
    } catch (e) {
        Swal.fire('Error', 'Terjadi kesalahan. Coba lagi.', 'error');
        if (btn) { btn.disabled = false; btn.classList.remove('opacity-70'); }
        if (btnV) { btnV.disabled = false; btnV.classList.remove('opacity-70'); }
    }
}
</script>
@endpush
@endif
@endsection
