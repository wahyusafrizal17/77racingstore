<?php

namespace App\Http\Controllers\Speedshop;

use App\Exports\Speedshop\ProdukSpeedshopExport;
use App\Exports\Speedshop\ProdukSpeedshopPriceTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\Speedshop\ProdukSpeedshopPriceImport;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Speedshop;
use App\Models\SpeedshopMasterProductArchive;
use App\Models\SpeedshopStock;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\Speedshop\SpeedshopMasterProductCleanupService;
use App\Services\Warehouse\WarehouseSpeedshopCatalogSyncService;
use App\Support\ProductMasterCatalog;
use App\Support\SpeedshopMasterCleanableProductQuery;
use App\Support\UploadStorage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class MasterProdukController extends Controller
{
    private const UNUSED_PRODUCTS_PREVIEW_LIMIT = 100;

    /** Batch size agar tidak kena nginx 504 saat bersihkan ratusan/ribuan arsip. */
    private const UNUSED_CLEANUP_BATCH_SIZE = 80;

    public function __construct(
        private readonly SpeedshopMasterProductCleanupService $speedshopProductCleanup,
    ) {}

    /** @return Product::JENIS_* */
    protected function masterJenisProduk(): string
    {
        return ProductMasterCatalog::jenisProdukFor($this->masterCatalogKind());
    }

    protected function masterCatalogKind(): string
    {
        return ProductMasterCatalog::SPEEDSHOP_SUPPLIER;
    }

    protected function isWarehouseSharedCatalog(): bool
    {
        return ProductMasterCatalog::isWarehouseSharedKind($this->masterCatalogKind());
    }

    protected function resolveSpeedshopScopeIdForCreate(?int $speedshopId): int
    {
        return ProductMasterCatalog::scopeIdForKind(
            $this->masterCatalogKind(),
            $speedshopId ? (int) $speedshopId : 0,
        );
    }

    protected function masterIndexRouteName(): string
    {
        return 'speedshop.master.produk';
    }

    protected function masterBasePath(): string
    {
        return 'speedshop/master/produk';
    }

    /**
     * @return array<string, mixed>
     */
    protected function masterPageConfig(): array
    {
        $kind = $this->masterCatalogKind();
        $isPartLuar = $kind === ProductMasterCatalog::PART_LUAR;
        $label = ProductMasterCatalog::label($kind);

        return [
            'catalog_kind' => $kind,
            'jenis' => $this->masterJenisProduk(),
            'is_part_luar' => $isPartLuar,
            'title' => $label,
            'list_title' => 'Daftar '.$label,
            'list_subtitle' => ProductMasterCatalog::subtitle($kind),
            'summary_label' => 'Total '.$label,
            'base_url' => url($this->masterBasePath()),
            'route_index' => $this->masterIndexRouteName(),
            'route_store' => $this->masterIndexRouteName().'.store',
            'route_export' => $this->masterIndexRouteName().'.export',
            'route_pdf' => $this->masterIndexRouteName().'.pdf',
            'route_bulk_archive' => $this->masterIndexRouteName().'.bulk-archive',
            'route_import' => $this->masterIndexRouteName().'.import',
            'route_template' => $this->masterIndexRouteName().'.template',
            'route_trashed' => $this->masterIndexRouteName().'.trashed',
            'route_trashed_unused' => $this->masterIndexRouteName().'.trashed.unused',
            'route_trashed_unused_bulk_delete' => $this->masterIndexRouteName().'.trashed.unused-bulk-delete',
            'route_restore_all' => $this->masterIndexRouteName().'.restore-all-trashed',
            'route_move_catalog' => $this->masterIndexRouteName().'.move-catalog',
            'trashed_base_url' => url($this->masterBasePath().'/trashed'),
            'trashed_title' => 'Arsip '.$label,
            'trashed_subtitle' => $isPartLuar
                ? 'Part luar yang diarsipkan dari master outlet — data tidak dihapus dari database'
                : ($this->isWarehouseSharedCatalog()
                    ? 'Produk yang diarsipkan dari master outlet atau master warehouse — data tidak dihapus dari database'
                    : 'Produk yang diarsipkan dari master outlet — data tidak dihapus dari database'),
            'trashed_back_label' => '← '.$label,
            'trashed_period_label' => $isPartLuar ? 'Part luar diarsipkan' : 'Produk diarsipkan',
            'selection_storage_key' => 'speedshop_master_'.$kind.'_selected_',
            'hide_import_export' => $isPartLuar,
            'hide_brand_field' => $isPartLuar,
            'catalog_labels' => ProductMasterCatalog::labels(),
        ];
    }

    protected function applyMasterJenisFilter($query)
    {
        return $query->forCatalogJenis($this->masterCatalogKind());
    }

    /**
     * Samakan dengan daftar index: produk yang tampil di master katalog ini boleh diedit.
     */
    protected function assertProductInSpeedshopMasterCatalog(Product $product): void
    {
        if (! $this->speedshopMasterProductsQuery()->whereKey((int) $product->id)->exists()) {
            abort(403);
        }
    }

    /**
     * Query daftar master katalog speedshop (MO tidak di-filter ketat warehouse_id saja).
     */
    protected function speedshopMasterProductsQuery(array $with = [], bool $forListDisplay = false): Builder
    {
        $query = $with === [] ? Product::query() : Product::with($with);
        if (! $this->isWarehouseSharedCatalog()) {
            $query->forUser();
        }
        $ssid = auth()->user()?->activeSpeedshopId();
        $this->applySpeedshopMasterCatalogQuery($query, $ssid ? (int) $ssid : null);

        if ($forListDisplay && $this->shouldCollapseOutletCatalogByKode()) {
            $query->collapseToCanonicalKode();
        }

        return $query;
    }

    private function applySpeedshopMasterCatalogQuery($query, ?int $speedshopId)
    {
        if ($this->isWarehouseSharedCatalog()) {
            return $query->forWarehouseSharedCatalog($this->masterCatalogKind(), auth()->user()?->activeWarehouseId());
        }

        $stockScope = $this->masterOutletStockScope();
        if (($stockScope['is_all_scope'] ?? false) && $stockScope['stock_speedshop_ids'] !== []) {
            return $query->forSpeedshopStokCatalogAny(
                $stockScope['stock_speedshop_ids'],
                $this->masterCatalogKind(),
            );
        }

        // Satu cabang: samakan 1:1 dengan inventori stok (`forSpeedshopStokCatalog`).
        if ($speedshopId) {
            return $query->forSpeedshopStokCatalog((int) $speedshopId, $this->masterCatalogKind());
        }

        $this->applyMasterJenisFilter($query);

        return match ($this->masterCatalogKind()) {
            ProductMasterCatalog::SPEEDSHOP_SUPPLIER,
            ProductMasterCatalog::PART_LUAR => $query->where('speedshop_scope_id', '>', 0),
            default => $query,
        };
    }

    protected function speedshopBrandsForMasterUi(bool $isPartLuar)
    {
        $query = Brand::query()->forSpeedshop()->orderBy('nama');
        if (! $isPartLuar) {
            $query->excludeSpeedshopPartLuarBrands();
        }

        return $query->get();
    }

    protected function resolveJenisProdukForBrand(?int $brandId, string $defaultJenis): string
    {
        if (! $brandId) {
            return $defaultJenis;
        }

        $brand = Brand::query()->find($brandId);

        return ($brand && $brand->isSpeedshopPartLuarCatalog())
            ? Product::JENIS_PART_LUAR
            : $defaultJenis;
    }

    protected function resolveJenisProdukForCreate(?int $brandId): string
    {
        $defaultJenis = $this->masterJenisProduk();
        if (! in_array($this->masterCatalogKind(), [ProductMasterCatalog::MO, ProductMasterCatalog::SPEEDSHOP_SUPPLIER], true)) {
            return $defaultJenis;
        }

        return $this->resolveJenisProdukForBrand($brandId, $defaultJenis);
    }

    protected function usesBrandFolderBrowse(): bool
    {
        return false;
    }

    public function index(Request $request)
    {
        return $this->renderSpeedshopMasterProduk($request, browseBrands: $this->usesBrandFolderBrowse());
    }

    public function byBrand(Request $request, Brand $brand)
    {
        abort_unless($this->usesBrandFolderBrowse(), 404);

        return $this->renderSpeedshopMasterProduk($request, browseBrands: false, selectedBrand: $brand);
    }

    public function unbranded(Request $request)
    {
        abort_unless($this->usesBrandFolderBrowse(), 404);

        return $this->renderSpeedshopMasterProduk($request, browseBrands: false, unbranded: true);
    }

    public function uploadBrandLogo(Request $request, Brand $brand): JsonResponse
    {
        abort_unless($this->usesBrandFolderBrowse(), 404);

        $canManage = auth()->user()?->canManageProductMasterCatalog() ?? false;
        $canUploadImage = auth()->user()?->canUploadMoProductImages() ?? false;
        $isManager = auth()->user()?->isManager() ?? false;
        if (! $canManage && ! $canUploadImage && ! $isManager) {
            abort(403);
        }

        $request->validate([
            'logo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        try {
            UploadStorage::delete($brand->logo_path);
            $path = UploadStorage::store($request->file('logo'), 'brands/logo');
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'logo' => ['Gagal mengunggah logo brand. Gunakan JPG/PNG/WebP maks. 5 MB.'],
            ]);
        }

        $brand->update(['logo_path' => $path]);

        return response()->json([
            'success' => true,
            'message' => 'Logo brand berhasil disimpan.',
            'logo_url' => $brand->fresh()->logoUrl(),
        ]);
    }

    public function applyPricePercent(Request $request): JsonResponse
    {
        abort_unless($this->usesBrandFolderBrowse(), 404);

        $canManage = auth()->user()?->canManageProductMasterCatalog() ?? false;
        $isManager = auth()->user()?->isManager() ?? false;
        if (! $canManage && ! $isManager) {
            abort(403);
        }

        $validated = $request->validate([
            'field' => ['required', Rule::in(['harga_jual_speedshop', 'harga_eceran_terendah'])],
            'percent' => ['required', 'numeric', 'min:0', 'max:1000'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'unbranded' => ['nullable', 'boolean'],
            'kode' => ['nullable', 'string', 'max:80'],
            'nama' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        $isShared = $this->isWarehouseSharedCatalog();
        $field = $validated['field'];
        if ($isShared && $field !== 'harga_eceran_terendah') {
            abort(422, 'Kolom harga tidak valid untuk katalog ini.');
        }
        if (! $isShared && $field !== 'harga_jual_speedshop') {
            abort(422, 'Kolom harga tidak valid untuk katalog ini.');
        }

        $selectedBrand = ! empty($validated['brand_id'])
            ? Brand::query()->find((int) $validated['brand_id'])
            : null;
        $unbranded = $request->boolean('unbranded');

        $query = $this->speedshopMasterProductsQuery([], true);
        $this->applySpeedshopMasterProdukFilters($query, $request, $selectedBrand, $unbranded);

        $ids = $query->pluck('id');
        if ($ids->isEmpty()) {
            return response()->json(['message' => 'Tidak ada produk yang bisa diperbarui.'], 422);
        }

        $percent = (float) $validated['percent'];
        $factorSql = rtrim(rtrim(sprintf('%.8F', 1 + ($percent / 100)), '0'), '.');
        $expr = $isShared
            ? 'ROUND(COALESCE(NULLIF(harga_jual_speedshop, 0), hpp, 0) * '.$factorSql.', 0)'
            : 'ROUND(COALESCE(hpp, 0) * '.$factorSql.', 0)';

        $updates = [$field => DB::raw($expr)];
        if ($isShared) {
            $updates['harga_jual'] = DB::raw($expr);
        }

        Product::query()->whereIn('id', $ids)->update($updates);

        if ($isShared) {
            Product::query()->whereIn('id', $ids)->orderBy('id')->chunkById(80, function ($products) {
                $sync = app(WarehouseSpeedshopCatalogSyncService::class);
                foreach ($products as $product) {
                    $sync->afterWarehouseSharedCatalogSaved($product);
                }
            });
        }

        $label = rtrim(rtrim(number_format($percent, 2, ',', '.'), '0'), ',');
        $targetLabel = $isShared ? 'Harga Jual Cust' : 'Harga Jual';
        $baseLabel = $isShared ? 'HPP Speedshop' : 'HPP';

        return response()->json([
            'success' => true,
            'message' => $targetLabel.' '.$ids->count().' produk dihitung ulang dari '.$baseLabel.' + '.$label.'%.',
            'updated' => $ids->count(),
        ]);
    }

    public function store(Request $request)
    {
        $ssid = auth()->user()?->activeSpeedshopId();
        $warehouseId = $this->resolveWarehouseIdForMoCatalog(
            auth()->user()->activeWarehouseId(),
            $ssid ? (int) $ssid : null,
        );
        $scopeId = $this->resolveSpeedshopScopeIdForCreate($ssid ? (int) $ssid : null);
        if ($this->isWarehouseSharedCatalog() && ! $warehouseId) {
            return back()->with('error', 'Warehouse cabang tidak terdeteksi. Pilih cabang speedshop di navbar terlebih dahulu.');
        }
        if (in_array($this->masterCatalogKind(), [
            ProductMasterCatalog::SPEEDSHOP_SUPPLIER,
            ProductMasterCatalog::PART_LUAR,
        ], true) && $scopeId <= 0) {
            return back()->with('error', 'Pilih cabang speedshop di navbar terlebih dahulu.');
        }
        $isPartLuar = $this->masterJenisProduk() === Product::JENIS_PART_LUAR;
        $kode = mb_strtoupper(trim((string) $request->input('kode_produk')));
        $request->merge(['kode_produk' => $kode]);
        $validated = $request->validate([
            'kode_produk' => ['required', 'string', 'max:50'],
            'kode_supplier' => ['nullable', 'string', 'min:2', 'max:3'],
            'nama_produk' => ['required', 'string', 'max:255'],
            'category_id' => Category::validationRulesCategoryIdForSpeedshopProduct($warehouseId, $ssid ? (int) $ssid : null),
            'brand_id' => [$isPartLuar ? 'nullable' : 'nullable', 'exists:brands,id'],
            'hpp' => ['nullable', 'numeric', 'min:0'],
            'harga_jual_speedshop' => ['nullable', 'numeric', 'min:0'],
            'harga_eceran_terendah' => ['nullable', 'numeric', 'min:0'],
            'stok_awal' => ['nullable', 'integer', 'min:0'],
        ]);

        $existing = $this->findExistingProductByKode($warehouseId, $scopeId, $kode);
        if ($existing && ! $this->canReactivateExistingMasterProduct($existing, $ssid ? (int) $ssid : null)) {
            throw ValidationException::withMessages([
                'kode_produk' => [$this->duplicateKodeProdukMessage($existing)],
            ]);
        }

        $validated['warehouse_id'] = $warehouseId;
        $validated['speedshop_scope_id'] = $scopeId;
        $validated['jenis_produk'] = $this->masterJenisProduk();
        $validated['hidden_from_warehouse_master_at'] = null;
        if ($isPartLuar) {
            $validated['brand_id'] = (int) Brand::query()->firstOrCreate(
                ['nama' => 'Part Luar'],
            )->id;
        } else {
            $validated['brand_id'] = $request->filled('brand_id') ? $validated['brand_id'] : null;
            $validated['jenis_produk'] = $this->resolveJenisProdukForCreate(
                $validated['brand_id'] ? (int) $validated['brand_id'] : null,
            );
        }
        $validated['kode_supplier'] = $request->filled('kode_supplier') ? mb_strtoupper(trim((string) $validated['kode_supplier'])) : null;
        $validated = $this->applySpeedshopCatalogPrices($validated, preserveExistingHpp: (bool) $existing);
        $stockAwal = (int) ($validated['stok_awal'] ?? 0);
        unset($validated['stok_awal']);

        $label = $isPartLuar ? 'Part luar' : 'Produk';
        if ($existing) {
            $this->forceClearMasterArchiveFlags((int) $existing->id);
            $existing = Product::query()->whereKey($existing->id)->firstOrFail();
            $existing->fill(collect($validated)->except($this->speedshopReactivateExceptFields())->all());
            $existing->hidden_from_warehouse_master_at = null;
            $existing->save();
            $product = $existing->fresh();
            $message = $label.' diaktifkan kembali dan data diperbarui.';
        } else {
            $validated['harga_jual_reseler'] = 0;
            if (! $this->isWarehouseSharedCatalog()) {
                $validated['harga_eceran_terendah'] = 0;
            }
            $product = Product::create($validated + ['jumlah' => 0]);
            $message = $label.' berhasil ditambahkan.';
        }

        $stockScope = $this->masterOutletStockScope();
        $skipOutletStockWrite = $this->shouldSkipOutletStockWrite($stockScope);
        $ssid = auth()->user()?->activeSpeedshopId();
        if ($ssid && ! $skipOutletStockWrite) {
            SpeedshopMasterProductArchive::restoreForOutlet((int) $ssid, (int) $product->id);
            if ($existing) {
                SpeedshopStock::query()->firstOrCreate(
                    [
                        'speedshop_id' => (int) $ssid,
                        'product_id' => (int) $product->id,
                    ],
                    ['jumlah' => $stockAwal],
                );
            } else {
                SpeedshopStock::updateOrCreate([
                    'speedshop_id' => (int) $ssid,
                    'product_id' => (int) $product->id,
                ], [
                    'jumlah' => $stockAwal,
                ]);
            }
        }

        if ($this->isWarehouseSharedCatalog()) {
            app(WarehouseSpeedshopCatalogSyncService::class)->afterWarehouseSharedCatalogSaved($product->fresh());
        } elseif ($stockScope['is_all_scope']) {
            $this->syncOutletCatalogMasterFieldsToSiblings(
                $product->fresh(),
                $validated,
                $stockScope['stock_speedshop_ids'],
            );
        }

        $indexRoute = $this->masterIndexRouteName();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $message, 'redirect' => route($indexRoute)]);
        }

        return redirect()->route($indexRoute)->with('success', $message);
    }

    public function show(Product $product)
    {
        $warehouseId = auth()->user()->activeWarehouseId();
        if ($warehouseId !== null && $product->warehouse_id !== $warehouseId) {
            abort(403);
        }
        $this->assertProductInSpeedshopMasterCatalog($product);

        $stockScope = $this->masterOutletStockScope();
        $stokOutlet = $this->outletStockQtyForScope($product, $stockScope['stock_speedshop_ids']);

        return response()->json([
            'id' => $product->id,
            'kode_produk' => $product->kode_produk,
            'kode_supplier' => $product->kode_supplier,
            'nama_produk' => $product->nama_produk,
            'category_id' => $product->category_id,
            'brand_id' => $product->brand_id,
            'hpp' => $product->hpp,
            'harga_jual_speedshop' => $product->harga_jual_speedshop,
            'harga_eceran_terendah' => $product->harga_eceran_terendah,
            'stok_awal' => $stokOutlet,
            'stok_combined' => $stockScope['is_all_scope'],
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $warehouseId = auth()->user()->activeWarehouseId();
        if ($warehouseId !== null && $product->warehouse_id !== $warehouseId) {
            abort(403);
        }
        $this->assertProductInSpeedshopMasterCatalog($product);

        $ssid = auth()->user()?->activeSpeedshopId();
        // Unik per gudang + scope baris produk (bukan outlet login), agar Bogor/Depok boleh kode sama.
        $scopeIdForUnique = (int) ($product->speedshop_scope_id ?? 0);
        $isPartLuar = $this->masterJenisProduk() === Product::JENIS_PART_LUAR;
        $validated = $request->validate([
            'kode_produk' => [
                'required',
                'string',
                'max:50',
                Rule::unique('products', 'kode_produk')->where(function ($query) use ($warehouseId, $scopeIdForUnique) {
                    return $query->where('warehouse_id', $warehouseId)
                        ->where('speedshop_scope_id', $scopeIdForUnique)
                        ->whereNull('deleted_at');
                })->ignore($product->id),
            ],
            'kode_supplier' => ['nullable', 'string', 'min:2', 'max:3'],
            'nama_produk' => ['required', 'string', 'max:255'],
            'category_id' => Category::validationRulesCategoryIdForSpeedshopProduct($warehouseId, $ssid ? (int) $ssid : null),
            'brand_id' => ['nullable', 'exists:brands,id'],
            'hpp' => ['nullable', 'numeric', 'min:0'],
            'harga_jual_speedshop' => ['nullable', 'numeric', 'min:0'],
            'harga_eceran_terendah' => ['nullable', 'numeric', 'min:0'],
            'stok_awal' => ['nullable', 'integer', 'min:0'],
        ]);

        if ($isPartLuar) {
            $validated['brand_id'] = (int) Brand::query()->firstOrCreate(
                ['nama' => 'Part Luar'],
            )->id;
            $validated['jenis_produk'] = Product::JENIS_PART_LUAR;
        } else {
            $validated['brand_id'] = $request->filled('brand_id') ? $validated['brand_id'] : null;
            $validated['jenis_produk'] = $this->resolveJenisProdukForCreate(
                $validated['brand_id'] ? (int) $validated['brand_id'] : null,
            );
        }
        $validated['kode_supplier'] = $request->filled('kode_supplier') ? mb_strtoupper(trim((string) $validated['kode_supplier'])) : null;
        $validated = $this->applySpeedshopCatalogPrices($validated, preserveExistingHpp: $this->isWarehouseSharedCatalog());
        $stockAwal = $validated['stok_awal'] ?? null;
        unset($validated['stok_awal']);

        $previousKode = (string) $product->kode_produk;
        $product->update($validated);

        $stockScope = $this->masterOutletStockScope();
        if ($request->has('stok_awal') && ! $this->shouldSkipOutletStockWrite($stockScope)) {
            $ssid = auth()->user()?->activeSpeedshopId();
            if ($ssid) {
                SpeedshopMasterProductArchive::restoreForOutlet((int) $ssid, (int) $product->id);
                SpeedshopStock::updateOrCreate([
                    'speedshop_id' => (int) $ssid,
                    'product_id' => (int) $product->id,
                ], [
                    'jumlah' => (int) ($stockAwal ?? 0),
                ]);
            }
        }

        if ($this->isWarehouseSharedCatalog()) {
            app(WarehouseSpeedshopCatalogSyncService::class)->afterWarehouseSharedCatalogSaved($product->fresh(), $previousKode);
        } elseif ($stockScope['is_all_scope']) {
            $this->syncOutletCatalogMasterFieldsToSiblings(
                $product->fresh(),
                $validated,
                $stockScope['stock_speedshop_ids'],
            );
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => ($isPartLuar ? 'Part luar' : 'Produk').' berhasil diperbarui.']);
        }

        return redirect()->route($this->masterIndexRouteName())->with('success', ($isPartLuar ? 'Part luar' : 'Produk').' berhasil diperbarui.');
    }

    /**
     * Pindahkan produk antar katalog master.
     */
    public function moveCatalog(Request $request, Product $product): JsonResponse|RedirectResponse
    {
        if (! auth()->user()?->canManageProductMasterCatalog()) {
            abort(403);
        }

        $warehouseId = auth()->user()->activeWarehouseId();
        if ($warehouseId !== null && (int) $product->warehouse_id !== (int) $warehouseId) {
            abort(403);
        }

        $ssid = auth()->user()?->activeSpeedshopId();
        if (! $ssid) {
            $msg = 'Pilih cabang speedshop di navbar terlebih dahulu.';
            if ($request->wantsJson()) {
                return response()->json(['message' => $msg], 422);
            }

            return redirect()->route($this->masterIndexRouteName())->with('error', $msg);
        }

        if (! Product::query()
            ->forUser()
            ->forSpeedshopStokCatalog((int) $ssid, $this->masterCatalogKind())
            ->whereKey((int) $product->id)
            ->exists()) {
            abort(403);
        }

        $validated = $request->validate([
            'target_kind' => ['required', 'string', Rule::in(ProductMasterCatalog::kinds())],
        ]);

        $targetKind = (string) $validated['target_kind'];
        $currentKind = ProductMasterCatalog::kindForProduct($product);

        if ($targetKind === $currentKind) {
            $msg = 'Produk sudah berada di katalog '.ProductMasterCatalog::label($currentKind).'.';
            if ($request->wantsJson()) {
                return response()->json(['message' => $msg], 422);
            }

            return redirect()->route($this->masterIndexRouteName())->with('error', $msg);
        }

        $newScopeId = ProductMasterCatalog::scopeIdForKind($targetKind, (int) $ssid);

        $kodeConflict = Product::query()
            ->where('warehouse_id', $product->warehouse_id)
            ->where('speedshop_scope_id', $newScopeId)
            ->where('kode_produk', $product->kode_produk)
            ->whereNull('deleted_at')
            ->whereKeyNot((int) $product->id)
            ->exists();

        if ($kodeConflict) {
            $msg = 'Kode produk sudah dipakai di katalog tujuan (scope yang sama). Ubah kode dulu atau pilih produk lain.';
            if ($request->wantsJson()) {
                return response()->json(['message' => $msg], 422);
            }

            return redirect()->route($this->masterIndexRouteName())->with('error', $msg);
        }

        $attrs = [
            'speedshop_scope_id' => $newScopeId,
            'jenis_produk' => ProductMasterCatalog::jenisProdukFor($targetKind),
        ];

        if ($targetKind === ProductMasterCatalog::PART_LUAR) {
            $attrs['brand_id'] = (int) Brand::query()->firstOrCreate(
                ['nama' => 'Part Luar'],
            )->id;
        } elseif ($currentKind === ProductMasterCatalog::PART_LUAR) {
            // Lepas brand Part Luar agar update berikutnya tidak mengembalikan jenis ke part_luar.
            $attrs['brand_id'] = null;
        }

        $product->update($attrs);

        SpeedshopMasterProductArchive::restoreForOutlet((int) $ssid, (int) $product->id);
        SpeedshopStock::firstOrCreate([
            'speedshop_id' => (int) $ssid,
            'product_id' => (int) $product->id,
        ], [
            'jumlah' => 0,
        ]);

        $fromLabel = ProductMasterCatalog::label($currentKind);
        $toLabel = ProductMasterCatalog::label($targetKind);
        $msg = "Produk dipindahkan dari {$fromLabel} ke {$toLabel}.";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'redirect' => route($this->masterIndexRouteName()),
            ]);
        }

        return redirect()->route($this->masterIndexRouteName())->with('success', $msg);
    }

    public function destroy(Product $product): JsonResponse|RedirectResponse
    {
        if (! auth()->user()?->canManageProductMasterCatalog()) {
            abort(403);
        }

        $warehouseId = auth()->user()->activeWarehouseId();
        if ($warehouseId !== null && $product->warehouse_id !== $warehouseId) {
            abort(403);
        }
        $this->assertProductInSpeedshopMasterCatalog($product);

        $speedshopId = auth()->user()?->activeSpeedshopId();
        if (! $speedshopId) {
            if (request()->wantsJson()) {
                return response()->json(['message' => 'Outlet speedshop tidak terdeteksi.'], 422);
            }

            return redirect()->route($this->masterIndexRouteName())->with('error', 'Outlet speedshop tidak terdeteksi.');
        }

        $hadStock = $this->outletStockQty($product, (int) $speedshopId) > 0;
        $this->archiveProductForSpeedshop($product, (int) $speedshopId);

        $stockNote = $hadStock ? ' Stok outlet direset menjadi 0.' : '';
        $arsipHint = $this->masterJenisProduk() === Product::JENIS_PART_LUAR
            ? ' Kembalikan lewat menu Arsip part luar jika perlu.'
            : ' Kembalikan lewat menu Arsip produk jika perlu.';
        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Diarsipkan dari master speedshop.'.$stockNote.$arsipHint,
            ]);
        }

        return redirect()->route($this->masterIndexRouteName())->with('success', 'Diarsipkan dari master speedshop.'.$stockNote.$arsipHint);
    }

    /**
     * Arsip massal: semua produk katalog outlet atau produk terpilih (seleksi lintas halaman).
     */
    public function bulkArchive(Request $request): JsonResponse|RedirectResponse
    {
        if (! auth()->user()?->canManageProductMasterCatalog()) {
            abort(403);
        }

        $speedshopId = auth()->user()?->activeSpeedshopId();
        if (! $speedshopId) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Outlet speedshop tidak terdeteksi.'], 422);
            }

            return redirect()->route($this->masterIndexRouteName())->with('error', 'Outlet speedshop tidak terdeteksi.');
        }

        $archiveAll = $request->boolean('archive_all');

        $rules = [
            'confirm' => ['accepted'],
            'archive_all' => ['sometimes', 'boolean'],
        ];
        if (! $archiveAll) {
            $rules['product_ids'] = ['required', 'array', 'min:1', 'max:500'];
            $rules['product_ids.*'] = ['integer', 'distinct'];
        }

        $validated = $request->validate($rules, [
            'product_ids.required' => 'Pilih minimal satu produk untuk diarsipkan.',
            'product_ids.min' => 'Pilih minimal satu produk untuk diarsipkan.',
            'product_ids.max' => 'Maksimal 500 produk per permintaan arsip.',
            'confirm.accepted' => $archiveAll
                ? 'Centang konfirmasi untuk mengarsipkan semua produk.'
                : 'Centang konfirmasi untuk mengarsipkan produk terpilih.',
        ]);

        $archived = 0;
        $zeroed = 0;

        if ($archiveAll) {
            $base = $this->speedshopMasterProductsQuery();

            DB::transaction(function () use ($base, $speedshopId, &$archived, &$zeroed) {
                foreach ((clone $base)->orderBy('id')->cursor() as $product) {
                    if ($this->outletStockQty($product, (int) $speedshopId) > 0) {
                        $zeroed++;
                    }
                    $this->archiveProductForSpeedshop($product, (int) $speedshopId, false);
                    $archived++;
                }
            });
        } else {
            $ids = array_values(array_unique(array_map('intval', $validated['product_ids'] ?? [])));

            if ($ids === []) {
                $message = 'Pilih minimal satu produk untuk diarsipkan.';
                if ($request->wantsJson()) {
                    return response()->json(['message' => $message], 422);
                }

                return redirect()->route($this->masterIndexRouteName())->with('error', $message);
            }

            $base = $this->speedshopMasterProductsQuery()
                ->whereIn('id', $ids);

            $products = (clone $base)->orderBy('id')->get();

            if ($products->count() !== count($ids)) {
                $message = 'Beberapa produk tidak valid, sudah diarsipkan, atau bukan milik outlet ini.';
                if ($request->wantsJson()) {
                    return response()->json(['message' => $message], 422);
                }

                return redirect()->route($this->masterIndexRouteName())->with('error', $message);
            }

            DB::transaction(function () use ($products, $speedshopId, &$archived, &$zeroed) {
                foreach ($products as $product) {
                    if ($this->outletStockQty($product, (int) $speedshopId) > 0) {
                        $zeroed++;
                    }
                    $this->archiveProductForSpeedshop($product, (int) $speedshopId, false);
                    $archived++;
                }
            });
        }

        $msg = $archived > 0
            ? "{$archived} produk diarsipkan dari master speedshop."
            : 'Tidak ada produk yang diarsipkan.';
        if ($zeroed > 0) {
            $msg .= " Stok outlet {$zeroed} produk direset menjadi 0.";
        }
        $msg .= ' Pulihkan lewat menu Arsip produk di sidebar jika perlu.';

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'archived' => $archived,
                'stock_zeroed' => $zeroed,
                'message' => $msg,
            ]);
        }

        return redirect()->route($this->masterIndexRouteName())->with('success', $msg);
    }

    public function trashedIndex(Request $request)
    {
        if (! auth()->user()?->canManageProductMasterCatalog()) {
            abort(403);
        }

        $ssid = auth()->user()?->activeSpeedshopId();
        if (! $ssid) {
            if ($request->boolean('modal')) {
                return response('<div class="px-5 py-12 text-center text-sm text-gray-500">Pilih cabang speedshop di navbar terlebih dahulu.</div>', 200)
                    ->header('Content-Type', 'text/html; charset=UTF-8');
            }

            return redirect()->route($this->masterIndexRouteName())->with('error', 'Outlet speedshop tidak terdeteksi.');
        }

        $with = [
            'category',
            'brand',
            'speedshopMasterArchives' => function ($q) use ($ssid) {
                $q->where('speedshop_id', (int) $ssid);
                if (SpeedshopMasterProductArchive::supportsPurgedAtColumn()) {
                    $q->whereNull('purged_at');
                }
            },
            'speedshopStocks' => fn ($q) => $q->where('speedshop_id', (int) $ssid),
        ];

        $query = Product::withTrashed()
            ->with($with)
            ->forUser();
        $this->applySpeedshopTrashedCatalogQuery($query, (int) $ssid);

        if ($request->filled('kode')) {
            $kode = (string) $request->kode;
            $query->where(function ($q) use ($kode, $ssid) {
                $this->constrainKodeProdukSearch($q, $kode, 'products.kode_produk');
                if (SpeedshopMasterProductArchive::supportsOriginalKodeColumn()) {
                    $q->orWhereHas('speedshopMasterArchives', function ($aq) use ($kode, $ssid) {
                        $aq->where('speedshop_id', (int) $ssid)
                            ->where('original_kode_produk', 'like', '%'.$kode.'%');
                        if (SpeedshopMasterProductArchive::supportsPurgedAtColumn()) {
                            $aq->whereNull('purged_at');
                        }
                    });
                }
            });
        }

        if ($request->filled('nama')) {
            $query->where('nama_produk', 'like', '%'.$request->nama.'%');
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        $items = $query
            ->leftJoin('speedshop_master_product_archives as smpa', function ($join) use ($ssid) {
                $join->on('smpa.product_id', '=', 'products.id')
                    ->where('smpa.speedshop_id', (int) $ssid);
                if (SpeedshopMasterProductArchive::supportsPurgedAtColumn()) {
                    $join->whereNull('smpa.purged_at');
                }
            })
            ->orderByDesc(DB::raw('COALESCE(smpa.archived_at, products.hidden_from_warehouse_master_at, products.updated_at)'))
            ->select('products.*')
            ->paginate(20)
            ->withQueryString();

        $categories = Category::categoriesForSpeedshopProductUi(
            auth()->user()?->activeWarehouseId(),
            (int) $ssid,
        );
        $mp = $this->masterPageConfig();
        $brands = $this->speedshopBrandsForMasterUi($mp['is_part_luar']);

        $totalTrashedCountQuery = Product::withTrashed()
            ->forUser();
        $this->applySpeedshopTrashedCatalogQuery($totalTrashedCountQuery, (int) $ssid);
        $totalTrashedCount = (int) $totalTrashedCountQuery->count();

        $canManageUnusedCleanup = auth()->user()?->canManageProductMasterCatalog() ?? false;

        if ($request->boolean('modal')) {
            return view('speedshop.master-data.partials.produk-trashed-panel', compact('items', 'categories', 'brands', 'ssid', 'totalTrashedCount', 'canManageUnusedCleanup', 'mp'));
        }

        return view('speedshop.master-data.produk-trashed', compact('items', 'categories', 'brands', 'ssid', 'totalTrashedCount', 'canManageUnusedCleanup', 'mp'));
    }

    /**
     * Daftar produk arsip master outlet yang belum dipakai di operasional outlet ini.
     */
    public function unusedProducts(Request $request): JsonResponse
    {
        if (! auth()->user()?->canManageProductMasterCatalog()) {
            abort(403);
        }

        $speedshopId = auth()->user()?->activeSpeedshopId();
        $warehouseId = auth()->user()?->activeWarehouseId();
        if (! $speedshopId || ! $warehouseId) {
            return response()->json(['message' => 'Outlet speedshop tidak terdeteksi.'], 422);
        }

        $base = $this->unusedProductsBaseQuery((int) $warehouseId, (int) $speedshopId);
        $total = (clone $base)->count();

        $products = (clone $base)
            ->with([
                'category',
                'brand',
                'speedshopStocks' => fn ($q) => $q->where('speedshop_id', (int) $speedshopId),
                'speedshopMasterArchives' => function ($q) use ($speedshopId) {
                    $q->where('speedshop_id', (int) $speedshopId);
                    if (SpeedshopMasterProductArchive::supportsPurgedAtColumn()) {
                        $q->whereNull('purged_at');
                    }
                },
            ])
            ->orderBy('nama_produk')
            ->limit(self::UNUSED_PRODUCTS_PREVIEW_LIMIT)
            ->get(['id', 'kode_produk', 'nama_produk', 'category_id', 'brand_id', 'speedshop_scope_id', 'created_at']);

        $ssid = (int) $speedshopId;
        $items = $products->map(function (Product $p) use ($ssid) {
            $ownedByOutlet = (int) $p->speedshop_scope_id === $ssid;
            $displayKode = $p->speedshopMasterArchives->first()?->original_kode_produk ?: $p->kode_produk;

            return [
                'id' => $p->id,
                'kode_produk' => $displayKode,
                'nama_produk' => $p->nama_produk,
                'kategori' => $p->category?->nama,
                'brand' => $p->brand?->nama,
                'stok_outlet' => (int) ($p->speedshopStocks->first()->jumlah ?? 0),
                'scope' => $ownedByOutlet ? 'Produk outlet' : 'Katalog gudang',
                'efek_hapus' => $ownedByOutlet
                    ? 'Hapus permanen dari database'
                    : 'Hilang dari master & arsip outlet (produk gudang tetap ada)',
                'created_at' => $p->created_at?->format('d/m/Y'),
            ];
        })->values();

        return response()->json([
            'total' => $total,
            'items' => $items,
            'preview_limit' => self::UNUSED_PRODUCTS_PREVIEW_LIMIT,
            'preview_truncated' => $total > self::UNUSED_PRODUCTS_PREVIEW_LIMIT,
        ]);
    }

    /**
     * Bersihkan / hapus permanen produk arsip outlet yang tidak terpakai di operasional outlet ini.
     * Diproses per batch (lihat remaining) agar request tidak timeout di nginx.
     */
    public function bulkForceDeleteUnused(Request $request): JsonResponse
    {
        if (! auth()->user()?->canManageProductMasterCatalog()) {
            abort(403);
        }

        $speedshopId = auth()->user()?->activeSpeedshopId();
        $warehouseId = auth()->user()?->activeWarehouseId();
        if (! $speedshopId || ! $warehouseId) {
            return response()->json(['message' => 'Outlet speedshop tidak terdeteksi.'], 422);
        }

        $request->validate([
            'confirm' => ['accepted'],
            'batch_size' => ['sometimes', 'integer', 'min:20', 'max:150'],
        ], [
            'confirm.accepted' => 'Centang konfirmasi untuk menghapus permanen.',
        ]);

        @set_time_limit(120);
        ini_set('max_execution_time', '120');

        $batchSize = (int) ($request->input('batch_size') ?: self::UNUSED_CLEANUP_BATCH_SIZE);
        $batchSize = max(20, min(150, $batchSize));

        $base = $this->unusedProductsBaseQuery((int) $warehouseId, (int) $speedshopId);
        $remainingBefore = (int) (clone $base)->count();

        $ids = (clone $base)
            ->orderBy('id')
            ->limit($batchSize)
            ->pluck('id')
            ->all();

        if ($ids === []) {
            return response()->json([
                'success' => false,
                'deleted' => 0,
                'skipped' => 0,
                'remaining' => 0,
                'done' => true,
                'message' => 'Tidak ada produk yang dibersihkan.',
                'skip_notes' => [],
            ], 422);
        }

        $products = Product::query()
            ->forUser()
            ->whereIn('id', $ids)
            ->get();

        $result = $this->speedshopProductCleanup->cleanupArchivedProductsBatch(
            $products,
            (int) $warehouseId,
            (int) $speedshopId,
        );

        $remaining = (int) $this->unusedProductsBaseQuery((int) $warehouseId, (int) $speedshopId)->count();
        $deleted = (int) ($result['deleted'] ?? 0);
        $skipped = (int) ($result['skipped'] ?? 0);
        $done = $remaining === 0;

        $msg = $deleted > 0
            ? "Batch: membersihkan {$deleted} produk."
                .($done ? ' Semua produk tidak terpakai selesai.' : " Sisa {$remaining} produk.")
            : 'Tidak ada produk yang dibersihkan pada batch ini.';
        if ($skipped > 0) {
            $msg .= " ({$skipped} dilewati)";
        }

        return response()->json([
            'success' => $deleted > 0 || $done,
            'deleted' => $deleted,
            'skipped' => $skipped,
            'remaining' => $remaining,
            'remaining_before' => $remainingBefore,
            'done' => $done,
            'message' => $msg,
            'skip_notes' => $result['skip_notes'] ?? [],
        ], ($deleted > 0 || $done) ? 200 : 422);
    }

    private function unusedProductsBaseQuery(int $warehouseId, int $speedshopId)
    {
        $query = Product::query()
            ->with(['category:id,nama', 'brand:id,nama'])
            ->forUser()
            ->where('warehouse_id', $warehouseId)
            ->whereHas('speedshopMasterArchives', function ($q) use ($speedshopId) {
                $q->where('speedshop_id', $speedshopId);
                if (SpeedshopMasterProductArchive::supportsPurgedAtColumn()) {
                    $q->whereNull('purged_at');
                }
            });
        $this->applyMasterJenisFilter($query);

        return SpeedshopMasterCleanableProductQuery::applyNotUsedBySpeedshopOutletScope($query, $speedshopId);
    }

    public function restoreTrashed(Request $request, int $id): JsonResponse|RedirectResponse
    {
        if (! auth()->user()?->canManageProductMasterCatalog()) {
            abort(403);
        }

        $trashedRoute = $this->masterIndexRouteName().'.trashed';
        $speedshopId = auth()->user()?->activeSpeedshopId();
        if (! $speedshopId) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Outlet speedshop tidak terdeteksi.'], 422);
            }

            return redirect()->route($trashedRoute)->with('error', 'Outlet speedshop tidak terdeteksi.');
        }

        $productQuery = Product::withTrashed()->forUser()->whereKey($id);
        $this->applyMasterJenisFilter($productQuery);
        $product = $productQuery->firstOrFail();

        $this->forceClearMasterArchiveFlags((int) $product->id);
        $restored = SpeedshopMasterProductArchive::restoreForOutlet((int) $speedshopId, (int) $product->id);
        $fresh = Product::query()->whereKey($product->id)->first() ?? $product;
        $visible = $this->isVisibleInCurrentMasterCatalog($fresh, (int) $speedshopId);

        $label = $this->masterJenisProduk() === Product::JENIS_PART_LUAR ? 'Part luar' : 'Produk';

        if (! $visible && $restored === 0) {
            $stillArchived = SpeedshopMasterProductArchive::isArchivedForOutlet((int) $speedshopId, (int) $product->id);
            $message = $stillArchived
                ? $label.' tidak dapat dipulihkan: kode produk sudah dipakai produk aktif lain. Ganti kode produk aktif terlebih dahulu.'
                : $label.' tidak dalam status arsip master speedshop.';

            if ($request->wantsJson()) {
                return response()->json(['message' => $message], $stillArchived ? 422 : 404);
            }

            return redirect()->route($trashedRoute)->with('error', $message);
        }

        $msg = $label.' ditampilkan kembali di master speedshop.';
        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return redirect()->route($trashedRoute)->with('success', $msg);
    }

    public function restoreAllTrashed(Request $request): JsonResponse|RedirectResponse
    {
        if (! auth()->user()?->canManageProductMasterCatalog()) {
            abort(403);
        }

        $trashedRoute = $this->masterIndexRouteName().'.trashed';
        $speedshopId = auth()->user()?->activeSpeedshopId();
        if (! $speedshopId) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Outlet speedshop tidak terdeteksi.'], 422);
            }

            return redirect()->route($trashedRoute)->with('error', 'Outlet speedshop tidak terdeteksi.');
        }

        $request->validate([
            'confirm' => ['accepted'],
        ], [
            'confirm.accepted' => 'Centang konfirmasi pemulihan semua produk.',
        ]);

        $productIdsQuery = Product::withTrashed()
            ->forUser();
        $this->applySpeedshopTrashedCatalogQuery($productIdsQuery, (int) $speedshopId);
        $productIds = $productIdsQuery->pluck('id');
        $candidateCount = $productIds->count();

        if ($productIds->isNotEmpty()) {
            $this->forceClearMasterArchiveFlags($productIds->all());
        }

        if ($productIds->isNotEmpty()) {
            SpeedshopMasterProductArchive::restoreForOutlet((int) $speedshopId, $productIds->all());
        }

        $stillArchivedQuery = Product::query()
            ->forUser()
            ->whereIn('id', $productIds);
        $this->applySpeedshopTrashedCatalogQuery($stillArchivedQuery, (int) $speedshopId);
        $skipped = $productIds->isEmpty() ? 0 : (int) $stillArchivedQuery->count();
        $restored = max(0, $candidateCount - $skipped);
        $label = $this->masterJenisProduk() === Product::JENIS_PART_LUAR ? 'part luar' : 'produk';
        $msg = $restored > 0
            ? "Berhasil memulihkan {$restored} {$label} ke daftar master speedshop."
                .($skipped > 0 ? " {$skipped} dilewati karena kode sudah dipakai produk aktif." : '')
            : ($skipped > 0
                ? 'Tidak ada arsip yang dapat dipulihkan: kode produk sudah dipakai produk aktif lain.'
                : 'Tidak ada arsip untuk dipulihkan.');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => $restored > 0,
                'restored' => $restored,
                'message' => $msg,
            ], $restored > 0 ? 200 : 422);
        }

        if ($restored > 0) {
            return redirect()->route($trashedRoute)->with('success', $msg);
        }

        return redirect()->route($trashedRoute)->with('error', $msg);
    }

    private function applySpeedshopTrashedCatalogQuery($query, int $speedshopId)
    {
        $this->applyMasterJenisFilter($query);

        $archiveConstraint = function ($q) use ($speedshopId) {
            $q->where('speedshop_id', $speedshopId);
            if (SpeedshopMasterProductArchive::supportsPurgedAtColumn()) {
                $q->whereNull('purged_at');
            }
        };

        if ($this->isWarehouseSharedCatalog()) {
            return $query->where('speedshop_scope_id', 0)
                ->where(function ($q) use ($archiveConstraint) {
                    $q->whereNotNull('hidden_from_warehouse_master_at')
                        ->orWhereNotNull('deleted_at')
                        ->orWhereHas('speedshopMasterArchives', $archiveConstraint);
                });
        }

        return $query->whereHas('speedshopMasterArchives', $archiveConstraint);
    }

    private function findExistingProductByKode(?int $warehouseId, int $scopeId, string $kode): ?Product
    {
        $query = Product::withTrashed()
            ->where('speedshop_scope_id', $scopeId)
            ->where(function ($q) use ($kode) {
                $q->where('kode_produk', $kode)
                    ->orWhereRaw('UPPER(TRIM(kode_produk)) = ?', [$kode]);
            });

        if ($this->isWarehouseSharedCatalog() && $warehouseId) {
            $query->where(function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId)
                    ->orWhereNull('warehouse_id');
            });
        } else {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->first();
    }

    private function isVisibleInCurrentMasterCatalog(Product $product, ?int $speedshopId): bool
    {
        if (! $product->exists || $product->trashed()) {
            return false;
        }

        $query = Product::query()->whereKey($product->id);
        $this->applySpeedshopMasterCatalogQuery($query, $speedshopId);
        if (! $this->isWarehouseSharedCatalog()) {
            $query->forUser();
        }

        return $query->exists();
    }

    private function canReactivateExistingMasterProduct(Product $existing, ?int $speedshopId): bool
    {
        if (ProductMasterCatalog::kindForProduct($existing) !== $this->masterCatalogKind()) {
            return false;
        }

        return ! $this->isVisibleInCurrentMasterCatalog($existing, $speedshopId);
    }

    /**
     * @param  int|list<int>  $productIds
     */
    private function forceClearMasterArchiveFlags(int|array $productIds): void
    {
        $ids = array_values(array_filter(array_map('intval', is_array($productIds) ? $productIds : [$productIds])));
        if ($ids === []) {
            return;
        }

        Product::withTrashed()->whereIn('id', $ids)->update([
            'deleted_at' => null,
            'hidden_from_warehouse_master_at' => null,
        ]);
    }

    private function constrainKodeProdukSearch($query, string $kode, string $column = 'kode_produk'): void
    {
        $kode = trim($kode);
        if ($kode === '') {
            return;
        }

        $normalized = mb_strtoupper($kode);
        $query->where(function ($q) use ($kode, $normalized, $column) {
            $q->where($column, 'like', '%'.$kode.'%')
                ->orWhereRaw('UPPER(TRIM('.$column.')) = ?', [$normalized]);
        });
    }

    private function duplicateKodeProdukMessage(Product $product): string
    {
        $nama = trim((string) $product->nama_produk);
        $nama = $nama !== '' ? $nama : 'tanpa nama';
        $kindLabel = ProductMasterCatalog::label(ProductMasterCatalog::kindForProduct($product));

        if (ProductMasterCatalog::kindForProduct($product) !== $this->masterCatalogKind()) {
            return "Kode sudah dipakai di {$kindLabel}: {$nama}.";
        }

        return "Kode sudah dipakai produk aktif: {$nama}. Cari nama itu di daftar produk (filter kode kadang tidak kena jika ada spasi/karakter tersembunyi).";
    }

    private function outletStockQty(Product $product, int $speedshopId): int
    {
        return (int) (SpeedshopStock::query()
            ->where('speedshop_id', $speedshopId)
            ->where('product_id', (int) $product->id)
            ->value('jumlah') ?? 0);
    }

    private function zeroSpeedshopStockForArchive(Product $product, int $speedshopId): int
    {
        $row = SpeedshopStock::query()
            ->where('speedshop_id', $speedshopId)
            ->where('product_id', (int) $product->id)
            ->first();

        $qty = (int) ($row->jumlah ?? 0);
        if ($qty <= 0) {
            return 0;
        }

        $row->update(['jumlah' => 0]);

        return $qty;
    }

    private function archiveProductForSpeedshop(Product $product, int $speedshopId, bool $wrapTransaction = true): void
    {
        $archive = function () use ($product, $speedshopId) {
            $this->zeroSpeedshopStockForArchive($product, $speedshopId);

            $currentKode = trim((string) $product->kode_produk);
            $placeholder = SpeedshopMasterProductArchive::archivedKodePlaceholder((int) $product->id);
            $payload = ['archived_at' => now()];

            $existingArchive = SpeedshopMasterProductArchive::query()
                ->where('speedshop_id', $speedshopId)
                ->where('product_id', (int) $product->id)
                ->first();

            if (
                SpeedshopMasterProductArchive::supportsOriginalKodeColumn()
                && blank($existingArchive?->original_kode_produk)
                && $currentKode !== ''
                && $currentKode !== $placeholder
            ) {
                $payload['original_kode_produk'] = $currentKode;
            }

            SpeedshopMasterProductArchive::updateOrCreate(
                [
                    'speedshop_id' => $speedshopId,
                    'product_id' => (int) $product->id,
                ],
                $payload
            );

            // Outlet-scoped: bebaskan unik (warehouse+scope+kode) supaya upload kode sama = produk baru.
            SpeedshopMasterProductArchive::releaseOutletScopedKode($product, $speedshopId);
        };

        if ($wrapTransaction) {
            DB::transaction($archive);

            return;
        }

        $archive();
    }

    /**
     * Produk yang masih punya stok outlet tidak boleh tetap di arsip master (mis. setelah import ulang).
     */
    private function reconcileArchivedMasterProductsWithStock(int $speedshopId): void
    {
        $archivedProductIds = SpeedshopMasterProductArchive::query()
            ->where('speedshop_id', $speedshopId)
            ->whereIn('product_id', SpeedshopStock::query()
                ->where('speedshop_id', $speedshopId)
                ->where('jumlah', '>', 0)
                ->select('product_id'))
            ->pluck('product_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($archivedProductIds !== []) {
            SpeedshopMasterProductArchive::restoreForOutlet($speedshopId, $archivedProductIds);
        }
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Product>  $query
     * @return array{jual: string}
     */
    private function resolveAppliedPricePercents($query): array
    {
        $isShared = $this->isWarehouseSharedCatalog();
        $products = (clone $query)->get(['hpp', 'harga_jual_speedshop', 'harga_eceran_terendah']);

        if ($isShared) {
            return [
                'jual' => $this->formatPercentInput($this->uniformMarkupPercent($products, 'harga_eceran_terendah', 'harga_jual_speedshop')),
            ];
        }

        return [
            'jual' => $this->formatPercentInput($this->uniformMarkupPercent($products, 'harga_jual_speedshop')),
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Product>  $products
     */
    private function uniformMarkupPercent($products, string $field, string $baseField = 'hpp'): ?float
    {
        $baseOf = function ($p) use ($baseField) {
            $base = (float) $p->{$baseField};
            if ($base <= 0 && $baseField !== 'hpp') {
                $base = (float) $p->hpp;
            }

            return $base;
        };

        $usable = $products->filter(fn ($p) => $baseOf($p) > 0);
        if ($usable->isEmpty()) {
            return null;
        }

        $first = $usable->first();
        $raw = (((float) $first->{$field} / $baseOf($first)) - 1) * 100;
        $candidates = array_values(array_unique([
            round($raw, 2),
            round($raw, 1),
            round($raw, 0),
        ]));

        foreach ($candidates as $percent) {
            $factor = 1 + ($percent / 100);
            $matches = $usable->every(function ($p) use ($field, $factor, $baseOf) {
                return (int) round($baseOf($p) * $factor) === (int) round((float) $p->{$field});
            });
            if ($matches) {
                return (float) $percent;
            }
        }

        return null;
    }

    private function formatPercentInput(?float $percent): string
    {
        if ($percent === null) {
            return '';
        }

        return rtrim(rtrim(number_format($percent, 2, '.', ''), '0'), '.');
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Product>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Product>
     */
    private function applySpeedshopMasterProdukFilters($query, Request $request, ?Brand $selectedBrand = null, bool $unbranded = false)
    {
        if ($unbranded || $request->boolean('unbranded')) {
            $query->whereNull('brand_id');
        } elseif ($selectedBrand) {
            $query->where('brand_id', $selectedBrand->id);
        } elseif ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        if ($request->filled('kode')) {
            $this->constrainKodeProdukSearch($query, (string) $request->kode);
        }

        if ($request->filled('nama')) {
            $query->where('nama_produk', 'like', '%'.$request->nama.'%');
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if (
            $request->filled('search')
            && ! $request->filled('kode')
            && ! $request->filled('nama')
            && ! $request->filled('category_id')
            && ! $request->filled('brand_id')
            && ! $request->boolean('unbranded')
            && $selectedBrand === null
            && ! $unbranded
        ) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('kode_produk', 'like', "%{$search}%")
                    ->orWhere('nama_produk', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    private function renderSpeedshopMasterProduk(Request $request, bool $browseBrands, ?Brand $selectedBrand = null, bool $unbranded = false)
    {
        $ssid = auth()->user()?->activeSpeedshopId();
        $stockScope = $this->masterOutletStockScope();
        $isAllScope = $stockScope['is_all_scope'];
        if ($ssid && ! $isAllScope) {
            $this->reconcileArchivedMasterProductsWithStock((int) $ssid);
        }

        $mp = $this->masterPageConfig();
        $categories = Category::categoriesForSpeedshopProductUi(
            auth()->user()?->activeWarehouseId(),
            $ssid ? (int) $ssid : null,
        );
        $brands = $this->speedshopBrandsForMasterUi($mp['is_part_luar']);
        $suppliers = $ssid
            ? Supplier::query()->forSpeedshop((int) $ssid)->orderBy('nama')->get(['id', 'kode_supplier', 'nama'])
            : Supplier::query()->forSection(Supplier::SECTION_SPEEDSHOP)->orderBy('nama')->get(['id', 'kode_supplier', 'nama']);

        $totalCatalogCount = 0;
        $unbrandedCount = 0;
        $brandFolders = collect();
        $canCount = $ssid || $isAllScope || $this->isWarehouseSharedCatalog();
        if ($canCount) {
            $totalCatalogCount = (int) $this->speedshopMasterProductsQuery([], true)->count();

            $countRows = $this->speedshopMasterProductsQuery([], true)
                ->selectRaw('brand_id, COUNT(*) as produk_count')
                ->groupBy('brand_id')
                ->get();

            $countsByBrand = [];
            foreach ($countRows as $row) {
                if ($row->brand_id === null) {
                    $unbrandedCount = (int) $row->produk_count;
                } else {
                    $countsByBrand[(int) $row->brand_id] = (int) $row->produk_count;
                }
            }

            $brandFolders = $brands->map(function (Brand $brand) use ($countsByBrand) {
                $brand->produk_count = (int) ($countsByBrand[$brand->id] ?? 0);

                return $brand;
            })->sortBy([
                ['produk_count', 'desc'],
                ['nama', 'asc'],
            ])->values();
        } else {
            $brandFolders = $brands->map(function (Brand $brand) {
                $brand->produk_count = 0;

                return $brand;
            })->sortBy('nama')->values();
        }

        if ($browseBrands) {
            $items = Product::query()->whereRaw('1 = 0')->paginate(20);

            return view('speedshop.master-data.produk', compact(
                'items',
                'categories',
                'brands',
                'suppliers',
                'ssid',
                'totalCatalogCount',
                'mp',
                'isAllScope',
                'brandFolders',
                'unbrandedCount',
            ) + [
                'browseBrands' => true,
                'selectedBrand' => null,
                'unbranded' => false,
                'pricePercents' => ['jual' => ''],
            ]);
        }

        $with = ['category', 'brand'];
        $this->eagerLoadOutletStocks($with, $stockScope['stock_speedshop_ids']);
        $query = $this->speedshopMasterProductsQuery($with, true);
        $this->applySpeedshopMasterProdukFilters($query, $request, $selectedBrand, $unbranded);
        $pricePercents = $this->resolveAppliedPricePercents($query);

        $items = $query->orderBy('nama_produk')->paginate(20)->withQueryString();
        $this->attachCombinedSpeedshopStock($items, $stockScope);

        return view('speedshop.master-data.produk', compact(
            'items',
            'categories',
            'brands',
            'suppliers',
            'ssid',
            'totalCatalogCount',
            'mp',
            'isAllScope',
            'brandFolders',
            'unbrandedCount',
            'selectedBrand',
            'unbranded',
            'pricePercents',
        ) + [
            'browseBrands' => false,
        ]);
    }

    public function export(Request $request)
    {
        $filters = $request->only(['kode', 'nama', 'category_id', 'brand_id', 'search', 'unbranded']);

        return Excel::download(new ProdukSpeedshopExport($filters, $this->masterCatalogKind()), 'database-produk-speedshop-'.now()->format('Y-m-d-His').'.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $ssid = auth()->user()?->activeSpeedshopId();
        $stockScope = $this->masterOutletStockScope();
        $isAllScope = $stockScope['is_all_scope'];
        $with = ['category', 'brand'];
        $this->eagerLoadOutletStocks($with, $stockScope['stock_speedshop_ids']);
        $query = $this->speedshopMasterProductsQuery($with, true);
        $this->applySpeedshopMasterProdukFilters($query, $request);

        $items = $query->orderBy('nama_produk')->get();
        $this->attachCombinedSpeedshopStock($items, $stockScope);
        $filename = 'database-produk-speedshop-'.now()->format('Y-m-d-His').'.pdf';

        return Pdf::loadView('speedshop.master-data.produk-pdf', [
            'items' => $items,
            'search' => $request->get('search'),
            'printedAt' => now(),
            'ssid' => $ssid,
            'isAllScope' => $isAllScope,
            'catalogKind' => $this->masterCatalogKind(),
        ])->setPaper('a4', 'landscape')->stream($filename);
    }

    protected function importCatalogSummaryLabel(): string
    {
        return match ($this->masterCatalogKind()) {
            ProductMasterCatalog::PART_LUAR => 'part luar',
            ProductMasterCatalog::MERCHANDISE => 'merchandise',
            ProductMasterCatalog::CONSUMABLE => 'consumable',
            ProductMasterCatalog::MO => 'sparepart MO',
            default => 'master outlet',
        };
    }

    public function template()
    {
        $filename = $this->masterCatalogKind() === ProductMasterCatalog::PART_LUAR
            ? 'template-part-luar-harga.xlsx'
            : 'template-database-produk-harga.xlsx';

        return Excel::download(new ProdukSpeedshopPriceTemplateExport($this->masterCatalogKind()), $filename);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
        ]);

        if (
            in_array($this->masterCatalogKind(), [
                ProductMasterCatalog::PART_LUAR,
                ProductMasterCatalog::SPEEDSHOP_SUPPLIER,
            ], true)
            && ! auth()->user()?->activeSpeedshopId()
        ) {
            return back()->with('error', 'Pilih cabang speedshop di navbar terlebih dahulu.');
        }

        try {
            $importer = new ProdukSpeedshopPriceImport($this->masterCatalogKind());
            Excel::import($importer, $request->file('file'));

            $touched = $importer->updated + $importer->created;
            $unique = $importer->uniqueProductCount();
            $catalogLabel = $this->importCatalogSummaryLabel();
            $parts = [];
            if ($importer->created > 0) {
                $parts[] = 'Menambah '.$importer->created.' baris '.$catalogLabel.' baru (kategori otomatis dibuat jika belum ada).';
            }
            if ($importer->updated > 0) {
                $parts[] = 'Memperbarui '.$importer->updated.' baris '.$catalogLabel.'.';
            }
            if ($touched > 0) {
                $parts[] = 'Total '.$unique.' produk unik di katalog '.$catalogLabel.'.';
                if ($importer->duplicateRows > 0) {
                    $parts[] = '('.$importer->duplicateRows.' baris kode duplikat diabaikan; dipakai baris terakhir per kode.)';
                }
            }
            if ($touched === 0) {
                $parts[] = 'Tidak ada baris yang diproses. Periksa template: untuk produk baru isi Kode, Nama Produk, dan Kategori; untuk pembaruan cukup Kode yang sudah ada.';
            }
            if ($importer->skippedUnknownKode > 0) {
                $parts[] = $importer->skippedUnknownKode.' baris dilewati (kode baru tanpa nama/kategori lengkap).';
            }
            if ($importer->skippedEmpty > 0) {
                $parts[] = $importer->skippedEmpty.' baris tanpa kode produk dilewati.';
            }
            if ($importer->skippedWrongCatalog > 0) {
                $sample = array_slice($importer->skippedWrongCatalogKodes, 0, 5);
                $extra = $importer->skippedWrongCatalog > 5 ? ' dan lainnya' : '';
                $parts[] = $importer->skippedWrongCatalog.' baris dilewati karena kode sudah dipakai katalog lain ('
                    .implode(', ', $sample).$extra.').';
            }
            session()->flash($touched > 0 ? 'success' : 'error', implode(' ', $parts));
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $messages = [];
            foreach ($failures as $failure) {
                $messages[] = 'Baris '.$failure->row().': '.implode(', ', $failure->errors());
            }

            return back()->with('error', implode(' ', $messages));
        } catch (\Throwable $e) {
            return back()->with('error', 'Import gagal: '.$e->getMessage());
        }

        return redirect()->route($this->masterIndexRouteName());
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function applySpeedshopCatalogPrices(array $validated, bool $preserveExistingHpp = false): array
    {
        $hargaSpeedshop = (float) ($validated['harga_jual_speedshop'] ?? 0);
        $validated['harga_jual_speedshop'] = $hargaSpeedshop;

        if ($this->isWarehouseSharedCatalog()) {
            if ($preserveExistingHpp) {
                unset($validated['hpp'], $validated['harga_pembelian']);
            } else {
                $hpp = (float) ($validated['hpp'] ?? 0);
                $validated['hpp'] = $hpp;
                $validated['harga_pembelian'] = $hpp;
            }
            $eceran = (float) ($validated['harga_eceran_terendah'] ?? 0);
            $validated['harga_eceran_terendah'] = $eceran;
            $validated['harga_jual'] = $eceran;

            return $validated;
        }

        $hpp = (float) ($validated['hpp'] ?? 0);
        $validated['hpp'] = $hpp;
        $validated['harga_pembelian'] = $hpp;
        $validated['harga_jual'] = $hargaSpeedshop;
        unset($validated['harga_eceran_terendah']);

        return $validated;
    }

    /**
     * @return list<string>
     */
    private function speedshopReactivateExceptFields(): array
    {
        $except = ['speedshop_scope_id', 'harga_jual_reseler'];
        if ($this->isWarehouseSharedCatalog()) {
            $except[] = 'hpp';
            $except[] = 'harga_pembelian';
        } else {
            $except[] = 'warehouse_id';
            $except[] = 'harga_eceran_terendah';
        }

        return $except;
    }

    /**
     * Outlet yang dipakai kolom Stok Speedshop: satu cabang, atau semua cabang jika filter Semua Speedshop.
     *
     * @return array{is_all_scope: bool, stock_speedshop_ids: list<int>}
     */
    private function masterOutletStockScope(): array
    {
        $user = auth()->user();
        $ids = array_values(array_unique(array_filter(
            array_map('intval', $user?->reportSpeedshopFilterIds() ?? []),
            fn (int $id) => $id > 0,
        )));

        return [
            'is_all_scope' => (bool) ($user?->isSpeedshopAllScopeSelected() ?? false),
            'stock_speedshop_ids' => $ids,
        ];
    }

    /**
     * @param  array<string, mixed>  $with
     * @param  list<int>  $speedshopIds
     */
    private function eagerLoadOutletStocks(array &$with, array $speedshopIds): void
    {
        if ($speedshopIds === []) {
            return;
        }

        $with['speedshopStocks'] = fn ($q) => $q->whereIn('speedshop_id', $speedshopIds);
    }

    /** @param  list<int>  $speedshopIds */
    private function outletStockQtyForScope(Product $product, array $speedshopIds): int
    {
        if ($speedshopIds === []) {
            return 0;
        }

        $productIds = [(int) $product->id];
        if (! $this->isWarehouseSharedCatalog() && (auth()->user()?->isSpeedshopAllScopeSelected() ?? false)) {
            $kode = mb_strtoupper(trim((string) $product->kode_produk));
            if ($kode !== '') {
                $productIds = Product::query()
                    ->forUser()
                    ->forSpeedshopStokCatalogAny($speedshopIds, $this->masterCatalogKind())
                    ->whereRaw('UPPER(TRIM(kode_produk)) = ?', [$kode])
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();
            }
        }

        if ($productIds === []) {
            return 0;
        }

        return (int) SpeedshopStock::query()
            ->whereIn('product_id', $productIds)
            ->whereIn('speedshop_id', $speedshopIds)
            ->sum('jumlah');
    }

    /** @param  array{is_all_scope: bool, stock_speedshop_ids: list<int>}  $stockScope */
    private function shouldSkipOutletStockWrite(array $stockScope): bool
    {
        return (bool) ($stockScope['is_all_scope'] ?? false);
    }

    private function shouldCollapseOutletCatalogByKode(): bool
    {
        if ($this->isWarehouseSharedCatalog()) {
            return false;
        }

        $scope = $this->masterOutletStockScope();

        return ($scope['is_all_scope'] ?? false) && count($scope['stock_speedshop_ids']) > 1;
    }

    /**
     * @param  \Illuminate\Contracts\Pagination\Paginator|\Illuminate\Support\Collection<int, Product>  $items
     * @param  array{is_all_scope: bool, stock_speedshop_ids: list<int>}  $stockScope
     */
    private function attachCombinedSpeedshopStock($items, array $stockScope): void
    {
        $products = method_exists($items, 'getCollection')
            ? $items->getCollection()
            : collect($items);

        if ($products->isEmpty()) {
            return;
        }

        $isAllScope = (bool) ($stockScope['is_all_scope'] ?? false);
        $speedshopIds = $stockScope['stock_speedshop_ids'] ?? [];

        if ($this->isWarehouseSharedCatalog() || ! $isAllScope) {
            foreach ($products as $product) {
                $qty = $isAllScope
                    ? (int) $product->speedshopStocks->sum('jumlah')
                    : (int) ($product->speedshopStocks->first()->jumlah ?? 0);
                $product->setAttribute('combined_speedshop_stock', $qty);
            }

            return;
        }

        $qtyByKode = $this->combinedOutletStockByKode($products, $speedshopIds);
        foreach ($products as $product) {
            $kode = mb_strtoupper(trim((string) $product->kode_produk));
            $product->setAttribute('combined_speedshop_stock', (int) ($qtyByKode[$kode] ?? 0));
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Product>  $products
     * @param  list<int>  $speedshopIds
     * @return array<string, int>
     */
    private function combinedOutletStockByKode($products, array $speedshopIds): array
    {
        $kodes = $products
            ->map(fn (Product $p) => mb_strtoupper(trim((string) $p->kode_produk)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($kodes === [] || $speedshopIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($kodes), '?'));
        $peers = Product::query()
            ->forUser()
            ->forSpeedshopStokCatalogAny($speedshopIds, $this->masterCatalogKind())
            ->whereRaw('UPPER(TRIM(kode_produk)) IN ('.$placeholders.')', $kodes)
            ->get(['id', 'kode_produk']);

        if ($peers->isEmpty()) {
            return [];
        }

        $stockByProduct = SpeedshopStock::query()
            ->whereIn('product_id', $peers->pluck('id')->all())
            ->whereIn('speedshop_id', $speedshopIds)
            ->selectRaw('product_id, SUM(jumlah) as qty')
            ->groupBy('product_id')
            ->pluck('qty', 'product_id');

        $qtyByKode = [];
        foreach ($peers as $peer) {
            $kode = mb_strtoupper(trim((string) $peer->kode_produk));
            $qtyByKode[$kode] = (int) ($qtyByKode[$kode] ?? 0) + (int) ($stockByProduct[(int) $peer->id] ?? 0);
        }

        return $qtyByKode;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @param  list<int>  $speedshopIds
     */
    private function syncOutletCatalogMasterFieldsToSiblings(Product $product, array $validated, array $speedshopIds): void
    {
        if ($this->isWarehouseSharedCatalog() || $speedshopIds === []) {
            return;
        }

        $payload = collect($validated)->only([
            'kode_supplier',
            'nama_produk',
            'category_id',
            'brand_id',
            'hpp',
            'harga_pembelian',
            'harga_jual_speedshop',
            'harga_jual',
            'jenis_produk',
        ])->all();

        if ($payload === []) {
            return;
        }

        $kode = mb_strtoupper(trim((string) $product->kode_produk));
        if ($kode === '') {
            return;
        }

        Product::query()
            ->forUser()
            ->forSpeedshopStokCatalogAny($speedshopIds, $this->masterCatalogKind())
            ->whereRaw('UPPER(TRIM(kode_produk)) = ?', [$kode])
            ->whereKeyNot((int) $product->id)
            ->update($payload);
    }

    private function resolveWarehouseIdForMoCatalog(?int $warehouseId, ?int $speedshopId): ?int
    {
        if ($warehouseId) {
            return (int) $warehouseId;
        }

        if ($speedshopId) {
            $shop = Speedshop::query()->find($speedshopId);
            $fromShop = $shop?->mutasi_warehouse_id ?? $shop?->warehouse_id;
            if ($fromShop) {
                return (int) $fromShop;
            }
        }

        return null;
    }
}
