@extends('layouts.speedshop')

@section('title', 'Detail Work Order - ' . $service_order->kode_servis)

@section('content')
@push('styles')
@include('partials.speedshop.dashboard-load-animations')
@include('partials.speedshop.work-order-create-styles')
@endpush

@php
    $woPanelClass = 'panel-card panel-card-flush overflow-visible ss-dash-enter-child';
@endphp

<div
    x-data="workOrderShow()"
    x-init="init(); $nextTick(() => { requestAnimationFrame(() => $el.classList.add('is-ready')); })"
    class="ss-dashboard ss-dash-page ss-wo-create"
>
<div class="ss-dash-content space-y-5">

    @if($errors->any())
    <div class="ss-dash-enter wo-alert" style="--ss-delay: 40ms">
        <p class="font-semibold">Tidak dapat menyerahkan ke mekanik:</p>
        <ul class="mt-1 list-inside list-disc">
            @foreach($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="flex flex-col gap-3 ss-dash-enter sm:flex-row sm:items-start sm:justify-between" style="--ss-delay: 0ms">
        <div>
            <h1 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">{{ $service_order->kode_servis }}</h1>
            <p class="mt-1 text-[13px] text-gray-500">Detail work order</p>
        </div>
        <a href="{{ route('speedshop.wip') }}" class="btn-secondary shrink-0">← Kembali</a>
    </div>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-12 ss-dash-enter" style="--ss-delay: 80ms">
        {{-- Left: Content --}}
        <div class="space-y-5 lg:col-span-8">
            {{-- Informasi Work Order --}}
            <div class="{{ $woPanelClass }}" style="--ss-delay: 120ms; --ss-stagger: 0">
                <div class="flex items-start justify-between border-b border-gray-100 px-5 py-4">
                    <div>
                        <h2 class="text-[20px] font-semibold text-gray-900">Informasi Work Order</h2>
                        <p class="mt-1 text-[13px] text-gray-500">Data pelanggan, kendaraan, dan detail service</p>
                    </div>
                    <div class="flex items-center gap-2">
                        @if($service_order->status === 'antri')
                            <span class="inline-flex items-center gap-1 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">Antrian</span>
                        @elseif($service_order->status === 'proses')
                            <span class="inline-flex items-center gap-1 rounded-full border border-blue-200 bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">Dikerjakan</span>
                        @elseif($service_order->status === 'selesai')
                            <span class="inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Selesai</span>
                        @else
                            <span class="inline-flex items-center gap-1 rounded-full border border-rose-200 bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700">{{ ucfirst($service_order->status) }}</span>
                        @endif
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-red-50 text-red-600">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 px-5 py-5 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Pelanggan</label>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm font-medium text-gray-900">{{ $service_order->pelanggan->nama ?? '-' }}</div>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">No HP</label>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700">{{ $service_order->pelanggan->no_hp ?? '-' }}</div>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Alamat</label>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700">{{ $service_order->pelanggan->alamat ?? '-' }}</div>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Kendaraan &amp; Service</label>
                        <div class="rounded-xl border border-gray-200 bg-[#f7f4f5]">
                            <div class="flex items-center gap-3 border-b border-gray-200 px-4 py-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand text-brand-ink shadow-md shadow-brand/35">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h12m-9-8.25h-1.5a2.25 2.25 0 0 0-2.25 2.25v7.5a2.25 2.25 0 0 0 2.25 2.25h3a2.25 2.25 0 0 0 2.25-2.25v-7.5a2.25 2.25 0 0 0-2.25-2.25H9.75m-6 0V9.75A2.25 2.25 0 0 1 5.25 7.5h13.5A2.25 2.25 0 0 1 21 9.75v1.5m-18 0A2.25 2.25 0 0 0 5.25 12.75h13.5A2.25 2.25 0 0 0 21 11.25v-1.5"/></svg>
                                </div>
                                <div class="min-w-0">
                                    <div class="truncate text-base font-semibold text-gray-900">{{ $service_order->kendaraan?->nomor_polisi ?? '—' }}</div>
                                    <div class="text-[11px] uppercase tracking-wide text-gray-500">Identitas Kendaraan &amp; Layanan</div>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 gap-1 px-4 py-3 sm:grid-cols-3">
                                <div>
                                    <div class="text-[11px] font-medium uppercase tracking-wide text-gray-500">Merk / Tipe</div>
                                    <div class="mt-0.5 text-sm font-semibold text-gray-900">{{ $service_order->kendaraan ? trim(($service_order->kendaraan->merk ?? '').' '.($service_order->kendaraan->tipe ?? '')) ?: '—' : '—' }}</div>
                                </div>
                                <div>
                                    <div class="text-[11px] font-medium uppercase tracking-wide text-gray-500">Mekanik</div>
                                    <div class="mt-0.5 text-sm font-semibold text-gray-900">{{ $service_order->mekanik?->nama ?? '—' }}</div>
                                </div>
                                <div>
                                    <div class="text-[11px] font-medium uppercase tracking-wide text-gray-500">Sumber Kedatangan</div>
                                    <div class="mt-0.5 text-sm font-semibold text-gray-900">{{ $service_order->sumber_kedatangan ? (\App\Models\ServiceOrder::SUMBER_KEDATANGAN[$service_order->sumber_kedatangan] ?? $service_order->sumber_kedatangan) : '—' }}</div>
                                </div>
                                <div>
                                    <div class="text-[11px] font-medium uppercase tracking-wide text-gray-500">Kategori Service</div>
                                    <div class="mt-0.5 text-sm font-semibold text-gray-900">{{ $service_order->kategori_service ? (\App\Models\ServiceOrder::KATEGORI_SERVICE[$service_order->kategori_service] ?? $service_order->kategori_service) : '—' }}</div>
                                </div>
                                <div>
                                    <div class="text-[11px] font-medium uppercase tracking-wide text-gray-500">Tanggal Masuk</div>
                                    <div class="mt-0.5 text-sm font-semibold text-gray-900">{{ $service_order->tanggal_masuk?->format('d M Y') ?? '—' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @if($service_order->keluhan)
                <div class="px-5 pb-5">
                    <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Keluhan</label>
                    <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700">{{ $service_order->keluhan }}</div>
                </div>
                @endif
            </div>

            {{-- Daftar Pekerjaan --}}
            @if($service_order->jasaServis->isNotEmpty())
            <div class="{{ $woPanelClass }}" style="--ss-delay: 120ms; --ss-stagger: 1">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <div>
                        <h2 class="text-[20px] font-semibold text-gray-900">Daftar Pekerjaan</h2>
                        <p class="mt-1 text-[13px] text-gray-500">Jasa yang dikerjakan pada work order ini</p>
                    </div>
                    <span class="wo-badge">{{ $service_order->jasaServis->count() }} jasa</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="bg-[#f7f3f4]">
                                <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No.</th>
                                <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Nama Jasa</th>
                                <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-right">Biaya</th>
                                @if($service_order->status === 'selesai')
                                <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-right">Diskon</th>
                                <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-right">Setelah Diskon</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($service_order->jasaServis as $i => $j)
                            @php
                                $qty = (int) ($j->pivot->qty ?? 1);
                                $biaya = $qty * (int) $j->pivot->biaya;
                                $diskon = (int) ($j->pivot->diskon ?? 0);
                            @endphp
                            <tr class="transition hover:bg-[#fcfafb]">
                                <td class="px-5 py-4 text-gray-500">{{ $i + 1 }}</td>
                                <td class="px-5 py-4 font-semibold text-gray-900">{{ $j->nama }} @if($qty > 1)<span class="text-xs font-normal text-gray-500">({{ $qty }}x)</span>@endif</td>
                                <td class="px-5 py-4 text-right text-gray-700">Rp {{ number_format($biaya, 0, ',', '.') }}</td>
                                @if($service_order->status === 'selesai')
                                <td class="px-5 py-4 text-right text-amber-600">Rp {{ number_format($diskon, 0, ',', '.') }}</td>
                                <td class="px-5 py-4 text-right font-medium text-gray-900">Rp {{ number_format($biaya - $diskon, 0, ',', '.') }}</td>
                                @endif
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Daftar Sparepart --}}
            @if($service_order->products->isNotEmpty())
            <div class="{{ $woPanelClass }}" style="--ss-delay: 120ms; --ss-stagger: 2">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <div>
                        <h2 class="text-[20px] font-semibold text-gray-900">Daftar Sparepart</h2>
                        <p class="mt-1 text-[13px] text-gray-500">Item sparepart yang digunakan</p>
                    </div>
                    <span class="wo-badge">{{ $service_order->products->count() }} item</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="bg-[#f7f3f4]">
                                <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No.</th>
                                <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Kode</th>
                                <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Nama</th>
                                <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-center">Qty</th>
                                <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-right">Harga</th>
                                <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-right">Subtotal</th>
                                @if($service_order->status === 'selesai')
                                <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-right">Diskon</th>
                                <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-right">Setelah Diskon</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($service_order->products as $i => $p)
                            @php $subtotal = $p->pivot->qty * $p->pivot->harga; $diskon = (int) ($p->pivot->diskon ?? 0); @endphp
                            <tr class="transition hover:bg-[#fcfafb]">
                                <td class="px-5 py-4 text-gray-500">{{ $i + 1 }}</td>
                                <td class="px-5 py-4 font-semibold text-gray-900">{{ $p->kode_produk }}</td>
                                <td class="px-5 py-4 text-gray-700">{{ $p->nama_produk }}</td>
                                <td class="px-5 py-4 text-center">{{ $p->pivot->qty }}</td>
                                <td class="px-5 py-4 text-right">Rp {{ number_format($p->pivot->harga, 0, ',', '.') }}</td>
                                <td class="px-5 py-4 text-right">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                                @if($service_order->status === 'selesai')
                                <td class="px-5 py-4 text-right text-amber-600">Rp {{ number_format($diskon, 0, ',', '.') }}</td>
                                <td class="px-5 py-4 text-right font-medium text-gray-900">Rp {{ number_format($subtotal - $diskon, 0, ',', '.') }}</td>
                                @endif
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>

        {{-- Right: Ringkasan & Aksi --}}
        <div class="space-y-4 lg:col-span-4">
            @php
                $totalJasaRingkasan = (int) $service_order->total_jasa;
                $totalSparepartRingkasan = (int) $service_order->total_sparepart;
                $baseTotalRingkasan = (int) $service_order->grand_total;
                $invoiceSettings = app(\App\Services\InvoiceSettingsService::class)->get();

                $chargeModeRingkasan = (string) ($service_order->invoice?->charge_mode ?? ($invoiceSettings['invoice_charge_mode'] ?? 'percent'));
                $chargePercentRingkasan = (float) ($service_order->invoice?->charge_percent ?? ($invoiceSettings['invoice_charge_percent'] ?? 0));
                $chargeRupiahRingkasan = (int) ($service_order->invoice?->charge_rupiah ?? ($invoiceSettings['invoice_charge_rupiah'] ?? 0));
                $chargeAmountRingkasan = $service_order->invoice
                    ? \App\Services\InvoiceSettingsService::resolveChargeAmountForInvoice(
                        $service_order->invoice,
                        $baseTotalRingkasan,
                    )
                    : \App\Services\InvoiceSettingsService::calculateChargeAmount(
                        $baseTotalRingkasan,
                        $chargeModeRingkasan,
                        $chargePercentRingkasan,
                        $chargeRupiahRingkasan,
                    );

                $ppnModeRingkasan = (string) ($service_order->invoice?->ppn_mode ?? ($invoiceSettings['invoice_ppn_mode'] ?? 'percent'));
                $ppnPercentRingkasan = (float) ($service_order->invoice?->ppn_percent ?? ($invoiceSettings['invoice_ppn_percent'] ?? 0));
                $ppnRupiahRingkasan = (int) ($service_order->invoice?->ppn_rupiah ?? ($invoiceSettings['invoice_ppn_rupiah'] ?? 0));
                $ppnAmountRingkasan = $service_order->invoice
                    ? (int) ($service_order->invoice?->ppn_amount ?? 0)
                    : ($ppnModeRingkasan === 'rupiah'
                        ? max(0, $ppnRupiahRingkasan)
                        : (int) round($baseTotalRingkasan * ($ppnPercentRingkasan / 100)));

                $totalAkhirRingkasan = $service_order->invoice
                    ? $service_order->finalGrandTotal()
                    : ($baseTotalRingkasan + $chargeAmountRingkasan + $ppnAmountRingkasan);
            @endphp
            <div class="panel-card panel-card-flush summary-card ss-dash-enter-child" style="--ss-delay: 120ms; --ss-stagger: 3">
                <div class="section-hd">
                    <h2>Ringkasan</h2>
                    <p>{{ $service_order->status === 'selesai' ? 'Rincian biaya transaksi' : 'Estimasi biaya work order' }}</p>
                </div>
                <div class="mt-2 mb-2">
                    <div class="summary-row">
                        <span>Total Jasa</span>
                        <span>Rp {{ number_format($totalJasaRingkasan, 0, ',', '.') }}</span>
                    </div>
                    <div class="summary-row">
                        <span>Total Sparepart</span>
                        <span>Rp {{ number_format($totalSparepartRingkasan, 0, ',', '.') }}</span>
                    </div>
                    <div class="summary-row">
                        <span>Biaya Charge</span>
                        <span>Rp {{ number_format($chargeAmountRingkasan, 0, ',', '.') }}</span>
                    </div>
                    <div class="summary-row">
                        <span>PPN</span>
                        <span>Rp {{ number_format($ppnAmountRingkasan, 0, ',', '.') }}</span>
                    </div>
                    <div class="summary-total">
                        <span>{{ $service_order->status === 'selesai' ? 'Total Bayar' : 'Estimasi Total' }}</span>
                        <span>Rp {{ number_format($totalAkhirRingkasan, 0, ',', '.') }}</span>
                    </div>
                </div>
                <div class="border-t border-gray-100 px-5 py-4">
                    <label class="mb-3 block text-[11px] font-semibold uppercase tracking-wider text-gray-500">Alur Pengerjaan</label>
                    @php
                        $currentStep = match($service_order->status) {
                            'antri' => 1,
                            'proses' => 2,
                            'selesai' => 4,
                            default => 1,
                        };
                        $steps = [
                            ['label' => 'Pendaftaran'],
                            ['label' => 'Antrian'],
                            ['label' => 'Dikerjakan'],
                            ['label' => 'Selesai'],
                        ];
                    @endphp
                    <div class="flex items-start justify-between gap-1">
                        @foreach($steps as $idx => $step)
                        @php $isDone = $idx < $currentStep; @endphp
                        <div class="flex flex-col items-center" style="width: {{ 100 / count($steps) }}%">
                            <div class="relative flex w-full items-center justify-center">
                                @if($idx > 0)
                                <div class="absolute right-1/2 h-0.5 w-full {{ $isDone ? 'bg-green-400' : 'bg-gray-200' }}"></div>
                                @endif
                                @if($idx < count($steps) - 1)
                                <div class="absolute left-1/2 h-0.5 w-full {{ ($idx + 1) < $currentStep ? 'bg-green-400' : 'bg-gray-200' }}"></div>
                                @endif
                                @if($isDone)
                                    <div class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full bg-green-500 shadow-sm shadow-green-200">
                                        <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    </div>
                                @elseif($idx === $currentStep)
                                    <div class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full border-2 border-amber-400 bg-amber-50">
                                        <div class="h-2.5 w-2.5 rounded-full bg-yellow-400 animate-pulse"></div>
                                    </div>
                                @else
                                    <div class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full bg-gray-200">
                                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    </div>
                                @endif
                            </div>
                            <p class="mt-2 text-center text-xs font-medium {{ $isDone ? 'text-green-600' : ($idx === $currentStep ? 'text-amber-600' : 'text-gray-400') }}">{{ $step['label'] }}</p>
                        </div>
                        @endforeach
                    </div>
                </div>

                @if($service_order->status === 'selesai')
                @php
                    $wacloudReady = app(\App\Services\WacloudMessageService::class)->isConfigured();
                    $digitsInv = preg_replace('/\D/', '', (string) ($service_order->pelanggan?->no_hp ?? ''));
                    $hpValidForWa = false;
                    if ($digitsInv !== '') {
                        if (str_starts_with($digitsInv, '0')) {
                            $digitsInv = '62'.substr($digitsInv, 1);
                        } elseif (! str_starts_with($digitsInv, '62')) {
                            $digitsInv = '62'.$digitsInv;
                        }
                        $hpValidForWa = strlen($digitsInv) >= 11;
                    }
                    $waDisabledTitle = ! $wacloudReady
                        ? 'Isi WACLOUD_API_KEY dan device ID outlet (Bandung/Depok) di .env'
                        : (! $hpValidForWa ? 'Nomor HP pelanggan tidak valid' : '');
                @endphp
                <div class="space-y-3 border-t border-gray-100 px-5 pb-5 pt-5">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Invoice</p>
                    <a href="{{ route('speedshop.work-orders.invoice.thermal', $service_order) }}" target="_blank" rel="noopener noreferrer" class="btn-primary flex h-11 w-full">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9"/></svg>
                        Cetak Invoice
                    </a>
                    <form id="form-invoice-whatsapp" method="POST" action="{{ route('speedshop.work-orders.invoice.whatsapp', $service_order) }}" class="contents" @submit.prevent="confirmSendInvoiceWhatsApp">
                        @csrf
                        <button
                            type="submit"
                            title="{{ $waDisabledTitle }}"
                            @disabled(!$wacloudReady || !$hpValidForWa)
                            class="flex h-11 w-full items-center justify-center gap-2 rounded-xl border border-emerald-300 bg-emerald-600 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:border-gray-200 disabled:bg-gray-300 disabled:text-gray-500"
                        >
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                            Kirim Invoice Ke WhatsApp
                        </button>
                    </form>
                </div>
                @endif

                @if($service_order->status === 'antri')
                <div class="mt-5 space-y-3 px-5 pb-5">
                    <form id="form-serahkan-mekanik" action="{{ route('speedshop.work-orders.start', $service_order) }}" method="POST" class="space-y-2.5" @submit.prevent="processWorkOrder()">
                        @csrf
                        <input type="hidden" name="print_sheet" value="1">
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold text-gray-600">Mekanik *</label>
                            <select name="mekanik_id" id="mekanik-select-inline" class="select2-mekanik w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40" @disabled($mekaniks->isEmpty())>
                                <option value="">- Pilih Mekanik -</option>
                                @foreach($mekaniks as $m)
                                @php $isBusy = in_array((int) $m->id, ($busyMekanikIds ?? []), true); @endphp
                                <option value="{{ $m->id }}">
                                    {{ $m->nama }}{{ $m->spesialisasi ? " ({$m->spesialisasi})" : '' }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" @disabled($mekaniks->isEmpty()) class="flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-brand px-5 text-sm font-semibold text-brand-ink shadow-lg shadow-brand/35 transition hover:bg-brand-hover disabled:cursor-not-allowed disabled:bg-gray-300">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Cetak Work Order
                        </button>
                        <p class="text-center text-xs text-gray-500">Pilih mekanik, lalu cetak. Status akan berubah ke Proses.</p>
                    </form>
                </div>
                @endif

            </div>
        </div>
    </div>
</div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<style>
/* Select2 styling: samakan feel dengan TomSelect (rounded-xl, border, padding, focus ring merah) */
.select2-container { width: 100% !important; }
.select2-container--default .select2-selection--single {
  height: 44px;
  border: 1px solid #e5e7eb; /* gray-200 */
  border-radius: 12px;      /* rounded-xl */
  background: #fff;
  display: flex;
  align-items: center;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
  padding-left: 12px;
  padding-right: 36px;
  line-height: 1.25rem;
  color: #111827; /* gray-900 */
  font-size: 14px;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
  height: 44px;
  right: 10px;
}
.select2-container--default .select2-selection--single .select2-selection__clear {
  margin-right: 28px;
}
/* Sembunyikan tombol clear (icon X) */
.select2-container--default .select2-selection--single .select2-selection__clear {
  display: none;
}
.select2-container--default.select2-container--focus .select2-selection--single {
  border-color: #f87171; /* red-400 */
  box-shadow: 0 0 0 4px rgba(248, 113, 113, 0.20); /* ring-red-400/20 */
}
.select2-dropdown {
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  overflow: hidden;
}
.select2-container--default .select2-search--dropdown .select2-search__field {
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  padding: 10px 12px;
  font-size: 14px;
}
.select2-container--default .select2-results__option {
  font-size: 14px;
  padding: 10px 12px;
}
.select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
  background: #fee2e2; /* red-100 */
  color: #111827;
}
</style>
<script>
function workOrderShow() {
    const sheetUrl = @json(route('speedshop.work-orders.sheet.a4', $service_order));
    return {
        sheetUrl,
        init() {
            // Inisialisasi Select2 untuk dropdown mekanik (tanpa modal).
            const el = document.getElementById('mekanik-select-inline');
            if (!el) return;
            if (typeof window.jQuery === 'undefined' || typeof window.jQuery.fn?.select2 === 'undefined') {
                return;
            }
            const $el = window.jQuery(el);
            if ($el.data('select2')) {
                return;
            }
            $el.select2({
                width: '100%',
                placeholder: '- Pilih Mekanik -',
                allowClear: true,
            });
        },
        async confirmSendInvoiceWhatsApp(e) {
            const form = e.target;
            if (!form || form.tagName !== 'FORM') {
                return;
            }
            const result = await Swal.fire({
                title: 'Kirim invoice ke WhatsApp?',
                text: 'Ringkasan struk (teks) dan lampiran PDF invoice akan dikirim ke nomor WhatsApp pelanggan melalui WACLOUD.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#059669',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, kirim',
                cancelButtonText: 'Batal',
            });
            if (result.isConfirmed) {
                form.submit();
            }
        },
        async processWorkOrder() {
            const form = document.getElementById('form-serahkan-mekanik');
            if (!form) return;
            const sel = document.getElementById('mekanik-select-inline');
            const mekanikId = (sel?.value || '').trim();
            if (!mekanikId) {
                await Swal.fire('Perhatian', 'Pilih mekanik terlebih dahulu.', 'warning');
                return;
            }
            @if($service_order->jasaServis->isEmpty())
            await Swal.fire('Perhatian', 'Tambahkan minimal satu jasa servis sebelum cetak Work Order.', 'warning');
            return;
            @endif

            const result = await Swal.fire({
                title: 'Cetak Work Order?',
                text: 'Status akan diubah ke Proses. Lanjutkan?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, cetak',
                cancelButtonText: 'Batal'
            });
            if (!result.isConfirmed) return;

            const printTab = window.open('about:blank', '_blank');
            try {
                const res = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').content,
                        'Accept': 'application/json',
                    },
                    body: new FormData(form),
                });
                if (res.status === 422) {
                    const data = await res.json().catch(() => ({}));
                    const msg = Object.values(data.errors || {}).flat().filter(Boolean)[0] || 'Data tidak valid.';
                    if (printTab && !printTab.closed) printTab.close();
                    Swal.fire('Perhatian', msg, 'warning');
                    return;
                }
                if (!res.ok) {
                    if (printTab && !printTab.closed) printTab.close();
                    Swal.fire('Gagal', 'Terjadi kesalahan saat memproses work order.', 'error');
                    return;
                }
                const data = await res.json().catch(() => ({}));
                const redirect = data.redirect || '';
                const printUrl = data.open_print_url || '';
                if (printUrl) {
                    if (printTab && !printTab.closed) {
                        printTab.location.href = printUrl;
                    } else {
                        window.open(printUrl, '_blank', 'noopener');
                    }
                } else if (printTab && !printTab.closed) {
                    printTab.close();
                }
                if (redirect) {
                    window.location.href = redirect;
                } else {
                    window.location.reload();
                }
            } catch (e) {
                if (printTab && !printTab.closed) printTab.close();
                Swal.fire('Gagal', e?.message || 'Terjadi kesalahan.', 'error');
            }
        },

    };
}
</script>
@endpush
@endsection
