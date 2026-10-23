@extends('layouts.speedshop')

@section('title', 'Detail Tagihan')

@section('content')
@push('styles')
@include('partials.speedshop.dashboard-load-animations')
@include('partials.speedshop.dashboard-manager-template-styles')
@include('partials.speedshop.work-order-create-styles')
@endpush

@php
    $tz = config('app.timezone');
    $indexRoute = 'speedshop.keuangan.tagihan.index';
    $isBelumLunas = $tagihan->isBelumLunas();
    $isDibayar = $tagihan->isDibayar();
    $isLunas = $tagihan->isLunas();
    $statusLabel = \App\Models\SpeedshopPoTagihan::statusLabel($tagihan->status);
    $statusBadgeClass = match ($tagihan->status) {
        \App\Models\SpeedshopPoTagihan::STATUS_DIBAYAR => 'border-amber-200 bg-amber-50 text-amber-700',
        \App\Models\SpeedshopPoTagihan::STATUS_LUNAS => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        default => 'border-red-200 bg-red-50 text-red-700',
    };
    $steps = [
        ['label' => 'Tagihan terbit', 'done' => true],
        ['label' => 'Dibayar Speedshop', 'done' => $isDibayar || $isLunas],
        ['label' => 'Lunas dikonfirmasi', 'done' => $isLunas],
    ];
    $currentStep = $isBelumLunas ? 0 : ($isDibayar ? 1 : 2);
    $itemCount = $tagihan->items->count();
    $canMarkPaid = $isBelumLunas && (auth()->user()->isAccounting() || auth()->user()->isManager());
    $woPanelClass = 'panel-card panel-card-flush overflow-visible ss-dash-enter-child';
@endphp

<div
    class="ss-dashboard ss-dash-page ss-wo-create"
    x-init="$nextTick(() => { requestAnimationFrame(() => $el.classList.add('is-ready')); })"
>
<div class="ss-dash-content space-y-5">
    <div class="flex flex-col gap-3 ss-dash-enter sm:flex-row sm:items-start sm:justify-between" style="--ss-delay: 0ms">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">{{ $tagihan->kode }}</h1>
                <span class="inline-flex items-center rounded-lg border px-2.5 py-1 text-xs font-semibold uppercase tracking-wide {{ $statusBadgeClass }}">{{ $statusLabel }}</span>
            </div>
            <p class="mt-1 text-[13px] text-gray-500">
                Tagihan PO ke warehouse · {{ $tagihan->purchaseOrder?->kode ?? '—' }} · {{ $tagihan->tanggal_tagihan?->translatedFormat('d M Y') ?? '—' }}
            </p>
        </div>
        <a href="{{ route($indexRoute) }}" class="btn-secondary shrink-0">← Kembali</a>
    </div>


    <div class="grid grid-cols-1 gap-5 lg:grid-cols-12 ss-dash-enter" style="--ss-delay: 80ms">
        <div class="space-y-5 lg:col-span-8">
            <div class="{{ $woPanelClass }}" style="--ss-delay: 120ms; --ss-stagger: 0">
                <div class="flex items-start justify-between border-b border-gray-100 px-5 py-4">
                    <div>
                        <h2 class="text-[20px] font-semibold text-gray-900">Tagihan PO ke Warehouse</h2>
                        <p class="mt-1 text-[13px] text-gray-500">Informasi tagihan dan pihak terkait</p>
                    </div>
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-red-50 text-red-600">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                </div>
                <div class="space-y-4 px-5 py-5">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">No. tagihan</label>
                            <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm font-semibold text-gray-900">{{ $tagihan->kode }}</div>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">No. PO</label>
                            <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm font-semibold text-gray-900">{{ $tagihan->purchaseOrder?->kode ?? '—' }}</div>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Warehouse</label>
                            <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700">{{ $tagihan->warehouse?->nama ?? '—' }}</div>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Tanggal tagihan</label>
                            <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700">{{ $tagihan->tanggal_tagihan?->translatedFormat('d M Y') ?? '—' }}</div>
                        </div>
                    </div>

                    @if($tagihan->dibayar_at)
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Dibayar pada</label>
                            <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700">{{ $tagihan->dibayar_at->timezone($tz)->format('d M Y H:i') }}</div>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Dicatat oleh</label>
                            <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700">{{ $tagihan->dibayarByUser?->name ?? '—' }}</div>
                        </div>
                    </div>
                    @endif

                    @if($tagihan->catatan_pembayaran)
                    <div>
                        <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Catatan pembayaran</label>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700 whitespace-pre-wrap">{{ $tagihan->catatan_pembayaran }}</div>
                    </div>
                    @endif

                    @if($tagihan->bukti_bayar_path)
                    <div>
                        <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Bukti pembayaran</label>
                        <a href="{{ asset('storage/'.$tagihan->bukti_bayar_path) }}" target="_blank" rel="noopener" class="btn-secondary inline-flex items-center gap-2 border-red-200 bg-red-50 px-4 py-2.5 text-red-600 hover:bg-brand-hover hover:text-white">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            Lihat bukti
                        </a>
                    </div>
                    @endif
                </div>
            </div>

            <div class="{{ $woPanelClass }}" style="--ss-delay: 120ms; --ss-stagger: 1">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <div>
                        <h2 class="text-[20px] font-semibold text-gray-900">Rincian barang</h2>
                        <p class="mt-1 text-[13px] text-gray-500">Item dari Purchase Order yang diverifikasi</p>
                    </div>
                    <span class="wo-badge">{{ $itemCount }} item</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="bg-[#f7f3f4]">
                                <th class="px-4 py-3 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No.</th>
                                <th class="px-4 py-3 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Produk</th>
                                <th class="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-gray-500">Qty</th>
                                <th class="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-gray-500">Harga</th>
                                <th class="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-gray-500">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($tagihan->items as $i => $item)
                            <tr class="transition hover:bg-[#fcfafb]">
                                <td class="px-4 py-3 text-gray-500">{{ $i + 1 }}</td>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-gray-900">{{ $item->product?->nama_produk ?? '—' }}</div>
                                    <div class="mt-0.5 font-mono text-xs text-gray-500">{{ $item->product?->kode_produk ?? '' }}</div>
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums text-gray-800">{{ $item->qty }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-gray-700">Rp {{ number_format($item->harga_satuan, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right font-semibold tabular-nums text-gray-900">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t border-gray-200 bg-[#fbf8f9]">
                                <td colspan="4" class="px-4 py-3 text-right text-sm font-semibold text-gray-700">Total tagihan</td>
                                <td class="px-4 py-3 text-right text-base font-bold tabular-nums text-gray-900">Rp {{ number_format($tagihan->total_nominal, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="space-y-4 lg:col-span-4">
            <div class="panel-card panel-card-flush summary-card ss-dash-enter-child" style="--ss-delay: 120ms; --ss-stagger: 2">
                <div class="section-hd">
                    <h2>Ringkasan</h2>
                    <p>Status dan nominal tagihan</p>
                </div>

                <div class="mt-2 mb-2">
                    <div class="summary-row">
                        <span>Status</span>
                        <span class="inline-flex items-center rounded-lg border px-2 py-0.5 text-xs font-semibold uppercase tracking-wide {{ $statusBadgeClass }}">{{ $statusLabel }}</span>
                    </div>
                    <div class="summary-row">
                        <span>Total tagihan</span>
                        <span class="tabular-nums">Rp {{ number_format($tagihan->total_nominal, 0, ',', '.') }}</span>
                    </div>
                    <div class="summary-total">
                        <span>Nominal dibayar</span>
                        <span>{{ ($isDibayar || $isLunas) ? 'Rp '.number_format($tagihan->total_nominal, 0, ',', '.') : '—' }}</span>
                    </div>
                </div>

                <div class="border-t border-gray-100 px-5 py-4">
                    <label class="mb-3 block text-[11px] font-semibold uppercase tracking-wider text-gray-500">Alur pembayaran</label>
                    <div class="flex items-start justify-between gap-1">
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
                                <div class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full bg-green-500 shadow-sm shadow-green-200">
                                    <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </div>
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
                            <p class="mt-2 text-center text-[10px] font-medium leading-tight sm:text-xs {{ $step['done'] ? 'text-green-600' : ($idx === $currentStep ? 'text-amber-600' : 'text-gray-400') }}">{{ $step['label'] }}</p>
                        </div>
                        @endforeach
                    </div>
                    @if($isLunas && $tagihan->lunas_at)
                    <p class="mt-3 text-center text-xs text-gray-500">
                        Dikonfirmasi warehouse · {{ $tagihan->lunas_at->timezone($tz)->format('d M Y H:i') }}
                    </p>
                    @elseif($isDibayar)
                    <p class="mt-3 text-center text-xs text-gray-500">Menunggu konfirmasi penerimaan dari Warehouse</p>
                    @elseif($isBelumLunas)
                    <p class="mt-3 text-center text-xs text-gray-500">Silakan lakukan pembayaran ke Warehouse</p>
                    @endif
                </div>

                @if($canMarkPaid)
                <div class="space-y-2.5 border-t border-gray-100 px-5 pb-5 pt-4">
                    <form method="POST" action="{{ route('speedshop.keuangan.tagihan.bayar', $tagihan) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="wo-label">Catatan pembayaran</label>
                            <textarea name="catatan_pembayaran" rows="2" maxlength="1000" class="wo-input wo-native min-h-[4.5rem] resize-y py-2.5" placeholder="Opsional…">{{ old('catatan_pembayaran') }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="wo-label">Bukti bayar (opsional)</label>
                            <input type="file" name="bukti_bayar" accept=".jpg,.jpeg,.png,.pdf" class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-red-50 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-red-700">
                        </div>
                        <button type="submit" class="btn-primary flex h-11 w-full items-center justify-center gap-2">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            Tandai sudah dibayar
                        </button>
                    </form>
                </div>
                @elseif($isBelumLunas)
                <div class="border-t border-gray-100 px-5 py-4">
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900">
                        Tagihan ini menunggu pembayaran. Hanya Manager atau Accounting yang dapat menandai sudah dibayar.
                    </div>
                </div>
                @elseif($isDibayar)
                <div class="border-t border-gray-100 px-5 py-4">
                    <div class="rounded-xl border border-sky-200 bg-sky-50 p-3 text-xs text-sky-900">
                        Pembayaran telah dicatat. Warehouse akan mengonfirmasi penerimaan.
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
</div>
@endsection
