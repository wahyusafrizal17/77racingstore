@extends('layouts.speedshop')

@section('title', 'Work In Progress - ' . $service_order->kode_servis)

@section('content')
@php
    $isManager = auth()->user()->isManager();
    $jasaOptionsForJs = ($jasaServisOptions ?? collect())
        ->map(function ($jasa) {
            return [
                'id' => (int) $jasa->id,
                'nama' => (string) $jasa->nama,
                'biaya' => (int) $jasa->biaya,
            ];
        })
        ->values()
        ->all();
@endphp
<div x-data="selesaikanTransaksiShow()" class="space-y-5">
    <nav class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('speedshop.dashboard') }}" class="transition hover:text-gray-700">Home</a>
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('speedshop.wip') }}" class="transition hover:text-gray-700">Work In Progress</a>
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <span class="font-medium text-gray-900">{{ $service_order->kode_servis }}</span>
    </nav>

    <form id="form-wip-items" action="{{ route('speedshop.wip.items.update', $service_order) }}" method="POST" @submit.prevent="submitWipForm($event)">
        @csrf
        <div id="remove-jasa-container"></div>
        <div id="remove-sparepart-container"></div>
        <div id="add-jasa-container"></div>
        <div id="add-sparepart-container"></div>

        {{-- Reuse UI from selesaikan-transaksi --}}
        <div class="grid grid-cols-1 gap-5 lg:grid-cols-12">
            <div class="space-y-5 lg:col-span-8">
                @include('speedshop.selesaikan-transaksi._info', ['service_order' => $service_order])
                @include('speedshop.selesaikan-transaksi._jasa', ['service_order' => $service_order, 'jasaServisOptions' => $jasaServisOptions])
                @include('speedshop.selesaikan-transaksi._sparepart', ['service_order' => $service_order, 'productsForForm' => $productsForForm])
            </div>

            <div class="space-y-4 lg:col-span-4">
                @include('speedshop.selesaikan-transaksi._ringkasan', ['service_order' => $service_order])

                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white">
                    <div class="border-b border-gray-100 px-5 py-4">
                        <h2 class="text-[20px] font-semibold text-gray-900">Aksi</h2>
                        <p class="mt-1 text-[13px] text-gray-500">Simpan perubahan jasa &amp; sparepart (tanpa menyelesaikan transaksi).</p>
                    </div>
                    <div class="space-y-2 px-5 py-4">
                        <button type="submit" class="inline-flex h-11 w-full items-center justify-center rounded-xl bg-emerald-600 px-5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                            Simpan Perubahan
                        </button>
                        <a href="{{ route('speedshop.wip') }}" class="inline-flex h-11 w-full items-center justify-center rounded-xl border border-gray-200 bg-white px-5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                            Kembali
                        </a>
                        <a href="{{ route('speedshop.selesaikan-transaksi.show', $service_order) }}" class="inline-flex h-11 w-full items-center justify-center rounded-xl bg-gray-900 px-5 text-sm font-semibold text-white transition hover:bg-gray-800">
                            Ke Selesaikan Transaksi
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
    // Bridge: keep same JS component name, different submit.
    function selesaikanTransaksiShow() {
        const base = window.__selesaikanTransaksiShowFactory
            ? window.__selesaikanTransaksiShowFactory()
            : {};

        return {
            ...base,
            async submitWipForm(e) {
                const form = e.target;
                const fd = new FormData(form);
                try {
                    const res = await fetch(form.action, {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        body: fd,
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) {
                        const msg = data.message || 'Gagal menyimpan perubahan.';
                        if (window.Swal) {
                            Swal.fire({ icon: 'error', title: 'Tidak dapat menyimpan', html: msg });
                        } else {
                            alert(msg);
                        }
                        return;
                    }
                    const msg = data.message || 'Berhasil disimpan.';
                    if (window.Swal) {
                        await Swal.fire({ icon: 'success', title: 'Tersimpan', text: msg, timer: 1200, showConfirmButton: false });
                    }
                    window.location.href = data.redirect || window.location.href;
                } catch (err) {
                    if (window.Swal) {
                        Swal.fire({ icon: 'error', title: 'Tidak dapat menyimpan', text: 'Terjadi kesalahan jaringan.' });
                    } else {
                        alert('Terjadi kesalahan jaringan.');
                    }
                }
            },
        };
    }
</script>
@endpush


