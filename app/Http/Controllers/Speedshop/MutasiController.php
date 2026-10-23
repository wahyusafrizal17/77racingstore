<?php

namespace App\Http\Controllers\Speedshop;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\Mutasi;
use App\Models\MutasiItem;
use App\Models\Product;
use App\Models\Speedshop;
use App\Models\SpeedshopStock;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseTransaksiItem;
use App\Support\PdfFilename;
use App\Support\ProductMasterCatalog;
use App\Support\Speedshop\SpeedshopMutasiStockReceive;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MutasiController extends Controller
{
    private function canAccessMutasiForActiveSpeedshop(Mutasi $mutasi, ?int $activeSpeedshopId): bool
    {
        if (! $activeSpeedshopId) {
            return false;
        }

        if (($mutasi->sumber ?? '') !== 'speedshop') {
            return false;
        }

        $sid = (int) $activeSpeedshopId;

        return (int) ($mutasi->speedshop_id ?? 0) === $sid
            || (
                ($mutasi->destination_type ?? 'warehouse') === 'speedshop'
                && (int) ($mutasi->destination_speedshop_id ?? 0) === $sid
            );
    }

    private function isDestinationSpeedshop(Mutasi $mutasi, ?int $activeSpeedshopId): bool
    {
        return $activeSpeedshopId
            && ($mutasi->destination_type ?? 'warehouse') === 'speedshop'
            && (int) ($mutasi->destination_speedshop_id ?? 0) === (int) $activeSpeedshopId;
    }

    private function resolveListPeriod(Request $request): array
    {
        $mode = in_array($request->input('mode'), ['harian', 'bulanan', 'tahunan', 'range'], true)
            ? $request->input('mode')
            : 'bulanan';

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

    public function index(Request $request)
    {
        $period = $this->resolveListPeriod($request);
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

        $speedshopId = auth()->user()->activeSpeedshopId();
        if (! $speedshopId) {
            $mutasis = Mutasi::query()->whereRaw('1=0')->paginate(20)->withQueryString();
            $summaryTotalMutasi = 0;
            $summaryDikirim = 0;
            $summaryDiterima = 0;
            $summaryDitolak = 0;

            return view('speedshop.mutasi.index', compact(
                'mutasis',
                'mode',
                'selectedDate',
                'selectedMonth',
                'selectedYear',
                'selectedDari',
                'selectedSampai',
                'dari',
                'sampai',
                'summaryTotalMutasi',
                'summaryDikirim',
                'summaryDiterima',
                'summaryDitolak',
            ));
        }

        $query = Mutasi::with(['user', 'warehouse', 'destinationSpeedshop', 'items.product'])
            ->where('sumber', 'speedshop');

        // Tampilkan mutasi keluar (speedshop_id = aktif) dan mutasi masuk (destination_speedshop_id = aktif).
        $query->where(function ($q) use ($speedshopId) {
            $q->where('speedshop_id', $speedshopId)
                ->orWhere(function ($q2) use ($speedshopId) {
                    $q2->where('destination_type', 'speedshop')
                        ->where('destination_speedshop_id', $speedshopId);
                });
        });

        if ($periodStart && $periodEnd) {
            $query->whereDate('tanggal', '>=', $periodStart)
                ->whereDate('tanggal', '<=', $periodEnd);
        }

        if ($request->filled('kode')) {
            $query->where('kode', 'like', '%'.$request->kode.'%');
        }

        if ($request->filled('warehouse')) {
            $term = $request->warehouse;
            $query->where(function ($q) use ($term) {
                $q->whereHas('warehouse', fn ($q2) => $q2->where('nama', 'like', '%'.$term.'%'))
                    ->orWhereHas('destinationSpeedshop', fn ($q2) => $q2->where('nama', 'like', '%'.$term.'%'));
            });
        }

        if (
            $request->filled('search')
            && ! $request->filled('kode')
            && ! $request->filled('warehouse')
            && ! $request->filled('status')
        ) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('kode', 'like', "%{$search}%");
            });
        }

        $statsQuery = clone $query;
        $summaryTotalMutasi = (int) (clone $statsQuery)->count();
        $summaryDikirim = (int) (clone $statsQuery)->where('status', 'dikirim')->count();
        $summaryDiterima = (int) (clone $statsQuery)->where('status', 'diterima')->count();
        $summaryDitolak = (int) (clone $statsQuery)->where('status', 'ditolak')->count();

        if ($request->filled('status') && in_array($request->status, ['dikirim', 'diterima', 'ditolak'], true)) {
            $query->where('status', $request->status);
        }

        $mutasis = $query->latest()->paginate(20)->withQueryString();

        return view('speedshop.mutasi.index', compact(
            'mutasis',
            'mode',
            'selectedDate',
            'selectedMonth',
            'selectedYear',
            'selectedDari',
            'selectedSampai',
            'dari',
            'sampai',
            'summaryTotalMutasi',
            'summaryDikirim',
            'summaryDiterima',
            'summaryDitolak',
        ));
    }

    public function create()
    {
        $speedshopId = auth()->user()->activeSpeedshopId();
        if (! $speedshopId) {
            return redirect()->route('speedshop.mutasi.index')
                ->with('error', 'Speedshop tidak terdeteksi.');
        }

        $speedshop = Speedshop::find($speedshopId);
        $warehouseId = $speedshop?->warehouse_id;

        // Samakan inventori: Produk MO (stok-produk-mo) + Produk Speedshop supplier (stok-produk-speedshop).
        $products = $this->productsQueryForMutasi((int) $speedshopId)
            ->get(['id', 'kode_produk', 'kode_supplier', 'nama_produk']);

        $stockByProduct = SpeedshopStock::query()
            ->where('speedshop_id', $speedshopId)
            ->whereIn('product_id', $products->pluck('id'))
            ->pluck('jumlah', 'product_id');

        $supplierKodeFromTransaksi = [];
        if ($warehouseId && $products->isNotEmpty()) {
            $rows = WarehouseTransaksiItem::query()
                ->select(['warehouse_transaksi_items.product_id', 'suppliers.kode_supplier'])
                ->join('warehouse_transaksis', 'warehouse_transaksis.id', '=', 'warehouse_transaksi_items.warehouse_transaksi_id')
                ->join('users', 'users.id', '=', 'warehouse_transaksis.user_id')
                ->join('suppliers', 'suppliers.id', '=', 'warehouse_transaksi_items.supplier_id')
                ->where('warehouse_transaksis.warehouse_id', $warehouseId)
                ->where('users.section', 'speedshop')
                ->where('users.speedshop_id', (int) $speedshopId)
                ->whereIn('warehouse_transaksi_items.product_id', $products->pluck('id'))
                ->whereNotNull('warehouse_transaksi_items.supplier_id')
                ->orderByDesc('warehouse_transaksi_items.id')
                ->get();

            foreach ($rows as $r) {
                $pid = (int) $r->product_id;
                if (! array_key_exists($pid, $supplierKodeFromTransaksi)) {
                    $supplierKodeFromTransaksi[$pid] = $r->kode_supplier ?: null;
                }
            }
        }

        $stocks = $products->map(function (Product $p) use ($stockByProduct, $supplierKodeFromTransaksi) {
            $supplier = trim((string) ($p->kode_supplier ?? ''));
            if ($supplier === '' && ! empty($supplierKodeFromTransaksi[$p->id])) {
                $supplier = trim((string) $supplierKodeFromTransaksi[$p->id]);
            }

            return [
                'id' => $p->id,
                'kode_produk' => $p->kode_produk,
                'kode_supplier' => $supplier,
                'nama_produk' => $p->nama_produk,
                'jumlah' => (int) ($stockByProduct[$p->id] ?? 0),
            ];
        })->values();

        $warehouses = collect();
        $currentSpeedshop = Speedshop::query()->find($speedshopId);
        $currentNama = $currentSpeedshop?->nama ? trim((string) $currentSpeedshop->nama) : null;

        $speedshopsLain = Speedshop::query()
            ->when($currentNama, fn ($q) => $q->where('nama', '!=', $currentNama))
            ->whereKeyNot($speedshopId)
            ->orderBy('nama')
            ->get(['id', 'nama', 'alamat']);

        $autoKode = Mutasi::generateKode();

        return view('speedshop.mutasi.create', [
            'stocks' => $stocks,
            'warehouses' => $warehouses,
            'speedshopsLain' => $speedshopsLain,
            'autoKode' => $autoKode,
            'currentSpeedshopId' => (int) $speedshopId,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'destination_type' => ['required', 'in:speedshop'],
            'speedshop_tujuan_id' => ['required', 'integer', 'exists:speedshops,id'],
            // Nomor surat jalan di-generate otomatis; tidak perlu diisi manual dari form.
            'nomor_surat_jalan' => ['nullable', 'string', 'max:100'],
            'catatan' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $speedshopId = auth()->user()->activeSpeedshopId();
        if (! $speedshopId) {
            return response()->json(['message' => 'Speedshop tidak terdeteksi.'], 403);
        }

        $destinationType = 'speedshop';
        $warehouseTujuanId = null;
        $speedshopTujuanId = (int) $request->speedshop_tujuan_id;

        if ($destinationType === 'speedshop' && $speedshopTujuanId === $speedshopId) {
            return response()->json(['message' => 'Speedshop tujuan tidak boleh sama dengan speedshop asal.'], 422);
        }

        foreach ($request->items as $item) {
            $row = SpeedshopStock::where('speedshop_id', $speedshopId)
                ->where('product_id', $item['product_id'])
                ->first();
            $qty = (int) $item['quantity'];
            if (! $row || $row->jumlah < $qty) {
                $product = Product::find($item['product_id']);

                return response()->json([
                    'message' => 'Stok '.($product->nama_produk ?? 'produk').' tidak mencukupi. Tersedia: '.($row->jumlah ?? 0),
                ], 422);
            }
        }

        $mutasi = null;
        $warehouseTujuan = null;
        $speedshopTujuan = $speedshopTujuanId ? Speedshop::find($speedshopTujuanId) : null;

        DB::transaction(function () use ($request, $speedshopId, $destinationType, $warehouseTujuanId, $speedshopTujuanId, $warehouseTujuan, $speedshopTujuan, &$mutasi) {
            $mutasi = Mutasi::create([
                'kode' => Mutasi::generateKode(),
                'nomor_surat_jalan' => $request->filled('nomor_surat_jalan')
                    ? (string) $request->nomor_surat_jalan
                    : Mutasi::generateNomorSuratJalan(),
                'tanggal' => now()->toDateString(),
                'user_id' => auth()->id(),
                // `warehouse_id` tidak boleh null di schema + ada FK. Untuk mutasi antar speedshop,
                // simpan warehouse asal speedshop agar valid.
                'warehouse_id' => null,
                'speedshop_id' => $speedshopId,
                'sumber' => 'speedshop',
                'destination_type' => $destinationType,
                'destination_speedshop_id' => $speedshopTujuanId,
                'status' => 'dikirim',
                'catatan' => $request->catatan,
            ]);

            foreach ($request->items as $item) {
                MutasiItem::create([
                    'mutasi_id' => $mutasi->id,
                    'product_id' => $item['product_id'],
                    'quantity' => (int) $item['quantity'],
                ]);
                SpeedshopStock::where('speedshop_id', $speedshopId)
                    ->where('product_id', $item['product_id'])
                    ->decrement('jumlah', (int) $item['quantity']);
            }

            $totalItems = collect($request->items)->sum('quantity');
            if ($destinationType === 'warehouse' && $warehouseTujuanId) {
                $tujuanNama = $warehouseTujuan ? $warehouseTujuan->nama : 'Warehouse';
                $mutasiLink = route('warehouse.mutasi-masuk.show', $mutasi);

                $warehouseUsers = User::where('section', 'warehouse')
                    ->where(function ($q) use ($warehouseTujuanId) {
                        $q->where('warehouse_id', $warehouseTujuanId)->orWhereNull('warehouse_id');
                    })
                    ->get();

                foreach ($warehouseUsers as $user) {
                    if ($user->activeWarehouseId() != $warehouseTujuanId) {
                        continue;
                    }
                    AppNotification::create([
                        'user_id' => $user->id,
                        'type' => 'mutasi_produk',
                        'title' => 'Mutasi dari Speedshop',
                        'message' => "Mutasi {$mutasi->kode} ({$totalItems} unit) dari Speedshop ke {$tujuanNama}",
                        'data' => ['mutasi_id' => $mutasi->id, 'kode' => $mutasi->kode],
                        'link' => $mutasiLink,
                    ]);
                }
            } elseif ($destinationType === 'speedshop' && $speedshopTujuanId && $speedshopTujuan) {
                $mutasiLink = route('speedshop.mutasi.show', $mutasi);

                $targets = User::query()
                    ->where('section', 'speedshop')
                    ->where(function ($q) use ($speedshopTujuanId) {
                        $q->where('speedshop_id', $speedshopTujuanId)
                            // Manager regional juga boleh dapat notifikasi
                            ->orWhereIn('id', User::speedshopManagersForOutlet($speedshopTujuanId)->pluck('id')->all());
                    })
                    ->get();

                foreach ($targets as $u) {
                    AppNotification::create([
                        'user_id' => $u->id,
                        'type' => 'mutasi_produk',
                        'title' => 'Mutasi masuk dari Speedshop lain',
                        'message' => "Mutasi {$mutasi->kode} ({$totalItems} unit) dari Speedshop asal ke {$speedshopTujuan->nama}",
                        'data' => ['mutasi_id' => $mutasi->id, 'kode' => $mutasi->kode],
                        'link' => $mutasiLink,
                    ]);
                }
            }
        });

        $message = $destinationType === 'warehouse'
            ? 'Mutasi ke Warehouse berhasil dibuat. Warehouse tujuan akan mendapat notifikasi untuk verifikasi.'
            : 'Mutasi ke Speedshop lain berhasil dibuat.';

        return response()->json([
            'success' => true,
            'message' => $message,
            'redirect' => route('speedshop.mutasi.show', $mutasi),
        ]);
    }

    public function show(Mutasi $mutasi)
    {
        $speedshopId = auth()->user()->activeSpeedshopId();
        if (! $this->canAccessMutasiForActiveSpeedshop($mutasi, $speedshopId)) {
            abort(403, 'Mutasi ini bukan milik Speedshop Anda.');
        }

        $mutasi->load(['user', 'warehouse', 'destinationSpeedshop', 'items.product']);

        if ($mutasi->status === 'diterima'
            && ($mutasi->destination_type ?? '') === 'speedshop'
            && $mutasi->destination_speedshop_id) {
            try {
                DB::transaction(fn () => $this->applySpeedshopInterOutletReceivedStock(
                    $mutasi,
                    (int) $mutasi->destination_speedshop_id
                ));
                $mutasi->load(['items.product']);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $supplierKodeByProduct = [];
        $speedshop = Speedshop::find($speedshopId);
        $warehouseId = $speedshop?->warehouse_id;
        $productIds = $mutasi->items->pluck('product_id')->filter()->unique()->values();

        if ($warehouseId && $productIds->count() > 0) {
            $rows = WarehouseTransaksiItem::query()
                ->select(['warehouse_transaksi_items.product_id', 'suppliers.kode_supplier'])
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
                if (! array_key_exists($pid, $supplierKodeByProduct)) {
                    $supplierKodeByProduct[$pid] = $r->kode_supplier ?: null;
                }
            }
        }

        return view('speedshop.mutasi.show', compact('mutasi', 'supplierKodeByProduct'));
    }

    public function pdf(Mutasi $mutasi)
    {
        $speedshopId = auth()->user()->activeSpeedshopId();
        if (! $this->canAccessMutasiForActiveSpeedshop($mutasi, $speedshopId)) {
            abort(403, 'Mutasi ini bukan milik Speedshop Anda.');
        }

        $mutasi->load(['user', 'warehouse', 'destinationSpeedshop', 'items.product']);

        $filename = PdfFilename::mutasi((string) $mutasi->kode);

        return Pdf::loadView('speedshop.mutasi.pdf', compact('mutasi'))
            ->setPaper('a4', 'portrait')
            ->stream($filename, ['Attachment' => false]);
    }

    public function verify(Request $request, Mutasi $mutasi)
    {
        $speedshopId = auth()->user()->activeSpeedshopId();
        if (! $this->isDestinationSpeedshop($mutasi, $speedshopId)) {
            abort(403);
        }

        return DB::transaction(function () use ($mutasi) {
            /** @var Mutasi $locked */
            $locked = Mutasi::query()->whereKey($mutasi->id)->lockForUpdate()->firstOrFail();
            if (! $locked->isDikirim()) {
                return response()->json(['message' => 'Hanya mutasi berstatus Dikirim yang dapat diverifikasi.'], 422);
            }

            $locked->update([
                'status' => 'diterima',
                'verified_at' => now(),
                'verified_by' => auth()->id(),
                'rejected_at' => null,
                'rejected_by' => null,
            ]);

            if (($locked->destination_type ?? '') === 'speedshop' && $locked->destination_speedshop_id) {
                $this->applySpeedshopInterOutletReceivedStock($locked, (int) $locked->destination_speedshop_id);
            }

            // Notifikasi balik ke speedshop asal
            foreach (User::speedshopManagersForOutlet((int) $locked->speedshop_id) as $u) {
                AppNotification::create([
                    'user_id' => $u->id,
                    'type' => 'mutasi_produk',
                    'title' => 'Mutasi diverifikasi',
                    'message' => auth()->user()->name.' memverifikasi mutasi '.$locked->kode.'.',
                    'data' => ['mutasi_id' => $locked->id, 'kode' => $locked->kode],
                    'link' => route('speedshop.mutasi.show', $locked),
                ]);
            }

            return response()->json(['success' => true]);
        });
    }

    public function reject(Request $request, Mutasi $mutasi)
    {
        $speedshopId = auth()->user()->activeSpeedshopId();
        if (! $this->isDestinationSpeedshop($mutasi, $speedshopId)) {
            abort(403);
        }

        $validated = $request->validate([
            'rejected_reason' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        return DB::transaction(function () use ($mutasi, $speedshopId, $validated) {
            /** @var Mutasi $locked */
            $locked = Mutasi::query()->whereKey($mutasi->id)->lockForUpdate()->firstOrFail();
            if (! $locked->isDikirim()) {
                return response()->json(['message' => 'Hanya mutasi berstatus Dikirim yang dapat ditolak.'], 422);
            }

            $locked->load(['items']);

            // Kembalikan stok ke cabang pengirim (stok tujuan baru ditambah saat verifikasi diterima).
            foreach ($locked->items as $it) {
                $pid = (int) $it->product_id;
                $qty = (int) $it->quantity;
                if ($qty <= 0) {
                    continue;
                }

                $srcRow = SpeedshopStock::firstOrCreate(
                    ['speedshop_id' => (int) $locked->speedshop_id, 'product_id' => $pid],
                    ['jumlah' => 0]
                );
                $srcRow->increment('jumlah', $qty);
            }

            $locked->update([
                'status' => 'ditolak',
                'rejected_at' => now(),
                'rejected_by' => auth()->id(),
                'rejected_reason' => Schema::hasColumn('mutasis', 'rejected_reason')
                    ? trim((string) ($validated['rejected_reason'] ?? ''))
                    : null,
            ]);

            foreach (User::speedshopManagersForOutlet((int) $locked->speedshop_id) as $u) {
                AppNotification::create([
                    'user_id' => $u->id,
                    'type' => 'mutasi_produk',
                    'title' => 'Mutasi ditolak',
                    'message' => auth()->user()->name.' menolak mutasi '.$locked->kode.': '.trim((string) ($validated['rejected_reason'] ?? '')),
                    'data' => ['mutasi_id' => $locked->id, 'kode' => $locked->kode],
                    'link' => route('speedshop.mutasi.show', $locked),
                ]);
            }

            return response()->json(['success' => true]);
        });
    }

    /** Produk yang boleh dimutasi dari outlet (ORI MO + supplier outlet). */
    private function productsQueryForMutasi(int $speedshopId): Builder
    {
        return Product::query()
            ->forUser()
            ->where(function (Builder $outer) use ($speedshopId) {
                foreach (array_merge(
                    ProductMasterCatalog::warehouseSharedKinds(),
                    [ProductMasterCatalog::SPEEDSHOP_SUPPLIER],
                ) as $kind) {
                    $outer->orWhere(function (Builder $q) use ($speedshopId, $kind) {
                        $q->forSpeedshopStokCatalog($speedshopId, $kind);
                    });
                }
            })
            ->orderBy('nama_produk');
    }

    private function applySpeedshopInterOutletReceivedStock(Mutasi $mutasi, int $destinationSpeedshopId): void
    {
        $warehouseId = (int) Speedshop::query()->whereKey($destinationSpeedshopId)->value('warehouse_id');
        if ($warehouseId <= 0) {
            throw new \InvalidArgumentException('Warehouse cabang tujuan tidak terdeteksi.');
        }

        SpeedshopMutasiStockReceive::apply($mutasi, $destinationSpeedshopId);
    }
}
