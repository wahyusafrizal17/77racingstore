<?php $__env->startSection('title', 'Buat Work Order'); ?>

<?php $__env->startSection('content'); ?>
<?php $__env->startPush('styles'); ?>
<?php echo $__env->make('partials.speedshop.dashboard-load-animations', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('partials.speedshop.work-order-create-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopPush(); ?>

<div
    x-data="workOrderCreate()"
    x-init="$nextTick(() => { requestAnimationFrame(() => $el.classList.add('is-ready')); })"
    class="ss-dashboard ss-dash-page ss-wo-create"
>
<div class="ss-dash-content space-y-5">
    <?php if($errors->any()): ?>
    <div class="ss-dash-enter wo-alert" style="--ss-delay: 40ms">
        <ul>
            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $err): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <li><?php echo e($err); ?></li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
    </div>
    <?php endif; ?>

    <form id="form-work-order" action="<?php echo e(route('speedshop.work-orders.store')); ?>" method="POST" novalidate @submit.prevent="submitForm" class="ss-dash-enter" style="--ss-delay: 80ms">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="tipe_pemesanan" id="input-tipe-pemesanan" x-model="tipePemesanan" value="servis">
        
        <input type="hidden" name="sumber_kedatangan" x-model="formSumberKedatangan">

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
            <div class="space-y-5 lg:col-span-2">
                <div class="panel-card panel-card-flush ss-dash-enter-child" style="--ss-delay: 120ms; --ss-stagger: 0">
                    <div class="panel-card-head">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
                            <div class="min-w-0">
                                <p class="panel-card-title" style="font-size:13px">Jenis transaksi</p>
                                <p class="panel-card-sub">Servis bengkel lengkap, atau penjualan part tanpa kendaraan &amp; jasa.</p>
                            </div>
                            <div class="type-toggle">
                                <button type="button" @click="setTipe('servis')" :class="tipePemesanan === 'servis' && 'is-active'">
                                    Servis &amp; Penggantian Parts
                                </button>
                                <button type="button" @click="setTipe('pembelian_part')" :class="tipePemesanan === 'pembelian_part' && 'is-active'">
                                    Pembelian Parts
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="panel-card-body">
                    <div class="space-y-4">
                        
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Customer <span class="text-red-500">*</span></label>
                                <div class="flex gap-2 items-center">
                                    <select name="pelanggan_id" id="pelanggan-select-wo" class="flex-1 rounded-xl bg-white text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40">
                                        <option value="">Pilih Customer</option>
                                    </select>
                                    <button type="button" @click="showQuickAddModal = true" class="wo-icon-btn" title="Tambah Customer Baru">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                    </button>
                                </div>
                            </div>
                            <div x-show="tipePemesanan === 'servis'" x-cloak>
                                <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Kendaraan <span class="text-red-500">*</span></label>
                                <div class="flex gap-2 items-center">
                                    <select name="kendaraan_id" id="kendaraan-select-wo" class="flex-1 rounded-xl bg-white text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40" :disabled="tipePemesanan === 'pembelian_part'">
                                        <option value="">- Pilih Kendaraan -</option>
                                    </select>
                                    <button type="button" @click="openAddKendaraanModal()" :disabled="!pelangganId" class="wo-icon-btn" title="Tambah Kendaraan Baru">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                    </button>
                                </div>
                            </div>
                            <div x-show="tipePemesanan === 'pembelian_part'" x-cloak>
                                <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Tanggal order <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input type="date" name="tanggal_masuk" value="<?php echo e(date('Y-m-d')); ?>"
                                           readonly
                                           class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 pr-10 text-sm text-gray-700"
                                           :disabled="tipePemesanan === 'servis'">
                                    <svg class="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                            </div>
                        </div>

                        
                        <div x-show="tipePemesanan === 'servis'" x-cloak class="space-y-4">
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Tanggal Masuk <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <input type="date" name="tanggal_masuk" value="<?php echo e(date('Y-m-d')); ?>" readonly class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 pr-10 text-sm text-gray-700" :disabled="tipePemesanan === 'pembelian_part'">
                                        <svg class="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Kilometer Kendaraan</label>
                                    <input type="number" name="kilometer" x-model.number="formKilometer" min="0" placeholder="Contoh: 15000" class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40" :disabled="tipePemesanan === 'pembelian_part'">
                                    <p class="mt-1 text-xs text-gray-400" x-show="lastKilometer != null" x-text="'Kilometer terakhir: ' + lastKilometer"></p>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Sumber Kedatangan <span class="text-red-500">*</span></label>
                                    <select class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40" :disabled="tipePemesanan === 'pembelian_part'" :value="formSumberKedatangan" @change="formSumberKedatangan = $event.target.value">
                                        <option value="">-- Pilih --</option>
                                        <?php $__currentLoopData = \App\Models\ServiceOrder::SUMBER_KEDATANGAN; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($val); ?>"><?php echo e($label); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Kategori Service <span class="text-red-500">*</span></label>
                                    <select name="kategori_service" x-model="formKategoriService" class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40" :disabled="tipePemesanan === 'pembelian_part'">
                                        <option value="">-- Pilih --</option>
                                        <?php $__currentLoopData = \App\Models\ServiceOrder::KATEGORI_SERVICE; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($val); ?>"><?php echo e($label); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        
                        <div x-show="tipePemesanan === 'pembelian_part'" x-cloak class="grid grid-cols-1 gap-4">
                            <div>
                                <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Sumber Kedatangan <span class="text-red-500">*</span></label>
                                <select class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40" :disabled="tipePemesanan === 'servis'" :value="formSumberKedatangan" @change="formSumberKedatangan = $event.target.value">
                                    <option value="">-- Pilih --</option>
                                    <?php $__currentLoopData = \App\Models\ServiceOrder::SUMBER_KEDATANGAN; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($val); ?>"><?php echo e($label); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                        </div>

                        <div x-show="tipePemesanan === 'servis'" x-cloak>
                            <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Keluhan</label>
                            <textarea name="keluhan" rows="3" class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40" placeholder="Keluhan / deskripsi service" :disabled="tipePemesanan === 'pembelian_part'"></textarea>
                        </div>
                        <div x-show="tipePemesanan === 'pembelian_part'" x-cloak>
                            <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Catatan</label>
                            <textarea name="catatan" rows="3" x-model="formCatatanPart" class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40" placeholder="Catatan transaksi (opsional)"></textarea>
                        </div>
                    </div>
                    </div>
                </div>

                <div class="panel-card ss-dash-enter-child" style="--ss-delay: 120ms; --ss-stagger: 1" x-show="tipePemesanan === 'servis'" x-cloak>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="section-hd" style="margin-bottom:0">
                            <h2>Pekerjaan</h2>
                            <p>Wajib minimal satu jasa. Tambah pekerjaan/jasa yang dikerjakan pada order ini.</p>
                        </div>
                        <span class="wo-badge" x-text="formJasa.length + ' jasa'"></span>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-4">
                        <div class="min-w-[200px] flex-1">
                            <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Jasa</label>
                            <select id="jasa-select-wo" class="w-full rounded-xl border border-gray-200 bg-white text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40"></select>
                        </div>
                        <div class="w-24">
                            <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Qty</label>
                            <input type="number" x-model.number="addJasaQty" min="1" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-center text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40">
                        </div>
                        <div class="flex items-end">
                            <button type="button" @click="addJasa()" class="btn-primary">+ Tambah Jasa</button>
                        </div>
                    </div>
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead><tr><th>No.</th><th>Nama Jasa</th><th class="text-center">Qty</th><th class="text-right">Biaya Satuan</th><th class="text-right">Subtotal</th><th class="w-16"></th></tr></thead>
                            <tbody>
                                <template x-for="(j, idx) in formJasa" :key="j.id + '-' + idx">
                                    <tr class="transition hover:bg-[#fcfafb]">
                                        <td class="px-5 py-4 text-gray-500" x-text="idx + 1"></td>
                                        <td class="px-5 py-4 font-medium text-gray-900" x-text="j.nama"></td>
                                        <td class="px-5 py-4 text-center font-medium text-gray-900" x-text="Number(j.qty || 0).toLocaleString('id-ID')"></td>
                                        <td class="px-5 py-4 text-right">
                                            <span class="inline-flex min-w-[8rem] justify-end rounded-lg bg-gray-50 px-3 py-1.5 text-sm font-medium text-gray-700" x-text="formatRupiah(j.biaya)"></span>
                                        </td>
                                        <td class="px-5 py-4 text-right">
                                            <span class="inline-flex min-w-[8rem] justify-end rounded-lg bg-gray-50 px-3 py-1.5 text-sm font-medium text-gray-700" x-text="formatRupiah((j.qty || 0) * (j.biaya || 0))"></span>
                                        </td>
                                        <td class="px-5 py-4">
                                            <button type="button" @click="formJasa.splice(idx, 1)" class="rounded-lg p-1.5 text-red-400 transition hover:bg-red-50 hover:text-red-600">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                                <template x-if="formJasa.length === 0">
                                    <tr><td colspan="6" class="empty-cell">Belum ada jasa ditambahkan.</td></tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="panel-card ss-dash-enter-child" style="--ss-delay: 120ms; --ss-stagger: 2">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="section-hd" style="margin-bottom:0">
                            <h2>Sparepart</h2>
                            <p x-show="tipePemesanan === 'pembelian_part'" x-cloak>Diskon diisi per baris. Maks. 10% per item; lebih dari itu perlu verifikasi Manager.</p>
                            <p x-show="tipePemesanan !== 'pembelian_part'">Tambah produk sparepart.</p>
                        </div>
                        <span class="wo-badge" x-text="formSparepart.length + ' item'"></span>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-4">
                        <div class="min-w-[200px] flex-1">
                            <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Produk</label>
                            <select x-ref="sparepartSelect" id="product-select-wo" class="w-full rounded-xl border border-gray-200 bg-white text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40"></select>
                        </div>
                        <div class="w-24">
                            <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Qty</label>
                            <input type="number" x-model.number="addSparepartQty" min="1" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-center text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40">
                        </div>
                        <div class="flex items-end">
                            <button type="button" @click="addSparepart()" class="btn-primary">+ Tambah Sparepart</button>
                        </div>
                    </div>
                    <div class="wo-notice"
                         x-show="tipePemesanan === 'pembelian_part' && pembelianPartNeedsManagerVerify"
                         x-cloak>
                        Ada diskon lebih dari 10% per item. Transaksi akan diajukan ke Manager untuk verifikasi sebelum invoice dibuat.
                    </div>
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No.</th>
                                    <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Kode</th>
                                    <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Nama</th>
                                    <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-center">Qty</th>
                                    <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-right" x-show="tipePemesanan === 'servis'">Harga</th>
                                    <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-right" x-show="tipePemesanan === 'servis'">Subtotal</th>
                                    <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-right" x-show="tipePemesanan === 'pembelian_part'" x-cloak>Subtotal</th>
                                    <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500" x-show="tipePemesanan === 'pembelian_part'" x-cloak>Jenis Diskon</th>
                                    <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-right" x-show="tipePemesanan === 'pembelian_part'" x-cloak>Nilai Diskon</th>
                                    <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-right" x-show="tipePemesanan === 'pembelian_part'" x-cloak>Total Diskon</th>
                                    <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500" x-show="tipePemesanan === 'pembelian_part'" x-cloak>Catatan Diskon</th>
                                    <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-center" x-show="tipePemesanan === 'pembelian_part'" x-cloak>Hapus</th>
                                    <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 w-16" x-show="tipePemesanan === 'servis'"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="(s, idx) in formSparepart" :key="s.product_id + '-' + idx">
                                    <tr class="transition hover:bg-[#fcfafb]">
                                        <td class="px-5 py-4 text-gray-500" x-text="idx + 1"></td>
                                        <td class="px-5 py-4 font-semibold text-gray-900" x-text="s.kode_produk"></td>
                                        <td class="px-5 py-4 text-gray-700">
                                            <span x-text="s.nama_produk"></span>
                                            <template x-if="s.catalog_kind === 'mo'">
                                                <span class="ml-1 inline-flex rounded bg-indigo-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-indigo-800">MO</span>
                                            </template>
                                            <template x-if="s.catalog_kind === 'speedshop_supplier'">
                                                <span class="ml-1 inline-flex rounded bg-emerald-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-emerald-800">Speedshop</span>
                                            </template>
                                            <template x-if="s.catalog_kind === 'part_luar' || s.is_part_luar">
                                                <span class="ml-1 inline-flex rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-800">Part Luar</span>
                                            </template>
                                        </td>
                                        <td class="px-5 py-4 text-center">
                                            <input type="number" min="1" step="1" x-model.number="s.qty" @input="onSparepartQtyChange(idx)" class="w-20 rounded-xl border border-gray-200 bg-white px-2 py-1.5 text-center text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40">
                                        </td>
                                        <td class="px-5 py-4 text-right text-gray-700 tabular-nums" x-show="tipePemesanan === 'servis'" x-text="formatRupiah(s.harga)"></td>
                                        <td class="px-5 py-4 text-right text-gray-700 tabular-nums" x-show="tipePemesanan === 'servis'" x-text="formatRupiah(sparepartSubtotalBruto(s))"></td>
                                        <td class="px-5 py-4 text-right text-gray-700 tabular-nums" x-show="tipePemesanan === 'pembelian_part'" x-cloak x-text="formatRupiah(sparepartSubtotalBruto(s))"></td>
                                        <td class="px-5 py-4" x-show="tipePemesanan === 'pembelian_part'" x-cloak>
                                            <select x-model="s.diskon_tipe" @change="syncSparepartDiskon(idx)" class="diskon-tipe-sparepart w-20 rounded-xl border border-gray-200 bg-white px-2 py-1.5 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40">
                                                <option value="rp">Rp</option>
                                                <option value="persen">%</option>
                                            </select>
                                        </td>
                                        <td class="px-5 py-4 text-right" x-show="tipePemesanan === 'pembelian_part'" x-cloak>
                                            <input type="number" min="0" x-model.number="s.diskon_value" @input="syncSparepartDiskon(idx)" class="diskon-value-sparepart w-24 rounded-xl border border-gray-200 bg-white px-2 py-1.5 text-right text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40" placeholder="0">
                                        </td>
                                        <td class="px-5 py-4 text-right text-xs text-gray-500" x-show="tipePemesanan === 'pembelian_part'" x-cloak x-text="formatRupiah(s.diskon || 0)"></td>
                                        <td class="px-5 py-4" x-show="tipePemesanan === 'pembelian_part'" x-cloak>
                                            <input type="text"
                                                   x-model="s.catatan_diskon"
                                                   :required="(Number(s.diskon) || 0) > 0"
                                                   maxlength="500"
                                                   placeholder="Wajib jika ada diskon"
                                                   class="catatan-diskon-sparepart w-full min-w-[160px] rounded-xl border border-gray-200 bg-white px-2 py-1.5 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40"
                                                   :class="(Number(s.diskon) || 0) > 0 && !String(s.catatan_diskon || '').trim() ? 'border-amber-400 ring-1 ring-amber-200' : ''">
                                        </td>
                                        <td class="px-5 py-4 text-center" x-show="tipePemesanan === 'pembelian_part'" x-cloak>
                                            <button type="button" @click="removeSparepart(idx)" class="inline-flex items-center rounded-lg border border-red-200 bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100">Hapus</button>
                                        </td>
                                        <td class="px-5 py-4" x-show="tipePemesanan === 'servis'">
                                            <button type="button" @click="removeSparepart(idx)" class="rounded-lg p-1.5 text-red-400 transition hover:bg-red-50 hover:text-red-600">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                                <template x-if="formSparepart.length === 0">
                                    <tr><td :colspan="tipePemesanan === 'pembelian_part' ? 10 : 7" class="px-5 py-8 text-center text-gray-400">Belum ada sparepart ditambahkan.</td></tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <div class="space-y-5 ss-dash-enter-child" style="--ss-delay: 120ms; --ss-stagger: 3">
                <div class="panel-card summary-card">
                    <div class="section-hd">
                        <h2>Ringkasan</h2>
                        <p>Estimasi biaya work order</p>
                    </div>
                    <div class="mt-2">
                        <div class="summary-row" x-show="tipePemesanan === 'servis'" x-cloak>
                            <span>Total Jasa</span>
                            <span x-text="formatRupiah(totalJasa)"></span>
                        </div>
                        <div class="summary-row">
                            <span>Total Sparepart</span>
                            <span x-text="formatRupiah(totalSparepart)"></span>
                        </div>
                        <div class="summary-row" x-show="tipePemesanan === 'pembelian_part'" x-cloak>
                            <span>Biaya Charge</span>
                            <span x-text="formatRupiah(chargeAmount())"></span>
                        </div>
                        <div class="summary-row" x-show="tipePemesanan === 'pembelian_part'" x-cloak>
                            <span>PPN</span>
                            <span x-text="formatRupiah(ppnAmount())"></span>
                        </div>
                        <div class="summary-total">
                            <span x-text="tipePemesanan === 'pembelian_part' ? 'Total Akhir' : 'Estimasi Total'"></span>
                            <span x-text="formatRupiah(tipePemesanan === 'pembelian_part' ? totalAkhirFinal() : ((tipePemesanan === 'servis' ? totalJasa : 0) + totalSparepart))"></span>
                        </div>
                    </div>
                    <input type="hidden" name="estimasi_biaya" :value="tipePemesanan === 'pembelian_part' ? totalAkhirFinal() : ((tipePemesanan === 'servis' ? totalJasa : 0) + totalSparepart)">
                    <div class="mt-5 space-y-3" x-show="tipePemesanan === 'pembelian_part'" x-cloak>
                        <input type="hidden" name="pembayaran_mode" :value="pembayaranMode">
                        <div>
                            <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Pembayaran <span class="text-red-500">*</span></label>
                            <div class="pay-toggle">
                                <button type="button" @click="setPembayaranMode('single')" :class="pembayaranMode === 'single' && 'is-active'">
                                    Tunggal
                                </button>
                                <button type="button" @click="setPembayaranMode('split')" :class="pembayaranMode === 'split' && 'is-active'">
                                    Split bill
                                </button>
                            </div>
                        </div>
                        <div x-show="pembayaranMode === 'single'" x-cloak class="space-y-1">
                            <label class="mb-1 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Jenis pembayaran</label>
                            <select name="jenis_pembayaran" x-model="formJenisPembayaran" :required="pembayaranMode === 'single'" :disabled="pembayaranMode !== 'single'"
                                    class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40 disabled:bg-gray-100">
                                <option value="cash">Tunai</option>
                                <option value="transfer">Transfer</option>
                                <option value="qris">QRIS</option>
                                <option value="edc">EDC</option>
                            </select>
                        </div>
                        <div x-show="pembayaranMode === 'split'" x-cloak class="space-y-2">
                            <template x-for="(row, idx) in splitRows" :key="idx">
                                <div class="flex flex-wrap items-end gap-2">
                                    <div class="min-w-[120px] flex-1">
                                        <label class="mb-1 block text-[10px] font-medium uppercase tracking-wide text-gray-500">Metode</label>
                                        <select class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40"
                                                x-model="row.metode"
                                                :disabled="pembayaranMode !== 'split'"
                                                :name="'pembayaran[' + idx + '][metode]'">
                                            <option value="cash">Tunai</option>
                                            <option value="transfer">Transfer</option>
                                            <option value="qris">QRIS</option>
                                            <option value="edc">EDC</option>
                                        </select>
                                    </div>
                                    <div class="min-w-[100px] flex-1">
                                        <label class="mb-1 block text-[10px] font-medium uppercase tracking-wide text-gray-500">Nominal (Rp)</label>
                                        <input type="text" inputmode="numeric" autocomplete="off"
                                               class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm tabular-nums focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40"
                                               :disabled="pembayaranMode !== 'split'"
                                               x-model="row.jumlahDisplay"
                                               @input="
                                                    const raw = (row.jumlahDisplay || '').toString();
                                                    const digits = raw.replace(/\D/g, '');
                                                    row.jumlah = digits ? parseInt(digits, 10) : 0;
                                                    row.jumlahDisplay = formatNumber(row.jumlah);
                                               "
                                               placeholder="0">
                                        <input type="hidden" :disabled="pembayaranMode !== 'split'"
                                               :name="'pembayaran[' + idx + '][jumlah]'"
                                               :value="row.jumlah">
                                    </div>
                                    <button type="button" @click="removeSplitRow(idx)" :disabled="splitRows.length <= 2"
                                            class="mb-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 text-gray-500 transition hover:bg-gray-50 disabled:opacity-40"
                                            title="Hapus baris">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </template>
                            <div class="flex justify-end">
                                <button type="button" @click="addSplitRow()" class="inline-flex items-center gap-1 rounded-lg border border-dashed border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-600 transition hover:border-gray-400 hover:bg-gray-50">
                                    + Tambah metode
                                </button>
                            </div>
                            <div class="rounded-xl border px-3 py-2 text-xs" :class="splitPaymentOk ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-amber-200 bg-amber-50 text-amber-900'">
                                <div class="flex justify-between gap-2">
                                    <span>Total dibayar</span>
                                    <span class="font-semibold tabular-nums" x-text="'Rp ' + formatNumber(splitTotalPaid)"></span>
                                </div>
                                <div class="mt-1 flex justify-between gap-2">
                                    <span>Total transaksi</span>
                                    <span class="font-semibold tabular-nums" x-text="'Rp ' + formatNumber(totalAkhirFinal())"></span>
                                </div>
                                <p class="mt-1.5 font-medium" x-show="!splitPaymentOk && pembayaranMode === 'split'" x-cloak>Selisih harus Rp 0 agar bisa disimpan.</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-6">
                        <button type="submit" :disabled="submitting || !canSubmit || (tipePemesanan === 'pembelian_part' && pembayaranMode === 'split' && !splitPaymentOk)" class="btn-primary w-full">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            <span x-text="submitting ? 'Menyimpan...' : (tipePemesanan === 'pembelian_part' ? 'Simpan pembelian part' : 'Proses Work Order')"></span>
                        </button>
                    </div>
                </div>

                <a href="<?php echo e(route('speedshop.wip')); ?>" class="btn-secondary w-full">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Kembali ke Daftar
                </a>
            </div>
        </div>

        
        <div id="form-jasa-container"></div>
        <div id="form-sparepart-container"></div>
    </form>

    
    <template x-teleport="body">
        <div
            x-show="showQuickAddModal"
            x-cloak
            class="wo-modal-root"
            @keydown.escape.window="showQuickAddModal = false"
            role="dialog"
            aria-modal="true"
            aria-labelledby="wo-modal-customer-title"
        >
            <div class="wo-modal-backdrop" x-show="showQuickAddModal" x-transition.opacity.duration.200ms @click="showQuickAddModal = false"></div>
            <div class="wo-modal-center">
                <div
                    class="wo-modal-panel"
                    x-show="showQuickAddModal"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-3 scale-[0.98]"
                    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                    x-transition:leave-end="opacity-0 translate-y-2 scale-[0.98]"
                    @click.stop
                >
                    <div class="wo-modal-hd">
                        <div class="min-w-0">
                            <h3 id="wo-modal-customer-title">Tambah Customer Baru</h3>
                            <p>Isi data customer, lalu pilih/tambahkan kendaraan dari kolom kendaraan.</p>
                        </div>
                        <button type="button" class="wo-modal-close" @click="showQuickAddModal = false" aria-label="Tutup">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="wo-modal-bd">
                        <div class="space-y-3">
                            <div>
                                <label class="wo-modal-label">Nama <span class="text-red-500">*</span></label>
                                <input type="text" x-model="quickAdd.nama" class="wo-modal-input" placeholder="Nama customer" @keydown.enter.prevent="submitQuickAdd()">
                            </div>
                            <div>
                                <label class="wo-modal-label">No HP</label>
                                <input type="text" x-model="quickAdd.no_hp" class="wo-modal-input" placeholder="08xxxx">
                            </div>
                            <div>
                                <label class="wo-modal-label">Alamat</label>
                                <textarea x-model="quickAdd.alamat" rows="2" class="wo-modal-input wo-modal-textarea" placeholder="Alamat"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="wo-modal-ft">
                        <button type="button" @click="showQuickAddModal = false" class="wo-modal-btn-ghost">Batal</button>
                        <button type="button" @click="submitQuickAdd()" :disabled="quickAddLoading" class="wo-modal-btn-primary">
                            <span x-show="!quickAddLoading">Simpan &amp; Pilih</span>
                            <span x-show="quickAddLoading" x-cloak>Menyimpan...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <div
            x-show="showAddKendaraanModal"
            x-cloak
            class="wo-modal-root"
            @keydown.escape.window="showAddKendaraanModal = false"
            role="dialog"
            aria-modal="true"
            aria-labelledby="wo-modal-kendaraan-title"
        >
            <div class="wo-modal-backdrop" x-show="showAddKendaraanModal" x-transition.opacity.duration.200ms @click="showAddKendaraanModal = false"></div>
            <div class="wo-modal-center">
                <div
                    class="wo-modal-panel"
                    x-show="showAddKendaraanModal"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-3 scale-[0.98]"
                    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                    x-transition:leave-end="opacity-0 translate-y-2 scale-[0.98]"
                    @click.stop
                >
                    <div class="wo-modal-hd">
                        <div class="min-w-0">
                            <h3 id="wo-modal-kendaraan-title">Tambah Kendaraan Baru</h3>
                            <p>Isi data kendaraan untuk customer yang dipilih.</p>
                        </div>
                        <button type="button" class="wo-modal-close" @click="showAddKendaraanModal = false" aria-label="Tutup">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="wo-modal-bd">
                        <div class="space-y-3">
                            <div>
                                <label class="wo-modal-label">No Polisi <span class="text-red-500">*</span></label>
                                <input type="text" x-model="addKendaraan.nomor_polisi" class="wo-modal-input" placeholder="B 1234 XYZ" @keydown.enter.prevent="submitAddKendaraan()">
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="wo-modal-label">Merk <span class="text-red-500">*</span></label>
                                    <input type="text" x-model="addKendaraan.merk" class="wo-modal-input" placeholder="Honda">
                                </div>
                                <div>
                                    <label class="wo-modal-label">Tipe</label>
                                    <input type="text" x-model="addKendaraan.tipe" class="wo-modal-input" placeholder="Beat">
                                </div>
                            </div>
                            <div>
                                <label class="wo-modal-label">Tahun</label>
                                <input type="number" x-model="addKendaraan.tahun" min="1990" max="2030" class="wo-modal-input" placeholder="2024">
                            </div>
                        </div>
                    </div>
                    <div class="wo-modal-ft">
                        <button type="button" @click="showAddKendaraanModal = false" class="wo-modal-btn-ghost">Batal</button>
                        <button type="button" @click="submitAddKendaraan()" :disabled="addKendaraanLoading" class="wo-modal-btn-primary">
                            <span x-show="!addKendaraanLoading">Simpan &amp; Pilih</span>
                            <span x-show="addKendaraanLoading" x-cloak>Menyimpan...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
function workOrderCreate() {
    const jasaList = <?php echo json_encode($jasaServis->map(fn($j) => ['id' => $j->id, 'nama' => $j->nama, 'biaya' => (int)$j->biaya])) ?>;
    const productsSearchUrl = <?php echo json_encode(route('speedshop.work-orders.products.search'), 15, 512) ?>;
    const productsById = {};
    const catalogBadgeText = { mo: 'MO', speedshop_supplier: 'Speedshop', part_luar: 'Part Luar', merchandise: 'Merch', consumable: 'Consumable' };
    const catalogBadgeCls = { mo: 'bg-indigo-100 text-indigo-800', speedshop_supplier: 'bg-emerald-100 text-emerald-800', part_luar: 'bg-amber-100 text-amber-800', merchandise: 'bg-pink-100 text-pink-800', consumable: 'bg-sky-100 text-sky-800' };
    function mapProductOption(p) {
        const kind = p.catalog_kind || (p.is_part_luar ? 'part_luar' : 'mo');
        const badgeText = catalogBadgeText[kind] || kind;
        const option = {
            id: p.id,
            kode_produk: p.kode_produk,
            nama_produk: p.nama_produk,
            harga_jual_speedshop: p.harga_jual_speedshop,
            harga_jual: p.harga_jual,
            harga_eceran_terendah: p.harga_eceran_terendah,
            harga_jual_cust: p.harga_jual_cust,
            hpp: p.hpp,
            jumlah: Number(p.jumlah) || 0,
            catalog_kind: kind,
            catalog_label: p.catalog_label || kind,
            is_part_luar: !!p.is_part_luar || kind === 'part_luar',
            label: `[${p.kode_produk}] ${p.nama_produk} · ${badgeText} — Stok: ${Number(p.jumlah || 0).toLocaleString('id-ID')}`,
        };
        productsById[String(p.id)] = option;
        return option;
    }
    const initialPelanggans = <?php echo json_encode($initialPelanggans, 15, 512) ?>;
    const invoiceSettings = <?php echo json_encode($invoiceSettings ?? [], 15, 512) ?>;

    return {
        tipePemesanan: 'servis',
        _typeState: {
            sparepart: {
                servis: [],
                pembelian_part: [],
            },
        },
        formJenisPembayaran: 'cash',
        pembayaranMode: 'single',
        splitRows: [
            { metode: 'cash', jumlah: 0, jumlahDisplay: '0' },
            { metode: 'transfer', jumlah: 0, jumlahDisplay: '0' },
        ],
        formCatatanPart: '',
        pelangganId: '',
        kendaraanId: '',
        kendaraans: [],
        kendaraanSelect: null,
        jasaSelect: null,
        formJasa: [],
        addJasaQty: 1,
        addSparepartQty: 1,
        formSparepart: [],
        chargeMode: String(invoiceSettings.invoice_charge_mode || 'percent'),
        chargePercent: Number(invoiceSettings.invoice_charge_percent || 0),
        chargeRupiah: Number(invoiceSettings.invoice_charge_rupiah || 0),
        chargePercentMinimum: <?php echo \Illuminate\Support\Js::from(\App\Services\InvoiceSettingsService::CHARGE_PERCENT_MINIMUM)->toHtml() ?>,
        ppnMode: String(invoiceSettings.invoice_ppn_mode || 'percent'),
        ppnPercent: Number(invoiceSettings.invoice_ppn_percent || 0),
        ppnRupiah: Number(invoiceSettings.invoice_ppn_rupiah || 0),
        sparepartSelect: null,
        _spInitialStock: {},
        _spRemainingStock: {},
        submitting: false,

        get totalJasa() {
            return this.formJasa.reduce((s, j) => s + ((Number(j.qty) || 0) * (Number(j.biaya) || 0)), 0);
        },
        get totalSparepart() {
            return this.formSparepart.reduce((s, p) => {
                const bruto = this.sparepartSubtotalBruto(p);
                if (this.tipePemesanan === 'pembelian_part') {
                    return s + Math.max(0, bruto - (Number(p.diskon) || 0));
                }
                return s + bruto;
            }, 0);
        },

        sparepartSubtotalBruto(row) {
            return (Number(row?.qty) || 0) * (Number(row?.harga) || 0);
        },

        computeDiskonRp(subtotal, tipe, val) {
            const base = Math.max(0, Number(subtotal) || 0);
            const t = String(tipe || 'rp');
            const v = Number(val) || 0;
            if (base <= 0) return 0;
            if (t === 'rp') {
                return Math.min(Math.max(0, Math.floor(v)), base);
            }
            const pct = Math.min(100, Math.max(0, v));
            return Math.floor(base * pct / 100);
        },

        syncSparepartDiskon(idx) {
            const row = this.formSparepart[idx];
            if (!row) return;
            const subtotal = this.sparepartSubtotalBruto(row);
            row.diskon = this.computeDiskonRp(subtotal, row.diskon_tipe, row.diskon_value);
            if (row.diskon_tipe === 'rp') {
                row.diskon_value = row.diskon;
            }
        },

        lineDiskonNeedsManagerVerify(subtotal, diskon) {
            const base = Math.max(0, Number(subtotal) || 0);
            const d = Math.max(0, Number(diskon) || 0);
            if (base <= 0 || d <= 0) return false;
            return d > Math.floor(base * 0.10);
        },

        get pembelianPartNeedsManagerVerify() {
            if (this.tipePemesanan !== 'pembelian_part') return false;
            return this.formSparepart.some((row) => {
                const subtotal = this.sparepartSubtotalBruto(row);
                return this.lineDiskonNeedsManagerVerify(subtotal, row.diskon || 0);
            });
        },

        onSparepartQtyChange(idx) {
            this.adjustSparepartQty(idx);
            if (this.tipePemesanan === 'pembelian_part') {
                this.syncSparepartDiskon(idx);
            }
        },

        baseGrandTotalPembelianPart() {
            return Number(this.totalSparepart || 0);
        },

        chargeAmount() {
            const base = this.baseGrandTotalPembelianPart();
            if (String(this.chargeMode || 'percent') === 'rupiah') {
                return Math.max(0, Math.round(Number(this.chargeRupiah || 0)));
            }
            const pct = Number(this.chargePercent || 0);
            if (pct <= 0) {
                return 0;
            }
            const fromPercent = Math.max(0, Math.round(base * (pct / 100)));

            return Math.max(this.chargePercentMinimum || 0, fromPercent);
        },

        ppnAmount() {
            const base = this.baseGrandTotalPembelianPart();
            if (String(this.ppnMode || 'percent') === 'rupiah') {
                return Math.max(0, Math.round(Number(this.ppnRupiah || 0)));
            }
            const pct = Number(this.ppnPercent || 0);
            return Math.max(0, Math.round(base * (pct / 100)));
        },

        totalAkhirFinal() {
            return this.baseGrandTotalPembelianPart() + this.chargeAmount() + this.ppnAmount();
        },

        formatRupiah(n) {
            return 'Rp ' + Number(n || 0).toLocaleString('id-ID');
        },

        formatNumber(n) {
            return Number(n || 0).toLocaleString('id-ID');
        },

        setPembayaranMode(m) {
            this.pembayaranMode = m;
            if (m === 'split' && (!this.splitRows || this.splitRows.length < 2)) {
                this.splitRows = [
                    { metode: 'cash', jumlah: 0, jumlahDisplay: this.formatNumber(0) },
                    { metode: 'transfer', jumlah: 0, jumlahDisplay: this.formatNumber(0) },
                ];
            }
        },

        addSplitRow() {
            this.splitRows.push({ metode: 'cash', jumlah: 0, jumlahDisplay: this.formatNumber(0) });
        },

        removeSplitRow(i) {
            if (this.splitRows.length <= 2) return;
            this.splitRows.splice(i, 1);
        },

        get splitTotalPaid() {
            return this.splitRows.reduce((s, r) => {
                const n = parseInt(String(r.jumlah || '').replace(/\D/g, ''), 10);
                return s + (Number.isFinite(n) ? n : 0);
            }, 0);
        },

        get splitPaymentOk() {
            if (this.pembayaranMode !== 'split') return true;
            const activeLines = this.splitRows.filter((r) => (parseInt(String(r.jumlah || '').replace(/\D/g, ''), 10) || 0) > 0);
            return activeLines.length >= 2 && this.splitTotalPaid === this.totalAkhirFinal();
        },

        _clone(val) {
            try {
                return JSON.parse(JSON.stringify(val ?? []));
            } catch (e) {
                return [];
            }
        },

        setTipe(next) {
            const cur = this.tipePemesanan;
            if (!next || next === cur) return;

            // Simpan sparepart per-tipe agar tidak kebawa antar tab.
            this._typeState.sparepart[cur] = this._clone(this.formSparepart);

            this.tipePemesanan = next;

            // Restore sparepart untuk tipe tujuan.
            this.formSparepart = this._clone(this._typeState.sparepart[next] || []).map((row) => ({
                ...row,
                diskon_tipe: row.diskon_tipe || 'persen',
                diskon_value: row.diskon_value ?? 0,
                diskon: row.diskon ?? 0,
                catatan_diskon: row.catatan_diskon ?? '',
            }));
            if (next === 'pembelian_part') {
                this.formSparepart.forEach((_, i) => this.syncSparepartDiskon(i));
            }
            this._recalcSparepartRemainingStock();
            this._applyRemainingStockToSparepartSelect();
            if (this.sparepartSelect) this.sparepartSelect.clear(true);
            this.addSparepartQty = 1;

            // Default jenis pembayaran hanya relevan untuk pembelian part.
            if (this.tipePemesanan !== 'pembelian_part') {
                this.formJenisPembayaran = 'cash';
            }
        },

        showQuickAddModal: false,
        quickAddLoading: false,
        quickAdd: { nama: '', no_hp: '', alamat: '' },
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

        async submitQuickAdd() {
            if (!this.quickAdd.nama?.trim()) {
                Swal.fire('Perhatian', 'Nama customer wajib diisi.', 'warning');
                return;
            }
            this.quickAddLoading = true;
            try {
                const res = await fetch('<?php echo e(route('speedshop.pelanggans.store')); ?>', {
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
                const kendList = (p.kendaraans || []).map(k => ({ id: k.id, pelanggan_id: p.id, nomor_polisi: k.nomor_polisi, merk: k.merk, tipe: k.tipe || '', tahun: k.tahun }));
                this.kendaraanByPelanggan[String(p.id)] = kendList;
                kendList.forEach(k => this.allKendaraansFlat.push(k));
                this.pelanggansList.push({ id: p.id, nama: p.nama, no_hp: p.no_hp || '', kendaraans: kendList });
                if (this.pelangganSelect) {
                    this.pelangganSelect.addOption({ id: String(p.id), nama: p.nama, no_hp: p.no_hp || '', label: p.nama + (p.no_hp ? ' (' + p.no_hp + ')' : '') });
                    this.pelangganSelect.setValue(String(p.id));
                }
                this.pelangganId = String(p.id);
                if (kendList.length) {
                    this.kendaraanId = String(kendList[0].id);
                }
                // Pastikan dropdown kendaraan langsung refresh setelah quick add customer.
                this.loadKendaraan();
                this.showQuickAddModal = false;
                this.quickAdd = { nama: '', no_hp: '', alamat: '' };
                Swal.fire('Berhasil', 'Customer berhasil ditambahkan.', 'success');
            } catch (e) {
                Swal.fire('Gagal', e.message || 'Gagal menyimpan data.', 'error');
            }
            this.quickAddLoading = false;
        },

        addJasa() {
            const sel = this.jasaSelect;
            const id = sel ? sel.getValue() : '';
            if (!id) {
                Swal.fire('Perhatian', 'Pilih jasa terlebih dahulu.', 'warning');
                return;
            }
            const j = jasaList.find(x => x.id == id);
            const qty = Math.max(1, Number(this.addJasaQty) || 1);
            if (!j) return;

            const existing = this.formJasa.find(x => x.id == id);
            if (existing) {
                existing.qty = (Number(existing.qty) || 0) + qty;
            } else {
                this.formJasa.push({ id: j.id, nama: j.nama, qty: qty, biaya: j.biaya });
            }

            this.addJasaQty = 1;
            if (sel) sel.clear();
        },

        addSparepart() {
            const sel = this.sparepartSelect;
            const id = sel ? sel.getValue() : '';
            if (!sel || !id) {
                Swal.fire('Perhatian', 'Pilih produk terlebih dahulu.', 'warning');
                return;
            }
            const product = productsById[String(id)] || (sel.options ? sel.options[id] : null);
            if (!product) return;
            const stok = Number(this._spRemainingStock[String(product.id)] ?? product.jumlah) || 0;
            if (stok < 1) {
                Swal.fire('Perhatian', 'Stok produk habis, tidak bisa ditambahkan.', 'warning');
                return;
            }
            const qty = Math.max(1, this.addSparepartQty || 1);
            const sudahDitambah = this.formSparepart.find(p => p.product_id == product.id);
            const totalQty = (sudahDitambah ? sudahDitambah.qty : 0) + qty;
            if (totalQty > stok) {
                Swal.fire('Perhatian', 'Stok tidak mencukupi. Tersedia: ' + stok, 'warning');
                return;
            }
            const harga = Number(product.harga_jual_cust || product.harga_eceran_terendah || product.harga_jual || product.harga_jual_speedshop || product.hpp || 0);
            if (sudahDitambah) {
                sudahDitambah.qty += qty;
            } else {
                this.formSparepart.push({
                    product_id: product.id,
                    kode_produk: product.kode_produk,
                    nama_produk: product.nama_produk,
                    catalog_kind: product.catalog_kind || (product.is_part_luar ? 'part_luar' : 'mo'),
                    catalog_label: product.catalog_label || '',
                    is_part_luar: !!product.is_part_luar,
                    qty: qty,
                    harga: harga,
                    subtotal: qty * harga,
                    diskon_tipe: 'persen',
                    diskon_value: 0,
                    diskon: 0,
                    catatan_diskon: '',
                });
            }
            if (this.tipePemesanan === 'pembelian_part') {
                const target = sudahDitambah || this.formSparepart[this.formSparepart.length - 1];
                const idx = this.formSparepart.indexOf(target);
                if (idx >= 0) {
                    this.syncSparepartDiskon(idx);
                }
            }
            this._recalcSparepartRemainingStock();
            this._applyRemainingStockToSparepartSelect();
            this.addSparepartQty = 1;
            if (sel) sel.clear();
        },

        adjustSparepartQty(idx) {
            const row = this.formSparepart[idx];
            if (!row) return;

            const productId = String(row.product_id);
            const stokAwal = Number(this._spInitialStock[productId] ?? 0);
            const desired = Math.max(1, Number(row.qty) || 1);

            // total qty dari baris lain (untuk produk yang sama)
            const otherTotal = this.formSparepart
                .filter((r, i) => i !== idx && String(r.product_id) === productId)
                .reduce((s, r) => s + (Number(r.qty) || 0), 0);

            const maxAllowed = Math.max(1, stokAwal - otherTotal);
            if (desired > maxAllowed) {
                row.qty = maxAllowed;
                Swal.fire('Perhatian', 'Stok tidak mencukupi. Tersedia: ' + stokAwal, 'warning');
            } else {
                row.qty = desired;
            }

            row.subtotal = this.sparepartSubtotalBruto(row);
            if (this.tipePemesanan === 'pembelian_part') {
                this.syncSparepartDiskon(idx);
            }
            this._recalcSparepartRemainingStock();
            this._applyRemainingStockToSparepartSelect();
        },

        removeSparepart(idx) {
            this.formSparepart.splice(idx, 1);
            this._recalcSparepartRemainingStock();
            this._applyRemainingStockToSparepartSelect();
        },

        _recalcSparepartRemainingStock() {
            this._spRemainingStock = { ...this._spInitialStock };
            this.formSparepart.forEach(row => {
                const id = String(row.product_id);
                const cur = Number(this._spRemainingStock[id] ?? 0);
                this._spRemainingStock[id] = Math.max(0, cur - (Number(row.qty) || 0));
            });
        },

        _applyRemainingStockToSparepartSelect() {
            if (!this.sparepartSelect) return;

            const options = this.sparepartSelect.options || {};
            Object.keys(options).forEach(optId => {
                if (!optId) return;
                const remain = Number(this._spRemainingStock[String(optId)] ?? options[optId].jumlah ?? 0);
                const next = { ...options[optId] };
                const kind = next.catalog_kind || (next.is_part_luar ? 'part_luar' : 'mo');
                const badgeText = kind === 'mo' ? 'MO' : (kind === 'speedshop_supplier' ? 'Speedshop' : 'Part Luar');
                next.jumlah = remain;
                next.disabled = remain <= 0;
                next.label = `[${next.kode_produk}] ${next.nama_produk} · ${badgeText} — Stok: ${Number(remain).toLocaleString('id-ID')}`;
                this.sparepartSelect.updateOption(optId, next);
            });

            this.sparepartSelect.refreshOptions(false);
        },

        showAddKendaraanModal: false,
        addKendaraanLoading: false,
        addKendaraan: { nomor_polisi: '', merk: '', tipe: '', tahun: '' },
        formKilometer: '',
        formSumberKedatangan: '',
        formKategoriService: '',
        get canSubmitWo() {
            return Boolean(
                this.pelangganId &&
                this.kendaraanId &&
                this.formSumberKedatangan &&
                this.formKategoriService &&
                this.formJasa.length >= 1
            );
        },
        get canSubmit() {
            if (this.tipePemesanan === 'pembelian_part') {
                const pelOk = String(this.pelangganId || '').trim() !== ''
                    || String(this.pelangganSelect?.getValue?.() || '').trim() !== '';
                return Boolean(
                    pelOk &&
                    this.formSumberKedatangan &&
                    this.formSparepart.length >= 1 &&
                    (this.pembayaranMode !== 'split' || this.splitPaymentOk)
                );
            }
            return this.canSubmitWo;
        },
        get lastKilometer() {
            if (!this.kendaraanId) return null;
            const k = this.kendaraans.find(x => String(x.id) === String(this.kendaraanId));
            return k && k.last_kilometer != null ? k.last_kilometer : null;
        },
        openAddKendaraanModal() {
            if (!this.pelangganId) {
                Swal.fire('Perhatian', 'Pilih customer terlebih dahulu.', 'warning');
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
                const res = await fetch('<?php echo e(route('speedshop.kendaraans.quick-add')); ?>', {
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
                // Refresh option list agar kendaraan baru langsung muncul tanpa reselect customer.
                this.syncKendaraanSelect();
                this.showAddKendaraanModal = false;
                this.addKendaraan = { nomor_polisi: '', merk: '', tipe: '', tahun: '' };
                Swal.fire('Berhasil', 'Kendaraan berhasil ditambahkan.', 'success');
            } catch (e) {
                Swal.fire('Gagal', e.message || 'Gagal menyimpan kendaraan.', 'error');
            }
            this.addKendaraanLoading = false;
        },

        async submitForm() {
            if (this.submitting) return;
            const pelVal = this.pelangganSelect ? this.pelangganSelect.getValue() : '';
            const pelOkSubmit = String(pelVal || '').trim() !== '' || String(this.pelangganId || '').trim() !== '';
            if (!pelOkSubmit) {
                Swal.fire('Perhatian', 'Pilih customer terlebih dahulu.', 'warning');
                return;
            }
            if (this.tipePemesanan === 'pembelian_part') {
                if (!this.formSumberKedatangan) {
                    Swal.fire('Perhatian', 'Sumber kedatangan wajib dipilih.', 'warning');
                    return;
                }
                if (this.formSparepart.length < 1) {
                    Swal.fire('Perhatian', 'Tambah minimal satu part.', 'warning');
                    return;
                }
                for (const s of this.formSparepart) {
                    if ((Number(s.diskon) || 0) > 0 && !String(s.catatan_diskon || '').trim()) {
                        Swal.fire('Perhatian', 'Catatan diskon wajib diisi untuk setiap part yang memiliki diskon.', 'warning');
                        return;
                    }
                }
                if (this.pembayaranMode === 'split' && !this.splitPaymentOk) {
                    Swal.fire('Perhatian', 'Total split pembayaran harus sama dengan total transaksi (selisih Rp 0).', 'warning');
                    return;
                }
                const resultPart = await Swal.fire({
                    title: 'Simpan pembelian part?',
                    text: this.pembelianPartNeedsManagerVerify
                        ? 'Ada diskon > 10%. Transaksi akan menunggu verifikasi Manager sebelum invoice dibuat. Stok part tetap berkurang.'
                        : 'Invoice akan dibuat dan stok part berkurang.',
                    icon: this.pembelianPartNeedsManagerVerify ? 'warning' : 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Ya, simpan',
                    cancelButtonText: 'Batal',
                });
                if (!resultPart.isConfirmed) return;
                this.submitting = true;
                const jasaContP = document.getElementById('form-jasa-container');
                const spareContP = document.getElementById('form-sparepart-container');
                jasaContP.innerHTML = '';
                spareContP.innerHTML = '';
                this.formSparepart.forEach((s, i) => {
                    ['product_id', 'qty', 'harga'].forEach((key) => {
                        const inp = document.createElement('input');
                        inp.type = 'hidden';
                        inp.name = `sparepart[${i}][${key}]`;
                        inp.value = s[key];
                        spareContP.appendChild(inp);
                    });
                    const diskonInp = document.createElement('input');
                    diskonInp.type = 'hidden';
                    diskonInp.name = `sparepart[${i}][diskon]`;
                    diskonInp.value = String(Number(s.diskon) || 0);
                    spareContP.appendChild(diskonInp);
                    const catatanInp = document.createElement('input');
                    catatanInp.type = 'hidden';
                    catatanInp.name = `sparepart[${i}][catatan_diskon]`;
                    catatanInp.value = String(s.catatan_diskon || '').trim();
                    spareContP.appendChild(catatanInp);
                });
                await this._submitAjax();
                return;
            }

            const kendVal = this.kendaraanId || '';
            if (!kendVal) {
                Swal.fire('Perhatian', 'Pilih kendaraan terlebih dahulu.', 'warning');
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
            if (this.formJasa.length < 1) {
                Swal.fire('Perhatian', 'Tambah minimal satu jasa servis.', 'warning');
                return;
            }
            const result = await Swal.fire({
                title: 'Simpan Work Order?',
                text: 'Data work order akan disimpan. Setelah itu Anda dapat mencetak lembar kerja dari halaman Work In Progress saat menyerahkan ke mekanik.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, simpan',
                cancelButtonText: 'Batal',
            });
            if (!result.isConfirmed) return;
            this.submitting = true;
            const jasaCont = document.getElementById('form-jasa-container');
            const spareCont = document.getElementById('form-sparepart-container');
            jasaCont.innerHTML = '';
            spareCont.innerHTML = '';
            this.formJasa.forEach((j, i) => {
                ['jasa_servis_id', 'qty', 'biaya'].forEach((key) => {
                    const inp = document.createElement('input');
                    inp.type = 'hidden';
                    inp.name = `jasa[${i}][${key}]`;
                    if (key === 'jasa_servis_id') {
                        inp.value = j.id;
                    } else if (key === 'qty') {
                        inp.value = Math.max(1, Number(j.qty) || 1);
                    } else {
                        inp.value = j.biaya;
                    }
                    jasaCont.appendChild(inp);
                });
            });
            this.formSparepart.forEach((s, i) => {
                ['product_id', 'qty', 'harga'].forEach((key) => {
                    const inp = document.createElement('input');
                    inp.type = 'hidden';
                    inp.name = `sparepart[${i}][${key}]`;
                    inp.value = s[key];
                    spareCont.appendChild(inp);
                });
            });
            await this._submitAjax();
        },

        async _submitAjax() {
            const form = document.getElementById('form-work-order');
            if (!form) return;

            try {
                const res = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: new FormData(form),
                });

                if (res.status === 422) {
                    const data = await res.json().catch(() => ({}));
                    const errors = data.errors || {};
                    const messages = Object.values(errors).flat().filter(Boolean);
                    this.submitting = false;
                    Swal.fire('Perhatian', messages[0] || 'Data tidak valid. Periksa input.', 'warning');
                    return;
                }

                if (!res.ok) {
                    const text = await res.text().catch(() => '');
                    this.submitting = false;
                    let msg = 'Terjadi kesalahan saat menyimpan.';
                    try {
                        const j = JSON.parse(text || '{}');
                        if (j && typeof j.message === 'string' && j.message.trim() !== '') {
                            msg = j.message.trim();
                        }
                    } catch (e) {
                        // ignore non-json
                    }
                    Swal.fire('Gagal', msg, 'error');
                    console.error('WO submit failed', res.status, text);
                    return;
                }

                const data = await res.json().catch(() => ({}));
                const redirect = data.redirect || '<?php echo e(route('speedshop.wip')); ?>';
                if (data.message) {
                    await Swal.fire('Berhasil', data.message, 'success');
                }
                window.location.href = redirect;
            } catch (e) {
                this.submitting = false;
                Swal.fire('Gagal', e?.message || 'Terjadi kesalahan saat menyimpan.', 'error');
            }
        },

        init() {
            this.loadKendaraan();
            this.$nextTick(() => {
                const self = this;
                const fitTsDropdownToViewport = function () {
                    const dropdown = this.dropdown;
                    const control = this.control;
                    if (!dropdown || !control) return;

                    const rect = control.getBoundingClientRect();
                    const gap = 8;
                    const spaceBelow = window.innerHeight - rect.bottom - gap;
                    const spaceAbove = rect.top - gap;
                    const openUp = spaceBelow < 140 && spaceAbove > spaceBelow;
                    const maxH = Math.max(120, Math.min(280, openUp ? spaceAbove : spaceBelow));

                    dropdown.style.maxHeight = `${maxH}px`;
                    dropdown.style.overflow = 'hidden';
                    dropdown.style.display = 'flex';
                    dropdown.style.flexDirection = 'column';

                    const content = dropdown.querySelector('.ts-dropdown-content');
                    if (content) {
                        content.style.maxHeight = 'none';
                        content.style.flex = '1 1 auto';
                        content.style.minHeight = '0';
                        content.style.overflowY = 'auto';
                    }

                    if (openUp) {
                        const height = Math.min(dropdown.offsetHeight || maxH, maxH);
                        dropdown.style.top = `${rect.top - height - 4}px`;
                    }
                };
                const tsDropdown = {
                    dropdownParent: 'body',
                    onDropdownOpen() {
                        fitTsDropdownToViewport.call(this);
                        requestAnimationFrame(() => fitTsDropdownToViewport.call(this));
                    },
                };
                if (typeof TomSelect !== 'undefined') {
                    const pelEl = document.getElementById('pelanggan-select-wo');
                    if (pelEl) {
                        this.pelangganSelect = new TomSelect(pelEl, {
                            ...tsDropdown,
                            placeholder: 'Pilih Customer',
                            allowEmptyOption: true,
                            searchField: ['nama', 'no_hp'],
                            valueField: 'id',
                            labelField: 'label',
                            options: [{ id: '', label: 'Pilih Customer' }, ...self.pelanggansList.map(p => ({
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
                                    if (!data.id) return `<div class="text-gray-400">Pilih Customer</div>`;
                                    return `<div><span class="font-medium">${escape(data.nama)}</span>${data.no_hp ? ' <span class="text-gray-400 text-xs">' + escape(data.no_hp) + '</span>' : ''}</div>`;
                                },
                                item: (data, escape) => `<div>${escape(data.label)}</div>`,
                            },
                        });
                    }
                    const kendEl = document.getElementById('kendaraan-select-wo');
                    if (kendEl) {
                        this.kendaraanSelect = new TomSelect(kendEl, {
                            ...tsDropdown,
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
                    const jasaEl = document.getElementById('jasa-select-wo');
                    if (jasaEl) {
                        this.jasaSelect = new TomSelect(jasaEl, {
                            ...tsDropdown,
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
                                option: (data, escape) => `<div class="flex items-center justify-between gap-3"><span class="font-medium">${escape(data.nama)}</span><span class="text-gray-400 text-xs text-right">Rp ${Number(data.biaya).toLocaleString('id-ID')}</span></div>`,
                                item: (data, escape) => `<div>${escape(data.label)}</div>`,
                            },
                        });
                    }
                }
                const el = document.getElementById('product-select-wo');
                if (el && typeof TomSelect !== 'undefined') {
                    this.sparepartSelect = new TomSelect(el, {
                        ...tsDropdown,
                        valueField: 'id',
                        labelField: 'label',
                        searchField: ['kode_produk', 'nama_produk', 'catalog_label'],
                        optgroupField: 'catalog_kind',
                        optgroupLabelField: 'label',
                        optgroupValueField: 'value',
                        lockOptgroupOrder: true,
                        placeholder: 'Ketik kode atau nama produk...',
                        preload: false,
                        loadThrottle: 250,
                        maxOptions: null,
                        shouldLoad: (query) => String(query || '').trim().length >= 1,
                        load: (query, callback) => {
                            const url = new URL(productsSearchUrl, window.location.origin);
                            url.searchParams.set('q', String(query || '').trim());
                            fetch(url.toString(), {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                credentials: 'same-origin',
                            })
                                .then((res) => res.ok ? res.json() : Promise.reject())
                                .then((rows) => {
                                    const opts = (Array.isArray(rows) ? rows : []).map(mapProductOption);
                                    opts.forEach((o) => {
                                        self._spInitialStock[String(o.id)] = Number(o.jumlah) || 0;
                                    });
                                    callback(opts);
                                    self._recalcSparepartRemainingStock();
                                    self._applyRemainingStockToSparepartSelect();
                                })
                                .catch(() => callback());
                        },
                        optgroups: <?php echo json_encode(\App\Support\ProductMasterCatalog::pickerOptgroups(), 15, 512) ?>,
                        options: [],
                        render: {
                            optgroup_header: (data, escape) => `<div class="px-2 py-1.5 text-[10px] font-semibold uppercase tracking-wide text-gray-500 bg-gray-50 border-b border-gray-100">${escape(data.label)}</div>`,
                            option: (data, escape) => {
                                const stok = Number(data.jumlah || 0);
                                const stokClass = stok === 0 ? 'text-red-600 font-medium' : 'text-gray-500';
                                const kind = data.catalog_kind || 'mo';
                                const badgeText = { mo: 'MO', speedshop_supplier: 'Speedshop', part_luar: 'Part Luar', merchandise: 'Merch', consumable: 'Consumable' }[kind] || kind;
                                const badgeCls = { mo: 'bg-indigo-100 text-indigo-800', speedshop_supplier: 'bg-emerald-100 text-emerald-800', part_luar: 'bg-amber-100 text-amber-800', merchandise: 'bg-pink-100 text-pink-800', consumable: 'bg-sky-100 text-sky-800' }[kind] || 'bg-gray-100 text-gray-700';
                                const badge = `<span class="ml-1 rounded ${badgeCls} px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide">${badgeText}</span>`;
                                return `<div class="flex justify-between items-center gap-2"><span><span class="font-medium">${escape(data.kode_produk)}</span> - ${escape(data.nama_produk)}${badge}</span> <span class="text-xs ${stokClass} shrink-0">Stok: ${stok.toLocaleString('id-ID')}</span></div>`;
                            },
                            item: (data, escape) => {
                                const kind = data.catalog_kind || 'mo';
                                const badgeText = { mo: 'MO', speedshop_supplier: 'Speedshop', part_luar: 'Part Luar', merchandise: 'Merch', consumable: 'Consumable' }[kind] || kind;
                                return `<div>${escape(data.kode_produk)} - ${escape(data.nama_produk)} · ${badgeText} (Stok: ${Number(data.jumlah||0).toLocaleString('id-ID')})</div>`;
                            },
                        },
                    });
                    this._spInitialStock = {};
                    this._recalcSparepartRemainingStock();
                    this._applyRemainingStockToSparepartSelect();
                }
            });
        }
    };
}
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.speedshop', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/wahyusafrizal/work/Github/77racingstore/resources/views/speedshop/work-order/create.blade.php ENDPATH**/ ?>