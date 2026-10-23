@extends('layouts.speedshop')

@php
    use App\Support\ProductMasterCatalog;

    $stokPage = $stokPage ?? [
        'title' => ProductMasterCatalog::label(ProductMasterCatalog::MO),
        'indexRoute' => ProductMasterCatalog::stokRouteBase(ProductMasterCatalog::MO),
        'auditRoute' => ProductMasterCatalog::stokRouteBase(ProductMasterCatalog::MO).'.audit',
        'auditApplyRoute' => ProductMasterCatalog::stokRouteBase(ProductMasterCatalog::MO).'.audit.apply',
    ];
@endphp

@section('title', 'Audit Stok · '.$stokPage['title'])

@section('content')
@push('styles')
@include('partials.speedshop.dashboard-load-animations')
@include('partials.speedshop.dashboard-manager-template-styles')
@include('partials.speedshop.work-order-create-styles')
<style>
.ss-dashboard .sp-summary-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 20px;
}
@media (max-width: 900px) {
  .ss-dashboard .sp-summary-grid { grid-template-columns: 1fr; }
}
.ss-dashboard .pay-icon.cyan { background: rgba(6,182,212,0.10); color: #0891B2; }
.ss-dashboard .pay-icon.teal { background: rgba(20,184,166,0.10); color: #14B8A6; }
.ss-dashboard .pay-icon.amber { background: rgba(245,158,11,0.10); color: #D97706; }
.ss-dashboard .audit-diff-match { color: #059669; }
.ss-dashboard .audit-diff-mismatch { color: #DC2626; font-weight: 600; }
</style>
@endpush

<div
    class="ss-dashboard ss-dash-page ss-wo-create"
    x-data="{ showOnlyMismatch: false }"
    x-init="$nextTick(() => { requestAnimationFrame(() => $el.classList.add('is-ready')); })"
>
<div class="ss-dash-content space-y-5">
    <div class="flex flex-col gap-3 ss-dash-enter sm:flex-row sm:items-start sm:justify-between" style="--ss-delay: 0ms">
        <div>
            <h1 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">Audit Stok · {{ $stokPage['title'] }}</h1>
            <p class="mt-1 text-[13px] text-gray-500">
                Hitung stok fisik untuk sampel acak · {{ number_format($auditPercent, 2, ',', '.') }}% dari {{ number_format($totalProducts) }} produk
                (min. {{ \App\Services\InvoiceSettingsService::STOK_AUDIT_MIN_PRODUCTS }}) = {{ number_format($sampleSize) }} item
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route($stokPage['indexRoute']) }}" class="btn-secondary inline-flex h-10 items-center px-4">Kembali</a>
            <a href="{{ route($stokPage['auditRoute'], ['refresh' => 1]) }}" class="btn-secondary inline-flex h-10 items-center gap-2 px-4">
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Acak Ulang
            </a>
        </div>
    </div>


    <div class="sp-summary-grid ss-dash-enter" style="--ss-delay: 80ms">
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 0">
            <div class="pay-card-top">
                <span class="pay-card-label">Total Produk</span>
                <span class="pay-icon cyan">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </span>
            </div>
            <div class="pay-amount">{{ number_format($totalProducts) }}</div>
            <p class="pay-period">Dalam scope inventori</p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 1">
            <div class="pay-card-top">
                <span class="pay-card-label">Sampel Audit</span>
                <span class="pay-icon teal">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                </span>
            </div>
            <div class="pay-amount">{{ number_format($sampleSize) }}</div>
            <p class="pay-period">Produk acak hari ini</p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 2">
            <div class="pay-card-top">
                <span class="pay-card-label">Persentase</span>
                <span class="pay-icon amber">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                </span>
            </div>
            <div class="pay-amount">{{ number_format($auditPercent, 2, ',', '.') }}%</div>
            <p class="pay-period">Diatur di Settings</p>
        </div>
    </div>

    <div class="panel-card panel-card-flush ss-dash-enter" style="--ss-delay: 120ms">
        <div class="flex flex-col gap-3 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">Daftar Audit</h2>
                <p class="mt-1 text-[13px] text-gray-500">Isi stok fisik hasil hitung, bandingkan dengan stok sistem</p>
            </div>
            @if($items->isNotEmpty())
            <label class="inline-flex cursor-pointer items-center gap-2 text-[13px] text-gray-600">
                <input type="checkbox" x-model="showOnlyMismatch" class="rounded border-gray-300 accent-brand text-brand focus:ring-brand">
                Tampilkan hanya selisih
            </label>
            @endif
        </div>

        @if(! $canApplyAdjustments)
        <div class="border-b border-amber-100 bg-amber-50 px-5 py-3 text-[13px] text-amber-900">
            Mode scope <strong>Semua Speedshop</strong>: penyesuaian stok dari audit tidak tersedia. Pilih satu cabang untuk menyimpan perubahan stok.
        </div>
        @endif

        @if($items->isEmpty())
        <div class="px-5 py-12 text-center text-gray-400">
            <p class="text-sm font-medium">Belum ada produk dalam scope inventori untuk diaudit.</p>
        </div>
        @else
        <form method="POST" action="{{ route($stokPage['auditApplyRoute']) }}">
            @csrf
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="bg-[#f7f3f4]">
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 w-16">No.</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Kode</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Nama Produk</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Kategori</th>
                            <th class="px-5 py-3.5 text-center text-[11px] font-semibold uppercase tracking-wider text-gray-500">Stok Sistem</th>
                            <th class="px-5 py-3.5 text-center text-[11px] font-semibold uppercase tracking-wider text-gray-500">Stok Fisik</th>
                            <th class="px-5 py-3.5 text-center text-[11px] font-semibold uppercase tracking-wider text-gray-500">Selisih</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    @foreach($items as $i => $product)
                    @php
                        $stokSistem = (int) (($speedshopStockMap[$product->id] ?? null)?->jumlah ?? 0);
                    @endphp
                    <tr
                        class="transition hover:bg-[#fcfafb]"
                        x-data="{ system: {{ $stokSistem }}, physical: {{ old('physical.'.$product->id, 'null') }} }"
                        x-show="!showOnlyMismatch || (physical !== null && physical !== '' && Number(physical) !== system)"
                    >
                        <td class="px-5 py-4 text-gray-500">{{ $i + 1 }}</td>
                        <td class="px-5 py-4 font-mono text-sm font-semibold text-gray-700">{{ $product->kode_produk }}</td>
                        <td class="px-5 py-4 font-semibold text-gray-900">{{ $product->nama_produk }}</td>
                        <td class="px-5 py-4 text-gray-600">{{ $product->category?->nama ?? '–' }}</td>
                        <td class="px-5 py-4 text-center">
                            <span class="inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-semibold {{ $stokSistem > 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                {{ number_format($stokSistem) }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-center">
                            <input
                                type="number"
                                min="0"
                                step="1"
                                name="physical[{{ $product->id }}]"
                                x-model="physical"
                                @if(! $canApplyAdjustments) disabled @endif
                                class="h-9 w-24 rounded-lg border border-gray-200 bg-white px-2 text-center text-[13px] tabular-nums outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/40 disabled:bg-gray-50 disabled:text-gray-400"
                                placeholder="–"
                            >
                        </td>
                        <td class="px-5 py-4 text-center tabular-nums">
                            <span
                                x-show="physical === null || physical === ''"
                                class="text-gray-400"
                            >–</span>
                            <span
                                x-show="physical !== null && physical !== '' && Number(physical) === system"
                                x-cloak
                                class="audit-diff-match"
                            >0</span>
                            <span
                                x-show="physical !== null && physical !== '' && Number(physical) !== system"
                                x-cloak
                                class="audit-diff-mismatch"
                                x-text="(Number(physical) - system) > 0 ? '+' + (Number(physical) - system) : (Number(physical) - system)"
                            ></span>
                        </td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            @if($canApplyAdjustments)
            <div class="flex flex-col gap-3 border-t border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-end">
                <p class="text-[13px] text-gray-500 sm:mr-auto">Produk dengan stok fisik terisi akan disesuaikan ke nilai fisik.</p>
                <button type="submit" class="btn-primary inline-flex h-10 items-center justify-center px-6">Simpan Penyesuaian</button>
            </div>
            @endif
        </form>
        @endif
    </div>
</div>
</div>
@endsection
