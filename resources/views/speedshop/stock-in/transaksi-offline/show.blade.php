@extends('layouts.speedshop')

@section('title', 'Detail Transaksi Offline - ' . $transaksiOffline->no_transaksi)

@section('content')
@php
    $isPoFromSpeedshop = $transaksiOffline->tujuan === 'speedshop'
        && $transaksiOffline->speedshop_purchase_order_id
        && $transaksiOffline->petugas_id === null
        && ! $transaksiOffline->isVerified()
        && ! $transaksiOffline->isRejected();

    $needsWarehousePoReview = $isPoFromSpeedshop
        && $transaksiOffline->warehouse_po_reviewed_at === null
        && $transaksiOffline->warehouse_po_review_rejected_at === null;

    $warehousePoReviewRejected = $transaksiOffline->warehouse_po_review_rejected_at !== null;
    $warehousePoClosed = $transaksiOffline->warehouse_po_closed_at !== null;

    $showCetakPdf = $transaksiOffline->isVerified()
        || $transaksiOffline->isRejected()
        || $transaksiOffline->warehouse_po_review_rejected_at !== null
        || $warehousePoClosed;

    $statusLabel = 'Menunggu Proses';

    if ($transaksiOffline->isVerified()) {
        $statusLabel = 'Selesai (Diverifikasi Speedshop)';
    } elseif ($transaksiOffline->isRejected()) {
        $statusLabel = 'Selesai (Ditolak Speedshop)';
    } elseif ($transaksiOffline->warehouse_po_review_rejected_at !== null) {
        $statusLabel = 'Selesai (Ditolak Warehouse)';
    } elseif ($warehousePoClosed) {
        $statusLabel = 'Dibatalkan Warehouse';
    } elseif ($transaksiOffline->tujuan === 'speedshop') {
        $statusLabel = 'Menunggu Verifikasi Speedshop';
    } elseif ($needsWarehousePoReview) {
        $statusLabel = 'Menunggu Review Warehouse';
    } elseif ($transaksiOffline->warehouse_po_reviewed_at !== null && $transaksiOffline->petugas_id === null) {
        $statusLabel = 'Menunggu Diproses Warehouse';
    } elseif ($transaksiOffline->petugas_id !== null) {
        $statusLabel = 'Diproses Warehouse';
    }

    $showWaitingVerificationAlert = str_starts_with($statusLabel, 'Menunggu Verifikasi');
@endphp
<div class="space-y-5">
    @php
        $fromPurchaseOrder = $fromPurchaseOrder ?? false;
        $backLabel = $fromPurchaseOrder ? 'Purchase Order' : 'Transaksi Offline';
        $backRoute = $fromPurchaseOrder ? route('speedshop.stock-in.pembelian-mo.index') : route('speedshop.stock-in.transaksi-offline.index');
    @endphp
    <nav class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('speedshop.dashboard') }}" class="transition hover:text-gray-700">Home</a>
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('speedshop.stock-in.pembelian-mo.index') }}" class="transition hover:text-gray-700">Stock IN</a>
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ $backRoute }}" class="transition hover:text-gray-700">{{ $backLabel }}</a>
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <span class="font-medium text-gray-900">{{ $fromPurchaseOrder && !empty($purchaseOrder?->kode) ? $purchaseOrder->kode : $transaksiOffline->no_transaksi }}</span>
    </nav>


    <div class="grid grid-cols-1 gap-5 lg:grid-cols-12">
        <div class="space-y-5 lg:col-span-8">
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white">
                <div class="flex items-start justify-between border-b border-gray-100 px-5 py-4">
                    <div>
                        <h2 class="text-[20px] font-semibold text-gray-900">{{ $fromPurchaseOrder ? 'Informasi Purchase Order' : 'Informasi Transaksi Offline' }}</h2>
                        <p class="mt-1 text-[13px] text-gray-500">{{ $fromPurchaseOrder ? 'Pengajuan ke warehouse untuk pengiriman stok ke Speedshop' : 'Detail penjualan ke Speedshop, Reseller, atau Umum' }}</p>
                    </div>
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-red-50 text-red-600">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    </div>
                </div>

                <div class="space-y-4 px-5 py-5">
                <div class="flex flex-wrap items-end gap-3">
                    <div class="min-w-[200px] flex-1 max-w-md">
                        <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">No. Invoice</label>
                        <div class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm font-medium text-gray-900 break-all">{{ $fromPurchaseOrder && !empty($purchaseOrder?->kode) ? $purchaseOrder->kode : $transaksiOffline->no_transaksi }}</div>
                    </div>
                    <div class="w-44">
                        <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Tanggal</label>
                        <div class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700">{{ $transaksiOffline->created_at->format('d M Y H:i') }}</div>
                    </div>
                    <div class="flex-1 min-w-[180px]">
                        <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Dibuat oleh</label>
                        <div class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700">{{ $transaksiOffline->user?->name ?? '—' }}</div>
                    </div>
                </div>

                <div>
                    <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Tujuan / Toko</label>
                    @php
                        $payLabel = null;
                        if ($transaksiOffline->jenis_pembayaran) {
                            $payLabel = match(strtolower((string) $transaksiOffline->jenis_pembayaran)) {
                                'cash' => 'Cash',
                                'transfer' => 'Transfer',
                                'qris' => 'Qris',
                                'edc' => 'EDC',
                                default => $transaksiOffline->jenis_pembayaran,
                            };
                        }
                    @endphp
                    <div class="rounded-xl border border-gray-200 bg-[#f7f4f5]">
                        <div class="flex items-center gap-3 border-b border-gray-200 px-4 py-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand text-brand-ink shadow-md shadow-brand/35">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                            </div>
                            <div>
                                <div class="text-base font-semibold text-gray-900">{{ $transaksiOffline->nama_toko ?: '-' }}</div>
                                <div class="text-[11px] uppercase tracking-wide text-gray-500">Identitas Tujuan</div>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-1 px-4 py-3 sm:grid-cols-3">
                            <div>
                                <div class="text-[11px] font-medium uppercase tracking-wide text-gray-500">No. HP</div>
                                <div class="mt-0.5 text-sm font-semibold text-gray-900">{{ $transaksiOffline->no_hp ?: '—' }}</div>
                            </div>
                            <div>
                                <div class="text-[11px] font-medium uppercase tracking-wide text-gray-500">Pembayaran</div>
                                <div class="mt-0.5 text-sm font-semibold text-gray-900">{{ $payLabel ?: '—' }}</div>
                            </div>
                            <div>
                                <div class="text-[11px] font-medium uppercase tracking-wide text-gray-500">Petugas</div>
                                <div class="mt-0.5 text-sm font-semibold text-gray-900">{{ $transaksiOffline->petugas->name ?? '—' }}</div>
                            </div>
                            <div class="sm:col-span-3">
                                <div class="text-[11px] font-medium uppercase tracking-wide text-gray-500">Alamat</div>
                                <div class="mt-0.5 text-sm font-semibold text-gray-900">{{ $transaksiOffline->alamat ?: '—' }}</div>
                            </div>
                </div>
                </div>
                </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <div>
                        <h2 class="text-[20px] font-semibold text-gray-900">Daftar Produk</h2>
                        <p class="mt-1 text-[13px] text-gray-500">Item yang diajukan dalam transaksi ini</p>
                    </div>
                    <span class="inline-flex items-center rounded-full border border-red-200 bg-red-50 px-3 py-1 text-xs font-semibold text-red-600">{{ $transaksiOffline->items->count() }} item</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="bg-[#f7f3f4]">
                                <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No.</th>
                                <th class="min-w-[120px] px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Kode Produk</th>
                                <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Nama Supplier</th>
                                <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Nama Produk</th>
                                <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-center">Qty Diminta</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($transaksiOffline->items as $i => $item)
                            <tr class="transition hover:bg-[#fcfafb]">
                                <td class="px-5 py-4 text-gray-500">{{ $i + 1 }}</td>
                                <td class="min-w-[120px] px-5 py-4 font-semibold text-gray-900 whitespace-nowrap">{{ $item->product->kode_produk ?? '-' }}</td>
                                <td class="px-5 py-4 text-gray-700">{{ $supplierByProduct[$item->product_id]['nama_supplier'] ?? '—' }}</td>
                                <td class="px-5 py-4 text-gray-700">{{ $item->product->nama_produk ?? '-' }}</td>
                                <td class="px-5 py-4 text-center">
                                    <span class="inline-flex items-center rounded-lg bg-green-100 px-3 py-1 text-sm font-bold text-green-700">{{ $item->qty }}</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="space-y-5 lg:col-span-4">
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white">
                <div class="border-b border-gray-100 px-5 py-4">
                    <h2 class="text-[20px] font-semibold text-gray-900">Ringkasan</h2>
                </div>

                <div class="space-y-2 px-5 py-3">
                    <div class="flex items-center justify-between rounded-lg border border-blue-100 bg-blue-50 px-4 py-3">
                        <span class="text-sm font-medium text-gray-700">Total Item</span>
                        <span class="text-sm font-bold text-gray-900">{{ $transaksiOffline->items->count() }} item</span>
                    </div>
                </div>
                <div class="space-y-2 border-t border-gray-100 px-5 py-4">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">Total Qty</span>
                        <span class="font-semibold text-gray-900">{{ number_format($transaksiOffline->items->sum('qty')) }} unit</span>
                    </div>
                    <div class="flex items-center justify-between rounded-xl bg-gradient-to-r from-red-600 to-red-700 px-4 py-3">
                        <span class="text-sm font-medium text-red-100">Tujuan</span>
                        <span class="font-semibold text-white">{{ ucfirst($transaksiOffline->tujuan) }}</span>
                    </div>
                </div>

                @php
                    $isPoFromSpeedshopFlow = $transaksiOffline->tujuan === 'speedshop' && $transaksiOffline->speedshop_purchase_order_id;
                    $isWarehouseApproved = $transaksiOffline->warehouse_po_reviewed_at !== null && $transaksiOffline->warehouse_po_review_rejected_at === null;
                    $isWarehouseRejected = $transaksiOffline->warehouse_po_review_rejected_at !== null;
                    $isProcessed = $transaksiOffline->petugas_id !== null;
                    $isSpeedshopApproved = $transaksiOffline->isVerified();
                    $isSpeedshopRejected = $transaksiOffline->isRejected();
                    $isFinished = $isSpeedshopApproved || $isSpeedshopRejected || $isWarehouseRejected || $warehousePoClosed;

                    $currentReview = $isPoFromSpeedshopFlow && ! $isWarehouseApproved && ! $isWarehouseRejected;
                    $currentProcessed = $isPoFromSpeedshopFlow && $isWarehouseApproved && ! $isProcessed && ! $isWarehouseRejected;
                    $currentSpeedshopDecision = $isPoFromSpeedshopFlow && $isProcessed && ! $isSpeedshopApproved && ! $isSpeedshopRejected && ! $warehousePoClosed && ! $isWarehouseRejected;
                    $currentFinished = $isPoFromSpeedshopFlow && $isFinished && ! $currentReview && ! $currentProcessed && ! $currentSpeedshopDecision;

                    $step2Label = $isWarehouseRejected
                        ? 'Ditolak'
                        : ($isWarehouseApproved ? 'Diterima' : 'Diterima/Ditolak');

                    $stepsPoFlow = [
                        ['label' => 'Diajukan', 'done' => true, 'err' => false, 'current' => false],
                        ['label' => 'Verifikasi Warehouse', 'done' => $isWarehouseApproved || $isWarehouseRejected, 'err' => $isWarehouseRejected, 'current' => $currentReview],
                        ['label' => 'Diproses', 'done' => $isProcessed || $isWarehouseRejected || $warehousePoClosed, 'err' => $isWarehouseRejected, 'current' => $currentProcessed],
                        ['label' => 'Verifikasi Speedshop', 'done' => $isSpeedshopApproved || $isSpeedshopRejected || $warehousePoClosed || $isWarehouseRejected, 'err' => $isSpeedshopRejected || $isWarehouseRejected, 'current' => $currentSpeedshopDecision],
                        ['label' => 'Selesai', 'done' => $isFinished, 'err' => $isSpeedshopRejected || $isWarehouseRejected, 'current' => $currentFinished],
                    ];
                @endphp

                <div class="border-t border-gray-100 px-5 py-4">
                    <label class="mb-3 block text-[11px] font-semibold uppercase tracking-wider text-gray-500">Alur Persetujuan</label>
                @if($isPoFromSpeedshopFlow)
                    <div class="flex items-start justify-between">
                        @foreach($stepsPoFlow as $idx => $step)
                        <div class="flex flex-col items-center" style="width: {{ 100 / count($stepsPoFlow) }}%">
                            <div class="relative flex w-full items-center justify-center">
                                @if($idx > 0)
                                <div class="absolute right-1/2 h-0.5 w-full @if(($step['done'] ?? false) || ($step['err'] ?? false) || (!empty($step['current']))) {{ ($step['err'] ?? false) ? 'bg-red-400' : 'bg-green-400' }} @else bg-gray-200 @endif"></div>
                                @endif
                                @if($idx < count($stepsPoFlow) - 1)
                                @php $nextPo = $stepsPoFlow[$idx + 1]; @endphp
                                <div class="absolute left-1/2 h-0.5 w-full @if(($nextPo['done'] ?? false) || ($nextPo['err'] ?? false) || (!empty($nextPo['current']))) {{ ($nextPo['err'] ?? false) ? 'bg-red-400' : 'bg-green-400' }} @else bg-gray-200 @endif"></div>
                                @endif

                                @if(($step['done'] ?? false) || ($step['err'] ?? false))
                                    @if($step['err'] ?? false)
                                    <div class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full bg-brand shadow-sm"><svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></div>
                                    @else
                                    <div class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full bg-green-500 shadow-sm"><svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></div>
                                    @endif
                                @elseif(!empty($step['current']))
                                    <div class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full border-2 border-amber-400 bg-amber-50"><div class="h-2.5 w-2.5 rounded-full bg-amber-400 animate-pulse"></div></div>
                                @else
                                    <div class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full bg-gray-200"><svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></div>
                                @endif
                            </div>
                            <p class="mt-2 text-center text-xs font-medium @if($step['done'] ?? false) {{ ($step['err'] ?? false) ? 'text-red-600' : 'text-green-600' }} @elseif(!empty($step['current'])) text-amber-600 @else text-gray-400 @endif">{{ $step['label'] }}</p>
                        </div>
                        @endforeach
                    </div>

                    @if($isWarehouseApproved && $transaksiOffline->warehouse_po_reviewed_at)
                    <p class="mt-3 text-center text-xs text-gray-500">Diterima {{ $transaksiOffline->warehouse_po_reviewed_at->format('d M Y H:i') }}@if($transaksiOffline->warehousePoReviewedBy) · {{ $transaksiOffline->warehousePoReviewedBy->name }}@endif</p>
                    @elseif($isWarehouseRejected && $transaksiOffline->warehouse_po_review_rejected_at)
                    <p class="mt-3 text-center text-xs text-gray-500">Ditolak {{ $transaksiOffline->warehouse_po_review_rejected_at->format('d M Y H:i') }}@if($transaksiOffline->warehousePoReviewRejectedBy) · {{ $transaksiOffline->warehousePoReviewRejectedBy->name }}@endif</p>
                    @elseif($warehousePoClosed)
                    <p class="mt-3 text-center text-xs text-gray-500">
                        Selesai {{ $transaksiOffline->warehouse_po_closed_at?->format('d M Y H:i') }}
                        @if($transaksiOffline->warehousePoClosedBy) · {{ $transaksiOffline->warehousePoClosedBy->name }} @endif
                    </p>
                    @elseif($isProcessed && $transaksiOffline->petugas)
                    <p class="mt-3 text-center text-xs text-gray-500">Diproses oleh {{ $transaksiOffline->petugas->name }}</p>
                    @elseif($transaksiOffline->isVerified() && $transaksiOffline->verified_at)
                    <p class="mt-3 text-center text-xs text-gray-500">Selesai (diverifikasi Speedshop) {{ $transaksiOffline->verified_at->format('d M Y H:i') }}</p>
                    @elseif($transaksiOffline->isRejected() && $transaksiOffline->rejected_at)
                    <p class="mt-3 text-center text-xs text-gray-500">Selesai (ditolak Speedshop) {{ $transaksiOffline->rejected_at->format('d M Y H:i') }}@if($transaksiOffline->rejectedByUser) · {{ $transaksiOffline->rejectedByUser->name }}@endif</p>
                    @endif
                @else
                    @php
                        $isSpeedshopNonPo = $transaksiOffline->tujuan === 'speedshop';
                        $offlineSelesaiGudang = ! $isSpeedshopNonPo && $transaksiOffline->petugas_id !== null;
                        $secondStepLabel = $isSpeedshopNonPo ? 'Menunggu Verifikasi' : 'Diproses';
                        $secondStepDone = $offlineSelesaiGudang
                            || $transaksiOffline->isVerified()
                            || $transaksiOffline->isRejected();
                        $finalDone = $offlineSelesaiGudang
                            || $transaksiOffline->isVerified()
                            || $transaksiOffline->isRejected();
                        $steps = [
                            ['label' => 'Diajukan', 'done' => true],
                            ['label' => $secondStepLabel, 'done' => $secondStepDone],
                            ['label' => ($transaksiOffline->isRejected() ? 'Ditolak' : 'Selesai'), 'done' => $finalDone],
                        ];
                        $isRejectedStep = $transaksiOffline->isRejected();
                        $currentStep = $transaksiOffline->isVerified() || $transaksiOffline->isRejected() || $offlineSelesaiGudang
                            ? 2
                            : 1;
                    @endphp
                    <div class="flex items-start justify-between">
                        @foreach($steps as $idx => $step)
                        <div class="flex flex-col items-center" style="width: {{ 100 / count($steps) }}%">
                            <div class="relative flex w-full items-center justify-center">
                                @if($idx > 0)
                                <div class="absolute right-1/2 h-0.5 w-full {{ $step['done'] ? ($isRejectedStep && $idx === count($steps) - 1 ? 'bg-red-400' : 'bg-green-400') : 'bg-gray-200' }}"></div>
                                @endif
                                @if($idx < count($steps) - 1)
                                <div class="absolute left-1/2 h-0.5 w-full {{ $steps[$idx + 1]['done'] ? ($isRejectedStep ? 'bg-red-400' : 'bg-green-400') : 'bg-gray-200' }}"></div>
                                @endif
                                @if($step['done'])
                                    @if($isRejectedStep && $idx === count($steps) - 1)
                                    <div class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full bg-brand shadow-sm"><svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></div>
                                    @else
                                    <div class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full bg-green-500 shadow-sm"><svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></div>
                                    @endif
                                @elseif($idx === $currentStep)
                                    <div class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full border-2 border-amber-400 bg-amber-50"><div class="h-2.5 w-2.5 rounded-full bg-amber-400 animate-pulse"></div></div>
                                @else
                                    <div class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full bg-gray-200"><svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></div>
                                @endif
                            </div>
                            <p class="mt-2 text-center text-xs font-medium {{ $step['done'] ? ($isRejectedStep && $idx === count($steps) - 1 ? 'text-red-600' : 'text-green-600') : ($idx === $currentStep ? 'text-amber-600' : 'text-gray-400') }}">{{ $step['label'] }}</p>
                        </div>
                        @endforeach
                    </div>
                @endif
                </div>

                @if($warehousePoReviewRejected && $transaksiOffline->warehouse_po_review_rejected_reason)
                <div class="mt-5 px-5 pb-5">
                    <div class="rounded-lg border border-red-200 bg-red-50 p-3">
                        <p class="text-xs font-semibold text-red-600">Alasan Penolakan</p>
                        <p class="mt-0.5 text-sm text-red-700">{{ $transaksiOffline->warehouse_po_review_rejected_reason }}</p>
                    </div>
                </div>
                @endif

                @if($warehousePoClosed && $transaksiOffline->warehouse_po_closed_reason)
                <div class="mt-5 px-5 pb-5">
                    <div class="rounded-lg border border-rose-200 bg-rose-50 p-3">
                        <p class="text-xs font-semibold text-rose-700">Alasan Pembatalan</p>
                        <p class="mt-0.5 text-sm text-rose-700">{{ $transaksiOffline->warehouse_po_closed_reason }}</p>
                    </div>
                </div>
                @endif

                @if($transaksiOffline->isRejected())
                <div class="mt-5 px-5 pb-5">
                    <div class="rounded-lg border border-red-200 bg-red-50 p-3">
                        <p class="text-xs font-semibold text-red-800">Transaksi ditolak</p>
                        <p class="mt-1 text-xs text-red-700">Stok dikembalikan ke warehouse pada {{ $transaksiOffline->rejected_at->format('d M Y H:i') }}@if($transaksiOffline->rejectedByUser) — {{ $transaksiOffline->rejectedByUser->name }}@endif.</p>
                        @if($transaksiOffline->rejected_reason)
                        <p class="mt-2 text-xs text-red-800"><span class="font-semibold">Alasan:</span> {{ $transaksiOffline->rejected_reason }}</p>
                        @endif
                    </div>
                </div>
                @elseif(!$transaksiOffline->isVerified())
                    @if($transaksiOffline->isAwaitingWarehousePoProcessing())
                    @elseif(! $transaksiOffline->isAwaitingWarehousePoProcessing() && (auth()->user()->isManager() || auth()->user()->isSpeedshopStaf()))
                        <div class="mt-5 space-y-3 px-5 pb-5">
                            <button type="button" onclick="verifyTransaksi()" id="btn-verify" class="flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-600 px-5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/25 transition hover:brightness-105 disabled:cursor-not-allowed disabled:opacity-50">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                Approve - Setujui
                            </button>
                            <button type="button" onclick="rejectTransaksi()" id="btn-reject" class="flex h-10 w-full items-center justify-center gap-2 rounded-xl border border-red-200 bg-red-50 px-5 text-sm font-medium text-red-600 transition hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-50">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                Reject - Tolak
                            </button>
                        </div>
                    @endif
                @else
                    <div class="mt-5 px-5 pb-5">
                        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3">
                            <p class="text-xs font-semibold text-emerald-800">Sudah diverifikasi</p>
                            <p class="mt-1 text-xs text-emerald-700">Stok telah masuk pada {{ $transaksiOffline->verified_at->format('d M Y H:i') }}</p>
                        </div>
                    </div>
                @endif
            </div>

            @if($showCetakPdf && !($fromPurchaseOrder ?? false))
            <a href="{{ route('speedshop.stock-in.transaksi-offline.pdf', $transaksiOffline) }}" target="_blank" class="btn-export-pdf flex h-11 w-full items-center justify-center gap-2">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Cetak PDF
            </a>
            @endif

            @if($showWaitingVerificationAlert)
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                <div class="flex gap-3">
                    <svg class="h-5 w-5 flex-shrink-0 text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <div>
                        <p class="text-sm font-semibold text-amber-800">Menunggu Verifikasi</p>
                        <p class="mt-1 text-xs text-amber-700">Transaksi masih menunggu verifikasi dari pihak terkait. Cetak PDF tersedia setelah status selesai.</p>
                    </div>
                </div>
            </div>
            @endif

            <a href="{{ route('speedshop.stock-in.transaksi-offline.index') }}" class="flex h-10 w-full items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-5 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Kembali ke Daftar
            </a>
        </div>
    </div>
</div>
@if(!$transaksiOffline->isVerified() && !$transaksiOffline->isRejected() && (auth()->user()->isManager() || auth()->user()->isSpeedshopStaf()) && !$transaksiOffline->isAwaitingWarehousePoProcessing())
@push('scripts')
<script>
const verifyUrl = '{{ route("speedshop.stock-in.transaksi-offline.verify", $transaksiOffline) }}';
const rejectUrl = '{{ route("speedshop.stock-in.transaksi-offline.reject", $transaksiOffline) }}';

async function verifyTransaksi() {
    if (!window.Swal) {
        if (!confirm('Verifikasi transaksi ini?')) return;
    }
    const result = await Swal.fire({
        title: 'Verifikasi Transaksi?',
        text: 'Stok produk akan ditambahkan ke stok Speedshop Anda. Tindakan ini tidak dapat dibatalkan.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Verifikasi!',
        cancelButtonText: 'Batal'
    });
    if (!result.isConfirmed) return;

    const btn = document.getElementById('btn-verify');
    if (btn) { btn.disabled = true; btn.classList.add('opacity-70'); }

    try {
        const res = await fetch(verifyUrl, {
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

async function rejectTransaksi() {
    if (!window.Swal) {
        if (!confirm('Tolak transaksi ini?')) return;
    }
    const result = await Swal.fire({
        title: 'Tolak transaksi ini?',
        html: '<p class="text-sm text-gray-600 mb-3">Seluruh qty akan dikembalikan ke stok warehouse. Tindakan ini tidak dapat dibatalkan.</p>',
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

    const btn = document.getElementById('btn-reject');
    const btnV = document.getElementById('btn-verify');
    if (btn) { btn.disabled = true; btn.classList.add('opacity-70'); }
    if (btnV) { btnV.disabled = true; btnV.classList.add('opacity-70'); }

    try {
        const res = await fetch(rejectUrl, {
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
