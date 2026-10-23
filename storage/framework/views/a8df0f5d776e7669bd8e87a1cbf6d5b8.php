<?php
    $filterBaseClass = 'h-8 w-full rounded-lg border border-gray-200 bg-white px-2.5 text-[13px] font-normal text-gray-700 placeholder-gray-400 outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/40';
    $exportQuery = request()->only(['nama', 'no_hp', 'level']);
    $mekanikStats = $mekanikStats ?? ['total' => 0, 'aktif' => 0, 'level_count' => 0];
    $hasActiveFilters = request()->filled('nama') || request()->filled('no_hp') || request()->filled('level');
?>

<?php $__env->startSection('title', 'Master Data — Mekanik'); ?>

<?php $__env->startSection('content'); ?>
<?php $__env->startPush('styles'); ?>
<?php echo $__env->make('partials.speedshop.dashboard-load-animations', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('partials.speedshop.dashboard-manager-template-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('partials.speedshop.work-order-create-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<style>
.ss-dashboard .mk-summary-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 20px;
}
@media (max-width: 900px) {
  .ss-dashboard .mk-summary-grid { grid-template-columns: 1fr; }
}
.ss-dashboard .pay-icon.cyan { background: rgba(6,182,212,0.10); color: #0891B2; }
.ss-dashboard .pay-icon.teal { background: rgba(20,184,166,0.10); color: #14B8A6; }
.ss-dashboard .pay-icon.amber { background: rgba(245,158,11,0.10); color: #D97706; }
</style>
<?php $__env->stopPush(); ?>

<div
    class="ss-dashboard ss-dash-page ss-wo-create"
    x-data="mekanikCrud()"
    x-init="$nextTick(() => { requestAnimationFrame(() => $el.classList.add('is-ready')); })"
>
<div class="ss-dash-content space-y-5">
    

    <div class="mk-summary-grid ss-dash-enter" style="--ss-delay: 80ms">
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 0">
            <div class="pay-card-top">
                <span class="pay-card-label">Total Mekanik</span>
                <span class="pay-icon cyan">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </span>
            </div>
            <div class="pay-amount"><?php echo e(number_format($mekanikStats['total'], 0, ',', '.')); ?></div>
            <p class="pay-period">Terdaftar</p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 1">
            <div class="pay-card-top">
                <span class="pay-card-label">Status Aktif</span>
                <span class="pay-icon teal">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="pay-amount"><?php echo e(number_format($mekanikStats['aktif'], 0, ',', '.')); ?></div>
            <p class="pay-period">Siap assign WO</p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 2">
            <div class="pay-card-top">
                <span class="pay-card-label">Variasi Level</span>
                <span class="pay-icon amber">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                </span>
            </div>
            <div class="pay-amount"><?php echo e(number_format($mekanikStats['level_count'], 0, ',', '.')); ?></div>
            <p class="pay-period">Level berbeda</p>
        </div>
    </div>

    <div class="panel-card panel-card-flush ss-dash-enter" style="--ss-delay: 120ms">
        <div class="flex flex-col gap-3 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">Daftar Mekanik</h2>
                <p class="mt-1 text-[13px] text-gray-500">
                    Data mekanik dikelola sebagai karyawan Speedshop (role Mekanik) di Admin → Karyawan. Halaman ini hanya untuk lihat & export.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="<?php echo e(route('speedshop.mekaniks.export', $exportQuery)); ?>" class="btn-export">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Export Excel
                </a>
                <a href="<?php echo e(route('speedshop.mekaniks.pdf', $exportQuery)); ?>" target="_blank" rel="noopener noreferrer" class="btn-export-pdf">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Export PDF
                </a>
            </div>
        </div>

        <?php if(empty($levels)): ?>
        <div class="mx-5 mb-4 mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            Level mekanik belum dibuat. Buat level terlebih dahulu di
            <a href="<?php echo e(route('speedshop.mekanik-levels.index')); ?>" class="font-semibold text-red-700 underline hover:text-red-800">Kelola Level</a>.
        </div>
        <?php endif; ?>

        <div class="overflow-x-auto">
            <form method="GET">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="bg-[#f7f3f4]">
                            <th class="w-16 px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No.</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Nama</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No HP</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Level</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Cabang</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Email login</th>
                        </tr>
                        <tr class="border-t border-gray-200 bg-[#fbf8f9]">
                            <th class="px-5 py-2"></th>
                            <th class="px-5 py-2">
                                <input name="nama" value="<?php echo e(request('nama')); ?>" placeholder="Cari nama…" class="<?php echo e($filterBaseClass); ?>" onchange="this.form.submit()">
                            </th>
                            <th class="px-5 py-2">
                                <input name="no_hp" value="<?php echo e(request('no_hp')); ?>" placeholder="Cari no HP…" class="<?php echo e($filterBaseClass); ?>" onchange="this.form.submit()">
                            </th>
                            <th class="px-5 py-2">
                                <select name="level" class="<?php echo e($filterBaseClass); ?>" onchange="this.form.submit()">
                                    <option value="">Semua</option>
                                    <?php $__currentLoopData = $levels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $level): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($level); ?>" <?php if(request('level') === $level): echo 'selected'; endif; ?>><?php echo e($level); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </th>
                            <th class="px-5 py-2"></th>
                            <th class="px-5 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    <?php $__empty_1 = true; $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="transition hover:bg-[#fcfafb]">
                        <td class="px-5 py-4 tabular-nums text-gray-500"><?php echo e($items->firstItem() + $i); ?></td>
                        <td class="px-5 py-4 font-semibold text-gray-900"><?php echo e($item->nama); ?></td>
                        <td class="px-5 py-4 text-gray-700"><?php echo e($item->no_hp ?: '—'); ?></td>
                        <td class="px-5 py-4">
                            <?php if($item->levelName()): ?>
                                <span class="inline-flex items-center rounded-full border border-teal-200 bg-teal-50 px-2.5 py-1 text-xs font-semibold text-teal-700"><?php echo e($item->levelName()); ?></span>
                            <?php else: ?>
                                <span class="text-gray-400">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-5 py-4 text-gray-700"><?php echo e($item->speedshop?->nama ?? '—'); ?></td>
                        <td class="px-5 py-4 text-gray-700"><?php echo e($item->user?->email ?? '—'); ?></td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="7" class="px-5 py-12 text-center text-gray-400">
                            <?php if($hasActiveFilters): ?>
                                Tidak ada mekanik sesuai filter.
                            <?php else: ?>
                                <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <p class="mt-3 text-sm font-medium text-gray-700">Belum ada data mekanik</p>
                                <p class="mt-1 text-xs">Tambah mekanik baru untuk assign work order</p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </form>
        </div>

        <?php if($items->hasPages()): ?>
        <div class="border-t border-gray-100 px-5 py-4"><?php echo e($items->links()); ?></div>
        <?php endif; ?>
    </div>
</div>

<div x-show="showImportModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="showImportModal = false">
    <div x-show="showImportModal" x-transition.opacity class="fixed inset-0 bg-black/50" @click="showImportModal = false"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div x-show="showImportModal" x-transition class="wo-modal-panel relative w-full max-w-2xl !p-6 sm:!p-8" @click.stop>
            <h3 class="text-lg font-bold text-gray-900">Import Mekanik</h3>
            <p class="mt-1 text-sm text-gray-500">Download template Excel, isi data, lalu upload file untuk import.</p>
            <div class="mt-6">
                <p class="wo-label mb-3">Upload File Excel</p>
                <form action="<?php echo e(route('speedshop.mekaniks.import')); ?>" method="POST" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>
                    <label class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-200 bg-gray-50/50 px-8 py-14 transition hover:border-red-300 hover:bg-red-50/30">
                        <svg class="h-14 w-14 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                        <span class="mt-3 text-sm font-medium text-gray-600" x-text="importFileName || 'Klik untuk memilih file .xlsx atau .xls'"></span>
                        <input type="file" name="file" accept=".xlsx,.xls" class="hidden" @change="importFileName = $event.target.files[0]?.name || ''">
                    </label>
                    <div class="mt-6 flex flex-wrap items-center justify-end gap-3 border-t border-gray-100 pt-6">
                        <a href="<?php echo e(route('speedshop.mekaniks.template')); ?>" target="_blank" rel="noopener" class="btn-secondary inline-flex items-center gap-2 border-emerald-200 bg-emerald-50 px-4 py-2.5 text-emerald-700 hover:bg-emerald-100">
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
        <div x-show="showModal" x-transition class="wo-modal-panel relative w-full max-w-md !p-6 sm:!p-8" @click.stop>
            <h3 class="text-lg font-bold text-gray-900" x-text="isEdit ? 'Edit Mekanik' : 'Tambah Mekanik'"></h3>
            <div class="mt-6 space-y-4">
                <div>
                    <label class="wo-label">Nama <span class="text-red-500">*</span></label>
                    <input type="text" x-model="form.nama" class="wo-input wo-native" :class="errors.nama && 'border-red-400'">
                    <template x-if="errors.nama"><p class="mt-1 text-xs text-red-500" x-text="errors.nama[0]"></p></template>
                </div>
                <div>
                    <label class="wo-label">No HP</label>
                    <input type="text" x-model="form.no_hp" class="wo-input wo-native" :class="errors.no_hp && 'border-red-400'">
                    <template x-if="errors.no_hp"><p class="mt-1 text-xs text-red-500" x-text="errors.no_hp[0]"></p></template>
                </div>
                <div>
                    <label class="wo-label">Level <span class="text-red-500">*</span></label>
                    <select x-model="form.level" class="wo-input wo-native" :class="errors.level && 'border-red-400'">
                        <?php $__currentLoopData = $levels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $level): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($level); ?>"><?php echo e($level); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <template x-if="errors.level"><p class="mt-1 text-xs text-red-500" x-text="errors.level[0]"></p></template>
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
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
function mekanikCrud() {
    return {
        showImportModal: false,
        importFileName: '',
        showModal: false, isEdit: false, editId: null, loading: false, errors: {},
        form: { nama: '', no_hp: '', level: <?php echo json_encode($levels[0] ?? null, 15, 512) ?> },
        openCreate() {
            this.isEdit = false;
            this.editId = null;
            this.errors = {};
            this.form = { nama: '', no_hp: '', level: <?php echo json_encode($levels[0] ?? null, 15, 512) ?> };
            this.showModal = true;
        },
        async openEdit(id) {
            this.isEdit = true;
            this.editId = id;
            this.errors = {};
            this.loading = true;
            this.showModal = true;
            const res = await fetch(`<?php echo e(url('speedshop/mekaniks')); ?>/${id}`, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            this.form = {
                nama: data.nama || '',
                no_hp: data.no_hp || '',
                level: data.level || <?php echo json_encode($levels[0] ?? null, 15, 512) ?>,
            };
            this.loading = false;
        },
        async save() {
            this.loading = true;
            this.errors = {};
            const url = this.isEdit ? `<?php echo e(url('speedshop/mekaniks')); ?>/${this.editId}` : '<?php echo e(route('speedshop.mekaniks.store')); ?>';
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
            const result = await Swal.fire({
                title: 'Hapus mekanik?',
                text: 'Data yang dihapus tidak dapat dikembalikan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            });
            if (!result.isConfirmed) return;
            const res = await fetch(`<?php echo e(url('speedshop/mekaniks')); ?>/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
            if (!res.ok) {
                const d = await res.json().catch(() => ({}));
                await Swal.fire('Gagal', d.message || 'Mekanik tidak dapat dihapus.', 'warning');
                return;
            }
            window.location.reload();
        }
    }
}
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.speedshop', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/wahyusafrizal/work/Github/77racingstore/resources/views/speedshop/mekaniks/index.blade.php ENDPATH**/ ?>