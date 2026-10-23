@extends('layouts.speedshop')

@section('title', 'Input Pembelian Luar Baru')

@section('content')
@push('styles')
@include('partials.speedshop.dashboard-load-animations')
@include('partials.speedshop.dashboard-manager-template-styles')
@include('partials.speedshop.work-order-create-styles')
<style>
.ts-dropdown.sis-prod-dropdown,
.ts-dropdown.sis-sup-dropdown {
    min-width: min(100%, 28rem);
    overflow: hidden;
}
.ts-dropdown.sis-prod-dropdown .ts-dropdown-content,
.ts-dropdown.sis-sup-dropdown .ts-dropdown-content {
    max-height: min(360px, calc(100dvh - 96px));
    padding: 4px 0;
}
.ts-dropdown.sis-prod-dropdown .option,
.ts-dropdown.sis-sup-dropdown .option {
    padding: 10px 12px;
    border-bottom: 1px solid #f3f4f6;
    color: inherit;
}
.ts-dropdown.sis-prod-dropdown .option:last-child,
.ts-dropdown.sis-sup-dropdown .option:last-child { border-bottom: none; }
.ts-dropdown.sis-prod-dropdown .option.active,
.ts-dropdown.sis-prod-dropdown .option:hover,
.ts-dropdown.sis-sup-dropdown .option.active,
.ts-dropdown.sis-sup-dropdown .option:hover {
    background: #fef2f2;
    color: inherit;
}
.ts-dropdown.sis-prod-dropdown .option.selected,
.ts-dropdown.sis-sup-dropdown .option.selected {
    background: #fff;
    color: inherit;
}
.ts-dropdown.sis-prod-dropdown .option.selected.active,
.ts-dropdown.sis-sup-dropdown .option.selected.active {
    background: #fef2f2;
}

.sis-prod {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
}
.sis-prod__main { min-width: 0; flex: 1; }
.sis-prod__sku {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.01em;
    color: #6b7280;
    line-height: 1.3;
    word-break: break-all;
}
.sis-prod__name {
    margin-top: 3px;
    font-size: 13px;
    font-weight: 600;
    line-height: 1.35;
    color: #111827;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.sis-prod__meta {
    margin-top: 3px;
    font-size: 11px;
    color: #9ca3af;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.ts-dropdown.sis-prod-dropdown .option.active .sis-prod__sku { color: #b91c1c; }
.ts-dropdown.sis-prod-dropdown .option.active .sis-prod__name { color: #991b1b; }
.sis-prod__qty {
    flex-shrink: 0;
    min-width: 52px;
    padding: 5px 8px;
    border-radius: 8px;
    background: #f3f4f6;
    text-align: center;
    line-height: 1.15;
}
.sis-prod__qty-lbl {
    display: block;
    font-size: 9px;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #9ca3af;
}
.sis-prod__qty-val {
    display: block;
    margin-top: 1px;
    font-size: 13px;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    color: #111827;
}
.sis-prod__qty--empty { background: #fef2f2; }
.sis-prod__qty--empty .sis-prod__qty-lbl { color: #f87171; }
.sis-prod__qty--empty .sis-prod__qty-val { color: #dc2626; }

.sis-prod-sel {
    display: flex;
    align-items: baseline;
    gap: 8px;
    min-width: 0;
    overflow: hidden;
}
.sis-prod-sel__sku {
    flex-shrink: 0;
    max-width: 42%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 11px;
    font-weight: 600;
    color: #6b7280;
}
.sis-prod-sel__name {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 13px;
    font-weight: 600;
    color: #111827;
}

.sis-sup {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
}
.sis-sup__main { min-width: 0; flex: 1; }
.sis-sup__kode {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.01em;
    color: #6b7280;
    line-height: 1.3;
}
.sis-sup__name {
    margin-top: 3px;
    font-size: 13px;
    font-weight: 600;
    line-height: 1.35;
    color: #111827;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.ts-dropdown.sis-sup-dropdown .option.active .sis-sup__kode { color: #b91c1c; }
.ts-dropdown.sis-sup-dropdown .option.active .sis-sup__name { color: #991b1b; }
.sis-sup__phone {
    flex-shrink: 0;
    max-width: 42%;
    padding: 5px 8px;
    border-radius: 8px;
    background: #f3f4f6;
    text-align: right;
    line-height: 1.15;
}
.sis-sup__phone-lbl {
    display: block;
    font-size: 9px;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #9ca3af;
}
.sis-sup__phone-val {
    display: block;
    margin-top: 1px;
    font-size: 12px;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
    color: #111827;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.sis-sup-sel {
    display: flex;
    align-items: baseline;
    gap: 8px;
    min-width: 0;
    overflow: hidden;
}
.sis-sup-sel__kode {
    flex-shrink: 0;
    max-width: 38%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 11px;
    font-weight: 600;
    color: #6b7280;
}
.sis-sup-sel__name {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 13px;
    font-weight: 600;
    color: #111827;
}

.ss-wo-create .sis-item-card {
    border: 1px solid var(--ss-border, #e5e7eb);
    border-radius: 12px;
    background: #fcfafb;
    padding: 16px;
}
.ss-wo-create .wo-input[readonly] {
    background: var(--ss-bg, #F4F5F7);
    color: #4b5563;
    cursor: default;
}

.ss-wo-create .wo-file-input {
    width: 100%;
    border: 1px solid var(--ss-border, #e5e7eb);
    border-radius: 9px;
    background: #fff;
    padding: 8px 12px;
    font-size: 13px;
}
.ss-wo-create .wo-file-input::file-selector-button {
    margin-right: 12px;
    border: 0;
    border-radius: 8px;
    background: #fef2f2;
    padding: 6px 12px;
    font-size: 11px;
    font-weight: 600;
    color: #dc2626;
    cursor: pointer;
}
</style>
@endpush

@php
    $woPanelClass = 'panel-card panel-card-flush overflow-visible ss-dash-enter-child';
@endphp

<div
    x-data="transaksiCreate()"
    x-init="$nextTick(() => { requestAnimationFrame(() => $el.classList.add('is-ready')); })"
    class="ss-dashboard ss-dash-page ss-wo-create"
>
<div class="ss-dash-content space-y-5">
    <div class="flex flex-col gap-3 ss-dash-enter sm:flex-row sm:items-start sm:justify-between" style="--ss-delay: 0ms">
        <div>
            <h1 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">Input Pembelian Luar</h1>
            <p class="mt-1 text-[13px] text-gray-500">Stok masuk dari supplier luar — lampiran invoice wajib diunggah</p>
        </div>
        <a href="{{ route('speedshop.stock-in.pembelian-luar.index') }}" class="btn-secondary shrink-0">← Kembali</a>
    </div>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-12 ss-dash-enter" style="--ss-delay: 80ms">
        <div class="space-y-5 lg:col-span-8">
            <div class="{{ $woPanelClass }}" style="--ss-delay: 120ms; --ss-stagger: 0">
                <div class="border-b border-gray-100 px-5 py-4">
                    <div class="section-hd" style="margin-bottom:0">
                        <h2>No. Invoice &amp; Supplier</h2>
                        <p>Nomor invoice manual (unik). Supplier dari master data outlet.</p>
                    </div>
                </div>
                <div class="panel-card-body space-y-4">
                    @if(($suppliers ?? collect())->isEmpty())
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                        Belum ada supplier di master data untuk outlet ini. Tambahkan supplier di master data Speedshop terlebih dahulu.
                    </div>
                    @endif
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="min-w-0">
                            <label class="wo-label">No. Invoice <span class="req">*</span></label>
                            <input type="text" x-model="formKode" maxlength="100" class="wo-input font-semibold" placeholder="Contoh: INV-20260305-001">
                        </div>
                        <div class="min-w-0">
                            <label class="wo-label">Supplier <span class="req">*</span></label>
                            <select id="supplier-select"></select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="{{ $woPanelClass }}" style="--ss-delay: 120ms; --ss-stagger: 1">
                <div class="flex flex-col gap-3 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="section-hd" style="margin-bottom:0">
                        <h2>Item Pembelian</h2>
                        <p>Pilih produk dari katalog Sparepart Luar</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <span class="wo-badge" x-text="formItems.length + ' item'"></span>
                        <button type="button" @click="addRow()" class="btn-primary inline-flex h-10 items-center gap-2 px-4">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                            Tambah Item
                        </button>
                    </div>
                </div>

                <div class="panel-card-body space-y-3">
                    <template x-for="(row, idx) in formItems" :key="row._id">
                        <div class="sis-item-card">
                            <div class="flex items-start justify-between gap-3">
                                <div class="text-sm font-semibold text-gray-900">Item #<span x-text="idx + 1"></span></div>
                                <button type="button" @click="removeRow(idx)"
                                        class="wo-icon-btn"
                                        :disabled="formItems.length <= 1"
                                        :class="formItems.length <= 1 && 'opacity-40 pointer-events-none'"
                                        title="Hapus item">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>

                            <div class="mt-3 space-y-3">
                                <div>
                                    <label class="wo-label">Produk <span class="req">*</span></label>
                                    <select :id="'product-select-' + row._id" class="product-select-tom w-full"></select>
                                </div>
                            </div>

                            <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="wo-label">HPP</label>
                                    <div class="relative">
                                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs font-medium text-gray-400">Rp</span>
                                        <input type="text" readonly tabindex="-1" placeholder="0"
                                               class="wo-input pl-10 text-right tabular-nums"
                                               :value="formatRupiahDigits(row.hpp)">
                                    </div>
                                    <p x-show="Number(row.harga_invoice) > Number(row.hpp_awal || 0)" x-cloak
                                       class="mt-1 text-[11px] text-amber-700">HPP naik mengikuti harga invoice</p>
                                </div>
                                <div>
                                    <label class="wo-label">Harga Jual</label>
                                    <div class="relative">
                                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs font-medium text-gray-400">Rp</span>
                                        <input type="text" readonly tabindex="-1" placeholder="0"
                                               class="wo-input pl-10 text-right tabular-nums"
                                               :value="formatRupiahDigits(row.harga_jual)">
                                    </div>
                                </div>
                                <div>
                                    <label class="wo-label">Harga Invoice <span class="req">*</span></label>
                                    <div class="relative">
                                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs font-medium text-gray-400">Rp</span>
                                        <input type="text" inputmode="numeric" autocomplete="off" placeholder="0"
                                               class="wo-input pl-10 text-right tabular-nums"
                                               :value="formatRupiahDigits(row.harga_invoice)"
                                               @input="onRupiahRowInput($event, row, 'harga_invoice')">
                                    </div>
                                    <p class="mt-1 text-[11px] text-gray-400">Harga beli sesuai invoice. Jika lebih tinggi dari HPP, HPP master naik.</p>
                                </div>
                                <div>
                                    <label class="wo-label">Qty <span class="req">*</span></label>
                                    <input type="number" x-model.number="row.qty" min="1" step="1" class="wo-input text-center font-semibold">
                                </div>
                            </div>
                        </div>
                    </template>

                    <template x-if="formItems.length === 0">
                        <div class="rounded-xl border border-dashed border-gray-200 bg-gray-50 px-5 py-12 text-center text-gray-400">
                            <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            <p class="mt-3 text-sm font-medium">Belum ada item</p>
                            <p class="mt-1 text-xs">Klik Tambah Item untuk mulai mengisi daftar pembelian</p>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <div class="space-y-4 lg:col-span-4">
            <div class="panel-card panel-card-flush summary-card ss-dash-enter-child" style="--ss-delay: 120ms; --ss-stagger: 2">
                <div class="section-hd">
                    <h2>Ringkasan</h2>
                    <p>Total item, lampiran invoice, dan konfirmasi</p>
                </div>

                <div class="mt-2 space-y-4 px-5">
                    <div class="mb-2">
                        <div class="summary-row">
                            <span>Baris Terisi</span>
                            <span x-text="filledRowCount()"></span>
                        </div>
                        <div class="summary-row">
                            <span>Total Qty</span>
                            <span x-text="totalQty()"></span>
                        </div>
                        <div class="summary-total">
                            <span>Total Harga Invoice</span>
                            <span x-text="formatRupiah(totalHarga())"></span>
                        </div>
                    </div>

                    <div>
                        <label class="wo-label">Lampiran Invoice <span class="req">*</span></label>
                        <p class="panel-card-sub mb-2">Satu file untuk seluruh transaksi · maks 5MB · PDF/JPG/PNG/WEBP</p>
                        <input type="file" x-ref="invoiceFile" accept=".pdf,.jpg,.jpeg,.png,.webp"
                               @change="invoiceHasFile = !!($event.target.files && $event.target.files.length)"
                               class="wo-file-input">
                    </div>

                    <div>
                        <label class="wo-label">Catatan <span class="text-gray-400 normal-case tracking-normal">(opsional)</span></label>
                        <textarea x-model="formCatatan" rows="3" class="wo-textarea" placeholder="Catatan tambahan..."></textarea>
                    </div>
                </div>

                <div class="space-y-3 border-t border-gray-100 px-5 pb-5 pt-5">
                    <button type="button" @click="submitTransaksi()" :disabled="loading || !canSubmit()"
                            class="btn-primary flex h-11 w-full disabled:cursor-not-allowed disabled:opacity-50">
                        <svg x-show="!loading" class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <svg x-show="loading" class="h-4 w-4 shrink-0 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        <span x-text="loading ? 'Mengirim...' : (totalHarga() > 200000 ? 'Submit untuk Verifikasi' : 'Simpan & Tambah Stok')"></span>
                    </button>
                </div>
            </div>

            <div class="panel-card panel-card-flush ss-dash-enter-child rounded-xl border-amber-200/80 bg-amber-50/80 p-4" style="--ss-delay: 120ms; --ss-stagger: 3">
                <div class="flex gap-3">
                    <svg class="h-5 w-5 shrink-0 text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <div>
                        <p class="text-sm font-semibold text-amber-800">Perlu Verifikasi Manager</p>
                        <p class="mt-1 text-xs leading-relaxed text-amber-700">
                            Jika total <strong>harga invoice × qty</strong> lebih dari <strong>Rp 200.000</strong>, transaksi menunggu persetujuan Manager Speedshop sebelum stok bertambah.
                            Jika <strong>Rp 200.000 atau kurang</strong>, stok langsung bertambah tanpa verifikasi.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script>
function transaksiCreate() {
    const baseUrl = '{{ url("speedshop/stock-in/pembelian-luar") }}';
    const suppliersList = @json($suppliersForJs ?? []);
    const suppliersById = Object.fromEntries(suppliersList.map((s) => [String(s.id), s]));
    let rowId = 0;
    return {
        loading: false,
        formKode: '',
        supplierId: '',
        formItems: [],
        formCatatan: '',
        invoiceHasFile: false,
        productsList: @json($productsForJs ?? []),
        rowSelects: {},
        refreshingRowSelects: false,

        init() {
            this.addRow();
            this.$nextTick(() => this.initSupplierSelect());
        },

        selectedSupplier() {
            if (!this.supplierId) return null;
            return suppliersById[String(this.supplierId)] || null;
        },

        supplierOptionHtml(data, escape) {
            const kode = (data.kode_supplier || '').trim();
            const phone = (data.no_hp || '').trim();
            const kodeRow = kode ? `<div class="sis-sup__kode">${escape(kode)}</div>` : '';
            const phoneCol = phone
                ? `<div class="sis-sup__phone"><span class="sis-sup__phone-lbl">No. HP</span><span class="sis-sup__phone-val">${escape(phone)}</span></div>`
                : '';
            return `<div class="sis-sup"><div class="sis-sup__main">${kodeRow}<div class="sis-sup__name">${escape(data.nama || '')}</div></div>${phoneCol}</div>`;
        },

        supplierItemHtml(data, escape) {
            const kode = (data.kode_supplier || '').trim();
            const kodeSpan = kode ? `<span class="sis-sup-sel__kode">${escape(kode)}</span>` : '';
            return `<div class="sis-sup-sel">${kodeSpan}<span class="sis-sup-sel__name">${escape(data.nama || '')}</span></div>`;
        },

        initSupplierSelect() {
            const el = document.getElementById('supplier-select');
            if (!el || typeof TomSelect === 'undefined') return;
            const self = this;
            const ts = new TomSelect(el, {
                valueField: 'id',
                labelField: 'nama',
                searchField: ['nama', 'kode_supplier', 'no_hp'],
                placeholder: 'Cari / pilih supplier...',
                allowEmptyOption: false,
                dropdownClass: 'ts-dropdown sis-sup-dropdown',
                options: suppliersList,
                render: {
                    option: (data, escape) => self.supplierOptionHtml(data, escape),
                    item: (data, escape) => self.supplierItemHtml(data, escape),
                    no_results: () => '<div class="no-results">Supplier tidak ditemukan</div>',
                },
                onChange(val) {
                    self.supplierId = String(val || '');
                    self.applySupplierKodeToItems();
                    self.refreshAllRowSelectOptions();
                },
            });
            if (this.supplierId) {
                ts.setValue(String(this.supplierId), true);
            }
        },

        selectedSupplierKode() {
            if (!this.supplierId) return '';
            const s = suppliersById[String(this.supplierId)];
            return (s?.kode_supplier || '').trim();
        },

        resolveKodeSupplier(productKode) {
            const fromInvoice = this.selectedSupplierKode();
            if (fromInvoice) return fromInvoice;
            return (productKode || '').trim();
        },

        applySupplierKodeToItems() {
            const kodeSup = this.selectedSupplierKode();
            this.formItems.forEach((row) => {
                if (row.product_id) row.kode_supplier = kodeSup;
            });
        },

        productLineLabel(row) {
            const kode = (row?.kode_produk || '').trim() || '—';
            const kodeSup = this.resolveKodeSupplier(row?.kode_supplier).trim() || '—';
            const nama = (row?.nama_produk || '').trim() || '—';
            return `${kode} - ${kodeSup} - ${nama}`;
        },

        liveRow(rowOrId) {
            const id = (rowOrId && typeof rowOrId === 'object') ? rowOrId._id : rowOrId;
            return this.formItems.find((r) => r._id === id) || null;
        },

        numPrice(...vals) {
            for (const v of vals) {
                const n = Number(v);
                if (Number.isFinite(n) && n > 0) return n;
            }
            return 0;
        },

        _productTomRow(p) {
            return {
                id: String(p.id),
                kode_produk: p.kode_produk,
                kode_supplier: this.resolveKodeSupplier(p.kode_supplier),
                nama_produk: p.nama_produk,
                category_id: p.category_id,
                category_nama: p.category_nama,
                brand_id: p.brand_id,
                brand_nama: p.brand_nama,
                hpp: p.hpp,
                harga_pembelian: p.harga_pembelian,
                harga_jual: p.harga_jual,
                harga_jual_speedshop: p.harga_jual_speedshop,
                jumlah: Number(p.jumlah) || 0,
            };
        },

        productTomOptions() {
            return this.productsList.map(p => {
                const row = this._productTomRow(p);
                return { ...row, label: this.productLineLabel(row) };
            });
        },

        productOptionHtml(data, escape) {
            const stok = Number(data.jumlah || 0);
            const brand = (data.brand_nama || '').trim();
            const empty = stok === 0 ? ' sis-prod__qty--empty' : '';
            const meta = brand ? `<div class="sis-prod__meta">${escape(brand)}</div>` : '';
            return `<div class="sis-prod"><div class="sis-prod__main"><div class="sis-prod__sku">${escape(data.kode_produk || '')}</div><div class="sis-prod__name">${escape(data.nama_produk || '')}</div>${meta}</div><div class="sis-prod__qty${empty}"><span class="sis-prod__qty-lbl">Stok</span><span class="sis-prod__qty-val">${stok.toLocaleString('id-ID')}</span></div></div>`;
        },

        productItemHtml(data, escape) {
            return `<div class="sis-prod-sel"><span class="sis-prod-sel__sku">${escape(data.kode_produk || '')}</span><span class="sis-prod-sel__name">${escape(data.nama_produk || '')}</span></div>`;
        },

        initRowSelect(row) {
            if (typeof TomSelect === 'undefined') return;
            const rowKey = row._id;
            const el = document.getElementById('product-select-' + rowKey);
            if (!el) return;
            this.destroyRowSelect(rowKey);
            const self = this;
            const ts = new TomSelect(el, {
                valueField: 'id',
                labelField: 'label',
                searchField: ['kode_produk', 'kode_supplier', 'nama_produk', 'brand_nama'],
                placeholder: 'Cari / pilih produk...',
                maxOptions: 10000,
                dropdownClass: 'ts-dropdown sis-prod-dropdown',
                options: self.productTomOptions(),
                render: {
                    option: (data, escape) => self.productOptionHtml(data, escape),
                    item: (data, escape) => self.productItemHtml(data, escape),
                },
                onChange(val) {
                    if (self.refreshingRowSelects) return;
                    const live = self.liveRow(rowKey);
                    if (live) self.onProductPicked(live, val, val ? ts.options[val] : null);
                },
            });
            const live = this.liveRow(rowKey);
            if (live?.product_id) {
                ts.setValue(String(live.product_id), true);
            }
            this.rowSelects[rowKey] = ts;
        },

        destroyRowSelect(rowKey) {
            const ts = this.rowSelects[rowKey];
            if (ts) {
                ts.destroy();
                delete this.rowSelects[rowKey];
            }
        },

        refreshAllRowSelectOptions() {
            this.refreshingRowSelects = true;
            try {
                const opts = this.productTomOptions();
                Object.values(this.rowSelects).forEach(ts => {
                    const current = ts.getValue();
                    ts.clearOptions();
                    ts.addOptions(opts);
                    if (current) ts.setValue(current, true);
                });
            } finally {
                this.refreshingRowSelects = false;
            }
        },

        onProductPicked(row, val, opt) {
            const live = this.liveRow(row) || row;
            if (!val) {
                live.product_id = '';
                live.kode_produk = '';
                live.kode_supplier = '';
                live.nama_produk = '';
                live.category_id = '';
                live.category_nama = '';
                live.brand_id = '';
                live.brand_nama = '';
                live.hpp = 0;
                live.hpp_awal = 0;
                live.harga_jual = 0;
                live.harga_invoice = 0;
                return;
            }
            const p = this.productsList.find(x => String(x.id) === String(val)) || opt;
            if (!p) return;
            live.product_id = p.id;
            live.kode_produk = p.kode_produk;
            live.kode_supplier = this.resolveKodeSupplier(p.kode_supplier);
            live.nama_produk = p.nama_produk;
            live.category_id = p.category_id;
            live.category_nama = p.category_nama;
            live.brand_id = p.brand_id || '';
            live.brand_nama = p.brand_nama || '';
            live.hpp_awal = this.numPrice(p.hpp, p.harga_pembelian);
            live.hpp = live.hpp_awal;
            live.harga_jual = this.numPrice(p.harga_jual_speedshop, p.harga_jual);
            live.harga_invoice = 0;
        },

        addRow() {
            const row = {
                _id: ++rowId,
                product_id: '',
                kode_produk: '',
                kode_supplier: '',
                nama_produk: '',
                category_id: '',
                category_nama: '',
                brand_id: '',
                brand_nama: '',
                hpp: 0,
                hpp_awal: 0,
                harga_jual: 0,
                harga_invoice: 0,
                qty: 1,
            };
            this.formItems.push(row);
            this.$nextTick(() => this.initRowSelect(this.liveRow(row._id) || row));
        },

        removeRow(idx) {
            if (this.formItems.length <= 1) return;
            const row = this.formItems[idx];
            this.destroyRowSelect(row._id);
            this.formItems.splice(idx, 1);
        },

        parseRupiahDigits(str) {
            const d = String(str ?? '').replace(/\D/g, '');
            if (d === '') return 0;
            const n = parseInt(d, 10);
            return Number.isNaN(n) ? 0 : Math.min(n, 999999999999);
        },

        onRupiahRowInput(e, row, field) {
            const live = this.liveRow(row) || row;
            live[field] = this.parseRupiahDigits(e.target.value);
            if (field === 'harga_invoice') {
                this.syncHppFromInvoice(live);
            }
        },

        syncHppFromInvoice(row) {
            const base = Number(row.hpp_awal || 0);
            const invoice = Number(row.harga_invoice) || 0;
            row.hpp = invoice > base ? invoice : base;
        },

        formatRupiahDigits(n) {
            const v = Number(n);
            if (!Number.isFinite(v)) return '';
            return Math.floor(v).toLocaleString('id-ID');
        },

        formatRupiah(n) {
            return 'Rp ' + Number(n || 0).toLocaleString('id-ID');
        },

        filledRows() {
            return this.formItems.filter((r) => {
                const pid = parseInt(String(r.product_id || '').trim(), 10);
                const invoice = Number(r.harga_invoice);
                const q = parseInt(r.qty, 10);
                return pid > 0
                    && !Number.isNaN(invoice) && invoice >= 0.01
                    && !Number.isNaN(q) && q >= 1;
            });
        },

        filledRowCount() {
            return this.filledRows().length;
        },

        duplicateProductIdsInFilled() {
            const ids = this.filledRows().map((r) => parseInt(String(r.product_id), 10));
            return new Set(ids).size !== ids.length;
        },

        totalQty() {
            return this.filledRows().reduce((acc, r) => acc + (parseInt(r.qty, 10) || 0), 0);
        },

        totalHarga() {
            return this.filledRows().reduce((acc, r) => acc + (Number(r.harga_invoice || 0) * (parseInt(r.qty, 10) || 0)), 0);
        },

        canSubmit() {
            if (!this.formKode.trim() || !String(this.supplierId || '').trim()) return false;
            if (!this.invoiceHasFile) return false;
            @if(($suppliers ?? collect())->isEmpty())
                return false;
            @endif
            if (this.duplicateProductIdsInFilled()) return false;
            return this.filledRowCount() >= 1;
        },

        buildPayloadItems() {
            return this.filledRows().map((r) => ({
                product_id: parseInt(String(r.product_id), 10),
                harga_invoice: Number(r.harga_invoice) || 0,
                qty: Math.max(1, parseInt(r.qty, 10) || 1),
            }));
        },

        async submitTransaksi() {
            if (!this.formKode.trim()) {
                Swal.fire('Perhatian', 'No. Invoice wajib diisi.', 'warning');
                return;
            }
            if (!String(this.supplierId || '').trim()) {
                Swal.fire('Perhatian', 'Supplier wajib dipilih.', 'warning');
                return;
            }
            if (!this.invoiceHasFile || !this.$refs.invoiceFile?.files?.[0]) {
                Swal.fire('Perhatian', 'Lampiran invoice wajib diunggah.', 'warning');
                return;
            }
            if (this.filledRowCount() < 1) {
                Swal.fire('Perhatian', 'Minimal satu baris item lengkap: pilih produk, isi harga invoice (minimal Rp 0,01), dan qty.', 'warning');
                return;
            }
            if (this.duplicateProductIdsInFilled()) {
                Swal.fire('Perhatian', 'Produk tidak boleh dipilih dua kali dalam satu transaksi.', 'warning');
                return;
            }

            const over = this.totalHarga() > 200000;
            const result = await Swal.fire({
                title: over ? 'Ajukan untuk verifikasi?' : 'Simpan pembelian?',
                html: over
                    ? 'Total harga invoice × qty melebihi <strong>Rp 200.000</strong>. Stok akan naik setelah Manager menyetujui.'
                    : 'Stok Speedshop akan langsung bertambah sesuai qty.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: over ? 'Ya, ajukan' : 'Ya, simpan',
                cancelButtonText: 'Batal'
            });
            if (!result.isConfirmed) return;

            this.loading = true;

            const fd = new FormData();
            fd.append('kode', this.formKode.trim());
            fd.append('supplier_id', String(this.supplierId).trim());
            fd.append('catatan', this.formCatatan || '');
            fd.append('invoice', this.$refs.invoiceFile.files[0]);
            fd.append('items', JSON.stringify(this.buildPayloadItems()));

            const res = await fetch(baseUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: fd,
            });

            const d = await res.json().catch(() => ({}));
            if (!res.ok) {
                this.loading = false;
                let msg = d.message || 'Gagal menyimpan.';
                if (d.errors && typeof d.errors === 'object') {
                    const first = Object.values(d.errors).flat().find(Boolean);
                    if (first) msg = first;
                }
                Swal.fire('Error', msg, 'error');
                return;
            }

            this.loading = false;
            window.location.href = '{{ route("speedshop.stock-in.pembelian-luar.index") }}';
        }
    }
}
</script>
@endpush
