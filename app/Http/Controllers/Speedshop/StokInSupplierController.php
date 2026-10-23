<?php

namespace App\Http\Controllers\Speedshop;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Support\ProductMasterCatalog;
use App\Support\SpeedshopStockInPricing;
use App\Services\WarehouseSupplierTagihanService;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Speedshop;
use App\Models\SpeedshopStock;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Models\WarehouseTransaksi;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StokInSupplierController extends Controller
{
    private const TIPE = 'stok_in_supplier';

    private const BRAND_PART_LUAR = 'Part Luar';

    private function warehouseId(): ?int
    {
        return auth()->user()->activeWarehouseId();
    }

    private function partLuarBrandId(): int
    {
        return (int) Brand::query()->firstOrCreate(
            ['nama' => self::BRAND_PART_LUAR],
        )->id;
    }

    /**
     * Sinkronkan satu baris input manual ke master produk (per gudang) dan kembalikan model Product.
     */
    private function syncProductFromManualLine(array $line, int $warehouseId, int $speedshopId): Product
    {
        $kodeProduk = mb_strtoupper(trim((string) ($line['kode_produk'] ?? '')));
        $namaProduk = trim((string) ($line['nama_produk'] ?? ''));
        $categoryId = (int) ($line['category_id'] ?? 0);
        $hpp = (float) ($line['hpp'] ?? 0);
        $hargaJualSpeedshop = (float) ($line['harga_jual'] ?? 0);

        $category = Category::query()
            ->forSpeedshopOutlet((int) $speedshopId)
            ->whereKey($categoryId)
            ->firstOrFail();
        $brandId = (int) ($line['brand_id'] ?? 0);
        Brand::query()->forSpeedshop()->whereKey($brandId)->firstOrFail();

        $product = Product::findByKodeForWarehouseAndSpeedshopOutlet($warehouseId, $kodeProduk, $speedshopId);

        $payload = [
            'nama_produk' => $namaProduk,
            'category_id' => $category->id,
            'brand_id' => $brandId,
            'hpp' => $hpp,
            'harga_pembelian' => $hpp,
            'harga_jual_speedshop' => $hargaJualSpeedshop,
            'harga_jual' => $hargaJualSpeedshop,
        ];

        if ($product) {
            $product->update([
                'nama_produk' => $namaProduk,
                'category_id' => $category->id,
                'brand_id' => $brandId,
            ]);
            SpeedshopStockInPricing::applyHigherPricesOnly($product, $hpp, $hargaJualSpeedshop);

            return $product->fresh();
        }

        return Product::create(array_merge($payload, [
            'kode_produk' => $kodeProduk,
            'warehouse_id' => $warehouseId,
            'speedshop_scope_id' => (int) $speedshopId,
            'jumlah' => 0,
            'harga_jual_reseler' => 0,
            'harga_eceran_terendah' => 0,
        ]));
    }

    public function index(Request $request)
    {
        return view(
            'speedshop.stock-in.supplier.index',
            $this->supplierIndexPayload($request, false),
        );
    }

    /** Halaman verifikasi Accounting (filter status via dropdown, sama pola setoran). */
    public function indexVerifikasiPending(Request $request)
    {
        return view(
            'speedshop.stock-in.supplier.index',
            $this->supplierIndexPayload($request, true),
        );
    }

    private function resolveListPeriod(Request $request, string $defaultMode = 'harian'): array
    {
        if (! in_array($defaultMode, ['harian', 'bulanan', 'tahunan', 'range'], true)) {
            $defaultMode = 'harian';
        }

        $mode = in_array($request->input('mode'), ['harian', 'bulanan', 'tahunan', 'range'], true)
            ? $request->input('mode')
            : $defaultMode;

        if ($request->filled('dari') && $request->filled('sampai') && ! $request->filled('mode')) {
            $mode = 'range';
        }

        $selectedDate = $request->input('tanggal') ?: now()->format('Y-m-d');
        $selectedMonth = $request->input('bulan') ?: now()->format('Y-m');
        $selectedYear = (int) ($request->input('tahun') ?: now()->year);
        $selectedDari = $request->input('dari') ?: now()->startOfMonth()->format('Y-m-d');
        $selectedSampai = $request->input('sampai') ?: now()->format('Y-m-d');

        if ($mode === 'range') {
            try {
                $dari = Carbon::parse($selectedDari)->startOfDay();
                $sampai = Carbon::parse($selectedSampai)->endOfDay();
            } catch (\Throwable $e) {
                $selectedDari = now()->startOfMonth()->format('Y-m-d');
                $selectedSampai = now()->format('Y-m-d');
                $dari = now()->startOfMonth()->startOfDay();
                $sampai = now()->endOfDay();
            }

            if ($dari->gt($sampai)) {
                [$dari, $sampai] = [$sampai->copy()->startOfDay(), $dari->copy()->endOfDay()];
                $selectedDari = $dari->format('Y-m-d');
                $selectedSampai = $sampai->format('Y-m-d');
            }

            return [
                'dari' => $dari,
                'sampai' => $sampai,
                'mode' => 'range',
                'selectedDate' => $selectedDari,
                'selectedMonth' => $dari->format('Y-m'),
                'selectedYear' => (int) $dari->format('Y'),
                'selectedDari' => $selectedDari,
                'selectedSampai' => $selectedSampai,
            ];
        }

        if ($mode === 'harian') {
            try {
                $dari = Carbon::parse($selectedDate)->startOfDay();
                $sampai = Carbon::parse($selectedDate)->endOfDay();
            } catch (\Throwable $e) {
                $selectedDate = now()->format('Y-m-d');
                $dari = now()->startOfDay();
                $sampai = now()->endOfDay();
            }
        } elseif ($mode === 'tahunan') {
            $selectedYear = max(2000, min(2100, $selectedYear));
            $dari = now()->setYear($selectedYear)->startOfYear();
            $sampai = now()->setYear($selectedYear)->endOfYear();
        } else {
            try {
                $monthBase = Carbon::parse($selectedMonth.'-01');
            } catch (\Throwable $e) {
                $selectedMonth = now()->format('Y-m');
                $monthBase = now()->startOfMonth();
            }
            $dari = $monthBase->copy()->startOfMonth();
            $sampai = $monthBase->copy()->endOfMonth();
        }

        return [
            'dari' => $dari,
            'sampai' => $sampai,
            'mode' => $mode,
            'selectedDate' => $selectedDate,
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
            'selectedDari' => $dari->format('Y-m-d'),
            'selectedSampai' => $sampai->format('Y-m-d'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function supplierIndexPayload(Request $request, bool $verifikasiStokInSupplierAccounting): array
    {
        $period = $this->resolveListPeriod(
            $request,
            'bulanan',
        );
        $mode = $period['mode'];
        $selectedDate = $period['selectedDate'];
        $selectedMonth = $period['selectedMonth'];
        $selectedYear = $period['selectedYear'];
        $selectedDari = $period['selectedDari'];
        $selectedSampai = $period['selectedSampai'];
        $dari = $period['dari'];
        $sampai = $period['sampai'];
        $periodStart = $dari->toDateString();
        $periodEnd = $sampai->toDateString();

        $periodVars = compact(
            'mode',
            'selectedDate',
            'selectedMonth',
            'selectedYear',
            'selectedDari',
            'selectedSampai',
            'dari',
            'sampai',
        );

        $warehouseId = $this->warehouseId();
        $speedshopId = auth()->user()->activeSpeedshopId();

        if (! $warehouseId || ! $speedshopId) {
            $transaksis = WarehouseTransaksi::query()->whereRaw('1=0')->paginate(20);
            $totalAll = $totalApproved = $totalRejected = $totalPending = 0;

            return compact('transaksis', 'totalAll', 'totalApproved', 'totalRejected', 'totalPending') + $periodVars + [
                'verifikasiStokInSupplierAccounting' => $verifikasiStokInSupplierAccounting,
            ];
        }

        $query = WarehouseTransaksi::with(['user', 'approver', 'items.product', 'items.supplier'])
            ->where('warehouse_id', $warehouseId)
            ->where('tipe', self::TIPE)
            ->whereHas('user', fn ($q) => $q->where('section', 'speedshop')->where('speedshop_id', (int) $speedshopId));

        if ($periodStart && $periodEnd) {
            $query->whereDate('created_at', '>=', $periodStart)
                ->whereDate('created_at', '<=', $periodEnd);
        }

        if ($request->filled('kode')) {
            $query->where('kode', 'like', '%'.$request->kode.'%');
        }
        if ($request->filled('diajukan_oleh')) {
            $term = $request->diajukan_oleh;
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', '%'.$term.'%'));
        }

        if (
            $request->filled('search')
            && ! $request->filled('kode')
            && ! $request->filled('diajukan_oleh')
            && ! $request->filled('status')
        ) {
            $query->where('kode', 'like', "%{$request->search}%");
        }

        $summaryQuery = clone $query;
        $totalAll = (int) (clone $summaryQuery)->count();
        $totalPending = (int) (clone $summaryQuery)->where('status', 'pending')->count();
        $totalApproved = (int) (clone $summaryQuery)->where('status', 'approved')->count();
        $totalRejected = (int) (clone $summaryQuery)->where('status', 'rejected')->count();

        if ($request->filled('status') && in_array($request->status, ['pending', 'approved', 'rejected'], true)) {
            $query->where('status', $request->status);
        }

        $transaksis = $query
            ->when(
                $verifikasiStokInSupplierAccounting,
                fn ($q) => $q->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'approved' THEN 1 ELSE 2 END"),
            )
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return compact('transaksis', 'totalAll', 'totalApproved', 'totalRejected', 'totalPending') + $periodVars + [
            'verifikasiStokInSupplierAccounting' => $verifikasiStokInSupplierAccounting,
        ];
    }

    public function create()
    {
        $warehouseId = $this->warehouseId();
        $ssid = auth()->user()?->activeSpeedshopId();
        $categories = Category::categoriesForSpeedshopProductUi(
            $warehouseId,
            $ssid ? (int) $ssid : null,
        );
        $brands = Brand::query()->forSpeedshop()->orderBy('nama')->get(['id', 'nama']);
        $defaultBrandId = $brands->first()?->id;
        $suppliers = $ssid
            ? Supplier::query()
                ->forSpeedshop((int) $ssid)
                ->orderBy('nama')
                ->get(['id', 'kode_supplier', 'nama', 'no_hp'])
            : collect();

        $suppliersForJs = $suppliers->map(fn ($s) => [
            'id' => (string) $s->id,
            'kode_supplier' => (string) ($s->kode_supplier ?? ''),
            'nama' => (string) ($s->nama ?? ''),
            'no_hp' => (string) ($s->no_hp ?? ''),
        ])->values()->all();

        $productsForJs = $this->productsForSupplierJs($warehouseId, $ssid ? (int) $ssid : null);

        return view('speedshop.stock-in.supplier.create', compact('categories', 'brands', 'defaultBrandId', 'suppliers', 'suppliersForJs', 'productsForJs'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function productsForSupplierJs(?int $warehouseId, ?int $speedshopId): array
    {
        if (! $warehouseId || ! $speedshopId) {
            return [];
        }

        $stockMap = SpeedshopStock::query()
            ->where('speedshop_id', $speedshopId)
            ->get()
            ->keyBy('product_id');

        return Product::query()
            ->where('warehouse_id', $warehouseId)
            ->forSpeedshopStokCatalog($speedshopId, ProductMasterCatalog::SPEEDSHOP_SUPPLIER)
            ->with(['category:id,nama', 'brand:id,nama'])
            ->orderBy('nama_produk')
            ->get()
            ->map(fn (Product $p) => [
                'id' => (int) $p->id,
                'kode_produk' => (string) $p->kode_produk,
                'kode_supplier' => (string) ($p->kode_supplier ?? ''),
                'nama_produk' => (string) $p->nama_produk,
                'category_id' => (int) $p->category_id,
                'category_nama' => (string) ($p->category?->nama ?? ''),
                'brand_id' => $p->brand_id ? (int) $p->brand_id : null,
                'brand_nama' => (string) ($p->brand?->nama ?? ''),
                'hpp' => (float) ($p->hpp ?? $p->harga_pembelian ?? 0),
                'harga_pembelian' => (float) ($p->harga_pembelian ?? $p->hpp ?? 0),
                'harga_jual' => (float) ($p->harga_jual ?? $p->harga_jual_speedshop ?? 0),
                'harga_jual_speedshop' => (float) ($p->harga_jual_speedshop ?? $p->harga_jual ?? 0),
                'jumlah' => (int) (($stockMap[$p->id] ?? null)?->jumlah ?? 0),
            ])
            ->values()
            ->all();
    }

    public function storeProductQuick(Request $request)
    {
        abort_if(auth()->user()?->role === User::ROLE_SPEEDSHOP_FLP, 403, 'Akun FLP tidak dapat menambah produk dari halaman ini.');

        $warehouseId = $this->warehouseId();
        $speedshopId = auth()->user()?->activeSpeedshopId();
        if (! $warehouseId || ! $speedshopId) {
            return response()->json(['message' => 'Outlet speedshop tidak terdeteksi.'], 422);
        }

        $scopeId = (int) $speedshopId;
        $validated = $request->validate([
            'kode_produk' => [
                'required',
                'string',
                'max:50',
                Rule::unique('products', 'kode_produk')->where(function ($query) use ($warehouseId, $scopeId) {
                    return $query->where('warehouse_id', $warehouseId)->where('speedshop_scope_id', $scopeId);
                }),
            ],
            'nama_produk' => ['required', 'string', 'max:255'],
            'category_id' => Category::validationRulesCategoryIdForSpeedshopProduct($warehouseId, $scopeId),
            'brand_id' => [
                'required',
                'integer',
                Rule::exists('brands', 'id'),
            ],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'hpp' => ['nullable', 'numeric', 'min:0'],
            'harga_jual' => ['nullable', 'numeric', 'min:0.01'],
        ]);

        $hpp = (float) ($validated['hpp'] ?? 0);
        $hargaJual = (float) ($validated['harga_jual'] ?? 0.01);

        $kodeSupplierProduk = null;
        if (filled($validated['supplier_id'] ?? null)) {
            $supplier = Supplier::query()->forSpeedshop($scopeId)->find((int) $validated['supplier_id']);
            $kodeSupplierProduk = $supplier?->kode_supplier
                ? mb_strtoupper(trim((string) $supplier->kode_supplier))
                : null;
        }

        $product = Product::create([
            'kode_produk' => mb_strtoupper(trim((string) $validated['kode_produk'])),
            'nama_produk' => trim((string) $validated['nama_produk']),
            'category_id' => (int) $validated['category_id'],
            'kode_supplier' => $kodeSupplierProduk,
            'brand_id' => (int) $validated['brand_id'],
            'warehouse_id' => $warehouseId,
            'speedshop_scope_id' => $scopeId,
            'jenis_produk' => ProductMasterCatalog::jenisProdukFor(ProductMasterCatalog::SPEEDSHOP_SUPPLIER),
            'jumlah' => 0,
            'hpp' => $hpp,
            'harga_pembelian' => $hpp,
            'harga_jual_speedshop' => $hargaJual,
            'harga_jual' => $hargaJual,
            'harga_jual_reseler' => 0,
            'harga_eceran_terendah' => 0,
        ]);

        SpeedshopStock::firstOrCreate(
            ['speedshop_id' => $scopeId, 'product_id' => (int) $product->id],
            ['jumlah' => 0],
        );

        $product->load(['category:id,nama', 'brand:id,nama']);

        return response()->json([
            'product' => [
                'id' => (int) $product->id,
                'kode_produk' => (string) $product->kode_produk,
                'kode_supplier' => (string) ($product->kode_supplier ?? $kodeSupplierProduk ?? ''),
                'nama_produk' => (string) $product->nama_produk,
                'category_id' => (int) $product->category_id,
                'category_nama' => (string) ($product->category?->nama ?? ''),
                'brand_id' => $product->brand_id ? (int) $product->brand_id : null,
                'brand_nama' => (string) ($product->brand?->nama ?? ''),
                'hpp' => (float) ($product->hpp ?? $product->harga_pembelian ?? 0),
                'harga_pembelian' => (float) ($product->harga_pembelian ?? $product->hpp ?? 0),
                'harga_jual' => (float) ($product->harga_jual ?? $product->harga_jual_speedshop ?? 0),
                'harga_jual_speedshop' => (float) ($product->harga_jual_speedshop ?? $product->harga_jual ?? 0),
                'jumlah' => 0,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'kode' => ['required', 'string', 'max:100', 'unique:warehouse_transaksis,kode'],
            'catatan' => ['nullable', 'string', 'max:500'],
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'tanggal_jatuh_tempo' => ['required', 'date', 'after_or_equal:today'],
            'invoice' => ['required', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png,webp'],
            'items' => ['required'],
        ]);

        $warehouseId = $this->warehouseId();
        if (! $warehouseId) {
            return response()->json(['message' => 'Warehouse tidak ditemukan.'], 422);
        }
        $speedshopId = auth()->user()->activeSpeedshopId();
        if (! $speedshopId) {
            return response()->json(['message' => 'Speedshop tidak terdeteksi.'], 422);
        }

        $rawItems = $request->input('items', []);
        if (is_string($rawItems)) {
            $decoded = json_decode($rawItems, true);
            $rawItems = is_array($decoded) ? $decoded : [];
        }

        $lines = collect($rawItems)
            ->filter(fn ($row) => filled($row['product_id'] ?? null))
            ->values();
        if ($lines->isEmpty()) {
            return response()->json(['message' => 'Minimal satu produk wajib dipilih pada daftar item.', 'errors' => ['items' => ['Minimal satu item.']]], 422);
        }

        $lineRules = [
            'product_id' => ['required', 'integer'],
            'harga_invoice' => ['required', 'numeric', 'min:0.01'],
            'qty' => ['required', 'integer', 'min:1'],
        ];
        foreach ($lines as $idx => $line) {
            $v = Validator::make($line, $lineRules);
            if ($v->fails()) {
                return response()->json([
                    'message' => 'Data item tidak valid (baris '.($idx + 1).').',
                    'errors' => $v->errors(),
                ], 422);
            }
        }

        $productIds = $lines->map(fn ($row) => (int) ($row['product_id'] ?? 0));
        if ($productIds->count() !== $productIds->unique()->count()) {
            return response()->json(['message' => 'Produk tidak boleh duplikat dalam satu transaksi.', 'errors' => ['items' => ['Produk duplikat.']]], 422);
        }

        $supplierId = (int) $request->input('supplier_id');
        $supplier = Supplier::query()->forSpeedshop((int) $speedshopId)->find($supplierId);
        if (! $supplier) {
            throw ValidationException::withMessages([
                'supplier_id' => ['Supplier tidak valid atau bukan bagian master data outlet ini.'],
            ]);
        }

        $uploadDisk = (string) config('filesystems.upload_disk', 'public');
        $sp = Speedshop::query()->find($speedshopId);
        $outletCode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $sp?->invoiceOutletCode() ?: 'UNK'));
        $prefix = 'speedshop/'.$outletCode.'-'.$speedshopId.'/stok-in-supplier/invoices';
        $invoicePath = $request->file('invoice')->store($prefix, $uploadDisk);
        if (! is_string($invoicePath) || $invoicePath === '') {
            return response()->json(['message' => 'Gagal mengunggah invoice.'], 422);
        }

        $transaksi = null;
        DB::transaction(function () use ($request, $warehouseId, $speedshopId, $lines, $invoicePath, $supplier, &$transaksi) {

            $transaksi = WarehouseTransaksi::create([
                'kode' => $request->kode,
                'tipe' => self::TIPE,
                'user_id' => auth()->id(),
                'warehouse_id' => $warehouseId,
                'supplier_id' => $supplier->id,
                'status' => 'pending',
                'approved_by' => null,
                'approved_at' => null,
                'catatan' => $request->catatan,
                'tanggal_jatuh_tempo' => $request->date('tanggal_jatuh_tempo'),
                'invoice_path' => $invoicePath,
            ]);

            foreach ($lines as $line) {
                $product = Product::query()
                    ->where('warehouse_id', $warehouseId)
                    ->forSpeedshopStokCatalog((int) $speedshopId, ProductMasterCatalog::SPEEDSHOP_SUPPLIER)
                    ->whereKey((int) $line['product_id'])
                    ->firstOrFail();

                $hargaInvoice = (float) ($line['harga_invoice'] ?? 0);
                SpeedshopStockInPricing::applyHigherHppOnly($product, $hargaInvoice);

                $transaksi->items()->create([
                    'supplier_id' => $supplier->id,
                    'product_id' => $product->id,
                    'harga_pembelian' => $hargaInvoice,
                    'qty' => (int) ($line['qty'] ?? 0),
                ]);
            }

            foreach (User::accountingUsersForSpeedshop((int) $speedshopId) as $acc) {
                AppNotification::create([
                    'user_id' => $acc->id,
                    'type' => 'speedshop_stok_in_supplier_pending',
                    'title' => 'Stok In Supplier Menunggu Verifikasi',
                    'message' => auth()->user()->name.' mengajukan stok in supplier '.$transaksi->kode,
                    'data' => ['warehouse_transaksi_id' => $transaksi->id],
                    'link' => route('speedshop.stock-in.supplier.show', $transaksi),
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan stok in supplier berhasil dibuat dan menunggu verifikasi Accounting.',
            'redirect' => route('speedshop.stock-in.supplier.show', $transaksi),
        ]);
    }

    public function show(WarehouseTransaksi $warehouse_transaksi)
    {
        $t = $warehouse_transaksi;
        if (($t->tipe ?? '') !== self::TIPE) {
            abort(404);
        }
        $t->load(['user', 'approver', 'warehouse', 'items.product', 'items.supplier']);

        $speedshopId = auth()->user()->activeSpeedshopId();
        if ($speedshopId && (int) ($t->user?->speedshop_id ?? 0) !== (int) $speedshopId) {
            abort(403);
        }

        return view('speedshop.stock-in.supplier.show', ['transaksi' => $t]);
    }

    public function pdf(WarehouseTransaksi $warehouse_transaksi)
    {
        $t = $warehouse_transaksi;
        if (($t->tipe ?? '') !== self::TIPE) {
            abort(404);
        }
        $t->load('user');
        if ($t->user->section !== 'speedshop') {
            abort(403, 'Transaksi ini bukan stok in supplier speedshop.');
        }
        $speedshopId = auth()->user()->activeSpeedshopId();
        if (! $speedshopId || (int) ($t->user->speedshop_id ?? 0) !== (int) $speedshopId) {
            abort(403);
        }
        $warehouseId = $this->warehouseId();
        if ($warehouseId !== null && $t->warehouse_id !== $warehouseId) {
            abort(403);
        }

        $t->load(['user', 'approver', 'warehouse', 'items.product', 'items.supplier']);

        return view('warehouse.transaksi.pdf', ['transaksi' => $t]);
    }

    public function approve(WarehouseTransaksi $warehouse_transaksi)
    {
        $t = $warehouse_transaksi;
        if (($t->tipe ?? '') !== self::TIPE) {
            abort(404);
        }
        if (! auth()->user()->isAccounting()) {
            abort(403, 'Hanya Accounting Speedshop yang dapat menyetujui.');
        }
        if (! $t->isPending()) {
            return response()->json(['message' => 'Transaksi sudah diproses.'], 422);
        }
        $speedshopId = auth()->user()->activeSpeedshopId();
        if (! $speedshopId || (int) ($t->user?->speedshop_id ?? 0) !== (int) $speedshopId) {
            abort(403);
        }

        $warehouseId = $this->warehouseId();
        if ($warehouseId !== null && $t->warehouse_id !== $warehouseId) {
            abort(403);
        }

        DB::transaction(function () use ($t, $speedshopId) {
            $t->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            $tujuanWarehouseId = $t->warehouse_id;
            $t->load('items.product');
            foreach ($t->items as $item) {
                $sourceProduct = $item->product;
                $hargaInvoice = (float) ($item->harga_pembelian ?? 0);

                // Katalog Speedshop outlet. Jangan pakai findByKodeForWarehouseMutasiTarget — itu memprioritaskan MO shared.
                $productTujuan = $sourceProduct;
                if (
                    ! $productTujuan
                    || (int) ($productTujuan->speedshop_scope_id ?? 0) !== (int) $speedshopId
                    || ProductMasterCatalog::kindForProduct($productTujuan) !== ProductMasterCatalog::SPEEDSHOP_SUPPLIER
                ) {
                    $productTujuan = Product::query()
                        ->where('warehouse_id', (int) $tujuanWarehouseId)
                        ->where('kode_produk', (string) ($sourceProduct?->kode_produk ?? ''))
                        ->where('speedshop_scope_id', (int) $speedshopId)
                        ->forCatalogJenis(ProductMasterCatalog::SPEEDSHOP_SUPPLIER)
                        ->first();
                }

                if (! $productTujuan) {
                    $productTujuan = Product::create([
                        'kode_produk' => $sourceProduct->kode_produk,
                        'nama_produk' => $sourceProduct->nama_produk,
                        'category_id' => $sourceProduct->category_id,
                        'brand_id' => $sourceProduct->brand_id,
                        'warehouse_id' => $tujuanWarehouseId,
                        'speedshop_scope_id' => (int) $speedshopId,
                        'jenis_produk' => ProductMasterCatalog::jenisProdukFor(ProductMasterCatalog::SPEEDSHOP_SUPPLIER),
                        'jumlah' => 0,
                        'harga_pembelian' => $item->harga_pembelian ?? $sourceProduct->harga_pembelian ?? 0,
                        'harga_jual' => $sourceProduct->harga_jual ?? 0,
                        'hpp' => $sourceProduct->hpp ?? 0,
                        'harga_jual_speedshop' => $sourceProduct->harga_jual_speedshop ?? 0,
                        'harga_jual_reseler' => $sourceProduct->harga_jual_reseler ?? 0,
                        'harga_eceran_terendah' => $sourceProduct->harga_eceran_terendah ?? 0,
                    ]);
                }

                SpeedshopStockInPricing::applyHigherHppOnly($productTujuan, $hargaInvoice);

                $row = SpeedshopStock::firstOrCreate(
                    ['speedshop_id' => $speedshopId, 'product_id' => $productTujuan->id],
                    ['jumlah' => 0]
                );
                $row->increment('jumlah', $item->qty);

                StockMovement::create([
                    'product_id' => $productTujuan->id,
                    'type' => 'masuk',
                    'qty' => $item->qty,
                    'keterangan' => "Stok In Supplier (approve) - {$t->kode}",
                    'reference' => 'stok_in_supplier:'.$t->id,
                ]);
            }

            app(WarehouseSupplierTagihanService::class)
                ->createFromApprovedSpeedshopStokInSupplier($t->fresh(['items.product', 'supplier']), (int) $speedshopId);

            AppNotification::create([
                'user_id' => $t->user_id,
                'type' => 'speedshop_stok_in_supplier_approved',
                'title' => 'Stok In Supplier Disetujui',
                'message' => auth()->user()->name.' menyetujui transaksi '.$t->kode,
                'data' => ['warehouse_transaksi_id' => $t->id],
                'link' => route('speedshop.stock-in.supplier.show', $t),
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Transaksi disetujui. Stok dan hutang supplier telah diperbarui.']);
    }

    public function reject(Request $request, WarehouseTransaksi $warehouse_transaksi)
    {
        $t = $warehouse_transaksi;
        if (($t->tipe ?? '') !== self::TIPE) {
            abort(404);
        }
        if (! auth()->user()->isAccounting()) {
            abort(403, 'Hanya Accounting Speedshop yang dapat menolak.');
        }
        if (! $t->isPending()) {
            return response()->json(['message' => 'Transaksi sudah diproses.'], 422);
        }
        $speedshopId = auth()->user()->activeSpeedshopId();
        if (! $speedshopId || (int) ($t->user?->speedshop_id ?? 0) !== (int) $speedshopId) {
            abort(403);
        }

        $request->validate([
            'rejected_reason' => ['required', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($request, $t) {
            $t->update([
                'status' => 'rejected',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'rejected_reason' => $request->rejected_reason,
            ]);

            AppNotification::create([
                'user_id' => $t->user_id,
                'type' => 'speedshop_stok_in_supplier_rejected',
                'title' => 'Stok In Supplier Ditolak',
                'message' => auth()->user()->name.' menolak transaksi '.$t->kode.': '.$request->rejected_reason,
                'data' => ['warehouse_transaksi_id' => $t->id],
                'link' => route('speedshop.stock-in.supplier.show', $t),
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Transaksi telah ditolak.']);
    }
}
