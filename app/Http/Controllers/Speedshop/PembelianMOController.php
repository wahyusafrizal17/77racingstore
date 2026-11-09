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
use App\Services\ModifikasiOri\ModifikasiOriApiException;
use App\Services\ModifikasiOri\MoPurchaseOrderService;
use App\Support\ProductMasterCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

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

    private function moService(): MoPurchaseOrderService
    {
        return app(MoPurchaseOrderService::class);
    }

    /** Status daftar PO (filter index) untuk PO yang dikirim ke Modifikasi Ori via API. */
    private function moIndexStatus(SpeedshopPurchaseOrder $po): string
    {
        return match ($po->mo_status) {
            SpeedshopPurchaseOrder::MO_STATUS_APPROVED => 'processed',
            SpeedshopPurchaseOrder::MO_STATUS_SHIPPED => 'speedshop_decision',
            SpeedshopPurchaseOrder::MO_STATUS_REJECTED_BY_WAREHOUSE => 'warehouse_decision',
            SpeedshopPurchaseOrder::MO_STATUS_RECEIVED => $po->received_at !== null ? 'finished' : 'speedshop_decision',
            SpeedshopPurchaseOrder::MO_STATUS_CANCELLED,
            SpeedshopPurchaseOrder::MO_STATUS_REJECTED_BY_PARTNER => 'finished',
            default => 'submitted',
        };
    }

    private function mapPurchaseOrderRow(SpeedshopPurchaseOrder $po): array
    {
        if ($po->isModifikasiOriPo()) {
            return [
                'kode' => $po->invoice_no ?: $po->kode,
                'tanggal' => $po->created_at,
                'speedshop' => auth()->user()->speedshop?->nama ?? '-',
                'status' => $this->moIndexStatus($po),
                'dibuat_oleh' => $po->creator?->name ?? '-',
                'detail_url' => route('speedshop.stock-in.pembelian-mo.purchase-order.show', $po),
            ];
        }

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

        // Tarik status terbaru dari Modifikasi Ori (maks. sekali per menit per outlet; gagal = tampilkan data terakhir).
        if ($speedshopId && Cache::add('mo_po_index_sync_'.$speedshopId, 1, now()->addMinute())) {
            try {
                $this->moService()->syncMany(
                    SpeedshopPurchaseOrder::query()
                        ->where('speedshop_id', $speedshopId)
                        ->whereNotNull('mo_status')
                        ->whereNotIn('mo_status', SpeedshopPurchaseOrder::MO_FINAL_STATUSES)
                        ->get()
                );
            } catch (ModifikasiOriApiException $e) {
                Log::warning('Sinkron status PO Modifikasi Ori gagal', ['error' => $e->getMessage()]);
            }
        }

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

        if ($purchaseOrder->isModifikasiOriPo()) {
            $syncError = null;
            try {
                $this->moService()->sync($purchaseOrder);
            } catch (ModifikasiOriApiException $e) {
                $syncError = $e->getMessage();
            }
            $purchaseOrder->refresh()->load(['items.product', 'speedshop', 'creator', 'receiver', 'rejector']);

            $user = auth()->user();

            return view('speedshop.stock-in.pembelian-mo.show-mo-purchase-order', [
                'purchaseOrder' => $purchaseOrder,
                'syncError' => $syncError,
                'warehouseName' => (string) config('services.modifikasi_ori.warehouse_name'),
                'canDecide' => $user->isManager() || $user->isSpeedshopStaf(),
            ]);
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

    /**
     * Form Buat PO: produk diambil dari katalog warehouse Modifikasi Ori via API,
     * stok SS = stok lokal 77 Racing (speedshop_stock) untuk SKU yang sama.
     */
    public function create()
    {
        $speedshopId = auth()->user()->activeSpeedshopId();
        $client = $this->moService()->client();

        $catalogError = null;
        $catalog = [];
        if (! $client->isConfigured()) {
            $catalogError = 'Integrasi Modifikasi Ori belum dikonfigurasi. Isi MO_PARTNER_API_URL dan MO_PARTNER_API_TOKEN di .env.';
        } else {
            try {
                $catalog = $client->catalog();
            } catch (ModifikasiOriApiException $e) {
                $catalogError = $e->getMessage();
            }
        }

        $skus = collect($catalog)->map(fn (array $r) => mb_strtoupper(trim((string) ($r['sku'] ?? ''))))->filter()->unique()->values();

        $localStockBySku = collect();
        if ($speedshopId && $skus->isNotEmpty()) {
            $localStockBySku = SpeedshopStock::query()
                ->join('products', 'products.id', '=', 'speedshop_stock.product_id')
                ->where('speedshop_stock.speedshop_id', (int) $speedshopId)
                ->whereIn('products.kode_produk', $skus)
                ->selectRaw('UPPER(TRIM(products.kode_produk)) as sku, SUM(speedshop_stock.jumlah) as jumlah')
                ->groupByRaw('UPPER(TRIM(products.kode_produk))')
                ->pluck('jumlah', 'sku');
        }

        $products = collect($catalog)->map(function (array $r) use ($localStockBySku) {
            $sku = mb_strtoupper(trim((string) ($r['sku'] ?? '')));

            return [
                // TomSelect memakai "id" sebagai value — untuk PO Modifikasi Ori nilainya SKU.
                'id' => $sku,
                'kode_produk' => $sku,
                'kode_supplier' => (string) ($r['kode_supplier'] ?? ''),
                'nama_produk' => (string) ($r['nama'] ?? ''),
                'brand_nama' => (string) ($r['brand'] ?? ''),
                'harga' => (int) ($r['harga'] ?? 0),
                'jumlah' => (int) ($localStockBySku[$sku] ?? 0),
                'jumlah_wh' => (int) ($r['stok_warehouse'] ?? 0),
            ];
        })->filter(fn (array $r) => $r['id'] !== '')->values();

        return view('speedshop.stock-in.pembelian-mo.create', [
            'speedshopId' => $speedshopId,
            'warehouseName' => (string) config('services.modifikasi_ori.warehouse_name'),
            'catalogError' => $catalogError,
            // Keep variable name to avoid refactor in blade JS.
            'warehouseProducts' => $products,
        ]);
    }

    public function storePurchaseOrder(Request $request)
    {
        $validated = $request->validate([
            'catatan' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sku' => ['required', 'string', 'max:100'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
        ], [
            'items.required' => 'Minimal 1 item wajib diisi.',
        ]);

        $speedshopId = auth()->user()->activeSpeedshopId();
        if (! $speedshopId) {
            return response()->json(['message' => 'Speedshop tidak terdeteksi.'], 422);
        }

        try {
            $po = $this->moService()->create((int) $speedshopId, auth()->user(), $validated['catatan'] ?? null, $validated['items']);
        } catch (ModifikasiOriApiException $e) {
            // PO lokal mungkin sudah tersimpan dengan status "Gagal terkirim" — bisa dikirim ulang dari detail.
            $failed = SpeedshopPurchaseOrder::query()
                ->where('speedshop_id', $speedshopId)
                ->where('created_by', auth()->id())
                ->where('mo_status', SpeedshopPurchaseOrder::MO_STATUS_SEND_FAILED)
                ->where('created_at', '>=', now()->subMinutes(2))
                ->latest('id')
                ->first();

            return response()->json([
                'message' => $failed
                    ? 'PO tersimpan tetapi belum terkirim ke Modifikasi Ori: '.$e->getMessage().' Buka detail PO lalu klik "Kirim Ulang".'
                    : $e->getMessage(),
                'redirect' => $failed ? route('speedshop.stock-in.pembelian-mo.purchase-order.show', $failed) : null,
            ], 502);
        }

        return response()->json([
            'success' => true,
            'message' => 'Purchase Order '.$po->kode.' berhasil dikirim ke warehouse Modifikasi Ori.',
            'redirect' => route('speedshop.stock-in.pembelian-mo.purchase-order.show', $po),
        ]);
    }

    private function authorizeMoPurchaseOrder(SpeedshopPurchaseOrder $purchaseOrder): void
    {
        $speedshopId = auth()->user()->activeSpeedshopId();
        if (! $speedshopId || (int) $purchaseOrder->speedshop_id !== (int) $speedshopId) {
            abort(403, 'Purchase Order ini bukan untuk Speedshop Anda.');
        }
        if (! $purchaseOrder->isModifikasiOriPo()) {
            abort(404);
        }
    }

    /** Jalankan aksi PO Modifikasi Ori lalu kembali ke detail dengan pesan sukses/gagal. */
    private function runMoAction(SpeedshopPurchaseOrder $purchaseOrder, callable $action, string $successMessage)
    {
        $back = route('speedshop.stock-in.pembelian-mo.purchase-order.show', $purchaseOrder);

        try {
            $action();
        } catch (ModifikasiOriApiException $e) {
            return redirect($back)->with('error', $e->getMessage());
        } catch (ValidationException $e) {
            return redirect($back)->with('error', collect($e->errors())->flatten()->first() ?: 'Aksi tidak dapat diproses.');
        }

        return redirect($back)->with('success', $successMessage);
    }

    public function moResend(SpeedshopPurchaseOrder $purchaseOrder)
    {
        $this->authorizeMoPurchaseOrder($purchaseOrder);
        if ($purchaseOrder->mo_status !== SpeedshopPurchaseOrder::MO_STATUS_SEND_FAILED) {
            return redirect()->route('speedshop.stock-in.pembelian-mo.purchase-order.show', $purchaseOrder)
                ->with('error', 'PO ini sudah terkirim ke Modifikasi Ori.');
        }

        return $this->runMoAction($purchaseOrder, fn () => $this->moService()->submit($purchaseOrder), 'PO berhasil dikirim ke warehouse Modifikasi Ori.');
    }

    public function moRefresh(SpeedshopPurchaseOrder $purchaseOrder)
    {
        $this->authorizeMoPurchaseOrder($purchaseOrder);

        return $this->runMoAction($purchaseOrder, fn () => $this->moService()->syncMany(collect([$purchaseOrder])), 'Status PO diperbarui dari Modifikasi Ori.');
    }

    public function moCancel(Request $request, SpeedshopPurchaseOrder $purchaseOrder)
    {
        $this->authorizeMoPurchaseOrder($purchaseOrder);
        $validated = $request->validate(['cancelled_reason' => ['required', 'string', 'max:500']]);

        return $this->runMoAction(
            $purchaseOrder,
            fn () => $this->moService()->cancel($purchaseOrder, auth()->user(), trim($validated['cancelled_reason'])),
            'Purchase Order dibatalkan.'
        );
    }

    public function moReceive(SpeedshopPurchaseOrder $purchaseOrder)
    {
        $this->authorizeMoPurchaseOrder($purchaseOrder);
        if (! (auth()->user()->isManager() || auth()->user()->isSpeedshopStaf())) {
            abort(403, 'Hanya Manager/Staf Speedshop yang dapat menerima barang.');
        }

        return $this->runMoAction(
            $purchaseOrder,
            fn () => $this->moService()->receive($purchaseOrder, auth()->user()),
            'Barang diterima. Stok Speedshop telah ditambahkan dan tagihan dibuat di Modifikasi Ori.'
        );
    }

    public function moReject(Request $request, SpeedshopPurchaseOrder $purchaseOrder)
    {
        $this->authorizeMoPurchaseOrder($purchaseOrder);
        if (! (auth()->user()->isManager() || auth()->user()->isSpeedshopStaf())) {
            abort(403, 'Hanya Manager/Staf Speedshop yang dapat menolak barang.');
        }
        $validated = $request->validate(['rejected_reason' => ['required', 'string', 'max:500']]);

        return $this->runMoAction(
            $purchaseOrder,
            fn () => $this->moService()->reject($purchaseOrder, auth()->user(), trim($validated['rejected_reason'])),
            'Penerimaan ditolak. Stok dikembalikan ke warehouse Modifikasi Ori.'
        );
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
