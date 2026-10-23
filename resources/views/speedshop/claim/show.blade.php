@extends('layouts.speedshop')

@section('title', 'Detail Claim - ' . ($claim->claim_number ?? $claim->id))

@section('content')
@php
    $totalItem = $items->count();
    $totalQty = $items->sum('qty');
    $totalHarga = $items->sum(function ($item) {
        $h = (float) ($item->product->harga_pembelian ?? 0);

        return $h * (int) $item->qty;
    });
    $hasPending = $items->where('status', 'pending')->isNotEmpty();
    $to = $transaksiPengganti ?? null;
    $toRejected = $to && $to->isRejected();
    $toVerified = $to && $to->isVerified();
    $terminalDone = $toVerified || $toRejected;
    $steps = [
        ['label' => 'Diajukan', 'done' => true],
        ['label' => 'Diproses', 'done' => ! $hasPending],
        ['label' => 'Dikirim', 'done' => $to !== null],
        ['label' => $toRejected ? 'Ditolak' : 'Selesai', 'done' => $terminalDone],
    ];
    $isRejectedFlow = $toRejected;
    $currentStep = ($toVerified || $toRejected) ? null : ($hasPending ? 1 : ($to === null ? 2 : 3));
@endphp
<div class="space-y-5">
    <nav class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('speedshop.dashboard') }}" class="transition hover:text-gray-700">Home</a>
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('speedshop.claim.index') }}" class="transition hover:text-gray-700">Claim Produk Cacat</a>
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <span class="font-medium text-gray-900">{{ $claim->claim_number ?? 'Detail' }}</span>
    </nav>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-5">
            <div class="rounded-2xl border border-gray-200 bg-white p-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-[22px] font-semibold leading-tight tracking-tight text-gray-900">Informasi Claim</h2>
                        <p class="mt-1 text-[13px] text-gray-500">Detail claim produk cacat yang diajukan ke warehouse</p>
                    </div>
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-red-50 text-red-600">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    </div>
                </div>

                <div class="mt-5 flex flex-wrap items-end gap-3">
                    <div class="min-w-[180px] flex-1">
                        <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">No. Claim</label>
                        <div class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm font-semibold text-gray-900">{{ $claim->claim_number ?? '—' }}</div>
                    </div>
                    <div class="w-44">
                        <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Tanggal</label>
                        <div class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700">{{ $claim->created_at->format('d M Y H:i') }}</div>
                    </div>
                    <div class="min-w-[200px] flex-1">
                        <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Warehouse Tujuan</label>
                        <div class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm font-semibold text-gray-900">{{ $claim->warehouse->nama ?? '—' }}</div>
                    </div>
                </div>

                @if($claim->catatan)
                <div class="mt-4">
                    <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Catatan</label>
                    <div class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700">{{ $claim->catatan }}</div>
                </div>
                @endif
            </div>

            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <div>
                        <h2 class="text-[20px] font-semibold text-gray-900">Daftar Produk Claim</h2>
                        <p class="mt-1 text-[13px] text-gray-500">Item dan qty yang diajukan</p>
                    </div>
                    <span class="inline-flex items-center rounded-full border border-red-200 bg-red-50 px-3 py-1 text-xs font-semibold text-red-600">{{ $items->count() }} item</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="bg-[#f7f3f4]">
                                <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No.</th>
                                <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Kode</th>
                                <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Nama Produk</th>
                                <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-center">Qty</th>
                                <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Catatan Item</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($items as $i => $item)
                            <tr class="transition hover:bg-[#fcfafb]">
                                <td class="px-5 py-4 text-gray-500">{{ $i + 1 }}</td>
                                <td class="px-5 py-4 font-semibold text-gray-900 whitespace-nowrap">{{ $item->product->kode_produk ?? '—' }}</td>
                                <td class="px-5 py-4 text-gray-700">{{ $item->product->nama_produk ?? '—' }}</td>
                                <td class="px-5 py-4 text-center">
                                    <span class="inline-flex items-center rounded-lg bg-green-100 px-3 py-1 text-sm font-bold text-green-700">{{ $item->qty }}</span>
                                </td>
                                <td class="px-5 py-4 text-gray-600">{{ $item->catatan ?: '—' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="space-y-5">
            <div class="rounded-2xl border border-gray-200 bg-white p-5">
                <h2 class="text-[22px] font-semibold leading-tight tracking-tight text-gray-900">Ringkasan</h2>
                <p class="mt-1 text-[13px] text-gray-500">Alur: <span class="font-medium text-gray-800">Diajukan</span> → warehouse <span class="font-medium text-gray-800">Diproses</span> → <span class="font-medium text-gray-800">Dikirim</span> (Transaksi Offline) → <span class="font-medium text-gray-800">Selesai</span> setelah Anda memverifikasi transaksi.</p>

                <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3">
                    <p class="text-xs font-semibold text-amber-900">Stok Speedshop tidak berkurang dari claim ini.</p>
                    <p class="mt-1 text-xs leading-relaxed text-amber-800">Pengganti dikirim warehouse lewat Transaksi Offline; stok Speedshop bertambah setelah Anda verifikasi transaksi tersebut.</p>
                </div>

                <div class="mt-4 space-y-3">
                    <div class="flex items-center justify-between rounded-lg bg-gray-50 px-4 py-3">
                        <span class="text-sm font-medium text-gray-700">Total Item</span>
                        <span class="text-sm font-bold text-gray-900">{{ $totalItem }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-gray-50 px-4 py-3">
                        <span class="text-sm font-medium text-gray-700">Total Qty</span>
                        <span class="text-sm font-bold text-gray-900">{{ $totalQty }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-green-50 px-4 py-3">
                        <span class="text-sm font-medium text-green-700">Total Harga</span>
                        <span class="text-sm font-bold text-green-800">Rp {{ number_format($totalHarga, 0, ',', '.') }}</span>
                    </div>
                </div>

                <div class="mt-5">
                    <label class="mb-3 block text-[11px] font-semibold uppercase tracking-wider text-gray-500">Status</label>
                    <div class="flex items-start justify-between">
                        @foreach($steps as $idx => $step)
                        <div class="flex flex-col items-center" style="width: {{ 100 / count($steps) }}%">
                            <div class="relative flex w-full items-center justify-center">
                                @if($idx > 0)
                                <div class="absolute right-1/2 h-0.5 w-full {{ $step['done'] ? 'bg-green-400' : 'bg-gray-200' }}"></div>
                                @endif
                                @if($idx < count($steps) - 1)
                                <div class="absolute left-1/2 h-0.5 w-full {{ $steps[$idx + 1]['done'] ? 'bg-green-400' : 'bg-gray-200' }}"></div>
                                @endif
                                @if($step['done'])
                                    @if($isRejectedFlow && $idx === count($steps) - 1)
                                    <div class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full bg-brand shadow-sm">
                                        <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </div>
                                    @else
                                    <div class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full bg-green-500 shadow-sm">
                                        <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    </div>
                                    @endif
                                @elseif($idx === $currentStep)
                                <div class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full border-2 border-amber-400 bg-amber-50">
                                    <div class="h-2.5 w-2.5 animate-pulse rounded-full bg-amber-400"></div>
                                </div>
                                @else
                                <div class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full bg-gray-200">
                                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </div>
                                @endif
                            </div>
                            <p class="mt-2 text-center text-xs font-medium {{ $step['done'] ? ($isRejectedFlow && $idx === count($steps) - 1 ? 'text-red-600' : 'text-green-600') : ($idx === $currentStep ? 'text-amber-600' : 'text-gray-400') }}">{{ $step['label'] }}</p>
                        </div>
                        @endforeach
                    </div>
                    @if($to && $to->isPendingSpeedshopDecision())
                    <p class="mt-3 text-center text-xs text-gray-500">Transaksi <span class="font-semibold text-gray-800">{{ $to->no_transaksi }}</span> menunggu verifikasi Anda.</p>
                    @elseif($toVerified)
                    <p class="mt-3 text-center text-xs text-gray-500">Transaksi terverifikasi · stok pengganti sudah masuk ke Speedshop.</p>
                    @elseif($toRejected)
                    <p class="mt-3 text-center text-xs text-red-600">Transaksi offline ditolak.</p>
                    @endif
                </div>

                @if($to)
                <div class="mt-5 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Transaksi pengganti</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900">{{ $to->no_transaksi }}</p>
                    <a href="{{ route('speedshop.stock-in.transaksi-offline.show', $to) }}" class="mt-2 inline-flex text-sm font-semibold text-red-600 transition hover:text-red-700">Lihat detail transaksi →</a>
                </div>
                @endif
            </div>

            <div class="space-y-3">
                @if($to && $to->isPendingSpeedshopDecision() && auth()->user()->isManager())
                <button type="button" id="btn-verify-claim-transaksi" onclick="verifyClaimTransaksi()" class="flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-600 px-5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/25 transition hover:brightness-105 disabled:cursor-not-allowed disabled:opacity-50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    Konfirmasi &amp; Selesaikan
                </button>
                @endif
                <a href="{{ route('speedshop.claim.index') }}" class="flex h-10 w-full items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-5 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Kembali ke Daftar Claim
            </a>
            </div>
        </div>
    </div>
</div>
@if(isset($to) && $to && $to->isPendingSpeedshopDecision() && auth()->user()->isManager())
@push('scripts')
<script>
const verifyClaimTransaksiUrl = '{{ route("speedshop.stock-in.transaksi-offline.verify", $to) }}';

async function verifyClaimTransaksi() {
    const result = await Swal.fire({
        title: 'Selesaikan claim ini?',
        html: 'Dengan mengonfirmasi, transaksi <strong>{{ $to->no_transaksi }}</strong> akan diverifikasi dan stok pengganti masuk ke Speedshop Anda. Alur claim menjadi <strong>Selesai</strong>.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, konfirmasi',
        cancelButtonText: 'Batal'
    });
    if (!result.isConfirmed) return;

    const btn = document.getElementById('btn-verify-claim-transaksi');
    if (btn) {
        btn.disabled = true;
        btn.classList.add('opacity-70');
    }

    try {
        const res = await fetch(verifyClaimTransaksiUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            await Swal.fire('Gagal', data.message || 'Verifikasi gagal.', 'error');
            if (btn) {
                btn.disabled = false;
                btn.classList.remove('opacity-70');
            }
            return;
        }
        await Swal.fire('Berhasil', data.message || 'Transaksi diverifikasi. Claim selesai.', 'success');
        window.location.reload();
    } catch (e) {
        await Swal.fire('Error', 'Terjadi kesalahan. Coba lagi.', 'error');
        if (btn) {
            btn.disabled = false;
            btn.classList.remove('opacity-70');
        }
    }
}
</script>
@endpush
@endif
@endsection
