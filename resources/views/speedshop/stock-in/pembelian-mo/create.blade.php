@extends('layouts.speedshop')

@section('title', 'Buat Purchase Order')

@section('content')
@push('styles')
@include('partials.speedshop.dashboard-load-animations')
@include('partials.speedshop.dashboard-manager-template-styles')
@include('partials.speedshop.work-order-create-styles')
<style>
.ts-dropdown.sis-prod-dropdown {
    min-width: min(100%, 28rem);
    overflow: hidden;
}
.ts-dropdown.sis-prod-dropdown .ts-dropdown-content {
    max-height: min(360px, calc(100dvh - 96px));
    padding: 4px 0;
}
.ts-dropdown.sis-prod-dropdown .option {
    padding: 10px 12px;
    border-bottom: 1px solid #f3f4f6;
    color: inherit;
}
.ts-dropdown.sis-prod-dropdown .option:last-child { border-bottom: none; }
.ts-dropdown.sis-prod-dropdown .option.active,
.ts-dropdown.sis-prod-dropdown .option:hover {
    background: #fef2f2;
    color: inherit;
}
.ts-dropdown.sis-prod-dropdown .option.selected { background: #fff; color: inherit; }
.ts-dropdown.sis-prod-dropdown .option.selected.active { background: #fef2f2; }

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
.sis-prod__qtys {
    display: flex;
    flex-shrink: 0;
    gap: 6px;
}
.sis-prod__qty {
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
.sis-prod__qty--wh { background: #eff6ff; }
.sis-prod__qty--wh .sis-prod__qty-lbl { color: #93c5fd; }
.sis-prod__qty--wh .sis-prod__qty-val { color: #1d4ed8; }
.sis-prod__qty--wh.sis-prod__qty--empty { background: #fef2f2; }
.sis-prod__qty--wh.sis-prod__qty--empty .sis-prod__qty-lbl { color: #f87171; }
.sis-prod__qty--wh.sis-prod__qty--empty .sis-prod__qty-val { color: #dc2626; }

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
</style>
@endpush

@php
    $woPanelClass = 'panel-card panel-card-flush overflow-visible ss-dash-enter-child';
@endphp

<div
    x-data="purchaseOrderUnifiedCreate()"
    x-init="$nextTick(() => { requestAnimationFrame(() => $el.classList.add('is-ready')); })"
    class="ss-dashboard ss-dash-page ss-wo-create"
>
<div class="ss-dash-content space-y-5">
    <div class="flex flex-col gap-3 ss-dash-enter sm:flex-row sm:items-start sm:justify-between" style="--ss-delay: 0ms">
        <div>
            <h1 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">Buat Purchase Order</h1>
            <p class="mt-1 text-[13px] text-gray-500">Ajukan permintaan stok ke warehouse 77racing</p>
        </div>
        <a href="{{ route('speedshop.stock-in.pembelian-mo.index') }}" class="btn-secondary shrink-0">← Kembali</a>
    </div>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-12 ss-dash-enter" style="--ss-delay: 80ms">
        <div class="space-y-5 lg:col-span-8">
            <div class="{{ $woPanelClass }}" style="--ss-delay: 120ms; --ss-stagger: 0">
                <div class="flex flex-col gap-3 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="section-hd" style="margin-bottom:0">
                        <h2>Daftar Item</h2>
                        <p>Pilih sparepart Modifikasi Ori (sama dengan menu Inventori → Sparepart Modifikasi Ori)</p>
                    </div>
                    <span class="wo-badge" x-text="formItems.length + ' item · ' + formItems.reduce((s, i) => s + (Number(i.qty)||0), 0) + ' qty'"></span>
                </div>

                <div class="panel-card-body border-b border-gray-100">
                    <div class="flex flex-wrap items-end gap-4">
                        <div class="min-w-[260px] flex-1">
                            <label class="wo-label">Produk <span class="req">*</span></label>
                            <select x-ref="productSelect" id="product-select" placeholder="Cari / pilih produk..."></select>
                        </div>
                        <div class="w-28">
                            <label class="wo-label">Qty <span class="req">*</span></label>
                            <input type="number" x-model.number="addQty" min="1" class="wo-input text-center">
                        </div>
                        <div class="flex items-end">
                            <button type="button" @click="addItem()" class="btn-primary inline-flex h-10 items-center gap-2 px-5">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                Tambah
                            </button>
                        </div>
                    </div>
                    <p class="panel-card-sub mt-3">Stok SS dan WH ditampilkan sebagai informasi — qty PO tidak dibatasi oleh stok lokal.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="bg-[#f7f3f4]">
                                <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No.</th>
                                <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Kode</th>
                                <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Nama Produk</th>
                                <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-center">Stok SS / WH</th>
                                <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-center">Qty</th>
                                <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 w-14"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="(row, idx) in formItems" :key="row.product_id">
                                <tr class="transition hover:bg-[#fcfafb]">
                                    <td class="px-5 py-4 text-gray-500" x-text="idx + 1"></td>
                                    <td class="px-5 py-4 font-semibold text-gray-900" x-text="row.kode_produk"></td>
                                    <td class="px-5 py-4 text-gray-700" x-text="row.nama_produk"></td>
                                    <td class="px-5 py-4 text-center">
                                        <div class="flex flex-col items-center gap-0.5 text-xs">
                                            <span class="inline-flex items-center rounded-full border border-gray-200 bg-gray-50 px-2.5 py-1 font-semibold text-gray-700" x-text="'SS: ' + row.stok_ss_display"></span>
                                            <span class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-2.5 py-1 font-semibold text-blue-700" x-text="'WH: ' + row.stok_wh_display"></span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <input type="number" x-model.number="row.qty" min="1" class="wo-input mx-auto w-24 text-center">
                                    </td>
                                    <td class="px-5 py-4">
                                        <button type="button" @click="removeItem(idx)" class="wo-icon-btn" title="Hapus">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                            <template x-if="formItems.length === 0">
                                <tr>
                                    <td colspan="6" class="px-5 py-12 text-center text-gray-400">
                                        <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        <p class="mt-3 text-sm font-medium">Belum ada item ditambahkan</p>
                                        <p class="mt-1 text-xs">Pilih produk di atas, lalu klik Tambah</p>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="lg:col-span-4">
            <div class="panel-card panel-card-flush summary-card ss-dash-enter-child" style="--ss-delay: 120ms; --ss-stagger: 1">
                <div class="section-hd">
                    <h2>Ringkasan</h2>
                    <p>Total item, catatan, dan konfirmasi pengajuan</p>
                </div>

                <div class="mt-2 space-y-4 px-5">
                    <div class="mb-2">
                        <div class="summary-row">
                            <span>Total Item</span>
                            <span x-text="formItems.length + ' SKU'"></span>
                        </div>
                        <div class="summary-row">
                            <span>Total Qty</span>
                            <span x-text="formItems.reduce((s, i) => s + (Number(i.qty)||0), 0) + ' unit'"></span>
                        </div>
                        <div class="summary-total">
                            <span>Warehouse Tujuan</span>
                            <span>{{ $warehouseName ?? '77racing' }}</span>
                        </div>
                    </div>

                    <div>
                        <label class="wo-label">Catatan <span class="text-gray-400 normal-case tracking-normal">(opsional)</span></label>
                        <textarea x-model="catatan" rows="3" class="wo-textarea" placeholder="Catatan tambahan..."></textarea>
                    </div>
                </div>

                <div class="space-y-3 border-t border-gray-100 px-5 pb-5 pt-5">
                    <button
                        type="button"
                        @click="submit()"
                        :disabled="loading || formItems.length === 0"
                        class="btn-primary flex h-11 w-full disabled:cursor-not-allowed disabled:opacity-70"
                    >
                        <svg x-show="!loading" class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <svg x-show="loading" class="h-4 w-4 shrink-0 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        <span x-text="loading ? 'Mengirim...' : 'Ajukan ke Warehouse'"></span>
                    </button>
                    <p class="text-center text-xs text-gray-500">Pengajuan akan dikirim ke warehouse untuk diproses pengiriman.</p>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script>
function purchaseOrderUnifiedCreate() {
    const warehouseProducts = @json($warehouseProducts ?? []);

    const storePurchaseOrderUrl = '{{ route('speedshop.stock-in.pembelian-mo.purchase-order.store') }}';

    return {
        loading: false,
        catatan: '',

        addProductId: '',
        addQty: 1,

        formItems: [],

        productSelect: null,

        _ssStockByProductId: {},

        init() {
            this._initStocks();
            this.$nextTick(() => {
                this._initProductSelect();
            });
        },

        _initStocks() {
            this._ssStockByProductId = {};
            (warehouseProducts || []).forEach(p => {
                const s = Number(p.jumlah) || 0;
                this._ssStockByProductId[p.id] = s;
            });
        },

        _productLineLabel(data) {
            const kode = (data?.kode_produk || '').trim() || '—';
            const nama = (data?.nama_produk || '').trim() || '—';
            return `${kode} - ${nama}`;
        },

        _productOptionsForTipe() {
            return (warehouseProducts || []).map(p => {
                const stokSs = Number(this._ssStockByProductId[p.id] ?? (Number(p.jumlah) || 0)) || 0;
                const stokWh = Number(p.jumlah_wh ?? 0) || 0;
                const row = {
                    id: p.id,
                    kode_produk: p.kode_produk,
                    nama_produk: p.nama_produk,
                    brand_nama: p.brand_nama || '',
                    kode_supplier: (p.kode_supplier || '').trim(),
                    stok_ss: stokSs,
                    stok_wh: stokWh,
                    jumlah: stokSs,
                };
                return { ...row, label: this._productLineLabel(row) };
            });
        },

        productOptionHtml(data, escape) {
            const stokSs = Number(data.stok_ss || 0);
            const stokWh = Number(data.stok_wh || 0);
            const brand = (data.brand_nama || '').trim();
            const emptySs = stokSs === 0 ? ' sis-prod__qty--empty' : '';
            const emptyWh = stokWh === 0 ? ' sis-prod__qty--empty' : '';
            const meta = brand ? `<div class="sis-prod__meta">${escape(brand)}</div>` : '';
            return `<div class="sis-prod"><div class="sis-prod__main"><div class="sis-prod__sku">${escape(data.kode_produk || '')}</div><div class="sis-prod__name">${escape(data.nama_produk || '')}</div>${meta}</div><div class="sis-prod__qtys"><div class="sis-prod__qty${emptySs}"><span class="sis-prod__qty-lbl">SS</span><span class="sis-prod__qty-val">${stokSs.toLocaleString('id-ID')}</span></div><div class="sis-prod__qty sis-prod__qty--wh${emptyWh}"><span class="sis-prod__qty-lbl">WH</span><span class="sis-prod__qty-val">${stokWh.toLocaleString('id-ID')}</span></div></div></div>`;
        },

        productItemHtml(data, escape) {
            return `<div class="sis-prod-sel"><span class="sis-prod-sel__sku">${escape(data.kode_produk || '')}</span><span class="sis-prod-sel__name">${escape(data.nama_produk || '')}</span></div>`;
        },

        _initProductSelect() {
            const el = this.$refs.productSelect;
            if (!el || typeof TomSelect === 'undefined') return;
            if (this.productSelect) {
                this.productSelect.destroy();
                this.productSelect = null;
            }

            const self = this;
            this.productSelect = new TomSelect(el, {
                valueField: 'id',
                labelField: 'label',
                searchField: ['kode_produk', 'kode_supplier', 'nama_produk', 'brand_nama'],
                placeholder: 'Cari / pilih produk...',
                maxOptions: 10000,
                dropdownClass: 'ts-dropdown sis-prod-dropdown',
                options: this._productOptionsForTipe(),
                onChange(val) {
                    self.addProductId = val || '';
                },
                render: {
                    option: (data, escape) => self.productOptionHtml(data, escape),
                    item: (data, escape) => self.productItemHtml(data, escape),
                    no_results: () => '<div class="no-results">Produk tidak ditemukan</div>',
                }
            });
        },

        addItem() {
            if (!this.addProductId || this.addQty < 1) {
                Swal.fire('Perhatian', 'Pilih produk dan isi qty minimal 1.', 'warning');
                return;
            }

            const list = warehouseProducts;
            const found = (list || []).find(p => p.id == this.addProductId);
            if (!found) return;

            const qty = Math.max(1, Number(this.addQty) || 1);

            const exists = this.formItems.find(i =>
                i.product_id == found.id
            );
            if (exists) {
                exists.qty += qty;
            } else {
                this.formItems.push({
                    product_id: found.id,
                    kode_produk: found.kode_produk,
                    nama_produk: found.nama_produk,
                    qty: qty,
                    stok_ss_display: Number(found.jumlah || 0).toLocaleString('id-ID'),
                    stok_wh_display: Number(found.jumlah_wh || 0).toLocaleString('id-ID'),
                });
            }

            this.addProductId = '';
            this.addQty = 1;
            if (this.productSelect) this.productSelect.clear();
        },

        removeItem(idx) {
            this.formItems.splice(idx, 1);
        },

        async submit() {
            if (this.formItems.length === 0) {
                Swal.fire('Perhatian', 'Minimal satu item wajib ditambahkan.', 'warning');
                return;
            }

            for (const item of this.formItems) {
                if (Number(item.qty) < 1) {
                    Swal.fire('Perhatian', 'Qty setiap item minimal 1.', 'warning');
                    return;
                }
            }

            const result = await Swal.fire({
                title: 'Submit Purchase Order?',
                text: 'Pengajuan akan dikirim ke Warehouse untuk diproses pengiriman.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Submit!',
                cancelButtonText: 'Batal'
            });
            if (!result.isConfirmed) return;

            this.loading = true;

            try {
                const res = await fetch(storePurchaseOrderUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        catatan: this.catatan || null,
                        items: this.formItems.map(i => ({ product_id: i.product_id, qty: Number(i.qty || 0) })),
                    }),
                });
                if (!res.ok) {
                    const d = await res.json().catch(() => ({}));
                    this.loading = false;
                    Swal.fire('Error', d.message || 'Gagal menyimpan.', 'error');
                    return;
                }
                const data = await res.json().catch(() => ({}));
                await Swal.fire('Berhasil', data.message || 'Purchase Order berhasil diajukan.', 'success');
                window.location.href = data.redirect || '{{ route('speedshop.stock-in.pembelian-mo.index') }}';
            } catch (e) {
                this.loading = false;
                Swal.fire('Error', 'Terjadi kesalahan jaringan.', 'error');
            }
        },
    };
}
</script>
@endpush
