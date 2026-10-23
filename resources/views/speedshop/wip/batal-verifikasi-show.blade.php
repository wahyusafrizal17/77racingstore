@extends('layouts.speedshop')

@section('title', 'Detail Pembatalan WO')

@section('content')
@php
    $tz = config('app.timezone');
    $batalStatus = $service_order->batal_verifikasi_status ?? null;
    $isPending = $batalStatus === 'pending';
    $isRejected = $batalStatus === 'rejected';
    $isCompleted = $batalStatus === 'approved';
    $totalJasa = $service_order->jasaServis->sum(fn ($j) => ((int) ($j->pivot->qty ?? 1) * (int) $j->pivot->biaya));
    $totalSparepart = $service_order->products->sum(fn ($p) => (int) $p->pivot->qty * (int) $p->pivot->harga);
    $grandTotal = $totalJasa + $totalSparepart;
    $sparepartQtyTotal = $service_order->products->sum(fn ($p) => (int) $p->pivot->qty);
    $rincianBaris = $service_order->jasaServis->count() + $service_order->products->count();
    $steps = [
        ['label' => 'Diajukan', 'done' => true],
        ['label' => 'Menunggu Verifikasi Accounting', 'done' => $isCompleted || $isRejected],
        ['label' => $isRejected ? 'Ditolak' : 'Disetujui', 'done' => $isCompleted || $isRejected],
    ];
    $currentStep = $isPending ? 1 : ($isCompleted || $isRejected ? 2 : 0);
    $accountingUser = auth()->user()->isAccounting();
    $indexRoute = 'speedshop.verifikasi.batal-work-order.index';
@endphp
<div class="space-y-5">
    <nav class="flex flex-wrap items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('speedshop.dashboard') }}" class="transition hover:text-gray-700">Home</a>
        <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route($indexRoute) }}" class="transition hover:text-gray-700">Verifikasi Batal WO</a>
        <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <span class="font-medium text-gray-900">{{ $service_order->kode_servis }}</span>
    </nav>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-12">
        <div class="space-y-5 lg:col-span-8">
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white">
                <div class="flex items-start justify-between border-b border-gray-100 px-5 py-4">
                    <div>
                        <h2 class="text-[20px] font-semibold text-gray-900">Pembatalan work order</h2>
                        <p class="mt-1 text-[13px] text-gray-500">Informasi pengajuan pembatalan work order</p>
                    </div>
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-red-50 text-red-600">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                </div>
                <div class="space-y-4 px-5 py-5">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label class="mb-1 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Kode WO</label>
                            <div class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm font-semibold text-gray-900">{{ $service_order->kode_servis }}</div>
                        </div>
                        <div>
                            <label class="mb-1 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Status WO</label>
                            <div class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm font-semibold text-gray-900">{{ ucfirst($service_order->status) }}</div>
                        </div>
                        <div>
                            <label class="mb-1 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Diajukan oleh</label>
                            <div class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700">{{ $service_order->batalVerifikasiRequestedBy->name ?? '—' }}</div>
                        </div>
                        <div>
                            <label class="mb-1 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Waktu pengajuan</label>
                            <div class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700">{{ $service_order->batal_verifikasi_requested_at?->timezone($tz)->format('d M Y H:i') ?? '—' }}</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Pelanggan</label>
                            <div class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700">{{ $service_order->pelanggan->nama ?? '—' }}</div>
                        </div>
                        <div>
                            <label class="mb-1 block text-[11px] font-medium uppercase tracking-wide text-gray-500">No. polisi</label>
                            <div class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700">{{ $service_order->kendaraan->nomor_polisi ?? '—' }}</div>
                        </div>
                        <div>
                            <label class="mb-1 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Kendaraan</label>
                            <div class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700">{{ trim(($service_order->kendaraan->merk ?? '').' '.($service_order->kendaraan->tipe ?? '')) ?: '—' }}</div>
                        </div>
                        <div>
                            <label class="mb-1 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Mekanik</label>
                            <div class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700">{{ $service_order->mekanik->nama ?? '—' }}</div>
                        </div>
                    </div>

                    @if($service_order->batal_verifikasi_reason)
                    <div>
                        <label class="mb-1 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Alasan pembatalan</label>
                        <div class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700">{{ $service_order->batal_verifikasi_reason }}</div>
                    </div>
                    @endif
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <div>
                        <h2 class="text-[20px] font-semibold text-gray-900">Rincian work order</h2>
                        <p class="mt-1 text-[13px] text-gray-500">Jasa servis dan sparepart pada work order ini</p>
                    </div>
                    <span class="inline-flex items-center rounded-full border border-red-200 bg-red-50 px-3 py-1 text-xs font-semibold text-red-600">{{ $rincianBaris }} baris</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="bg-[#f7f3f4]">
                                <th class="px-4 py-3 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No.</th>
                                <th class="px-4 py-3 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Jenis</th>
                                <th class="px-4 py-3 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Item</th>
                                <th class="px-4 py-3 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-center">Qty</th>
                                <th class="px-4 py-3 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-right">Subtotal</th>
                                <th class="px-4 py-3 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @php $rowNo = 0; @endphp
                            @foreach($service_order->jasaServis as $j)
                            @php
                                $rowNo++;
                                $sub = (int) ($j->pivot->qty ?? 1) * (int) $j->pivot->biaya;
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-gray-500">{{ $rowNo }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full bg-sky-50 px-2 py-0.5 text-xs font-semibold text-sky-800 ring-1 ring-sky-200/70">Jasa</span>
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $j->nama }}</td>
                                <td class="px-4 py-3 text-center text-gray-700">{{ (int) ($j->pivot->qty ?? 1) }}</td>
                                <td class="px-4 py-3 text-right font-semibold tabular-nums text-gray-900">Rp {{ number_format($sub, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-gray-500">—</td>
                            </tr>
                            @endforeach
                            @foreach($service_order->products as $p)
                            @php
                                $rowNo++;
                                $sub = (int) $p->pivot->qty * (int) $p->pivot->harga;
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-gray-500">{{ $rowNo }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-800 ring-1 ring-emerald-200/70">Sparepart</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900">{{ $p->nama_produk }}</div>
                                    <div class="mt-0.5 font-mono text-xs text-gray-500">{{ $p->kode_produk }}</div>
                                </td>
                                <td class="px-4 py-3 text-center font-semibold text-emerald-700">{{ (int) $p->pivot->qty }}</td>
                                <td class="px-4 py-3 text-right font-semibold tabular-nums text-gray-900">Rp {{ number_format($sub, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-xs text-gray-600">
                                    @if($isPending)
                                        Stok dikembalikan jika disetujui
                                    @elseif($isCompleted)
                                        Stok dikembalikan
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                            @if($rincianBaris === 0)
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">Tidak ada jasa atau sparepart pada work order ini.</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                @if($sparepartQtyTotal > 0)
                <div class="border-t border-gray-100 px-5 py-4 text-xs text-gray-500">
                    <p>Total qty sparepart dikembalikan: <span class="font-medium text-gray-800">{{ $sparepartQtyTotal }} unit</span></p>
                </div>
                @endif
            </div>
        </div>

        <div class="space-y-4 lg:col-span-4">
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white">
                <div class="border-b border-gray-100 px-5 py-4">
                    <h2 class="text-[20px] font-semibold text-gray-900">Ringkasan</h2>
                </div>
                <div class="space-y-2 px-5 py-4">
                    <div class="flex items-center justify-between rounded-lg border border-sky-100 bg-sky-50 px-3 py-2.5">
                        <span class="text-sm font-medium text-sky-900">Total jasa</span>
                        <span class="text-sm font-bold tabular-nums text-sky-950">Rp {{ number_format($totalJasa, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg border border-emerald-100 bg-emerald-50 px-3 py-2.5">
                        <span class="text-sm font-medium text-emerald-900">Total sparepart</span>
                        <span class="text-sm font-bold tabular-nums text-emerald-950">Rp {{ number_format($totalSparepart, 0, ',', '.') }}</span>
                    </div>
                    @if($sparepartQtyTotal > 0)
                    <div class="flex items-center justify-between rounded-lg border border-teal-100 bg-teal-50 px-3 py-2.5">
                        <span class="text-sm font-medium text-teal-950">Qty sparepart dikembalikan</span>
                        <span class="text-sm font-bold tabular-nums text-teal-950">{{ $sparepartQtyTotal }} unit</span>
                    </div>
                    @endif
                </div>
                <div class="border-t border-gray-100 px-5 py-4">
                    <div class="flex items-center justify-between rounded-xl bg-gradient-to-r from-red-600 to-red-700 px-4 py-3">
                        <span class="text-sm font-medium text-red-100">Total nilai WO</span>
                        <span class="text-lg font-semibold tabular-nums text-white">Rp {{ number_format($grandTotal, 0, ',', '.') }}</span>
                    </div>
                </div>

                <div class="border-t border-gray-100 px-5 py-4">
                    <label class="mb-3 block text-[11px] font-semibold uppercase tracking-wider text-gray-500">Alur persetujuan</label>
                    <div class="flex items-start justify-between gap-1">
                        @foreach($steps as $idx => $step)
                        <div class="flex flex-col items-center" style="width: {{ 100 / count($steps) }}%">
                            <div class="relative flex w-full items-center justify-center">
                                @if($idx > 0)
                                <div class="absolute right-1/2 h-0.5 w-full {{ $step['done'] ? ($isRejected && $idx === count($steps) - 1 ? 'bg-red-400' : 'bg-green-400') : 'bg-gray-200' }}"></div>
                                @endif
                                @if($idx < count($steps) - 1)
                                <div class="absolute left-1/2 h-0.5 w-full {{ $steps[$idx + 1]['done'] ? ($isRejected ? 'bg-red-400' : 'bg-green-400') : 'bg-gray-200' }}"></div>
                                @endif
                                @if($step['done'])
                                    @if($isRejected && $idx === count($steps) - 1)
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
                                        <span class="text-xs text-gray-400">{{ $idx + 1 }}</span>
                                    </div>
                                @endif
                            </div>
                            <p class="mt-2 text-center text-[10px] font-medium leading-tight sm:text-xs {{ $step['done'] ? ($isRejected && $idx === count($steps) - 1 ? 'text-red-600' : 'text-green-600') : ($idx === $currentStep ? 'text-amber-600' : 'text-gray-400') }}">{{ $step['label'] }}</p>
                        </div>
                        @endforeach
                    </div>
                    @if($isCompleted)
                    <p class="mt-3 text-center text-xs text-gray-500">
                        @if($service_order->batalVerifikasiVerifiedBy)
                            Disetujui oleh {{ $service_order->batalVerifikasiVerifiedBy->name }} · {{ $service_order->batal_verifikasi_verified_at?->timezone($tz)->format('d M Y H:i') }}
                        @else
                            Selesai · {{ $service_order->batal_verifikasi_verified_at?->timezone($tz)->format('d M Y H:i') }}
                        @endif
                    </p>
                    @elseif($isPending)
                    <p class="mt-3 text-center text-xs text-gray-500">Menunggu tindakan Accounting</p>
                    @endif

                    @if($isRejected && $service_order->batal_verifikasi_rejected_reason)
                    <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-3">
                        <p class="text-xs font-semibold text-red-600">Alasan penolakan</p>
                        <p class="mt-1 text-sm text-red-800">{{ $service_order->batal_verifikasi_rejected_reason }}</p>
                    </div>
                    @endif
                </div>

                @if($isPending && $accountingUser)
                <div class="space-y-2.5 border-t border-gray-100 px-5 pb-5 pt-4">
                    <form method="POST" action="{{ route('speedshop.wip.cancel.approve', $service_order) }}" data-swal-confirm="approve">
                        @csrf
                        <button type="submit" class="flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-green-600 to-green-700 px-5 text-sm font-semibold text-white shadow-sm transition hover:brightness-105">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            Setujui pembatalan
                        </button>
                    </form>
                    <div x-data="{ open: false }" class="space-y-2">
                        <button type="button" @click="open = !open" class="flex h-10 w-full items-center justify-center gap-2 rounded-xl border border-red-200 bg-red-50 px-5 text-sm font-medium text-red-600 transition hover:bg-red-100">
                            Tolak pengajuan
                        </button>
                        <form x-show="open" x-cloak method="POST" action="{{ route('speedshop.wip.cancel.reject', $service_order) }}" data-swal-confirm="reject" class="space-y-2 rounded-xl border border-gray-200 bg-gray-50 p-3">
                            @csrf
                            <label class="block text-xs font-semibold text-gray-600">Alasan penolakan <span class="text-red-500">*</span></label>
                            <textarea name="rejected_reason" rows="3" required minlength="3" maxlength="500" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm" placeholder="Jelaskan alasan penolakan…"></textarea>
                            <div class="flex gap-2">
                                <button type="submit" class="flex-1 rounded-lg bg-red-600 px-3 py-2 text-sm font-semibold text-white hover:bg-red-700">Kirim penolakan</button>
                                <button type="button" @click="open = false" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-600">Batal</button>
                            </div>
                        </form>
                    </div>
                </div>
                @elseif($isPending)
                <div class="border-t border-gray-100 px-5 py-4">
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900">
                        Pengajuan ini menunggu verifikasi Accounting. Anda akan mendapat notifikasi setelah diproses.
                    </div>
                </div>
                @endif
            </div>

            <a href="{{ route($indexRoute) }}" class="flex h-10 w-full items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-5 text-sm font-medium text-gray-600 shadow-sm transition hover:bg-gray-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Kembali ke daftar
            </a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const forms = document.querySelectorAll('form[data-swal-confirm]');
  forms.forEach((form) => {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();

      const kind = form.getAttribute('data-swal-confirm');
      if (!kind) return form.submit();

      if (kind === 'reject') {
        const textarea = form.querySelector('textarea[name="rejected_reason"]');
        const reason = (textarea?.value || '').trim();
        if (reason.length < 3) {
          if (textarea) textarea.focus();
          return Swal.fire('Perhatian', 'Alasan penolakan wajib diisi (min. 3 karakter).', 'warning');
        }
      }

      const cfg = kind === 'approve'
        ? {
            title: 'Setujui pembatalan?',
            text: 'Work order akan dibatalkan dan stok sparepart dikembalikan ke inventory.',
            icon: 'question',
            confirmButtonText: 'Ya, setujui',
            confirmButtonColor: '#16a34a',
          }
        : {
            title: 'Tolak pengajuan pembatalan?',
            text: 'Pengajuan akan ditandai ditolak.',
            icon: 'warning',
            confirmButtonText: 'Ya, tolak',
            confirmButtonColor: '#dc2626',
          };

      const result = await Swal.fire({
        ...cfg,
        showCancelButton: true,
        cancelButtonText: 'Batal',
        cancelButtonColor: '#9ca3af',
      });

      if (result.isConfirmed) {
        form.submit();
      }
    });
  });
});
</script>
@endpush
