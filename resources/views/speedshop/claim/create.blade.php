@extends('layouts.speedshop')

@section('title', 'Buat Claim Produk Cacat')

@section('content')
<div x-data="claimCreate()" class="space-y-5">
    <nav class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('speedshop.dashboard') }}" class="transition hover:text-gray-700">Home</a>
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('speedshop.claim.index') }}" class="transition hover:text-gray-700">Claim Produk Cacat</a>
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <span class="font-medium text-gray-900">Buat Claim</span>
    </nav>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-5">
            <div class="rounded-2xl border border-gray-200 bg-white p-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-[22px] font-semibold leading-tight tracking-tight text-gray-900">Pilih Produk</h2>
                        <p class="mt-1 text-[13px] text-gray-500">Pilih produk lewat dropdown (sama seperti sparepart di Work Order). Stok Speedshop ditampilkan di setiap opsi; stok 0 tidak bisa ditambahkan ke claim.</p>
                    </div>
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-red-50 text-red-600">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                </div>

                @if(count($stocks) === 0)
                <p class="mt-5 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-[13px] text-amber-800">Belum ada produk di master data untuk warehouse yang terhubung ke Speedshop ini.</p>
                @else
                <div class="mt-5 flex flex-wrap items-end gap-4">
                    <div class="min-w-[200px] flex-1">
                        <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Produk</label>
                        <select id="product-select-claim" class="w-full rounded-xl border border-gray-200 bg-white text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40"></select>
                    </div>
                    <div class="w-24">
                        <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Qty</label>
                        <input type="number" x-model.number="addQty" min="1" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-center text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40">
                    </div>
                    <div class="flex items-end pb-0.5">
                        <button type="button" @click="addProduct()" class="inline-flex items-center gap-2 rounded-xl bg-brand px-5 py-2.5 text-sm font-medium text-brand-ink shadow-lg shadow-brand/35 transition hover:bg-brand-hover">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                            Tambah
                        </button>
                    </div>
                </div>
                @endif

                <div class="mt-6 overflow-x-auto rounded-xl border border-gray-200">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="bg-[#f7f3f4]">
                                <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Kode</th>
                                <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Nama Produk</th>
                                <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-center">Stok</th>
                                <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-center">Jumlah Claim</th>
                                <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 w-12"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="(item, idx) in formItems" :key="idx">
                                <tr class="transition hover:bg-[#fcfafb]">
                                    <td class="px-5 py-4 font-semibold text-gray-900" x-text="item.kode_produk"></td>
                                    <td class="px-5 py-4 text-gray-700" x-text="item.nama_produk"></td>
                                    <td class="px-5 py-4 text-center">
                                        <span class="inline-flex items-center rounded-lg border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-800" x-text="item.jumlah + ' unit'"></span>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <input type="number" x-model.number="item.quantity" :max="item.jumlah" min="1" class="w-24 rounded-lg border border-gray-200 px-2 py-1.5 text-center text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40">
                                    </td>
                                    <td class="px-5 py-4">
                                        <button type="button" @click="formItems.splice(idx, 1)" class="flex h-8 w-8 items-center justify-center rounded-lg text-red-400 transition hover:bg-red-50 hover:text-red-600">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                            <template x-if="formItems.length === 0">
                                <tr>
                                    <td colspan="5" class="px-5 py-12 text-center text-[13px] text-gray-500">Belum ada produk ditambahkan</td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="space-y-5">
            <div class="rounded-2xl border border-gray-200 bg-white p-5">
                <h2 class="text-[22px] font-semibold leading-tight tracking-tight text-gray-900">Detail Claim</h2>
                <p class="mt-1 text-[13px] text-gray-500">Catatan opsional. Warehouse mendapat notifikasi di menu Produk Cacat.</p>

                <div class="mt-4 space-y-3">
                    <div class="flex items-center justify-between rounded-lg bg-gray-50 px-4 py-3">
                        <span class="text-sm font-medium text-gray-700">Total Produk</span>
                        <span class="text-sm font-bold text-gray-900" x-text="formItems.length + ' item'"></span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-gray-50 px-4 py-3">
                        <span class="text-sm font-medium text-gray-700">Total Unit</span>
                        <span class="text-sm font-bold text-gray-900" x-text="formItems.reduce((s, i) => s + (i.quantity || 0), 0) + ' unit'"></span>
                    </div>
                </div>

                <div class="mt-5">
                    <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Catatan (opsional)</label>
                    <textarea x-model="formCatatan" rows="3" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm transition focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40" placeholder="Catatan tambahan..."></textarea>
                </div>

                <div class="mt-5 space-y-3">
                    <button type="button" @click="submitClaim()" :disabled="loading || formItems.length === 0"
                            class="flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-brand px-5 text-sm font-semibold text-brand-ink shadow-lg shadow-brand/35 transition hover:bg-brand-hover disabled:cursor-not-allowed disabled:opacity-50">
                        <svg x-show="!loading" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        <svg x-show="loading" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        <span x-text="loading ? 'Mengirim...' : 'Ajukan Claim'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function claimCreate() {
    const catalog = @json($stocks);

    return {
        loading: false,
        formItems: [],
        formCatatan: '',
        addQty: 1,
        productSelect: null,

        init() {
            this.$nextTick(() => {
                const el = document.getElementById('product-select-claim');
                if (!el || typeof TomSelect === 'undefined' || !catalog || catalog.length === 0) {
                    return;
                }
                this.productSelect = new TomSelect(el, {
                    valueField: 'id',
                    labelField: 'label',
                    searchField: ['kode_produk', 'nama_produk'],
                    placeholder: 'Pilih produk...',
                    options: catalog.map((p) => ({
                        id: p.id,
                        kode_produk: p.kode_produk || '',
                        nama_produk: p.nama_produk || '',
                        jumlah: Number(p.jumlah) || 0,
                        label: `[${p.kode_produk || ''}] ${p.nama_produk || ''} — Stok: ${Number(p.jumlah || 0).toLocaleString('id-ID')}`,
                    })),
                    render: {
                        option: (data, escape) => {
                            const stok = Number(data.jumlah || 0);
                            const stokClass = stok === 0 ? 'text-red-600 font-medium' : 'text-gray-500';
                            return `<div class="flex items-center justify-between gap-3"><span><span class="font-medium">${escape(data.kode_produk)}</span> - ${escape(data.nama_produk)}</span><span class="text-xs ${stokClass}">Stok: ${stok.toLocaleString('id-ID')}</span></div>`;
                        },
                        item: (data, escape) => `<div>${escape(data.kode_produk)} - ${escape(data.nama_produk)} <span class="text-gray-400 text-xs">(Stok: ${Number(data.jumlah || 0).toLocaleString('id-ID')})</span></div>`,
                    },
                });
            });
        },

        addProduct() {
            const sel = this.productSelect;
            const id = sel ? sel.getValue() : '';
            if (!sel || !id) {
                Swal.fire('Perhatian', 'Pilih produk terlebih dahulu.', 'warning');
                return;
            }
            const found = catalog.find((p) => String(p.id) === String(id));
            if (!found) {
                return;
            }
            if (found.jumlah < 1) {
                Swal.fire('Perhatian', 'Stok produk habis, tidak bisa ditambahkan ke claim.', 'warning');
                return;
            }
            let qty = Math.max(1, Number(this.addQty) || 1);
            if (qty > found.jumlah) {
                Swal.fire('Stok Tidak Cukup', `Stok ${found.nama_produk} tersedia ${found.jumlah} unit.`, 'warning');
                return;
            }
            const exists = this.formItems.find((i) => Number(i.product_id) === Number(found.id));
            if (exists) {
                exists.quantity = Math.min((exists.quantity || 0) + qty, found.jumlah);
            } else {
                this.formItems.push({
                    product_id: found.id,
                    kode_produk: found.kode_produk || '',
                    nama_produk: found.nama_produk || '',
                    jumlah: found.jumlah,
                    quantity: qty,
                });
            }
            this.addQty = 1;
            sel.clear();
        },

        async submitClaim() {
            if (this.formItems.length === 0) return;

            const totalUnit = this.formItems.reduce((s, i) => s + (i.quantity || 0), 0);
            const result = await Swal.fire({
                title: 'Ajukan Claim?',
                html: `<div style="text-align:left;font-size:14px;">
                    <p><strong>Total:</strong> ${this.formItems.length} produk, ${totalUnit} unit</p>
                    <p class="mt-2 text-gray-500">Claim akan dikirim ke warehouse. Stok Speedshop tidak berkurang. Warehouse akan mendapat notifikasi di menu Produk Cacat.</p>
                </div>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Ajukan'
            });
            if (!result.isConfirmed) return;

            this.loading = true;
            const payload = {
                _token: document.querySelector('meta[name="csrf-token"]').content,
                items: this.formItems.map(i => ({ product_id: i.product_id, quantity: i.quantity })),
                catatan: this.formCatatan || null,
            };

            try {
                const res = await fetch('{{ route("speedshop.claim.store") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': payload._token, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({
                        items: payload.items,
                        catatan: payload.catatan,
                    })
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    Swal.fire('Gagal', data.message || 'Gagal mengajukan claim.', 'error');
                    this.loading = false;
                    return;
                }
                await Swal.fire('Berhasil', data.message || 'Claim berhasil diajukan.', 'success');
                window.location.href = data.redirect || '{{ route("speedshop.claim.index") }}';
            } catch (e) {
                Swal.fire('Error', 'Gagal mengirim data.', 'error');
                this.loading = false;
            }
        }
    };
}
</script>
@endpush
