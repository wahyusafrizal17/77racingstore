@extends('layouts.speedshop')

@section('title', 'Pengaturan')

@section('content')
@php
    $invoice = $invoiceSettings ?? [];
    $chargeMode = (string) ($invoice['invoice_charge_mode'] ?? 'percent');
    $ppnMode = (string) ($invoice['invoice_ppn_mode'] ?? 'percent');
    $chargeSummary = $chargeMode === 'rupiah'
        ? 'Rp '.number_format((int) ($invoice['invoice_charge_rupiah'] ?? 0), 0, ',', '.')
        : number_format((float) ($invoice['invoice_charge_percent'] ?? 0), 2, ',', '.').'%';
    $ppnSummary = $ppnMode === 'rupiah'
        ? 'Rp '.number_format((int) ($invoice['invoice_ppn_rupiah'] ?? 0), 0, ',', '.')
        : number_format((float) ($invoice['invoice_ppn_percent'] ?? 0), 2, ',', '.').'%';
    $auditPercent = (float) ($invoice['stok_audit_percent'] ?? \App\Services\InvoiceSettingsService::STOK_AUDIT_DEFAULT_PERCENT);
@endphp

@push('styles')
@include('partials.speedshop.dashboard-load-animations')
@include('partials.speedshop.dashboard-manager-template-styles')
@include('partials.speedshop.work-order-create-styles')
<style>
.ss-dashboard .settings-summary-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 20px;
}
@media (max-width: 1100px) {
  .ss-dashboard .settings-summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 640px) {
  .ss-dashboard .settings-summary-grid { grid-template-columns: 1fr; }
}
.ss-dashboard .pay-icon.red { background: rgba(239,68,68,0.10); color: #DC2626; }
.ss-dashboard .pay-icon.amber { background: rgba(245,158,11,0.10); color: #D97706; }
.ss-dashboard .pay-icon.teal { background: rgba(20,184,166,0.10); color: #14B8A6; }
.ss-dashboard .pay-icon.indigo { background: rgba(99,102,241,0.10); color: #4F46E5; }
</style>
@endpush

<div
    class="ss-dashboard ss-dash-page ss-wo-create"
    x-init="$nextTick(() => { requestAnimationFrame(() => $el.classList.add('is-ready')); })"
>
<div class="ss-dash-content space-y-5">
    <div class="flex flex-col gap-3 ss-dash-enter sm:flex-row sm:items-start sm:justify-between" style="--ss-delay: 0ms">
        <div>
            <h1 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">Pengaturan</h1>
            <p class="mt-1 text-[13px] text-gray-500">Pengaturan global cabang speedshop · khusus Manager</p>
        </div>
    </div>


    <div class="settings-summary-grid ss-dash-enter" style="--ss-delay: 80ms">
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 0">
            <div class="pay-card-top">
                <span class="pay-card-label">Charge Invoice</span>
                <span class="pay-icon red">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="pay-amount text-[22px] sm:text-[26px]">{{ $chargeSummary }}</div>
            <p class="pay-period">Mode {{ $chargeMode === 'rupiah' ? 'rupiah' : 'persen' }}</p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 1">
            <div class="pay-card-top">
                <span class="pay-card-label">PPN Invoice</span>
                <span class="pay-icon amber">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                </span>
            </div>
            <div class="pay-amount text-[22px] sm:text-[26px]">{{ $ppnSummary }}</div>
            <p class="pay-period">Mode {{ $ppnMode === 'rupiah' ? 'rupiah' : 'persen' }}</p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 2">
            <div class="pay-card-top">
                <span class="pay-card-label">Audit Stok</span>
                <span class="pay-icon indigo">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                </span>
            </div>
            <div class="pay-amount text-[22px] sm:text-[26px]">{{ number_format($auditPercent, 2, ',', '.') }}%</div>
            <p class="pay-period">Min. {{ \App\Services\InvoiceSettingsService::STOK_AUDIT_MIN_PRODUCTS }} produk acak</p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 3">
            <div class="pay-card-top">
                <span class="pay-card-label">Cakupan</span>
                <span class="pay-icon teal">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </span>
            </div>
            <div class="pay-amount text-[18px] sm:text-[22px]">Semua Speedshop</div>
            <p class="pay-period">Berlaku global cabang</p>
        </div>
    </div>

    @include('speedshop.settings.partials.invoice')
    @include('speedshop.settings.partials.stok-audit')
</div>
</div>
@endsection
