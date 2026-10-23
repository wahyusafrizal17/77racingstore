@extends('layouts.speedshop')

@php
    $isVerifikasi = request()->routeIs('speedshop.verifikasi.stok-in-supplier*');
    $backUrl = route($isVerifikasi ? 'speedshop.verifikasi.stok-in-supplier.index' : 'speedshop.stock-in.supplier.index');

    $woPanelClass = 'panel-card panel-card-flush overflow-visible ss-dash-enter-child';

    $transaksiStatus = (string) ($transaksi->status ?? 'pending');
    $isRejected = $transaksiStatus === 'rejected';
    $isSelesai = in_array($transaksiStatus, ['approved', 'rejected'], true);

    $currentStep = match ($transaksiStatus) {
        'pending' => 1,
        default => 2,
    };
    $steps = [
        ['label' => 'Diajukan'],
        ['label' => 'Menunggu Verifikasi'],
        ['label' => $isRejected ? 'Ditolak' : 'Disetujui'],
    ];

    $statusBadgeClass = match ($transaksiStatus) {
        'pending' => 'border-amber-200 bg-amber-50 text-amber-700',
        'rejected' => 'border-red-200 bg-red-50 text-red-700',
        default => 'border-emerald-200 bg-emerald-50 text-emerald-700',
    };
    $statusBadgeLabel = match ($transaksiStatus) {
        'pending' => 'Menunggu',
        'rejected' => 'Ditolak',
        'approved' => 'Disetujui',
        default => ucfirst($transaksiStatus),
    };

    $uploadDisk = (string) config('filesystems.upload_disk', 'public');
    $invoiceUrl = $transaksi->invoice_path ? \Illuminate\Support\Facades\Storage::disk($uploadDisk)->url($transaksi->invoice_path) : null;

    $totalQty = (int) $transaksi->items->sum('qty');
    $totalHarga = (float) $transaksi->items->sum(fn ($i) => $i->harga_pembelian * $i->qty);
    $canApproveReject = $transaksi->isPending() && auth()->user()->isAccounting();
@endphp

@section('title', ($isVerifikasi ? 'Verifikasi Stok In Supplier' : 'Detail Stok In Supplier') . ' - ' . $transaksi->kode)

@section('content')
@push('styles')
@include('partials.speedshop.dashboard-load-animations')
@include('partials.speedshop.dashboard-manager-template-styles')
@include('partials.speedshop.work-order-create-styles')
@endpush

<div
    class="ss-dashboard ss-dash-page ss-wo-create"
    x-data="transaksiDetail()"
    x-init="$nextTick(() => { requestAnimationFrame(() => $el.classList.add('is-ready')); })"
>
<div class="ss-dash-content space-y-5">
    <div class="flex flex-col gap-3 ss-dash-enter sm:flex-row sm:items-start sm:justify-between" style="--ss-delay: 0ms">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">{{ $transaksi->kode }}</h1>
                <span class="inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-xs font-semibold {{ $statusBadgeClass }}">{{ $statusBadgeLabel }}</span>
            </div>
            <p class="mt-1 text-[13px] text-gray-500">
                @if($isVerifikasi)
                    Verifikasi stok in supplier · {{ $transaksi->created_at->format('d M Y H:i') }}
                @else
                    Detail stok in supplier · {{ $transaksi->created_at->format('d M Y H:i') }}
                @endif
            </p>
        </div>
        <a href="{{ $backUrl }}" class="btn-secondary shrink-0">← Kembali</a>
    </div>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-12 ss-dash-enter" style="--ss-delay: 80ms">
        <div class="space-y-5 lg:col-span-8">
            <div class="{{ $woPanelClass }}" style="--ss-delay: 120ms; --ss-stagger: 0">
                <div class="flex items-start justify-between border-b border-gray-100 px-5 py-4">
                    <div>
                        <h2 class="text-[20px] font-semibold text-gray-900">Informasi Transaksi</h2>
                        <p class="mt-1 text-[13px] text-gray-500">Detail pengajuan pembelian produk dari supplier master data</p>
                    </div>
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-red-50 text-red-600">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 px-5 py-5 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Kode / Invoice</label>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm font-semibold text-gray-900 break-all">{{ $transaksi->kode }}</div>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Diajukan Oleh</label>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700">{{ $transaksi->user?->name ?? '—' }}</div>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Tanggal</label>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700">{{ $transaksi->created_at->format('d M Y H:i') }}</div>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Jatuh tempo</label>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm font-semibold text-gray-900">{{ $transaksi->tanggal_jatuh_tempo?->translatedFormat('d M Y') ?? '—' }}</div>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Lampiran Invoice</label>
                        @if($invoiceUrl)
                            <a href="{{ $invoiceUrl }}" target="_blank" rel="noopener noreferrer" class="btn-primary inline-flex h-10 items-center gap-2 px-4">
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v12m0 0l-3-3m3 3l3-3m-9 7h12"/></svg>
                                Lihat Invoice
                            </a>
                        @else
                            <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-500">—</div>
                        @endif
                    </div>
                </div>

                @if($transaksi->catatan)
                <div class="px-5 pb-5">
                    <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Catatan</label>
                    <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700 whitespace-pre-line">{{ $transaksi->catatan }}</div>
                </div>
                @endif
            </div>

            <div class="{{ $woPanelClass }}" style="--ss-delay: 120ms; --ss-stagger: 1">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <div>
                        <h2 class="text-[20px] font-semibold text-gray-900">Daftar Item</h2>
                        <p class="mt-1 text-[13px] text-gray-500">Produk dan supplier per baris</p>
                    </div>
                    <span class="wo-badge">{{ $transaksi->items->count() }} item · {{ number_format($totalQty) }} unit</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="bg-[#f7f3f4]">
                                <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No.</th>
                                <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Supplier</th>
                                <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Kode Produk</th>
                                <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Kode Supplier</th>
                                <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Nama Produk</th>
                                <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-right">HPP</th>
                                <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-right">Harga Jual</th>
                                <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-right">Harga Invoice</th>
                                <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-center">Qty</th>
                                <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($transaksi->items as $i => $item)
                            <tr class="transition hover:bg-[#fcfafb]">
                                <td class="px-5 py-4 text-gray-500">{{ $i + 1 }}</td>
                                <td class="px-5 py-4 text-gray-700">{{ $item->supplier?->nama ?? '—' }}</td>
                                <td class="px-5 py-4 font-semibold text-gray-900 whitespace-nowrap">{{ $item->product->kode_produk ?? '—' }}</td>
                                <td class="px-5 py-4 text-gray-700 whitespace-nowrap">{{ $item->supplier?->kode_supplier ?? '—' }}</td>
                                <td class="px-5 py-4 text-gray-700">{{ $item->product->nama_produk ?? '—' }}</td>
                                <td class="px-5 py-4 text-right text-gray-700 tabular-nums">Rp {{ number_format((float) ($item->product->hpp ?? $item->product->harga_pembelian ?? 0), 0, ',', '.') }}</td>
                                <td class="px-5 py-4 text-right text-gray-700 tabular-nums">Rp {{ number_format((float) ($item->product->harga_jual_speedshop ?? $item->product->harga_jual ?? 0), 0, ',', '.') }}</td>
                                <td class="px-5 py-4 text-right font-semibold text-gray-900 tabular-nums">Rp {{ number_format($item->harga_pembelian, 0, ',', '.') }}</td>
                                <td class="px-5 py-4 text-center">
                                    <span class="inline-flex items-center rounded-lg bg-emerald-50 px-3 py-1 text-sm font-bold text-emerald-700">+{{ $item->qty }}</span>
                                </td>
                                <td class="px-5 py-4 text-right font-semibold text-gray-900 tabular-nums">Rp {{ number_format($item->harga_pembelian * $item->qty, 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="space-y-4 lg:col-span-4">
            <div class="panel-card panel-card-flush summary-card ss-dash-enter-child" style="--ss-delay: 120ms; --ss-stagger: 2">
                <div class="section-hd">
                    <h2>Ringkasan</h2>
                    <p>Total item, nilai pembelian, dan status verifikasi</p>
                </div>

                <div class="mt-2 mb-2">
                    <div class="summary-row">
                        <span>Total Item</span>
                        <span>{{ $transaksi->items->count() }} SKU</span>
                    </div>
                    <div class="summary-row">
                        <span>Total Qty</span>
                        <span>{{ number_format($totalQty) }} unit</span>
                    </div>
                    <div class="summary-total">
                        <span>Total Harga Invoice</span>
                        <span>Rp {{ number_format($totalHarga, 0, ',', '.') }}</span>
                    </div>
                </div>

                <div class="border-t border-gray-100 px-5 py-4">
                    <label class="mb-3 block text-[11px] font-semibold uppercase tracking-wider text-gray-500">Alur Verifikasi</label>
                    <div class="flex items-start justify-between gap-1">
                        @foreach($steps as $idx => $step)
                        @php
                            $isStepDone = $idx < $currentStep || ($isSelesai && $idx === count($steps) - 1);
                            $isStepCurrent = $idx === $currentStep && ! $isSelesai;
                            $isLastRejected = $isRejected && $idx === count($steps) - 1 && $isStepDone;
                        @endphp
                        <div class="flex flex-col items-center" style="width: {{ 100 / count($steps) }}%">
                            <div class="relative flex w-full items-center justify-center">
                                @if($idx > 0)
                                <div class="absolute right-1/2 h-0.5 w-full {{ $isStepDone ? ($isLastRejected ? 'bg-red-400' : 'bg-green-400') : 'bg-gray-200' }}"></div>
                                @endif
                                @if($idx < count($steps) - 1)
                                @php $nextDone = ($idx + 1) < $currentStep || ($isSelesai && ($idx + 1) === count($steps) - 1); @endphp
                                <div class="absolute left-1/2 h-0.5 w-full {{ $nextDone ? ($isRejected && ($idx + 1) === count($steps) - 1 ? 'bg-red-400' : 'bg-green-400') : 'bg-gray-200' }}"></div>
                                @endif
                                @if($isStepDone)
                                    @if($isLastRejected)
                                        <div class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full bg-brand shadow-sm shadow-brand/30">
                                            <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </div>
                                    @else
                                        <div class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full bg-green-500 shadow-sm shadow-green-200">
                                            <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        </div>
                                    @endif
                                @elseif($isStepCurrent)
                                    <div class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full border-2 border-amber-400 bg-amber-50">
                                        <div class="h-2.5 w-2.5 rounded-full bg-yellow-400 animate-pulse"></div>
                                    </div>
                                @else
                                    <div class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full bg-gray-200">
                                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    </div>
                                @endif
                            </div>
                            <p class="mt-2 text-center text-xs font-medium {{ $isStepDone ? ($isLastRejected ? 'text-red-600' : 'text-green-600') : ($isStepCurrent ? 'text-amber-600' : 'text-gray-400') }}">{{ $step['label'] }}</p>
                        </div>
                        @endforeach
                    </div>
                    @if($transaksi->approver)
                    <p class="mt-3 text-center text-xs text-gray-500">oleh {{ $transaksi->approver->name }} · {{ $transaksi->approved_at?->format('d M Y H:i') }}</p>
                    @endif
                </div>

                @if($isRejected && !empty($transaksi->rejected_reason))
                <div class="border-t border-gray-100 px-5 py-4">
                    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3">
                        <div class="text-[11px] font-semibold uppercase tracking-wider text-red-600">Alasan Ditolak</div>
                        <div class="mt-1 whitespace-pre-line text-sm font-medium text-red-700">{{ $transaksi->rejected_reason }}</div>
                    </div>
                </div>
                @endif

                @if($canApproveReject)
                <div class="space-y-3 border-t border-gray-100 px-5 py-4">
                    <button type="button" @click="approveTransaksi()" :disabled="loading" class="flex h-11 w-full items-center justify-center gap-2 rounded-xl border border-emerald-300 bg-emerald-600 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50">
                        <svg x-show="!loading" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <svg x-show="loading" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        Setujui Stok In
                    </button>

                    <button type="button" x-show="!showRejectForm" @click="showRejectForm = true" :disabled="loading" class="btn-secondary flex h-10 w-full text-red-600">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        Tolak Stok In
                    </button>

                    <form x-show="showRejectForm" x-transition x-cloak @submit.prevent="confirmReject()" class="rounded-xl border border-red-200 bg-white p-4">
                        <label class="mb-2 block text-xs font-semibold text-gray-700">Alasan penolakan <span class="text-red-600">*</span></label>
                        <textarea x-model="rejectedReason" rows="3" class="wo-textarea" placeholder="Masukkan alasan penolakan..."></textarea>
                        <p x-show="rejectError" class="mt-1 text-xs text-red-600" x-text="rejectError"></p>
                        <div class="mt-3 flex items-center justify-end gap-2">
                            <button type="button" @click="showRejectForm = false; rejectedReason = ''; rejectError = ''" class="btn-secondary h-9 px-4">Batal</button>
                            <button type="submit" :disabled="loading" class="btn-primary h-9 px-4 disabled:opacity-50">Tolak & Kirim</button>
                        </div>
                    </form>
                </div>
                @endif

                <div class="space-y-3 border-t border-gray-100 px-5 pb-5 pt-5">
                    <a href="{{ route('speedshop.stock-in.supplier.pdf', $transaksi) }}" target="_blank" rel="noopener noreferrer" class="btn-primary flex h-11 w-full">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Cetak PDF
                    </a>
                </div>
            </div>

            @if($canApproveReject)
            <div class="panel-card panel-card-flush ss-dash-enter-child rounded-xl border-amber-200/80 bg-amber-50/80 p-4" style="--ss-delay: 120ms; --ss-stagger: 3">
                <div class="flex gap-3">
                    <svg class="h-5 w-5 shrink-0 text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <div>
                        <p class="text-sm font-semibold text-amber-800">Menunggu Verifikasi Accounting</p>
                        <p class="mt-1 text-xs leading-relaxed text-amber-700">Periksa item yang diajukan, lalu setujui atau tolak pengajuan ini. Stok akan bertambah setelah disetujui.</p>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script>
function transaksiDetail() {
    return {
        loading: false,
        showRejectForm: false,
        rejectedReason: '',
        rejectError: '',

        async approveTransaksi() {
            const result = await Swal.fire({
                title: 'Approve Transaksi?',
                text: 'Stok produk akan bertambah sesuai jumlah yang diajukan.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Approve!',
                cancelButtonText: 'Batal'
            });
            if (!result.isConfirmed) return;

            this.loading = true;
            const res = await fetch('{{ route("speedshop.stock-in.supplier.approve", $transaksi) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });

            if (!res.ok) {
                const d = await res.json();
                this.loading = false;
                Swal.fire('Error', d.message || 'Gagal approve.', 'error');
                return;
            }

            @if($isVerifikasi)
            window.location.href = @json(route('speedshop.verifikasi.stok-in-supplier.index'));
            @else
            window.location.reload();
            @endif
        },

        async confirmReject() {
            if (!this.rejectedReason.trim()) {
                this.rejectError = 'Masukkan alasan penolakan.';
                return;
            }

            const result = await Swal.fire({
                title: 'Reject Transaksi?',
                text: 'Transaksi akan ditolak dan stok tidak akan berubah.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Tolak!',
                cancelButtonText: 'Batal'
            });
            if (!result.isConfirmed) return;

            this.loading = true;

            const res = await fetch('{{ route("speedshop.stock-in.supplier.reject", $transaksi) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ rejected_reason: this.rejectedReason.trim() })
            });

            if (!res.ok) {
                const d = await res.json();
                this.loading = false;
                Swal.fire('Error', d.message || 'Gagal menolak.', 'error');
                return;
            }

            window.location.reload();
        }
    }
}
</script>
@endpush
