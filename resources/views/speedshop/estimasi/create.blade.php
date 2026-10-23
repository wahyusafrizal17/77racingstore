@extends('layouts.speedshop')

@section('title', 'Estimasi Service')

@section('content')
@push('styles')
@include('partials.speedshop.dashboard-load-animations')
@include('partials.speedshop.work-order-create-styles')
<style>
.ss-wo-create .btn-whatsapp {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    border: 1px solid #25D366; border-radius: 9px; background: #ECFDF5; color: #128C7E;
    padding: 10px 18px; font-size: 13px; font-weight: 600; cursor: pointer;
    transition: background 0.15s, border-color 0.15s;
}
.ss-wo-create .btn-whatsapp:hover {
    background: #D1FAE5; border-color: #128C7E;
}
</style>
@endpush

<div
    x-data="estimasiService()"
    x-init="init(); $nextTick(() => { requestAnimationFrame(() => $el.classList.add('is-ready')); })"
    class="ss-dashboard ss-dash-page ss-wo-create"
>
<div class="ss-dash-content space-y-5">
    @if($errors->any())
    <div class="no-print ss-dash-enter wo-alert" style="--ss-delay: 40ms">
        <ul>
            @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="no-print ss-dash-enter" style="--ss-delay: 80ms">
        <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
            {{-- Left: Form & Items --}}
            <div class="lg:col-span-2 space-y-5">
                {{-- Informasi Servis --}}
                <div class="panel-card panel-card-flush ss-dash-enter-child" style="--ss-delay: 120ms; --ss-stagger: 0">
                    <div class="panel-card-head">
                        <div class="section-hd" style="margin-bottom:0">
                            <h2>Informasi Servis</h2>
                            <p>Data pelanggan, kendaraan, dan detail service</p>
                        </div>
                    </div>
                    <div class="panel-card-body">
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Pelanggan <span class="text-red-500">*</span></label>
                                <div class="flex gap-2 items-center">
                                    <select id="pelanggan-select-estimasi" class="flex-1 rounded-xl bg-white text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40">
                                        <option value="">Pilih Pelanggan</option>
                                        @foreach($pelanggans as $p)
                                        <option value="{{ $p->id }}">{{ $p->nama }}{{ $p->no_hp ? " ({$p->no_hp})" : '' }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" @click="showQuickAddModal = true" class="wo-icon-btn" title="Tambah Pelanggan Baru">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Kendaraan <span class="text-red-500">*</span></label>
                                <div class="flex gap-2 items-center">
                                    <select id="kendaraan-select-estimasi" class="flex-1 rounded-xl bg-white text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40">
                                        <option value="">- Pilih Kendaraan -</option>
                                    </select>
                                    <button type="button" @click="openAddKendaraanModal()" :disabled="!pelangganId" class="wo-icon-btn" title="Tambah Kendaraan Baru">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Tanggal Masuk <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input type="date" x-model="tanggalEstimasi" readonly class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 pr-10 text-sm text-gray-700">
                                    <svg class="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Kilometer Kendaraan <span class="text-red-500">*</span></label>
                                <input type="number" x-model.number="formKilometer" min="0" placeholder="Contoh: 15000" class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40">
                                <p class="mt-1 text-xs text-gray-400" x-show="lastKilometer != null" x-text="'Kilometer terakhir: ' + lastKilometer"></p>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Sumber Kedatangan <span class="text-red-500">*</span></label>
                                <select x-model="formSumberKedatangan" class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40">
                                    <option value="">-- Pilih --</option>
                                    @foreach(\App\Models\ServiceOrder::SUMBER_KEDATANGAN as $val => $label)
                                    <option value="{{ $val }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Kategori Service <span class="text-red-500">*</span></label>
                                <select x-model="formKategoriService" class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40">
                                    <option value="">-- Pilih --</option>
                                    @foreach(\App\Models\ServiceOrder::KATEGORI_SERVICE as $val => $label)
                                    <option value="{{ $val }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Keluhan</label>
                            <textarea x-model="keluhan" rows="3" class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40" placeholder="Keluhan / deskripsi service"></textarea>
                        </div>
                    </div>
                    </div>
                </div>

                {{-- Modal Quick Add Pelanggan --}}
                <div x-show="showQuickAddModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="showQuickAddModal = false">
                    <div x-show="showQuickAddModal" x-transition.opacity class="fixed inset-0 bg-black/50" @click="showQuickAddModal = false"></div>
                    <div class="flex min-h-full items-center justify-center p-4">
                        <div x-show="showQuickAddModal" x-transition class="wo-modal-panel relative w-full max-w-lg" @click.stop>
                            <h3 class="text-[20px] font-semibold text-gray-900">Tambah Pelanggan Baru</h3>
                            <p class="mt-1 text-[13px] text-gray-500">Isi data pelanggan, lalu pilih/tambahkan kendaraan dari kolom kendaraan.</p>
                            <div class="mt-5 space-y-4">
                                <div class="rounded-lg border border-gray-100 bg-gray-50/50 p-4">
                                    <p class="mb-3 text-[11px] font-medium uppercase tracking-wide text-gray-500">Data Pelanggan</p>
                                    <div class="space-y-3">
                                        <div>
                                            <label class="mb-1 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Nama *</label>
                                            <input type="text" x-model="quickAdd.nama" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40" placeholder="Nama pelanggan">
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-[11px] font-medium uppercase tracking-wide text-gray-500">No HP</label>
                                            <input type="text" x-model="quickAdd.no_hp" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40" placeholder="08xxxx">
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Alamat</label>
                                            <textarea x-model="quickAdd.alamat" rows="2" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40" placeholder="Alamat"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex gap-3 pt-2">
                                    <button type="button" @click="submitQuickAdd()" :disabled="quickAddLoading" class="btn-primary flex-1">
                                        <span x-show="!quickAddLoading">Simpan & Pilih</span>
                                        <span x-show="quickAddLoading">Menyimpan...</span>
                                    </button>
                                    <button type="button" @click="showQuickAddModal = false" class="btn-secondary">Batal</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Modal Tambah Kendaraan Baru --}}
                <div x-show="showAddKendaraanModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="showAddKendaraanModal = false">
                    <div x-show="showAddKendaraanModal" x-transition.opacity class="fixed inset-0 bg-black/50" @click="showAddKendaraanModal = false"></div>
                    <div class="flex min-h-full items-center justify-center p-4">
                        <div x-show="showAddKendaraanModal" x-transition class="wo-modal-panel relative w-full max-w-md" @click.stop>
                            <h3 class="text-[20px] font-semibold text-gray-900">Tambah Kendaraan Baru</h3>
                            <p class="mt-1 text-[13px] text-gray-500">Isi data kendaraan untuk pelanggan yang dipilih</p>
                            <div class="mt-5 space-y-4">
                                <div class="space-y-3">
                                    <div>
                                        <label class="mb-1 block text-[11px] font-medium uppercase tracking-wide text-gray-500">No Polisi *</label>
                                        <input type="text" x-model="addKendaraan.nomor_polisi" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40" placeholder="B 1234 XYZ">
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="mb-1 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Merk *</label>
                                            <input type="text" x-model="addKendaraan.merk" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40" placeholder="Honda">
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Tipe</label>
                                            <input type="text" x-model="addKendaraan.tipe" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40" placeholder="Beat">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Tahun</label>
                                        <input type="number" x-model="addKendaraan.tahun" min="1990" max="2030" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40" placeholder="2024">
                                    </div>
                                </div>
                                <div class="flex gap-3 pt-2">
                                    <button type="button" @click="submitAddKendaraan()" :disabled="addKendaraanLoading" class="btn-primary flex-1">
                                        <span x-show="!addKendaraanLoading">Simpan & Pilih</span>
                                        <span x-show="addKendaraanLoading">Menyimpan...</span>
                                    </button>
                                    <button type="button" @click="showAddKendaraanModal = false" class="btn-secondary">Batal</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Jasa Servis --}}
                <div class="panel-card ss-dash-enter-child" style="--ss-delay: 120ms; --ss-stagger: 1">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="section-hd" style="margin-bottom:0">
                            <h2>Jasa Servis</h2>
                            <p>Tambah jasa servis yang dikerjakan</p>
                        </div>
                        <span class="wo-badge" x-text="formJasa.length + ' jasa'"></span>
                    </div>
                    <div class="mt-4 flex gap-4">
                        <div class="flex-1">
                            <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Jasa</label>
                            <select id="jasa-select-estimasi" class="w-full rounded-xl bg-white text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40"></select>
                        </div>
                        <div class="flex items-end">
                            <button type="button" @click="addJasa()" class="btn-primary">+ Tambah Jasa</button>
                        </div>
                    </div>
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead><tr><th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No.</th><th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Nama Jasa</th><th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-right">Biaya</th><th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 w-16"></th></tr></thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="(j, idx) in formJasa" :key="j.id + '-' + idx">
                                    <tr class="transition hover:bg-[#fcfafb]">
                                        <td class="px-5 py-4 text-gray-500" x-text="idx + 1"></td>
                                        <td class="px-5 py-4 font-medium text-gray-900" x-text="j.nama"></td>
                                        <td class="px-5 py-4 text-right">
                                            <input type="number" x-model.number="j.biaya" min="0" class="w-32 rounded-lg border border-gray-200 px-3 py-1.5 text-right text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40">
                                        </td>
                                        <td class="px-5 py-4">
                                            <button type="button" @click="formJasa.splice(idx, 1)" class="rounded-lg p-1.5 text-red-400 transition hover:bg-red-50 hover:text-red-600">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                                <template x-if="formJasa.length === 0">
                                    <tr><td colspan="4" class="empty-cell">Belum ada jasa ditambahkan.</td></tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Sparepart --}}
                <div class="panel-card ss-dash-enter-child" style="--ss-delay: 120ms; --ss-stagger: 2">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="section-hd" style="margin-bottom:0">
                            <h2>Sparepart</h2>
                            <p>Tambah produk sparepart untuk estimasi (tanpa batas stok)</p>
                        </div>
                        <span class="wo-badge" x-text="formSparepart.length + ' item'"></span>
                    </div>
                    <div class="mt-4 flex gap-4 flex-wrap">
                        <div class="flex-1 min-w-[200px]">
                            <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Produk</label>
                            <select x-ref="sparepartSelect" id="product-select-estimasi" class="w-full rounded-xl bg-white text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40"></select>
                        </div>
                        <div class="w-24">
                            <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Qty</label>
                            <input type="number" x-model.number="addSparepartQty" min="1" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-center text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40">
                        </div>
                        <div class="flex items-end">
                            <button type="button" @click="addSparepart()" class="btn-primary">+ Tambah Sparepart</button>
                        </div>
                    </div>
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead><tr><th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No.</th><th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Kode</th><th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Nama</th><th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-center">Qty</th><th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-right">Harga</th><th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-right">Subtotal</th><th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 w-16"></th></tr></thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="(s, idx) in formSparepart" :key="s.product_id + '-' + idx">
                                    <tr class="transition hover:bg-[#fcfafb]">
                                        <td class="px-5 py-4 text-gray-500" x-text="idx + 1"></td>
                                        <td class="px-5 py-4 font-medium text-gray-900" x-text="s.kode_produk"></td>
                                        <td class="px-5 py-4 text-gray-700" x-text="s.nama_produk"></td>
                                        <td class="px-5 py-4 text-center">
                                            <input type="number" x-model.number="s.qty" min="1" @change="adjustSparepartQty(idx)" class="w-16 rounded-lg border border-gray-200 px-2 py-1.5 text-center text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40">
                                        </td>
                                        <td class="px-5 py-4 text-right">
                                            <input type="number" x-model.number="s.harga" min="0" @change="s.subtotal = s.qty * s.harga" class="w-28 rounded-lg border border-gray-200 px-2 py-1.5 text-right text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40">
                                        </td>
                                        <td class="px-5 py-4 text-right font-medium text-gray-900" x-text="formatRupiah((s.qty * s.harga))"></td>
                                        <td class="px-5 py-4">
                                            <button type="button" @click="removeSparepart(idx)" class="rounded-lg p-1.5 text-red-400 transition hover:bg-red-50 hover:text-red-600">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                                <template x-if="formSparepart.length === 0">
                                    <tr><td colspan="7" class="empty-cell">Belum ada sparepart ditambahkan.</td></tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Right: Ringkasan & Button --}}
            <div class="space-y-5 ss-dash-enter-child" style="--ss-delay: 120ms; --ss-stagger: 3">
                <div class="panel-card summary-card">
                    <div class="section-hd">
                        <h2>Ringkasan</h2>
                        <p>Estimasi biaya work order</p>
                    </div>
                    <div class="mt-2">
                        <div class="summary-row">
                            <span>Total Jasa</span>
                            <span x-text="formatRupiah(totalJasa)"></span>
                        </div>
                        <div class="summary-row">
                            <span>Total Sparepart</span>
                            <span x-text="formatRupiah(totalSparepart)"></span>
                        </div>
                        <div class="summary-total">
                            <span>Estimasi Total</span>
                            <span x-text="formatRupiah(totalJasa + totalSparepart)"></span>
                        </div>
                    </div>
                    <div class="mt-6">
                        <button type="button" @click="hitungEstimasi()" :disabled="submitting || !canHitungEstimasi" class="btn-primary flex h-11 w-full disabled:cursor-not-allowed disabled:opacity-70">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            <span x-text="submitting ? 'Memproses...' : 'Hitung estimasi'"></span>
                        </button>
                    </div>
                </div>

                <a href="{{ route('speedshop.wip') }}" class="btn-secondary flex h-10 w-full">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Kembali ke Daftar
                </a>
            </div>
        </div>
    </div>

    {{-- Modal Hasil Estimasi --}}
    <div x-show="showEstimasiModal" x-cloak class="no-print fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="showEstimasiModal = false">
        <div x-show="showEstimasiModal" x-transition.opacity class="fixed inset-0 bg-black/50" @click="showEstimasiModal = false"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div x-show="showEstimasiModal" x-transition class="wo-modal-panel relative flex w-full max-w-3xl max-h-[92vh] flex-col !p-0" @click.stop>
                <div class="flex items-start justify-between border-b border-gray-100 px-5 py-4 sm:px-6">
                    <div>
                        <h3 class="text-[20px] font-semibold text-gray-900">Hasil Estimasi</h3>
                        <p class="mt-1 text-[13px] text-gray-500">Kirim ke WhatsApp pelanggan atau cetak estimasi</p>
                    </div>
                    <button type="button" @click="showEstimasiModal = false" class="wo-icon-btn" aria-label="Tutup">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-5 py-5 sm:px-6">
                    <div id="estimasi-print-area" class="panel-card !shadow-none print:border-0 print:shadow-none print:p-8">
                        <div class="border-b-2 border-red-500 pb-4">
                            <h1 class="text-2xl font-bold text-red-600">ESTIMASI</h1>
                            <p class="text-gray-700">77racing Bengkel</p>
                        </div>
                        <div class="mt-4 flex justify-end text-right text-sm text-gray-600">
                            <div>
                                <p>Tanggal: <span x-text="formatDate(tanggalEstimasi)"></span></p>
                            </div>
                        </div>
                        <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-gray-500">Pelanggan</p>
                                <p class="mt-1 font-medium text-gray-900" x-text="selectedPelanggan?.nama || '-'"></p>
                                <p class="text-sm text-gray-600" x-text="selectedPelanggan?.no_hp || '-'"></p>
                                <p class="text-sm text-gray-600" x-text="selectedPelanggan?.alamat || '-'"></p>
                                <p class="text-sm text-gray-600" x-text="selectedPelanggan?.kota || '-'"></p>
                            </div>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-gray-500">Kendaraan</p>
                                <p class="mt-1 font-bold text-gray-900" x-text="selectedKendaraan?.nomor_polisi || '-'"></p>
                                <p class="text-sm text-gray-600" x-text="(selectedKendaraan ? selectedKendaraan.merk + ' ' + (selectedKendaraan.tipe || '') : '-').trim()"></p>
                                <p class="text-sm text-gray-600" x-text="selectedKendaraan?.tahun ? 'Tahun ' + selectedKendaraan.tahun : '-'"></p>
                            </div>
                        </div>
                        <div class="mt-8">
                            <p class="mb-2 text-xs font-bold uppercase tracking-wider text-gray-500">Jasa Servis</p>
                            <table class="w-full text-left text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200">
                                        <th class="pb-2 font-semibold text-gray-600">Nama Jasa</th>
                                        <th class="pb-2 text-right font-semibold text-gray-600">Biaya</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(j, idx) in formJasa" :key="j.id + '-print-' + idx">
                                        <tr class="border-b border-gray-100">
                                            <td class="py-2 text-gray-900" x-text="j.nama"></td>
                                            <td class="py-2 text-right text-gray-700" x-text="formatRupiah(j.biaya)"></td>
                                        </tr>
                                    </template>
                                    <template x-if="formJasa.length === 0">
                                        <tr><td colspan="2" class="py-4 text-center text-gray-400">-</td></tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-6">
                            <p class="mb-2 text-xs font-bold uppercase tracking-wider text-gray-500">Sparepart</p>
                            <table class="w-full text-left text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200">
                                        <th class="pb-2 font-semibold text-gray-600">Nama</th>
                                        <th class="pb-2 text-center font-semibold text-gray-600">Qty</th>
                                        <th class="pb-2 text-right font-semibold text-gray-600">Harga</th>
                                        <th class="pb-2 text-right font-semibold text-gray-600">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(s, idx) in formSparepart" :key="s.product_id + '-print-' + idx">
                                        <tr class="border-b border-gray-100">
                                            <td class="py-2 text-gray-900" x-text="s.nama_produk"></td>
                                            <td class="py-2 text-center text-gray-700" x-text="s.qty"></td>
                                            <td class="py-2 text-right text-gray-700" x-text="formatRupiah(s.harga)"></td>
                                            <td class="py-2 text-right text-gray-700" x-text="formatRupiah(s.qty * s.harga)"></td>
                                        </tr>
                                    </template>
                                    <template x-if="formSparepart.length === 0">
                                        <tr><td colspan="4" class="py-4 text-center text-gray-400">-</td></tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-8 flex justify-end">
                            <div class="w-64 space-y-2 text-right">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Total Jasa</span>
                                    <span x-text="formatRupiah(totalJasa)"></span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Total Sparepart</span>
                                    <span x-text="formatRupiah(totalSparepart)"></span>
                                </div>
                                <div class="border-t border-gray-200 pt-3">
                                    <div class="flex justify-between font-bold text-red-600">
                                        <span>Grand Total</span>
                                        <span x-text="formatRupiah(totalJasa + totalSparepart)"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p class="mt-12 text-center text-sm text-gray-500">Terima kasih telah mempercayakan kendaraan Anda kepada kami.</p>
                        <p class="mt-2 text-center text-xs text-gray-400">77racing Bengkel - {{ date('Y') }}</p>
                    </div>
                </div>

                <div class="flex flex-col gap-3 border-t border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-end sm:px-6">
                    <button type="button" @click="showEstimasiModal = false" class="btn-secondary inline-flex h-10 items-center justify-center px-5">Tutup</button>
                    <button type="button" @click="sendEstimasiWhatsApp()" class="btn-whatsapp inline-flex h-10 items-center gap-2 px-5">
                        <svg class="h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.883 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        Kirim WhatsApp
                    </button>
                    <button type="button" @click="printEstimasi()" class="btn-primary inline-flex h-10 items-center gap-2 px-5">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Cetak
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

@push('scripts')
<script>
function estimasiService() {
    const jasaList = @json($jasaServis->map(fn($j) => ['id' => $j->id, 'nama' => $j->nama, 'biaya' => (int)$j->biaya]));
    const products = @json($productsForEstimasi);
    const initialPelanggans = @json($initialPelanggans);

    return {
        pelangganId: '',
        kendaraanId: '',
        kendaraans: [],
        pelangganSelect: null,
        kendaraanSelect: null,
        jasaSelect: null,
        formJasa: [],
        addSparepartQty: 1,
        formSparepart: [],
        sparepartSelect: null,
        showEstimasiModal: false,
        submitting: false,
        tanggalEstimasi: '{{ date('Y-m-d') }}',
        formKilometer: '',
        formSumberKedatangan: '',
        formKategoriService: '',
        keluhan: '',

        get totalJasa() { return this.formJasa.reduce((s, j) => s + (Number(j.biaya) || 0), 0); },
        get totalSparepart() { return this.formSparepart.reduce((s, p) => s + (p.qty * p.harga), 0); },
        get selectedPelanggan() { return this.pelanggansList.find(p => String(p.id) === String(this.pelangganId)) || null; },
        get selectedKendaraan() {
            const flat = this.allKendaraansFlat.find(k => String(k.id) === String(this.kendaraanId));
            if (flat) return flat;
            return this.kendaraans.find(k => String(k.id) === String(this.kendaraanId)) || null;
        },
        get lastKilometer() {
            if (!this.kendaraanId) return null;
            const k = this.kendaraans.find(x => String(x.id) === String(this.kendaraanId));
            return k && k.last_kilometer != null ? k.last_kilometer : null;
        },
        get canHitungEstimasi() {
            return Boolean(
                this.pelangganId &&
                this.kendaraanId &&
                String(this.formKilometer ?? '').trim() !== '' &&
                this.formSumberKedatangan &&
                this.formKategoriService
            );
        },

        formatRupiah(n) { return 'Rp ' + Number(n || 0).toLocaleString('id-ID'); },
        formatDate(d) {
            if (!d) return '-';
            const dt = new Date(d);
            return dt.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
        },

        showQuickAddModal: false,
        quickAddLoading: false,
        quickAdd: { nama: '', no_hp: '', alamat: '' },
        showAddKendaraanModal: false,
        addKendaraanLoading: false,
        addKendaraan: { nomor_polisi: '', merk: '', tipe: '', tahun: '' },
        pelanggansList: initialPelanggans,
        kendaraanByPelanggan: Object.fromEntries(initialPelanggans.map(p => [String(p.id), (p.kendaraans || []).map(k => ({ ...k, pelanggan_id: k.pelanggan_id ?? p.id }))])),
        allKendaraansFlat: initialPelanggans.flatMap(p => (p.kendaraans || []).map(k => ({ ...k, pelanggan_id: k.pelanggan_id ?? p.id }))),

        loadKendaraan() {
            if (this.pelangganId) {
                this.kendaraans = this.kendaraanByPelanggan[this.pelangganId] || [];
                const currentInList = this.kendaraans.some(k => String(k.id) === String(this.kendaraanId));
                if (this.kendaraanId && !currentInList) this.kendaraanId = '';
            } else {
                this.kendaraans = this.allKendaraansFlat;
            }
            this.syncKendaraanSelect();
        },

        syncKendaraanSelect() {
            if (!this.kendaraanSelect) return;
            this.kendaraanSelect.clear(true);
            this.kendaraanSelect.clearOptions();
            this.kendaraanSelect.addOption({ id: '', label: '- Pilih Kendaraan -' });
            this.kendaraans.forEach(k => {
                const label = k.nomor_polisi + ' - ' + k.merk + ' ' + (k.tipe || '');
                this.kendaraanSelect.addOption({ id: String(k.id), label, nomor_polisi: k.nomor_polisi, merk: k.merk, tipe: k.tipe || '' });
            });
            this.kendaraanSelect.setValue(this.kendaraanId || '', true);
            this.kendaraanSelect.refreshOptions(false);
        },

        onKendaraanChange() {
            if (!this.kendaraanId) return;
            const k = this.kendaraans.find(x => String(x.id) === String(this.kendaraanId));
            if (k && k.pelanggan_id && String(this.pelangganId) !== String(k.pelanggan_id)) {
                this.pelangganId = String(k.pelanggan_id);
                if (this.pelangganSelect) this.pelangganSelect.setValue(String(k.pelanggan_id));
                this.loadKendaraan();
                this.kendaraanId = String(k.id);
            }
        },

        openAddKendaraanModal() {
            if (!this.pelangganId) {
                Swal.fire('Perhatian', 'Pilih pelanggan terlebih dahulu.', 'warning');
                return;
            }
            this.addKendaraan = { nomor_polisi: '', merk: '', tipe: '', tahun: '' };
            this.showAddKendaraanModal = true;
        },

        async submitAddKendaraan() {
            if (!this.addKendaraan.nomor_polisi?.trim()) {
                Swal.fire('Perhatian', 'No Polisi wajib diisi.', 'warning');
                return;
            }
            if (!this.addKendaraan.merk?.trim()) {
                Swal.fire('Perhatian', 'Merk kendaraan wajib diisi.', 'warning');
                return;
            }
            this.addKendaraanLoading = true;
            try {
                const res = await fetch('{{ route('speedshop.kendaraans.quick-add') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({
                        pelanggan_id: this.pelangganId,
                        nomor_polisi: this.addKendaraan.nomor_polisi,
                        merk: this.addKendaraan.merk,
                        tipe: this.addKendaraan.tipe || null,
                        tahun: this.addKendaraan.tahun ? parseInt(this.addKendaraan.tahun) : null,
                    })
                });
                const data = await res.json();
                if (!data.success || !data.kendaraan) throw new Error('Gagal menyimpan');
                const k = data.kendaraan;
                const kendList = this.kendaraanByPelanggan[this.pelangganId] || [];
                const newK = { id: k.id, pelanggan_id: this.pelangganId, nomor_polisi: k.nomor_polisi, merk: k.merk, tipe: k.tipe || '', tahun: k.tahun, last_kilometer: null };
                this.kendaraanByPelanggan[this.pelangganId] = [...kendList, newK];
                this.allKendaraansFlat.push(newK);
                this.kendaraans = this.kendaraanByPelanggan[this.pelangganId] || [];
                this.kendaraanId = String(k.id);
                if (this.kendaraanSelect) this.kendaraanSelect.setValue(this.kendaraanId);
                this.showAddKendaraanModal = false;
                this.addKendaraan = { nomor_polisi: '', merk: '', tipe: '', tahun: '' };
                Swal.fire('Berhasil', 'Kendaraan berhasil ditambahkan.', 'success');
            } catch (e) {
                Swal.fire('Gagal', e.message || 'Gagal menyimpan kendaraan.', 'error');
            }
            this.addKendaraanLoading = false;
        },

        async submitQuickAdd() {
            if (!this.quickAdd.nama?.trim()) {
                Swal.fire('Perhatian', 'Nama pelanggan wajib diisi.', 'warning');
                return;
            }
            this.quickAddLoading = true;
            try {
                const res = await fetch('{{ route('speedshop.pelanggans.store') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                    body: JSON.stringify({
                        nama: this.quickAdd.nama,
                        no_hp: this.quickAdd.no_hp || null,
                        alamat: this.quickAdd.alamat || null,
                    })
                });
                const data = await res.json();
                if (!data.success || !data.pelanggan) throw new Error('Gagal menyimpan');
                const p = data.pelanggan;
                const kendList = (p.kendaraans || []).map(k => ({
                    id: k.id,
                    pelanggan_id: p.id,
                    nomor_polisi: k.nomor_polisi,
                    merk: k.merk,
                    tipe: k.tipe || '',
                    tahun: k.tahun,
                    last_kilometer: null,
                }));
                this.kendaraanByPelanggan[String(p.id)] = kendList;
                kendList.forEach(k => this.allKendaraansFlat.push(k));
                this.pelanggansList.push({
                    id: p.id,
                    nama: p.nama,
                    no_hp: p.no_hp || '',
                    alamat: p.alamat || '',
                    kota: (p.kota && p.kota.nama) ? p.kota.nama : '',
                    kendaraans: kendList,
                });
                if (this.pelangganSelect) {
                    this.pelangganSelect.addOption({ id: String(p.id), nama: p.nama, no_hp: p.no_hp || '', label: p.nama + (p.no_hp ? ' (' + p.no_hp + ')' : '') });
                    this.pelangganSelect.setValue(String(p.id));
                }
                this.pelangganId = String(p.id);
                this.loadKendaraan();
                if (kendList.length) {
                    this.kendaraanId = String(kendList[0].id);
                    if (this.kendaraanSelect) this.kendaraanSelect.setValue(this.kendaraanId);
                }
                this.showQuickAddModal = false;
                this.quickAdd = { nama: '', no_hp: '', alamat: '' };
                Swal.fire('Berhasil', 'Pelanggan berhasil ditambahkan.', 'success');
            } catch (e) {
                Swal.fire('Gagal', e.message || 'Gagal menyimpan data.', 'error');
            }
            this.quickAddLoading = false;
        },

        addJasa() {
            const sel = this.jasaSelect;
            const id = sel ? sel.getValue() : '';
            if (!id) { Swal.fire('Perhatian', 'Pilih jasa terlebih dahulu.', 'warning'); return; }
            const j = jasaList.find(x => x.id == id);
            if (!j || this.formJasa.some(x => x.id == id)) {
                if (this.formJasa.some(x => x.id == id)) Swal.fire('Perhatian', 'Jasa sudah ditambahkan.', 'warning');
                return;
            }
            this.formJasa.push({ id: j.id, nama: j.nama, biaya: j.biaya });
            if (sel) sel.clear();
        },

        addSparepart() {
            const sel = this.sparepartSelect;
            const id = sel ? sel.getValue() : '';
            if (!sel || !id) {
                Swal.fire('Perhatian', 'Pilih produk terlebih dahulu.', 'warning');
                return;
            }
            const product = products.find(p => p.id == id);
            if (!product) return;
            const qty = Math.max(1, this.addSparepartQty || 1);
            const sudahDitambah = this.formSparepart.find(p => p.product_id == product.id);
            const harga = Number(product.harga_jual_cust || product.harga_eceran_terendah || product.harga_jual || product.harga_jual_speedshop || product.hpp || 0);
            if (sudahDitambah) {
                sudahDitambah.qty += qty;
                sudahDitambah.subtotal = sudahDitambah.qty * sudahDitambah.harga;
            } else {
                this.formSparepart.push({
                    product_id: product.id,
                    kode_produk: product.kode_produk,
                    nama_produk: product.nama_produk,
                    qty: qty,
                    harga: harga,
                    subtotal: qty * harga
                });
            }
            this.addSparepartQty = 1;
            if (sel) sel.clear();
        },

        adjustSparepartQty(idx) {
            const row = this.formSparepart[idx];
            if (!row) return;
            row.qty = Math.max(1, Number(row.qty) || 1);
            row.subtotal = (Number(row.qty) || 0) * (Number(row.harga) || 0);
        },

        removeSparepart(idx) {
            this.formSparepart.splice(idx, 1);
        },

        hitungEstimasi() {
            if (this.submitting) return;
            const pelVal = this.pelangganSelect ? this.pelangganSelect.getValue() : '';
            const kendVal = this.kendaraanId || '';
            if (!pelVal) {
                Swal.fire('Perhatian', 'Pilih pelanggan terlebih dahulu.', 'warning');
                return;
            }
            if (!kendVal) {
                Swal.fire('Perhatian', 'Pilih kendaraan terlebih dahulu.', 'warning');
                return;
            }
            if (String(this.formKilometer ?? '').trim() === '') {
                Swal.fire('Perhatian', 'Kilometer kendaraan wajib diisi.', 'warning');
                return;
            }
            if (!this.formSumberKedatangan) {
                Swal.fire('Perhatian', 'Sumber kedatangan wajib dipilih.', 'warning');
                return;
            }
            if (!this.formKategoriService) {
                Swal.fire('Perhatian', 'Kategori service wajib dipilih.', 'warning');
                return;
            }
            if (this.formJasa.length === 0 && this.formSparepart.length === 0) {
                Swal.fire('Perhatian', 'Tambahkan minimal 1 jasa atau sparepart.', 'warning');
                return;
            }
            this.pelangganId = pelVal;
            this.submitting = true;
            this.showEstimasiModal = true;
            this.$nextTick(() => {
                this.submitting = false;
            });
        },

        normalizeWhatsAppPhone(raw) {
            let phone = String(raw || '').replace(/\D/g, '');
            if (!phone) return '';
            if (phone.startsWith('0')) phone = '62' + phone.slice(1);
            else if (!phone.startsWith('62')) phone = '62' + phone;
            return phone;
        },

        buildEstimasiWhatsAppText() {
            const lines = [
                '*ESTIMASI SERVIS*',
                '77racing Bengkel',
                '',
                `Tanggal: ${this.formatDate(this.tanggalEstimasi)}`,
                '',
                `Pelanggan: ${this.selectedPelanggan?.nama || '-'}`,
                `Kendaraan: ${this.selectedKendaraan?.nomor_polisi || '-'} (${(this.selectedKendaraan ? this.selectedKendaraan.merk + ' ' + (this.selectedKendaraan.tipe || '') : '-').trim()})`,
                '',
            ];

            if (this.formJasa.length) {
                lines.push('*Jasa Servis*');
                this.formJasa.forEach(j => lines.push(`- ${j.nama}: ${this.formatRupiah(j.biaya)}`));
                lines.push('');
            }

            if (this.formSparepart.length) {
                lines.push('*Sparepart*');
                this.formSparepart.forEach(s => lines.push(`- ${s.nama_produk} (${s.qty}x): ${this.formatRupiah(s.qty * s.harga)}`));
                lines.push('');
            }

            lines.push(`Total Jasa: ${this.formatRupiah(this.totalJasa)}`);
            lines.push(`Total Sparepart: ${this.formatRupiah(this.totalSparepart)}`);
            lines.push(`*Grand Total: ${this.formatRupiah(this.totalJasa + this.totalSparepart)}*`);
            lines.push('');
            lines.push('Estimasi ini bersifat perkiraan biaya dan dapat berubah sesuai kondisi kendaraan.');

            return lines.join('\n');
        },

        sendEstimasiWhatsApp() {
            const phone = this.normalizeWhatsAppPhone(this.selectedPelanggan?.no_hp);
            if (!phone) {
                Swal.fire('Perhatian', 'Nomor HP pelanggan tidak tersedia.', 'warning');
                return;
            }
            const url = `https://wa.me/${phone}?text=${encodeURIComponent(this.buildEstimasiWhatsAppText())}`;
            window.open(url, '_blank', 'noopener,noreferrer');
        },

        escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        },

        buildEstimasiPrintDocumentHtml() {
            const e = (value) => this.escapeHtml(value);
            const pel = this.selectedPelanggan;
            const kend = this.selectedKendaraan;
            const kendaraanLabel = kend
                ? `${kend.merk || ''} ${kend.tipe || ''}`.trim()
                : '-';

            const jasaRows = this.formJasa.length
                ? this.formJasa.map(j => `<tr><td>${e(j.nama)}</td><td class="right">${e(this.formatRupiah(j.biaya))}</td></tr>`).join('')
                : '<tr><td colspan="2" class="muted center">-</td></tr>';

            const sparepartRows = this.formSparepart.length
                ? this.formSparepart.map(s => `<tr><td>${e(s.nama_produk)}</td><td class="center">${e(s.qty)}</td><td class="right">${e(this.formatRupiah(s.harga))}</td><td class="right">${e(this.formatRupiah(s.qty * s.harga))}</td></tr>`).join('')
                : '<tr><td colspan="4" class="muted center">-</td></tr>';

            return `<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Estimasi Servis</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: ui-sans-serif, system-ui, -apple-system, sans-serif; color: #111827; margin: 0; padding: 32px; }
        .header { border-bottom: 2px solid #ef4444; padding-bottom: 16px; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 28px; color: #dc2626; }
        .header p { margin: 6px 0 0; color: #374151; }
        .meta { text-align: right; font-size: 14px; color: #4b5563; margin-bottom: 24px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 28px; }
        .label { font-size: 11px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: #6b7280; margin: 0 0 6px; }
        .value { margin: 0 0 4px; font-size: 14px; color: #374151; }
        .value-strong { margin: 0 0 4px; font-size: 15px; font-weight: 700; color: #111827; }
        .section { margin-bottom: 24px; }
        .section-title { font-size: 11px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: #6b7280; margin: 0 0 8px; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td { padding: 8px 4px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        th { font-size: 13px; font-weight: 600; color: #4b5563; }
        .right { text-align: right; }
        .center { text-align: center; }
        .muted { color: #9ca3af; }
        .totals { margin-left: auto; width: 260px; margin-top: 28px; }
        .totals-row { display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 8px; color: #374151; }
        .totals-grand { border-top: 1px solid #e5e7eb; padding-top: 12px; margin-top: 8px; display: flex; justify-content: space-between; font-size: 16px; font-weight: 700; color: #dc2626; }
        .footer { margin-top: 48px; text-align: center; font-size: 13px; color: #6b7280; }
        .footer small { display: block; margin-top: 8px; font-size: 11px; color: #9ca3af; }
        @media print { body { padding: 16px; } }
    </style>
</head>
<body>
    <div class="header">
        <h1>ESTIMASI</h1>
        <p>77racing Bengkel</p>
    </div>
    <div class="meta">Tanggal: ${e(this.formatDate(this.tanggalEstimasi))}</div>
    <div class="grid">
        <div>
            <p class="label">Pelanggan</p>
            <p class="value-strong">${e(pel?.nama || '-')}</p>
            <p class="value">${e(pel?.no_hp || '-')}</p>
            <p class="value">${e(pel?.alamat || '-')}</p>
            <p class="value">${e(pel?.kota || '-')}</p>
        </div>
        <div>
            <p class="label">Kendaraan</p>
            <p class="value-strong">${e(kend?.nomor_polisi || '-')}</p>
            <p class="value">${e(kendaraanLabel)}</p>
            <p class="value">${kend?.tahun ? e('Tahun ' + kend.tahun) : '-'}</p>
        </div>
    </div>
    <div class="section">
        <p class="section-title">Jasa Servis</p>
        <table>
            <thead><tr><th>Nama Jasa</th><th class="right">Biaya</th></tr></thead>
            <tbody>${jasaRows}</tbody>
        </table>
    </div>
    <div class="section">
        <p class="section-title">Sparepart</p>
        <table>
            <thead><tr><th>Nama</th><th class="center">Qty</th><th class="right">Harga</th><th class="right">Subtotal</th></tr></thead>
            <tbody>${sparepartRows}</tbody>
        </table>
    </div>
    <div class="totals">
        <div class="totals-row"><span>Total Jasa</span><span>${e(this.formatRupiah(this.totalJasa))}</span></div>
        <div class="totals-row"><span>Total Sparepart</span><span>${e(this.formatRupiah(this.totalSparepart))}</span></div>
        <div class="totals-grand"><span>Grand Total</span><span>${e(this.formatRupiah(this.totalJasa + this.totalSparepart))}</span></div>
    </div>
    <div class="footer">
        Terima kasih telah mempercayakan kendaraan Anda kepada kami.
        <small>77racing Bengkel - {{ date('Y') }}</small>
    </div>
</body>
</html>`;
        },

        printEstimasi() {
            let iframe = document.getElementById('estimasi-print-frame');
            if (!iframe) {
                iframe = document.createElement('iframe');
                iframe.id = 'estimasi-print-frame';
                iframe.setAttribute('aria-hidden', 'true');
                iframe.style.cssText = 'position:fixed;width:0;height:0;border:0;clip:rect(0,0,0,0);overflow:hidden;';
                document.body.appendChild(iframe);
            }

            const frameWindow = iframe.contentWindow;
            if (!frameWindow) {
                Swal.fire('Gagal', 'Tidak dapat membuka dialog cetak.', 'error');
                return;
            }

            frameWindow.document.open();
            frameWindow.document.write(this.buildEstimasiPrintDocumentHtml());
            frameWindow.document.close();

            const runPrint = () => {
                frameWindow.focus();
                frameWindow.print();
            };

            if (frameWindow.document.readyState === 'complete') {
                runPrint();
            } else {
                iframe.onload = runPrint;
                setTimeout(runPrint, 300);
            }
        },

        init() {
            this.loadKendaraan();
            this.$nextTick(() => {
                const self = this;
                if (typeof TomSelect !== 'undefined') {
                    const pelEl = document.getElementById('pelanggan-select-estimasi');
                    if (pelEl) {
                        this.pelangganSelect = new TomSelect(pelEl, {
                            placeholder: 'Pilih Pelanggan',
                            allowEmptyOption: true,
                            searchField: ['nama', 'no_hp'],
                            valueField: 'id',
                            labelField: 'label',
                            options: [{ id: '', label: 'Pilih Pelanggan' }, ...self.pelanggansList.map(p => ({
                                id: String(p.id),
                                nama: p.nama,
                                no_hp: p.no_hp || '',
                                label: p.nama + (p.no_hp ? ' (' + p.no_hp + ')' : ''),
                            }))],
                            onChange(val) {
                                self.pelangganId = val || '';
                                self.kendaraanId = '';
                                self.loadKendaraan();
                            },
                            render: {
                                option: (data, escape) => {
                                    if (!data.id) return `<div class="text-gray-400">Pilih Pelanggan</div>`;
                                    return `<div><span class="font-medium">${escape(data.nama)}</span>${data.no_hp ? ' <span class="text-gray-400 text-xs">' + escape(data.no_hp) + '</span>' : ''}</div>`;
                                },
                                item: (data, escape) => `<div>${escape(data.label)}</div>`,
                            },
                        });
                    }
                    const kendEl = document.getElementById('kendaraan-select-estimasi');
                    if (kendEl) {
                        this.kendaraanSelect = new TomSelect(kendEl, {
                            placeholder: '- Pilih Kendaraan -',
                            allowEmptyOption: true,
                            searchField: ['label', 'nomor_polisi', 'merk'],
                            valueField: 'id',
                            labelField: 'label',
                            options: [],
                            onChange(val) {
                                self.kendaraanId = val || '';
                                self.onKendaraanChange();
                            },
                            render: {
                                option: (data, escape) => {
                                    if (!data.id) return `<div class="text-gray-400">- Pilih Kendaraan -</div>`;
                                    return `<div><span class="font-medium">${escape(data.nomor_polisi)}</span> <span class="text-gray-500">${escape(data.merk)} ${escape(data.tipe || '')}</span></div>`;
                                },
                                item: (data, escape) => `<div>${escape(data.label)}</div>`,
                            },
                        });
                        this.syncKendaraanSelect();
                    }
                    const jasaEl = document.getElementById('jasa-select-estimasi');
                    if (jasaEl) {
                        this.jasaSelect = new TomSelect(jasaEl, {
                            valueField: 'id',
                            labelField: 'label',
                            searchField: ['nama'],
                            placeholder: 'Pilih jasa servis...',
                            options: jasaList.map(j => ({
                                id: j.id,
                                nama: j.nama,
                                biaya: j.biaya,
                                label: `${j.nama} - Rp ${Number(j.biaya).toLocaleString('id-ID')}`,
                            })),
                            render: {
                                option: (data, escape) => `<div><span class="font-medium">${escape(data.nama)}</span> <span class="text-gray-400 text-xs">Rp ${Number(data.biaya).toLocaleString('id-ID')}</span></div>`,
                                item: (data, escape) => `<div>${escape(data.label)}</div>`,
                            },
                        });
                    }
                }
                const el = document.getElementById('product-select-estimasi');
                if (el && typeof TomSelect !== 'undefined') {
                    this.sparepartSelect = new TomSelect(el, {
                        valueField: 'id',
                        labelField: 'label',
                        searchField: ['kode_produk', 'nama_produk', 'catalog_label'],
                        optgroupField: 'catalog_kind',
                        optgroupLabelField: 'label',
                        optgroupValueField: 'value',
                        lockOptgroupOrder: true,
                        placeholder: 'Pilih produk...',
                        optgroups: @json(\App\Support\ProductMasterCatalog::pickerOptgroups()),
                        options: products.map(p => {
                            const kind = p.catalog_kind || (p.is_part_luar ? 'part_luar' : 'mo');
                            const badgeText = { mo: 'MO', speedshop_supplier: 'Speedshop', part_luar: 'Part Luar', merchandise: 'Merch', consumable: 'Consumable' }[kind] || kind;
                            return {
                                id: p.id,
                                kode_produk: p.kode_produk,
                                nama_produk: p.nama_produk,
                                harga_jual_speedshop: p.harga_jual_speedshop,
                                harga_jual: p.harga_jual,
                                hpp: p.hpp,
                                jumlah: Number(p.jumlah) || 0,
                                catalog_kind: kind,
                                catalog_label: p.catalog_label || badgeText,
                                label: `[${p.kode_produk}] ${p.nama_produk} · ${badgeText}`,
                            };
                        }),
                        render: {
                            optgroup_header: (data, escape) => `<div class="px-2 py-1.5 text-[10px] font-semibold uppercase tracking-wide text-gray-500 bg-gray-50 border-b border-gray-100">${escape(data.label)}</div>`,
                            option: (data, escape) => {
                                const kind = data.catalog_kind || 'mo';
                                const badgeText = { mo: 'MO', speedshop_supplier: 'Speedshop', part_luar: 'Part Luar', merchandise: 'Merch', consumable: 'Consumable' }[kind] || kind;
                                const badgeCls = { mo: 'bg-indigo-100 text-indigo-800', speedshop_supplier: 'bg-emerald-100 text-emerald-800', part_luar: 'bg-amber-100 text-amber-800', merchandise: 'bg-pink-100 text-pink-800', consumable: 'bg-sky-100 text-sky-800' }[kind] || 'bg-gray-100 text-gray-700';
                                const badge = `<span class="ml-1 rounded ${badgeCls} px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide">${badgeText}</span>`;
                                return `<div><span class="font-medium">${escape(data.kode_produk)}</span> — ${escape(data.nama_produk)}${badge}</div>`;
                            },
                            item: (data, escape) => {
                                const kind = data.catalog_kind || 'mo';
                                const badgeText = { mo: 'MO', speedshop_supplier: 'Speedshop', part_luar: 'Part Luar', merchandise: 'Merch', consumable: 'Consumable' }[kind] || kind;
                                return `<div>${escape(data.kode_produk)} — ${escape(data.nama_produk)} · ${badgeText}</div>`;
                            },
                        },
                    });
                }
            });
        }
    };
}
</script>
@endpush
@endsection
