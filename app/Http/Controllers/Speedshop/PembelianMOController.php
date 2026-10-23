<?php

namespace App\Http\Controllers\Speedshop;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\Mutasi;
use App\Models\Product;
use App\Models\SpeedshopPurchaseOrder;
use App\Models\SpeedshopPurchaseOrderItem;
use App\Models\SpeedshopStock;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\TransaksiOffline;
use App\Models\TransaksiOfflineItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseTransaksiItem;
use App\Support\ProductMasterCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PembelianMOController extends Controller
{
    private function warehouseId(): ?int
    {
        return auth()->user()->activeWarehouseId();
    }

    /**
     * Warehouse tujuan untuk Purchase Order (77racing).
     * Fallback penting karena beberapa user speedshop tidak punya warehouse_id pada record speedshop.
     */
    private function purchaseOrderWarehouseId(): ?int
    {
        $fromUser = auth()->user()->activeWarehouseId();
        if ($fromUser) {
            return (int) $fromUser;
        }

        return null;
    }

    private function mapPurchaseOrderRow(SpeedshopPurchaseOrder $po): array
    {
        $to = $po->mirroredTransaksiOffline;
        $status = 'submitted';

        if ($to) {
            if ($to->warehouse_po_review_rejected_at !== null) {
                $status = 'warehouse_decision';
            } elseif ($to->warehouse_po_closed_at !== null) {
                $status = 'finished';
            } elseif ($to->isVerified() || $to->isRejected()) {
                $status = 'finished';
            } elseif ($to->petugas_id !== null) {
                $status = 'speedshop_decision';
            } elseif ($to->warehouse_po_reviewed_at !== null) {
                $status = 'processed';
            } else {
                $status = 'submitted';
            }
        }

        return [
            'kode' => $po->invoice_no ?: $po->kode,
            'tanggal' => $po->created_at,
            'speedshop' => auth()->user()->speedshop?->nama ?? '-',
            'status' => $status,
            'dibuat_oleh' => $to?->user?->name ?? ($po->creator?->name ?? '-'),
            'detail_url' => route('speedshop.stock-in.pembelian-mo.purchase-order.show', $po),
        ];
    }

    public function index(Request $request)
    {
        $speedshopId = auth()->user()->activeSpeedshopId();

        $purchaseOrders = $speedshopId
            ? SpeedshopPurchaseOrder::with(['items.product', 'warehouse', 'mirroredTransaksiOffline.user', 'creator'])
                ->where('speedshop_id', $speedshopId)
                ->latest()
                ->get()
            : collect();

        $rows = $purchaseOrders
            ->map(fn (SpeedshopPurchaseOrder $po) => $this->mapPurchaseOrderRow($po))
            ->values()
            ->toBase();

        $filters = $request->only(['kode', 'speedshop', 'status', 'dibuat_oleh']);

        if (! empty($filters['kode'])) {
            $term = mb_strtolower((string) $filters['kode']);
            $rows = $rows->filter(fn (array $r) => str_contains(mb_strtolower((string) ($r['kode'] ?? '')), $term));
        }

        if (! empty($filters['speedshop'])) {
            $term = mb_strtolower((string) $filters['speedshop']);
            $rows = $rows->filter(fn (array $r) => str_contains(mb_strtolower((string) ($r['speedshop'] ?? '')), $term));
        }

        if (! empty($filters['dibuat_oleh'])) {
            $term = mb_strtolower((string) $filters['dibuat_oleh']);
            $rows = $rows->filter(fn (array $r) => str_contains(mb_strtolower((string) ($r['dibuat_oleh'] ?? '')), $term));
        }

        if (! empty($filters['status'])) {
            $term = (string) $filters['status'];
            $rows = $rows->filter(fn (array $r) => (string) ($r['status'] ?? '') === $term);
        }

        $summaryTotalPo = (int) $rows->count();
        $summaryMenunggu = (int) $rows->filter(fn (array $r) => ($r['status'] ?? '') !== 'finished')->count();

        $rows = $rows->sortByDesc(fn (array $r) => $r['tanggal'])->values();

        return view('speedshop.stock-in.pembelian-mo.index', [
            'rows' => $rows,
            'config' => $this->getConfig(),
            'summaryTotalPo' => $summaryTotalPo,
            'summaryMenunggu' => $summaryMenunggu,
        ]);
    }

    public function showPurchaseOrder(SpeedshopPurchaseOrder $purchaseOrder)
    {
        $speedshopId = auth()->user()->activeSpeedshopId();
        if (! $speedshopId || (int) $purchaseOrder->speedshop_id !== (int) $speedshopId) {
            abort(403, 'Purchase Order ini bukan untuk Speedshop Anda.');
        }

        $purchaseOrder->load([
            'items.product',
            'warehouse',
            'speedshop',
            'creator',
            'approver',
            'rejector',
            'mirroredTransaksiOffline.warehousePoReviewRejectedBy',
            'mirroredTransaksiOffline.rejectedByUser',
        ]);

        $supplierByProduct = [];
        $warehouseId = (int) ($purchaseOrder->warehouse_id ?? 0);
        $productIds = $purchaseOrder->items->pluck('product_id')->filter()->unique()->values();

        if ($warehouseId > 0 && $productIds->count() > 0) {
            $rows = WarehouseTransaksiItem::query()
                ->select([
                    'warehouse_transaksi_items.product_id',
                    'suppliers.kode_supplier',
                    'suppliers.nama as nama_supplier',
                ])
                ->join('warehouse_transaksis', 'warehouse_transaksis.id', '=', 'warehouse_transaksi_items.warehouse_transaksi_id')
                ->join('users', 'users.id', '=', 'warehouse_transaksis.user_id')
                ->join('suppliers', 'suppliers.id', '=', 'warehouse_transaksi_items.supplier_id')
                ->where('warehouse_transaksis.warehouse_id', $warehouseId)
                ->where('users.section', 'speedshop')
                ->where('users.speedshop_id', (int) $speedshopId)
                ->whereIn('warehouse_transaksi_items.product_id', $productIds)
                ->whereNotNull('warehouse_transaksi_items.supplier_id')
                ->orderByDesc('warehouse_transaksi_items.id')
                ->get();

            foreach ($rows as $r) {
                $pid = (int) $r->product_id;
                if (! array_key_exists($pid, $supplierByProduct)) {
                    $supplierByProduct[$pid] = [
                        'kode_supplier' => $r->kode_supplier ?: null,
                        'nama_supplier' => $r->nama_supplier ?: null,
                    ];
                }
            }
        }

        $transaksiOffline = $purchaseOrder->mirroredTransaksiOffline;
        if ($transaksiOffline) {
            $transaksiOffline->loadMissing([
                'items.product',
                'user',
                'warehouse',
                'petugas',
                'rejectedByUser',
                'speedshop',
                'warehousePoReviewedBy',
                'warehousePoReviewRejectedBy',
                'warehousePoClosedBy',
            ]);

            $fromPurchaseOrder = true;

            return view('speedshop.stock-in.transaksi-offline.show', compact('transaksiOffline', 'purchaseOrder', 'fromPurchaseOrder', 'supplierByProduct'));
        }

        // Fallback jika mirror transaksi offline belum ada (seharusnya selalu ada).
        return view('speedshop.stock-in.pembelian-mo.show-purchase-order', compact('purchaseOrder', 'supplierByProduct'));
    }

    public function create()
    {
        $speedshopId = auth()->user()->activeSpeedshopId();
        $warehouseId = $this->purchaseOrderWarehouseId();

        $warehouse = null;
        $suppliers = $speedshopId
            ? Supplier::with('kota')->forSpeedshop((int) $speedshopId)->orderBy('nama')->get()
            : Supplier::with('kota')->forSection(Supplier::SECTION_SPEEDSHOP)->orderBy('nama')->get();

        // Dropdown produk: selaras Inventori → Produk 77racing (stok-produk-mo).
        $speedshopProducts = collect();
        if ($speedshopId) {
            $products = $this->productsQueryForPembelianMo((int) $speedshopId)
                ->with(['brand:id,nama'])
                ->get(['id', 'kode_produk', 'kode_supplier', 'nama_produk', 'brand_id']);

            $stockByProduct = SpeedshopStock::query()
                ->where('speedshop_id', $speedshopId)
                ->whereIn('product_id', $products->pluck('id'))
                ->pluck('jumlah', 'product_id');

            $warehouseStockByKode = collect();
            if ($warehouseId) {
                $warehouseStockByKode = Product::query()
                    ->where('warehouse_id', (int) $warehouseId)
                    ->where('speedshop_scope_id', 0)
                    ->whereIn('kode_produk', $products->pluck('kode_produk')->filter()->unique()->values())
                    ->get(['kode_produk', 'jumlah'])
                    ->mapWithKeys(fn (Product $row) => [
                        mb_strtoupper(trim((string) $row->kode_produk)) => (int) $row->jumlah,
                    ]);
            }

            $speedshopProducts = $products->map(function (Product $p) use ($stockByProduct, $warehouseStockByKode, $warehouseId) {
                $kodeKey = mb_strtoupper(trim((string) $p->kode_produk));

                return [
                    'id' => (int) $p->id,
                    'kode_produk' => (string) ($p->kode_produk ?? ''),
                    'kode_supplier' => (string) ($p->kode_supplier ?? ''),
                    'nama_produk' => (string) ($p->nama_produk ?? ''),
                    'brand_id' => $p->brand_id ? (int) $p->brand_id : null,
                    'brand_nama' => (string) ($p->brand?->nama ?? ''),
                    'jumlah' => (int) ($stockByProduct[$p->id] ?? 0),
                    'jumlah_wh' => (int) ($warehouseStockByKode[$kodeKey] ?? $p->warehouseInventoryStock($warehouseId ? (int) $warehouseId : null)),
                ];
            })->values();
        }

        $supplierByProduct = [];
        if ($warehouseId && $speedshopId && $speedshopProducts->count() > 0) {
            $productIds = $speedshopProducts->pluck('id')->filter()->unique()->values();

            $rows = WarehouseTransaksiItem::query()
                ->select([
                    'warehouse_transaksi_items.product_id',
                    'suppliers.kode_supplier',
                    'suppliers.nama as nama_supplier',
                ])
                ->join('warehouse_transaksis', 'warehouse_transaksis.id', '=', 'warehouse_transaksi_items.warehouse_transaksi_id')
                ->join('users', 'users.id', '=', 'warehouse_transaksis.user_id')
                ->join('suppliers', 'suppliers.id', '=', 'warehouse_transaksi_items.supplier_id')
                ->where('warehouse_transaksis.warehouse_id', (int) $warehouseId)
                ->where('users.section', 'speedshop')
                ->where('users.speedshop_id', (int) $speedshopId)
                ->whereIn('warehouse_transaksi_items.product_id', $productIds)
                ->whereNotNull('warehouse_transaksi_items.supplier_id')
                ->orderByDesc('warehouse_transaksi_items.id')
                ->get();

            foreach ($rows as $r) {
                $pid = (int) $r->product_id;
                if (! array_key_exists($pid, $supplierByProduct)) {
                    $supplierByProduct[$pid] = [
                        'kode_supplier' => $r->kode_supplier ?: null,
                        'nama_supplier' => $r->nama_supplier ?: null,
                    ];
                }
            }
        }

        return view('speedshop.stock-in.pembelian-mo.create', [
            'speedshopId' => $speedshopId,
            'warehouseId' => $warehouseId,
            'warehouseName' => $warehouse?->nama,
            'suppliers' => $suppliers,
            'supplierByProduct' => $supplierByProduct,
            // Keep variable name to avoid refactor in blade JS.
            'warehouseProducts' => $speedshopProducts,
        ]);
    }

    public function storePurchaseOrder(Request $request)
    {
        $request->validate([
            'catatan' => ['nullable', 'string', 'max:500'],
            'items' => ['nullable', 'array'],
            'items.*.product_id' => ['required_with:items', 'exists:products,id'],
            'items.*.qty' => ['required_with:items', 'integer', 'min:1'],
        ]);

        $speedshopId = auth()->user()->activeSpeedshopId();
        $warehouseId = $this->purchaseOrderWarehouseId();
        if (! $speedshopId) {
            return response()->json(['message' => 'Speedshop tidak terdeteksi.'], 422);
        }
        if (! $warehouseId) {
            return response()->json(['message' => 'Warehouse tidak terdeteksi.'], 422);
        }
        if (empty($request->items) || ! is_array($request->items) || count($request->items) < 1) {
            return response()->json(['message' => 'Minimal 1 item wajib diisi.'], 422);
        }

        // Pastikan produk tersedia di warehouse untuk setiap item yang dipesan.
        // (Stok warehouse bisa diproses/ditentukan oleh pihak warehouse saat approve.)
        foreach ($request->items as $it) {
            $p = $this->productsQueryForPembelianMo((int) $speedshopId)
                ->whereKey($it['product_id'])
                ->where('warehouse_id', $warehouseId)
                ->first();
            if (! $p) {
                return response()->json(['message' => 'Produk tidak valid untuk outlet Speedshop ini.'], 422);
            }
        }

        $po = null;
        DB::transaction(function () use ($request, $speedshopId, $warehouseId, &$po) {
            $kode = SpeedshopPurchaseOrder::generateKodeSpeedshop((int) $speedshopId, now());
            $po = SpeedshopPurchaseOrder::create([
                'kode' => $kode,
                // invoice_no dipakai sebagai "kode/invoice" di list; isi sama dengan kode agar konsisten.
                'invoice_no' => $kode,
                'speedshop_id' => $speedshopId,
                'warehouse_id' => $warehouseId,
                'tipe' => 'modifikasi_ori',
                'status' => 'pending',
                'created_by' => auth()->id(),
                'catatan' => $request->catatan ? trim((string) $request->catatan) : null,
            ]);

            foreach ($request->items as $it) {
                SpeedshopPurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'product_id' => (int) $it['product_id'],
                    'qty' => (int) $it['qty'],
                ]);
            }

            // Draft Transaksi Offline untuk alur verifikasi / stok Speedshop (tanpa mengurangi stok warehouse dulu).
            // speedshop_purchase_order_id menghubungkan ke PO agar baris ini tidak tampil ganda di daftar Purchase Order.
            $noTransaksi = TransaksiOffline::generateNoTransaksi();

            $transaksi = TransaksiOffline::create([
                'no_transaksi' => $noTransaksi,
                'tujuan' => 'speedshop',
                'pelanggan_id' => null,
                'speedshop_id' => $speedshopId,
                'nama_toko' => $po->speedshop?->nama ?? 'Speedshop',
                'alamat' => null,
                'no_hp' => null,
                'petugas_id' => null,
                'jenis_pembayaran' => null,
                'user_id' => auth()->id(),
                'warehouse_id' => $warehouseId,
                'speedshop_purchase_order_id' => $po->id,
            ]);

            foreach ($request->items as $it) {
                TransaksiOfflineItem::create([
                    'transaksi_offline_id' => $transaksi->id,
                    'product_id' => (int) $it['product_id'],
                    'qty' => (int) $it['qty'],
                ]);
            }
        });

        // Notifikasi ke Manager Warehouse agar memproses pengajuan PO
        $warehouseUsers = User::query()
            ->where('section', 'warehouse')
            ->where('role', 'Manager')
            ->where(function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId)->orWhereNull('warehouse_id');
            })
            ->get();

        foreach ($warehouseUsers as $u) {
            AppNotification::create([
                'user_id' => $u->id,
                'type' => 'speedshop_purchase_order_pending',
                'title' => 'Purchase Order Speedshop Menunggu Proses',
                'message' => auth()->user()->name.' mengajukan PO '.($po->invoice_no ?: $po->kode),
                'data' => ['speedshop_purchase_order_id' => $po->id],
                // PO Speedshop punya menu khusus di sisi Warehouse.
                'link' => route('warehouse.po-speedshop.index'),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Purchase Order berhasil dibuat.',
            'redirect' => route('speedshop.stock-in.pembelian-mo.index'),
        ]);
    }

    public function lookup(Request $request)
    {
        $no = trim($request->get('no') ?? '');
        if (! $no) {
            return response()->json(['found' => false]);
        }

        $warehouseId = $this->warehouseId();

        $mutasi = Mutasi::with(['items.product'])
            ->where('warehouse_id', $warehouseId)
            ->where('sumber', 'warehouse')
            ->where('status', 'dikirim')
            ->where(function ($q) use ($no) {
                $q->where('nomor_surat_jalan', 'like', "%{$no}%")
                    ->orWhere('kode', 'like', "%{$no}%");
            })
            ->first();

        if ($mutasi) {
            return response()->json([
                'found' => true,
                'type' => 'mutasi',
                'data' => [
                    'id' => $mutasi->id,
                    'kode' => $mutasi->kode,
                    'nomor_surat_jalan' => $mutasi->nomor_surat_jalan,
                    'tanggal' => $mutasi->tanggal?->format('d M Y'),
                    'items' => $mutasi->items->map(fn ($i) => [
                        'product_id' => $i->product_id,
                        'kode_produk' => $i->product->kode_produk ?? '-',
                        'nama_produk' => $i->product->nama_produk ?? '-',
                        'qty' => $i->quantity,
                    ]),
                ],
            ]);
        }

        $transaksi = TransaksiOffline::with(['items.product'])
            ->where('tujuan', 'speedshop')
            ->where('no_transaksi', $no)
            ->first();

        if ($transaksi) {
            return response()->json([
                'found' => true,
                'type' => 'transaksi_offline',
                'data' => [
                    'id' => $transaksi->id,
                    'no_transaksi' => $transaksi->no_transaksi,
                    'nama_toko' => $transaksi->nama_toko,
                    'items' => $transaksi->items->map(fn ($i) => [
                        'product_id' => $i->product_id,
                        'kode_produk' => $i->product->kode_produk ?? '-',
                        'nama_produk' => $i->product->nama_produk ?? '-',
                        'qty' => $i->qty,
                    ]),
                ],
            ]);
        }

        return response()->json(['found' => false]);
    }

    public function showMutasi(Mutasi $mutasi)
    {
        $warehouseId = $this->warehouseId();
        if ($warehouseId !== null && $mutasi->warehouse_id !== $warehouseId) {
            abort(403);
        }
        if ($mutasi->sumber !== 'warehouse') {
            abort(403, 'Mutasi ini bukan mutasi ke Speedshop.');
        }
        $this->assertWarehouseMutasiForActiveSpeedshop($mutasi);

        $mutasi->load(['user', 'warehouse', 'items.product']);

        return view('speedshop.stock-in.pembelian-mo.show-mutasi', compact('mutasi'));
    }

    public function verifyMutasi(Mutasi $mutasi)
    {
        if (! $mutasi->isDikirim()) {
            return response()->json(['message' => 'Mutasi sudah diverifikasi sebelumnya.'], 422);
        }

        $warehouseId = $this->warehouseId();
        if ($warehouseId !== null && $mutasi->warehouse_id !== $warehouseId) {
            abort(403);
        }
        if ($mutasi->sumber !== 'warehouse') {
            return response()->json(['message' => 'Mutasi ini bukan mutasi ke Speedshop.'], 403);
        }

        if (! (auth()->user()->isManager() || auth()->user()->isSpeedshopStaf())) {
            abort(403, 'Hanya Manager/Staf Speedshop yang dapat memverifikasi.');
        }

        $speedshopId = auth()->user()->activeSpeedshopId();
        if (! $speedshopId) {
            return response()->json(['message' => 'Speedshop tidak terdeteksi.'], 403);
        }
        $this->assertWarehouseMutasiForActiveSpeedshop($mutasi);

        DB::transaction(function () use ($mutasi, $speedshopId) {
            $mutasi->update(['status' => 'diterima']);
            $tujuanWarehouseId = $mutasi->warehouse_id;

            foreach ($mutasi->items as $item) {
                $sourceProduct = $item->product;
                $productTujuan = Product::findByKodeForWarehouseMutasiTarget(
                    (int) $tujuanWarehouseId,
                    (string) $sourceProduct->kode_produk,
                    (int) $speedshopId
                );

                if (! $productTujuan) {
                    $productTujuan = Product::create([
                        'kode_produk' => $sourceProduct->kode_produk,
                        'nama_produk' => $sourceProduct->nama_produk,
                        'category_id' => $sourceProduct->category_id,
                        'brand_id' => $sourceProduct->brand_id,
                        'warehouse_id' => $tujuanWarehouseId,
                        'speedshop_scope_id' => 0,
                        'jumlah' => 0,
                        'harga_pembelian' => $sourceProduct->harga_pembelian ?? 0,
                        'harga_jual' => $sourceProduct->harga_jual ?? 0,
                        'hpp' => $sourceProduct->hpp ?? 0,
                        'harga_jual_speedshop' => $sourceProduct->harga_jual_speedshop ?? 0,
                        'harga_jual_reseler' => $sourceProduct->harga_jual_reseler ?? 0,
                        'harga_eceran_terendah' => $sourceProduct->harga_eceran_terendah ?? 0,
                    ]);
                }

                // Stok masuk ke speedshop (bukan ke warehouse)
                $row = SpeedshopStock::firstOrCreate(
                    ['speedshop_id' => $speedshopId, 'product_id' => $productTujuan->id],
                    ['jumlah' => 0]
                );
                $row->increment('jumlah', $item->quantity);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Mutasi berhasil diverifikasi. Stok produk telah ditambahkan.',
        ]);
    }

    public function rejectMutasi(Request $request, Mutasi $mutasi)
    {
        if (! $mutasi->isDikirim()) {
            return response()->json(['message' => 'Mutasi tidak dapat ditolak. Status bukan dikirim.'], 422);
        }

        $warehouseId = $this->warehouseId();
        if ($warehouseId !== null && $mutasi->warehouse_id !== $warehouseId) {
            abort(403);
        }
        if ($mutasi->sumber !== 'warehouse') {
            return response()->json(['message' => 'Mutasi ini bukan mutasi ke Speedshop.'], 403);
        }

        if (! (auth()->user()->isManager() || auth()->user()->isSpeedshopStaf())) {
            abort(403, 'Hanya Manager/Staf Speedshop yang dapat menolak.');
        }
        $this->assertWarehouseMutasiForActiveSpeedshop($mutasi);

        $request->validate([
            'alasan' => ['nullable', 'string', 'max:500'],
        ]);

        $mutasi->loadMissing(['items.product', 'user']);

        DB::transaction(function () use ($mutasi, $request) {
            $mutasi->update([
                'status' => 'ditolak',
                'catatan' => $request->filled('alasan')
                    ? ($mutasi->catatan ? $mutasi->catatan."\n[Ditolak Speedshop] ".$request->alasan : '[Ditolak Speedshop] '.$request->alasan)
                    : $mutasi->catatan,
            ]);

            // Kembalikan stok ke Warehouse (stok sudah dikurangi saat Warehouse memproses pengiriman).
            foreach ($mutasi->items as $item) {
                Product::where('id', $item->product_id)->increment('jumlah', $item->quantity);
                StockMovement::create([
                    'product_id' => $item->product_id,
                    'type' => 'masuk',
                    'qty' => $item->quantity,
                    'keterangan' => "Mutasi ditolak Speedshop - Mutasi: {$mutasi->kode}",
                    'reference' => 'mutasi:'.$mutasi->id,
                ]);
            }

            // Notifikasi ke pengirim (Warehouse user).
            if ($mutasi->user_id) {
                AppNotification::create([
                    'user_id' => $mutasi->user_id,
                    'type' => 'mutasi_ditolak',
                    'title' => 'Mutasi Ditolak Speedshop',
                    'message' => auth()->user()->name.' menolak mutasi '.$mutasi->kode.($request->filled('alasan') ? ': '.$request->alasan : '.'),
                    'data' => ['mutasi_id' => $mutasi->id],
                    'link' => route('warehouse.transaksi-speedshop.show', $mutasi),
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Mutasi ditolak. Stok telah dikembalikan ke Warehouse.',
        ]);
    }

    public function indexTransaksiOffline(Request $request)
    {
        $speedshopId = auth()->user()->activeSpeedshopId();
        if (! $speedshopId) {
            $transaksis = TransaksiOffline::query()->whereRaw('1=0')->paginate(20)->withQueryString();

            return view('speedshop.stock-in.transaksi-offline.index', [
                'transaksis' => $transaksis,
            ]);
        }

        $query = TransaksiOffline::with(['items.product', 'warehouse', 'user'])
            ->where('speedshop_id', $speedshopId)
            ->where('tujuan', 'speedshop')
            ->whereNull('verified_at')
            ->whereNull('rejected_at');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('no_transaksi', 'like', "%{$search}%")
                    ->orWhere('nama_toko', 'like', "%{$search}%");
            });
        }

        $transaksis = $query->latest()->paginate(20)->withQueryString();

        return view('speedshop.stock-in.transaksi-offline.index', compact('transaksis'));
    }

    public function showTransaksiOffline(TransaksiOffline $transaksiOffline)
    {
        $speedshopId = auth()->user()->activeSpeedshopId();
        if (! $speedshopId || $transaksiOffline->speedshop_id != $speedshopId) {
            abort(403, 'Transaksi ini bukan untuk Speedshop Anda.');
        }
        if ($transaksiOffline->tujuan !== 'speedshop') {
            abort(403);
        }

        $transaksiOffline->load([
            'items.product',
            'user',
            'warehouse',
            'petugas',
            'rejectedByUser',
            'speedshop',
            'warehousePoReviewedBy',
            'warehousePoReviewRejectedBy',
        ]);

        return view('speedshop.stock-in.transaksi-offline.show', compact('transaksiOffline'));
    }

    public function pdfTransaksiOffline(TransaksiOffline $transaksiOffline)
    {
        $speedshopId = auth()->user()->activeSpeedshopId();
        if (! $speedshopId || $transaksiOffline->speedshop_id != $speedshopId) {
            abort(403, 'Transaksi ini bukan untuk Speedshop Anda.');
        }
        if ($transaksiOffline->tujuan !== 'speedshop') {
            abort(403);
        }

        $transaksiOffline->load(['items.product', 'user', 'warehouse', 'petugas', 'rejectedByUser']);

        return view('warehouse.transaksi-offline.pdf', compact('transaksiOffline'));
    }

    public function verifyTransaksiOffline(TransaksiOffline $transaksiOffline)
    {
        if ($transaksiOffline->isVerified()) {
            return response()->json(['message' => 'Transaksi sudah diverifikasi sebelumnya.'], 422);
        }
        if ($transaksiOffline->isRejected()) {
            return response()->json(['message' => 'Transaksi telah ditolak. Tidak dapat diverifikasi.'], 422);
        }

        $speedshopId = auth()->user()->activeSpeedshopId();
        if (! $speedshopId || $transaksiOffline->speedshop_id != $speedshopId) {
            abort(403, 'Transaksi ini bukan untuk Speedshop Anda.');
        }
        if ($transaksiOffline->tujuan !== 'speedshop') {
            return response()->json(['message' => 'Transaksi tidak valid.'], 403);
        }

        if (! (auth()->user()->isManager() || auth()->user()->isSpeedshopStaf())) {
            return response()->json(['message' => 'Hanya Manager/Staf Speedshop yang dapat memverifikasi.'], 403);
        }

        if ($transaksiOffline->isAwaitingWarehousePoProcessing()) {
            return response()->json([
                'message' => 'Transaksi ini menunggu warehouse memproses pengajuan (mutasi). Setelah dikirim, verifikasi dapat dilakukan.',
            ], 422);
        }

        DB::transaction(function () use ($transaksiOffline, $speedshopId) {
            $transaksiOffline->update(['verified_at' => now()]);

            foreach ($transaksiOffline->items as $item) {
                $row = SpeedshopStock::firstOrCreate(
                    ['speedshop_id' => $speedshopId, 'product_id' => $item->product_id],
                    ['jumlah' => 0]
                );
                $row->increment('jumlah', $item->qty);
            }
        });

        $transaksiOffline->refresh();
        if ($transaksiOffline->speedshop_purchase_order_id) {
            app(\App\Services\SpeedshopPoTagihanService::class)
                ->createFromVerifiedTransaksi($transaksiOffline->load(['items.product', 'speedshopPurchaseOrder']));
        }

        return response()->json([
            'success' => true,
            'message' => 'Transaksi berhasil diverifikasi. Stok produk telah ditambahkan ke stok Speedshop.',
        ]);
    }

    public function rejectTransaksiOffline(Request $request, TransaksiOffline $transaksiOffline)
    {
        $request->validate([
            'rejected_reason' => ['nullable', 'string', 'max:500'],
        ]);

        if ($transaksiOffline->isVerified()) {
            return response()->json(['message' => 'Transaksi sudah diverifikasi, tidak dapat ditolak.'], 422);
        }
        if ($transaksiOffline->isRejected()) {
            return response()->json(['message' => 'Transaksi sudah ditolak sebelumnya.'], 422);
        }

        $speedshopId = auth()->user()->activeSpeedshopId();
        if (! $speedshopId || $transaksiOffline->speedshop_id != $speedshopId) {
            abort(403, 'Transaksi ini bukan untuk Speedshop Anda.');
        }
        if ($transaksiOffline->tujuan !== 'speedshop') {
            return response()->json(['message' => 'Transaksi tidak valid.'], 403);
        }

        if (! (auth()->user()->isManager() || auth()->user()->isSpeedshopStaf())) {
            return response()->json(['message' => 'Hanya Manager/Staf Speedshop yang dapat menolak transaksi.'], 403);
        }

        if ($transaksiOffline->isAwaitingWarehousePoProcessing()) {
            return response()->json([
                'message' => 'Transaksi ini menunggu warehouse memproses pengajuan. Penolakan dari Speedshop dapat dilakukan setelah warehouse mengirim mutasi.',
            ], 422);
        }

        $transaksiOffline->load('items.product', 'warehouse');

        DB::transaction(function () use ($request, $transaksiOffline) {
            $warehouseId = $transaksiOffline->warehouse_id;

            foreach ($transaksiOffline->items as $item) {
                $product = Product::findOrFail($item->product_id);
                if ($warehouseId !== null && (int) $product->warehouse_id !== (int) $warehouseId) {
                    throw new \RuntimeException('Produk tidak sesuai gudang sumber transaksi.');
                }
                $product->increment('jumlah', $item->qty);

                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => 'masuk',
                    'qty' => $item->qty,
                    'keterangan' => "Tolak transaksi offline — No: {$transaksiOffline->no_transaksi} (stok kembali ke warehouse)",
                    'reference' => 'transaksi_offline_reject:'.$transaksiOffline->id,
                ]);
            }

            $transaksiOffline->update([
                'rejected_at' => now(),
                'rejected_reason' => $request->input('rejected_reason') ? trim((string) $request->input('rejected_reason')) : null,
                'rejected_by' => auth()->id(),
            ]);
        });

        $transaksiOffline->refresh();
        $this->notifyWarehouseTransaksiOfflineRejected($transaksiOffline);

        return response()->json([
            'success' => true,
            'message' => 'Transaksi ditolak. Stok telah dikembalikan ke warehouse.',
        ]);
    }

    private function notifyWarehouseTransaksiOfflineRejected(TransaksiOffline $transaksiOffline): void
    {
        $warehouseId = $transaksiOffline->warehouse_id;
        $speedshopNama = auth()->user()->speedshop?->nama ?? 'Speedshop';
        $link = route('warehouse.transaksi-offline.show', $transaksiOffline);

        $query = User::query()
            ->where('section', 'warehouse')
            ->where('role', 'Manager');

        if ($warehouseId !== null) {
            $query->where(function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId)->orWhereNull('warehouse_id');
            });
        }

        foreach ($query->get() as $user) {
            AppNotification::create([
                'user_id' => $user->id,
                'type' => 'transaksi_offline_rejected',
                'title' => 'Transaksi Offline Ditolak Speedshop',
                'message' => "{$speedshopNama} menolak transaksi {$transaksiOffline->no_transaksi}. Stok telah dikembalikan ke gudang.",
                'data' => ['transaksi_offline_id' => $transaksiOffline->id],
                'link' => $link,
            ]);
        }
    }

    /**
     * Mutasi dari warehouse ke outlet: hanya cabang tujuan (speedshop_id) yang boleh melihat / verifikasi.
     */
    private function assertWarehouseMutasiForActiveSpeedshop(Mutasi $mutasi): void
    {
        if ($mutasi->sumber !== 'warehouse') {
            return;
        }
        $ssid = auth()->user()->activeSpeedshopId();
        if ($mutasi->speedshop_id && $ssid && (int) $mutasi->speedshop_id !== (int) $ssid) {
            abort(403, 'Mutasi ini untuk cabang Speedshop lain.');
        }
    }

    protected function getConfig(): array
    {
        return [
            'pageTitle' => 'Purchase Order',
            'breadcrumb' => 'Purchase Order',
            'indexRoute' => 'speedshop.stock-in.pembelian-mo.index',
            'description' => 'Purchase Order ke warehouse 77racing untuk diproses pengiriman ke Speedshop Anda.',
        ];
    }

    private function productsQueryForPembelianMo(int $speedshopId): Builder
    {
        return Product::query()
            ->forUser()
            ->forSpeedshopStokCatalog($speedshopId, ProductMasterCatalog::MO)
            ->orderBy('nama_produk');
    }
}
