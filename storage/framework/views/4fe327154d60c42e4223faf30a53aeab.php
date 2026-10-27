
<div x-show="showBarcode" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="showBarcode = false">
    <div x-show="showBarcode" x-transition.opacity class="fixed inset-0 bg-black/50" @click="showBarcode = false"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div x-show="showBarcode" x-transition class="wo-modal-panel relative w-full max-w-md !p-6 sm:!p-8" @click.stop>
            <h3 class="text-lg font-bold text-gray-900">Barcode Produk</h3>
            <div class="mt-6 flex w-full flex-col items-center gap-3">
                <div id="qrcodeElSs" class="inline-block"></div>
                <p class="w-full text-center font-mono text-sm font-semibold tracking-wide text-gray-900" x-text="barcodeValue"></p>
            </div>
            <div class="mt-5">
                <label class="wo-label">Jumlah Label</label>
                <div class="mt-2 flex items-center gap-3">
                    <input type="number" x-model.number="labelQty" min="1" max="100" class="wo-input wo-native w-24 text-center tabular-nums">
                    <button type="button" @click="printBarcode()" class="btn-primary px-5 py-2.5">Print QR Code</button>
                </div>
            </div>
        </div>
    </div>
</div>


<div x-show="showImportModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="showImportModal = false">
    <div x-show="showImportModal" x-transition.opacity class="fixed inset-0 bg-black/50" @click="showImportModal = false"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div x-show="showImportModal" x-transition class="wo-modal-panel relative w-full max-w-2xl !p-6 sm:!p-8" @click.stop>
            <h3 class="text-lg font-bold text-gray-900">Import data produk</h3>
            <p class="mt-1 text-sm text-gray-500">
                <?php if(\App\Support\ProductMasterCatalog::isWarehouseSharedKind((string) ($mp['catalog_kind'] ?? ''))): ?>
                    Template: Kode Produk, Nama Produk, Kategori, Brand, HPP Speedshop, Harga Jual Cust. Upload stok dilakukan di menu Inventori Produk.
                <?php else: ?>
                    Template: Kode Produk, Nama Produk, Kategori, Brand, HPP, Harga Jual. Upload stok dilakukan di menu Inventori Produk.
                    <?php if($isAllScope ?? false): ?>
                        Filter Semua Speedshop: setiap baris di-insert/update ke semua cabang. Stok tidak diubah dari file ini.
                    <?php endif; ?>
                <?php endif; ?>
            </p>
            <div class="mt-6">
                <p class="wo-label mb-3">Upload File Excel</p>
                <form action="<?php echo e(route($mp['route_import'] ?? 'speedshop.master.produk.import')); ?>" method="POST" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>
                    <label class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-200 bg-gray-50/50 px-8 py-14 transition hover:border-red-300 hover:bg-red-50/30">
                        <svg class="h-14 w-14 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                        <span class="mt-3 text-sm font-medium text-gray-600" x-text="importFileName || 'Klik untuk memilih file .xlsx atau .xls'"></span>
                        <input type="file" name="file" accept=".xlsx,.xls" class="hidden" @change="importFileName = $event.target.files[0]?.name || ''">
                    </label>
                    <div class="mt-6 flex flex-wrap items-center justify-end gap-3 border-t border-gray-100 pt-6">
                        <a href="<?php echo e(route($mp['route_template'] ?? 'speedshop.master.produk.template')); ?>" target="_blank" rel="noopener" class="btn-secondary inline-flex items-center gap-2 border-emerald-200 bg-emerald-50 px-4 py-2.5 text-emerald-700 hover:bg-emerald-100">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            Download Template
                        </a>
                        <button type="submit" class="btn-primary px-5 py-2.5">Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="showModal = false">
    <div x-show="showModal" x-transition.opacity class="fixed inset-0 bg-black/50" @click="showModal = false"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div x-show="showModal" x-transition class="wo-modal-panel relative max-h-[90vh] w-full max-w-lg overflow-y-auto !p-6 sm:!p-8" @click.stop>
            <h3 class="text-lg font-bold text-gray-900" x-show="isEdit"><?php echo e(($mp['is_part_luar'] ?? false) ? 'Edit Part Luar' : 'Edit Produk'); ?></h3>
            <h3 class="text-lg font-bold text-gray-900" x-show="!isEdit"><?php echo e(($mp['is_part_luar'] ?? false) ? 'Tambah Part Luar' : 'Tambah Produk Baru'); ?></h3>
            <?php if($isAllScope ?? false): ?>
            <p class="mt-2 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800">
                Filter Semua Speedshop: perubahan kode supplier, nama, kategori, brand, dan harga berlaku ke semua cabang. Stok tetap diubah per cabang di Inventori.
            </p>
            <?php endif; ?>
            <form @submit.prevent="save()" class="mt-6 space-y-4">
                <div>
                    <label class="wo-label">Kode Produk <span class="text-red-500">*</span></label>
                    <input type="text" x-model="form.kode_produk" :readonly="isEdit" class="wo-input wo-native font-mono" :class="[errors.kode_produk && 'border-red-400', isEdit && 'bg-gray-100']" placeholder="Contoh: SP-BRG-001">
                    <template x-if="errors.kode_produk"><p class="mt-1 text-xs text-red-500" x-text="errors.kode_produk[0]"></p></template>
                </div>
                <div>
                    <label class="wo-label">Kode Supplier</label>
                    <select x-ref="kodeSupplierSelect" id="kode-supplier-select"
                            class="wo-input wo-native"
                            :class="errors.kode_supplier && 'border-red-400'">
                        <option value="">— Pilih supplier —</option>
                        <?php $__currentLoopData = ($suppliers ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $code = trim((string) ($s->kode_supplier ?? ''));
                                $label = $code !== '' ? ($code.' - '.$s->nama) : $s->nama;
                            ?>
                            <?php if($code !== ''): ?>
                                <option value="<?php echo e($code); ?>"><?php echo e($label); ?></option>
                            <?php endif; ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <template x-if="errors.kode_supplier"><p class="mt-1 text-xs text-red-500" x-text="errors.kode_supplier[0]"></p></template>
                </div>
                <div>
                    <label class="wo-label">Nama Produk <span class="text-red-500">*</span></label>
                    <input type="text" x-model="form.nama_produk" class="wo-input wo-native" :class="errors.nama_produk && 'border-red-400'" placeholder="Nama produk">
                    <template x-if="errors.nama_produk"><p class="mt-1 text-xs text-red-500" x-text="errors.nama_produk[0]"></p></template>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="wo-label">Kategori <span class="text-red-500">*</span></label>
                        <select x-model="form.category_id" class="wo-input wo-native" :class="errors.category_id && 'border-red-400'">
                            <option value="">Pilih Kategori</option>
                            <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($c->id); ?>"><?php echo e($c->nama); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <template x-if="errors.category_id"><p class="mt-1 text-xs text-red-500" x-text="errors.category_id[0]"></p></template>
                    </div>
                    <?php if(empty($mp['hide_brand_field'])): ?>
                    <div>
                        <label class="wo-label">Brand</label>
                        <select x-model="form.brand_id" class="wo-input wo-native">
                            <option value="">Pilih Brand</option>
                            <?php $__currentLoopData = $brands; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($b->id); ?>"><?php echo e($b->nama); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <?php else: ?>
                    <div>
                        <label class="wo-label">Jenis</label>
                        <input type="text" class="wo-input wo-native bg-gray-100" value="Part Luar" readonly>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <?php if(\App\Support\ProductMasterCatalog::isWarehouseSharedKind((string) ($mp['catalog_kind'] ?? ''))): ?>
                    <div>
                        <label class="wo-label">HPP Speedshop (Rp)</label>
                        <input type="number" min="0" step="1" x-model="form.harga_jual_speedshop" class="wo-input wo-native tabular-nums" placeholder="0">
                        <template x-if="errors.harga_jual_speedshop"><p class="mt-1 text-xs text-red-500" x-text="errors.harga_jual_speedshop[0]"></p></template>
                    </div>
                    <div>
                        <label class="wo-label">Harga Jual Cust (Rp)</label>
                        <input type="number" min="0" step="1" x-model="form.harga_eceran_terendah" class="wo-input wo-native tabular-nums" placeholder="0">
                        <template x-if="errors.harga_eceran_terendah"><p class="mt-1 text-xs text-red-500" x-text="errors.harga_eceran_terendah[0]"></p></template>
                    </div>
                    <?php else: ?>
                    <div>
                        <label class="wo-label">HPP</label>
                        <input type="number" min="0" step="1" x-model="form.hpp" class="wo-input wo-native tabular-nums" placeholder="0">
                        <template x-if="errors.hpp"><p class="mt-1 text-xs text-red-500" x-text="errors.hpp[0]"></p></template>
                    </div>
                    <div>
                        <label class="wo-label">Harga Jual (Rp)</label>
                        <input type="number" min="0" step="1" x-model="form.harga_jual_speedshop" class="wo-input wo-native tabular-nums" placeholder="0">
                        <template x-if="errors.harga_jual_speedshop"><p class="mt-1 text-xs text-red-500" x-text="errors.harga_jual_speedshop[0]"></p></template>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-6">
                    <button type="button" @click="showModal = false" class="btn-secondary px-6 py-2.5">Batal</button>
                    <button type="submit" :disabled="loading" class="btn-primary px-6 py-2.5 disabled:opacity-50">
                        <span x-show="!loading" x-text="isEdit ? 'Update' : 'Simpan'"></span>
                        <span x-show="loading">Menyimpan...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<div x-show="showMoveModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="showMoveModal = false">
    <div x-show="showMoveModal" x-transition.opacity class="fixed inset-0 bg-black/50" @click="showMoveModal = false"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div x-show="showMoveModal" x-transition class="wo-modal-panel relative w-full max-w-lg !p-6 sm:!p-8" @click.stop>
            <h3 class="text-lg font-bold text-gray-900">Pindah Katalog</h3>
            <p class="mt-1 text-sm text-gray-500">Stok & riwayat transaksi tetap pada produk yang sama.</p>
            <form @submit.prevent="submitMoveCatalog()" class="mt-6 space-y-4">
                <div>
                    <label class="wo-label">Kode Produk</label>
                    <input type="text" :value="moveForm.kode" readonly class="wo-input wo-native bg-gray-100 font-mono">
                </div>
                <div>
                    <label class="wo-label">Dari Katalog</label>
                    <input type="text" :value="moveForm.from_label" readonly class="wo-input wo-native bg-gray-100">
                </div>
                <div>
                    <label class="wo-label">Ke Katalog <span class="text-red-500">*</span></label>
                    <select x-model="moveForm.target_kind" class="wo-input wo-native" :class="moveError && 'border-red-400'">
                        <option value="">Pilih katalog tujuan</option>
                        <?php $__currentLoopData = ($mp['catalog_labels'] ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $kind => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php if($kind !== ($mp['catalog_kind'] ?? '')): ?>
                                <option value="<?php echo e($kind); ?>"><?php echo e($label); ?></option>
                            <?php endif; ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <template x-if="moveError"><p class="mt-1 text-xs text-red-500" x-text="moveError"></p></template>
                </div>
                <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-6">
                    <button type="button" @click="showMoveModal = false" class="btn-secondary px-6 py-2.5">Batal</button>
                    <button type="submit" :disabled="moveLoading" class="btn-primary px-6 py-2.5 disabled:opacity-50">
                        <span x-show="!moveLoading">Pindahkan</span>
                        <span x-show="moveLoading">Memindahkan...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php /**PATH /Users/wahyusafrizal/work/Github/77racingstore/resources/views/speedshop/master-data/partials/produk-modals.blade.php ENDPATH**/ ?>