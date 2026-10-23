<?php
    use App\Support\ProductMasterCatalog;

    $filterBaseClass = 'h-8 w-full rounded-lg border border-gray-200 bg-white px-2.5 text-[13px] font-normal text-gray-700 placeholder-gray-400 outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/40';
    $stokPage = $stokPage ?? [
        'title' => ProductMasterCatalog::label(ProductMasterCatalog::MO),
        'indexRoute' => ProductMasterCatalog::stokRouteBase(ProductMasterCatalog::MO),
        'auditRoute' => ProductMasterCatalog::stokRouteBase(ProductMasterCatalog::MO).'.audit',
        'auditApplyRoute' => ProductMasterCatalog::stokRouteBase(ProductMasterCatalog::MO).'.audit.apply',
        'exportRoute' => ProductMasterCatalog::stokRouteBase(ProductMasterCatalog::MO).'.export',
        'pdfRoute' => ProductMasterCatalog::stokRouteBase(ProductMasterCatalog::MO).'.pdf',
        'importRoute' => ProductMasterCatalog::stokRouteBase(ProductMasterCatalog::MO).'.import',
        'templateRoute' => ProductMasterCatalog::stokRouteBase(ProductMasterCatalog::MO).'.template',
        'updateStockRoute' => ProductMasterCatalog::stokRouteBase(ProductMasterCatalog::MO).'.update-stock',
    ];
    $isMoCatalog = ProductMasterCatalog::isWarehouseSharedKind($stokPage['catalogKind'] ?? ProductMasterCatalog::MO);
    $canEditStok = ($canEditStok ?? false) && ! empty($stokPage['updateStockRoute'] ?? null);
    $isAllScope = $isAllScope ?? false;
    $isFlpUser = auth()->user()?->isSpeedshop() && auth()->user()?->role === \App\Models\User::ROLE_SPEEDSHOP_FLP;
    $terlarisActive = $isFlpUser ? false : ($terlarisActive ?? request()->boolean('terlaris'));
    $filterQuery = request()->only(['kode', 'nama', 'category_id', 'brand_id', 'search', 'sort_stock']);
    $exportQuery = array_merge($filterQuery, $terlarisActive ? ['terlaris' => 1] : []);
    $terlarisOnQuery = array_merge($filterQuery, ['terlaris' => 1]);
?>

<?php $__env->startSection('title', $stokPage['title'].' & Stok'); ?>

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
    x-data="stokProdukView()"
    x-init="$nextTick(() => { requestAnimationFrame(() => $el.classList.add('is-ready')); })"
>
<div class="ss-dash-content space-y-5">
    <div class="sp-summary-grid ss-dash-enter" style="--ss-delay: 80ms">
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 0">
            <div class="pay-card-top">
                <span class="pay-card-label">Total Item</span>
                <span class="pay-icon cyan">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </span>
            </div>
            <div class="pay-amount"><?php echo e(number_format($items->total())); ?></div>
            <p class="pay-period">Produk terdaftar</p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 1">
            <div class="pay-card-top">
                <span class="pay-card-label">Total Stok</span>
                <span class="pay-icon teal">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                </span>
            </div>
            <div class="pay-amount"><?php echo e(number_format($totalStok ?? 0)); ?></div>
            <p class="pay-period">Unit tersedia</p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 2">
            <div class="pay-card-top">
                <span class="pay-card-label">Modal</span>
                <span class="pay-icon amber">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-2.21 0-4 .895-4 2s1.79 2 4 2 4 .895 4 2-1.79 2-4 2m0-8V6m0 12v-2m9-4a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="pay-amount text-[22px] sm:text-[26px]">Rp <?php echo e(number_format((float) ($totalModal ?? 0), 0, ',', '.')); ?></div>
            <p class="pay-period">Total HPP stok</p>
        </div>
    </div>

    <div class="panel-card panel-card-flush ss-dash-enter" style="--ss-delay: 120ms">
        <div class="flex flex-col gap-3 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">Daftar <?php echo e($stokPage['title']); ?></h2>
                <p class="mt-1 text-[13px] text-gray-500">
                    <?php if($isAllScope): ?>
                        Stok aktif: <span class="font-semibold text-gray-700">Semua Speedshop</span> (gabungan semua cabang, satu baris per kode) · Filter cabang di navbar ·
                    <?php endif; ?>
                    <?php echo e($isFlpUser ? 'Filter kode, nama, kategori, brand, atau urutkan stok' : 'Filter kode, nama, kategori, brand, urutkan stok, atau produk terlaris'); ?>

                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <?php if(! empty($canImportStok) && ! empty($stokPage['importRoute'])): ?>
                <button type="button" @click="showImportModal = true" class="btn-secondary inline-flex h-10 items-center gap-2 border-indigo-200 bg-indigo-50 px-4 text-indigo-700 hover:bg-indigo-100">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Import Stok
                </button>
                <?php endif; ?>
                <?php if (! ($isFlpUser)): ?>
                <?php if($terlarisActive): ?>
                <a href="<?php echo e(route($stokPage['indexRoute'], $filterQuery)); ?>" class="btn-secondary inline-flex h-10 items-center gap-2 border-amber-300 bg-amber-50 px-4 text-amber-800 hover:bg-amber-100">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                    Produk Terlaris
                </a>
                <?php else: ?>
                <a href="<?php echo e(route($stokPage['indexRoute'], $terlarisOnQuery)); ?>" class="btn-secondary inline-flex h-10 items-center gap-2 px-4">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                    Produk Terlaris
                </a>
                <?php endif; ?>
                <a href="<?php echo e(route($stokPage['auditRoute'])); ?>" class="btn-secondary inline-flex h-10 items-center gap-2 px-4">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    Audit Stok
                </a>
                <?php endif; ?>
                <a href="<?php echo e(route($stokPage['exportRoute'], $exportQuery)); ?>" class="btn-export">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Export Excel
                </a>
                <a href="<?php echo e(route($stokPage['pdfRoute'], $exportQuery)); ?>" target="_blank" rel="noopener noreferrer" class="btn-export-pdf">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Export PDF
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <form method="GET">
                <?php if($terlarisActive): ?>
                <input type="hidden" name="terlaris" value="1">
                <?php endif; ?>
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="bg-[#f7f3f4]">
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 w-16">No.</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Kode Produk</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Kode Supplier</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Nama Produk</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Kategori</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Brand</th>
                            <?php if($isMoCatalog): ?>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-right">HPP Speedshop</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-right">Harga Jual Cust</th>
                            <?php else: ?>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-right">HPP</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-right">Harga Jual</th>
                            <?php endif; ?>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-center">Stok</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 w-20"></th>
                        </tr>
                        <tr class="border-t border-gray-200 bg-[#fbf8f9]">
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
                                    <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($cat->id); ?>" <?php echo e((string) request('category_id') === (string) $cat->id ? 'selected' : ''); ?>><?php echo e($cat->nama); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </th>
                            <th class="px-5 py-2">
                                <select name="brand_id" class="<?php echo e($filterBaseClass); ?>" onchange="this.form.submit()">
                                    <option value="">Semua</option>
                                    <?php $__currentLoopData = $brands; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $brand): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($brand->id); ?>" <?php echo e((string) request('brand_id') === (string) $brand->id ? 'selected' : ''); ?>><?php echo e($brand->nama); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </th>
                            <th class="px-5 py-2"></th>
                            <th class="px-5 py-2"></th>
                            <th class="px-5 py-2">
                                <select name="sort_stock" class="<?php echo e($filterBaseClass); ?>" onchange="this.form.submit()">
                                    <option value="">Default</option>
                                    <option value="desc" <?php echo e(request('sort_stock') === 'desc' ? 'selected' : ''); ?>>Stok terbanyak</option>
                                    <option value="asc" <?php echo e(request('sort_stock') === 'asc' ? 'selected' : ''); ?>>Stok terdikit</option>
                                </select>
                            </th>
                            <th class="px-5 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    <?php $__empty_1 = true; $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php $stokSpeedshop = (int) (($speedshopStockMap[$product->id] ?? null)?->jumlah ?? 0); ?>
                    <tr class="transition hover:bg-[#fcfafb]">
                        <td class="px-5 py-4 text-gray-500"><?php echo e($items->firstItem() + $i); ?></td>
                        <td class="px-5 py-4 font-mono text-sm font-semibold text-gray-700"><?php echo e($product->kode_produk); ?></td>
                        <td class="px-5 py-4 text-gray-600"><?php echo e($product->kode_supplier ?? '—'); ?></td>
                        <td class="px-5 py-4 font-semibold text-gray-900"><?php echo e($product->nama_produk); ?></td>
                        <td class="px-5 py-4">
                            <?php
                                $cat = $product->category;
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
                                $br = $product->brand;
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
                        <?php if($isMoCatalog): ?>
                        <td class="px-5 py-4 text-right font-medium text-gray-700 tabular-nums">
                            Rp <?php echo e(number_format((float) ($product->harga_jual_speedshop ?? $product->harga_jual ?? 0), 0, ',', '.')); ?>

                        </td>
                        <td class="px-5 py-4 text-right font-semibold text-gray-900 tabular-nums">
                            Rp <?php echo e(number_format((float) ($product->harga_eceran_terendah ?? 0), 0, ',', '.')); ?>

                        </td>
                        <?php else: ?>
                        <td class="px-5 py-4 text-right font-medium text-gray-700 tabular-nums">
                            Rp <?php echo e(number_format((float) ($product->hpp ?? 0), 0, ',', '.')); ?>

                        </td>
                        <td class="px-5 py-4 text-right font-semibold text-gray-900 tabular-nums">
                            Rp <?php echo e(number_format((float) ($product->harga_jual_speedshop ?? $product->harga_jual ?? 0), 0, ',', '.')); ?>

                        </td>
                        <?php endif; ?>
                        <td class="px-5 py-4 text-center">
                            <button
                                type="button"
                                @click="openHistory(<?php echo e($product->id); ?>)"
                                class="inline-flex cursor-pointer items-center rounded-lg px-2.5 py-1 text-xs font-semibold transition hover:ring-2 hover:ring-gray-300 <?php echo e(\App\Support\Warehouse\WarehouseInventoriProductQuery::usesWarehouseInventoriStockUi($stokPage['catalogKind'] ?? \App\Support\ProductMasterCatalog::MO) ? \App\Support\Warehouse\WarehouseInventoriProductQuery::stockBadgeClasses($stokSpeedshop) : ($stokSpeedshop > 0 ? 'bg-emerald-50 text-emerald-700 hover:ring-emerald-400' : 'bg-amber-50 text-amber-700 hover:ring-amber-400')); ?>"
                                title="<?php echo e($product->kode_produk); ?> - <?php echo e($product->nama_produk); ?>"
                            >
                                <?php echo e(number_format($stokSpeedshop)); ?>

                            </button>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center justify-end gap-2">
                                <?php if($canEditStok): ?>
                                <button
                                    type="button"
                                    @click="openEditStok(<?php echo e($product->id); ?>, <?php echo e(json_encode($product->kode_produk)); ?>, <?php echo e(json_encode($product->nama_produk)); ?>, <?php echo e($stokSpeedshop); ?>)"
                                    class="wo-icon-btn bg-gray-600 text-white hover:bg-gray-700"
                                    title="Edit Stok"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                </button>
                                <?php endif; ?>
                                <button type="button" @click="openBarcode(<?php echo e(json_encode($product->kode_produk)); ?>)" class="wo-icon-btn bg-gray-800 text-white hover:bg-gray-900" title="Lihat Barcode">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="10" class="px-5 py-12 text-center text-gray-400">
                            <?php if(request()->hasAny(['kode', 'nama', 'category_id', 'brand_id', 'search', 'sort_stock', 'terlaris'])): ?>
                                <?php if($terlarisActive): ?>
                                    Belum ada produk terlaris sesuai filter.
                                <?php else: ?>
                                    Tidak ada produk sesuai filter.
                                <?php endif; ?>
                            <?php else: ?>
                                <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                <p class="mt-3 text-sm font-medium">Belum ada data produk</p>
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

    <div x-show="showBarcode" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="showBarcode = false">
        <div x-show="showBarcode" x-transition.opacity class="fixed inset-0 bg-black/50" @click="showBarcode = false"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div x-show="showBarcode" x-transition class="wo-modal-panel relative w-full max-w-md !p-8" @click.stop>
                <div class="flex w-full flex-col items-center gap-3">
                    <div id="qrcodeElStok" class="inline-block"></div>
                    <p class="w-full text-center font-mono text-sm font-semibold tracking-wide text-gray-900" x-text="barcodeValue"></p>
                </div>
                <div class="mt-5">
                    <label class="wo-label">Jumlah Label</label>
                    <div class="mt-2 flex items-center gap-3">
                        <input type="number" x-model.number="labelQty" min="1" max="100" class="wo-input w-24 text-center">
                        <button type="button" @click="printBarcode()" class="btn-primary h-10 px-5">Print QR Code</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div x-show="showHistory" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="showHistory = false">
        <div x-show="showHistory" x-transition.opacity class="fixed inset-0 bg-black/50" @click="showHistory = false"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div x-show="showHistory" x-transition class="wo-modal-panel relative w-full max-w-2xl !p-6 sm:!p-8" @click.stop>
                <div class="flex items-start gap-4">
                    <div class="flex-1">
                        <h3 class="text-[20px] font-semibold text-gray-900">History Stok</h3>
                        <p class="mt-1 text-[13px] text-gray-500" x-text="historyLabel"></p>
                    </div>
                    <button type="button" @click="showHistory = false" class="wo-icon-btn" aria-label="Tutup">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="mt-5 max-h-96 overflow-y-auto rounded-xl border border-gray-200 bg-[#fcfafb]">
                    <table class="w-full text-left text-xs sm:text-sm">
                        <thead class="sticky top-0 bg-[#f7f3f4]">
                            <tr>
                                <th class="px-4 py-3 text-[11px] font-semibold uppercase tracking-wide text-gray-500">Tanggal</th>
                                <th class="px-4 py-3 text-[11px] font-semibold uppercase tracking-wide text-gray-500">Tipe</th>
                                <th class="px-4 py-3 text-center text-[11px] font-semibold uppercase tracking-wide text-gray-500">Qty</th>
                                <th class="px-4 py-3 text-[11px] font-semibold uppercase tracking-wide text-gray-500">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="m in historyMovements" :key="m.id">
                                <tr class="bg-white transition hover:bg-[#fcfafb]">
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-600" x-text="new Date(m.created_at).toLocaleDateString('id-ID', {day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'})"></td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold" :class="m.type === 'masuk' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'" x-text="m.type === 'masuk' ? 'Masuk' : 'Keluar'"></span>
                                    </td>
                                    <td class="px-4 py-3 text-center text-xs font-semibold tabular-nums" :class="m.type === 'masuk' ? 'text-emerald-600' : 'text-rose-600'" x-text="(m.type === 'masuk' ? '+' : '-') + m.qty"></td>
                                    <td class="px-4 py-3 text-gray-600" x-text="(m.keterangan || '-').split(' - ')[0]"></td>
                                </tr>
                            </template>
                            <template x-if="historyMovements.length === 0">
                                <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400">Belum ada history stok.</td></tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
    </div>
</div>

<?php if($canEditStok): ?>

<div x-show="showEditStok" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="showEditStok = false">
    <div x-show="showEditStok" x-transition.opacity class="fixed inset-0 bg-black/50" @click="showEditStok = false"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div x-show="showEditStok" x-transition class="wo-modal-panel relative w-full max-w-md !p-6 sm:!p-8" @click.stop>
            <h3 class="text-lg font-bold text-gray-900">Edit Stok</h3>
            <p class="mt-1 text-sm text-gray-500">
                <span class="font-mono font-semibold text-gray-700" x-text="editStok.kode"></span>
                <span class="text-gray-400"> — </span>
                <span x-text="editStok.nama"></span>
            </p>
            <form @submit.prevent="saveEditStok()" class="mt-6 space-y-4">
                <div>
                    <label class="wo-label">Stok (satuan) <span class="req">*</span></label>
                    <input
                        type="number"
                        min="0"
                        step="1"
                        x-model.number="editStok.jumlah"
                        class="wo-input tabular-nums"
                        :class="editStokError && 'border-red-400'"
                        placeholder="0"
                        required
                    >
                    <p class="mt-1 text-xs text-gray-500">Nilai absolut stok cabang aktif (bukan ditambah/dikurangi).</p>
                    <template x-if="editStokError"><p class="mt-1 text-xs text-red-500" x-text="editStokError"></p></template>
                </div>
                <div class="flex flex-wrap items-center justify-end gap-3 border-t border-gray-100 pt-6">
                    <button type="button" @click="showEditStok = false" class="btn-secondary px-5 py-2.5" :disabled="editStokSaving">Batal</button>
                    <button type="submit" class="btn-primary px-5 py-2.5" :disabled="editStokSaving">
                        <span x-show="!editStokSaving">Simpan</span>
                        <span x-show="editStokSaving">Menyimpan…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if(! empty($canImportStok) && ! empty($stokPage['importRoute'])): ?>

<div x-show="showImportModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="showImportModal = false">
    <div x-show="showImportModal" x-transition.opacity class="fixed inset-0 bg-black/50" @click="showImportModal = false"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div x-show="showImportModal" x-transition class="wo-modal-panel relative w-full max-w-2xl !p-6 sm:!p-8" @click.stop>
            <h3 class="text-lg font-bold text-gray-900">Import Stok · <?php echo e($stokPage['title']); ?></h3>
            <p class="mt-1 text-sm text-gray-500">Template: Kode Produk, Stok. Kolom Stok = nilai absolut stok cabang aktif (bukan ditambah). Produk harus sudah ada di Master Produk.</p>
            <div class="mt-6">
                <p class="wo-label mb-3">Upload File Excel</p>
                <form action="<?php echo e(route($stokPage['importRoute'])); ?>" method="POST" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>
                    <label class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-200 bg-gray-50/50 px-8 py-14 transition hover:border-red-300 hover:bg-red-50/30">
                        <svg class="h-14 w-14 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                        <span class="mt-3 text-sm font-medium text-gray-600" x-text="importFileName || 'Klik untuk memilih file .xlsx atau .xls'"></span>
                        <input type="file" name="file" accept=".xlsx,.xls" class="hidden" @change="importFileName = $event.target.files[0]?.name || ''">
                    </label>
                    <div class="mt-6 flex flex-wrap items-center justify-end gap-3 border-t border-gray-100 pt-6">
                        <a href="<?php echo e(route($stokPage['templateRoute'])); ?>" class="btn-secondary inline-flex items-center gap-2 border-emerald-200 bg-emerald-50 px-4 py-2.5 text-emerald-700 hover:bg-emerald-100">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            Download Template
                        </a>
                        <button type="button" @click="showImportModal = false" class="btn-secondary px-5 py-2.5">Batal</button>
                        <button type="submit" class="btn-primary px-5 py-2.5">Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
</div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<?php echo $__env->make('partials.product-qr-scripts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<script>
function stokProdukView() {
    const updateStockUrlTemplate = <?php echo \Illuminate\Support\Js::from(
        ! empty($stokPage['updateStockRoute'])
            ? route($stokPage['updateStockRoute'], ['product' => '__ID__'])
            : ''
    )->toHtml() ?>;
    const csrfToken = <?php echo \Illuminate\Support\Js::from(csrf_token())->toHtml() ?>;

    return {
        showHistory: false, historyLabel: '', historyMovements: [],
        showBarcode: false, barcodeValue: '', labelQty: 1,
        showImportModal: false, importFileName: '',
        showEditStok: false,
        editStokSaving: false,
        editStokError: '',
        editStok: { id: null, kode: '', nama: '', jumlah: 0 },

        async openHistory(id) {
            this.showHistory = true;
            this.historyLabel = '';
            this.historyMovements = [];
            const res = await fetch(`<?php echo e(url('speedshop/products')); ?>/${id}/stock-history`, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            this.historyLabel = `${data.product.kode_produk} — ${data.product.nama_produk}`;
            this.historyMovements = data.movements || [];
        },

        openBarcode(kode) {
            this.barcodeValue = kode; this.labelQty = 1; this.showBarcode = true;
            this.$nextTick(() => { window.renderProductQr('qrcodeElStok', kode); });
        },
        printBarcode() {
            window.printProductQrLabels('qrcodeElStok', this.barcodeValue, this.labelQty);
        },

        openEditStok(id, kode, nama, jumlah) {
            this.editStok = { id, kode, nama, jumlah: Number(jumlah) || 0 };
            this.editStokError = '';
            this.editStokSaving = false;
            this.showEditStok = true;
        },

        async saveEditStok() {
            if (! this.editStok.id || ! updateStockUrlTemplate) return;
            const qty = Number(this.editStok.jumlah);
            if (! Number.isInteger(qty) || qty < 0) {
                this.editStokError = 'Stok harus bilangan bulat ≥ 0.';
                return;
            }

            this.editStokSaving = true;
            this.editStokError = '';
            try {
                const url = updateStockUrlTemplate.replace('__ID__', String(this.editStok.id));
                const res = await fetch(url, {
                    method: 'PUT',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ jumlah: qty }),
                });
                const data = await res.json().catch(() => ({}));
                if (! res.ok) {
                    this.editStokError = data.message || (data.errors?.jumlah?.[0]) || 'Gagal menyimpan stok.';
                    this.editStokSaving = false;
                    return;
                }
                window.location.reload();
            } catch (e) {
                this.editStokError = 'Gagal menyimpan stok. Coba lagi.';
                this.editStokSaving = false;
            }
        },
    };
}
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.speedshop', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/wahyusafrizal/work/Github/77racingstore/resources/views/speedshop/stok-produk/index.blade.php ENDPATH**/ ?>