@php
    $invoice = $invoiceSettings ?? [];
    $auditPercent = (float) ($invoice['stok_audit_percent'] ?? \App\Services\InvoiceSettingsService::STOK_AUDIT_DEFAULT_PERCENT);
@endphp
<div id="pengaturan-audit-stok" class="panel-card panel-card-flush ss-dash-enter scroll-mt-6" style="--ss-delay: 140ms">
    <div class="border-b border-gray-100 px-5 py-4">
        <h2 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">Pengaturan Audit Stok</h2>
        <p class="mt-1 text-[13px] text-gray-500">Atur persentase produk acak yang diaudit di halaman Produk &amp; Stok</p>
    </div>

    <form
        method="POST"
        action="{{ route('speedshop.settings.stok-audit.update') }}"
        class="space-y-5 px-5 py-5"
    >
        @csrf
        @method('PUT')

        <div class="max-w-md space-y-4">
            <div>
                <label class="wo-label">Persentase Produk Diaudit (%)</label>
                <input
                    type="number"
                    step="0.01"
                    min="0.01"
                    max="100"
                    name="stok_audit_percent"
                    value="{{ old('stok_audit_percent', (string) $auditPercent) }}"
                    class="wo-input wo-native"
                    placeholder="15"
                />
                @error('stok_audit_percent')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1.5 text-xs text-gray-500">
                    Produk audit diacak sesuai persentase ini. Minimal {{ \App\Services\InvoiceSettingsService::STOK_AUDIT_MIN_PRODUCTS }} produk.
                </p>
            </div>
        </div>

        <div class="flex flex-col gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:items-center sm:justify-end">
            <button type="submit" class="btn-primary inline-flex h-10 items-center justify-center px-6">Simpan Audit Stok</button>
        </div>
    </form>
</div>
