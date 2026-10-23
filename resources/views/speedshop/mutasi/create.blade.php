@extends('layouts.speedshop')

@section('title', 'Buat Mutasi')

@section('content')
@push('styles')
@include('partials.speedshop.dashboard-load-animations')
@include('partials.speedshop.dashboard-manager-template-styles')
@include('partials.speedshop.work-order-create-styles')
@endpush

@php
    $woPanelClass = 'panel-card panel-card-flush overflow-visible ss-dash-enter-child';
@endphp

<div
    x-data="mutasiKeWarehouse()"
    x-init="init(); $nextTick(() => { requestAnimationFrame(() => $el.classList.add('is-ready')); })"
    class="ss-dashboard ss-dash-page ss-wo-create"
>
<div class="ss-dash-content space-y-5">
    <div class="flex flex-col gap-3 ss-dash-enter sm:flex-row sm:items-start sm:justify-between" style="--ss-delay: 0ms">
        <div>
            <h1 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">Buat Mutasi</h1>
            <p class="mt-1 text-[13px] text-gray-500">Kirim stok ke warehouse atau cabang Speedshop lain</p>
        </div>
        <a href="{{ route('speedshop.mutasi.index') }}" class="btn-secondary shrink-0">← Kembali</a>
    </div>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-12 ss-dash-enter" style="--ss-delay: 80ms">
        <div class="space-y-5 lg:col-span-8">
            <div class="{{ $woPanelClass }}" style="--ss-delay: 120ms; --ss-stagger: 0">
                <div class="flex flex-col gap-3 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="section-hd" style="margin-bottom:0">
                        <h2>Pilih Produk</h2>
                        <p>Sparepart Modifikasi Ori, Sparepart Speedshop, Merchandise, dan Consumable yang terdaftar di inventori cabang Anda</p>
                    </div>
                    <span class="wo-badge" x-text="formItems.length + ' item · ' + formItems.reduce((s, i) => s + (i.quantity || 0), 0) + ' unit'"></span>
                </div>

                <div class="panel-card-body border-b border-gray-100">
                    <div class="flex flex-wrap items-end gap-4">
                        <div class="min-w-[260px] flex-1">
                            <label class="wo-label">Produk</label>
                            <select x-ref="productSelect" id="product-select-mutasi" placeholder="Cari produk..."></select>
                        </div>
                        <div class="w-28">
                            <label class="wo-label">Jumlah</label>
                            <input type="number" x-model.number="addQty" min="1" class="wo-input text-center">
                        </div>
                        <div class="flex items-end">
                            <button type="button" @click="addProduct()" class="btn-primary inline-flex h-10 items-center gap-2 px-5">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                Tambah
                            </button>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="bg-[#f7f3f4]">
                                <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Kode</th>
                                <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Nama Produk</th>
                                <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-center">Stok</th>
                                <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-center">Jumlah Mutasi</th>
                                <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 w-14"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="(item, idx) in formItems" :key="idx">
                                <tr class="transition hover:bg-[#fcfafb]">
                                    <td class="px-5 py-4 font-semibold text-gray-900" x-text="item.kode_produk"></td>
                                    <td class="px-5 py-4 text-gray-700" x-text="item.nama_produk"></td>
                                    <td class="px-5 py-4 text-center">
                                        <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700" x-text="item.jumlah + ' unit'"></span>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <input type="number" x-model.number="item.quantity" :max="item.jumlah" min="1" class="wo-input mx-auto w-24 text-center">
                                    </td>
                                    <td class="px-5 py-4">
                                        <button type="button" @click="formItems.splice(idx, 1)" class="wo-icon-btn" title="Hapus">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                            <template x-if="formItems.length === 0">
                                <tr>
                                    <td colspan="5" class="px-5 py-12 text-center text-gray-400">
                                        <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                        <p class="mt-3 text-sm font-medium">Belum ada produk ditambahkan</p>
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
                    <p>Tujuan, catatan, dan konfirmasi pengiriman</p>
                </div>

                <div class="mt-2 space-y-4 px-5">
                    <div>
                        <label class="wo-label">Tujuan Mutasi <span class="req">*</span></label>
                        <select x-model="formDestination" class="wo-native">
                            <option value="">— Pilih tujuan —</option>
                            <optgroup label="Speedshop lain">
                                @foreach($speedshopsLain as $s)
                                @continue((int) ($currentSpeedshopId ?? 0) === (int) $s->id)
                                <option value="speedshop:{{ $s->id }}">Speedshop — {{ $s->nama }}</option>
                                @endforeach
                            </optgroup>
                        </select>
                        <p class="panel-card-sub mt-1.5">Nomor mutasi dibuat otomatis oleh sistem.</p>
                    </div>

                    <div class="mb-2">
                        <div class="summary-row">
                            <span>Total Produk</span>
                            <span x-text="formItems.length + ' SKU'"></span>
                        </div>
                        <div class="summary-row">
                            <span>Total Unit</span>
                            <span x-text="formItems.reduce((s, i) => s + (i.quantity || 0), 0) + ' unit'"></span>
                        </div>
                        <div class="summary-total">
                            <span x-text="isWarehouseDestination() ? 'Warehouse Tujuan' : (formDestination ? 'Speedshop Tujuan' : 'Tujuan')"></span>
                            <span x-text="destinationLabel()"></span>
                        </div>
                    </div>

                    <div>
                        <label class="wo-label">Catatan <span class="text-gray-400 normal-case tracking-normal">(opsional)</span></label>
                        <textarea x-model="formCatatan" rows="3" class="wo-textarea" placeholder="Catatan tambahan..."></textarea>
                    </div>
                </div>

                <div class="space-y-3 border-t border-gray-100 px-5 pb-5 pt-5">
                    <button
                        type="button"
                        @click="submitMutasi()"
                        :disabled="loading || formItems.length === 0 || !formDestination"
                        class="btn-primary flex h-11 w-full disabled:cursor-not-allowed disabled:opacity-70"
                    >
                        <svg x-show="!loading" class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                        <svg x-show="loading" class="h-4 w-4 shrink-0 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        <span x-text="loading ? 'Mengirim...' : (isWarehouseDestination() ? 'Kirim ke Warehouse' : 'Kirim ke Speedshop')"></span>
                    </button>
                    <p class="text-center text-xs text-gray-500">Stok speedshop asal akan dikurangi setelah mutasi dikirim.</p>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script>
function mutasiProductOptionLabel(data, escape) {
    const kode = String(data.kode_produk ?? '').trim();
    const supplier = String(data.kode_supplier ?? '').trim();
    const nama = String(data.nama_produk ?? '').trim();
    const parts = [kode];
    if (supplier) {
        parts.push(supplier);
    }
    if (nama) {
        parts.push(nama);
    }
    return parts.map((part) => escape(part)).join(' · ');
}

function mutasiKeWarehouse() {
    const products = @json($stocks);
    const warehouses = @json($warehouses);
    const speedshops = @json($speedshopsLain ?? []);
    const currentSpeedshopId = @json((int) ($currentSpeedshopId ?? 0));

    return {
        loading: false,
        formItems: [],
        formDestination: '',
        formCatatan: '',
        addQty: 1,
        productSelect: null,

        init() {
            this.$nextTick(() => {
                const el = document.getElementById('product-select-mutasi');
                if (!el || typeof TomSelect === 'undefined') return;
                const opts = products.map(p => ({
                    id: p.id,
                    kode_produk: p.kode_produk || '',
                    kode_supplier: p.kode_supplier || '',
                    nama_produk: p.nama_produk || '',
                    jumlah: p.jumlah,
                }));
                this.productSelect = new TomSelect(el, {
                    valueField: 'id',
                    labelField: 'nama_produk',
                    searchField: ['nama_produk', 'kode_produk', 'kode_supplier'],
                    placeholder: 'Cari produk...',
                    options: opts,
                    render: {
                        option: (data, escape) => `<div class="flex items-center justify-between gap-3"><span>${mutasiProductOptionLabel(data, escape)}</span><span class="text-xs text-gray-400 shrink-0">Stok: ${data.jumlah}</span></div>`,
                        item: (data, escape) => `<div>${mutasiProductOptionLabel(data, escape)}</div>`,
                    }
                });
            });
        },

        addProduct() {
            const val = this.productSelect?.getValue();
            if (!val) return;
            const found = products.find(p => p.id == val);
            if (!found) return;

            if (this.addQty > found.jumlah) {
                Swal.fire('Stok Tidak Cukup', `Stok ${found.nama_produk} tersedia ${found.jumlah} unit.`, 'warning');
                return;
            }

            const exists = this.formItems.find(i => i.product_id == found.id);
            if (exists) {
                exists.quantity = Math.min((exists.quantity || 0) + this.addQty, found.jumlah);
            } else {
                this.formItems.push({
                    product_id: found.id,
                    kode_produk: found.kode_produk,
                    nama_produk: found.nama_produk,
                    jumlah: found.jumlah,
                    quantity: this.addQty,
                });
            }
            this.addQty = 1;
            this.productSelect.clear();
        },

        parseDestination() {
            if (!this.formDestination) return { type: null, id: null };
            const [type, id] = this.formDestination.split(':');
            return { type, id };
        },

        isWarehouseDestination() {
            return this.parseDestination().type === 'warehouse';
        },

        destinationLabel() {
            const { type, id } = this.parseDestination();
            if (!type || !id) {
                return '—';
            }
            if (type === 'warehouse') {
                return warehouses.find(w => w.id == id)?.nama || 'Warehouse';
            }

            return speedshops.find(s => s.id == id)?.nama || 'Speedshop';
        },

        async submitMutasi() {
            if (this.formItems.length === 0 || !this.formDestination) return;

            const { type, id } = this.parseDestination();
            if (!type || !id) return;
            if (type === 'speedshop' && Number(id) === Number(currentSpeedshopId)) {
                Swal.fire('Perhatian', 'Tujuan mutasi tidak boleh Speedshop Anda sendiri.', 'warning');
                return;
            }

            const totalUnit = this.formItems.reduce((s, i) => s + (i.quantity || 0), 0);
            const tujuanNama = this.destinationLabel();
            const result = await Swal.fire({
                title: type === 'warehouse' ? 'Kirim Mutasi ke Warehouse?' : 'Kirim Mutasi ke Speedshop?',
                html: `<div style="text-align:left;font-size:14px;">
                    <p><strong>Tujuan:</strong> ${tujuanNama}</p>
                    <p><strong>Total:</strong> ${this.formItems.length} produk, ${totalUnit} unit</p>
                    <p class="mt-2 text-gray-500">
                        Stok Speedshop akan dikurangi.
                        ${type === 'warehouse'
                            ? 'Warehouse tujuan akan mendapat notifikasi.'
                            : 'Stok Speedshop tujuan akan langsung bertambah.'}
                    </p>
                </div>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Kirim!',
                cancelButtonText: 'Batal'
            });
            if (!result.isConfirmed) return;

            this.loading = true;

            const payload = {
                destination_type: type,
                warehouse_id: type === 'warehouse' ? id : null,
                speedshop_tujuan_id: type === 'speedshop' ? id : null,
                catatan: this.formCatatan || null,
                items: this.formItems.map(i => ({ product_id: i.product_id, quantity: i.quantity })),
            };

            const res = await fetch('{{ route("speedshop.mutasi.store") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (!res.ok) {
                const d = await res.json().catch(() => ({}));
                this.loading = false;
                Swal.fire('Error', d.message || 'Gagal menyimpan.', 'error');
                return;
            }

            const data = await res.json();
            await Swal.fire('Berhasil', data.message || 'Mutasi berhasil dibuat.', 'success');
            window.location.href = data.redirect || '{{ route("speedshop.mutasi.index") }}';
        }
    }
}
</script>
@endpush
