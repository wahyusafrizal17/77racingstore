<?php
    use App\Support\ProductMasterCatalog;

    $mp = array_merge([
        'jenis' => 'ori',
        'is_part_luar' => false,
        'title' => 'Database Produk',
        'list_title' => 'Daftar Produk',
        'list_subtitle' => 'Filter kode, nama, kategori, atau brand memuat ulang otomatis saat nilai diubah.',
        'summary_label' => 'Total Produk',
        'base_url' => url('speedshop/master/produk'),
        'route_index' => 'speedshop.master.produk',
        'route_export' => 'speedshop.master.produk.export',
        'route_pdf' => 'speedshop.master.produk.pdf',
        'route_bulk_archive' => 'speedshop.master.produk.bulk-archive',
        'route_import' => 'speedshop.master.produk.import',
        'route_template' => 'speedshop.master.produk.template',
        'selection_storage_key' => 'speedshop_master_produk_selected_',
        'hide_import_export' => false,
        'hide_brand_field' => false,
    ], $mp ?? []);
    $isWarehouseSharedCatalog = ProductMasterCatalog::isWarehouseSharedKind((string) ($mp['catalog_kind'] ?? ''));
    $isAllScope = (bool) ($isAllScope ?? false);
    $canUploadBrandLogo = (auth()->user()?->canManageProductMasterCatalog() ?? false)
        || (auth()->user()?->isManager() ?? false)
        || (auth()->user()?->canUploadMoProductImages() ?? false);
    $browseBrands = $browseBrands ?? false;
    $selectedBrand = $selectedBrand ?? null;
    $unbranded = $unbranded ?? false;
    $brandFolders = $brandFolders ?? collect();
    $unbrandedCount = (int) ($unbrandedCount ?? 0);
    $brandLabel = $unbranded ? 'Tanpa Brand' : ($selectedBrand->nama ?? '');
    $defaultFolderIcon = asset('images/brand-folder-default.png');
    $filterBaseClass = 'h-8 w-full rounded-lg border border-gray-200 bg-white px-2.5 text-[13px] font-normal text-gray-700 placeholder-gray-400 outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/40';
    $exportQuery = array_filter([
        'kode' => request('kode'),
        'nama' => request('nama'),
        'category_id' => request('category_id'),
        'brand_id' => $selectedBrand?->id,
        'unbranded' => $unbranded ? 1 : null,
        'search' => request('search'),
    ], fn ($v) => $v !== null && $v !== '');
    $hasActiveFilters = request()->filled('kode') || request()->filled('nama') || request()->filled('category_id') || request()->filled('search');
    $pricePercents = array_merge(['jual' => ''], $pricePercents ?? []);
    $canSetPricePercent = ($selectedBrand || $unbranded);
    $pricePercentMeta = $isWarehouseSharedCatalog
        ? ['field' => 'harga_eceran_terendah', 'target' => 'Harga Jual Cust', 'base' => 'HPP Speedshop']
        : ['field' => 'harga_jual_speedshop', 'target' => 'Harga Jual', 'base' => 'HPP'];
?>

<?php $__env->startSection('title', $mp['title']); ?>

<?php $__env->startSection('content'); ?>
<?php $__env->startPush('styles'); ?>
<?php echo $__env->make('partials.speedshop.dashboard-load-animations', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('partials.speedshop.dashboard-manager-template-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('partials.speedshop.work-order-create-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<style>
.ss-dashboard .mp-summary-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 20px;
}
@media (max-width: 900px) {
  .ss-dashboard .mp-summary-grid { grid-template-columns: 1fr; }
}
.ss-dashboard .pay-icon.cyan { background: rgba(6,182,212,0.10); color: #0891B2; }
.ss-dashboard .pay-icon.teal { background: rgba(20,184,166,0.10); color: #14B8A6; }
.ss-dashboard .pay-icon.violet { background: rgba(139,92,246,0.10); color: #7C3AED; }
.ss-dashboard .mp-brand-toolbar {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px 22px 4px;
}
.ss-dashboard .mp-brand-search {
  position: relative;
  width: min(100%, 320px);
}
.ss-dashboard .mp-brand-search svg {
  position: absolute;
  left: 12px;
  top: 50%;
  width: 16px;
  height: 16px;
  color: #9aa0a6;
  transform: translateY(-50%);
  pointer-events: none;
}
.ss-dashboard .mp-brand-search input {
  height: 40px;
  width: 100%;
  border: 1px solid rgba(13,13,13,0.08);
  border-radius: 12px;
  background: #f7f8fa;
  padding: 0 14px 0 38px;
  font-size: 13px;
  color: #0d0d0d;
  outline: none;
  transition: border-color .15s, background .15s, box-shadow .15s;
}
.ss-dashboard .mp-brand-search input::placeholder { color: #9aa0a6; }
.ss-dashboard .mp-brand-search input:focus {
  background: #fff;
  border-color: rgba(140,250,1,0.28);
  box-shadow: 0 0 0 4px rgba(140,250,1,0.08);
}
.ss-dashboard .mp-brand-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(176px, 1fr));
  gap: 16px;
  padding: 16px 22px 24px;
}
.ss-dashboard .mp-brand-folder {
  position: relative;
  overflow: hidden;
  border: 1px solid rgba(13,13,13,0.07);
  border-radius: 18px;
  background: #fff;
  box-shadow: 0 1px 2px rgba(13,13,13,0.03);
  transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
}
.ss-dashboard .mp-brand-folder:hover {
  border-color: rgba(13,13,13,0.14);
  box-shadow: 0 14px 32px rgba(13,13,13,0.08);
  transform: translateY(-3px);
}
.ss-dashboard .mp-brand-folder-main {
  display: flex;
  flex-direction: column;
  text-decoration: none;
  color: inherit;
}
.ss-dashboard .mp-brand-folder-stage {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 132px;
  padding: 22px;
  background:
    radial-gradient(120% 80% at 50% 0%, rgba(255,255,255,0.9) 0%, rgba(247,248,250,0.4) 45%, rgba(244,245,247,1) 100%);
  border-bottom: 1px solid rgba(13,13,13,0.045);
}
.ss-dashboard .mp-brand-folder.is-empty .mp-brand-folder-stage {
  background: #f8f8f9;
}
.ss-dashboard .mp-brand-folder-icon {
  width: 76px;
  height: 76px;
  border-radius: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  background: #fff;
  border: 1px solid rgba(13,13,13,0.06);
  box-shadow: 0 8px 20px rgba(13,13,13,0.05);
}
.ss-dashboard .mp-brand-folder-icon.has-logo {
  padding: 10px;
  background: #fff;
}
.ss-dashboard .mp-brand-folder-icon img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}
.ss-dashboard .mp-brand-folder-icon:not(.has-logo) {
  width: 72px;
  height: 72px;
  background: transparent;
  border: 0;
  box-shadow: none;
  border-radius: 0;
}
.ss-dashboard .mp-brand-folder-icon:not(.has-logo) img {
  filter: drop-shadow(0 8px 12px rgba(13,13,13,0.10));
}
.ss-dashboard .mp-brand-folder-body {
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding: 14px 14px 16px;
}
.ss-dashboard .mp-brand-folder-name {
  font-size: 13.5px;
  font-weight: 650;
  color: #111214;
  line-height: 1.3;
  letter-spacing: -0.01em;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  line-clamp: 2;
  overflow: hidden;
}
.ss-dashboard .mp-brand-folder-meta {
  display: inline-flex;
  align-self: flex-start;
  align-items: center;
  border-radius: 999px;
  background: #f3f4f6;
  padding: 3px 8px;
  font-size: 11px;
  font-weight: 550;
  color: #6b7178;
  letter-spacing: 0.01em;
}
.ss-dashboard .mp-brand-folder:hover .mp-brand-folder-meta {
  background: #f4e9eb;
  color: #b42318;
}
.ss-dashboard .mp-brand-logo-btn {
  position: absolute;
  top: 10px;
  right: 10px;
  z-index: 2;
  display: inline-flex;
  height: 30px;
  width: 30px;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
  border: 1px solid rgba(13,13,13,0.08);
  background: rgba(255,255,255,0.92);
  color: #6b7178;
  backdrop-filter: blur(8px);
  opacity: 0;
  transform: translateY(-4px);
  cursor: pointer;
  transition: opacity .16s ease, transform .16s ease, color .16s ease, border-color .16s ease;
}
.ss-dashboard .mp-brand-folder:hover .mp-brand-logo-btn,
.ss-dashboard .mp-brand-logo-btn:focus-visible {
  opacity: 1;
  transform: none;
}
.ss-dashboard .mp-brand-logo-btn:hover {
  border-color: rgba(140,250,1,0.25);
  color: #3d6b00;
}
.ss-dashboard .mp-brand-logo-btn:disabled {
  opacity: .4;
  cursor: not-allowed;
}
@media (hover: none) {
  .ss-dashboard .mp-brand-logo-btn { opacity: 1; transform: none; }
}
.ss-dashboard .mp-price-pct {
  display: flex;
  align-items: center;
  gap: 4px;
  position: relative;
}
.ss-dashboard .mp-price-pct input {
  height: 32px;
  width: 100%;
  min-width: 64px;
  border: 1px solid rgba(13,13,13,0.08);
  border-radius: 8px;
  background: #fff;
  padding: 0 22px 0 8px;
  font-size: 12px;
  text-align: right;
  color: #111214;
  outline: none;
  transition: border-color .15s, box-shadow .15s;
}
.ss-dashboard .mp-price-pct input::placeholder { color: #c4c8ce; }
.ss-dashboard .mp-price-pct input:focus {
  border-color: rgba(140,250,1,0.28);
  box-shadow: 0 0 0 3px rgba(140,250,1,0.08);
}
.ss-dashboard .mp-price-pct span {
  position: absolute;
  right: 36px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 11px;
  font-weight: 600;
  color: #9aa0a6;
  pointer-events: none;
}
.ss-dashboard .mp-price-pct-apply {
  display: inline-flex;
  height: 32px;
  width: 32px;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  border: 1px solid rgba(140,250,1,0.28);
  background: #8CFA01;
  color: #142000;
  cursor: pointer;
}
.ss-dashboard .mp-price-pct-apply:hover { background: #6ed000; }
.ss-dashboard .mp-price-pct-apply:disabled { opacity: .45; cursor: not-allowed; }
</style>
<?php $__env->stopPush(); ?>

<div
    class="ss-dashboard ss-dash-page ss-wo-create"
    x-data="produkCrud()"
    <?php if(auth()->user()->isManager()): ?>
    x-init="init(); $nextTick(() => { requestAnimationFrame(() => $el.classList.add('is-ready')); })"
    <?php else: ?>
    x-init="$nextTick(() => { requestAnimationFrame(() => $el.classList.add('is-ready')); })"
    <?php endif; ?>
>
<div class="ss-dash-content space-y-5">


    <div class="mp-summary-grid ss-dash-enter" style="--ss-delay: 80ms">
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 0">
            <div class="pay-card-top">
                <span class="pay-card-label"><?php echo e($mp['summary_label']); ?></span>
                <span class="pay-icon cyan">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </span>
            </div>
            <div class="pay-amount"><?php echo e(number_format((! $browseBrands && ($selectedBrand || $unbranded)) ? $items->total() : (int) ($totalCatalogCount ?? 0), 0, ',', '.')); ?></div>
            <p class="pay-period"><?php echo e($browseBrands ? 'Master outlet aktif' : (($selectedBrand || $unbranded) ? ($hasActiveFilters ? 'Sesuai filter tabel' : 'Produk brand ini') : 'Master outlet aktif')); ?></p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 1">
            <div class="pay-card-top">
                <span class="pay-card-label">Kategori</span>
                <span class="pay-icon teal">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/></svg>
                </span>
            </div>
            <div class="pay-amount"><?php echo e(number_format($categories->count(), 0, ',', '.')); ?></div>
            <p class="pay-period">Kategori terdaftar</p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 2">
            <div class="pay-card-top">
                <span class="pay-card-label">Brand</span>
                <span class="pay-icon violet">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </span>
            </div>
            <div class="pay-amount"><?php echo e(number_format($brands->count(), 0, ',', '.')); ?></div>
            <p class="pay-period">Brand terdaftar</p>
        </div>
    </div>

    <div class="panel-card panel-card-flush ss-dash-enter" style="--ss-delay: 120ms">
        <div class="flex flex-col gap-3 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <?php if($browseBrands): ?>
                <h2 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900"><?php echo e($mp['list_title']); ?></h2>
                <p class="mt-1 text-[13px] text-gray-500">Pilih folder brand untuk melihat dan mengelola produk</p>
                <?php else: ?>
                <?php if($selectedBrand || $unbranded): ?>
                <div class="mb-2">
                    <a href="<?php echo e(route($mp['route_index'])); ?>" class="inline-flex items-center gap-1 text-[13px] font-medium text-gray-500 transition hover:text-red-600">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                        Semua brand
                    </a>
                </div>
                <div class="flex items-center gap-3">
                    <?php if($selectedBrand?->logoUrl()): ?>
                    <img src="<?php echo e($selectedBrand->logoUrl()); ?>" alt="<?php echo e($brandLabel); ?>" class="h-12 w-12 rounded-xl border border-gray-200 bg-white object-contain p-1">
                    <?php else: ?>
                    <img src="<?php echo e($defaultFolderIcon); ?>" alt="" class="h-12 w-12 object-contain">
                    <?php endif; ?>
                    <div>
                        <h2 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900"><?php echo e($brandLabel); ?></h2>
                        <p class="mt-1 text-[13px] text-gray-500">Daftar produk dalam brand ini</p>
                    </div>
                </div>
                <?php else: ?>
                <h2 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900"><?php echo e($mp['list_title']); ?></h2>
                <p class="mt-1 text-[13px] text-gray-500"><?php echo e($mp['list_subtitle']); ?></p>
                <?php endif; ?>
                <?php endif; ?>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <?php if(empty($mp['hide_import_export'])): ?>
                <a href="<?php echo e(route($mp['route_export'], $exportQuery)); ?>" class="btn-export">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Export Excel
                </a>
                <a href="<?php echo e(route($mp['route_pdf'], $exportQuery)); ?>" target="_blank" rel="noopener noreferrer" class="btn-export-pdf">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Export PDF
                </a>
                <button type="button" @click="showImportModal = true" class="btn-secondary inline-flex h-10 items-center gap-2 border-indigo-200 bg-indigo-50 px-4 text-indigo-700 hover:bg-indigo-100">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Import Excel
                </button>
                <?php endif; ?>
                <?php if(auth()->user()->isManager()): ?>
                <button type="button" @click.prevent="bulkArchiveAll()"
                        :disabled="bulkArchivingAll || totalCatalogCount <= 0"
                        class="btn-secondary inline-flex h-10 items-center gap-2 border-amber-300 bg-amber-500 px-4 text-white hover:bg-amber-600 disabled:cursor-not-allowed disabled:opacity-50"
                        title="Arsipkan semua produk di master outlet ini. Stok outlet direset ke 0. Lihat daftar arsip lewat tombol Data Arsip.">
                    <svg x-show="!bulkArchivingAll" class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                    <svg x-show="bulkArchivingAll" class="h-4 w-4 shrink-0 animate-spin" x-cloak fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    <span x-text="bulkArchivingAll ? 'Mengarsipkan…' : ('Arsip produk (' + totalCatalogCount.toLocaleString('id-ID') + ')')"></span>
                </button>
                <?php endif; ?>
                <?php if(auth()->user()->canManageProductMasterCatalog()): ?>
                <a href="<?php echo e(route($mp['route_trashed'] ?? 'speedshop.master.produk.trashed')); ?>" class="btn-secondary inline-flex h-10 items-center gap-2 border-slate-300 bg-slate-100 px-4 text-slate-800 hover:bg-slate-200">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                    List Arsip
                </a>
                <?php endif; ?>
                <button type="button" @click="openCreate()" class="btn-primary inline-flex h-10 items-center gap-2 px-5">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Tambah <?php echo e($mp['is_part_luar'] ? 'Part Luar' : 'Produk'); ?>

                </button>
            </div>
        </div>

        <?php if(! $browseBrands && auth()->user()->isManager()): ?>
        <div x-show="selectedCount() > 0" x-cloak class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-amber-200 bg-amber-50/80 px-4 py-3 text-sm text-amber-950">
            <p class="font-medium">
                <span x-text="selectedCount().toLocaleString('id-ID')"></span> produk dipilih
                <span class="font-normal text-amber-800">— seleksi tetap saat pindah halaman</span>
            </p>
            <div class="flex items-center gap-2">
                <button type="button" @click="clearSelection()" class="rounded-lg border border-amber-300 bg-white px-3 py-1.5 text-xs font-medium text-amber-900 transition hover:bg-amber-100">Bersihkan pilihan</button>
                <button type="button" @click.prevent="bulkArchiveSelected()" :disabled="bulkArchiving" class="rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-amber-700 disabled:opacity-60">Arsip terpilih</button>
            </div>
        </div>
        <?php endif; ?>

        <?php if($browseBrands): ?>
        <div class="mp-brand-toolbar">
            <div class="mp-brand-search">
                <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 100-15 7.5 7.5 0 000 15z"/></svg>
                <input type="search" x-model="brandQuery" placeholder="Cari brand">
            </div>
            <input type="file" x-ref="brandLogoInput" accept="image/png,image/jpeg,image/webp" class="hidden" @change="uploadBrandLogo($event)">
        </div>
        <div class="mp-brand-grid">
            <?php if($unbrandedCount > 0): ?>
            <div class="mp-brand-folder is-ghost" x-show="brandMatches('Tanpa Brand')">
                <a href="<?php echo e(route($mp['route_index'].'.unbranded')); ?>" class="mp-brand-folder-main">
                    <span class="mp-brand-folder-stage">
                        <span class="mp-brand-folder-icon">
                            <img src="<?php echo e($defaultFolderIcon); ?>" alt="">
                        </span>
                    </span>
                    <span class="mp-brand-folder-body">
                        <span class="mp-brand-folder-name">Tanpa Brand</span>
                        <span class="mp-brand-folder-meta"><?php echo e(number_format($unbrandedCount, 0, ',', '.')); ?> produk</span>
                    </span>
                </a>
            </div>
            <?php endif; ?>
            <?php $__empty_1 = true; $__currentLoopData = $brandFolders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $folder): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
                $folderLogo = $folder->logoUrl();
                $folderCount = (int) ($folder->produk_count ?? 0);
            ?>
            <div
                class="mp-brand-folder <?php echo e($folderCount === 0 ? 'is-empty' : ''); ?>"
                x-show="brandMatches(<?php echo \Illuminate\Support\Js::from($folder->nama)->toHtml() ?>)"
            >
                <a href="<?php echo e(route($mp['route_index'].'.brand', $folder)); ?>" class="mp-brand-folder-main">
                    <span class="mp-brand-folder-stage">
                        <span class="mp-brand-folder-icon <?php echo e($folderLogo ? 'has-logo' : ''); ?>">
                            <?php if($folderLogo): ?>
                                <img src="<?php echo e($folderLogo); ?>" alt="<?php echo e($folder->nama); ?>">
                            <?php else: ?>
                                <img src="<?php echo e($defaultFolderIcon); ?>" alt="">
                            <?php endif; ?>
                        </span>
                    </span>
                    <span class="mp-brand-folder-body">
                        <span class="mp-brand-folder-name"><?php echo e($folder->nama); ?></span>
                        <span class="mp-brand-folder-meta"><?php echo e(number_format($folderCount, 0, ',', '.')); ?> produk</span>
                    </span>
                </a>
                <?php if($canUploadBrandLogo): ?>
                <button
                    type="button"
                    class="mp-brand-logo-btn"
                    :disabled="brandLogoUploading"
                    @click.stop.prevent="pickBrandLogo(<?php echo e((int) $folder->id); ?>)"
                    title="<?php echo e($folderLogo ? 'Ganti logo' : 'Unggah logo'); ?>"
                >
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                </button>
                <?php endif; ?>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <p class="col-span-full py-12 text-center text-sm text-gray-400">Belum ada brand terdaftar.</p>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <form method="GET">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="bg-[#f7f3f4]">
                            <?php if(auth()->user()->isManager()): ?>
                            <th class="w-12 px-3 py-3.5">
                                <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-amber-600 focus:ring-amber-500"
                                       :checked="allPageSelected()"
                                       @change="togglePage()"
                                       title="Pilih / batalkan semua di halaman ini">
                            </th>
                            <?php endif; ?>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 w-16">No.</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Kode Produk</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Kode Supplier</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Nama Produk</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Kategori</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Brand</th>
                            <?php if($isWarehouseSharedCatalog): ?>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-right">HPP Speedshop</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-right">Harga Jual Cust</th>
                            <?php else: ?>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-right">HPP</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-right">Harga Jual</th>
                            <?php endif; ?>
                            <?php if($isWarehouseSharedCatalog): ?>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-right">Stok Warehouse</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-right" <?php if($isAllScope): ?> title="Gabungan stok semua cabang" <?php endif; ?>>Stok Speedshop</th>
                            <?php else: ?>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-right" <?php if($isAllScope): ?> title="Gabungan stok semua cabang" <?php endif; ?>>Stok</th>
                            <?php endif; ?>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 w-28"></th>
                        </tr>
                        <tr class="border-t border-gray-200 bg-[#fbf8f9]">
                            <?php if(auth()->user()->isManager()): ?>
                            <th class="px-3 py-2"></th>
                            <?php endif; ?>
                            <th class="px-5 py-2"></th>
                            <th class="px-5 py-2">
                                <input name="kode" value="<?php echo e(request('kode')); ?>" placeholder="Cari kode..." class="<?php echo e($filterBaseClass); ?>" onchange="this.form.submit()">
                            </th>
                            <th class="px-5 py-2"></th>
                            <th class="px-5 py-2">
                                <input name="nama" value="<?php echo e(request('nama')); ?>" placeholder="Cari nama..." class="<?php echo e($filterBaseClass); ?>" onchange="this.form.submit()">
                            </th>
                            <th class="px-5 py-2">
                                <select name="category_id" class="<?php echo e($filterBaseClass); ?>" onchange="this.form.submit()">
                                    <option value="">Semua</option>
                                    <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($c->id); ?>" <?php if((string) request('category_id') === (string) $c->id): echo 'selected'; endif; ?>><?php echo e($c->nama); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </th>
                            <th class="px-5 py-2">
                                <?php if($selectedBrand || $unbranded): ?>
                                <input type="hidden" name="brand_id" value="<?php echo e($selectedBrand?->id); ?>">
                                <?php else: ?>
                                <select name="brand_id" class="<?php echo e($filterBaseClass); ?>" onchange="this.form.submit()">
                                    <option value="">Semua</option>
                                    <?php $__currentLoopData = $brands; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($b->id); ?>" <?php if((string) request('brand_id') === (string) $b->id): echo 'selected'; endif; ?>><?php echo e($b->nama); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                                <?php endif; ?>
                            </th>
                            <th class="px-5 py-2"></th>
                            <?php if($canSetPricePercent && (auth()->user()->isManager() || auth()->user()->canManageProductMasterCatalog())): ?>
                            <th class="px-5 py-2">
                                <div class="mp-price-pct">
                                    <input form="mp-price-percent-orphan" type="number" min="0" max="1000" step="0.01" inputmode="decimal" placeholder="<?php echo e($pricePercents['jual'] === '' ? '—' : '0'); ?>" title="<?php echo e($pricePercentMeta['base']); ?> + persentase" :disabled="pricePercentApplying" x-model="pricePercent.jual" @keydown.enter.prevent.stop="applyPricePercent(<?php echo \Illuminate\Support\Js::from($pricePercentMeta['field'])->toHtml() ?>, pricePercent.jual)">
                                    <span>%</span>
                                    <button type="button" class="mp-price-pct-apply" title="Terapkan persentase" :disabled="pricePercentApplying" @click.prevent="applyPricePercent(<?php echo \Illuminate\Support\Js::from($pricePercentMeta['field'])->toHtml() ?>, pricePercent.jual)">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    </button>
                                </div>
                            </th>
                            <?php elseif($canSetPricePercent): ?>
                            <th class="px-5 py-2 text-right text-xs font-semibold tabular-nums text-gray-500"><?php echo e($pricePercents['jual'] !== '' ? $pricePercents['jual'].'%' : '—'); ?></th>
                            <?php else: ?>
                            <th class="px-5 py-2"></th>
                            <?php endif; ?>
                            <?php if($isWarehouseSharedCatalog): ?>
                            <th class="px-5 py-2"></th>
                            <th class="px-5 py-2"></th>
                            <?php else: ?>
                            <th class="px-5 py-2"></th>
                            <?php endif; ?>
                            <th class="px-5 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    <?php $__empty_1 = true; $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $stokSpeedshopQty = array_key_exists('combined_speedshop_stock', $p->getAttributes())
                            ? (int) $p->combined_speedshop_stock
                            : (($ssid ?? null)
                                ? (int) ($isAllScope ? $p->speedshopStocks->sum('jumlah') : ($p->speedshopStocks->first()->jumlah ?? 0))
                                : null);
                    ?>
                    <tr class="transition hover:bg-[#fcfafb]" <?php if(auth()->user()->isManager()): ?> :class="isSelected(<?php echo e($p->id); ?>) && 'bg-amber-50/50'" <?php endif; ?>>
                        <?php if(auth()->user()->isManager()): ?>
                        <td class="px-3 py-4">
                            <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-amber-600 focus:ring-amber-500"
                                   :checked="isSelected(<?php echo e($p->id); ?>)"
                                   @change="toggleRow(<?php echo e($p->id); ?>)"
                                   aria-label="Pilih <?php echo e($p->kode_produk); ?>">
                        </td>
                        <?php endif; ?>
                        <td class="px-5 py-4 text-gray-500"><?php echo e($items->firstItem() + $i); ?></td>
                        <td class="px-5 py-4 font-mono text-sm font-semibold text-gray-700"><?php echo e($p->kode_produk); ?></td>
                        <td class="px-5 py-4 text-gray-600"><?php echo e($p->kode_supplier ?? '—'); ?></td>
                        <td class="px-5 py-4 font-semibold text-gray-900"><?php echo e($p->nama_produk); ?></td>
                        <td class="px-5 py-4">
                            <?php
                                $cat = $p->category;
                                $catIdx = $cat ? abs(crc32((string) $cat->id . '|' . ($cat->nama ?? ''))) % 12 : 0;
                            ?>
                            <?php if($cat): ?>
                                <?php switch($catIdx):
                                    case (0): ?>
                                        <span class="inline-flex max-w-[180px] items-center truncate rounded-lg bg-indigo-100 px-2.5 py-1 text-xs font-semibold text-indigo-800" title="<?php echo e($cat->nama); ?>"><?php echo e($cat->nama); ?></span>
                                        <?php break; ?>
                                    <?php case (1): ?>
                                        <span class="inline-flex max-w-[180px] items-center truncate rounded-lg bg-purple-100 px-2.5 py-1 text-xs font-semibold text-purple-800" title="<?php echo e($cat->nama); ?>"><?php echo e($cat->nama); ?></span>
                                        <?php break; ?>
                                    <?php case (2): ?>
                                        <span class="inline-flex max-w-[180px] items-center truncate rounded-lg bg-cyan-100 px-2.5 py-1 text-xs font-semibold text-cyan-800" title="<?php echo e($cat->nama); ?>"><?php echo e($cat->nama); ?></span>
                                        <?php break; ?>
                                    <?php case (3): ?>
                                        <span class="inline-flex max-w-[180px] items-center truncate rounded-lg bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-800" title="<?php echo e($cat->nama); ?>"><?php echo e($cat->nama); ?></span>
                                        <?php break; ?>
                                    <?php case (4): ?>
                                        <span class="inline-flex max-w-[180px] items-center truncate rounded-lg bg-teal-100 px-2.5 py-1 text-xs font-semibold text-teal-800" title="<?php echo e($cat->nama); ?>"><?php echo e($cat->nama); ?></span>
                                        <?php break; ?>
                                    <?php case (5): ?>
                                        <span class="inline-flex max-w-[180px] items-center truncate rounded-lg bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-800" title="<?php echo e($cat->nama); ?>"><?php echo e($cat->nama); ?></span>
                                        <?php break; ?>
                                    <?php case (6): ?>
                                        <span class="inline-flex max-w-[180px] items-center truncate rounded-lg bg-fuchsia-100 px-2.5 py-1 text-xs font-semibold text-fuchsia-800" title="<?php echo e($cat->nama); ?>"><?php echo e($cat->nama); ?></span>
                                        <?php break; ?>
                                    <?php case (7): ?>
                                        <span class="inline-flex max-w-[180px] items-center truncate rounded-lg bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800" title="<?php echo e($cat->nama); ?>"><?php echo e($cat->nama); ?></span>
                                        <?php break; ?>
                                    <?php case (8): ?>
                                        <span class="inline-flex max-w-[180px] items-center truncate rounded-lg bg-orange-100 px-2.5 py-1 text-xs font-semibold text-orange-800" title="<?php echo e($cat->nama); ?>"><?php echo e($cat->nama); ?></span>
                                        <?php break; ?>
                                    <?php case (9): ?>
                                        <span class="inline-flex max-w-[180px] items-center truncate rounded-lg bg-sky-100 px-2.5 py-1 text-xs font-semibold text-sky-800" title="<?php echo e($cat->nama); ?>"><?php echo e($cat->nama); ?></span>
                                        <?php break; ?>
                                    <?php case (10): ?>
                                        <span class="inline-flex max-w-[180px] items-center truncate rounded-lg bg-violet-100 px-2.5 py-1 text-xs font-semibold text-violet-800" title="<?php echo e($cat->nama); ?>"><?php echo e($cat->nama); ?></span>
                                        <?php break; ?>
                                    <?php default: ?>
                                        <span class="inline-flex max-w-[180px] items-center truncate rounded-lg bg-lime-100 px-2.5 py-1 text-xs font-semibold text-lime-800" title="<?php echo e($cat->nama); ?>"><?php echo e($cat->nama); ?></span>
                                <?php endswitch; ?>
                            <?php else: ?>
                                <span class="inline-flex items-center rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600">–</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-5 py-4">
                            <?php
                                $br = $p->brand;
                                $brIdx = $br ? abs(crc32((string) $br->id . '|' . ($br->nama ?? ''))) % 12 : 0;
                            ?>
                            <?php if($br): ?>
                                <?php switch($brIdx):
                                    case (0): ?>
                                        <span class="inline-flex max-w-[180px] items-center truncate rounded-lg bg-indigo-100 px-2.5 py-1 text-xs font-semibold text-indigo-800" title="<?php echo e($br->nama); ?>"><?php echo e($br->nama); ?></span>
                                        <?php break; ?>
                                    <?php case (1): ?>
                                        <span class="inline-flex max-w-[180px] items-center truncate rounded-lg bg-purple-100 px-2.5 py-1 text-xs font-semibold text-purple-800" title="<?php echo e($br->nama); ?>"><?php echo e($br->nama); ?></span>
                                        <?php break; ?>
                                    <?php case (2): ?>
                                        <span class="inline-flex max-w-[180px] items-center truncate rounded-lg bg-cyan-100 px-2.5 py-1 text-xs font-semibold text-cyan-800" title="<?php echo e($br->nama); ?>"><?php echo e($br->nama); ?></span>
                                        <?php break; ?>
                                    <?php case (3): ?>
                                        <span class="inline-flex max-w-[180px] items-center truncate rounded-lg bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-800" title="<?php echo e($br->nama); ?>"><?php echo e($br->nama); ?></span>
                                        <?php break; ?>
                                    <?php case (4): ?>
                                        <span class="inline-flex max-w-[180px] items-center truncate rounded-lg bg-teal-100 px-2.5 py-1 text-xs font-semibold text-teal-800" title="<?php echo e($br->nama); ?>"><?php echo e($br->nama); ?></span>
                                        <?php break; ?>
                                    <?php case (5): ?>
                                        <span class="inline-flex max-w-[180px] items-center truncate rounded-lg bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-800" title="<?php echo e($br->nama); ?>"><?php echo e($br->nama); ?></span>
                                        <?php break; ?>
                                    <?php case (6): ?>
                                        <span class="inline-flex max-w-[180px] items-center truncate rounded-lg bg-fuchsia-100 px-2.5 py-1 text-xs font-semibold text-fuchsia-800" title="<?php echo e($br->nama); ?>"><?php echo e($br->nama); ?></span>
                                        <?php break; ?>
                                    <?php case (7): ?>
                                        <span class="inline-flex max-w-[180px] items-center truncate rounded-lg bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800" title="<?php echo e($br->nama); ?>"><?php echo e($br->nama); ?></span>
                                        <?php break; ?>
                                    <?php case (8): ?>
                                        <span class="inline-flex max-w-[180px] items-center truncate rounded-lg bg-orange-100 px-2.5 py-1 text-xs font-semibold text-orange-800" title="<?php echo e($br->nama); ?>"><?php echo e($br->nama); ?></span>
                                        <?php break; ?>
                                    <?php case (9): ?>
                                        <span class="inline-flex max-w-[180px] items-center truncate rounded-lg bg-sky-100 px-2.5 py-1 text-xs font-semibold text-sky-800" title="<?php echo e($br->nama); ?>"><?php echo e($br->nama); ?></span>
                                        <?php break; ?>
                                    <?php case (10): ?>
                                        <span class="inline-flex max-w-[180px] items-center truncate rounded-lg bg-violet-100 px-2.5 py-1 text-xs font-semibold text-violet-800" title="<?php echo e($br->nama); ?>"><?php echo e($br->nama); ?></span>
                                        <?php break; ?>
                                    <?php default: ?>
                                        <span class="inline-flex max-w-[180px] items-center truncate rounded-lg bg-lime-100 px-2.5 py-1 text-xs font-semibold text-lime-800" title="<?php echo e($br->nama); ?>"><?php echo e($br->nama); ?></span>
                                <?php endswitch; ?>
                            <?php else: ?>
                                <span class="inline-flex items-center rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600">–</span>
                            <?php endif; ?>
                        </td>
                        <?php if($isWarehouseSharedCatalog): ?>
                        <td class="px-5 py-4 text-right text-gray-700">Rp <?php echo e(number_format($p->harga_jual_speedshop ?? 0, 0, ',', '.')); ?></td>
                        <td class="px-5 py-4 text-right text-gray-700">Rp <?php echo e(number_format($p->harga_eceran_terendah ?? 0, 0, ',', '.')); ?></td>
                        <?php else: ?>
                        <td class="px-5 py-4 text-right text-gray-700">Rp <?php echo e(number_format($p->hpp ?? 0, 0, ',', '.')); ?></td>
                        <td class="px-5 py-4 text-right text-gray-700">Rp <?php echo e(number_format($p->harga_jual_speedshop ?? 0, 0, ',', '.')); ?></td>
                        <?php endif; ?>
                        <?php if($isWarehouseSharedCatalog): ?>
                        <td class="px-5 py-4 text-right tabular-nums text-gray-800"><?php echo e(number_format((int) ($p->jumlah ?? 0), 0, ',', '.')); ?></td>
                        <td class="px-5 py-4 text-right tabular-nums text-gray-800">
                            <?php if($stokSpeedshopQty !== null): ?>
                                <?php echo e(number_format($stokSpeedshopQty, 0, ',', '.')); ?>

                            <?php else: ?>
                                –
                            <?php endif; ?>
                        </td>
                        <?php else: ?>
                        <td class="px-5 py-4 text-right tabular-nums text-gray-800">
                            <?php if($stokSpeedshopQty !== null): ?>
                                <?php echo e(number_format($stokSpeedshopQty, 0, ',', '.')); ?>

                            <?php else: ?>
                                –
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                        <td class="px-5 py-4">
                            <div class="flex items-center justify-end gap-2">
                                <button type="button" @click.prevent="openBarcode(<?php echo e(json_encode($p->kode_produk)); ?>)" class="wo-icon-btn bg-gray-700 text-white hover:bg-gray-800" title="Lihat Barcode">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                                </button>
                                <button type="button" @click.prevent="openEdit(<?php echo e($p->id); ?>)" class="wo-icon-btn bg-gray-600 text-white hover:bg-gray-700" title="Edit">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                </button>
                                <?php if(auth()->user()->canManageProductMasterCatalog()): ?>
                                <button type="button" @click.prevent="openMoveCatalog(<?php echo e($p->id); ?>, <?php echo e(json_encode($p->kode_produk)); ?>)" class="wo-icon-btn bg-indigo-600 text-white hover:bg-indigo-700" title="Pindah ke katalog lain">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                </button>
                                <?php endif; ?>
                                <?php if(auth()->user()->isManager()): ?>
                                <button type="button" @click.prevent="archiveProduct(<?php echo e($p->id); ?>)" class="wo-icon-btn bg-amber-600 text-white hover:bg-amber-700" title="Arsip produk (hilang dari master outlet; data tidak dihapus)">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="<?php echo e(auth()->user()->isManager() ? 11 : 10); ?>" class="px-5 py-12 text-center text-gray-400">Belum ada data produk. Klik Tambah Produk untuk menambah.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            </form>
        </div>

        <?php if($items->hasPages()): ?>
        <div class="border-t border-gray-100 px-5 py-4"><?php echo e($items->links()); ?></div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

    <?php echo $__env->make('speedshop.master-data.partials.produk-modals', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<?php echo $__env->make('partials.product-qr-scripts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<style>
/* Samakan look & feel dengan TomSelect (Customer WO) */
.select2-container { width: 100% !important; }
.select2-container--default .select2-selection--single {
  height: 2.5rem;
  border: 1px solid #e5e7eb; /* gray-200 */
  border-radius: 0.75rem; /* rounded-xl */
  background: #fff;
  padding: 0 1rem;
  display: flex;
  align-items: center;
  box-shadow: none;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
  padding-left: 0;
  padding-right: 1.75rem;
  color: #111827; /* gray-900 */
  line-height: 1.25rem;
  font-size: 0.875rem;
}
.select2-container--default .select2-selection--single .select2-selection__placeholder {
  color: #9ca3af; /* gray-400 */
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
  height: 2.5rem;
  right: 0.75rem;
}
.select2-container--default.select2-container--focus .select2-selection--single {
  border-color: #fca5a5; /* red-300/400 */
  box-shadow: 0 0 0 2px rgba(239,68,68,0.12); /* red-500/12% */
}
.select2-dropdown {
  border: 1px solid #e5e7eb;
  border-radius: 0.75rem;
  margin-top: 4px;
  overflow: hidden;
  box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -4px rgba(0,0,0,0.1);
}
.select2-container--default .select2-search--dropdown .select2-search__field {
  border: 1px solid #e5e7eb;
  border-radius: 0.75rem;
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  outline: none;
}
.select2-container--default .select2-results__option {
  font-size: 0.875rem;
  padding: 0.5rem 0.75rem;
}
.select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
  background: #fef2f2; /* red-50 */
  color: #dc2626; /* red-600 */
}
</style>
<script>
function produkCrud() {
    const baseUrl = <?php echo json_encode($mp['base_url'], 15, 512) ?>;
    const bulkArchiveUrl = <?php echo json_encode(route($mp['route_bulk_archive']), 15, 512) ?>;
    const bulkPricePercentUrl = <?php echo json_encode(\Illuminate\Support\Facades\Route::has($mp['route_index'].'.bulk-price-percent') ? route($mp['route_index'].'.bulk-price-percent') : '', 15, 512) ?>;
    const pricePercentMeta = <?php echo json_encode($pricePercentMeta, 15, 512) ?>;
    const currentCatalogKind = <?php echo json_encode($mp['catalog_kind'] ?? '', 15, 512) ?>;
    const catalogLabels = <?php echo json_encode($mp['catalog_labels'] ?? [], 15, 512) ?>;
    const isManager = <?php echo json_encode(auth()->user()->isManager(), 15, 512) ?>;
    const canMoveCatalog = <?php echo json_encode(auth()->user()->canManageProductMasterCatalog(), 15, 512) ?>;
    const totalCatalogCount = <?php echo e((int) ($totalCatalogCount ?? 0)); ?>;
    const selectionStorageKey = <?php echo json_encode($mp['selection_storage_key'], 15, 512) ?> + <?php echo json_encode(auth()->user()->activeSpeedshopId() ?? 0, 15, 512) ?>;
    const pageProductIds = <?php echo json_encode($items->pluck('id')->values(), 15, 512) ?>;
    const defaultBrandId = <?php echo json_encode($selectedBrand?->id ? (string) $selectedBrand->id : '', 15, 512) ?>;
    const unbrandedScope = <?php echo json_encode((bool) $unbranded, 15, 512) ?>;
    const priceFilterKode = <?php echo json_encode(request('kode'), 15, 512) ?>;
    const priceFilterNama = <?php echo json_encode(request('nama'), 15, 512) ?>;
    const priceFilterCategoryId = <?php echo json_encode(request('category_id'), 15, 512) ?>;
    return {
        showModal: false, showImportModal: false, showBarcode: false, showMoveModal: false, barcodeValue: '', labelQty: 1,
        importFileName: '', isEdit: false, editId: null, loading: false, moveLoading: false, bulkArchiving: false, bulkArchivingAll: false, errors: {},
        moveError: '',
        moveForm: { id: null, kode: '', from_label: '', target_kind: '' },
        totalCatalogCount,
        selectedIds: [],
        pageProductIds,
        brandQuery: '',
        logoBrandId: null,
        brandLogoUploading: false,
        pricePercent: { jual: <?php echo json_encode($pricePercents['jual'], 15, 512) ?> },
        pricePercentApplying: false,
        pricePercentMeta: pricePercentMeta,
        form: { kode_produk: '', kode_supplier: '', nama_produk: '', category_id: '', brand_id: defaultBrandId, hpp: '', harga_jual_speedshop: '', harga_eceran_terendah: '', stok_awal: 0 },
        init() {
            if (isManager) {
                this.loadSelectionFromStorage();
            }
        },
        loadSelectionFromStorage() {
            try {
                const raw = sessionStorage.getItem(selectionStorageKey);
                const parsed = raw ? JSON.parse(raw) : [];
                this.selectedIds = Array.isArray(parsed)
                    ? [...new Set(parsed.map((id) => parseInt(id, 10)).filter((id) => id > 0))]
                    : [];
            } catch (e) {
                this.selectedIds = [];
            }
        },
        saveSelectionToStorage() {
            sessionStorage.setItem(selectionStorageKey, JSON.stringify(this.selectedIds));
        },
        isSelected(id) {
            return this.selectedIds.includes(parseInt(id, 10));
        },
        toggleRow(id) {
            const n = parseInt(id, 10);
            if (this.isSelected(n)) {
                this.selectedIds = this.selectedIds.filter((x) => x !== n);
            } else {
                this.selectedIds = [...this.selectedIds, n];
            }
            this.saveSelectionToStorage();
        },
        allPageSelected() {
            if (!this.pageProductIds.length) return false;
            return this.pageProductIds.every((id) => this.isSelected(id));
        },
        togglePage() {
            if (this.allPageSelected()) {
                const pageSet = new Set(this.pageProductIds.map((id) => parseInt(id, 10)));
                this.selectedIds = this.selectedIds.filter((id) => !pageSet.has(id));
            } else {
                const merged = new Set([...this.selectedIds, ...this.pageProductIds.map((id) => parseInt(id, 10))]);
                this.selectedIds = [...merged];
            }
            this.saveSelectionToStorage();
        },
        selectedCount() {
            return this.selectedIds.length;
        },
        clearSelection() {
            this.selectedIds = [];
            sessionStorage.removeItem(selectionStorageKey);
        },
        removeIdsFromSelection(ids) {
            const removeSet = new Set(ids.map((id) => parseInt(id, 10)));
            this.selectedIds = this.selectedIds.filter((id) => !removeSet.has(id));
            this.saveSelectionToStorage();
        },
        brandMatches(name) {
            const q = (this.brandQuery || '').trim().toLowerCase();
            if (!q) return true;
            return String(name || '').toLowerCase().includes(q);
        },
        pickBrandLogo(id) {
            this.logoBrandId = id;
            if (this.$refs.brandLogoInput) {
                this.$refs.brandLogoInput.value = '';
                this.$refs.brandLogoInput.click();
            }
        },
        async applyPricePercent(field, raw) {
            if (this.pricePercentApplying || !bulkPricePercentUrl) return;
            const percent = parseFloat(String(raw ?? '').replace(',', '.'));
            if (!Number.isFinite(percent) || percent < 0) {
                await Swal.fire({ title: 'Persentase tidak valid', text: 'Isi angka 0 atau lebih, lalu tekan Enter.', icon: 'info' });
                return;
            }
            const pctText = String(percent).replace('.', ',');
            const result = await Swal.fire({
                title: 'Terapkan persentase?',
                html: '<strong>' + pricePercentMeta.target + '</strong> semua produk di daftar ini akan dihitung <strong>' + pricePercentMeta.base + ' + ' + pctText + '%</strong>.<br><br>Contoh: ' + pricePercentMeta.base + ' Rp 100.000 → ' + pricePercentMeta.target + ' Rp ' + Math.round(100000 * (1 + percent / 100)).toLocaleString('id-ID') + '.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#8CFA01',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, terapkan',
                cancelButtonText: 'Batal',
            });
            if (!result.isConfirmed) return;
            this.pricePercentApplying = true;
            try {
                const res = await fetch(bulkPricePercentUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        field,
                        percent,
                        brand_id: defaultBrandId || null,
                        unbranded: unbrandedScope ? 1 : 0,
                        kode: priceFilterKode || '',
                        nama: priceFilterNama || '',
                        category_id: priceFilterCategoryId || '',
                    }),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    await Swal.fire({ title: 'Gagal', text: data.message || 'Tidak dapat memperbarui harga.', icon: 'error' });
                    return;
                }
                await Swal.fire({ title: 'Berhasil', text: data.message || 'Harga berhasil dihitung ulang.', icon: 'success', timer: 1800, showConfirmButton: false });
                window.location.reload();
            } finally {
                this.pricePercentApplying = false;
            }
        },
        async uploadBrandLogo(event) {
            const file = event.target.files?.[0];
            const brandId = this.logoBrandId;
            event.target.value = '';
            if (!file || !brandId) return;
            this.brandLogoUploading = true;
            const fd = new FormData();
            fd.append('logo', file);
            try {
                const res = await fetch(`${baseUrl}/brand/${brandId}/logo`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: fd,
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    const msg = data?.errors?.logo?.[0] || data.message || 'Gagal mengunggah logo.';
                    await Swal.fire({ title: 'Gagal', text: msg, icon: 'error' });
                    return;
                }
                await Swal.fire({ title: 'Berhasil', text: data.message || 'Logo tersimpan.', icon: 'success', timer: 1400, showConfirmButton: false });
                window.location.reload();
            } finally {
                this.brandLogoUploading = false;
                this.logoBrandId = null;
            }
        },
        initKodeSupplierSelect2() {
            const el = this.$refs?.kodeSupplierSelect;
            if (!el) return;
            if (typeof window.jQuery === 'undefined' || typeof window.jQuery.fn?.select2 === 'undefined') return;
            const $el = window.jQuery(el);
            if ($el.data('select2')) return;
            $el.select2({
                width: '100%',
                placeholder: '— Pilih supplier —',
                allowClear: true,
                dropdownParent: $el.closest('div[x-show="showModal"]'),
            });
            $el.on('change', () => {
                this.form.kode_supplier = (String($el.val() || '')).trim();
            });
        },
        openCreate() {
            this.isEdit = false; this.editId = null; this.errors = {};
            this.form = { kode_produk: '', kode_supplier: '', nama_produk: '', category_id: '', brand_id: defaultBrandId, hpp: '', harga_jual_speedshop: '', harga_eceran_terendah: '', stok_awal: 0 };
            this.showModal = true;
            this.$nextTick(() => {
                this.initKodeSupplierSelect2();
                if (typeof window.jQuery !== 'undefined') {
                    const $el = window.jQuery(this.$refs?.kodeSupplierSelect);
                    if ($el?.data('select2')) $el.val(this.form.kode_supplier || '').trigger('change.select2');
                }
            });
        },
        async openEdit(id) {
            this.isEdit = true; this.editId = id; this.errors = {}; this.loading = true; this.showModal = true;
            try {
                const res = await fetch(`${baseUrl}/${id}`, { headers: { 'Accept': 'application/json' } });
                if (! res.ok) {
                    const err = await res.json().catch(() => ({}));
                    await Swal.fire({ title: 'Gagal memuat produk', text: err.message || ('HTTP ' + res.status), icon: 'error' });
                    this.showModal = false;
                    return;
                }
                const data = await res.json();
                this.form = {
                    kode_produk: data.kode_produk || '',
                    kode_supplier: data.kode_supplier || '',
                    nama_produk: data.nama_produk || '',
                    category_id: data.category_id ? String(data.category_id) : '',
                    brand_id: data.brand_id ? String(data.brand_id) : '',
                    hpp: data.hpp ?? '',
                    harga_jual_speedshop: data.harga_jual_speedshop ?? '',
                    harga_eceran_terendah: data.harga_eceran_terendah ?? '',
                    stok_awal: (data.stok_awal ?? 0)
                };
                this.$nextTick(() => {
                    this.initKodeSupplierSelect2();
                    if (typeof window.jQuery !== 'undefined') {
                        const $el = window.jQuery(this.$refs?.kodeSupplierSelect);
                        if ($el?.data('select2')) $el.val(this.form.kode_supplier || '').trigger('change.select2');
                    }
                });
            } catch (e) {
                this.form = { kode_produk: '', kode_supplier: '', nama_produk: '', category_id: '', brand_id: '', hpp: '', harga_jual_speedshop: '', harga_eceran_terendah: '', stok_awal: 0 };
            }
            this.loading = false;
        },
        async save() {
            this.loading = true; this.errors = {};
            const url = this.isEdit ? `${baseUrl}/${this.editId}` : <?php echo json_encode(route($mp['route_store'] ?? ($mp['route_index'].'.store')), 15, 512) ?>;
            const res = await fetch(url, {
                method: this.isEdit ? 'PUT' : 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                body: JSON.stringify(this.form)
            });
            const data = await res.json().catch(() => ({}));
            this.loading = false;
            if (!res.ok) { this.errors = data.errors || {}; return; }
            window.location.reload();
        },
        async archiveProduct(id) {
            const result = await Swal.fire({
                title: 'Arsip produk?',
                html: 'Produk <strong>tidak dihapus</strong> dari database. Hanya hilang dari daftar master outlet ini. Stok outlet yang masih ada akan <strong>direset ke 0</strong>. Bisa dikembalikan lewat halaman <strong>Arsip produk</strong>.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d97706',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, arsipkan',
                cancelButtonText: 'Batal',
            });
            if (!result.isConfirmed) return;
            const res = await fetch(`${baseUrl}/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' } });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) { Swal.fire({ title: 'Gagal', text: data.message || 'Tidak dapat mengarsipkan produk.', icon: 'error' }); return; }
            this.removeIdsFromSelection([id]);
            window.location.reload();
        },
        openMoveCatalog(id, kode) {
            if (!canMoveCatalog) return;
            this.moveError = '';
            this.moveLoading = false;
            this.moveForm = {
                id,
                kode: kode || ('#' + id),
                from_label: catalogLabels[currentCatalogKind] || currentCatalogKind,
                target_kind: '',
            };
            this.showMoveModal = true;
        },
        async submitMoveCatalog() {
            if (!canMoveCatalog || !this.moveForm.id) return;
            if (!this.moveForm.target_kind) {
                this.moveError = 'Pilih katalog tujuan.';
                return;
            }
            this.moveError = '';
            this.moveLoading = true;
            try {
                const res = await fetch(`${baseUrl}/${this.moveForm.id}/move-catalog`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ target_kind: this.moveForm.target_kind }),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    this.moveError = data.message || 'Tidak dapat memindahkan produk.';
                    this.moveLoading = false;
                    return;
                }
                this.removeIdsFromSelection([this.moveForm.id]);
                this.showMoveModal = false;
                window.location.reload();
            } catch (e) {
                this.moveError = 'Tidak dapat memindahkan produk.';
                this.moveLoading = false;
            }
        },
        async bulkArchiveAll() {
            if (this.totalCatalogCount <= 0) {
                Swal.fire('Perhatian', 'Tidak ada produk di master outlet untuk diarsipkan.', 'info');
                return;
            }
            const r1 = await Swal.fire({
                title: 'Arsipkan semua produk outlet?',
                html: 'Semua <strong>' + this.totalCatalogCount.toLocaleString('id-ID') + '</strong> produk di master outlet ini akan <strong>diarsipkan</strong> (tidak dihapus dari database). Stok outlet yang masih ada akan <strong>direset ke 0</strong>.<br><br>Pulihkan lewat menu <strong>Arsip produk</strong> di sidebar.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d97706',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Lanjut',
                cancelButtonText: 'Batal',
            });
            if (!r1.isConfirmed) return;
            const r2 = await Swal.fire({
                title: 'Konfirmasi terakhir',
                input: 'checkbox',
                inputValue: 0,
                inputPlaceholder: 'Saya mengerti semua produk master outlet akan diarsipkan',
                showCancelButton: true,
                confirmButtonColor: '#b45309',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, arsipkan semua',
                cancelButtonText: 'Batal',
                inputValidator: (v) => (v ? null : 'Centang kotak konfirmasi'),
            });
            if (!r2.isConfirmed) return;
            this.bulkArchivingAll = true;
            try {
                const res = await fetch(bulkArchiveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({ confirm: true, archive_all: true }),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    Swal.fire({ title: 'Gagal', text: data.message || 'Tidak dapat mengarsipkan semua produk.', icon: 'error' });
                    return;
                }
                this.clearSelection();
                await Swal.fire({ title: 'Berhasil', text: data.message || 'Produk telah diarsipkan.', icon: 'success', timer: 2200, showConfirmButton: false });
                window.location.reload();
            } finally {
                this.bulkArchivingAll = false;
            }
        },
        async bulkArchiveSelected() {
            const count = this.selectedCount();
            if (count === 0) {
                Swal.fire('Perhatian', 'Centang produk yang ingin diarsipkan terlebih dahulu. Pilihan tetap tersimpan saat pindah halaman.', 'info');
                return;
            }
            const r1 = await Swal.fire({
                title: 'Arsipkan produk terpilih?',
                html: '<strong>' + count.toLocaleString('id-ID') + '</strong> produk akan <strong>diarsipkan</strong> dari master outlet (tidak dihapus dari database). Stok outlet yang masih ada akan <strong>direset ke 0</strong>.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d97706',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Lanjut',
                cancelButtonText: 'Batal',
            });
            if (!r1.isConfirmed) return;
            const r2 = await Swal.fire({
                title: 'Konfirmasi terakhir',
                input: 'checkbox',
                inputValue: 0,
                inputPlaceholder: 'Saya mengerti stok outlet bisa direset ke 0 dan produk diarsipkan',
                showCancelButton: true,
                confirmButtonColor: '#b45309',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, arsipkan',
                cancelButtonText: 'Batal',
                inputValidator: (v) => (v ? null : 'Centang kotak konfirmasi'),
            });
            if (!r2.isConfirmed) return;
            const idsToArchive = [...this.selectedIds];
            this.bulkArchiving = true;
            try {
                const res = await fetch(bulkArchiveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({ confirm: true, product_ids: idsToArchive }),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    Swal.fire({ title: 'Gagal', text: data.message || 'Tidak dapat mengarsipkan produk terpilih.', icon: 'error' });
                    return;
                }
                this.clearSelection();
                await Swal.fire({ title: 'Berhasil', text: data.message || 'Produk telah diarsipkan.', icon: 'success', timer: 2200, showConfirmButton: false });
                window.location.reload();
            } finally {
                this.bulkArchiving = false;
            }
        },
        openBarcode(kode) {
            this.barcodeValue = kode; this.labelQty = 1; this.showBarcode = true;
            this.$nextTick(() => { window.renderProductQr('qrcodeElSs', kode); });
        },
        printBarcode() {
            window.printProductQrLabels('qrcodeElSs', this.barcodeValue, this.labelQty);
        }
    };
}
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make($mp['layout'] ?? 'layouts.speedshop', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/wahyusafrizal/work/Github/77racingstore/resources/views/speedshop/master-data/produk.blade.php ENDPATH**/ ?>