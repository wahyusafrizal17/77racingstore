@extends('layouts.speedshop')

@section('title', 'Detail Mutasi - ' . $mutasi->kode)

@section('content')
<div class="space-y-6">
    <nav class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('speedshop.dashboard') }}" class="transition hover:text-gray-700">Home</a>
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('speedshop.stock-in.pembelian-mo.index') }}" class="transition hover:text-gray-700">Purchase Order</a>
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <span class="font-medium text-gray-900">{{ $mutasi->kode }}</span>
    </nav>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-6">
            <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-bold text-gray-900">Informasi Mutasi</h2>
                <p class="mt-1 text-sm text-gray-500">Produk dari internal menuju Speedshop Anda</p>

                <div class="mt-5 flex flex-wrap items-end gap-3">
                    <div class="w-48">
                        <label class="mb-1.5 block text-xs font-semibold text-gray-600">Kode Mutasi</label>
                        <div class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm font-medium text-gray-900">{{ $mutasi->kode }}</div>
                    </div>
                    <div class="flex-1 min-w-[180px]">
                        <label class="mb-1.5 block text-xs font-semibold text-gray-600">No. Surat Jalan</label>
                        <div class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm font-medium text-gray-900">{{ $mutasi->nomor_surat_jalan }}</div>
                    </div>
                    <div class="w-44">
                        <label class="mb-1.5 block text-xs font-semibold text-gray-600">Tanggal</label>
                        <div class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700">{{ $mutasi->tanggal->format('d M Y') }}</div>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="mb-1.5 block text-xs font-semibold text-gray-600">Dikirim oleh</label>
                    <div class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700">{{ $mutasi->user->name ?? '-' }}</div>
                </div>
                <div class="mt-4">
                    <label class="mb-1.5 block text-xs font-semibold text-gray-600">Status</label>
                    <div class="w-full">
                        @if($mutasi->isDikirim())
                            <span class="inline-flex items-center rounded-lg bg-yellow-100 px-2.5 py-0.5 text-xs font-semibold text-yellow-700">Dikirim</span>
                        @else
                            <span class="inline-flex items-center rounded-lg bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-700">Diterima</span>
                        @endif
                    </div>
                </div>

                @if($mutasi->catatan)
                <div class="mt-4">
                    <label class="mb-1.5 block text-xs font-semibold text-gray-600">Catatan</label>
                    <div class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700">{{ $mutasi->catatan }}</div>
                </div>
                @endif
            </div>

            <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold text-gray-900">Daftar Produk</h2>
                    <span class="inline-flex items-center rounded-lg bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600">{{ $mutasi->items->count() }} produk</span>
                </div>

                <div class="mt-4 overflow-x-auto rounded-xl border border-gray-100">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-5 py-3.5 text-xs font-bold uppercase tracking-wider text-gray-500">No.</th>
                                <th class="px-5 py-3.5 text-xs font-bold uppercase tracking-wider text-gray-500">Kode</th>
                                <th class="px-5 py-3.5 text-xs font-bold uppercase tracking-wider text-gray-500">Nama Produk</th>
                                <th class="px-5 py-3.5 text-xs font-bold uppercase tracking-wider text-gray-500 text-center">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($mutasi->items as $i => $item)
                            <tr class="transition hover:bg-gray-50">
                                <td class="px-5 py-4 text-gray-500">{{ $i + 1 }}</td>
                                <td class="px-5 py-4 font-medium text-gray-900">{{ $item->product->kode_produk ?? '-' }}</td>
                                <td class="px-5 py-4 text-gray-700">{{ $item->product->nama_produk ?? '-' }}</td>
                                <td class="px-5 py-4 text-center font-semibold text-gray-900">{{ $item->quantity }}</td>
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
                    <div class="flex items-center justify-between rounded-lg bg-blue-50 px-4 py-3">
                        <span class="text-sm font-medium text-blue-700">Total Produk</span>
                        <span class="text-sm font-bold text-blue-800">{{ $mutasi->items->count() }} item</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-green-50 px-4 py-3">
                        <span class="text-sm font-medium text-green-700">Total Unit</span>
                        <span class="text-sm font-bold text-green-800">{{ $mutasi->items->sum('quantity') }} unit</span>
                    </div>
                </div>

                @if($mutasi->isDikirim())
                @if(auth()->user()->isManager())
                <div class="mt-5 space-y-3">
                    <button type="button" onclick="verifyMutasi()" id="btn-verify" class="flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-500 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-600">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        Verifikasi & Konfirmasi Penerimaan
                    </button>
                    <button type="button" onclick="rejectMutasi()" id="btn-reject" class="flex w-full items-center justify-center gap-2 rounded-xl border border-red-200 bg-red-50 px-5 py-3 text-sm font-semibold text-red-700 shadow-sm transition hover:bg-red-100">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        Tolak
                    </button>
                    <p class="text-center text-xs text-amber-700">Klik untuk memverifikasi. Stok produk akan ditambahkan.</p>
                </div>
                @else
                <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 p-3">
                    <p class="text-xs font-semibold text-amber-800">Menunggu konfirmasi</p>
                    <p class="mt-1 text-xs text-amber-700">Hubungi Manager Speedshop untuk konfirmasi penerimaan.</p>
                </div>
                @endif
                @endif

                <div class="mt-5">
                    <a href="{{ route('speedshop.stock-in.pembelian-mo.index') }}" class="flex w-full items-center justify-center gap-2 rounded-xl border border-gray-200 px-5 py-3 text-sm font-medium text-gray-600 transition hover:bg-gray-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        Kembali ke Daftar
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@if($mutasi->isDikirim() && auth()->user()->isManager())
@push('scripts')
<script>
const verifyUrl = '{{ route("speedshop.stock-in.pembelian-mo.verify-mutasi", $mutasi) }}';
const rejectUrl = '{{ route("speedshop.stock-in.pembelian-mo.reject-mutasi", $mutasi) }}';

async function verifyMutasi() {
    const result = await Swal.fire({
        title: 'Verifikasi Mutasi?',
        text: 'Stok produk akan ditambahkan ke Speedshop. Tindakan ini tidak dapat dibatalkan.',
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

        await Swal.fire('Berhasil', data.message || 'Mutasi berhasil diverifikasi.', 'success');
        window.location.reload();
    } catch (e) {
        Swal.fire('Error', 'Terjadi kesalahan. Coba lagi.', 'error');
        if (btn) { btn.disabled = false; btn.classList.remove('opacity-70'); }
    }
}

async function rejectMutasi() {
    const result = await Swal.fire({
        title: 'Tolak Mutasi?',
        input: 'textarea',
        inputLabel: 'Alasan (opsional)',
        inputPlaceholder: 'Tulis alasan penolakan...',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Tolak',
        cancelButtonText: 'Batal',
    });
    if (!result.isConfirmed) return;

    const btn = document.getElementById('btn-reject');
    const btnVerify = document.getElementById('btn-verify');
    if (btn) { btn.disabled = true; btn.classList.add('opacity-70'); }
    if (btnVerify) { btnVerify.disabled = true; btnVerify.classList.add('opacity-70'); }

    try {
        const res = await fetch(rejectUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ alasan: result.value || null })
        });
        const data = await res.json();

        if (!res.ok) {
            Swal.fire('Gagal', data.message || 'Penolakan gagal.', 'error');
            if (btn) { btn.disabled = false; btn.classList.remove('opacity-70'); }
            if (btnVerify) { btnVerify.disabled = false; btnVerify.classList.remove('opacity-70'); }
            return;
        }

        await Swal.fire('Berhasil', data.message || 'Mutasi berhasil ditolak.', 'success');
        window.location.reload();
    } catch (e) {
        Swal.fire('Error', 'Terjadi kesalahan. Coba lagi.', 'error');
        if (btn) { btn.disabled = false; btn.classList.remove('opacity-70'); }
        if (btnVerify) { btnVerify.disabled = false; btnVerify.classList.remove('opacity-70'); }
    }
}
</script>
@endpush
@endif
@endsection
