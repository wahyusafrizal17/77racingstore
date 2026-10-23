<?php

namespace App\Http\Controllers\Speedshop;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Speedshop;
use App\Models\SpeedshopStock;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Models\WarehouseTransaksi;
use App\Support\ProductMasterCatalog;
use App\Support\SpeedshopStockInPricing;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PembelianLuarController extends Controller
{
    /** Brand master untuk semua produk dari Pembelian Luar Speedshop. */
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

    public function index(Request $request)
    {
        return view(
            'speedshop.stock-in.pembelian-luar.index',
            $this->pembelianLuarIndexPayload($request, false),
        );
    }

    public function indexVerifikasiPending(Request $request)
    {
        return view(
            'speedshop.stock-in.pembelian-luar.index',
            $this->pembelianLuarIndexPayload($request, true),
        );
    }

    /**
     * @param  bool  $verifikasiPembelianLuarPage  Halaman verifikasi (filter status via dropdown, sama pola setoran).
     * @return array<string, mixed>
     */
    /**
     * @param  \Illuminate\Database\Eloquent\Builder<WarehouseTransaksi>  $query
     * @param  list<int>  $speedshopIds
     */
    private function applyPembelianLuarSpeedshopUserFilter($query, array $speedshopIds): void
    {
        $query->whereHas('user', function ($q) use ($speedshopIds) {
            $q->where('section', 'speedshop');
            if (count($speedshopIds) === 1) {
                $q->where('speedshop_id', (int) $speedshopIds[0]);
            } else {
                $q->whereIn('speedshop_id', $speedshopIds);
            }
        });
    }

    private function assertPembelianLuarTransaksiAccessible(WarehouseTransaksi $transaksi): void
    {
        $transaksi->loadMissing('user');
        if ($transaksi->user->section !== 'speedshop') {
            abort(403, 'Transaksi ini bukan pembelian luar speedshop.');
        }

        $user = auth()->user();
        $speedshopIds = $user?->reportSpeedshopFilterIds() ?? [];
        $txSpeedshopId = (int) ($transaksi->user->speedshop_id ?? 0);

        if ($user?->isSpeedshopHeadArea()) {
            if ($speedshopIds === [] || $txSpeedshopId === 0 || ! in_array($txSpeedshopId, $speedshopIds, true)) {
                abort(403, 'Persetujuan Head Area hanya untuk cabang yang dipegang.');
            }

            return;
        }

        $warehouseId = $this->warehouseId();
        if ($warehouseId !== null && $transaksi->warehouse_id !== $warehouseId) {
            abort(403);
        }

        if ($speedshopIds !== [] && $txSpeedshopId > 0 && ! in_array($txSpeedshopId, $speedshopIds, true)) {
            abort(403);
        }
    }

    private function resolveTransaksiSpeedshopId(WarehouseTransaksi $transaksi): ?int
    {
        $transaksi->loadMissing('user');
        $id = (int) ($transaksi->user->speedshop_id ?? 0);

        return $id > 0 ? $id : (auth()->user()?->activeSpeedshopId() ? (int) auth()->user()->activeSpeedshopId() : null);
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

    private function pembelianLuarIndexPayload(Request $request, bool $verifikasiPembelianLuarPage): array
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

        $warehouseId = $this->warehouseId();
        $speedshopIds = auth()->user()?->reportSpeedshopFilterIds() ?? [];
        $listAllSpeedshops = auth()->user()?->isSpeedshopAllScopeSelected() ?? false;

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

        if ($speedshopIds === [] || (! $warehouseId && ! auth()->user()?->isSpeedshopHeadArea())) {
            $transaksis = WarehouseTransaksi::query()->whereRaw('1=0')->paginate(20);
            $totalAll = $totalApproved = $totalRejected = $totalPending = 0;

            return compact('transaksis', 'totalAll', 'totalApproved', 'totalRejected', 'totalPending') + $periodVars + [
                'verifikasiPembelianLuarManager' => $verifikasiPembelianLuarPage,
                'listAllSpeedshops' => $listAllSpeedshops,
            ];
        }

        // Hanya transaksi yang diajukan oleh user speedshop (pembelian luar); transaksi dari warehouse jangan tampil di sini
        $query = WarehouseTransaksi::with(['user.speedshop', 'approver', 'items.product', 'items.supplier'])
            ->when(
                $warehouseId && ! auth()->user()?->isSpeedshopHeadArea(),
                fn ($q) => $q->where('warehouse_id', $warehouseId),
            )
            ->where(function ($q) {
                // Backward compat: data lama belum punya tipe (NULL)
                $q->whereNull('tipe')->orWhere('tipe', 'pembelian_luar');
            });

        $this->applyPembelianLuarSpeedshopUserFilter($query, $speedshopIds);

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
                $verifikasiPembelianLuarPage,
                fn ($q) => $q->withCount('items')
                    ->orderByDesc('items_count')
                    ->orderByDesc('id'),
                fn ($q) => $q->latest(),
            )
            ->paginate(20)
            ->withQueryString();

        return compact('transaksis', 'totalAll', 'totalApproved', 'totalRejected', 'totalPending') + $periodVars + [
            'verifikasiPembelianLuarManager' => $verifikasiPembelianLuarPage,
            'listAllSpeedshops' => $listAllSpeedshops,
        ];
    }

    public function create()
    {
        $warehouseId = $this->warehouseId();
        $speedshopId = auth()->user()?->activeSpeedshopId();
        $suppliers = $speedshopId
            ? Supplier::query()
                ->forSpeedshop((int) $speedshopId)
                ->orderBy('nama')
                ->get(['id', 'kode_supplier', 'nama', 'no_hp'])
            : collect();

        $suppliersForJs = $suppliers->map(fn ($s) => [
            'id' => (string) $s->id,
            'kode_supplier' => (string) ($s->kode_supplier ?? ''),
            'nama' => (string) ($s->nama ?? ''),
            'no_hp' => (string) ($s->no_hp ?? ''),
        ])->values()->all();

        $productsForJs = $this->productsForPembelianLuarJs($warehouseId, $speedshopId ? (int) $speedshopId : null);

        return view('speedshop.stock-in.pembelian-luar.create', compact('suppliers', 'suppliersForJs', 'productsForJs'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function productsForPembelianLuarJs(?int $warehouseId, ?int $speedshopId): array
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
            ->forSpeedshopStokCatalog($speedshopId, ProductMasterCatalog::PART_LUAR)
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
                'jenis_produk' => Product::JENIS_PART_LUAR,
            ])
            ->values()
            ->all();
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
        $brandId = $this->partLuarBrandId();

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
                'jenis_produk' => Product::JENIS_PART_LUAR,
            ]);
            SpeedshopStockInPricing::applyHigherPricesOnly($product, $hpp, $hargaJualSpeedshop);

            return $product->fresh();
        }

        return Product::create(array_merge($payload, [
            'kode_produk' => $kodeProduk,
            'warehouse_id' => $warehouseId,
            'speedshop_scope_id' => (int) $speedshopId,
            'jenis_produk' => Product::JENIS_PART_LUAR,
            'jumlah' => 0,
            'harga_jual_reseler' => 0,
            'harga_eceran_terendah' => 0,
        ]));
    }

    private function resolveOrCreateSpeedshopSupplier(string $namaToko, string $noHp, string $kodeSupplier): Supplier
    {
        $nama = trim($namaToko);
        $hp = trim($noHp);
        $kode = trim($kodeSupplier);

        // Jika kode supplier sudah ada, gunakan supplier yang existing (tidak membuat data baru).
        // Kode supplier bersifat unique sehingga ini adalah source of truth.
        $byKode = Supplier::query()->where('kode_supplier', $kode)->first();
        if ($byKode) {
            return $byKode;
        }

        $supplier = Supplier::query()
            ->forSection(Supplier::SECTION_SPEEDSHOP)
            ->where('nama', $nama)
            ->where('no_hp', $hp)
            ->first();

        if ($supplier) {
            if ($supplier->kode_supplier && $supplier->kode_supplier !== $kode) {
                throw ValidationException::withMessages([
                    'supplier_kode' => ['Kode supplier tidak sesuai dengan supplier yang sama.'],
                ]);
            }

            $usedByOther = Supplier::query()
                ->where('kode_supplier', $kode)
                ->where('id', '!=', $supplier->id)
                ->exists();
            if ($usedByOther) {
                throw ValidationException::withMessages([
                    'supplier_kode' => ['Kode supplier sudah digunakan supplier lain.'],
                ]);
            }

            if ($supplier->kode_supplier === null || $supplier->kode_supplier === '') {
                $supplier->update(['kode_supplier' => $kode]);
            }

            return $supplier;
        }

        $used = Supplier::query()->where('kode_supplier', $kode)->exists();
        if ($used) {
            throw ValidationException::withMessages([
                'supplier_kode' => ['Kode supplier sudah digunakan supplier lain.'],
            ]);
        }

        return Supplier::create([
            'section' => Supplier::SECTION_SPEEDSHOP,
            'kode_supplier' => $kode,
            'nama' => $nama,
            'no_hp' => $hp,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'kode' => ['required', 'string', 'max:100', 'unique:warehouse_transaksis,kode'],
            'catatan' => ['nullable', 'string', 'max:500'],
            'force_pending' => ['nullable', 'boolean'],
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
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

        $totalHarga = $lines->sum(fn ($item) => (float) ($item['harga_invoice'] ?? 0) * (int) ($item['qty'] ?? 0));
        $butuhVerifikasi = (bool) $request->boolean('force_pending') || $totalHarga > 200_000;

        $uploadDisk = (string) config('filesystems.upload_disk', 'public');
        $sp = Speedshop::query()->find($speedshopId);
        $outletCode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $sp?->invoiceOutletCode() ?: 'UNK'));
        $prefix = 'speedshop/'.$outletCode.'-'.$speedshopId.'/pembelian-luar/invoices';
        $invoicePath = $request->file('invoice')->store($prefix, $uploadDisk);
        if (! is_string($invoicePath) || $invoicePath === '') {
            return response()->json(['message' => 'Gagal mengunggah invoice.'], 422);
        }

        DB::transaction(function () use ($request, $warehouseId, $speedshopId, $butuhVerifikasi, $lines, $invoicePath) {
            $supplierId = (int) $request->input('supplier_id');
            $supplier = Supplier::query()->forSpeedshop((int) $speedshopId)->find($supplierId);
            if (! $supplier) {
                throw ValidationException::withMessages([
                    'supplier_id' => ['Supplier tidak valid.'],
                ]);
            }

            $transaksi = WarehouseTransaksi::create([
                'kode' => $request->kode,
                'tipe' => 'pembelian_luar',
                'user_id' => auth()->id(),
                'warehouse_id' => $warehouseId,
                'status' => $butuhVerifikasi ? 'pending' : 'approved',
                'approved_by' => $butuhVerifikasi ? null : auth()->id(),
                'approved_at' => $butuhVerifikasi ? null : now(),
                'catatan' => $request->catatan,
                'invoice_path' => $invoicePath,
            ]);

            foreach ($lines as $line) {
                $product = Product::query()
                    ->where('warehouse_id', $warehouseId)
                    ->forSpeedshopStokCatalog((int) $speedshopId, ProductMasterCatalog::PART_LUAR)
                    ->whereKey((int) $line['product_id'])
                    ->firstOrFail();

                $hargaInvoice = (float) ($line['harga_invoice'] ?? 0);
                SpeedshopStockInPricing::applyHigherHppOnly($product, $hargaInvoice);

                $hargaPembelian = $hargaInvoice;

                $transaksi->items()->create([
                    'supplier_id' => $supplier->id,
                    'product_id' => $product->id,
                    'harga_pembelian' => $hargaPembelian,
                    'qty' => (int) ($line['qty'] ?? 0),
                ]);
            }

            if ($butuhVerifikasi) {
                $managers = User::speedshopManagersForOutlet($speedshopId);

                foreach ($managers as $manager) {
                    AppNotification::create([
                        'user_id' => $manager->id,
                        'type' => 'speedshop_pembelian_luar_pending',
                        'title' => 'Pembelian Luar Speedshop Menunggu Verifikasi',
                        'message' => auth()->user()->name.' mengajukan pembelian luar '.$transaksi->kode,
                        'data' => ['warehouse_transaksi_id' => $transaksi->id],
                        'link' => route('speedshop.verifikasi.pembelian-luar.show', $transaksi),
                    ]);
                }
            } else {
                // Langsung approved dan update stok Speedshop (mirip approve())
                $transaksi->load('items.product');

                foreach ($transaksi->items as $item) {
                    $productTujuan = $item->product;
                    if (! $productTujuan) {
                        continue;
                    }

                    $row = SpeedshopStock::firstOrCreate(
                        ['speedshop_id' => $speedshopId, 'product_id' => $productTujuan->id],
                        ['jumlah' => 0]
                    );
                    $row->increment('jumlah', $item->qty);

                    StockMovement::create([
                        'product_id' => $productTujuan->id,
                        'type' => 'masuk',
                        'qty' => $item->qty,
                        'keterangan' => "Pembelian Luar Speedshop (≤ 200k) - {$transaksi->kode}",
                        'reference' => 'pembelian_luar:'.$transaksi->id,
                    ]);
                }
            }
        });

        $message = $butuhVerifikasi
            ? 'Transaksi berhasil diajukan, menunggu verifikasi Manager Speedshop.'
            : 'Transaksi berhasil disimpan dan stok Speedshop langsung bertambah (total ≤ Rp 200.000).';

        session()->flash('success', $message);

        return response()->json(['success' => true]);
    }

    public function show(WarehouseTransaksi $warehouse_transaksi)
    {
        $user = auth()->user();
        if ($user && ($user->isManager() || $user->isAccounting())) {
            return redirect()->route('speedshop.verifikasi.pembelian-luar.show', $warehouse_transaksi);
        }

        return $this->renderPembelianLuarShow($warehouse_transaksi, false);
    }

    public function pembelianLuarVerifikasiShow(WarehouseTransaksi $warehouse_transaksi)
    {
        return $this->renderPembelianLuarShow($warehouse_transaksi, true);
    }

    private function renderPembelianLuarShow(WarehouseTransaksi $warehouse_transaksi, bool $verifikasiPembelianLuarPage)
    {
        $this->assertPembelianLuarTransaksiAccessible($warehouse_transaksi);

        $warehouse_transaksi->load(['user.speedshop', 'approver', 'warehouse', 'items.product', 'items.supplier']);
        $transaksi = $warehouse_transaksi;

        return view('speedshop.stock-in.pembelian-luar.show', compact('transaksi', 'verifikasiPembelianLuarPage'));
    }

    public function pdf(WarehouseTransaksi $warehouse_transaksi)
    {
        $this->assertPembelianLuarTransaksiAccessible($warehouse_transaksi);

        $warehouse_transaksi->load(['user', 'approver', 'warehouse', 'items.product', 'items.supplier']);

        return view('warehouse.transaksi.pdf', ['transaksi' => $warehouse_transaksi]);
    }

    public function approve(WarehouseTransaksi $warehouse_transaksi)
    {
        $transaksi = $warehouse_transaksi;
        $this->assertPembelianLuarTransaksiAccessible($transaksi);
        if (! $transaksi->isPending()) {
            return response()->json(['message' => 'Transaksi sudah diproses.'], 422);
        }

        if (! auth()->user()->isManager() && ! auth()->user()->isAccounting()) {
            abort(403, 'Hanya Manager atau Accounting Speedshop yang dapat menyetujui.');
        }

        $speedshopId = $this->resolveTransaksiSpeedshopId($transaksi);
        if (! $speedshopId) {
            return response()->json(['message' => 'Cabang speedshop transaksi tidak terdeteksi.'], 422);
        }

        DB::transaction(function () use ($transaksi, $speedshopId) {
            $transaksi->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            $tujuanWarehouseId = $transaksi->warehouse_id;
            $transaksi->load('items.product');

            foreach ($transaksi->items as $item) {
                $sourceProduct = $item->product;
                // Part luar: stok harus ke baris product_id transaksi (jangan resolve ke katalog Ori shared).
                $productTujuan = $sourceProduct;
                if (! $productTujuan || ($productTujuan->jenis_produk ?? '') !== Product::JENIS_PART_LUAR) {
                    $productTujuan = Product::query()
                        ->where('warehouse_id', (int) $tujuanWarehouseId)
                        ->jenisPartLuar()
                        ->where('kode_produk', (string) ($sourceProduct->kode_produk ?? ''))
                        ->where(function ($q) use ($speedshopId) {
                            $q->where('speedshop_scope_id', (int) $speedshopId)
                                ->orWhere('speedshop_scope_id', 0);
                        })
                        ->orderByDesc('speedshop_scope_id')
                        ->first();
                }

                if (! $productTujuan) {
                    $productTujuan = Product::create([
                        'kode_produk' => $sourceProduct->kode_produk,
                        'nama_produk' => $sourceProduct->nama_produk,
                        'category_id' => $sourceProduct->category_id,
                        'brand_id' => $sourceProduct->brand_id ?? $this->partLuarBrandId(),
                        'warehouse_id' => $tujuanWarehouseId,
                        'speedshop_scope_id' => (int) $speedshopId,
                        'jenis_produk' => Product::JENIS_PART_LUAR,
                        'jumlah' => 0,
                        'harga_pembelian' => $item->harga_pembelian ?? $sourceProduct->harga_pembelian ?? 0,
                        'harga_jual' => $sourceProduct->harga_jual ?? 0,
                        'hpp' => $sourceProduct->hpp ?? 0,
                        'harga_jual_speedshop' => $sourceProduct->harga_jual_speedshop ?? 0,
                        'harga_jual_reseler' => $sourceProduct->harga_jual_reseler ?? 0,
                        'harga_eceran_terendah' => $sourceProduct->harga_eceran_terendah ?? 0,
                    ]);
                } elseif (($productTujuan->jenis_produk ?? '') !== Product::JENIS_PART_LUAR) {
                    $productTujuan->update(['jenis_produk' => Product::JENIS_PART_LUAR]);
                }

                // Stok masuk ke speedshop (bukan ke warehouse)
                $row = SpeedshopStock::firstOrCreate(
                    ['speedshop_id' => $speedshopId, 'product_id' => $productTujuan->id],
                    ['jumlah' => 0]
                );
                $row->increment('jumlah', $item->qty);

                StockMovement::create([
                    'product_id' => $productTujuan->id,
                    'type' => 'masuk',
                    'qty' => $item->qty,
                    'keterangan' => "Pembelian Luar Speedshop (approve) - {$transaksi->kode}",
                    'reference' => 'pembelian_luar:'.$transaksi->id,
                ]);
            }

            AppNotification::create([
                'user_id' => $transaksi->user_id,
                'type' => 'speedshop_pembelian_luar_approved',
                'title' => 'Pembelian Luar Disetujui',
                'message' => auth()->user()->name.' menyetujui transaksi '.$transaksi->kode,
                'data' => ['warehouse_transaksi_id' => $transaksi->id],
                'link' => route('speedshop.stock-in.pembelian-luar.show', $transaksi),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Transaksi '.$transaksi->kode.' berhasil disetujui. Stok telah diperbarui.',
        ]);
    }

    public function reject(Request $request, WarehouseTransaksi $warehouse_transaksi)
    {
        $transaksi = $warehouse_transaksi;
        $this->assertPembelianLuarTransaksiAccessible($transaksi);
        if (! $transaksi->isPending()) {
            return response()->json(['message' => 'Transaksi sudah diproses.'], 422);
        }

        if (! auth()->user()->isManager() && ! auth()->user()->isAccounting()) {
            abort(403, 'Hanya Manager atau Accounting Speedshop yang dapat menolak.');
        }

        $request->validate([
            'rejected_reason' => ['required', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($request, $transaksi) {
            $transaksi->update([
                'status' => 'rejected',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'rejected_reason' => $request->rejected_reason,
            ]);

            AppNotification::create([
                'user_id' => $transaksi->user_id,
                'type' => 'speedshop_pembelian_luar_rejected',
                'title' => 'Pembelian Luar Ditolak',
                'message' => auth()->user()->name.' menolak transaksi '.$transaksi->kode.': '.$request->rejected_reason,
                'data' => ['warehouse_transaksi_id' => $transaksi->id],
                'link' => route('speedshop.stock-in.pembelian-luar.show', $transaksi),
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Transaksi telah ditolak.']);
    }
}
