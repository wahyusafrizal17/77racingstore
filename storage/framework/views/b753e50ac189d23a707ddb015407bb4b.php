<?php
    $filterBaseClass = 'h-8 w-full rounded-lg border border-gray-200 bg-white px-2.5 text-[13px] font-normal text-gray-700 placeholder-gray-400 outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/40';
    $canManageSuppliers = auth()->user()->isSpeedshop() || auth()->user()->isAccounting();
    $exportQuery = request()->only(['kode_supplier', 'nama', 'no_hp', 'email', 'kota', 'alamat', 'rekening', 'search']);
    $supplierStats = $supplierStats ?? ['total' => 0, 'punya_no_hp' => 0, 'punya_rekening' => 0];
    $hasActiveFilters = collect($exportQuery)->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
    $emptyColspan = $canManageSuppliers ? 9 : 8;
?>

<?php $__env->startSection('title', 'Inventori — Supplier'); ?>

<?php $__env->startSection('content'); ?>
<?php $__env->startPush('styles'); ?>
<?php echo $__env->make('partials.speedshop.dashboard-load-animations', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('partials.speedshop.dashboard-manager-template-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('partials.speedshop.work-order-create-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<style>
.ss-dashboard .sp-summary-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 20px;
}
@media (max-width: 900px) {
  .ss-dashboard .sp-summary-grid { grid-template-columns: 1fr; }
}
.ss-dashboard .pay-icon.cyan { background: rgba(6,182,212,0.10); color: #0891B2; }
.ss-dashboard .pay-icon.teal { background: rgba(20,184,166,0.10); color: #14B8A6; }
.ss-dashboard .pay-icon.amber { background: rgba(245,158,11,0.10); color: #D97706; }
</style>
<?php $__env->stopPush(); ?>

<div
    class="ss-dashboard ss-dash-page ss-wo-create"
    x-data="suppliersCrud(<?php echo e($canManageSuppliers ? 'true' : 'false'); ?>)"
    x-init="$nextTick(() => { requestAnimationFrame(() => $el.classList.add('is-ready')); })"
>
<div class="ss-dash-content space-y-5">
    

    <div class="sp-summary-grid ss-dash-enter" style="--ss-delay: 80ms">
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 0">
            <div class="pay-card-top">
                <span class="pay-card-label">Total Supplier</span>
                <span class="pay-icon cyan">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </span>
            </div>
            <div class="pay-amount"><?php echo e(number_format($supplierStats['total'], 0, ',', '.')); ?></div>
            <p class="pay-period">Terdaftar</p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 1">
            <div class="pay-card-top">
                <span class="pay-card-label">Punya No HP</span>
                <span class="pay-icon teal">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                </span>
            </div>
            <div class="pay-amount"><?php echo e(number_format($supplierStats['punya_no_hp'], 0, ',', '.')); ?></div>
            <p class="pay-period">Kontak lengkap</p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 2">
            <div class="pay-card-top">
                <span class="pay-card-label">Punya Rekening</span>
                <span class="pay-icon amber">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                </span>
            </div>
            <div class="pay-amount"><?php echo e(number_format($supplierStats['punya_rekening'], 0, ',', '.')); ?></div>
            <p class="pay-period">Data pembayaran</p>
        </div>
    </div>

    <div class="panel-card panel-card-flush ss-dash-enter" style="--ss-delay: 120ms">
        <div class="flex flex-col gap-3 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">Daftar Supplier</h2>
                <p class="mt-1 text-[13px] text-gray-500">
                    Filter kolom memuat ulang otomatis saat nilai diubah.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="<?php echo e(route('speedshop.suppliers.export', $exportQuery)); ?>" class="btn-export">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Export Excel
                </a>
                <a href="<?php echo e(route('speedshop.suppliers.pdf', $exportQuery)); ?>" target="_blank" rel="noopener noreferrer" class="btn-export-pdf">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Export PDF
                </a>
                <?php if($canManageSuppliers): ?>
                <button type="button" @click="showImportModal = true" class="btn-secondary inline-flex h-10 items-center gap-2 border-indigo-200 bg-indigo-50 px-4 text-indigo-700 hover:bg-indigo-100">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Import Excel
                </button>
                <button type="button" @click="openCreate()" class="btn-primary inline-flex h-10 items-center gap-2 px-5">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Tambah Supplier
                </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="overflow-x-auto">
            <form method="GET">
                <table class="w-full min-w-max text-left text-sm">
                    <thead>
                        <tr class="bg-[#f7f3f4]">
                            <th class="w-16 px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No.</th>
                            <th class="min-w-[100px] px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Kode</th>
                            <th class="min-w-[160px] px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Nama Supplier</th>
                            <th class="min-w-[120px] px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No. HP</th>
                            <th class="min-w-[160px] px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Email</th>
                            <th class="min-w-[120px] px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Kota</th>
                            <th class="min-w-[180px] px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Alamat</th>
                            <th class="min-w-[160px] px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Rekening</th>
                            <?php if($canManageSuppliers): ?>
                            <th class="w-28 px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500"></th>
                            <?php endif; ?>
                        </tr>
                        <tr class="border-t border-gray-200 bg-[#fbf8f9]">
                            <th class="px-5 py-2"></th>
                            <th class="px-5 py-2">
                                <input type="text" name="kode_supplier" value="<?php echo e(request('kode_supplier')); ?>" placeholder="Kode…" class="<?php echo e($filterBaseClass); ?>" onchange="this.form.submit()">
                            </th>
                            <th class="px-5 py-2">
                                <input type="text" name="nama" value="<?php echo e(request('nama', request('search'))); ?>" placeholder="Nama…" class="<?php echo e($filterBaseClass); ?>" onchange="this.form.submit()">
                            </th>
                            <th class="px-5 py-2">
                                <input type="text" name="no_hp" value="<?php echo e(request('no_hp')); ?>" placeholder="No. HP…" class="<?php echo e($filterBaseClass); ?>" onchange="this.form.submit()">
                            </th>
                            <th class="px-5 py-2">
                                <input type="text" name="email" value="<?php echo e(request('email')); ?>" placeholder="Email…" class="<?php echo e($filterBaseClass); ?>" onchange="this.form.submit()">
                            </th>
                            <th class="px-5 py-2">
                                <input type="text" name="kota" value="<?php echo e(request('kota')); ?>" placeholder="Kota…" class="<?php echo e($filterBaseClass); ?>" onchange="this.form.submit()">
                            </th>
                            <th class="px-5 py-2">
                                <input type="text" name="alamat" value="<?php echo e(request('alamat')); ?>" placeholder="Alamat…" class="<?php echo e($filterBaseClass); ?>" onchange="this.form.submit()">
                            </th>
                            <th class="px-5 py-2">
                                <input type="text" name="rekening" value="<?php echo e(request('rekening')); ?>" placeholder="Rekening…" class="<?php echo e($filterBaseClass); ?>" onchange="this.form.submit()">
                            </th>
                            <?php if($canManageSuppliers): ?>
                            <th class="px-5 py-2"></th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    <?php $__empty_1 = true; $__currentLoopData = $suppliers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $supplier): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="transition hover:bg-[#fcfafb]">
                        <td class="px-5 py-4 tabular-nums text-gray-500"><?php echo e($suppliers->firstItem() + $i); ?></td>
                        <td class="px-5 py-4 font-mono text-sm font-semibold text-gray-700"><?php echo e($supplier->kode_supplier ?? '—'); ?></td>
                        <td class="px-5 py-4 font-semibold text-gray-900"><?php echo e($supplier->nama); ?></td>
                        <td class="px-5 py-4 text-gray-700"><?php echo e($supplier->no_hp ?? '—'); ?></td>
                        <td class="px-5 py-4 text-gray-700"><?php echo e($supplier->email ?? '—'); ?></td>
                        <td class="px-5 py-4">
                            <?php echo $__env->make('partials.supplier-kota-badge', ['supplier' => $supplier], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </td>
                        <td class="max-w-xs px-5 py-4 text-gray-700">
                            <?php if($supplier->alamat): ?>
                                <span class="line-clamp-2" title="<?php echo e(e($supplier->alamat)); ?>"><?php echo e($supplier->alamat); ?></span>
                            <?php else: ?>
                                <span class="text-gray-400">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="max-w-xs px-5 py-4 text-xs text-gray-700">
                            <?php if($supplier->jenis_rekening || $supplier->nama_rekening || $supplier->nomor_rekening): ?>
                                <span class="block font-medium"><?php echo e($supplier->jenis_rekening ?? '—'); ?></span>
                                <span class="block"><?php echo e($supplier->nama_rekening ?? '—'); ?></span>
                                <span class="block tabular-nums"><?php echo e($supplier->nomor_rekening ?? '—'); ?></span>
                            <?php else: ?>
                                <span class="text-gray-400">—</span>
                            <?php endif; ?>
                        </td>
                        <?php if($canManageSuppliers): ?>
                        <td class="px-5 py-4">
                            <div class="flex items-center justify-end gap-2">
                                <button type="button" @click.prevent="openEdit(<?php echo e($supplier->id); ?>)" class="wo-icon-btn bg-gray-600 text-white hover:bg-gray-700" title="Edit">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                </button>
                                <button type="button" @click.prevent="destroy(<?php echo e($supplier->id); ?>)" class="wo-icon-btn bg-red-600 text-white hover:bg-red-700" title="Hapus">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="<?php echo e($emptyColspan); ?>" class="px-5 py-12 text-center text-gray-400">
                            <?php if($hasActiveFilters): ?>
                                Tidak ada supplier sesuai filter.
                            <?php else: ?>
                                <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                <p class="mt-3 text-sm font-medium text-gray-700">Belum ada data supplier</p>
                                <?php if($canManageSuppliers): ?>
                                <p class="mt-1 text-xs">Klik Tambah Supplier untuk memulai</p>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </form>
        </div>

        <?php if($suppliers->hasPages()): ?>
        <div class="border-t border-gray-100 px-5 py-4"><?php echo e($suppliers->links()); ?></div>
        <?php endif; ?>
    </div>
</div>

<div x-show="canManage && showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="showModal = false">
    <div x-show="canManage && showModal" x-transition.opacity class="fixed inset-0 bg-black/50" @click="showModal = false"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div x-show="canManage && showModal" x-transition class="wo-modal-panel relative w-full max-w-lg !p-6 sm:!p-8" @click.stop>
            <h3 class="text-lg font-bold text-gray-900" x-text="isEdit ? 'Edit Supplier' : 'Tambah Supplier Baru'"></h3>
            <div class="mt-6 space-y-4">
                <div>
                    <label class="wo-label">Kode Supplier</label>
                    <input type="text" x-model="form.kode_supplier" minlength="2" maxlength="3" class="wo-input wo-native uppercase" :class="errors.kode_supplier && 'border-red-400'">
                    <template x-if="errors.kode_supplier"><p class="mt-1 text-xs text-red-500" x-text="errors.kode_supplier[0]"></p></template>
                </div>
                <div>
                    <label class="wo-label">Nama Supplier <span class="text-red-500">*</span></label>
                    <input type="text" x-model="form.nama" class="wo-input wo-native" :class="errors.nama && 'border-red-400'">
                    <template x-if="errors.nama"><p class="mt-1 text-xs text-red-500" x-text="errors.nama[0]"></p></template>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="wo-label">No. HP</label>
                        <input type="text" x-model="form.no_hp" class="wo-input wo-native">
                    </div>
                    <div>
                        <label class="wo-label">Email</label>
                        <input type="email" x-model="form.email" class="wo-input wo-native" :class="errors.email && 'border-red-400'">
                        <template x-if="errors.email"><p class="mt-1 text-xs text-red-500" x-text="errors.email[0]"></p></template>
                    </div>
                </div>
                <div>
                    <label class="wo-label">Kota</label>
                    <select x-model="form.kota_id" class="wo-input wo-native">
                        <option value="">Pilih Kota</option>
                        <?php $__currentLoopData = $kotas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $kota): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($kota->id); ?>"><?php echo e($kota->nama); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="wo-label">Jenis Rekening</label>
                        <input type="text" x-model="form.jenis_rekening" placeholder="Contoh: BCA, Mandiri" class="wo-input wo-native">
                    </div>
                    <div>
                        <label class="wo-label">Nama Rekening</label>
                        <input type="text" x-model="form.nama_rekening" class="wo-input wo-native">
                    </div>
                </div>
                <div>
                    <label class="wo-label">Nomor Rekening</label>
                    <input type="text" x-model="form.nomor_rekening" class="wo-input wo-native tabular-nums">
                </div>
                <div>
                    <label class="wo-label">Alamat</label>
                    <textarea x-model="form.alamat" rows="2" class="wo-input wo-native min-h-[4.5rem] resize-y"></textarea>
                </div>
            </div>
            <div class="mt-8 flex items-center justify-end gap-3">
                <button type="button" @click="showModal = false" class="btn-secondary px-6 py-2.5">Batal</button>
                <button type="button" @click="save()" :disabled="loading" class="btn-primary px-6 py-2.5 disabled:opacity-50">
                    <span x-show="!loading" x-text="isEdit ? 'Update' : 'Simpan'"></span>
                    <span x-show="loading">Menyimpan...</span>
                </button>
            </div>
        </div>
    </div>
</div>

<div x-show="canManage && showImportModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="showImportModal = false">
    <div x-show="canManage && showImportModal" x-transition.opacity class="fixed inset-0 bg-black/50" @click="showImportModal = false"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div x-show="canManage && showImportModal" x-transition class="wo-modal-panel relative w-full max-w-2xl !p-6 sm:!p-8" @click.stop>
            <h3 class="text-lg font-bold text-gray-900">Import Supplier</h3>
            <p class="mt-1 text-sm text-gray-500">Upload file <strong>.xlsx</strong> dari template atau dari <strong>Export Excel</strong>. Kode supplier 2–3 huruf; nama wajib diisi.</p>
            <div class="mt-6">
                <p class="wo-label mb-3">Upload File Excel</p>
                <form action="<?php echo e(route('speedshop.suppliers.import')); ?>" method="POST" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>
                    <label class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-200 bg-gray-50/50 px-8 py-14 transition hover:border-red-300 hover:bg-red-50/30">
                        <svg class="h-14 w-14 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                        <span class="mt-3 text-sm font-medium text-gray-600" x-text="importFileName || 'Klik untuk memilih file .xlsx atau .xls'"></span>
                        <input type="file" name="file" accept=".xlsx,.xls" class="hidden" @change="importFileName = $event.target.files[0]?.name || ''">
                    </label>
                    <div class="mt-6 flex flex-wrap items-center justify-end gap-3 border-t border-gray-100 pt-6">
                        <a href="<?php echo e(route('speedshop.suppliers.template')); ?>" class="btn-secondary inline-flex items-center gap-2 border-emerald-200 bg-emerald-50 px-4 py-2.5 text-emerald-700 hover:bg-emerald-100">
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
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
function suppliersCrud(canManage) {
    return {
        canManage: !!canManage,
        showImportModal: false,
        importFileName: '',
        showModal: false, isEdit: false, editId: null, loading: false, errors: {},
        form: { kode_supplier: '', nama: '', no_hp: '', email: '', alamat: '', kota_id: '', jenis_rekening: '', nama_rekening: '', nomor_rekening: '' },
        openCreate() {
            if (!this.canManage) return;
            this.isEdit = false;
            this.editId = null;
            this.errors = {};
            this.form = { kode_supplier: '', nama: '', no_hp: '', email: '', alamat: '', kota_id: '', jenis_rekening: '', nama_rekening: '', nomor_rekening: '' };
            this.showModal = true;
        },
        async openEdit(id) {
            if (!this.canManage) return;
            this.isEdit = true;
            this.editId = id;
            this.errors = {};
            this.loading = true;
            this.showModal = true;
            const res = await fetch(`<?php echo e(url('speedshop/suppliers')); ?>/${id}`, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            this.form = {
                kode_supplier: data.kode_supplier || '',
                nama: data.nama,
                no_hp: data.no_hp || '',
                email: data.email || '',
                alamat: data.alamat || '',
                kota_id: data.kota_id ? String(data.kota_id) : '',
                jenis_rekening: data.jenis_rekening || '',
                nama_rekening: data.nama_rekening || '',
                nomor_rekening: data.nomor_rekening || ''
            };
            this.loading = false;
        },
        async save() {
            if (!this.canManage) return;
            this.loading = true;
            this.errors = {};
            const url = this.isEdit ? `<?php echo e(url('speedshop/suppliers')); ?>/${this.editId}` : '<?php echo e(route("speedshop.suppliers.store")); ?>';
            const res = await fetch(url, {
                method: this.isEdit ? 'PUT' : 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(this.form)
            });
            if (!res.ok) {
                const d = await res.json().catch(() => ({}));
                this.errors = d.errors || {};
                this.loading = false;
                return;
            }
            window.location.reload();
        },
        async destroy(id) {
            if (!this.canManage) return;
            const result = await Swal.fire({
                title: 'Hapus supplier?',
                text: 'Data yang dihapus tidak dapat dikembalikan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            });
            if (!result.isConfirmed) return;
            await fetch(`<?php echo e(url('speedshop/suppliers')); ?>/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
            window.location.reload();
        }
    }
}
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.speedshop', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/wahyusafrizal/work/Github/77racingstore/resources/views/speedshop/suppliers/index.blade.php ENDPATH**/ ?>