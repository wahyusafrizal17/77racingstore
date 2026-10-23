@extends('layouts.speedshop')

@section('title', 'Hutang — '.$supplier->nama)

@section('content')
@php
    use App\Models\WarehouseSupplierTagihan;
    $summary = $summary ?? ['jumlah_invoice' => 0, 'jumlah_belum_lunas' => 0, 'jumlah_lunas' => 0, 'total_sisa' => 0, 'total_nominal' => 0];
    $indexRoute = 'speedshop.keuangan.hutang.index';
@endphp
@push('styles')
@include('partials.speedshop.dashboard-load-animations')
@include('partials.speedshop.dashboard-manager-template-styles')
@include('partials.speedshop.work-order-create-styles')
<style>
.ss-dashboard .tagihan-summary-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 20px;
}
@media (max-width: 1024px) {
  .ss-dashboard .tagihan-summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 640px) {
  .ss-dashboard .tagihan-summary-grid { grid-template-columns: 1fr; }
}
.ss-dashboard .pay-icon.blue { background: rgba(46,107,230,0.10); color: #2E6BE6; }
.ss-dashboard .pay-icon.red { background: rgba(239,68,68,0.10); color: #EF4444; }
.ss-dashboard .pay-icon.amber { background: rgba(245,158,11,0.10); color: #D97706; }
.ss-dashboard .pay-icon.green { background: rgba(16,185,129,0.10); color: #10B981; }
</style>
@endpush

<div
    class="ss-dashboard ss-dash-page ss-wo-create"
    x-init="$nextTick(() => { requestAnimationFrame(() => $el.classList.add('is-ready')); })"
>
<div class="ss-dash-content space-y-5">
    <div class="flex flex-col gap-3 ss-dash-enter sm:flex-row sm:items-start sm:justify-between" style="--ss-delay: 0ms">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">{{ $supplier->nama }}</h1>
            </div>
            <p class="mt-1 text-[13px] text-gray-500">
                Hutang per Stok In
                @if($supplier->kode_supplier)
                · {{ $supplier->kode_supplier }}
                @endif
            </p>
        </div>
        <a href="{{ route($indexRoute) }}" class="btn-secondary shrink-0">← Kembali</a>
    </div>

    <div class="tagihan-summary-grid ss-dash-enter" style="--ss-delay: 80ms">
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 0">
            <div class="pay-card-top">
                <span class="pay-card-label">Stok In</span>
                <span class="pay-icon blue">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </span>
            </div>
            <div class="pay-amount">{{ number_format($summary['jumlah_invoice'] ?? 0, 0, ',', '.') }}</div>
            <p class="pay-period">Invoice hutang supplier ini</p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 1">
            <div class="pay-card-top">
                <span class="pay-card-label">Belum lunas</span>
                <span class="pay-icon red">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="pay-amount">{{ number_format($summary['jumlah_belum_lunas'] ?? 0, 0, ',', '.') }}</div>
            <p class="pay-period">Stok In yang masih hutang</p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 2">
            <div class="pay-card-top">
                <span class="pay-card-label">Sisa hutang</span>
                <span class="pay-icon amber">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="pay-amount">Rp {{ number_format($summary['total_sisa'] ?? 0, 0, ',', '.') }}</div>
            <p class="pay-period">Nominal belum dibayar</p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 3">
            <div class="pay-card-top">
                <span class="pay-card-label">Lunas</span>
                <span class="pay-icon green">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </span>
            </div>
            <div class="pay-amount">{{ number_format($summary['jumlah_lunas'] ?? 0, 0, ',', '.') }}</div>
            <p class="pay-period">Stok In sudah dilunasi</p>
        </div>
    </div>

    <div class="panel-card panel-card-flush ss-dash-enter" style="--ss-delay: 120ms">
        <div class="border-b border-gray-100 px-5 py-4">
            <h2 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">Daftar Stok In</h2>
            <p class="mt-1 text-[13px] text-gray-500">Pilih invoice untuk melihat rincian dan mencatat pembayaran</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-max text-left text-sm">
                <thead>
                    <tr class="bg-[#f7f3f4]">
                        <th class="w-16 px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No.</th>
                        <th class="min-w-[140px] px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No. Hutang</th>
                        <th class="min-w-[140px] px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Stok In</th>
                        <th class="min-w-[130px] px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Tanggal</th>
                        <th class="min-w-[140px] px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Jatuh tempo</th>
                        <th class="min-w-[140px] px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-gray-500">Total</th>
                        <th class="min-w-[140px] px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-gray-500">Sisa</th>
                        <th class="min-w-[120px] px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Status</th>
                        <th class="w-28 px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($tagihans as $i => $t)
                    @php
                        $tgl = $t->tanggal_tagihan;
                        $jatuhTempo = $t->tanggal_jatuh_tempo ?? $t->warehouseTransaksi?->tanggal_jatuh_tempo;
                        $statusLabel = WarehouseSupplierTagihan::statusLabel($t->status);
                        $statusClass = match ($t->status) {
                            WarehouseSupplierTagihan::STATUS_LUNAS => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                            default => 'border-red-200 bg-red-50 text-red-700',
                        };
                        $sisa = $t->sisaHutang();
                    @endphp
                    <tr class="transition hover:bg-[#fcfafb]">
                        <td class="px-5 py-4 tabular-nums text-gray-500">{{ $i + 1 }}</td>
                        <td class="px-5 py-4 align-top font-semibold text-gray-900">{{ $t->kode }}</td>
                        <td class="px-5 py-4 align-top text-gray-700">{{ $t->warehouseTransaksi?->kode ?? '—' }}</td>
                        <td class="px-5 py-4 align-top">
                            @if($tgl)
                            <span class="font-semibold text-gray-900">{{ $tgl->translatedFormat('j M Y') }}</span>
                            <p class="text-xs text-gray-500">{{ $tgl->translatedFormat('l') }}</p>
                            @else
                            <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 align-top text-gray-800">{{ $jatuhTempo?->translatedFormat('d M Y') ?? '—' }}</td>
                        <td class="px-5 py-4 align-top text-right font-semibold tabular-nums text-gray-900">Rp {{ number_format($t->total_nominal, 0, ',', '.') }}</td>
                        <td class="px-5 py-4 align-top text-right font-semibold tabular-nums {{ $sisa > 0 ? 'text-red-700' : 'text-emerald-700' }}">Rp {{ number_format($sisa, 0, ',', '.') }}</td>
                        <td class="px-5 py-4 align-top">
                            <span class="inline-flex items-center rounded-lg border px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide {{ $statusClass }}">{{ $statusLabel }}</span>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center justify-end">
                                <a href="{{ route('speedshop.keuangan.hutang.show', $t) }}" class="btn-detail" title="Detail">
                                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    Detail
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-5 py-12 text-center text-sm text-gray-500">Belum ada Stok In hutang untuk supplier ini.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
@endsection
