{{-- Modal produk tidak terpakai (bersihkan / hapus permanen) — hanya Manager Speedshop --}}
<div x-show="showUnusedModal" x-cloak class="fixed inset-0 z-[100] overflow-y-auto" @keydown.escape.window="showUnusedModal = false">
    <div x-show="showUnusedModal" x-transition.opacity class="fixed inset-0 bg-black/50" @click="showUnusedModal = false"></div>
    <div class="relative z-[101] flex min-h-full items-center justify-center p-4">
        <div x-show="showUnusedModal" x-transition class="relative flex w-full max-w-4xl flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl" @click.stop>
            <div class="border-b border-gray-100 px-6 py-5 sm:px-8">
                <div class="flex items-start gap-4">
                    <div class="flex-1">
                        <h3 class="text-lg font-bold text-gray-900">Produk tidak terpakai — bersihkan dari outlet</h3>
                        <p class="mt-1 text-sm text-gray-500">Hanya produk <strong>sudah diarsipkan</strong> di master outlet ini yang <strong>belum dipakai</strong> di operasional outlet (penjualan, WO, PO, klaim, mutasi, stok &gt; 0, dll.). Produk <strong>milik outlet</strong> dihapus permanen dari database. Produk <strong>katalog gudang</strong> hilang dari master &amp; arsip outlet (data gudang tetap), <strong>tidak diaktifkan kembali</strong>. Tombol hapus memproses <strong>seluruh</strong> total, bukan hanya pratinjau.</p>
                    </div>
                    <button type="button" @click="showUnusedModal = false" class="rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600" aria-label="Tutup">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
            <div class="max-h-[min(24rem,50vh)] overflow-y-auto bg-white px-6 py-4 sm:px-8">
                <div x-show="unusedLoading" class="flex items-center justify-center gap-2 py-12 text-sm text-gray-500">
                    <svg class="h-5 w-5 animate-spin text-rose-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    Memuat daftar…
                </div>
                <template x-if="!unusedLoading && unusedTotal === 0">
                    <p class="py-10 text-center text-sm text-gray-500">Tidak ada produk yang memenuhi kriteria.</p>
                </template>
                <p x-show="!unusedLoading && unusedPreviewTruncated" class="mb-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900">
                    Menampilkan pratinjau <span x-text="unusedItems.length"></span> dari <span x-text="unusedTotal"></span> produk. Tombol hapus akan memproses <strong>semua</strong> <span x-text="unusedTotal"></span> produk.
                </p>
                <table x-show="!unusedLoading && unusedTotal > 0" class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-[11px] font-semibold uppercase tracking-wider text-gray-500">
                            <th class="pb-2 pr-3">Kode</th>
                            <th class="pb-2 pr-3">Nama</th>
                            <th class="pb-2 pr-3">Asal</th>
                            <th class="pb-2 pr-3 text-right">Stok outlet</th>
                            <th class="pb-2">Efek hapus</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <template x-for="row in unusedItems" :key="row.id">
                            <tr>
                                <td class="py-2.5 pr-3 font-mono text-xs font-semibold text-gray-700" x-text="row.kode_produk"></td>
                                <td class="py-2.5 pr-3 text-gray-900" x-text="row.nama_produk"></td>
                                <td class="py-2.5 pr-3">
                                    <span class="inline-flex rounded-lg border border-slate-200 bg-slate-50 px-2 py-0.5 text-[11px] font-semibold text-slate-700" x-text="row.scope || '—'"></span>
                                </td>
                                <td class="py-2.5 pr-3 text-right font-mono text-xs text-gray-700" x-text="row.stok_outlet ?? 0"></td>
                                <td class="py-2.5 text-[11px] text-gray-600" x-text="row.efek_hapus || '—'"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 bg-white px-6 py-4 sm:px-8">
                <p class="text-sm text-gray-600" x-show="!unusedLoading">
                    Total: <span class="font-semibold text-gray-900" x-text="unusedTotal"></span> produk
                </p>
                <div class="flex items-center gap-3">
                    <button type="button" @click="showUnusedModal = false" class="btn-secondary px-5 py-2.5">Tutup</button>
                    <button type="button" @click="bulkDeleteUnusedAll()" :disabled="bulkDeletingUnused || unusedLoading || unusedTotal === 0"
                            class="btn-primary inline-flex items-center gap-2 bg-rose-600 px-5 py-2.5 hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-50">
                        <svg x-show="bulkDeletingUnused" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        <span x-text="bulkDeletingUnused ? 'Memproses…' : 'Bersihkan semua'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
