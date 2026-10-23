<?php

namespace App\Http\Controllers;

use App\Exports\Speedshop\StokProdukSpeedshopExport;
use App\Exports\Speedshop\StokProdukSpeedshopTemplateExport;
use App\Exports\Warehouse\StokProdukWarehouseExport;
use App\Exports\Warehouse\StokProdukWarehouseRingkasanExport;
use App\Exports\Warehouse\StokProdukWarehouseTemplateExport;
use App\Imports\Speedshop\StokProdukSpeedshopImport;
use App\Imports\Warehouse\StokProdukWarehouseImport;
use App\Services\InvoiceSettingsService;
use App\Services\WarehouseSettingsService;
use App\Support\ProductMasterCatalog;
use App\Support\Warehouse\WarehouseInventoriProductQuery;
use App\Support\Warehouse\WarehouseStokRingkasanQuery;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ServiceOrder;
use App\Models\Speedshop;
use App\Models\SpeedshopStock;
use App\Models\StockMovement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class ProductController extends Controller
{
    private function setSpeedshopStockTo(Product $product, int $targetQty, string $reference, string $keterangan, ?int $speedshopId = null): void
    {
        $speedshopId = $speedshopId ?? auth()->user()?->activeSpeedshopId();
        if (! $speedshopId) {
            return;
        }

        $stock = SpeedshopStock::firstOrNew([
            'speedshop_id' => $speedshopId,
            'product_id' => $product->id,
        ]);

        $beforeQty = (int) ($stock->jumlah ?? 0);
        $stock->jumlah = $targetQty;
        $stock->save();

        $delta = $targetQty - $beforeQty;
        if ($delta === 0) {
            return;
        }

        StockMovement::create([
            'product_id' => $product->id,
            'type' => $delta > 0 ? 'masuk' : 'keluar',
            'qty' => abs($delta),
            'keterangan' => $keterangan,
            'reference' => $reference,
        ]);
    }

    private function setWarehouseStockTo(Product $product, int $targetQty, string $reference, string $keterangan): void
    {
        $warehouseId = auth()->user()?->activeWarehouseId();
        if ($warehouseId === null || (int) $product->warehouse_id !== (int) $warehouseId) {
            return;
        }

        $beforeQty = (int) $product->jumlah;
        if ($beforeQty === $targetQty) {
            return;
        }

        $product->update(['jumlah' => $targetQty]);

        $delta = $targetQty - $beforeQty;
        StockMovement::create([
            'product_id' => $product->id,
            'type' => $delta > 0 ? 'masuk' : 'keluar',
            'qty' => abs($delta),
            'keterangan' => $keterangan,
            'reference' => $reference,
        ]);
    }

    public function index(Request $request)
    {
        if ($this->detectSection() === 'speedshop') {
            return redirect()->route('speedshop.stok-produk-mo');
        }

        $inventoriPage = $this->warehouseInventoriPageConfig();
        $catalogKind = $inventoriPage['catalogKind'];
        $user = auth()->user();
        $inventoriStockSpeedshopIds = $this->warehouseInventoriStockSpeedshopIds($catalogKind);
        $inventoriScopeId = $user?->speedshopScopeId();
        $isAllOutletScope = $this->warehouseInventoriIsCombinedOutletScope($catalogKind);
        $isCombinedSharedScope = $this->warehouseInventoriIsCombinedSharedScope($catalogKind);
        // Samakan dengan master head-catalog: outlet terpilih, atau fallback cabang aktif.
        $catalogOutletId = ProductMasterCatalog::isWarehouseSharedKind($catalogKind)
            ? null
            : ($user?->speedshopScopeId() ?? $user?->activeSpeedshopId());

        $query = $this->buildWarehouseInventoriProductsQuery(
            $request,
            $inventoriStockSpeedshopIds !== [] ? $inventoriStockSpeedshopIds : null,
        );

        // Harus sebelum paginate(): paginate() memasang limit/offset pada builder yang sama.
        $speedshopStockMap = collect();
        $totalStok = 0;
        $totalModal = 0.0;
        $inventoriStockLabel = 'Warehouse';
        if ($this->detectSection() === 'warehouse') {
            if ($inventoriStockSpeedshopIds !== []) {
                $totalsQuery = $isAllOutletScope
                    ? $this->buildWarehouseInventoriProductsQuery($request, $inventoriStockSpeedshopIds, false)
                    : $query;
                $productIdsSub = (clone $totalsQuery)->select('products.id');
                $stockBase = SpeedshopStock::query()
                    ->whereIn('speedshop_id', $inventoriStockSpeedshopIds)
                    ->whereIn('product_id', $productIdsSub);
                $totalStok = (int) (clone $stockBase)->sum('jumlah');
                $totalModal = (float) SpeedshopStock::query()
                    ->join('products', 'products.id', '=', 'speedshop_stock.product_id')
                    ->whereIn('speedshop_stock.speedshop_id', $inventoriStockSpeedshopIds)
                    ->whereIn('speedshop_stock.product_id', $productIdsSub)
                    ->sum(DB::raw('speedshop_stock.jumlah * COALESCE(products.hpp, 0)'));

                $labelId = $inventoriScopeId ?? ($isAllOutletScope || $isCombinedSharedScope ? null : $catalogOutletId);
                $inventoriStockLabel = ($isAllOutletScope || $isCombinedSharedScope)
                    ? 'Semua Speedshop'
                    : ($labelId
                        ? (Speedshop::query()->whereKey($labelId)->value('nama') ?: 'Cabang Speedshop')
                        : 'Cabang Speedshop');
            } else {
                $totalStok = (int) (clone $query)->sum('jumlah');
                $totalModal = (float) (clone $query)->sum(DB::raw('jumlah * COALESCE(hpp, 0)'));
            }
        }

        $products = WarehouseInventoriProductQuery::applyStableListOrder(clone $query)
            ->paginate(20)
            ->withQueryString();
        if ($inventoriStockSpeedshopIds !== []) {
            $speedshopStockMap = $isAllOutletScope
                ? $this->combinedOutletStockMapForProducts(
                    $products->getCollection(),
                    $inventoriStockSpeedshopIds,
                    $catalogKind,
                )
                : $this->speedshopStockMapForProducts(
                    $products->getCollection()->pluck('id'),
                    ['filterSpeedshopIds' => $inventoriStockSpeedshopIds],
                );
        }

        $categories = $this->categoriesForCurrentProductSection();
        $brands = $this->brandsForCurrentProductSection();

        $section = $this->detectSection();
        $routePrefix = $section.'.products';
        $canImportStok = $user?->canManageProductMasterCatalog() ?? false;
        $importAllowsWarehouse = ProductMasterCatalog::isWarehouseSharedKind($inventoriPage['catalogKind'] ?? ProductMasterCatalog::MO)
            && $inventoriScopeId === null;
        $importSpeedshops = collect();
        if ($section === 'warehouse' && $canImportStok) {
            $speedshopIds = $user?->warehouseLinkedSpeedshopIds() ?: ($user?->activeSpeedshopIds() ?? []);
            if ($speedshopIds !== []) {
                $importSpeedshops = Speedshop::query()
                    ->whereIn('id', $speedshopIds)
                    ->orderBy('nama')
                    ->get(['id', 'nama']);
            }
        }

        $canEditInventoriStock = $canImportStok && (
            $inventoriScopeId !== null
            || (
                ProductMasterCatalog::isWarehouseSharedKind($catalogKind)
                && ! $isCombinedSharedScope
            )
        );
        $usesOutletStock = $inventoriStockSpeedshopIds !== [];

        return view("{$section}.products.index", compact(
            'products',
            'categories',
            'brands',
            'routePrefix',
            'speedshopStockMap',
            'totalStok',
            'totalModal',
            'inventoriPage',
            'canImportStok',
            'importAllowsWarehouse',
            'importSpeedshops',
            'inventoriScopeId',
            'inventoriStockLabel',
            'canEditInventoriStock',
            'usesOutletStock',
        ));
    }

    /**
     * Halaman inventori produk warehouse per katalog (MO / Speedshop / Part Luar).
     */
    public function warehouseInventoriProduk(Request $request)
    {
        abort_unless($this->isWarehouseSection(), 403);

        return $this->index($request);
    }

    private function detectSection(): string
    {
        $name = request()->route()->getName() ?? '';
        if (str_starts_with($name, 'produksi.')) {
            return 'produksi';
        }
        if (str_starts_with($name, 'warehouse.')) {
            return 'warehouse';
        }
        if (str_starts_with($name, 'speedshop.')) {
            return 'speedshop';
        }

        return 'speedshop';
    }

    /**
     * Kategori master dipisah per section (produksi / gudang / speedshop).
     */
    private function categoriesForCurrentProductSection()
    {
        return match ($this->detectSection()) {
            'produksi' => Category::query()->forProduksi()->orderBy('nama')->get(),
            'warehouse' => Category::query()->forWarehouse()->orderBy('nama')->get(),
            default => Category::categoriesForSpeedshopProductUi(
                auth()->user()?->activeWarehouseId(),
                auth()->user()?->activeSpeedshopId(),
            )->all(),
        };
    }

    private function brandsForCurrentProductSection()
    {
        return Brand::query()->orderBy('nama')->get();
    }

    private function isWarehouseSection(): bool
    {
        return str_starts_with(request()->route()->getName() ?? '', 'warehouse.');
    }

    private function isSpeedshopSection(): bool
    {
        return str_starts_with(request()->route()->getName() ?? '', 'speedshop.');
    }

    public function store(Request $request)
    {
        if ($this->isSpeedshopSection()) {
            abort(404);
        }

        if ($this->isWarehouseSection()) {
            abort(403, 'Warehouse tidak dapat menambah produk. Hanya Admin yang dapat mengelola data produk.');
        }

        $kodeValidated = $request->validate([
            'kode_produk' => ['required', 'string', 'max:50'],
            'stok' => ['nullable', 'integer', 'min:0'],
        ]);
        $stokAwal = (int) ($kodeValidated['stok'] ?? 0);

        if ($this->isSpeedshopSection()) {
            $warehouseId = auth()->user()->activeWarehouseId();
            $existing = Product::query()
                ->where('warehouse_id', $warehouseId)
                ->where('kode_produk', $kodeValidated['kode_produk'])
                ->first();

            if ($existing) {
                $existingValidated = $request->validate([
                    'nama_produk' => ['required', 'string', 'max:255'],
                    'category_id' => ['required', 'exists:categories,id'],
                    'brand_id' => ['nullable', 'exists:brands,id'],
                    'hpp' => ['nullable', 'numeric', 'min:0'],
                    'harga_jual_speedshop' => ['nullable', 'numeric', 'min:0'],
                ]);

                $existing->update([
                    'nama_produk' => $existingValidated['nama_produk'],
                    'category_id' => $existingValidated['category_id'],
                    'brand_id' => $existingValidated['brand_id'] ?? null,
                    'hpp' => (float) ($existingValidated['hpp'] ?? 0),
                    'harga_jual_speedshop' => (float) ($existingValidated['harga_jual_speedshop'] ?? 0),
                ]);

                $this->setSpeedshopStockTo(
                    $existing,
                    $stokAwal,
                    'speedshop_product_form',
                    'Penyesuaian stok dari form master produk speedshop'
                );

                session()->flash('success', 'Produk sudah ada. Data produk dan stok speedshop diperbarui sesuai nilai terbaru.');

                return response()->json(['success' => true, 'updated_existing' => true]);
            }
        }

        $validated = $request->validate([
            'kode_produk' => ['required', 'string', 'max:50', 'unique:products'],
            'nama_produk' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'hpp' => ['nullable', 'numeric', 'min:0'],
            'harga_jual_speedshop' => ['nullable', 'numeric', 'min:0'],
            'harga_jual_reseler' => ['nullable', 'numeric', 'min:0'],
            'harga_eceran_terendah' => ['nullable', 'numeric', 'min:0'],
        ]);

        $validated['jumlah'] = $this->isSpeedshopSection() ? 0 : $stokAwal;
        $validated['hpp'] = (float) ($validated['hpp'] ?? 0);
        $validated['harga_jual_speedshop'] = (float) ($validated['harga_jual_speedshop'] ?? 0);
        $validated['harga_jual_reseler'] = (float) ($validated['harga_jual_reseler'] ?? 0);
        $validated['harga_eceran_terendah'] = (float) ($validated['harga_eceran_terendah'] ?? 0);
        $warehouseId = auth()->user()->activeWarehouseId();
        if ($this->isSpeedshopSection() && ! $warehouseId) {
            throw ValidationException::withMessages([
                'kode_produk' => ['Warehouse cabang tidak terdeteksi. Pilih cabang speedshop di navbar terlebih dahulu.'],
            ]);
        }
        $validated['warehouse_id'] = $warehouseId;
        $product = Product::create($validated);

        if ($this->isSpeedshopSection() && $stokAwal > 0) {
            $this->setSpeedshopStockTo(
                $product,
                $stokAwal,
                'speedshop_product_form',
                'Stok masuk awal dari form master produk speedshop'
            );
        }
        session()->flash('success', 'Produk berhasil ditambahkan.');

        return response()->json(['success' => true]);
    }

    public function show(Product $product)
    {
        if ($this->isSpeedshopSection()) {
            abort(404);
        }

        if ($this->isWarehouseSection()) {
            abort(403, 'Warehouse hanya dapat melihat daftar produk dan stok.');
        }

        return response()->json($product);
    }

    public function update(Request $request, Product $product)
    {
        if ($this->isSpeedshopSection()) {
            abort(404);
        }

        if ($this->isWarehouseSection()) {
            abort(403, 'Warehouse tidak dapat mengubah produk. Hanya Admin yang dapat mengelola data produk.');
        }

        $validated = $request->validate([
            'kode_produk' => ['required', 'string', 'max:50', 'unique:products,kode_produk,'.$product->id],
            'nama_produk' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'hpp' => ['nullable', 'numeric', 'min:0'],
            'harga_jual_speedshop' => ['nullable', 'numeric', 'min:0'],
            'harga_jual_reseler' => ['nullable', 'numeric', 'min:0'],
            'harga_eceran_terendah' => ['nullable', 'numeric', 'min:0'],
        ]);

        $validated['hpp'] = (float) ($validated['hpp'] ?? 0);
        $validated['harga_jual_speedshop'] = (float) ($validated['harga_jual_speedshop'] ?? 0);
        $validated['harga_jual_reseler'] = (float) ($validated['harga_jual_reseler'] ?? 0);
        $validated['harga_eceran_terendah'] = (float) ($validated['harga_eceran_terendah'] ?? 0);
        $product->update($validated);
        session()->flash('success', 'Produk berhasil diperbarui.');

        return response()->json(['success' => true]);
    }

    public function destroy(Product $product)
    {
        if ($this->isSpeedshopSection()) {
            abort(404);
        }

        if ($this->isWarehouseSection()) {
            abort(403, 'Warehouse tidak dapat menghapus produk. Hanya Admin yang dapat mengelola data produk.');
        }

        $product->delete();
        session()->flash('success', 'Produk berhasil dihapus.');

        return response()->json(['success' => true]);
    }

    public function updatePrices(Request $request, Product $product)
    {
        if (! $this->isWarehouseSection()) {
            abort(403, 'Hanya warehouse yang dapat mengupdate harga produk.');
        }

        $warehouseId = auth()->user()->activeWarehouseId();
        if ($warehouseId !== null && $product->warehouse_id !== $warehouseId) {
            abort(403, 'Anda tidak memiliki akses ke produk ini.');
        }
        if ((int) ($product->speedshop_scope_id ?? 0) !== 0) {
            abort(403, 'Harga produk outlet-only tidak diubah dari halaman warehouse.');
        }

        $validated = $request->validate([
            'hpp' => ['required', 'numeric', 'min:0'],
            'harga_jual_speedshop' => ['required', 'numeric', 'min:0'],
            'harga_jual_reseler' => ['required', 'numeric', 'min:0'],
            'harga_eceran_terendah' => ['required', 'numeric', 'min:0'],
        ]);

        $product->update($validated);
        session()->flash('success', 'Harga produk berhasil diperbarui.');

        return response()->json(['success' => true]);
    }

    public function stokProdukSpeedshop(Request $request)
    {
        $stokPage = $this->stokProdukSpeedshopPageConfig();
        $ctx = $this->stokProdukSpeedshopContext();
        $query = $this->buildStokProdukSpeedshopQuery($request, $ctx);
        $this->applyStokProdukSpeedshopSort($query, $request, $ctx);

        $items = $query->paginate(20)->withQueryString();
        $speedshopStockMap = $this->stokProdukSpeedshopStockMap($items->getCollection(), $ctx);
        ['totalStok' => $totalStok, 'totalModal' => $totalModal] = $this->speedshopStockScopeTotals($ctx);
        $categories = Category::categoriesForSpeedshopProductUi(
            $ctx['warehouseId'] !== null ? (int) $ctx['warehouseId'] : null,
            $ctx['singleSpeedshopId'],
        );
        $brands = Brand::query()->forSpeedshop()->orderBy('nama')->get();
        $terlarisActive = $request->boolean('terlaris');
        $canManageStok = auth()->user()?->canManageProductMasterCatalog() ?? false;
        $canEditStok = $canManageStok && $ctx['singleSpeedshopId'] !== null;
        $canImportStok = $canEditStok;
        $isAllScope = (bool) ($ctx['isAllScope'] ?? false);

        return view('speedshop.stok-produk.index', compact(
            'items',
            'speedshopStockMap',
            'totalStok',
            'totalModal',
            'categories',
            'brands',
            'terlarisActive',
            'stokPage',
            'canImportStok',
            'canEditStok',
            'isAllScope',
        ));
    }

    public function exportSpeedshopStock(Request $request)
    {
        abort_unless(str_starts_with(request()->route()->getName() ?? '', 'speedshop.'), 403);

        $ctx = $this->stokProdukSpeedshopContext();
        $filters = $request->only(['kode', 'nama', 'category_id', 'brand_id', 'search', 'terlaris']);

        return Excel::download(
            new StokProdukSpeedshopExport(
                $filters,
                $ctx['filterSpeedshopIds'],
                $ctx['singleSpeedshopId'],
                $ctx['warehouseId'],
                $ctx['catalogKind'],
            ),
            ProductMasterCatalog::stokExportFilenamePrefix($ctx['catalogKind']).'-'.now()->format('Y-m-d-His').'.xlsx'
        );
    }

    public function exportSpeedshopStockPdf(Request $request)
    {
        abort_unless(str_starts_with(request()->route()->getName() ?? '', 'speedshop.'), 403);

        ini_set('memory_limit', '1536M');
        set_time_limit(120);

        $stokPage = $this->stokProdukSpeedshopPageConfig();
        $ctx = $this->stokProdukSpeedshopContext();
        $query = $this->buildStokProdukSpeedshopQuery($request, $ctx);
        $this->applyStokProdukSpeedshopSort($query, $request, $ctx);
        $items = $query->get();
        $speedshopStockMap = $this->stokProdukSpeedshopStockMap($items, $ctx);

        $filename = ProductMasterCatalog::stokExportFilenamePrefix($ctx['catalogKind']).'-'.now()->format('Y-m-d-His').'.pdf';

        return Pdf::loadView('speedshop.stok-produk.pdf', [
            'items' => $items,
            'speedshopStockMap' => $speedshopStockMap,
            'filters' => $request->only(['kode', 'nama', 'category_id', 'brand_id', 'search', 'terlaris']),
            'printedAt' => now(),
            'stokPage' => $stokPage,
        ])->setPaper('a4', 'landscape')->stream($filename);
    }

    public function stokProdukAudit(Request $request)
    {
        abort_unless(str_starts_with(request()->route()->getName() ?? '', 'speedshop.'), 403);

        $stokPage = $this->stokProdukSpeedshopPageConfig();
        $ctx = $this->stokProdukSpeedshopContext();
        $invoiceSettings = app(InvoiceSettingsService::class);
        $auditPercent = (float) ($invoiceSettings->get()['stok_audit_percent'] ?? InvoiceSettingsService::STOK_AUDIT_DEFAULT_PERCENT);

        $baseQuery = $this->buildStokProdukSpeedshopQuery(new Request, $ctx);
        $totalProducts = (int) (clone $baseQuery)->count('products.id');
        $sampleSize = $invoiceSettings->resolveStokAuditSampleSize($totalProducts);

        $sessionKey = $this->stokProdukAuditSessionKey($ctx['catalogKind']);
        $shouldRefresh = $request->boolean('refresh') || ! session()->has($sessionKey);

        if ($totalProducts === 0) {
            session()->forget($sessionKey);

            return view('speedshop.stok-produk.audit', [
                'items' => collect(),
                'speedshopStockMap' => collect(),
                'totalProducts' => 0,
                'sampleSize' => 0,
                'auditPercent' => $auditPercent,
                'canApplyAdjustments' => false,
                'stokPage' => $stokPage,
            ]);
        }

        if ($shouldRefresh) {
            $sampleIds = (clone $baseQuery)
                ->inRandomOrder()
                ->limit($sampleSize)
                ->pluck('products.id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            session([
                $sessionKey => $sampleIds,
                'speedshop_stok_audit_meta_'.$ctx['catalogKind'] => [
                    'total_products' => $totalProducts,
                    'sample_size' => count($sampleIds),
                    'audit_percent' => $auditPercent,
                ],
            ]);
        } else {
            $sampleIds = array_values(array_map('intval', session($sessionKey, [])));
        }

        if ($sampleIds === []) {
            $sampleIds = (clone $baseQuery)
                ->inRandomOrder()
                ->limit($sampleSize)
                ->pluck('products.id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();
            session([$sessionKey => $sampleIds]);
        }

        $items = Product::with(['category', 'brand'])
            ->whereIn('id', $sampleIds)
            ->get()
            ->sortBy(fn (Product $product) => array_search($product->id, $sampleIds, true))
            ->values();

        $speedshopStockMap = $this->stokProdukSpeedshopStockMap($items, $ctx);
        $canApplyAdjustments = $ctx['singleSpeedshopId'] !== null;

        return view('speedshop.stok-produk.audit', [
            'items' => $items,
            'speedshopStockMap' => $speedshopStockMap,
            'totalProducts' => $totalProducts,
            'sampleSize' => count($sampleIds),
            'auditPercent' => $auditPercent,
            'canApplyAdjustments' => $canApplyAdjustments,
            'stokPage' => $stokPage,
        ]);
    }

    public function stokProdukAuditApply(Request $request): RedirectResponse
    {
        abort_unless(str_starts_with(request()->route()->getName() ?? '', 'speedshop.'), 403);

        $stokPage = $this->stokProdukSpeedshopPageConfig();
        $ctx = $this->stokProdukSpeedshopContext();
        $auditRoute = $stokPage['auditRoute'];
        $sessionKey = $this->stokProdukAuditSessionKey($ctx['catalogKind']);

        $speedshopId = auth()->user()?->isSpeedshopAllScopeSelected()
            ? null
            : (auth()->user()?->speedshopScopeId() ?? auth()->user()?->activeSpeedshopId());
        if (! $speedshopId) {
            return redirect()
                ->route($auditRoute)
                ->with('error', 'Pilih satu cabang speedshop untuk menyimpan penyesuaian audit stok.');
        }

        $sessionIds = array_map('intval', session($sessionKey, []));
        if ($sessionIds === []) {
            return redirect()
                ->route($auditRoute, ['refresh' => 1])
                ->with('error', 'Daftar audit kosong. Silakan acak ulang produk audit.');
        }

        $data = $request->validate([
            'physical' => ['required', 'array'],
            'physical.*' => ['nullable', 'integer', 'min:0'],
        ]);

        $reference = 'audit-stok-'.now()->format('YmdHis');
        $adjusted = 0;

        foreach ($data['physical'] as $productId => $qty) {
            $productId = (int) $productId;
            if (! in_array($productId, $sessionIds, true)) {
                continue;
            }
            if ($qty === null || $qty === '') {
                continue;
            }

            $product = Product::query()->find($productId);
            if (! $product) {
                continue;
            }

            $this->setSpeedshopStockTo(
                $product,
                (int) $qty,
                $reference,
                'Penyesuaian audit stok',
            );
            $adjusted++;
        }

        session()->forget([$sessionKey, 'speedshop_stok_audit_meta_'.$ctx['catalogKind']]);

        $message = $adjusted > 0
            ? "Penyesuaian audit stok berhasil disimpan untuk {$adjusted} produk."
            : 'Tidak ada penyesuaian stok yang disimpan. Isi kolom stok fisik terlebih dahulu.';

        return redirect()
            ->route($auditRoute, ['refresh' => 1])
            ->with($adjusted > 0 ? 'success' : 'error', $message);
    }

    public function stockHistory(Product $product)
    {
        if ($this->isSpeedshopSection()) {
            $warehouseId = auth()->user()->activeWarehouseId();
            $ssid = auth()->user()?->activeSpeedshopId();
            if ($warehouseId !== null && $product->warehouse_id !== $warehouseId) {
                abort(403, 'Anda tidak memiliki akses ke produk ini.');
            }
            if ($ssid && ! in_array((int) ($product->speedshop_scope_id ?? 0), [0, (int) $ssid], true)) {
                abort(403, 'Anda tidak memiliki akses ke produk ini.');
            }
        }

        if ($this->isWarehouseSection()) {
            $warehouseId = auth()->user()->activeWarehouseId();
            if ($warehouseId !== null && $product->warehouse_id !== $warehouseId) {
                abort(403, 'Anda tidak memiliki akses ke produk ini.');
            }
        }

        $movementsQuery = $product->stockMovements();

        // Di Speedshop, histori dipakai untuk konteks stok Speedshop.
        // Pengurangan stok warehouse saat pengiriman PO tidak relevan untuk Speedshop, jadi disembunyikan.
        if ($this->isSpeedshopSection()) {
            // Filter hanya event pengiriman PO (keluar dari warehouse), tapi biarkan histori lain tetap tampil.
            $movementsQuery->where(function ($q) {
                $q->whereNull('keterangan')
                    ->orWhere('keterangan', 'not like', 'Pengiriman ke Speedshop (PO)%');
            });
        }

        $movements = $movementsQuery->latest()->limit(50)->get();

        return response()->json([
            'product' => $product->only(['id', 'kode_produk', 'nama_produk', 'jumlah']),
            'movements' => $movements,
        ]);
    }

    public function exportWarehouseStock(Request $request)
    {
        abort_unless($this->isWarehouseSection(), 403);

        $inventoriPage = $this->warehouseInventoriPageConfig();
        $speedshopIdsForStock = $this->warehouseInventoriStockSpeedshopIds($inventoriPage['catalogKind']);
        $usesOutletStock = $speedshopIdsForStock !== [];

        $query = WarehouseInventoriProductQuery::applyExportOrder(
            $this->buildWarehouseInventoriProductsQuery(
                $request,
                $usesOutletStock ? $speedshopIdsForStock : null,
            ),
        );

        if ($usesOutletStock) {
            WarehouseInventoriProductQuery::applyOutletStockJoin(
                $query,
                $speedshopIdsForStock,
                $inventoriPage['catalogKind'],
            );
        }

        return Excel::download(
            new StokProdukWarehouseExport($query, $usesOutletStock),
            ProductMasterCatalog::stokExportFilenamePrefix($inventoriPage['catalogKind']).'-'.now()->format('Y-m-d-His').'.xlsx'
        );
    }

    public function exportWarehouseStockPdf(Request $request)
    {
        abort_unless($this->isWarehouseSection(), 403);

        $inventoriPage = $this->warehouseInventoriPageConfig();
        $speedshopIdsForStock = $this->warehouseInventoriStockSpeedshopIds($inventoriPage['catalogKind']);
        $usesOutletStock = $speedshopIdsForStock !== [];

        $query = WarehouseInventoriProductQuery::applyExportOrder(
            $this->buildWarehouseInventoriProductsQuery(
                $request,
                $usesOutletStock ? $speedshopIdsForStock : null,
            ),
        );

        if ($usesOutletStock) {
            WarehouseInventoriProductQuery::applyOutletStockJoin(
                $query,
                $speedshopIdsForStock,
                $inventoriPage['catalogKind'],
            );
        }

        $items = $query->get();
        $filename = ProductMasterCatalog::stokExportFilenamePrefix($inventoriPage['catalogKind']).'-'.now()->format('Y-m-d-His').'.pdf';

        return Pdf::loadView('warehouse.products.stok-produk-pdf', [
            'items' => $items,
            'search' => $request->get('search'),
            'printedAt' => now(),
            'inventoriPage' => $inventoriPage,
            'usesOutletStock' => $usesOutletStock,
        ])->setPaper('a4', 'landscape')->stream($filename);
    }

    public function warehouseStockAudit(Request $request)
    {
        abort_unless($this->isWarehouseSection(), 403);

        $inventoriPage = $this->warehouseInventoriPageConfig();
        $warehouseId = auth()->user()?->activeWarehouseId();
        abort_if($warehouseId === null, 403);

        $warehouseSettings = app(WarehouseSettingsService::class);
        $auditPercent = $warehouseSettings->getStokAuditPercent($warehouseId);

        $baseQuery = $this->buildWarehouseProductsQuery(new Request);
        $totalProducts = (int) (clone $baseQuery)->count('products.id');
        $sampleSize = $warehouseSettings->resolveStokAuditSampleSize($totalProducts, $warehouseId);

        $sessionKey = 'warehouse_stok_audit_ids_'.$inventoriPage['catalogKind'];
        $shouldRefresh = $request->boolean('refresh') || ! session()->has($sessionKey);

        if ($totalProducts === 0) {
            session()->forget($sessionKey);

            return view('warehouse.products.audit', [
                'items' => collect(),
                'totalProducts' => 0,
                'sampleSize' => 0,
                'auditPercent' => $auditPercent,
                'canApplyAdjustments' => true,
                'inventoriPage' => $inventoriPage,
            ]);
        }

        if ($shouldRefresh) {
            $sampleIds = (clone $baseQuery)
                ->inRandomOrder()
                ->limit($sampleSize)
                ->pluck('products.id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            session([
                $sessionKey => $sampleIds,
                'warehouse_stok_audit_meta_'.$inventoriPage['catalogKind'] => [
                    'total_products' => $totalProducts,
                    'sample_size' => count($sampleIds),
                    'audit_percent' => $auditPercent,
                ],
            ]);
        } else {
            $sampleIds = array_values(array_map('intval', session($sessionKey, [])));
        }

        if ($sampleIds === []) {
            $sampleIds = (clone $baseQuery)
                ->inRandomOrder()
                ->limit($sampleSize)
                ->pluck('products.id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();
            session([$sessionKey => $sampleIds]);
        }

        $items = Product::with(['category', 'brand'])
            ->where('warehouse_id', $warehouseId)
            ->whereIn('id', $sampleIds)
            ->get()
            ->sortBy(fn (Product $product) => array_search($product->id, $sampleIds, true))
            ->values();

        return view('warehouse.products.audit', [
            'items' => $items,
            'totalProducts' => $totalProducts,
            'sampleSize' => count($sampleIds),
            'auditPercent' => $auditPercent,
            'canApplyAdjustments' => true,
            'inventoriPage' => $inventoriPage,
        ]);
    }

    public function warehouseStockAuditApply(Request $request): RedirectResponse
    {
        abort_unless($this->isWarehouseSection(), 403);

        $inventoriPage = $this->warehouseInventoriPageConfig();
        $auditRoute = $inventoriPage['auditRoute'];
        $sessionKey = 'warehouse_stok_audit_ids_'.$inventoriPage['catalogKind'];

        $warehouseId = auth()->user()?->activeWarehouseId();
        if ($warehouseId === null) {
            return redirect()
                ->route($auditRoute)
                ->with('error', 'Pilih warehouse aktif terlebih dahulu.');
        }

        $sessionIds = array_map('intval', session($sessionKey, []));
        if ($sessionIds === []) {
            return redirect()
                ->route($auditRoute, ['refresh' => 1])
                ->with('error', 'Daftar audit kosong. Silakan acak ulang produk audit.');
        }

        $data = $request->validate([
            'physical' => ['required', 'array'],
            'physical.*' => ['nullable', 'integer', 'min:0'],
        ]);

        $reference = 'warehouse-audit-stok-'.now()->format('YmdHis');
        $adjusted = 0;

        DB::transaction(function () use ($data, $sessionIds, $warehouseId, $reference, $inventoriPage, &$adjusted) {
            foreach ($data['physical'] as $productId => $qty) {
                $productId = (int) $productId;
                if (! in_array($productId, $sessionIds, true)) {
                    continue;
                }
                if ($qty === null || $qty === '') {
                    continue;
                }

                $productQuery = Product::query()
                    ->where('warehouse_id', $warehouseId)
                    ->whereKey($productId);
                $this->applyWarehouseInventoriCatalogScope($productQuery, $inventoriPage['catalogKind']);
                $product = $productQuery->lockForUpdate()->first();

                if (! $product) {
                    continue;
                }

                $before = (int) $product->jumlah;
                $target = (int) $qty;
                if ($before === $target) {
                    continue;
                }

                $this->setWarehouseStockTo($product, $target, $reference, 'Penyesuaian audit stok warehouse');
                $adjusted++;
            }
        });

        session()->forget([$sessionKey, 'warehouse_stok_audit_meta_'.$inventoriPage['catalogKind']]);

        $message = $adjusted > 0
            ? "Penyesuaian audit stok berhasil disimpan untuk {$adjusted} produk."
            : 'Tidak ada penyesuaian stok yang disimpan. Isi kolom stok fisik terlebih dahulu.';

        return redirect()
            ->route($auditRoute, ['refresh' => 1])
            ->with($adjusted > 0 ? 'success' : 'error', $message);
    }

    public function exportWarehouseStockRingkasan(Request $request)
    {
        abort_unless($this->isWarehouseSection(), 403);

        $filters = $request->only(['kode', 'nama', 'category_id', 'brand_id', 'search', 'filter_stok']);

        return Excel::download(
            new StokProdukWarehouseRingkasanExport($filters),
            'ringkasan-stok-warehouse-'.now()->format('Y-m-d-His').'.xlsx'
        );
    }

    public function exportWarehouseStockRingkasanPdf(Request $request)
    {
        abort_unless($this->isWarehouseSection(), 403);

        $warehouseId = auth()->user()->activeWarehouseId();
        $query = Product::with(['category', 'brand'])
            ->where('warehouse_id', $warehouseId)
            ->where('speedshop_scope_id', 0)
            ->visibleInWarehouseMasterKatalog();

        $this->applyProductListFilters($query, $request);

        $items = WarehouseStokRingkasanQuery::withStokKeluarTotal($query, $warehouseId)
            ->orderBy('nama_produk')
            ->get();

        $filename = 'ringkasan-stok-warehouse-'.now()->format('Y-m-d-His').'.pdf';

        return Pdf::loadView('warehouse.products.stok-produk-ringkasan-pdf', [
            'items' => $items,
            'search' => $request->get('search'),
            'printedAt' => now(),
        ])->setPaper('a4', 'landscape')->stream($filename);
    }

    private function buildWarehouseProductsQuery(Request $request): Builder
    {
        $inventoriPage = $this->warehouseInventoriPageConfig();
        $speedshopIds = $this->warehouseInventoriStockSpeedshopIds($inventoriPage['catalogKind']);

        return $this->buildWarehouseInventoriProductsQuery(
            $request,
            $speedshopIds !== [] ? $speedshopIds : null,
        );
    }

    /**
     * Query dasar inventori warehouse — dipakai list, export, PDF, dan audit.
     *
     * @param  list<int>|null  $speedshopIdsForStokFilter
     */
    private function buildWarehouseInventoriProductsQuery(
        Request $request,
        ?array $speedshopIdsForStokFilter = null,
        bool $collapseDuplicateKode = true,
    ): Builder {
        $inventoriPage = $this->warehouseInventoriPageConfig();

        $query = Product::query()
            ->forUser()
            ->with(['category', 'brand']);

        if ($this->detectSection() === 'warehouse') {
            $this->applyWarehouseInventoriCatalogScope(
                $query,
                $inventoriPage['catalogKind'],
                $collapseDuplicateKode,
            );
        }

        $this->applyProductListFilters(
            $query,
            $request,
            $speedshopIdsForStokFilter,
            $inventoriPage['catalogKind'],
        );

        return $query;
    }

    /**
     * @param  list<int>|null  $speedshopIdsForStokFilter
     */
    private function applyProductListFilters(
        Builder $query,
        Request $request,
        ?array $speedshopIdsForStokFilter = null,
        ?string $catalogKind = null,
    ): void {
        if ($request->filled('kode')) {
            $query->where('kode_produk', 'like', '%'.$request->kode.'%');
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

        if (
            $request->filled('search')
            && ! $request->filled('kode')
            && ! $request->filled('nama')
            && ! $request->filled('category_id')
            && ! $request->filled('brand_id')
        ) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('kode_produk', 'like', "%{$search}%")
                    ->orWhere('nama_produk', 'like', "%{$search}%");
            });
        }

        $this->applyProductStokFilter($query, $request->get('filter_stok'), $speedshopIdsForStokFilter, $catalogKind);
    }

    /**
     * @param  list<int>|null  $speedshopIds
     */
    private function applyProductStokFilter(Builder $query, ?string $filterStok, ?array $speedshopIds = null, ?string $catalogKind = null): void
    {
        if ($speedshopIds !== null && $speedshopIds !== []) {
            $stockExpr = $catalogKind
                && ! ProductMasterCatalog::isWarehouseSharedKind($catalogKind)
                && count($speedshopIds) > 1
                ? WarehouseInventoriProductQuery::combinedOutletStockSql($speedshopIds, $catalogKind)
                : '(SELECT COALESCE(SUM(ss.jumlah), 0) FROM speedshop_stock ss WHERE ss.product_id = products.id AND ss.speedshop_id IN ('.implode(',', array_map('intval', $speedshopIds)).'))';

            match ($filterStok) {
                'habis' => $query->whereRaw("{$stockExpr} <= 0"),
                'bawah_10' => $query->whereRaw("{$stockExpr} < 10"),
                default => null,
            };

            return;
        }

        match ($filterStok) {
            'habis' => $query->where('jumlah', '<=', 0),
            'bawah_10' => $query->where('jumlah', '<', 10),
            default => null,
        };
    }

    /**
     * @return array{
     *     filterSpeedshopIds: list<int>,
     *     singleSpeedshopId: ?int,
     *     isAllScope: bool,
     *     warehouseId: ?int,
     *     catalogKind: string
     * }
     */
    private function stokProdukSpeedshopContext(): array
    {
        $user = auth()->user();
        $filterIds = $user?->reportSpeedshopFilterIds() ?? [];
        $catalogKind = ProductMasterCatalog::kindFromStokRouteName(request()->route()?->getName());

        return [
            'filterSpeedshopIds' => $filterIds,
            'singleSpeedshopId' => ($user?->isSpeedshopAllScopeSelected() ?? false) || $filterIds === []
                ? null
                : (int) $filterIds[0],
            'isAllScope' => $user?->isSpeedshopAllScopeSelected() ?? false,
            'warehouseId' => $user?->activeWarehouseId(),
            'catalogKind' => $catalogKind,
        ];
    }

    /**
     * @return array{
     *     catalogKind: string,
     *     title: string,
     *     indexRoute: string,
     *     auditRoute: string,
     *     auditApplyRoute: string,
     *     exportRoute: string,
     *     pdfRoute: string
     * }
     */
    private function stokProdukSpeedshopPageConfig(): array
    {
        $catalogKind = ProductMasterCatalog::kindFromStokRouteName(request()->route()?->getName());
        $routeBase = ProductMasterCatalog::stokRouteBase($catalogKind);

        return [
            'catalogKind' => $catalogKind,
            'title' => ProductMasterCatalog::label($catalogKind),
            'indexRoute' => $routeBase,
            'auditRoute' => $routeBase.'.audit',
            'auditApplyRoute' => $routeBase.'.audit.apply',
            'exportRoute' => $routeBase.'.export',
            'pdfRoute' => $routeBase.'.pdf',
            'importRoute' => $routeBase.'.import',
            'templateRoute' => $routeBase.'.template',
            'updateStockRoute' => $routeBase.'.update-stock',
        ];
    }

    public function updateSpeedshopInventoriStock(Request $request, Product $product)
    {
        abort_unless(str_starts_with(request()->route()?->getName() ?? '', 'speedshop.'), 403);
        abort_unless(auth()->user()?->canManageProductMasterCatalog(), 403);

        $user = auth()->user();
        $speedshopId = $user?->isSpeedshopAllScopeSelected()
            ? null
            : ($user?->speedshopScopeId() ?? $user?->activeSpeedshopId());
        if ($speedshopId === null) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => 'Pilih cabang speedshop di navbar terlebih dahulu.'], 422)
                : redirect()->back()->with('error', 'Pilih cabang speedshop di navbar terlebih dahulu.');
        }

        $stokPage = $this->stokProdukSpeedshopPageConfig();
        $ctx = $this->stokProdukSpeedshopContext();
        $scoped = Product::query()->whereKey($product->id);
        $this->applyStokProdukSpeedshopScope($scoped, $ctx);
        abort_unless($scoped->exists(), 404);

        $validated = $request->validate([
            'jumlah' => ['required', 'integer', 'min:0'],
        ]);

        $target = (int) $validated['jumlah'];
        $reference = 'speedshop-inventori-edit-stok-'.now()->format('YmdHis');

        DB::transaction(function () use ($product, $target, $reference, $speedshopId) {
            $locked = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();
            $this->setSpeedshopStockTo(
                $locked,
                $target,
                $reference,
                'Penyesuaian stok manual Inventori Produk speedshop',
                $speedshopId,
            );
        });

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'jumlah' => $target,
                'message' => 'Stok produk berhasil diperbarui.',
            ]);
        }

        return redirect()
            ->route($stokPage['indexRoute'])
            ->with('success', 'Stok produk berhasil diperbarui.');
    }

    /**
     * @return array{
     *     catalogKind: string,
     *     title: string,
     *     indexRoute: string,
     *     auditRoute: string,
     *     exportRoute: string,
     *     pdfRoute: string,
     *     importRoute: string,
     *     templateRoute: string
     * }
     */
    private function warehouseInventoriPageConfig(): array
    {
        $routeName = request()->route()?->getName() ?? '';
        $isInventoriCatalog = str_starts_with($routeName, 'warehouse.inventori-');
        $catalogKind = $isInventoriCatalog
            ? ProductMasterCatalog::kindFromStokRouteName($routeName)
            : ProductMasterCatalog::MO;
        $routeBase = $isInventoriCatalog
            ? ProductMasterCatalog::warehouseInventoriRouteBase($catalogKind)
            : 'warehouse.products';

        return [
            'catalogKind' => $catalogKind,
            'title' => ProductMasterCatalog::label($catalogKind),
            'indexRoute' => $isInventoriCatalog ? $routeBase : 'warehouse.products.index',
            'auditRoute' => $isInventoriCatalog ? $routeBase.'.audit' : 'warehouse.products.audit',
            'auditApplyRoute' => $isInventoriCatalog ? $routeBase.'.audit.apply' : 'warehouse.products.audit.apply',
            'exportRoute' => $isInventoriCatalog ? $routeBase.'.export' : 'warehouse.products.export-excel',
            'pdfRoute' => $isInventoriCatalog ? $routeBase.'.pdf' : 'warehouse.products.export-pdf',
            'importRoute' => $isInventoriCatalog ? $routeBase.'.import' : 'warehouse.products.import-stok',
            'templateRoute' => $isInventoriCatalog ? $routeBase.'.template' : 'warehouse.products.template-stok',
            'updateStockRoute' => $isInventoriCatalog ? $routeBase.'.update-stock' : null,
        ];
    }

    public function updateWarehouseInventoriStock(Request $request, Product $product)
    {
        abort_unless($this->isWarehouseSection(), 403);
        abort_unless(auth()->user()?->canManageProductMasterCatalog(), 403);

        $user = auth()->user();
        if ($user?->isWarehouseStaffAccountingReseller()) {
            abort(403, 'Akun ini hanya dapat melihat inventori.');
        }

        $inventoriPage = $this->warehouseInventoriPageConfig();
        $warehouseId = $user?->activeWarehouseId();
        if ($warehouseId === null || (int) $product->warehouse_id !== (int) $warehouseId) {
            abort(403, 'Anda tidak memiliki akses ke produk ini.');
        }

        $scoped = Product::query()->whereKey($product->id);
        $this->applyWarehouseInventoriCatalogScope($scoped, $inventoriPage['catalogKind']);
        abort_unless($scoped->exists(), 404);

        $validated = $request->validate([
            'jumlah' => ['required', 'integer', 'min:0'],
        ]);

        $target = (int) $validated['jumlah'];
        $reference = 'warehouse-inventori-edit-stok-'.now()->format('YmdHis');
        $scopeId = $user?->speedshopScopeId();
        $allowedSpeedshopIds = $user?->warehouseLinkedSpeedshopIds()
            ?: ($user?->activeSpeedshopIds() ?? []);
        $editSpeedshopId = $scopeId;

        if ($editSpeedshopId !== null) {
            if (! in_array($editSpeedshopId, $allowedSpeedshopIds, true)) {
                abort(403, 'Cabang speedshop tidak valid.');
            }

            DB::transaction(function () use ($product, $target, $reference, $editSpeedshopId) {
                $locked = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();
                $this->setSpeedshopStockTo(
                    $locked,
                    $target,
                    $reference,
                    'Penyesuaian stok manual Inventori Produk cabang speedshop',
                    $editSpeedshopId,
                );
            });
        } else {
            if (
                ! ProductMasterCatalog::isWarehouseSharedKind($inventoriPage['catalogKind'])
                || $this->warehouseInventoriIsCombinedSharedScope($inventoriPage['catalogKind'])
            ) {
                return $request->expectsJson()
                    ? response()->json(['success' => false, 'message' => 'Pilih cabang speedshop di navbar terlebih dahulu.'], 422)
                    : redirect()
                        ->route($inventoriPage['indexRoute'])
                        ->with('error', 'Pilih cabang speedshop di navbar terlebih dahulu.');
            }

            DB::transaction(function () use ($product, $target, $reference) {
                $locked = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();
                $this->setWarehouseStockTo($locked, $target, $reference, 'Penyesuaian stok manual Inventori Produk warehouse');
            });
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'jumlah' => $target,
                'message' => 'Stok produk berhasil diperbarui.',
            ]);
        }

        return redirect()
            ->route($inventoriPage['indexRoute'])
            ->with('success', 'Stok produk berhasil diperbarui.');
    }

    private function applyWarehouseInventoriCatalogScope(Builder $query, string $catalogKind, bool $collapseDuplicateKode = true): void
    {
        WarehouseInventoriProductQuery::applyCatalogScope(
            $query,
            $catalogKind,
            $this->warehouseInventoriCatalogOutletId($catalogKind),
            $this->warehouseInventoriStockSpeedshopIds($catalogKind),
            $collapseDuplicateKode,
        );
    }

    /**
     * Cabang speedshop yang dipakai untuk kolom stok inventori warehouse (selaras halaman web).
     *
     * @return list<int>
     */
    private function warehouseInventoriStockSpeedshopIds(string $catalogKind): array
    {
        $user = auth()->user();
        $inventoriScopeId = $user?->speedshopScopeId();

        if (ProductMasterCatalog::isWarehouseSharedKind($catalogKind)) {
            if ($inventoriScopeId) {
                return [(int) $inventoriScopeId];
            }

            // Merchandise / consumable: filter Semua Speedshop = jumlah stok semua cabang.
            // Inventori MO tetap memakai stok warehouse jika cabang tidak dipilih.
            if ($catalogKind !== ProductMasterCatalog::MO && $user?->isSpeedshopAllScopeSelected()) {
                return array_values(array_unique(array_filter(
                    array_map('intval', $user->reportSpeedshopFilterIds() ?? []),
                    fn (int $id) => $id > 0,
                )));
            }

            return [];
        }

        if ($inventoriScopeId) {
            return [(int) $inventoriScopeId];
        }

        if ($user?->isSpeedshopAllScopeSelected()) {
            return array_values(array_unique(array_filter(
                array_map('intval', $user->reportSpeedshopFilterIds() ?? []),
                fn (int $id) => $id > 0,
            )));
        }

        $fallback = $user?->activeSpeedshopId();

        return $fallback ? [(int) $fallback] : [];
    }

    private function warehouseInventoriIsCombinedOutletScope(string $catalogKind): bool
    {
        return ! ProductMasterCatalog::isWarehouseSharedKind($catalogKind)
            && (auth()->user()?->isSpeedshopAllScopeSelected() ?? false)
            && count($this->warehouseInventoriStockSpeedshopIds($catalogKind)) > 1;
    }

    /**
     * Merchandise / consumable: satu SKU warehouse, stok cabang di speedshop_stock.
     * Filter Semua Speedshop menampilkan jumlah semua cabang (bukan stok warehouse).
     */
    private function warehouseInventoriIsCombinedSharedScope(string $catalogKind): bool
    {
        return ProductMasterCatalog::isWarehouseSharedKind($catalogKind)
            && $catalogKind !== ProductMasterCatalog::MO
            && (auth()->user()?->isSpeedshopAllScopeSelected() ?? false)
            && count($this->warehouseInventoriStockSpeedshopIds($catalogKind)) > 1;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Product>  $products
     * @param  list<int>  $speedshopIds
     * @return Collection<int|string, SpeedshopStock>
     */
    private function combinedOutletStockMapForProducts($products, array $speedshopIds, string $catalogKind): Collection
    {
        if ($products->isEmpty() || $speedshopIds === []) {
            return collect();
        }

        $kodes = $products
            ->map(fn (Product $p) => mb_strtoupper(trim((string) $p->kode_produk)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($kodes === []) {
            return collect();
        }

        $placeholders = implode(',', array_fill(0, count($kodes), '?'));
        $peers = Product::query()
            ->forUser()
            ->forSpeedshopStokCatalogAny($speedshopIds, $catalogKind)
            ->whereRaw('UPPER(TRIM(kode_produk)) IN ('.$placeholders.')', $kodes)
            ->get(['id', 'kode_produk']);

        $qtyByKode = [];
        if ($peers->isNotEmpty()) {
            $stockByProduct = SpeedshopStock::query()
                ->whereIn('product_id', $peers->pluck('id')->all())
                ->whereIn('speedshop_id', $speedshopIds)
                ->selectRaw('product_id, SUM(jumlah) as qty')
                ->groupBy('product_id')
                ->pluck('qty', 'product_id');

            foreach ($peers as $peer) {
                $kode = mb_strtoupper(trim((string) $peer->kode_produk));
                $qtyByKode[$kode] = (int) ($qtyByKode[$kode] ?? 0) + (int) ($stockByProduct[(int) $peer->id] ?? 0);
            }
        }

        $map = collect();
        foreach ($products as $product) {
            $kode = mb_strtoupper(trim((string) $product->kode_produk));
            $row = new SpeedshopStock;
            $row->product_id = (int) $product->id;
            $row->jumlah = (int) ($qtyByKode[$kode] ?? 0);
            $map->put($product->id, $row);
        }

        return $map;
    }

    /**
     * Outlet untuk scope inventori warehouse (selaras master head-catalog / activeSpeedshopId).
     */
    private function warehouseInventoriCatalogOutletId(string $catalogKind): ?int
    {
        if (ProductMasterCatalog::isWarehouseSharedKind($catalogKind)) {
            return null;
        }

        $user = auth()->user();
        $scopeId = $user?->speedshopScopeId();

        return $scopeId ?? $user?->activeSpeedshopId();
    }

    public function importSpeedshopStock(Request $request): RedirectResponse
    {
        abort_unless(str_starts_with(request()->route()->getName() ?? '', 'speedshop.'), 403);
        abort_unless(auth()->user()?->canManageProductMasterCatalog(), 403);

        $stokPage = $this->stokProdukSpeedshopPageConfig();
        if (auth()->user()?->isSpeedshopAllScopeSelected()) {
            return redirect()
                ->route($stokPage['indexRoute'])
                ->with('error', 'Pilih cabang speedshop di navbar terlebih dahulu.');
        }

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
        ]);

        try {
            $import = new StokProdukSpeedshopImport($stokPage['catalogKind']);
            Excel::import($import, $request->file('file'));

            return redirect()
                ->route($stokPage['indexRoute'])
                ->with('success', "Import stok berhasil: {$import->rowsApplied} produk diperbarui".($import->rowsSkipped > 0 ? ", {$import->rowsSkipped} baris dilewati" : '').'.');
        } catch (\Throwable $e) {
            return redirect()
                ->route($stokPage['indexRoute'])
                ->with('error', 'Import stok gagal: '.$e->getMessage());
        }
    }

    public function templateSpeedshopStock()
    {
        abort_unless(str_starts_with(request()->route()->getName() ?? '', 'speedshop.'), 403);
        abort_unless(auth()->user()?->canManageProductMasterCatalog(), 403);

        $stokPage = $this->stokProdukSpeedshopPageConfig();

        return Excel::download(
            new StokProdukSpeedshopTemplateExport,
            'template-stok-'.ProductMasterCatalog::stokExportFilenamePrefix($stokPage['catalogKind']).'.xlsx'
        );
    }

    public function importWarehouseStock(Request $request): RedirectResponse
    {
        abort_unless($this->isWarehouseSection(), 403);
        abort_unless(auth()->user()?->canManageProductMasterCatalog(), 403);

        $inventoriPage = $this->warehouseInventoriPageConfig();
        $catalogKind = $inventoriPage['catalogKind'];
        $allowsWarehouse = ProductMasterCatalog::isWarehouseSharedKind($catalogKind);
        $allowedSpeedshopIds = auth()->user()?->warehouseLinkedSpeedshopIds()
            ?: (auth()->user()?->activeSpeedshopIds() ?? []);
        $navbarScopeId = auth()->user()?->speedshopScopeId();

        // Filter navbar cabang mengunci tujuan import ke outlet terpilih.
        if ($navbarScopeId !== null) {
            abort_unless(in_array($navbarScopeId, $allowedSpeedshopIds, true), 403, 'Cabang speedshop tidak valid.');
            $request->merge([
                'import_target' => 'speedshop',
                'speedshop_id' => $navbarScopeId,
            ]);
        }

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
            'import_target' => ['required', Rule::in(($allowsWarehouse && $navbarScopeId === null) ? ['warehouse', 'speedshop'] : ['speedshop'])],
            'speedshop_id' => [
                Rule::requiredIf(fn () => $request->input('import_target') === 'speedshop'),
                'nullable',
                'integer',
                Rule::in($allowedSpeedshopIds),
            ],
        ]);

        $target = (string) $validated['import_target'];

        try {
            if ($target === 'warehouse') {
                $import = new StokProdukWarehouseImport($catalogKind);
                Excel::import($import, $request->file('file'));
            } else {
                $import = new StokProdukSpeedshopImport($catalogKind, (int) $validated['speedshop_id']);
                Excel::import($import, $request->file('file'));
            }

            $destination = $target === 'warehouse'
                ? 'warehouse'
                : 'cabang speedshop';

            return redirect()
                ->route($inventoriPage['indexRoute'])
                ->with('success', "Import stok {$destination} berhasil: {$import->rowsApplied} produk diperbarui".($import->rowsSkipped > 0 ? ", {$import->rowsSkipped} baris dilewati" : '').'.');
        } catch (\Throwable $e) {
            return redirect()
                ->route($inventoriPage['indexRoute'])
                ->with('error', 'Import stok gagal: '.$e->getMessage());
        }
    }

    public function templateWarehouseStock()
    {
        abort_unless($this->isWarehouseSection(), 403);
        abort_unless(auth()->user()?->canManageProductMasterCatalog(), 403);

        $inventoriPage = $this->warehouseInventoriPageConfig();

        return Excel::download(
            new StokProdukWarehouseTemplateExport,
            'template-stok-'.ProductMasterCatalog::stokExportFilenamePrefix($inventoriPage['catalogKind']).'.xlsx'
        );
    }

    private function stokProdukAuditSessionKey(string $catalogKind): string
    {
        return 'speedshop_stok_audit_ids_'.$catalogKind;
    }

    private function buildStokProdukSpeedshopQuery(Request $request, array $ctx): Builder
    {
        $query = Product::with(['category', 'brand'])->forUser();
        $this->applyStokProdukSpeedshopScope($query, $ctx);
        $this->applyStokProdukSpeedshopTableFilters($query, $request);

        return $query;
    }

    private function applyStokProdukSpeedshopScope(Builder $query, array $ctx, bool $collapseDuplicateKode = true): void
    {
        $catalogKind = $ctx['catalogKind'] ?? ProductMasterCatalog::MO;

        if ($ctx['singleSpeedshopId']) {
            $query->forSpeedshopStokCatalog((int) $ctx['singleSpeedshopId'], $catalogKind);

            return;
        }

        if ($ctx['filterSpeedshopIds'] !== []) {
            $ids = $ctx['filterSpeedshopIds'];
            if ($this->stokProdukSpeedshopIsCombinedOutlet($ctx)) {
                $query->forSpeedshopStokCatalogAny($ids, $catalogKind);
                if ($collapseDuplicateKode) {
                    $query->collapseToCanonicalKode();
                }

                return;
            }

            $query->where(function (Builder $outer) use ($ids, $catalogKind) {
                foreach ($ids as $speedshopId) {
                    $sid = (int) $speedshopId;
                    $outer->orWhere(function (Builder $q) use ($sid, $catalogKind) {
                        $q->forSpeedshopStokCatalog($sid, $catalogKind);
                    });
                }
            });
        }
    }

    private function applyStokProdukSpeedshopTableFilters(Builder $query, Request $request): void
    {
        if ($request->filled('kode')) {
            $query->where('kode_produk', 'like', '%'.$request->kode.'%');
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

        if (
            $request->filled('search')
            && ! $request->filled('kode')
            && ! $request->filled('nama')
            && ! $request->filled('category_id')
            && ! $request->filled('brand_id')
        ) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('kode_produk', 'like', "%{$search}%")
                    ->orWhere('nama_produk', 'like', "%{$search}%");
            });
        }
    }

    private function applyStokProdukSpeedshopSort(Builder $query, Request $request, array $ctx): void
    {
        if ($request->boolean('terlaris') && $ctx['filterSpeedshopIds'] !== []) {
            $salesSub = $this->speedshopProductSalesAggregateSubquery($ctx['filterSpeedshopIds'], $ctx['warehouseId']);
            $query
                ->joinSub($salesSub, 'sales', 'products.id', '=', 'sales.product_id')
                ->select('products.*')
                ->orderByDesc('sales.total_qty')
                ->orderBy('products.nama_produk');

            return;
        }

        $sortStock = strtolower((string) $request->get('sort_stock', ''));
        if ($ctx['filterSpeedshopIds'] !== [] && in_array($sortStock, ['asc', 'desc'], true)) {
            if ($this->stokProdukSpeedshopIsCombinedOutlet($ctx)) {
                $stockExpr = WarehouseInventoriProductQuery::combinedOutletStockSql(
                    $ctx['filterSpeedshopIds'],
                    $ctx['catalogKind'],
                );
                $query
                    ->select('products.*')
                    ->selectRaw($stockExpr.' as stok_sort')
                    ->orderBy('stok_sort', $sortStock)
                    ->orderBy('products.nama_produk');

                return;
            }

            $stockSub = $this->speedshopStockAggregateSubquery($ctx['filterSpeedshopIds']);
            $query
                ->leftJoinSub($stockSub, 'ss', 'products.id', '=', 'ss.product_id')
                ->select('products.*', DB::raw('COALESCE(ss.stok_sort, 0) as stok_sort'))
                ->orderBy('stok_sort', $sortStock)
                ->orderBy('products.nama_produk');

            return;
        }

        if (WarehouseInventoriProductQuery::usesWarehouseInventoriStockUi($ctx['catalogKind'] ?? ProductMasterCatalog::MO)) {
            WarehouseInventoriProductQuery::applyStableListOrder($query);

            return;
        }

        $query->orderBy('products.nama_produk');
    }

    /**
     * @param  list<int>  $speedshopIds
     */
    private function speedshopProductSalesAggregateSubquery(array $speedshopIds, ?int $warehouseId): \Illuminate\Database\Query\Builder
    {
        $orderIdsQuery = ServiceOrder::query()
            ->where('status', 'selesai')
            ->when(
                Schema::hasColumn('service_orders', 'speedshop_id') && $speedshopIds !== [],
                function ($q) use ($speedshopIds) {
                    if (count($speedshopIds) === 1) {
                        $q->where('speedshop_id', (int) $speedshopIds[0]);
                    } else {
                        $q->whereIn('speedshop_id', $speedshopIds);
                    }
                },
                fn ($q) => $q->when($warehouseId, fn ($q2) => $q2->where('warehouse_id', $warehouseId)),
            )
            ->select('id');

        return DB::table('service_order_products')
            ->whereIn('service_order_id', $orderIdsQuery)
            ->selectRaw('product_id, SUM(qty) as total_qty')
            ->groupBy('product_id');
    }

    /**
     * @param  list<int>  $speedshopIds
     */
    private function speedshopStockAggregateSubquery(array $speedshopIds): \Illuminate\Database\Query\Builder
    {
        return DB::table('speedshop_stock')
            ->selectRaw('product_id, SUM(jumlah) as stok_sort')
            ->whereIn('speedshop_id', $speedshopIds)
            ->groupBy('product_id');
    }

    /**
     * @param  array{filterSpeedshopIds: list<int>, isAllScope: bool, catalogKind: string}  $ctx
     */
    private function stokProdukSpeedshopIsCombinedOutlet(array $ctx): bool
    {
        $kind = $ctx['catalogKind'] ?? ProductMasterCatalog::MO;

        return ! ProductMasterCatalog::isWarehouseSharedKind($kind)
            && ($ctx['isAllScope'] ?? false)
            && count($ctx['filterSpeedshopIds'] ?? []) > 1;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Product>  $products
     * @param  array{filterSpeedshopIds: list<int>, isAllScope: bool, catalogKind: string}  $ctx
     * @return Collection<int|string, SpeedshopStock>
     */
    private function stokProdukSpeedshopStockMap($products, array $ctx): Collection
    {
        if ($this->stokProdukSpeedshopIsCombinedOutlet($ctx)) {
            return $this->combinedOutletStockMapForProducts(
                $products,
                $ctx['filterSpeedshopIds'],
                $ctx['catalogKind'],
            );
        }

        return $this->speedshopStockMapForProducts($products->pluck('id'), $ctx);
    }

    /**
     * @param  Collection<int, int|string>  $productIds
     */
    private function speedshopStockMapForProducts(Collection $productIds, array $ctx): Collection
    {
        $ids = $ctx['filterSpeedshopIds'];
        if ($ids === [] || $productIds->isEmpty()) {
            return collect();
        }

        return SpeedshopStock::query()
            ->whereIn('speedshop_id', $ids)
            ->whereIn('product_id', $productIds)
            ->selectRaw('product_id, SUM(jumlah) as jumlah')
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');
    }

    /**
     * Total stok & modal: produk yang sama dengan daftar Inventori
     * (ORI aktif master outlet — bukan soft-delete, bukan arsip, bukan part luar).
     *
     * @return array{totalStok: int, totalModal: float}
     */
    private function speedshopStockScopeTotals(array $ctx): array
    {
        $ids = $ctx['filterSpeedshopIds'];

        if ($ids === []) {
            return ['totalStok' => 0, 'totalModal' => 0.0];
        }

        $productIdsQuery = Product::query()->select('products.id');
        $this->applyStokProdukSpeedshopScope($productIdsQuery, $ctx, false);

        $base = SpeedshopStock::query()
            ->whereIn('speedshop_stock.speedshop_id', $ids)
            ->whereIn('speedshop_stock.product_id', $productIdsQuery);

        $totalStok = (int) (clone $base)->sum('speedshop_stock.jumlah');

        $totalModal = (float) SpeedshopStock::query()
            ->join('products', 'products.id', '=', 'speedshop_stock.product_id')
            ->whereIn('speedshop_stock.speedshop_id', $ids)
            ->whereIn('speedshop_stock.product_id', $productIdsQuery)
            ->sum(DB::raw('speedshop_stock.jumlah * COALESCE(products.hpp, 0)'));

        return ['totalStok' => $totalStok, 'totalModal' => $totalModal];
    }
}
