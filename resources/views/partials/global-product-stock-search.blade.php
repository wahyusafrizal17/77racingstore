{{-- Trigger: form search kecil di header. Klik buka modal besar. --}}
<div class="relative" x-data="globalProductStockSearch()">
    <button type="button" @click="open = true; search(); $nextTick(() => $refs.modalSearch?.focus())"
            title="Cari stok produk"
            aria-label="Cari stok produk"
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-gray-200 bg-gray-50/80 text-gray-500 transition hover:border-gray-300 hover:bg-gray-100 hover:text-gray-700 sm:h-auto sm:w-52 sm:justify-start sm:gap-2 sm:px-3 sm:py-2 sm:text-left sm:text-sm">
        <svg class="h-4 w-4 shrink-0 text-gray-400 sm:flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <span class="hidden truncate sm:inline">Cari stok produk...</span>
    </button>

    {{-- Modal besar --}}
    <div x-show="open" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[100] flex items-start justify-center overflow-y-auto bg-black/50 p-4 pt-20"
         @keydown.escape.window="open = false"
         @click.self="open = false">
        <div
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="w-full max-w-4xl rounded-2xl border border-gray-200 bg-white shadow-2xl"
             @click.stop>
            <div class="sticky top-0 z-10 border-b border-gray-100 bg-white px-6 py-4">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-lg font-bold text-gray-900">Cari Stok Produk (Semua Gudang)</h2>
                    <button type="button" @click="open = false" class="rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-12">
                    <div class="sm:col-span-3">
                        <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gray-500">Kode Produk</label>
                        <input type="text" x-ref="modalSearch" x-model="filters.kode" @input.debounce.300ms="search(1)"
                               placeholder="Kode…"
                               class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40">
                    </div>
                    <div class="sm:col-span-5">
                        <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gray-500">Nama Produk</label>
                        <input type="text" x-model="filters.nama" @input.debounce.300ms="search(1)"
                               placeholder="Nama…"
                               class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40">
                    </div>
                    <div class="sm:col-span-4">
                        <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-gray-500">Lokasi</label>
                        <select x-model="filters.lokasi" @change="search(1)"
                                class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40">
                            <option value="">Semua Lokasi</option>
                            <template x-for="loc in locations" :key="loc.key">
                                <option :value="loc.key" x-text="loc.label"></option>
                            </template>
                        </select>
                    </div>
                </div>
            </div>
            <div class="max-h-[70vh] overflow-y-auto p-6">
                <template x-if="loading">
                    <div class="flex items-center justify-center py-12">
                        <svg class="h-8 w-8 animate-spin text-red-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    </div>
                </template>
                <template x-if="!loading && products.length === 0 && pagination.total === 0">
                    <div class="py-12 text-center text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8 4-8-4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        <p class="mt-3 font-medium">Tidak ada produk ditemukan</p>
                        <p class="mt-1 text-sm">Coba kata kunci lain atau kosongkan untuk melihat semua</p>
                    </div>
                </template>
                <template x-if="!loading && (products.length > 0 || pagination.total > 0)">
                    <div>
                        <div class="overflow-hidden rounded-xl border border-gray-200">
                            <table class="w-full text-left text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 font-semibold text-gray-600">No.</th>
                                        <th class="px-4 py-3 font-semibold text-gray-600">Kode Produk</th>
                                        <th class="px-4 py-3 font-semibold text-gray-600">Nama Produk</th>
                                        <th class="px-4 py-3 font-semibold text-gray-600">Lokasi (Gudang / Speedshop)</th>
                                        <th class="px-4 py-3 text-right font-semibold text-gray-600">Stok</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <template x-for="(row, i) in products" :key="row.id">
                                        <tr class="transition hover:bg-gray-50">
                                            <td class="px-4 py-3 text-gray-500" x-text="pagination.from + i"></td>
                                            <td class="px-4 py-3 font-medium text-gray-900" x-text="row.kode_produk"></td>
                                            <td class="px-4 py-3 text-gray-700" x-text="row.nama_produk"></td>
                                            <td class="px-4 py-3 text-gray-700" x-text="row.lokasi_nama"></td>
                                            <td class="px-4 py-3 text-right font-semibold text-gray-900" x-text="formatNumber(row.jumlah)"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 pt-4">
                            <p class="text-sm text-gray-600" x-text="'Menampilkan ' + (pagination.from || 0) + '–' + (pagination.to || 0) + ' dari ' + (pagination.total || 0) + ' data'"></p>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="goToPage(pagination.current_page - 1)" :disabled="pagination.current_page <= 1"
                                        class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50">Sebelumnya</button>
                                <span class="text-sm text-gray-500" x-text="'Halaman ' + pagination.current_page + ' / ' + (pagination.last_page || 1)"></span>
                                <button type="button" @click="goToPage(pagination.current_page + 1)" :disabled="pagination.current_page >= pagination.last_page"
                                        class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50">Selanjutnya</button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
function globalProductStockSearch() {
    return {
        open: false,
        filters: { kode: '', nama: '', lokasi: '' },
        locations: [],
        products: [],
        loading: false,
        pagination: {
            current_page: 1,
            last_page: 1,
            per_page: 20,
            total: 0,
            from: 0,
            to: 0
        },

        search(page) {
            if (page === undefined) page = 1;
            this.loading = true;
            const base = '{{ route("product-stock-search") }}';
            const params = new URLSearchParams();
            params.set('per_page', '20');
            params.set('page', String(page));
            const kode = (this.filters?.kode || '').trim();
            const nama = (this.filters?.nama || '').trim();
            const lokasi = (this.filters?.lokasi || '').trim();
            if (kode) params.set('kode', kode);
            if (nama) params.set('nama', nama);
            if (lokasi) params.set('lokasi', lokasi);
            const url = base + (base.includes('?') ? '&' : '?') + params.toString();
            fetch(url, { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(data => {
                    this.products = data.products || [];
                    this.locations = Array.isArray(data.locations) ? data.locations : this.locations;
                    this.pagination = data.pagination || this.pagination;
                    this.loading = false;
                })
                .catch(() => {
                    this.products = [];
                    this.loading = false;
                });
        },

        goToPage(page) {
            if (page < 1 || page > this.pagination.last_page) return;
            this.search(page);
        },

        formatNumber(n) {
            return Number(n || 0).toLocaleString('id-ID');
        }
    };
}
</script>
@endpush
@endonce
