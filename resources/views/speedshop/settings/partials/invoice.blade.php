@php
    $invoice = $invoiceSettings ?? [];
@endphp
<div id="pengaturan-invoice" class="panel-card panel-card-flush ss-dash-enter scroll-mt-6" style="--ss-delay: 120ms">
    <div class="border-b border-gray-100 px-5 py-4">
        <h2 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">Pengaturan Invoice</h2>
        <p class="mt-1 text-[13px] text-gray-500">Atur charge dan PPN yang dipakai di invoice thermal &amp; WhatsApp</p>
    </div>

    <form
        method="POST"
        action="{{ route('speedshop.settings.update') }}"
        class="space-y-5 px-5 py-5"
        x-data="{ chargeMode: @js(old('invoice_charge_mode', (string) ($invoice['invoice_charge_mode'] ?? 'percent'))), ppnMode: @js(old('invoice_ppn_mode', (string) ($invoice['invoice_ppn_mode'] ?? 'percent'))) }"
    >
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div class="space-y-4">
                <div>
                    <label class="wo-label">Charge Mode</label>
                    <select name="invoice_charge_mode" x-model="chargeMode" class="wo-input wo-native">
                        <option value="percent">Persen (%)</option>
                        <option value="rupiah">Rupiah (Rp)</option>
                    </select>
                    @error('invoice_charge_mode')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div x-show="chargeMode === 'percent'" x-cloak>
                    <label class="wo-label">Charge (%)</label>
                    <input type="number" step="0.01" min="0" max="100" name="invoice_charge_percent"
                           value="{{ old('invoice_charge_percent', (string) ($invoice['invoice_charge_percent'] ?? 0)) }}"
                           class="wo-input wo-native"
                           placeholder="0" />
                    @error('invoice_charge_percent')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1.5 text-xs text-gray-500">Mode persen: charge minimal Rp {{ number_format(\App\Services\InvoiceSettingsService::CHARGE_PERCENT_MINIMUM, 0, ',', '.') }} per invoice. Jika hasil % lebih besar, dipakai nilai hasil %.</p>
                </div>

                <div x-show="chargeMode === 'rupiah'" x-cloak>
                    <label class="wo-label">Charge (Rp)</label>
                    <input type="number" step="1" min="0" name="invoice_charge_rupiah"
                           value="{{ old('invoice_charge_rupiah', (string) ((int) ($invoice['invoice_charge_rupiah'] ?? 0))) }}"
                           class="wo-input wo-native"
                           placeholder="0" />
                    @error('invoice_charge_rupiah')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="wo-label">PPN Mode</label>
                    <select name="invoice_ppn_mode" x-model="ppnMode" class="wo-input wo-native">
                        <option value="percent">Persen (%)</option>
                        <option value="rupiah">Rupiah (Rp)</option>
                    </select>
                    @error('invoice_ppn_mode')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div x-show="ppnMode === 'percent'" x-cloak>
                    <label class="wo-label">PPN (%)</label>
                    <input type="number" step="0.01" min="0" max="100" name="invoice_ppn_percent"
                           value="{{ old('invoice_ppn_percent', (string) ($invoice['invoice_ppn_percent'] ?? 0)) }}"
                           class="wo-input wo-native"
                           placeholder="0" />
                    @error('invoice_ppn_percent')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div x-show="ppnMode === 'rupiah'" x-cloak>
                    <label class="wo-label">PPN (Rp)</label>
                    <input type="number" step="1" min="0" name="invoice_ppn_rupiah"
                           value="{{ old('invoice_ppn_rupiah', (string) ((int) ($invoice['invoice_ppn_rupiah'] ?? 0))) }}"
                           class="wo-input wo-native"
                           placeholder="0" />
                    @error('invoice_ppn_rupiah')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:items-center sm:justify-end">
            <a href="{{ route('speedshop.dashboard') }}" class="btn-secondary inline-flex h-10 items-center justify-center px-6">Kembali</a>
            <button type="submit" class="btn-primary inline-flex h-10 items-center justify-center px-6">Simpan</button>
        </div>
    </form>
</div>
