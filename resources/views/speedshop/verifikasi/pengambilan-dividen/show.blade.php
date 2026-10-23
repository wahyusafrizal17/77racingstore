@extends('layouts.speedshop')

@section('title', 'Verifikasi Dividen — Detail')

@section('content')
@php
    use App\Models\SpeedshopOwnerPengambilanDividen;
    $tz = config('app.timezone', 'Asia/Jakarta');
    $isPending = $pengambilan->isPending();
@endphp
<div class="mx-auto max-w-3xl space-y-5">
    <nav class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('speedshop.verifikasi.pengambilan-dividen.index') }}" class="transition hover:text-gray-700">Verifikasi Dividen</a>
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <span class="font-medium text-gray-900">Detail #{{ $pengambilan->id }}</span>
    </nav>


    <div class="rounded-2xl border border-gray-200 bg-white p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">Pengambilan dividen</h2>
                <p class="mt-1 text-sm text-gray-500">Diajukan {{ $pengambilan->created_at?->timezone($tz)->format('d/m/Y H:i') ?? '—' }}</p>
            </div>
            @php
                $statusLabel = match ($pengambilan->status) {
                    SpeedshopOwnerPengambilanDividen::STATUS_COMPLETED => 'Disetujui',
                    SpeedshopOwnerPengambilanDividen::STATUS_REJECTED => 'Ditolak',
                    default => 'Menunggu verifikasi',
                };
            @endphp
            <span class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-900">{{ $statusLabel }}</span>
        </div>

        <dl class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 text-sm">
            <div>
                <dt class="text-xs font-medium uppercase text-gray-500">Owner</dt>
                <dd class="mt-1 font-medium text-gray-900">{{ $pengambilan->ownerUser?->name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium uppercase text-gray-500">Tanggal</dt>
                <dd class="mt-1 text-gray-900">{{ $pengambilan->tanggal->format('d/m/Y') }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium uppercase text-gray-500">Speedshop</dt>
                <dd class="mt-1 text-gray-900">{{ $pengambilan->speedshop?->nama ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium uppercase text-gray-500">Nominal</dt>
                <dd class="mt-1 text-lg font-semibold tabular-nums text-gray-900">Rp {{ number_format($pengambilan->nominal, 0, ',', '.') }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium uppercase text-gray-500">Rekening speedshop (sumber)</dt>
                <dd class="mt-1 text-gray-900">{{ $pengambilan->speedshopRekening?->nama ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium uppercase text-gray-500">Rekening owner (tujuan)</dt>
                <dd class="mt-1 text-gray-900">{{ $pengambilan->ownerRekening?->nama ?? '—' }}</dd>
            </div>
            @if($pengambilan->catatan)
            <div class="sm:col-span-2">
                <dt class="text-xs font-medium uppercase text-gray-500">Catatan owner</dt>
                <dd class="mt-1 whitespace-pre-wrap text-gray-800">{{ $pengambilan->catatan }}</dd>
            </div>
            @endif
            @if($pengambilan->buktiPengajuanUrl())
            <div class="sm:col-span-2">
                <dt class="text-xs font-medium uppercase text-gray-500">Bukti pengajuan</dt>
                <dd class="mt-1">
                    <a href="{{ $pengambilan->buktiPengajuanUrl() }}" target="_blank" rel="noopener" class="font-medium text-red-600 hover:underline">Lihat file</a>
                </dd>
            </div>
            @endif
            @if($pengambilan->buktiTransferUrl())
            <div class="sm:col-span-2">
                <dt class="text-xs font-medium uppercase text-gray-500">Bukti transfer</dt>
                <dd class="mt-1">
                    <a href="{{ $pengambilan->buktiTransferUrl() }}" target="_blank" rel="noopener" class="font-medium text-emerald-700 hover:underline">Lihat file</a>
                </dd>
            </div>
            @endif
            @if($pengambilan->status === SpeedshopOwnerPengambilanDividen::STATUS_REJECTED && $pengambilan->rejected_reason)
            <div class="sm:col-span-2">
                <dt class="text-xs font-medium uppercase text-gray-500">Alasan penolakan</dt>
                <dd class="mt-1 text-red-800">{{ $pengambilan->rejected_reason }}</dd>
            </div>
            @endif
        </dl>

        @if($isPending)
        <div class="mt-8 space-y-6 border-t border-gray-100 pt-6">
            <form method="POST" action="{{ route('speedshop.verifikasi.pengambilan-dividen.approve', $pengambilan) }}" enctype="multipart/form-data" class="rounded-xl border border-emerald-200 bg-emerald-50/50 p-5">
                @csrf
                <h3 class="text-sm font-semibold text-emerald-900">Setujui & posting jurnal</h3>
                <p class="mt-1 text-xs text-emerald-800/80">Unggah bukti transfer ke rekening owner. Saldo speedshop berkurang dan saldo owner bertambah.</p>
                <div class="mt-4">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">Bukti transfer <span class="text-red-500">*</span></label>
                    <input type="file" name="bukti_transfer" accept=".jpg,.jpeg,.png,.pdf" required class="block w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-100 file:px-4 file:py-2 file:text-sm file:font-medium file:text-emerald-800">
                    @error('bukti_transfer')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="mt-4 inline-flex h-10 items-center rounded-xl bg-emerald-600 px-5 text-sm font-semibold text-white hover:bg-emerald-700" onclick="return confirm('Setujui pengambilan dividen dan posting jurnal?')">
                    Setujui & transfer
                </button>
            </form>

            <form method="POST" action="{{ route('speedshop.verifikasi.pengambilan-dividen.reject', $pengambilan) }}" class="rounded-xl border border-red-200 bg-red-50/50 p-5" onsubmit="return confirm('Tolak pengajuan dividen ini?')">
                @csrf
                <h3 class="text-sm font-semibold text-red-900">Tolak pengajuan</h3>
                <div class="mt-3">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">Alasan penolakan <span class="text-red-500">*</span></label>
                    <textarea name="rejected_reason" rows="3" required minlength="5" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm" placeholder="Minimal 5 karakter">{{ old('rejected_reason') }}</textarea>
                    @error('rejected_reason')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="mt-3 inline-flex h-10 items-center rounded-xl border border-red-300 bg-white px-5 text-sm font-semibold text-red-700 hover:bg-red-50">
                    Tolak
                </button>
            </form>
        </div>
        @endif
    </div>

    <a href="{{ route('speedshop.verifikasi.pengambilan-dividen.index') }}" class="inline-flex text-sm font-medium text-gray-600 hover:text-gray-900">&larr; Kembali ke daftar</a>
</div>
@endsection
