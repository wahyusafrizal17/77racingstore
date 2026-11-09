@extends('layouts.speedshop')

@section('title', 'Detail Purchase Order — ' . ($purchaseOrder->invoice_no ?: $purchaseOrder->kode))

@section('content')
@php
    $ms = $purchaseOrder->mo_status;
    $sendFailed = $ms === \App\Models\SpeedshopPurchaseOrder::MO_STATUS_SEND_FAILED;
    $whRejected = $ms === \App\Models\SpeedshopPurchaseOrder::MO_STATUS_REJECTED_BY_WAREHOUSE;
    $cancelled = $ms === \App\Models\SpeedshopPurchaseOrder::MO_STATUS_CANCELLED;
    $partnerRejected = $ms === \App\Models\SpeedshopPurchaseOrder::MO_STATUS_REJECTED_BY_PARTNER;
    $shipped = in_array($ms, [\App\Models\SpeedshopPurchaseOrder::MO_STATUS_SHIPPED, \App\Models\SpeedshopPurchaseOrder::MO_STATUS_RECEIVED], true);
    $received = $ms === \App\Models\SpeedshopPurchaseOrder::MO_STATUS_RECEIVED && $purchaseOrder->received_at !== null;
    $awaitingStockIn = $ms === \App\Models\SpeedshopPurchaseOrder::MO_STATUS_RECEIVED && $purchaseOrder->received_at === null;

    $canCancel = in_array($ms, [\App\Models\SpeedshopPurchaseOrder::MO_STATUS_SEND_FAILED, \App\Models\SpeedshopPurchaseOrder::MO_STATUS_SUBMITTED], true);
    $canReceive = $canDecide && ($ms === \App\Models\SpeedshopPurchaseOrder::MO_STATUS_SHIPPED || $awaitingStockIn);
    $canReject = $canDecide && $ms === \App\Models\SpeedshopPurchaseOrder::MO_STATUS_SHIPPED;

    $badgeClass = match (true) {
        $received => 'bg-emerald-100 text-emerald-800',
        $whRejected, $cancelled, $partnerRejected, $sendFailed => 'bg-red-100 text-red-800',
        $ms === \App\Models\SpeedshopPurchaseOrder::MO_STATUS_SHIPPED => 'bg-blue-100 text-blue-800',
        default => 'bg-amber-100 text-amber-800',
    };

    // Stepper: Diajukan → Diterima warehouse → Dikirim → Selesai
    $failedAt = $whRejected ? 1 : (($cancelled && ! $shipped) ? 1 : ($partnerRejected ? 3 : null));
    $reached = match (true) {
        $received, $partnerRejected => 3,
        $shipped => 2,
        $ms === \App\Models\SpeedshopPurchaseOrder::MO_STATUS_APPROVED => 1,
        $whRejected, $cancelled => 1,
        $sendFailed => -1,
        default => 0,
    };
    $steps = [
        'Diajukan',
        $whRejected ? 'Ditolak WH' : ($cancelled ? 'Dibatalkan' : 'Diterima WH'),
        'Dikirim',
        $partnerRejected ? 'Ditolak' : 'Selesai',
    ];

    $showQtyApproved = $purchaseOrder->items->contains(fn ($i) => $i->qty_approved !== null);
    $totalHarga = $purchaseOrder->items->sum(fn ($i) => (int) ($i->harga_satuan ?? 0) * (int) ($i->qty_approved ?? $i->qty));
    $tagihan = $purchaseOrder->mo_tagihan;
@endphp

<div class="space-y-6">
    <nav class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('speedshop.dashboard') }}" class="transition hover:text-gray-700">Home</a>
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('speedshop.stock-in.pembelian-mo.index') }}" class="transition hover:text-gray-700">Purchase Order</a>
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <span class="font-medium text-gray-900">{{ $purchaseOrder->kode }}</span>
    </nav>

    @if($syncError)
    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        <span class="font-semibold">Status belum bisa diperbarui dari Modifikasi Ori.</span>
        <span class="mt-1 block text-amber-800">{{ $syncError }} — menampilkan data terakhir{{ $purchaseOrder->mo_synced_at ? ' ('.$purchaseOrder->mo_synced_at->format('d M Y H:i').')' : '' }}.</span>
    </div>
    @endif

    @if($sendFailed)
    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
        <span class="font-semibold">PO belum terkirim ke warehouse Modifikasi Ori.</span>
        @if($purchaseOrder->mo_sync_error)
            <span class="mt-1 block text-red-800">{{ $purchaseOrder->mo_sync_error }}</span>
        @endif
        <span class="mt-1 block text-red-800">Klik "Kirim Ulang" — aman diulang, PO tidak akan tercatat ganda.</span>
    </div>
    @endif

    @if(($whRejected || $cancelled || $partnerRejected) && $purchaseOrder->mo_reason)
    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
        <span class="font-semibold">{{ $purchaseOrder->moStatusLabel() }}.</span>
        <span class="mt-1 block text-red-800">{{ $purchaseOrder->mo_reason }}</span>
    </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">Purchase Order ke Modifikasi Ori</h1>
                        <p class="mt-1 text-sm text-gray-500">Pengajuan stok ke {{ $warehouseName ?: 'warehouse Modifikasi Ori' }} via API</p>
                    </div>
                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $badgeClass }}">{{ $purchaseOrder->moStatusLabel() }}</span>
                </div>

                <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-gray-600">Kode PO (77 Racing)</label>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 font-mono text-sm font-semibold text-gray-900">{{ $purchaseOrder->kode }}</div>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-gray-600">No. PO di Modifikasi Ori</label>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 font-mono text-sm text-gray-900">
                            {{ $purchaseOrder->mo_kode ?: '—' }}@if($purchaseOrder->mo_no_transaksi) <span class="text-gray-500">· {{ $purchaseOrder->mo_no_transaksi }}</span>@endif
                        </div>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-gray-600">Tanggal</label>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700">{{ $purchaseOrder->created_at->format('d M Y H:i') }}</div>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-gray-600">Dibuat oleh</label>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700">{{ $purchaseOrder->creator?->name ?? '—' }}</div>
                    </div>
                </div>

                @if($purchaseOrder->catatan)
                <div class="mt-4">
                    <label class="mb-1.5 block text-xs font-semibold text-gray-600">Catatan</label>
                    <div class="whitespace-pre-wrap rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-700">{{ $purchaseOrder->catatan }}</div>
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
                                <th class="px-5 py-3.5 text-xs font-bold uppercase tracking-wider text-gray-500">Nama Produk</th>
                                <th class="px-5 py-3.5 text-right text-xs font-bold uppercase tracking-wider text-gray-500">Harga</th>
                                <th class="px-5 py-3.5 text-center text-xs font-bold uppercase tracking-wider text-gray-500">Qty Diminta</th>
                                @if($showQtyApproved)
                                <th class="px-5 py-3.5 text-center text-xs font-bold uppercase tracking-wider text-gray-500">Qty Dikirim</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($purchaseOrder->items as $i => $item)
                            <tr class="transition hover:bg-gray-50">
                                <td class="px-5 py-4 text-gray-500">{{ $i + 1 }}</td>
                                <td class="px-5 py-4 font-medium text-gray-900">{{ $item->product->kode_produk ?? '—' }}</td>
                                <td class="px-5 py-4 text-gray-700">{{ $item->product->nama_produk ?? '—' }}</td>
                                <td class="px-5 py-4 text-right text-gray-700">{{ $item->harga_satuan !== null ? 'Rp '.number_format((int) $item->harga_satuan, 0, ',', '.') : '—' }}</td>
                                <td class="px-5 py-4 text-center font-semibold text-gray-900">{{ $item->qty }}</td>
                                @if($showQtyApproved)
                                <td class="px-5 py-4 text-center font-semibold {{ (int) $item->qty_approved < (int) $item->qty ? 'text-amber-700' : 'text-emerald-700' }}">{{ $item->qty_approved ?? '—' }}</td>
                                @endif
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($showQtyApproved)
                <p class="mt-3 text-xs text-gray-500">Qty dikirim ditentukan warehouse Modifikasi Ori sesuai stok. Stok masuk ke Speedshop sesuai qty dikirim saat Anda menekan "Terima Barang".</p>
                @endif
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold text-gray-900">Ringkasan</h2>
                    @unless($sendFailed)
                    <form method="POST" action="{{ route('speedshop.stock-in.pembelian-mo.purchase-order.mo-refresh', $purchaseOrder) }}">
                        @csrf
                        <button type="submit" class="text-xs font-semibold text-gray-500 transition hover:text-gray-800" title="Perbarui status dari Modifikasi Ori">↻ Perbarui</button>
                    </form>
                    @endunless
                </div>
                <div class="mt-4 space-y-3">
                    <div class="flex items-center justify-between rounded-lg bg-gray-50 px-4 py-3">
                        <span class="text-sm font-medium text-gray-700">Total Qty Diminta</span>
                        <span class="text-sm font-bold text-gray-900">{{ (int) $purchaseOrder->items->sum('qty') }} unit</span>
                    </div>
                    @if($showQtyApproved)
                    <div class="flex items-center justify-between rounded-lg bg-gray-50 px-4 py-3">
                        <span class="text-sm font-medium text-gray-700">Total Qty Dikirim</span>
                        <span class="text-sm font-bold text-gray-900">{{ (int) $purchaseOrder->items->sum('qty_approved') }} unit</span>
                    </div>
                    @endif
                    <div class="flex items-center justify-between rounded-lg bg-gray-50 px-4 py-3">
                        <span class="text-sm font-medium text-gray-700">Estimasi Nilai</span>
                        <span class="text-sm font-bold text-gray-900">Rp {{ number_format((int) $totalHarga, 0, ',', '.') }}</span>
                    </div>
                    @if(is_array($tagihan))
                    <div class="rounded-lg border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                        <p class="font-semibold">Tagihan {{ $tagihan['kode'] ?? '' }}</p>
                        <p class="mt-0.5">Rp {{ number_format((int) ($tagihan['total_nominal'] ?? 0), 0, ',', '.') }} · {{ str_replace('_', ' ', (string) ($tagihan['status'] ?? '')) }}</p>
                    </div>
                    @endif
                </div>

                <div class="mt-5">
                    <label class="mb-3 block text-sm font-semibold text-gray-700">Status</label>
                    <div class="flex items-start justify-between">
                        @foreach($steps as $idx => $label)
                        @php
                            $isErr = $failedAt === $idx;
                            $isDone = $idx <= $reached && ! $isErr;
                            $isCurrent = ! $isDone && ! $isErr && $idx === $reached + 1 && $failedAt === null && ! $received;
                        @endphp
                        <div class="flex flex-col items-center" style="width: 25%">
                            @if($isErr)
                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-brand shadow-sm"><svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></div>
                            @elseif($isDone)
                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-green-500 shadow-sm"><svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></div>
                            @elseif($isCurrent)
                                <div class="flex h-8 w-8 items-center justify-center rounded-full border-2 border-yellow-400 bg-yellow-50"><div class="h-2.5 w-2.5 animate-pulse rounded-full bg-yellow-400"></div></div>
                            @else
                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-200"><svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></div>
                            @endif
                            <p class="mt-2 text-center text-xs font-medium {{ $isErr ? 'text-red-600' : ($isDone ? 'text-green-600' : ($isCurrent ? 'text-yellow-600' : 'text-gray-400')) }}">{{ $label }}</p>
                        </div>
                        @endforeach
                    </div>
                    @if($received)
                    <p class="mt-3 text-center text-xs text-gray-500">Diterima {{ $purchaseOrder->received_at->format('d M Y H:i') }}@if($purchaseOrder->receiver) · {{ $purchaseOrder->receiver->name }}@endif</p>
                    @endif
                    @if($purchaseOrder->mo_synced_at)
                    <p class="mt-1 text-center text-[11px] text-gray-400">Sinkron terakhir {{ $purchaseOrder->mo_synced_at->format('d M Y H:i') }}</p>
                    @endif
                </div>

                @if($ms === \App\Models\SpeedshopPurchaseOrder::MO_STATUS_SUBMITTED)
                <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs leading-relaxed text-amber-800">
                    <p class="text-sm font-semibold text-amber-900">Menunggu warehouse</p>
                    Warehouse Modifikasi Ori akan menerima/menolak pengajuan, lalu memproses pengiriman.
                </div>
                @elseif($ms === \App\Models\SpeedshopPurchaseOrder::MO_STATUS_APPROVED)
                <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-xs leading-relaxed text-emerald-800">
                    <p class="text-sm font-semibold text-emerald-900">Pengajuan diterima warehouse</p>
                    Menunggu warehouse memproses pengiriman barang.
                </div>
                @elseif($ms === \App\Models\SpeedshopPurchaseOrder::MO_STATUS_SHIPPED)
                <div class="mt-5 rounded-xl border border-blue-200 bg-blue-50 p-4 text-xs leading-relaxed text-blue-800">
                    <p class="text-sm font-semibold text-blue-900">Barang dikirim warehouse</p>
                    Cek barang yang datang. Tekan "Terima Barang" agar stok masuk ke Speedshop, atau "Tolak" bila barang tidak sesuai (stok kembali ke warehouse Modifikasi Ori).
                </div>
                @elseif($awaitingStockIn)
                <div class="mt-5 rounded-xl border border-blue-200 bg-blue-50 p-4 text-xs leading-relaxed text-blue-800">
                    <p class="text-sm font-semibold text-blue-900">Penerimaan sudah dikonfirmasi di Modifikasi Ori</p>
                    Stok lokal belum tercatat. Tekan "Terima Barang" untuk mencatat stok masuk.
                </div>
                @endif

                <div class="mt-5 space-y-3">
                    @if($sendFailed)
                    <form method="POST" action="{{ route('speedshop.stock-in.pembelian-mo.purchase-order.mo-resend', $purchaseOrder) }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-brand px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:opacity-90">Kirim Ulang ke Modifikasi Ori</button>
                    </form>
                    @endif

                    @if($canReceive)
                    <form method="POST" action="{{ route('speedshop.stock-in.pembelian-mo.purchase-order.mo-receive', $purchaseOrder) }}" onsubmit="return moConfirm(event, 'Terima barang?', 'Stok Speedshop akan bertambah sesuai qty dikirim dan tagihan dibuat di Modifikasi Ori.')">
                        @csrf
                        <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-green-500 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-green-600">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            Terima Barang
                        </button>
                    </form>
                    @endif

                    @if($canReject)
                    <form method="POST" action="{{ route('speedshop.stock-in.pembelian-mo.purchase-order.mo-reject', $purchaseOrder) }}" onsubmit="return moAskReason(event, 'rejected_reason', 'Tolak penerimaan barang?', 'Alasan penolakan (wajib)')">
                        @csrf
                        <input type="hidden" name="rejected_reason">
                        <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl border border-gray-200 px-5 py-3 text-sm font-medium text-gray-600 transition hover:bg-gray-50">Tolak Barang</button>
                    </form>
                    @endif

                    @if($canCancel)
                    <form method="POST" action="{{ route('speedshop.stock-in.pembelian-mo.purchase-order.mo-cancel', $purchaseOrder) }}" onsubmit="return moAskReason(event, 'cancelled_reason', 'Batalkan Purchase Order?', 'Alasan pembatalan (wajib)')">
                        @csrf
                        <input type="hidden" name="cancelled_reason">
                        <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl border border-red-200 px-5 py-3 text-sm font-medium text-red-600 transition hover:bg-red-50">Batalkan PO</button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
async function moConfirm(event, title, text) {
    const form = event.target;
    if (form.dataset.confirmed === '1') return true;
    event.preventDefault();
    const r = await Swal.fire({ title, text, icon: 'question', showCancelButton: true, confirmButtonColor: '#ef4444', cancelButtonColor: '#6b7280', confirmButtonText: 'Ya', cancelButtonText: 'Batal' });
    if (r.isConfirmed) { form.dataset.confirmed = '1'; form.querySelectorAll('button').forEach(b => b.disabled = true); form.submit(); }
    return false;
}

async function moAskReason(event, field, title, placeholder) {
    const form = event.target;
    event.preventDefault();
    const r = await Swal.fire({
        title, input: 'textarea', inputPlaceholder: placeholder, showCancelButton: true,
        confirmButtonColor: '#ef4444', cancelButtonColor: '#6b7280', confirmButtonText: 'Kirim', cancelButtonText: 'Batal',
        inputValidator: (v) => (!v || !v.trim()) ? 'Alasan wajib diisi.' : undefined,
    });
    if (!r.isConfirmed) return false;
    form.querySelector(`input[name="${field}"]`).value = r.value.trim();
    form.querySelectorAll('button').forEach(b => b.disabled = true);
    form.submit();
    return false;
}
</script>
@endpush
