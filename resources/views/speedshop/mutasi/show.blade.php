@extends('layouts.speedshop')

@section('title', 'Detail Mutasi - ' . $mutasi->kode)

@php
    $woPanelClass = 'panel-card panel-card-flush overflow-visible ss-dash-enter-child';

    $destType = $mutasi->destination_type ?? 'warehouse';
    $destLabel = $destType === 'speedshop' ? 'Speedshop Tujuan' : 'Warehouse Tujuan';
    $destName = $destType === 'speedshop'
        ? ($mutasi->destinationSpeedshop->nama ?? '-')
        : ($mutasi->warehouse->nama ?? '-');

    $mutasiStatus = (string) ($mutasi->status ?? 'dikirim');
    $isDitolak = $mutasiStatus === 'ditolak';
    $isSelesai = in_array($mutasiStatus, ['diterima', 'ditolak'], true);
    $isDikirim = $mutasiStatus === 'dikirim';
    $isTujuanSpeedshop = $destType === 'speedshop'
        && (int) ($mutasi->destination_speedshop_id ?? 0) === (int) (auth()->user()?->activeSpeedshopId() ?? 0);

    $currentStep = match ($mutasiStatus) {
        'dikirim' => 1,
        'diterima', 'ditolak' => 2,
        default => 0,
    };
    $steps = [
        ['label' => 'Diajukan'],
        ['label' => 'Menunggu Verifikasi'],
        ['label' => $isDitolak ? 'Ditolak' : 'Disetujui'],
    ];

    $statusBadgeClass = match ($mutasiStatus) {
        'dikirim' => 'border-amber-200 bg-amber-50 text-amber-700',
        'ditolak' => 'border-red-200 bg-red-50 text-red-700',
        default => 'border-emerald-200 bg-emerald-50 text-emerald-700',
    };
    $statusBadgeLabel = match ($mutasiStatus) {
        'dikirim' => 'Dikirim',
        'ditolak' => 'Ditolak',
        default => 'Diterima',
    };
@endphp

@section('content')
@push('styles')
@include('partials.speedshop.dashboard-load-animations')
@include('partials.speedshop.dashboard-manager-template-styles')
@include('partials.speedshop.work-order-create-styles')
@endpush

<div
    class="ss-dashboard ss-dash-page ss-wo-create"
    x-data="mutasiVerifyActions()"
    x-init="$nextTick(() => { requestAnimationFrame(() => $el.classList.add('is-ready')); })"
>
<div class="ss-dash-content space-y-5">
    <div class="flex flex-col gap-3 ss-dash-enter sm:flex-row sm:items-start sm:justify-between" style="--ss-delay: 0ms">
        <div>
            <h1 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">{{ $mutasi->kode }}</h1>
            <p class="mt-1 text-[13px] text-gray-500">Detail mutasi stok · {{ $mutasi->tanggal->format('d M Y') }}</p>
        </div>
        <a href="{{ route('speedshop.mutasi.index') }}" class="btn-secondary shrink-0">← Kembali</a>
    </div>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-12 ss-dash-enter" style="--ss-delay: 80ms">
        <div class="space-y-5 lg:col-span-8">
            <div class="{{ $woPanelClass }}" style="--ss-delay: 120ms; --ss-stagger: 0">
                <div class="flex items-start justify-between border-b border-gray-100 px-5 py-4">
                    <div>
                        <h2 class="text-[20px] font-semibold text-gray-900">Informasi Mutasi</h2>
                        <p class="mt-1 text-[13px] text-gray-500">Data pengiriman stok ke warehouse atau cabang lain</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-xs font-semibold {{ $statusBadgeClass }}">{{ $statusBadgeLabel }}</span>
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-red-50 text-red-600">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 px-5 py-5 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Nomor Mutasi</label>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm font-medium text-gray-900">{{ $mutasi->kode }}</div>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Tanggal</label>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700">{{ $mutasi->tanggal->format('d M Y') }}</div>
                    </div>
                    @if($mutasi->nomor_surat_jalan)
                    <div>
                        <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Nomor Surat Jalan</label>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700">{{ $mutasi->nomor_surat_jalan }}</div>
                    </div>
                    @endif
                    <div>
                        <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">{{ $destLabel }}</label>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700">{{ $destName }}</div>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Dibuat Oleh</label>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700">{{ $mutasi->user->name ?? '-' }}</div>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Tipe Tujuan</label>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700">
                            {{ $destType === 'speedshop' ? 'Speedshop' : 'Warehouse' }}
                        </div>
                    </div>
                </div>

                @if($mutasi->catatan)
                <div class="px-5 pb-5">
                    <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Catatan</label>
                    <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700">{{ $mutasi->catatan }}</div>
                </div>
                @endif
            </div>

            <div class="{{ $woPanelClass }}" style="--ss-delay: 120ms; --ss-stagger: 1">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <div>
                        <h2 class="text-[20px] font-semibold text-gray-900">Daftar Produk</h2>
                        <p class="mt-1 text-[13px] text-gray-500">Item yang dimutasi</p>
                    </div>
                    <span class="wo-badge">{{ $mutasi->items->count() }} produk · {{ number_format($mutasi->items->sum('quantity')) }} unit</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="bg-[#f7f3f4]">
                                <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No.</th>
                                <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Kode</th>
                                <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Kode Supplier</th>
                                <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Nama Produk</th>
                                <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-center">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($mutasi->items as $i => $item)
                            <tr class="transition hover:bg-[#fcfafb]">
                                <td class="px-5 py-4 text-gray-500">{{ $i + 1 }}</td>
                                <td class="px-5 py-4 font-semibold text-gray-900">{{ $item->product->kode_produk ?? '-' }}</td>
                                <td class="px-5 py-4 text-gray-700">{{ $supplierKodeByProduct[$item->product_id] ?? '—' }}</td>
                                <td class="px-5 py-4 text-gray-700">{{ $item->product->nama_produk ?? '-' }}</td>
                                <td class="px-5 py-4 text-center font-semibold text-gray-900">{{ $item->quantity }}</td>
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
                    <p>Total item dan status verifikasi mutasi</p>
                </div>
                <div class="mt-2 mb-2">
                    <div class="summary-row">
                        <span>Total Item</span>
                        <span>{{ $mutasi->items->count() }} SKU</span>
                    </div>
                    <div class="summary-row">
                        <span>Total Unit</span>
                        <span>{{ number_format($mutasi->items->sum('quantity')) }} unit</span>
                    </div>
                    <div class="summary-total">
                        <span>{{ $destLabel }}</span>
                        <span>{{ $destName }}</span>
                    </div>
                </div>

                <div class="border-t border-gray-100 px-5 py-4">
                    <label class="mb-3 block text-[11px] font-semibold uppercase tracking-wider text-gray-500">Alur Verifikasi</label>
                    <div class="flex items-start justify-between gap-1">
                        @foreach($steps as $idx => $step)
                        @php
                            $isStepDone = $idx < $currentStep || ($isSelesai && $idx === count($steps) - 1);
                            $isStepCurrent = $idx === $currentStep && ! $isSelesai;
                            $isLastRejected = $isDitolak && $idx === count($steps) - 1 && $isStepDone;
                        @endphp
                        <div class="flex flex-col items-center" style="width: {{ 100 / count($steps) }}%">
                            <div class="relative flex w-full items-center justify-center">
                                @if($idx > 0)
                                <div class="absolute right-1/2 h-0.5 w-full {{ $isStepDone ? ($isLastRejected ? 'bg-red-400' : 'bg-green-400') : 'bg-gray-200' }}"></div>
                                @endif
                                @if($idx < count($steps) - 1)
                                @php $nextDone = ($idx + 1) < $currentStep || ($isSelesai && ($idx + 1) === count($steps) - 1); @endphp
                                <div class="absolute left-1/2 h-0.5 w-full {{ $nextDone ? ($isDitolak && ($idx + 1) === count($steps) - 1 ? 'bg-red-400' : 'bg-green-400') : 'bg-gray-200' }}"></div>
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
                </div>

                @if($isDitolak && !empty($mutasi->rejected_reason))
                <div class="border-t border-gray-100 px-5 py-4">
                    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3">
                        <div class="text-[11px] font-semibold uppercase tracking-wider text-red-600">Alasan Ditolak</div>
                        <div class="mt-1 whitespace-pre-line text-sm font-medium text-red-700">{{ $mutasi->rejected_reason }}</div>
                    </div>
                </div>
                @endif

                @if($isTujuanSpeedshop && $isDikirim)
                <div class="space-y-3 border-t border-gray-100 px-5 py-4">
                    <button type="button" @click="verify()" :disabled="loading" class="flex h-11 w-full items-center justify-center gap-2 rounded-xl border border-emerald-300 bg-emerald-600 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50">
                        <svg x-show="!loading" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <svg x-show="loading" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        Setujui Mutasi
                    </button>

                    <button type="button" @click="toggleReject()" :disabled="loading" class="btn-secondary flex h-10 w-full text-red-600">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        Tolak Mutasi
                    </button>

                    <form x-show="rejectOpen" x-transition @submit.prevent="submitReject()" class="rounded-xl border border-red-200 bg-white p-4">
                        <label class="mb-2 block text-xs font-semibold text-gray-700">Alasan penolakan</label>
                        <textarea x-model="rejected_reason" rows="3" class="wo-textarea" placeholder="Tuliskan alasan penolakan..."></textarea>
                        <div class="mt-3 flex items-center justify-end gap-2">
                            <button type="button" @click="rejectOpen=false" class="btn-secondary h-9 px-4">Batal</button>
                            <button type="submit" :disabled="loading" class="btn-primary h-9 px-4 disabled:opacity-50">Tolak & Kirim</button>
                        </div>
                        <p class="mt-2 text-[12px] text-gray-500">Stok akan dikembalikan ke speedshop asal.</p>
                    </form>
                </div>
                @endif

                <div class="space-y-3 border-t border-gray-100 px-5 pb-5 pt-5">
                    <a href="{{ route('speedshop.mutasi.pdf', $mutasi) }}" target="_blank" rel="noopener noreferrer" class="btn-primary flex h-11 w-full">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Cetak PDF
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script>
function mutasiVerifyActions() {
    return {
        loading: false,
        rejectOpen: false,
        rejected_reason: '',
        toggleReject() {
            this.rejectOpen = !this.rejectOpen;
            if (this.rejectOpen) {
                this.rejected_reason = '';
            }
        },
        async verify() {
            const result = await Swal.fire({
                title: 'Verifikasi Mutasi?',
                text: 'Mutasi akan ditandai sebagai diterima.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, verifikasi',
                cancelButtonText: 'Batal',
            });
            if (!result.isConfirmed) return;
            this.loading = true;
            const res = await fetch('{{ route("speedshop.mutasi.verify", $mutasi) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                }
            });
            const d = await res.json().catch(() => ({}));
            if (!res.ok) {
                this.loading = false;
                Swal.fire('Error', d.message || 'Gagal verifikasi.', 'error');
                return;
            }
            window.location.reload();
        },
        async submitReject() {
            const reason = (this.rejected_reason || '').trim();
            if (reason.length < 3) {
                Swal.fire('Alasan wajib diisi', 'Minimal 3 karakter.', 'warning');
                return;
            }

            const result = await Swal.fire({
                title: 'Tolak Mutasi?',
                text: 'Stok akan dikembalikan ke speedshop asal.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, tolak',
                cancelButtonText: 'Batal',
            });
            if (!result.isConfirmed) return;

            this.loading = true;
            const res = await fetch('{{ route("speedshop.mutasi.reject", $mutasi) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ rejected_reason: reason }),
            });
            const d = await res.json().catch(() => ({}));
            if (!res.ok) {
                this.loading = false;
                Swal.fire('Error', d.message || 'Gagal menolak.', 'error');
                return;
            }
            window.location.reload();
        }
    };
}
</script>
@endpush
