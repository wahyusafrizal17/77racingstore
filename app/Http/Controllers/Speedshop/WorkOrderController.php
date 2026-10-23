<?php

namespace App\Http\Controllers\Speedshop;

use App\Events\WorkOrderQueueUpdated;
use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\Category;
use App\Models\Invoice;
use App\Models\JasaServis;
use App\Models\Mekanik;
use App\Models\Pelanggan;
use App\Models\Product;
use App\Models\ServiceOrder;
use App\Models\Speedshop;
use App\Models\SpeedshopStock;
use App\Models\User;
use App\Services\ElevenLabsTtsService;
use App\Services\InvoiceSettingsService;
use App\Services\WacloudMessageService;
use App\Support\MonitorAntrianPayload;
use App\Support\ProductMasterCatalog;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

class WorkOrderController extends Controller
{
    /** Lama sinyal panggilan dibroadcast ke semua TV dalam satu cabang (detik). */
    private const MONITOR_PANGGILAN_BROADCAST_SECONDS = 25;

    private function busyMekanikIds(?int $warehouseId = null, ?int $exceptServiceOrderId = null): array
    {
        return ServiceOrder::query()
            ->where('status', 'proses')
            ->whereNotNull('mekanik_id')
            ->when($exceptServiceOrderId, fn ($q) => $q->where('id', '!=', $exceptServiceOrderId))
            ->when($warehouseId !== null, fn ($q) => $q->where(function ($q2) use ($warehouseId) {
                $q2->where('warehouse_id', $warehouseId)->orWhereNull('warehouse_id');
            }))
            ->pluck('mekanik_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function warehouseId(): ?int
    {
        return auth()->user()->activeWarehouseId();
    }

    private function resolveProductCatalogKind(Product $product): string
    {
        return ProductMasterCatalog::kindForProduct($product);
    }

    /**
     * Gabungan katalog inventori outlet untuk pilihan sparepart WO.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function productsForWorkOrderSparepart(
        ?int $warehouseId,
        ?int $speedshopId,
        ?string $search = null,
        ?int $limit = null,
    ): \Illuminate\Support\Collection {
        $query = Product::query()
            ->when($warehouseId !== null, function ($q) use ($warehouseId) {
                $q->where(function ($inner) use ($warehouseId) {
                    $inner->where('warehouse_id', $warehouseId)
                        ->orWhere(function ($orphan) {
                            $orphan->whereNull('warehouse_id')
                                ->where('speedshop_scope_id', 0)
                                ->whereHas('category', fn ($c) => $c->where('section', '!=', Category::SECTION_PRODUKSI));
                        });
                });
            });

        if ($speedshopId) {
            $sid = (int) $speedshopId;
            $query->where(function ($outer) use ($sid) {
                foreach (ProductMasterCatalog::kinds() as $kind) {
                    $outer->orWhere(function ($q) use ($sid, $kind) {
                        $q->forSpeedshopStokCatalog($sid, $kind);
                    });
                }
            });
        } else {
            $query->whereRaw('0 = 1');
        }

        $term = trim((string) $search);
        if ($term !== '') {
            $like = '%'.$term.'%';
            $query->where(function ($q) use ($like) {
                $q->where('kode_produk', 'like', $like)
                    ->orWhere('nama_produk', 'like', $like);
            });
        }

        if ($limit !== null && $limit > 0) {
            $query->limit($limit);
        }

        $products = $query
            ->orderBy('nama_produk')
            ->get([
                'id',
                'kode_produk',
                'nama_produk',
                'harga_jual_speedshop',
                'harga_jual',
                'harga_eceran_terendah',
                'hpp',
                'jenis_produk',
                'speedshop_scope_id',
            ]);

        $speedshopStockMap = ($speedshopId && $products->isNotEmpty())
            ? SpeedshopStock::where('speedshop_id', $speedshopId)
                ->whereIn('product_id', $products->pluck('id'))
                ->get()
                ->keyBy('product_id')
            : collect();

        $catalogOrder = array_flip(ProductMasterCatalog::kinds());

        return $products
            ->map(function (Product $p) use ($speedshopStockMap) {
                $catalogKind = $this->resolveProductCatalogKind($p);

                return [
                    'id' => $p->id,
                    'kode_produk' => $p->kode_produk,
                    'nama_produk' => $p->nama_produk,
                    'harga_jual_speedshop' => $p->harga_jual_speedshop,
                    'harga_jual' => $p->harga_jual,
                    'harga_eceran_terendah' => $p->harga_eceran_terendah,
                    'harga_jual_cust' => $p->hargaJualCustomer(),
                    'hpp' => $p->hpp,
                    'jumlah' => (int) (($speedshopStockMap[$p->id] ?? null)?->jumlah ?? 0),
                    'jenis_produk' => $p->jenis_produk ?? Product::JENIS_ORI,
                    'speedshop_scope_id' => (int) ($p->speedshop_scope_id ?? 0),
                    'catalog_kind' => $catalogKind,
                    'catalog_label' => ProductMasterCatalog::label($catalogKind),
                    'is_part_luar' => $catalogKind === ProductMasterCatalog::PART_LUAR,
                ];
            })
            ->sortBy([
                fn (array $row) => $catalogOrder[$row['catalog_kind']] ?? 9,
                fn (array $row) => mb_strtolower((string) $row['nama_produk']),
            ])
            ->values();
    }

    /** @param  list<string>  $allowed */
    private function normalizeRecordSort(?string $sort, string $default, array $allowed): string
    {
        $legacy = [
            'kunjungan' => 'kunjungan_desc',
            'pengeluaran' => 'pengeluaran_desc',
            'work_order' => 'work_order_desc',
            'pendapatan' => 'pendapatan_desc',
        ];

        $sort = $legacy[$sort ?? ''] ?? $sort;

        return in_array($sort, $allowed, true) ? $sort : $default;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $rows
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function sortPelangganRecordRows(\Illuminate\Support\Collection $rows, string $sort): \Illuminate\Support\Collection
    {
        return match ($sort) {
            'kunjungan_asc' => $rows->sortBy('total_kunjungan')->values(),
            'pengeluaran_asc' => $rows->sortBy('total_pengeluaran')->values(),
            'pengeluaran_desc' => $rows->sortByDesc('total_pengeluaran')->values(),
            default => $rows->sortByDesc('total_kunjungan')->values(),
        };
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $rows
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function sortMekanikRecordRows(\Illuminate\Support\Collection $rows, string $sort): \Illuminate\Support\Collection
    {
        return match ($sort) {
            'work_order_asc' => $rows->sortBy('total_work_order')->values(),
            'pendapatan_asc' => $rows->sortBy('total_pendapatan')->values(),
            'pendapatan_desc' => $rows->sortByDesc('total_pendapatan')->values(),
            default => $rows->sortByDesc('total_work_order')->values(),
        };
    }

    private function broadcastQueueUpdated(): void
    {
        $warehouseId = $this->warehouseId();
        if (! $warehouseId) {
            return;
        }

        WorkOrderQueueUpdated::dispatch((int) $warehouseId);
    }

    public function index(Request $request)
    {
        $isWip = $request->routeIs('speedshop.wip');
        $mekaniks = Mekanik::aktifForSpeedshop(auth()->user()?->activeSpeedshopId())->get();

        if ($isWip) {
            return $this->wipIndex($request, $mekaniks);
        }

        $ssid = auth()->user()?->activeSpeedshopId();

        $query = ServiceOrder::with(['pelanggan', 'kendaraan', 'mekanik'])
            ->when(
                $ssid && Schema::hasColumn('service_orders', 'speedshop_id'),
                fn ($q) => $q->where('speedshop_id', (int) $ssid),
            )
            ->when($warehouseId = $this->warehouseId(), fn ($q) => $q->where(function ($q2) use ($warehouseId) {
                $q2->where('warehouse_id', $warehouseId)->orWhereNull('warehouse_id');
            }));

        if ($request->filled('kode')) {
            $query->where('kode_servis', 'like', '%'.$request->kode.'%');
        }

        if ($request->filled('tanggal')) {
            $query->whereDate('tanggal_masuk', $request->tanggal);
        }

        if ($request->filled('pelanggan')) {
            $term = $request->pelanggan;
            $query->whereHas('pelanggan', fn ($q) => $q->where('nama', 'like', '%'.$term.'%'));
        }

        if ($request->filled('no_polisi')) {
            $term = $request->no_polisi;
            $query->whereHas('kendaraan', fn ($q) => $q->where('nomor_polisi', 'like', '%'.$term.'%'));
        }

        if ($request->filled('sumber')) {
            $query->where('sumber_kedatangan', $request->sumber);
        }

        if ($request->filled('kategori_service')) {
            $query->where('kategori_service', $request->kategori_service);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if (
            $request->filled('search')
            && ! $request->filled('kode')
            && ! $request->filled('tanggal')
            && ! $request->filled('pelanggan')
            && ! $request->filled('no_polisi')
            && ! $request->filled('sumber')
            && ! $request->filled('kategori_service')
            && ! $request->filled('status')
        ) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('kode_servis', 'like', "%{$s}%")
                    ->orWhereHas('pelanggan', fn ($q) => $q->where('nama', 'like', "%{$s}%"))
                    ->orWhereHas('kendaraan', fn ($q) => $q->where('nomor_polisi', 'like', "%{$s}%"));
            });
        }

        $orders = $query->latest('tanggal_masuk')->paginate(20)->withQueryString();

        return view('speedshop.work-order.index', compact('orders', 'mekaniks'));
    }

    private function wipIndex(Request $request, $mekaniks)
    {
        $period = $this->resolveWipPeriod($request);
        $dari = $period['dari'];
        $sampai = $period['sampai'];
        $mode = $period['mode'];
        $selectedDate = $period['selectedDate'];
        $selectedMonth = $period['selectedMonth'];
        $selectedYear = $period['selectedYear'];
        $selectedDari = $period['selectedDari'];
        $selectedSampai = $period['selectedSampai'];

        $baseQuery = $this->wipIndexBaseQuery($dari, $sampai);
        $wipSummaryAntri = (clone $baseQuery)->where('status', 'antri')->count();
        $wipSummaryEstimasi = (int) (clone $baseQuery)->where('status', 'antri')->sum('estimasi_biaya');

        $query = (clone $baseQuery)
            ->with(['pelanggan', 'kendaraan', 'mekanik'])
            ->where('status', 'antri');

        if ($request->filled('kode')) {
            $query->where('kode_servis', 'like', '%'.$request->kode.'%');
        }

        if ($request->filled('pelanggan')) {
            $term = $request->pelanggan;
            $query->whereHas('pelanggan', fn ($q) => $q->where('nama', 'like', '%'.$term.'%'));
        }

        if ($request->filled('no_polisi')) {
            $term = $request->no_polisi;
            $query->whereHas('kendaraan', fn ($q) => $q->where('nomor_polisi', 'like', '%'.$term.'%'));
        }

        if ($request->filled('sumber')) {
            $query->where('sumber_kedatangan', $request->sumber);
        }

        if ($request->filled('kategori_service')) {
            $query->where('kategori_service', $request->kategori_service);
        }

        if (
            $request->filled('search')
            && ! $request->filled('kode')
            && ! $request->filled('pelanggan')
            && ! $request->filled('no_polisi')
            && ! $request->filled('sumber')
            && ! $request->filled('kategori_service')
        ) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('kode_servis', 'like', "%{$s}%")
                    ->orWhereHas('pelanggan', fn ($q) => $q->where('nama', 'like', "%{$s}%"))
                    ->orWhereHas('kendaraan', fn ($q) => $q->where('nomor_polisi', 'like', "%{$s}%"));
            });
        }

        $orders = $query->latest('tanggal_masuk')->paginate(20)->withQueryString();

        return view('speedshop.work-order.index', compact(
            'orders',
            'mekaniks',
            'wipSummaryAntri',
            'wipSummaryEstimasi',
            'mode',
            'selectedDate',
            'selectedMonth',
            'selectedYear',
            'selectedDari',
            'selectedSampai',
            'dari',
            'sampai',
        ));
    }

    /**
     * @return array{dari: Carbon, sampai: Carbon, mode: string, selectedDate: string, selectedMonth: string, selectedYear: int, selectedDari: string, selectedSampai: string}
     */
    private function resolveWipPeriod(Request $request, string $defaultMode = 'harian'): array
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
     * Antrian WIP: tampilkan WO dalam periode + antrian lama (status antri, tanggal masuk sebelum periode).
     *
     * @return \Illuminate\Database\Eloquent\Builder<ServiceOrder>
     */
    private function wipIndexBaseQuery(Carbon $dari, Carbon $sampai): \Illuminate\Database\Eloquent\Builder
    {
        $speedshopIds = auth()->user()?->reportSpeedshopFilterIds() ?? [];

        return ServiceOrder::query()
            ->when(
                Schema::hasColumn('service_orders', 'speedshop_id') && $speedshopIds !== [],
                function ($q) use ($speedshopIds) {
                    if (count($speedshopIds) === 1) {
                        $q->where('speedshop_id', (int) $speedshopIds[0]);
                    } else {
                        $q->whereIn('speedshop_id', $speedshopIds);
                    }
                },
            )
            ->when(
                $speedshopIds === [] && ($warehouseId = $this->warehouseId()),
                fn ($q) => $q->where(function ($q2) use ($warehouseId) {
                    $q2->where('warehouse_id', $warehouseId)->orWhereNull('warehouse_id');
                })
            )
            ->where(function ($q) use ($dari, $sampai) {
                $q->where(function ($q2) use ($dari, $sampai) {
                    $q2->whereDate('tanggal_masuk', '>=', $dari->toDateString())
                        ->whereDate('tanggal_masuk', '<=', $sampai->toDateString());
                })->orWhere(function ($q2) use ($dari) {
                    $q2->where('status', 'antri')
                        ->whereDate('tanggal_masuk', '<', $dari->toDateString());
                });
            });
    }

    public function history(Request $request)
    {
        $user = auth()->user();
        $speedshopIds = $user?->reportSpeedshopFilterIds() ?? [];
        $historyAllSpeedshops = $user?->isSpeedshopAllScopeSelected() ?? false;

        $tipe = (string) $request->query('tipe', '');
        $tipe = in_array($tipe, [ServiceOrder::TIPE_SERVIS, ServiceOrder::TIPE_PEMBELIAN_PART], true) ? $tipe : '';

        $period = $this->resolveWipPeriod($request);
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

        $query = ServiceOrder::with(['pelanggan', 'kendaraan', 'mekanik', 'jasaServis', 'products', 'invoice', 'speedshop'])
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
            )
            ->when($warehouseId = $this->warehouseId(), fn ($q) => $q->where(function ($q2) use ($warehouseId) {
                $q2->where('warehouse_id', $warehouseId)->orWhereNull('warehouse_id');
            }))
            ->when($tipe !== '', fn ($q) => $q->where('tipe_pemesanan', $tipe));

        $historyForMekanik = $user?->isSpeedshopMekanik() ?? false;
        if ($historyForMekanik) {
            $mekanikId = $user?->mekanik?->id;
            if ($mekanikId) {
                $query->where('mekanik_id', $mekanikId);
            } else {
                $query->whereRaw('0 = 1');
            }
        }

        if ($request->filled('kode')) {
            $query->where('kode_servis', 'like', '%'.$request->kode.'%');
        }

        if ($periodStart && $periodEnd) {
            $query->whereTransaksiDateBetween($periodStart, $periodEnd);
        }

        if ($request->filled('tanggal_selesai')) {
            $query->whereDate('tanggal_selesai', $request->tanggal_selesai);
        }

        if ($request->filled('pelanggan')) {
            $term = $request->pelanggan;
            $query->whereHas('pelanggan', fn ($q) => $q->where('nama', 'like', '%'.$term.'%'));
        }

        if ($request->filled('no_polisi')) {
            $term = $request->no_polisi;
            $query->whereHas('kendaraan', fn ($q) => $q->where('nomor_polisi', 'like', '%'.$term.'%'));
        }

        if ($request->filled('mekanik') && ! $historyForMekanik) {
            $term = $request->mekanik;
            $query->whereHas('mekanik', fn ($q) => $q->where('nama', 'like', '%'.$term.'%'));
        }

        if ($request->filled('jenis_pembayaran') && in_array($request->jenis_pembayaran, ['cash', 'transfer', 'qris', 'edc', 'split'], true)) {
            $query->where('jenis_pembayaran', $request->jenis_pembayaran);
        }

        if ($request->filled('sumber')) {
            $query->where('sumber_kedatangan', $request->sumber);
        }

        if ($request->filled('kategori_service')) {
            $query->where('kategori_service', $request->kategori_service);
        }

        if ($request->filled('dari_tanggal')) {
            $query->whereDate('tanggal_selesai', '>=', $request->dari_tanggal);
        }

        if ($request->filled('sampai_tanggal')) {
            $query->whereDate('tanggal_selesai', '<=', $request->sampai_tanggal);
        }

        if (
            $request->filled('search')
            && ! $request->filled('kode')
            && ! $request->filled('tanggal')
            && ! $request->filled('tanggal_masuk')
            && ! $request->filled('tanggal_selesai')
            && ! $request->filled('pelanggan')
            && ! $request->filled('no_polisi')
            && ! $request->filled('mekanik')
            && ! $request->filled('sumber')
            && ! $request->filled('kategori_service')
            && ! $request->filled('dari_tanggal')
            && ! $request->filled('sampai_tanggal')
        ) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('kode_servis', 'like', "%{$s}%")
                    ->orWhereHas('pelanggan', fn ($q) => $q->where('nama', 'like', "%{$s}%"))
                    ->orWhereHas('kendaraan', fn ($q) => $q->where('nomor_polisi', 'like', "%{$s}%"));
            });
        }

        $summaryQuery = (clone $query);

        $orders = $query
            ->orderByDesc('tanggal_selesai')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $totalTransaksi = (int) $summaryQuery->count();
        $grandTotal = (int) $summaryQuery
            ->leftJoin('invoices', 'invoices.service_order_id', '=', 'service_orders.id')
            ->sum(DB::raw('COALESCE(invoices.grand_total, 0)'));

        return view('speedshop.history.index', [
            'orders' => $orders,
            'activeTipe' => $tipe,
            'totalTransaksi' => $totalTransaksi,
            'grandTotal' => $grandTotal,
            'mode' => $mode,
            'selectedDate' => $selectedDate,
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
            'selectedDari' => $selectedDari,
            'selectedSampai' => $selectedSampai,
            'dari' => $dari,
            'sampai' => $sampai,
            'historyAllSpeedshops' => $historyAllSpeedshops,
            'historyForMekanik' => $historyForMekanik,
            'mekanikNama' => $historyForMekanik ? ($user?->mekanik?->nama ?? $user?->name) : null,
        ]);
    }

    /**
     * Cabang yang boleh dipakai untuk monitor TV (hak user yang login).
     *
     * @return list<int>
     */
    private function monitorAntrianAllowedSpeedshopIds(): array
    {
        $user = auth()->user();
        if (! $user?->isSpeedshop()) {
            return [];
        }

        return array_values(array_unique(array_map(
            static fn ($id) => (int) $id,
            $user->activeSpeedshopIds(),
        )));
    }

    /**
     * Monitor TV selalu fix per cabang (bukan ikut ganti akun kasir / filter navbar).
     * Ambil dari ?speedshop_id=… (bookmark di TV), atau cabang tunggal milik akun.
     */
    private function resolveMonitorFixedSpeedshopId(?Request $request = null): ?int
    {
        $request ??= request();
        $allowed = $this->monitorAntrianAllowedSpeedshopIds();
        if ($allowed === []) {
            return null;
        }

        $requested = (int) $request->query('speedshop_id', 0);
        if ($requested > 0 && in_array($requested, $allowed, true)) {
            return $requested;
        }

        if (count($allowed) === 1) {
            return $allowed[0];
        }

        return null;
    }

    /**
     * Scope speedshop untuk monitor TV: selalu satu cabang yang di-pin.
     * null jika tabel service_orders belum punya kolom speedshop_id (filter hanya warehouse).
     *
     * @return null|array<int, int>
     */
    private function monitorAntrianSpeedshopScopeIds(?int $fixedSpeedshopId = null): ?array
    {
        if (! Schema::hasColumn('service_orders', 'speedshop_id')) {
            return null;
        }

        if ($fixedSpeedshopId && $fixedSpeedshopId > 0) {
            return [(int) $fixedSpeedshopId];
        }

        return [];
    }

    /**
     * @return array{warehouseId: int, inProcessData: array<int, array<string, mixed>>, queueData: array<int, array<string, mixed>>, inProcessExpressData: array<int, array<string, mixed>>, inProcessStandardData: array<int, array<string, mixed>>, queueExpressData: array<int, array<string, mixed>>, queueStandardData: array<int, array<string, mixed>>, videoUrl: string, monitorAntrianTitle: string, statTotalHariIni: int, statSelesaiHariIni: int}
     */
    private function buildMonitorTvAntrianPayload(?int $fixedSpeedshopId = null): array
    {
        $fixedSpeedshopId = $fixedSpeedshopId ?: $this->resolveMonitorFixedSpeedshopId();
        if (! $fixedSpeedshopId) {
            abort(403, 'Pilih cabang monitor terlebih dahulu.');
        }

        $speedshop = Speedshop::query()->find($fixedSpeedshopId);
        $warehouseId = (int) ($speedshop?->warehouse_id ?: $speedshop?->mutasi_warehouse_id ?: $this->warehouseId() ?: 0);

        $p = MonitorAntrianPayload::build($warehouseId, $this->monitorAntrianSpeedshopScopeIds($fixedSpeedshopId));
        $videoUrl = env('SPEEDSHOP_TV_VIDEO_URL', 'https://www.youtube.com/embed/dQw4w9WgXcQ');

        return [
            'warehouseId' => $p['warehouseId'],
            'speedshopId' => (int) $fixedSpeedshopId,
            'inProcessData' => $p['inProcessData'],
            'queueData' => $p['queueData'],
            'inProcessExpressData' => $p['inProcessExpressData'],
            'inProcessStandardData' => $p['inProcessStandardData'],
            'queueExpressData' => $p['queueExpressData'],
            'queueStandardData' => $p['queueStandardData'],
            'videoUrl' => $videoUrl,
            'monitorAntrianTitle' => $p['monitorAntrianTitle'],
            'statTotalHariIni' => $p['statTotalHariIni'],
            'statSelesaiHariIni' => $p['statSelesaiHariIni'],
        ];
    }

    private function monitorAntrianPanggilanCacheKey(int $warehouseId): string
    {
        return 'speedshop_monitor_antrian_panggilan_w_'.$warehouseId;
    }

    private function monitorAntrianPanggilanSpeedshopCacheKey(int $speedshopId): string
    {
        return 'speedshop_monitor_antrian_panggilan_ss_'.$speedshopId;
    }

    /**
     * Kunci cache panggilan monitor — harus sama dengan yang dibaca monitorTvAntrianData().
     *
     * @return list<string>
     */
    private function monitorAntrianPanggilanCacheKeys(ServiceOrder $service_order): array
    {
        if (Schema::hasColumn('service_orders', 'speedshop_id')) {
            $ssid = (int) ($service_order->speedshop_id ?? 0);
            if ($ssid > 0) {
                return [$this->monitorAntrianPanggilanSpeedshopCacheKey($ssid)];
            }
        }

        $monitorWarehouseId = (int) ($service_order->warehouse_id ?? 0);
        if ($monitorWarehouseId > 0) {
            return [$this->monitorAntrianPanggilanCacheKey($monitorWarehouseId)];
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $panggilan
     * @return array<string, mixed>|null
     */
    private function filterFreshMonitorPanggilanPayload(array $panggilan): ?array
    {
        $at = (int) ($panggilan['at'] ?? 0);
        if ($at > 0 && $at < now()->getTimestamp() - self::MONITOR_PANGGILAN_BROADCAST_SECONDS) {
            return null;
        }

        return $panggilan;
    }

    /**
     * Baca sinyal panggilan berdasarkan cabang yang di-pin di monitor (bukan session kasir).
     *
     * @return array<string, mixed>|null
     */
    private function peekMonitorAntrianPanggilan(int $monitorWarehouseId, ?int $fixedSpeedshopId = null): ?array
    {
        if (Schema::hasColumn('service_orders', 'speedshop_id')) {
            $ssid = (int) ($fixedSpeedshopId ?: 0);
            if ($ssid > 0) {
                $raw = Cache::get($this->monitorAntrianPanggilanSpeedshopCacheKey($ssid));
                if (is_array($raw)) {
                    return $this->filterFreshMonitorPanggilanPayload($raw);
                }

                return null;
            }
        }

        $raw = Cache::get($this->monitorAntrianPanggilanCacheKey($monitorWarehouseId));
        if (is_array($raw)) {
            return $this->filterFreshMonitorPanggilanPayload($raw);
        }

        return null;
    }

    /**
     * Teks untuk ElevenLabs di monitor TV (bahasa Indonesia).
     *
     * @return list<string>
     */
    private function buildMonitorAntrianPanggilanTexts(ServiceOrder $order): array
    {
        $ctx = $this->monitorAntrianPanggilanContext($order);

        return [
            'Konsumen atas nama '.$ctx['nama'].', dengan kendaraan '.$ctx['kendaraan'].', nomor polisi '.$ctx['plat'].', dipersilakan menuju ke meja kasir. Terima kasih',
        ];
    }

    /** @return array{nama: string, kendaraan: string, plat: string} */
    private function monitorAntrianPanggilanContext(ServiceOrder $order): array
    {
        $order->loadMissing(['pelanggan', 'kendaraan']);

        $nama = trim(preg_replace('/\s+/u', ' ', strip_tags((string) ($order->pelanggan?->nama ?? ''))));
        $merk = trim((string) ($order->kendaraan?->merk ?? ''));
        $tipe = trim((string) ($order->kendaraan?->tipe ?? ''));
        $kendaraan = trim(implode(' ', array_filter([$merk, $tipe], fn ($v) => (string) $v !== '')));
        $kendaraan = trim(preg_replace('/\s+/u', ' ', $kendaraan));
        $plat = trim((string) ($order->kendaraan?->nomor_polisi ?? ''));

        if ($nama === '') {
            $nama = 'konsumen';
        }
        if ($kendaraan === '') {
            $kendaraan = 'kendaraan';
        }
        if ($plat === '') {
            $plat = 'tidak diketahui';
        }

        return [
            'nama' => $nama,
            'kendaraan' => $kendaraan,
            'plat' => $plat,
        ];
    }

    public function monitorTvAntrian(Request $request)
    {
        $allowedIds = $this->monitorAntrianAllowedSpeedshopIds();
        if ($allowedIds === []) {
            abort(403, 'Tidak ada cabang yang dapat ditampilkan di monitor.');
        }

        $fixedSpeedshopId = $this->resolveMonitorFixedSpeedshopId($request);
        if ($fixedSpeedshopId === null) {
            $speedshops = Speedshop::query()
                ->whereIn('id', $allowedIds)
                ->orderBy('nama')
                ->get(['id', 'nama']);

            return view('speedshop.monitor-tv.pilih-cabang', [
                'speedshops' => $speedshops,
            ]);
        }

        // URL bookmarkable per cabang — TV tidak ikut ganti saat akun kasir berganti.
        if ((int) $request->query('speedshop_id', 0) !== (int) $fixedSpeedshopId) {
            return redirect()->route('speedshop.monitor-tv.antrian', [
                'speedshop_id' => $fixedSpeedshopId,
            ]);
        }

        $p = $this->buildMonitorTvAntrianPayload($fixedSpeedshopId);
        $warehouseId = (int) $p['warehouseId'];
        $eleven = app(ElevenLabsTtsService::class);

        return view('speedshop.monitor-tv.antrian', [
            'warehouseId' => $warehouseId,
            'monitorSpeedshopId' => (int) $fixedSpeedshopId,
            'inProcessData' => $p['inProcessData'],
            'queueData' => $p['queueData'],
            'inProcessExpressData' => $p['inProcessExpressData'],
            'inProcessStandardData' => $p['inProcessStandardData'],
            'queueExpressData' => $p['queueExpressData'],
            'queueStandardData' => $p['queueStandardData'],
            'videoUrl' => $p['videoUrl'],
            'monitorAntrianTitle' => $p['monitorAntrianTitle'],
            'statTotalHariIni' => $p['statTotalHariIni'],
            'statSelesaiHariIni' => $p['statSelesaiHariIni'],
            'antrianElevenlabsConfigured' => $eleven->isConfigured(),
        ]);
    }

    public function monitorTvAntrianTts(Request $request, ElevenLabsTtsService $elevenLabsTts): JsonResponse
    {
        if (! $elevenLabsTts->isConfigured()) {
            return response()->json([
                'ok' => false,
                'message' => 'ElevenLabs belum dikonfigurasi (ELEVENLABS_API_KEY / ELEVENLABS_VOICE_ID).',
            ], 503);
        }

        $validated = $request->validate([
            'texts' => ['required', 'array', 'max:5'],
            'texts.*' => ['required', 'string', 'min:1', 'max:500'],
        ]);

        try {
            $urls = $elevenLabsTts->resolveClipUrls($validated['texts']);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], 502);
        }

        $clips = [];
        foreach ($urls as $u) {
            $clips[] = ['url' => $u];
        }

        return response()->json([
            'ok' => true,
            'clips' => $clips,
        ])->header('Cache-Control', 'private, no-store');
    }

    public function monitorTvAntrianData(Request $request): JsonResponse
    {
        $fixedSpeedshopId = $this->resolveMonitorFixedSpeedshopId($request);
        if (! $fixedSpeedshopId) {
            return response()->json([
                'message' => 'Parameter speedshop_id wajib untuk monitor cabang.',
            ], 422);
        }

        $p = $this->buildMonitorTvAntrianPayload($fixedSpeedshopId);
        $panggilan = $this->peekMonitorAntrianPanggilan((int) $p['warehouseId'], $fixedSpeedshopId);

        return response()->json([
            'warehouse_id' => $p['warehouseId'],
            'speedshop_id' => $fixedSpeedshopId,
            'in_process' => $p['inProcessData'],
            'queue' => $p['queueData'],
            'in_process_express' => $p['inProcessExpressData'],
            'in_process_standard' => $p['inProcessStandardData'],
            'queue_express' => $p['queueExpressData'],
            'queue_standard' => $p['queueStandardData'],
            'monitor_antrian_title' => $p['monitorAntrianTitle'],
            'stat_total_hari_ini' => $p['statTotalHariIni'],
            'stat_selesai_hari_ini' => $p['statSelesaiHariIni'],
            'panggilan' => is_array($panggilan) ? $panggilan : null,
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    public function selesaikanPanggilAntrianMonitor(ServiceOrder $service_order): JsonResponse
    {
        $this->assertServiceOrderAccessible($service_order);
        if ($service_order->status !== 'proses') {
            return response()->json([
                'ok' => false,
                'message' => 'Hanya work order berstatus Proses yang dapat dipanggil ke monitor.',
            ], 422);
        }

        $targetSpeedshopId = Schema::hasColumn('service_orders', 'speedshop_id')
            ? (int) ($service_order->speedshop_id ?? auth()->user()?->activeSpeedshopId() ?? 0)
            : 0;
        $monitorWarehouseId = (int) ($service_order->warehouse_id ?? 0);
        if ($monitorWarehouseId <= 0 && $targetSpeedshopId <= 0) {
            return response()->json([
                'ok' => false,
                'message' => 'Cabang monitor tidak terdeteksi.',
            ], 422);
        }

        $texts = $this->buildMonitorAntrianPanggilanTexts($service_order);

        $panggilanPayload = [
            'id' => (int) $service_order->id,
            'at' => now()->getTimestamp(),
            'speedshop_id' => $targetSpeedshopId > 0 ? $targetSpeedshopId : null,
            'texts' => $texts,
        ];

        $eleven = app(ElevenLabsTtsService::class);
        if ($eleven->isConfigured()) {
            try {
                $clipUrls = array_values(array_filter(
                    $eleven->resolveClipUrls($texts),
                    fn ($u) => is_string($u) && $u !== ''
                ));
                if ($clipUrls !== []) {
                    $panggilanPayload['clip_urls'] = $clipUrls;
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $cacheKeys = $this->monitorAntrianPanggilanCacheKeys($service_order);
        if ($cacheKeys === []) {
            return response()->json([
                'ok' => false,
                'message' => 'Tidak dapat menentukan target monitor untuk cabang ini.',
            ], 422);
        }

        $expires = now()->addSeconds(120);
        foreach ($cacheKeys as $cacheKey) {
            Cache::put($cacheKey, $panggilanPayload, $expires);
        }

        return response()->json([
            'ok' => true,
            'monitor_warehouse_id' => $monitorWarehouseId,
            'speedshop_id' => $service_order->speedshop_id,
        ]);
    }

    public function serviceRecord(Request $request)
    {
        $user = auth()->user();
        $speedshopIds = $user?->reportSpeedshopFilterIds() ?? [];
        $serviceRecordAllSpeedshops = $user?->isSpeedshopAllScopeSelected() ?? false;

        $period = $this->resolveWipPeriod($request);
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

        $query = ServiceOrder::with(['pelanggan', 'jasaServis', 'products', 'invoice', 'kendaraan', 'mekanik', 'speedshop'])
            ->where('status', 'selesai')
            ->whereNotNull('pelanggan_id')
            ->when(
                Schema::hasColumn('service_orders', 'speedshop_id') && $speedshopIds !== [],
                function ($q) use ($speedshopIds) {
                    if (count($speedshopIds) === 1) {
                        $q->where('speedshop_id', (int) $speedshopIds[0]);
                    } else {
                        $q->whereIn('speedshop_id', $speedshopIds);
                    }
                },
            )
            ->when($warehouseId = $this->warehouseId(), fn ($q) => $q->where(function ($q2) use ($warehouseId) {
                $q2->where('warehouse_id', $warehouseId)->orWhereNull('warehouse_id');
            }));

        if ($periodStart && $periodEnd) {
            $query->whereTransaksiDateBetween($periodStart, $periodEnd);
        }

        if ($request->filled('pelanggan')) {
            $term = $request->pelanggan;
            $query->whereHas('pelanggan', fn ($q) => $q->where('nama', 'like', '%'.$term.'%'));
        }

        if ($request->filled('no_hp')) {
            $term = $request->no_hp;
            $query->whereHas('pelanggan', fn ($q) => $q->where('no_hp', 'like', '%'.$term.'%'));
        }

        if (
            $request->filled('search')
            && ! $request->filled('pelanggan')
            && ! $request->filled('no_hp')
        ) {
            $s = $request->search;
            $query->whereHas('pelanggan', fn ($q) => $q->where('nama', 'like', "%{$s}%")
                ->orWhere('no_hp', 'like', "%{$s}%"));
        }

        $orders = $query->get();
        $byPelanggan = $orders->groupBy('pelanggan_id')->map(function ($group) {
            $pelanggan = $group->first()->pelanggan;
            $ordersSorted = $group->sortByDesc(fn ($o) => $o->tanggal_selesai ?? $o->tanggal_masuk)->values();

            return (object) [
                'pelanggan' => $pelanggan,
                'total_kunjungan' => $group->count(),
                'total_pengeluaran' => $group->sum(fn ($o) => $o->finalGrandTotal()),
                'orders' => $ordersSorted,
            ];
        })->values();

        $sort = $this->normalizeRecordSort(
            $request->get('sort'),
            'kunjungan_desc',
            ['kunjungan_desc', 'kunjungan_asc', 'pengeluaran_desc', 'pengeluaran_asc']
        );
        $byPelanggan = $this->sortPelangganRecordRows($byPelanggan, $sort);

        $summaryTotalPelanggan = $byPelanggan->count();
        $summaryTotalKunjungan = (int) $byPelanggan->sum(fn ($row) => $row->total_kunjungan);
        $summaryTotalPengeluaran = (int) $byPelanggan->sum(fn ($row) => $row->total_pengeluaran);

        $perPage = 20;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $byPelanggan = new LengthAwarePaginator(
            $byPelanggan->forPage($currentPage, $perPage)->values(),
            $byPelanggan->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('speedshop.service-record.index', compact(
            'byPelanggan',
            'sort',
            'serviceRecordAllSpeedshops',
            'mode',
            'selectedDate',
            'selectedMonth',
            'selectedYear',
            'selectedDari',
            'selectedSampai',
            'dari',
            'sampai',
            'summaryTotalPelanggan',
            'summaryTotalKunjungan',
            'summaryTotalPengeluaran',
        ));
    }

    public function serviceRecordPdf(Pelanggan $pelanggan)
    {
        $pelanggan = Pelanggan::query()->forSpeedshop()->findOrFail($pelanggan->id);
        $user = auth()->user();
        $speedshopIds = $user?->reportSpeedshopFilterIds() ?? [];
        $serviceRecordAllSpeedshops = $user?->isSpeedshopAllScopeSelected() ?? false;

        $query = ServiceOrder::with(['pelanggan', 'jasaServis', 'products', 'invoice', 'kendaraan', 'mekanik', 'speedshop'])
            ->where('status', 'selesai')
            ->where('pelanggan_id', $pelanggan->id)
            ->when(
                Schema::hasColumn('service_orders', 'speedshop_id') && $speedshopIds !== [],
                function ($q) use ($speedshopIds) {
                    if (count($speedshopIds) === 1) {
                        $q->where('speedshop_id', (int) $speedshopIds[0]);
                    } else {
                        $q->whereIn('speedshop_id', $speedshopIds);
                    }
                },
            )
            ->when($warehouseId = $this->warehouseId(), fn ($q) => $q->where(function ($q2) use ($warehouseId) {
                $q2->where('warehouse_id', $warehouseId)->orWhereNull('warehouse_id');
            }));

        $orders = $query->get()
            ->sortByDesc(fn ($o) => $o->tanggal_selesai ?? $o->tanggal_masuk)
            ->values();

        $printedAt = now();
        $filename = 'service-record-'.($pelanggan->nama ?: 'pelanggan').'-'.$printedAt->format('Y-m-d-His').'.pdf';

        return Pdf::loadView('speedshop.service-record.pdf', [
            'pelanggan' => $pelanggan,
            'orders' => $orders,
            'totalKunjungan' => $orders->count(),
            'totalPengeluaran' => (int) $orders->sum(fn ($o) => $o->finalGrandTotal()),
            'printedAt' => $printedAt,
            'serviceRecordAllSpeedshops' => $serviceRecordAllSpeedshops,
        ])->setPaper('a4', 'portrait')->stream($filename);
    }

    public function mekanikRecord(Request $request)
    {
        $user = auth()->user();
        $speedshopIds = $user?->reportSpeedshopFilterIds() ?? [];
        $mekanikRecordAllSpeedshops = $user?->isSpeedshopAllScopeSelected() ?? false;

        $period = $this->resolveWipPeriod($request);
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

        $query = ServiceOrder::with(['mekanik', 'pelanggan', 'kendaraan', 'invoice', 'speedshop'])
            ->where('status', 'selesai')
            ->whereNotNull('mekanik_id')
            ->when(
                Schema::hasColumn('service_orders', 'speedshop_id') && $speedshopIds !== [],
                function ($q) use ($speedshopIds) {
                    if (count($speedshopIds) === 1) {
                        $q->where('speedshop_id', (int) $speedshopIds[0]);
                    } else {
                        $q->whereIn('speedshop_id', $speedshopIds);
                    }
                },
            )
            ->when($warehouseId = $this->warehouseId(), fn ($q) => $q->where(function ($q2) use ($warehouseId) {
                $q2->where('warehouse_id', $warehouseId)->orWhereNull('warehouse_id');
            }));

        if ($periodStart && $periodEnd) {
            $query->whereTransaksiDateBetween($periodStart, $periodEnd);
        }

        if ($request->filled('mekanik')) {
            $term = $request->mekanik;
            $query->whereHas('mekanik', fn ($q) => $q->where('nama', 'like', '%'.$term.'%'));
        }

        if ($request->filled('no_hp')) {
            $term = $request->no_hp;
            $query->whereHas('mekanik', fn ($q) => $q->where('no_hp', 'like', '%'.$term.'%'));
        }

        if (
            $request->filled('search')
            && ! $request->filled('mekanik')
            && ! $request->filled('no_hp')
        ) {
            $s = $request->search;
            $query->whereHas('mekanik', fn ($q) => $q->where('nama', 'like', "%{$s}%")
                ->orWhere('no_hp', 'like', "%{$s}%"));
        }

        $orders = $query->get();
        $byMekanik = $orders->groupBy('mekanik_id')->map(function ($group) {
            $mekanik = $group->first()->mekanik;
            $ordersSorted = $group->sortByDesc(fn ($o) => $o->tanggal_selesai ?? $o->tanggal_masuk)->values();

            return (object) [
                'mekanik' => $mekanik,
                'total_work_order' => $group->count(),
                'total_pendapatan' => $group->sum(fn ($o) => $o->finalGrandTotal()),
                'orders' => $ordersSorted,
            ];
        })->values();

        $sort = $this->normalizeRecordSort(
            $request->get('sort'),
            'work_order_desc',
            ['work_order_desc', 'work_order_asc', 'pendapatan_desc', 'pendapatan_asc']
        );
        $byMekanik = $this->sortMekanikRecordRows($byMekanik, $sort);

        $summaryTotalMekanik = $byMekanik->count();
        $summaryTotalWorkOrder = (int) $byMekanik->sum(fn ($row) => $row->total_work_order);
        $summaryTotalPendapatan = (int) $byMekanik->sum(fn ($row) => $row->total_pendapatan);

        $perPage = 20;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $byMekanik = new LengthAwarePaginator(
            $byMekanik->forPage($currentPage, $perPage)->values(),
            $byMekanik->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('speedshop.mekanik-record.index', compact(
            'byMekanik',
            'sort',
            'mekanikRecordAllSpeedshops',
            'mode',
            'selectedDate',
            'selectedMonth',
            'selectedYear',
            'selectedDari',
            'selectedSampai',
            'dari',
            'sampai',
            'summaryTotalMekanik',
            'summaryTotalWorkOrder',
            'summaryTotalPendapatan',
        ));
    }

    public function estimasi()
    {
        if (auth()->user()?->isManager()) {
            abort(403, 'Estimasi Service tidak tersedia untuk akun Manager Speedshop.');
        }

        $pelanggans = Pelanggan::query()->forSpeedshop()->with(['kendaraans', 'kota'])->orderBy('nama')->get();
        $jasaServis = JasaServis::query()->orderBy('nama')->get();
        $warehouseId = $this->warehouseId();
        $speedshopId = auth()->user()->activeSpeedshopId();
        // Estimasi: gabungan 3 katalog inventori outlet (MO / Speedshop / Part Luar).
        $productsForEstimasi = $this->productsForWorkOrderSparepart($warehouseId, $speedshopId ? (int) $speedshopId : null);

        $initialPelanggans = $pelanggans->map(fn ($p) => [
            'id' => $p->id,
            'nama' => $p->nama,
            'no_hp' => $p->no_hp,
            'alamat' => $p->alamat,
            'kota' => $p->kota?->nama,
            'kendaraans' => $p->kendaraans->map(fn ($k) => [
                'id' => $k->id,
                'pelanggan_id' => $p->id,
                'nomor_polisi' => $k->nomor_polisi,
                'merk' => $k->merk,
                'tipe' => $k->tipe ?? '',
                'tahun' => $k->tahun,
                'last_kilometer' => ServiceOrder::latestKilometerForKendaraan((int) $k->id),
            ])->values()->all(),
        ])->values()->all();

        return view('speedshop.estimasi.create', compact('pelanggans', 'jasaServis', 'productsForEstimasi', 'initialPelanggans'));
    }

    public function create()
    {
        $pelanggans = Pelanggan::query()->forSpeedshop()->with('kendaraans')->orderBy('nama')->get();
        $jasaServis = JasaServis::query()->orderBy('nama')->get();

        $kendaraanIds = $pelanggans->flatMap(fn (Pelanggan $p) => $p->kendaraans->pluck('id'))->all();
        $latestKm = ServiceOrder::latestKilometersForKendaraanIds($kendaraanIds);

        $initialPelanggans = $pelanggans->map(fn ($p) => [
            'id' => $p->id,
            'nama' => $p->nama,
            'no_hp' => $p->no_hp,
            'kendaraans' => $p->kendaraans->map(fn ($k) => [
                'id' => $k->id,
                'pelanggan_id' => $p->id,
                'nomor_polisi' => $k->nomor_polisi,
                'merk' => $k->merk,
                'tipe' => $k->tipe,
                'tahun' => $k->tahun,
                'last_kilometer' => $latestKm[(int) $k->id] ?? null,
            ])->values()->all(),
        ])->values()->all();

        $invoiceSettings = app(InvoiceSettingsService::class)->get();

        return view('speedshop.work-order.create', compact('jasaServis', 'initialPelanggans', 'invoiceSettings'));
    }

    public function searchSparepartProducts(Request $request): JsonResponse
    {
        $search = trim((string) $request->get('q', ''));
        if ($search === '') {
            return response()->json([]);
        }

        $warehouseId = $this->warehouseId();
        $speedshopId = auth()->user()?->activeSpeedshopId();
        $items = $this->productsForWorkOrderSparepart(
            $warehouseId,
            $speedshopId ? (int) $speedshopId : null,
            $search,
            60,
        );

        return response()->json($items);
    }

    public function store(Request $request)
    {
        $tipe = $request->input('tipe_pemesanan', ServiceOrder::TIPE_SERVIS);
        if ($tipe === ServiceOrder::TIPE_PEMBELIAN_PART) {
            return $this->storePembelianPart($request);
        }

        return $this->storeServisWorkOrder($request);
    }

    private function storeServisWorkOrder(Request $request)
    {
        if (! $request->filled('kilometer')) {
            $request->merge(['kilometer' => null]);
        }

        $validated = $request->validate([
            'pelanggan_id' => ['required', 'exists:pelanggan,id'],
            'kendaraan_id' => ['required', 'exists:kendaraans,id'],
            'mekanik_id' => ['nullable', 'exists:mekaniks,id'],
            'sumber_kedatangan' => ['nullable', 'string', 'in:'.implode(',', array_keys(ServiceOrder::SUMBER_KEDATANGAN))],
            'kategori_service' => ['nullable', 'string', 'in:'.implode(',', array_keys(ServiceOrder::KATEGORI_SERVICE))],
            'keluhan' => ['nullable', 'string'],
            'estimasi_biaya' => ['nullable', 'numeric', 'min:0'],
            'tanggal_masuk' => ['required', 'date'],
            'kilometer' => ['nullable', 'integer', 'min:0'],
            // Jenis pembayaran ditentukan saat menyelesaikan transaksi, bukan saat membuat WO.
            'jenis_pembayaran' => ['nullable', 'string', 'in:cash,transfer,qris,edc'],
            'jasa' => ['required', 'array', 'min:1'],
            'jasa.*.jasa_servis_id' => ['required', 'exists:jasa_servis,id'],
            'jasa.*.qty' => ['required', 'integer', 'min:1'],
            'jasa.*.biaya' => ['required', 'numeric', 'min:0'],
            'sparepart' => ['nullable', 'array'],
            'sparepart.*.product_id' => ['required_with:sparepart', 'exists:products,id'],
            'sparepart.*.qty' => ['required_with:sparepart', 'integer', 'min:1'],
            'sparepart.*.harga' => ['required_with:sparepart', 'numeric', 'min:0'],
        ]);

        if (array_key_exists('kilometer', $validated) && $validated['kilometer'] !== null && $validated['kilometer'] !== '') {
            $lastKm = ServiceOrder::latestKilometerForKendaraan((int) $validated['kendaraan_id']);
            if ($lastKm !== null && (int) $validated['kilometer'] < (int) $lastKm) {
                throw ValidationException::withMessages([
                    'kilometer' => 'Kilometer tidak boleh lebih kecil dari kilometer terakhir ('.number_format($lastKm, 0, ',', '.').' km).',
                ]);
            }
        }

        $speedshopId = auth()->user()->activeSpeedshopId();
        if ($speedshopId && $request->filled('sparepart')) {
            foreach ($request->input('sparepart') as $sp) {
                $stok = SpeedshopStock::where('speedshop_id', $speedshopId)
                    ->where('product_id', $sp['product_id'])
                    ->value('jumlah') ?? 0;
                if ((int) $stok < (int) ($sp['qty'] ?? 0)) {
                    $product = Product::find($sp['product_id']);

                    return back()->withErrors(['sparepart' => 'Stok speedshop tidak mencukupi untuk '.($product->nama_produk ?? 'produk').'. Tersedia: '.(int) $stok])->withInput();
                }
            }
        }

        $validated['tipe_pemesanan'] = ServiceOrder::TIPE_SERVIS;
        $validated['jenis_pembayaran'] = null;
        $validated['kode_servis'] = ServiceOrder::generateNextKode(
            (int) $validated['pelanggan_id'],
            Carbon::parse($validated['tanggal_masuk']),
            auth()->user()?->activeSpeedshopId() ? (int) auth()->user()->activeSpeedshopId() : null
        );
        $validated['warehouse_id'] = $this->warehouseId();
        if (Schema::hasColumn('service_orders', 'speedshop_id')) {
            $validated['speedshop_id'] = auth()->user()?->activeSpeedshopId();
        }
        $validated['status'] = 'antri';

        $serviceOrder = ServiceOrder::create($validated);

        foreach ($request->input('jasa', []) as $jasa) {
            $serviceOrder->jasaServis()->attach($jasa['jasa_servis_id'], [
                'qty' => (int) ($jasa['qty'] ?? 1),
                'biaya' => (int) ($jasa['biaya'] ?? 0),
            ]);
        }

        foreach ($request->input('sparepart', []) as $sp) {
            $serviceOrder->products()->attach($sp['product_id'], [
                'qty' => $sp['qty'],
                'harga' => $sp['harga'],
            ]);
            if ($speedshopId) {
                $row = SpeedshopStock::firstOrCreate(
                    ['speedshop_id' => $speedshopId, 'product_id' => $sp['product_id']],
                    ['jumlah' => 0]
                );
                $row->decrement('jumlah', (int) $sp['qty']);
            }
        }

        session()->flash('success', 'Work Order berhasil dibuat.');
        $this->broadcastQueueUpdated();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'redirect' => route('speedshop.wip.show', $serviceOrder),
            ]);
        }

        return redirect()->route('speedshop.wip.show', $serviceOrder);
    }

    private function storePembelianPart(Request $request)
    {
        if (! $request->filled('pembayaran_mode')) {
            $request->merge(['pembayaran_mode' => 'single']);
        }

        $validated = $request->validate([
            'pelanggan_id' => ['required', 'exists:pelanggan,id'],
            'tanggal_masuk' => ['required', 'date'],
            'sumber_kedatangan' => ['required', 'string', 'in:'.implode(',', array_keys(ServiceOrder::SUMBER_KEDATANGAN))],
            'pembayaran_mode' => ['required', 'in:single,split'],
            'jenis_pembayaran' => ['required_if:pembayaran_mode,single', 'nullable', 'string', 'in:cash,transfer,qris,edc'],
            'catatan' => ['nullable', 'string', 'max:5000'],
            'sparepart' => ['required', 'array', 'min:1'],
            'sparepart.*.product_id' => ['required', 'exists:products,id'],
            'sparepart.*.qty' => ['required', 'integer', 'min:1'],
            'sparepart.*.harga' => ['required', 'numeric', 'min:0'],
            'sparepart.*.diskon' => ['nullable', 'integer', 'min:0'],
            'sparepart.*.catatan_diskon' => ['nullable', 'string', 'max:500'],
        ]);

        if (($request->input('pembayaran_mode') ?? 'single') === 'split') {
            $request->validate([
                'pembayaran' => ['required', 'array', 'min:2'],
                'pembayaran.*.metode' => ['nullable', 'string', 'in:cash,transfer,qris,edc'],
                'pembayaran.*.jumlah' => ['nullable', 'integer', 'min:0'],
            ]);
        }

        foreach ($validated['sparepart'] as $i => $sp) {
            $subtotal = (int) $sp['qty'] * (int) $sp['harga'];
            $diskon = max(0, (int) ($sp['diskon'] ?? 0));
            if ($diskon > $subtotal) {
                throw ValidationException::withMessages([
                    "sparepart.{$i}.diskon" => 'Diskon tidak boleh melebihi subtotal baris.',
                ]);
            }
            $validated['sparepart'][$i]['diskon'] = $diskon;
            $catatanDiskon = $this->normalizedCatatanDiskon($request->input("sparepart.{$i}.catatan_diskon"));
            $this->assertCatatanDiskonRequiredWhenDiskon($diskon, $catatanDiskon, "sparepart.{$i}.catatan_diskon");
            $validated['sparepart'][$i]['catatan_diskon'] = $catatanDiskon;
        }

        $speedshopId = auth()->user()->activeSpeedshopId();
        if ($speedshopId) {
            foreach ($validated['sparepart'] as $sp) {
                $stok = SpeedshopStock::where('speedshop_id', $speedshopId)
                    ->where('product_id', $sp['product_id'])
                    ->value('jumlah') ?? 0;
                if ((int) $stok < (int) ($sp['qty'] ?? 0)) {
                    $product = Product::find($sp['product_id']);

                    return back()->withErrors(['sparepart' => 'Stok speedshop tidak mencukupi untuk '.($product->nama_produk ?? 'produk').'. Tersedia: '.(int) $stok])->withInput();
                }
            }
        }

        $user = auth()->user();
        $tanggal = Carbon::parse($validated['tanggal_masuk'])->toDateString();
        $estimasi = (int) collect($validated['sparepart'])->sum(
            fn ($sp) => max(0, (int) $sp['qty'] * (int) $sp['harga'] - (int) ($sp['diskon'] ?? 0))
        );
        $catatan = isset($validated['catatan']) ? trim((string) $validated['catatan']) : '';
        $needManagerVerify = Schema::hasColumn('service_orders', 'diskon_verifikasi_status')
            && $this->sparepartRowsNeedManagerVerify($validated['sparepart']);
        $grandTotal = $this->invoiceGrandTotalFromBase($estimasi);
        $paymentPayload = $this->resolvePembayaranPayload($request, $grandTotal);

        $serviceOrder = ServiceOrder::create([
            'tipe_pemesanan' => ServiceOrder::TIPE_PEMBELIAN_PART,
            'kode_servis' => ServiceOrder::generateNextKode(
                (int) $validated['pelanggan_id'],
                Carbon::parse($validated['tanggal_masuk']),
                auth()->user()?->activeSpeedshopId() ? (int) auth()->user()->activeSpeedshopId() : null
            ),
            'warehouse_id' => $this->warehouseId(),
            'speedshop_id' => Schema::hasColumn('service_orders', 'speedshop_id') ? auth()->user()?->activeSpeedshopId() : null,
            'pelanggan_id' => (int) $validated['pelanggan_id'],
            'kendaraan_id' => null,
            'mekanik_id' => null,
            'sumber_kedatangan' => $validated['sumber_kedatangan'],
            'jenis_pembayaran' => $paymentPayload['jenis_pembayaran'],
            'pembayaran_split' => $paymentPayload['pembayaran_split'],
            'kategori_service' => null,
            'keluhan' => $catatan !== '' ? $catatan : null,
            'estimasi_biaya' => $estimasi,
            'status' => $needManagerVerify ? 'proses' : 'selesai',
            'tanggal_masuk' => $tanggal,
            'tanggal_selesai' => $needManagerVerify ? null : $tanggal,
            'kilometer' => null,
        ]);

        foreach ($validated['sparepart'] as $sp) {
            $serviceOrder->products()->attach($sp['product_id'], [
                'qty' => $sp['qty'],
                'harga' => $sp['harga'],
                'diskon' => (int) ($sp['diskon'] ?? 0),
                'catatan_diskon' => $sp['catatan_diskon'] ?? null,
            ]);
            if ($speedshopId) {
                $row = SpeedshopStock::firstOrCreate(
                    ['speedshop_id' => $speedshopId, 'product_id' => $sp['product_id']],
                    ['jumlah' => 0]
                );
                $row->decrement('jumlah', (int) $sp['qty']);
            }
        }

        $serviceOrder->refresh();
        $serviceOrder->load(['jasaServis', 'products']);
        $serviceOrder->update(['estimasi_biaya' => (int) $serviceOrder->grand_total]);

        if ($needManagerVerify) {
            $this->markDiskonVerifikasiPending(
                $serviceOrder,
                $user,
                $speedshopId ? (int) $speedshopId : null,
                'pembelian part'
            );

            $this->broadcastQueueUpdated();

            $msg = 'Diskon > 10% diajukan. Menunggu verifikasi Manager Speedshop sebelum invoice dibuat.';
            $redirect = $user->isManager()
                ? route('speedshop.verifikasi.diskon-work-order.show', $serviceOrder)
                : route('speedshop.dashboard');
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'redirect' => $redirect,
                ]);
            }

            return redirect($redirect)->with('success', $msg);
        }

        Invoice::createForCompletedServiceOrder(
            $serviceOrder,
            $paymentPayload['jenis_pembayaran'] ?: 'cash',
            $catatan !== '' ? $catatan : null,
        );

        $this->broadcastQueueUpdated();

        $redirect = route('speedshop.dashboard');
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'redirect' => $redirect,
            ]);
        }

        return redirect($redirect)->with('success', 'Pembelian part berhasil dicatat dan invoice telah dibuat.');
    }

    public function start(Request $request, ServiceOrder $service_order)
    {
        $this->assertServiceOrderSpeedshopAccess($service_order);
        if ($service_order->status !== 'antri') {
            return back()->with('error', 'Hanya work order dengan status Antri yang dapat diserahkan ke mekanik.');
        }

        $service_order->load('jasaServis');
        if ($service_order->jasaServis->isEmpty()) {
            throw ValidationException::withMessages([
                'jasa' => ['Tambahkan minimal satu jasa servis sebelum cetak Work Order.'],
            ]);
        }

        $validated = $request->validate([
            'mekanik_id' => ['required', 'exists:mekaniks,id'],
            'print_sheet' => ['nullable', 'boolean'],
            'from_wip' => ['nullable', 'boolean'],
        ]);

        // Mekanik boleh menangani lebih dari 1 WO "proses" (sesuai kebutuhan operasional).

        $service_order->update([
            'status' => 'proses',
            'mekanik_id' => $validated['mekanik_id'],
        ]);

        session()->flash('success', 'Work Order diserahkan ke mekanik dan status diubah ke Proses.');
        $this->broadcastQueueUpdated();

        $redirect = $request->boolean('from_wip')
            ? route('speedshop.wip.show', $service_order)
            : route('speedshop.selesaikan-transaksi.show', $service_order);
        // Default cetak pakai HTML (lebih cepat dibuka dibanding render PDF Dompdf).
        $printUrl = route('speedshop.work-orders.sheet.a4', $service_order);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'redirect' => $redirect,
                'open_print_url' => $request->boolean('print_sheet') ? $printUrl : null,
            ]);
        }

        // Non-AJAX: tetap redirect biasa. (Cetak otomatis hanya dijamin pada mode AJAX karena popup blocker.)
        return redirect($redirect);
    }

    public function complete(ServiceOrder $service_order)
    {
        $this->assertServiceOrderSpeedshopAccess($service_order);
        if ($service_order->status !== 'proses') {
            return back()->with('error', 'Hanya work order dengan status Proses yang dapat diubah ke Selesai.');
        }

        $service_order->update([
            'status' => 'selesai',
            'tanggal_selesai' => now(),
        ]);

        $service_order->refresh();
        $this->ensureInvoiceForCompletedWorkOrder($service_order);

        session()->flash('success', 'Work Order berhasil diselesaikan.');
        $this->broadcastQueueUpdated();

        return redirect()->back();
    }

    public function show(ServiceOrder $service_order)
    {
        $this->assertServiceOrderAccessible($service_order);

        $service_order->load([
            'pelanggan.kota',
            'kendaraan',
            'mekanik',
            'jasaServis',
            'products',
            'invoice',
            'batalVerifikasiRequestedBy',
            'batalVerifikasiVerifiedBy',
            'batalVerifikasiRejectedBy',
        ]);
        $speedshopId = $this->resolveSpeedshopIdForServiceOrder($service_order);
        $warehouseId = $service_order->warehouse_id
            ? (int) $service_order->warehouse_id
            : $this->warehouseId();
        $busyMekanikIds = $this->busyMekanikIds($warehouseId, $service_order->id);
        $mekaniks = Mekanik::aktifForSpeedshop($speedshopId ?? auth()->user()?->activeSpeedshopId())->get();

        // Untuk status Antri/Proses: izinkan tambah/hapus jasa & sparepart (UI sama seperti Selesaikan Transaksi)
        if (in_array($service_order->status, ['antri', 'proses'], true)) {
            $productsForForm = $this->productsForWorkOrderSparepart($warehouseId, $speedshopId ? (int) $speedshopId : null);

            $jasaServisOptions = JasaServis::query()
                ->orderBy('nama')
                ->get(['id', 'nama', 'biaya']);

            return view('speedshop.selesaikan-transaksi.show', [
                'service_order' => $service_order,
                'productsForForm' => $productsForForm,
                'jasaServisOptions' => $jasaServisOptions,
                'invoiceSettings' => app(InvoiceSettingsService::class)->get(),
                'context' => 'wip',
                'mekaniks' => $mekaniks,
                'busyMekanikIds' => $busyMekanikIds,
            ]);
        }

        return view('speedshop.work-order.show', compact('service_order', 'mekaniks', 'busyMekanikIds'));
    }

    public function wipUpdateItems(Request $request, ServiceOrder $service_order): RedirectResponse|JsonResponse
    {
        $this->assertServiceOrderSpeedshopAccess($service_order);
        if (! in_array($service_order->status, ['antri', 'proses'], true)) {
            $msg = 'Hanya work order berstatus Antri/Proses yang dapat diubah item-nya.';
            if ($request->wantsJson()) {
                return response()->json(['message' => $msg], 422);
            }

            return back()->with('error', $msg);
        }
        if (Schema::hasColumn('service_orders', 'batal_verifikasi_status')
            && ($service_order->batal_verifikasi_status ?? null) === 'pending') {
            $msg = 'Work order sedang menunggu verifikasi pembatalan Accounting.';
            if ($request->wantsJson()) {
                return response()->json(['message' => $msg], 422);
            }

            return back()->with('error', $msg);
        }

        $service_order->load(['jasaServis', 'products']);
        $jasaIds = $service_order->jasaServis->pluck('id')->toArray();
        $productIds = $service_order->products->pluck('id')->toArray();

        [$removeJasaIdsReq, $removeSparepartIdsReq] = $this->normalizeSelesaikanItemPayload(
            $request,
            $service_order,
            $jasaIds,
            $productIds
        );

        $rules = [];
        $rules['remove_jasa_ids'] = ['nullable', 'array'];
        $rules['remove_jasa_ids.*'] = ['integer', 'exists:jasa_servis,id'];
        $rules['add_jasa'] = ['nullable', 'array'];
        $rules['add_jasa.*.jasa_servis_id'] = ['required', 'integer', 'exists:jasa_servis,id'];
        $rules['add_jasa.*.qty'] = ['required', 'integer', 'min:1'];
        $rules['add_jasa.*.diskon'] = ['nullable', 'numeric', 'min:0'];
        $rules['add_jasa.*.catatan_diskon'] = ['nullable', 'string', 'max:500'];
        $rules['remove_sparepart_ids'] = ['nullable', 'array'];
        $rules['remove_sparepart_ids.*'] = ['integer', 'exists:products,id'];
        $rules['add_sparepart'] = ['nullable', 'array'];
        $rules['add_sparepart.*.product_id'] = ['required', 'integer', 'exists:products,id'];
        $rules['add_sparepart.*.qty'] = ['required', 'integer', 'min:1'];
        $rules['add_sparepart.*.diskon'] = ['nullable', 'numeric', 'min:0'];
        $rules['add_sparepart.*.catatan_diskon'] = ['nullable', 'string', 'max:500'];
        // Flow WIP (awal): saat masih Antri, submit utama adalah "Cetak Work Order" → wajib pilih mekanik.
        if ($service_order->status === 'antri') {
            $rules['mekanik_id'] = ['required', 'exists:mekaniks,id'];
            $rules['print_sheet'] = ['nullable', 'boolean'];
        } else {
            $rules['mekanik_id'] = ['nullable', 'exists:mekaniks,id'];
            $rules['print_sheet'] = ['nullable', 'boolean'];
        }

        foreach ($jasaIds as $id) {
            if (in_array((int) $id, $removeJasaIdsReq, true)) {
                continue;
            }
            $rules["jasa.{$id}.qty"] = ['required', 'integer', 'min:1'];
            $rules["jasa.{$id}.diskon"] = ['nullable', 'numeric', 'min:0'];
            $rules["jasa.{$id}.catatan_diskon"] = ['nullable', 'string', 'max:500'];
        }
        foreach ($productIds as $id) {
            if (in_array((int) $id, $removeSparepartIdsReq, true)) {
                continue;
            }
            $rules["sparepart.{$id}.qty"] = ['required', 'integer', 'min:1'];
            $rules["sparepart.{$id}.diskon"] = ['nullable', 'numeric', 'min:0'];
            $rules["sparepart.{$id}.catatan_diskon"] = ['nullable', 'string', 'max:500'];
        }

        $validated = $request->validate($rules);

        // Reuse logic selesaikanStore: detach + sync qty/diskon.
        // Catatan: WIP TIDAK mengurangi stok. Stok baru berkurang saat WO diselesaikan.
        $removeJasaIds = collect($request->input('remove_jasa_ids', []))
            ->map(fn ($id) => (int) $id)
            ->intersect($jasaIds)
            ->values()
            ->all();

        $addJasaRows = collect((array) $request->input('add_jasa', []))
            ->map(fn ($row) => [
                'jasa_servis_id' => (int) ($row['jasa_servis_id'] ?? 0),
                'qty' => (int) ($row['qty'] ?? 0),
                'diskon' => (int) ($row['diskon'] ?? 0),
            ])
            ->filter(fn ($row) => $row['jasa_servis_id'] > 0 && $row['qty'] > 0)
            ->values();

        $targetJasaQty = [];
        $existingJasaPivots = $service_order->jasaServis()->get()->keyBy('id');
        foreach ($existingJasaPivots as $jasaId => $jasa) {
            if (in_array((int) $jasaId, $removeJasaIds, true)) {
                continue;
            }
            $targetJasaQty[(int) $jasaId] = (int) ($validated['jasa'][(int) $jasaId]['qty'] ?? ($jasa->pivot->qty ?? 1));
        }
        foreach ($addJasaRows as $row) {
            $jid = (int) ($row['jasa_servis_id'] ?? 0);
            $qty = (int) ($row['qty'] ?? 0);
            if ($jid <= 0 || $qty <= 0) {
                continue;
            }
            $targetJasaQty[$jid] = (int) ($targetJasaQty[$jid] ?? 0) + $qty;
        }

        if ($service_order->status === 'antri' && empty($targetJasaQty)) {
            throw ValidationException::withMessages([
                'jasa' => ['Tambahkan minimal satu jasa servis sebelum cetak Work Order.'],
            ]);
        }

        if (! empty($removeJasaIds)) {
            $service_order->jasaServis()->detach($removeJasaIds);
        }

        $addJasaDiskonById = $addJasaRows->keyBy('jasa_servis_id');
        $currentJasaIds = array_keys($targetJasaQty);
        if (! empty($currentJasaIds)) {
            $jasaMasterById = JasaServis::whereIn('id', $currentJasaIds)->get()->keyBy('id');
            foreach ($currentJasaIds as $jasaId) {
                $jasaMaster = $jasaMasterById->get($jasaId);
                if (! $jasaMaster) {
                    continue;
                }
                $existingPivot = $existingJasaPivots->get($jasaId)?->pivot;
                $qty = (int) ($targetJasaQty[$jasaId] ?? 1);
                $biaya = (int) ($existingPivot?->biaya ?? $jasaMaster->biaya ?? 0);
                $baseTotal = max(0, $qty * $biaya);
                $diskon = (int) ($request->input("jasa.{$jasaId}.diskon") ?? $addJasaDiskonById->get((int) $jasaId)['diskon'] ?? $existingPivot?->diskon ?? 0);
                $diskon = max(0, min($diskon, $baseTotal));

                $service_order->jasaServis()->syncWithoutDetaching([
                    $jasaId => [
                        'qty' => $qty,
                        'biaya' => $biaya,
                        'diskon' => $diskon,
                        'catatan_diskon' => $this->resolveCatatanDiskonForPivot($request, 'jasa', (int) $jasaId, $existingPivot?->catatan_diskon),
                        'diskon_verified_by' => null,
                        'diskon_verified_at' => null,
                    ],
                ]);
            }
        }

        $removeSparepartIds = collect($request->input('remove_sparepart_ids', []))
            ->map(fn ($id) => (int) $id)
            ->intersect($productIds)
            ->values()
            ->all();

        $targetQty = [];
        $existingProducts = $service_order->products()->get()->keyBy('id');
        foreach ($existingProducts as $productId => $product) {
            if (in_array((int) $productId, $removeSparepartIds, true)) {
                continue;
            }
            $targetQty[(int) $productId] = (int) ($validated['sparepart'][(int) $productId]['qty'] ?? $product->pivot->qty);
        }

        $addSparepartRows = collect((array) $request->input('add_sparepart', []))
            ->map(fn ($row) => [
                'product_id' => (int) ($row['product_id'] ?? 0),
                'qty' => (int) ($row['qty'] ?? 0),
                'diskon' => (int) ($row['diskon'] ?? 0),
            ])
            ->filter(fn ($row) => $row['product_id'] > 0 && $row['qty'] > 0)
            ->values();

        foreach ($addSparepartRows as $row) {
            $pid = (int) ($row['product_id'] ?? 0);
            $qty = (int) ($row['qty'] ?? 0);
            if ($pid <= 0 || $qty <= 0) {
                continue;
            }
            $targetQty[$pid] = (int) ($targetQty[$pid] ?? 0) + $qty;
        }

        foreach ($targetQty as $pid => $newQty) {
            $product = Product::find($pid);
            if (! $product) {
                continue;
            }

            $existingPivot = $existingProducts->get($pid)?->pivot;
            $oldQty = (int) ($existingPivot?->qty ?? 0);
            $oldHarga = (int) ($existingPivot?->harga ?? 0);
            $harga = $oldHarga > 0 ? $oldHarga : $product->hargaJualCustomer();
            $service_order->products()->syncWithoutDetaching([
                $pid => [
                    'qty' => $newQty,
                    'harga' => $harga,
                    'diskon' => (int) ($request->input("sparepart.{$pid}.diskon") ?? ($existingPivot?->diskon ?? 0)),
                    'catatan_diskon' => $this->resolveCatatanDiskonForPivot($request, 'sparepart', (int) $pid, $existingPivot?->catatan_diskon),
                    'diskon_verified_by' => null,
                    'diskon_verified_at' => null,
                ],
            ]);
        }

        $currentProductIds = array_keys($targetQty);
        $deleteProductIds = collect($productIds)
            ->reject(fn ($id) => in_array((int) $id, $currentProductIds, true))
            ->values()
            ->all();
        if (! empty($deleteProductIds)) {
            $service_order->products()->detach($deleteProductIds);
        }

        // Jika status masih Antri, maka submit = serahkan ke mekanik + cetak sheet (flow awal).
        if ($service_order->status === 'antri') {
            $service_order->update([
                'status' => 'proses',
                'mekanik_id' => (int) $validated['mekanik_id'],
            ]);
            session()->flash('success', 'Work Order diserahkan ke mekanik dan status diubah ke Proses.');
            $this->broadcastQueueUpdated();

            $printUrl = route('speedshop.work-orders.sheet.a4', $service_order);
            $afterRedirect = route('speedshop.selesaikan-transaksi.show', $service_order);
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Work Order diproses. Membuka cetak Work Order…',
                    'redirect' => $afterRedirect,
                    'open_print_url' => $request->boolean('print_sheet', true) ? $printUrl : null,
                ]);
            }

            // Non-AJAX: redirect biasa.
            return redirect()->to($afterRedirect);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Item jasa & sparepart berhasil diperbarui.',
                'redirect' => route('speedshop.wip.show', $service_order),
            ]);
        }

        return redirect()->route('speedshop.wip.show', $service_order)->with('success', 'Item jasa & sparepart berhasil diperbarui.');
    }

    public function invoice(ServiceOrder $service_order)
    {
        if ($redirect = $this->redirectUnlessInvoiceAllowed($service_order)) {
            return $redirect;
        }
        $service_order->load(['pelanggan.kota', 'kendaraan', 'mekanik', 'jasaServis', 'products', 'invoice']);

        return view('speedshop.work-order.invoice', compact('service_order'));
    }

    /**
     * Cetak struk thermal 58mm (buka tab lalu Print / pilih kertas 58mm).
     */
    public function invoiceThermal(ServiceOrder $service_order)
    {
        if ($redirect = $this->redirectUnlessInvoiceAllowed($service_order)) {
            return $redirect;
        }
        $service_order->load(['pelanggan.speedshop.kota', 'kendaraan', 'mekanik', 'jasaServis', 'products', 'invoice']);
        $outlet = $this->outletForWorkOrderInvoice($service_order);

        return view('speedshop.work-order.invoice-thermal', compact('service_order', 'outlet'));
    }

    /**
     * PDF A4 lembar kerja WO (layout sama dengan invoice A4) untuk diserahkan ke mekanik.
     * Dapat diakses saat status antri/proses/selesai (belum memerlukan invoice final).
     */
    public function workOrderSheetPdfA4(ServiceOrder $service_order)
    {
        $this->assertServiceOrderSpeedshopAccess($service_order);
        if (! in_array($service_order->status, ['antri', 'proses', 'selesai'], true)) {
            abort(404);
        }

        // Performa: render Dompdf cukup berat (terutama watermark). Cache hasil PDF per versi WO
        // agar akses ulang (mis. buka/tab print ulang) jauh lebih cepat.
        $kodeFile = preg_replace('/[^\w\-]+/u', '_', (string) $service_order->kode_servis);
        $filename = 'work-order-'.$kodeFile.'-'.now()->format('Ymd-His').'.pdf';

        $tplPath = resource_path('views/speedshop/work-order/invoice-a4-pdf.blade.php');
        $tplSig = @md5_file($tplPath);
        // Fallback: minimal invalidasi harian kalau file tidak bisa dibaca (env tertentu).
        $tplSig = $tplSig ?: ('na'.now()->format('Ymd'));
        $ver = $service_order->updated_at
            ? \Illuminate\Support\Carbon::parse($service_order->updated_at)->format('YmdHis')
            : now()->format('YmdHis');
        $cacheDir = storage_path('app/pdf-cache/work-order-sheet');
        if (! is_dir($cacheDir)) {
            @mkdir($cacheDir, 0775, true);
        }
        $cachePath = $cacheDir.'/wo-sheet-'.$service_order->id.'-'.$ver.'-tpl'.$tplSig.'.pdf';

        if (is_file($cachePath)) {
            return response()->file($cachePath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
                'Cache-Control' => 'private, max-age=60',
            ]);
        }

        $binary = $this->makeInvoiceA4PdfBuilder($service_order, pdfMode: 'sheet')->output();
        @file_put_contents($cachePath, $binary);

        // Bersihkan cache versi lama untuk WO ini (biar tidak numpuk).
        foreach (glob($cacheDir.'/wo-sheet-'.$service_order->id.'-*.pdf') ?: [] as $old) {
            if ($old !== $cachePath) {
                @unlink($old);
            }
        }

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
        ]);
    }

    /**
     * Lembar kerja WO A4 berbasis HTML (cepat dibuka, print via browser).
     */
    public function workOrderSheetA4Html(ServiceOrder $service_order)
    {
        $this->assertServiceOrderSpeedshopAccess($service_order);
        if (! in_array($service_order->status, ['antri', 'proses', 'selesai'], true)) {
            abort(404);
        }

        $service_order->load(['pelanggan.speedshop.kota', 'kendaraan', 'mekanik', 'jasaServis', 'products', 'invoice']);
        $outlet = $this->outletForWorkOrderInvoice($service_order);

        return view('speedshop.work-order.sheet-a4-html', compact('service_order', 'outlet'));
    }

    /**
     * Invoice A4 (PDF) untuk dibagikan lewat WhatsApp atau arsip.
     */
    public function invoicePdfA4(ServiceOrder $service_order)
    {
        if ($redirect = $this->redirectUnlessInvoiceAllowed($service_order)) {
            return $redirect;
        }
        $printedAt = now();
        $kodeFile = preg_replace('/[^\w\-]+/u', '_', (string) ($service_order->invoice?->kode_invoice ?? $service_order->kode_servis));
        $filename = 'invoice-'.$kodeFile.'-'.$printedAt->format('Ymd-His').'.pdf';

        return $this->makeInvoiceA4PdfBuilder($service_order, pdfMode: 'invoice')->download($filename);
    }

    /**
     * Unduhan PDF bertanda tangan untuk WACLOUD (tanpa login; validasi signature + expires).
     */
    public function invoicePdfA4Signed(ServiceOrder $service_order)
    {
        if ($service_order->status !== 'selesai' || ! $service_order->invoice) {
            abort(404);
        }

        // Untuk WACLOUD/WhatsApp: pakai layout seperti WO sheet, tanpa area analisis & TTD.
        // Cache karena endpoint ini bisa di-hit berkali-kali oleh WA client / preview / retry download.
        $invVer = $service_order->invoice?->updated_at
            ? \Illuminate\Support\Carbon::parse($service_order->invoice->updated_at)->format('YmdHis')
            : now()->format('YmdHis');
        $soVer = $service_order->updated_at
            ? \Illuminate\Support\Carbon::parse($service_order->updated_at)->format('YmdHis')
            : now()->format('YmdHis');
        $tplPath = resource_path('views/speedshop/work-order/invoice-a4-pdf.blade.php');
        $tplSig = @md5_file($tplPath);
        $tplSig = $tplSig ?: ('na'.now()->format('Ymd'));
        $cacheDir = storage_path('app/pdf-cache/invoice-wa');
        if (! is_dir($cacheDir)) {
            @mkdir($cacheDir, 0775, true);
        }
        $cachePath = $cacheDir.'/invoice-wa-'.$service_order->id.'-'.$invVer.'-'.$soVer.'-tpl'.$tplSig.'.pdf';

        if (is_file($cachePath)) {
            return response()->file($cachePath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="invoice.pdf"',
                'Cache-Control' => 'private, max-age=120',
            ]);
        }

        $binary = $this->makeInvoiceA4PdfBuilder($service_order, pdfMode: 'wa_invoice')->output();
        @file_put_contents($cachePath, $binary);

        foreach (glob($cacheDir.'/invoice-wa-'.$service_order->id.'-*.pdf') ?: [] as $old) {
            if ($old !== $cachePath) {
                @unlink($old);
            }
        }

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="invoice.pdf"',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
        ]);
    }

    /**
     * Kirim invoice PDF ke nomor pelanggan lewat WACLOUD.
     */
    public function sendInvoiceWhatsApp(Request $request, ServiceOrder $service_order, WacloudMessageService $wacloud)
    {
        if ($redirect = $this->redirectUnlessInvoiceAllowed($service_order)) {
            return $redirect;
        }
        $service_order->loadMissing(['pelanggan', 'kendaraan', 'mekanik', 'jasaServis', 'products', 'invoice']);
        if (! $service_order->invoice) {
            return redirect()->route('speedshop.wip.show', $service_order)->with('error', 'Invoice belum tersedia.');
        }

        $to = $this->normalizeIndonesiaWhatsappNumber($service_order->pelanggan?->no_hp ?? '');
        if ($to === null) {
            return redirect()->route('speedshop.wip.show', $service_order)->with('error', 'Nomor HP pelanggan tidak valid untuk WhatsApp.');
        }

        if (! $wacloud->isConfigured()) {
            return redirect()->route('speedshop.wip.show', $service_order)->with('error', 'WACLOUD belum dikonfigurasi: isi WACLOUD_API_KEY dan device ID outlet (Bandung/Depok) di file .env.');
        }

        $appUrl = rtrim((string) config('app.url'), '/');
        if ($appUrl === '' || str_starts_with($appUrl, 'http://localhost') || str_starts_with($appUrl, 'http://127.0.0.1')) {
            return redirect()->route('speedshop.wip.show', $service_order)->with('error', 'APP_URL harus URL publik (HTTPS) agar server WACLOUD dapat mengunduh PDF invoice.');
        }

        $signedPdfUrl = URL::temporarySignedRoute(
            'speedshop.invoice.a4.signed',
            now()->addMinutes(45),
            ['service_order' => $service_order]
        );

        $inv = (string) ($service_order->invoice->kode_invoice ?? $service_order->kode_servis);
        $filename = 'Invoice-'.preg_replace('/[^\w\-\.]+/u', '_', $inv).'.pdf';
        $outlet = $this->outletForWorkOrderInvoice($service_order);
        $caption = $this->buildInvoiceWhatsAppCaption($service_order, $outlet);

        $result = $wacloud->sendDocument($to, $signedPdfUrl, $filename, $caption);
        if (! ($result['ok'] ?? false)) {
            return redirect()->route('speedshop.wip.show', $service_order)->with('error', $result['message'] ?? 'Gagal mengirim pesan WhatsApp.');
        }

        return redirect()->route('speedshop.wip.show', $service_order)->with('success', 'Ringkasan invoice (struk) dan PDF telah dikirim ke WhatsApp pelanggan.');
    }

    private function makeInvoiceA4PdfBuilder(ServiceOrder $service_order, string $pdfMode = 'invoice')
    {
        $service_order->load(['pelanggan.speedshop.kota', 'kendaraan', 'mekanik', 'jasaServis', 'products', 'invoice']);
        $outlet = $this->outletForWorkOrderInvoice($service_order);

        $pdf = Pdf::loadView('speedshop.work-order.invoice-a4-pdf', [
            'service_order' => $service_order,
            'outlet' => $outlet,
            'printedAt' => now(),
            'pdfMode' => $pdfMode,
        ])->setPaper('a4', 'portrait');

        // Performa: WO sheet dan WA invoice memakai watermark besar (rotate) yang cukup berat di Dompdf.
        // Menurunkan DPI mempercepat render dengan kualitas yang masih oke untuk cetak A4.
        if (in_array($pdfMode, ['sheet', 'wa_invoice'], true)) {
            $pdf->setOption('dpi', 72);
        }

        return $pdf;
    }

    private function normalizeIndonesiaWhatsappNumber(string $raw): ?string
    {
        $digits = preg_replace('/\D/', '', $raw) ?? '';
        if ($digits === '') {
            return null;
        }
        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif (! str_starts_with($digits, '62')) {
            $digits = '62'.$digits;
        }
        if (strlen($digits) < 11) {
            return null;
        }

        return $digits;
    }

    /**
     * Baris teks "Bayar (...)" untuk caption WhatsApp (split bill = beberapa baris).
     *
     * @return list<string>
     */
    private function invoiceCaptionBayarLines(ServiceOrder $serviceOrder): array
    {
        $serviceOrder->loadMissing('invoice');
        $inv = $serviceOrder->invoice;
        $grand = (int) ($inv?->grand_total ?? $serviceOrder->grand_total);
        $metRaw = strtolower((string) ($inv?->metode_pembayaran ?? 'cash'));
        $split = $inv?->pembayaran_split;
        if (is_string($split)) {
            $decoded = json_decode($split, true);
            $split = is_array($decoded) ? $decoded : null;
        }
        if ($metRaw === 'split' && is_array($split) && $split !== []) {
            $out = [];
            foreach ($split as $row) {
                $m = strtolower(trim((string) ($row['metode'] ?? '')));
                $amt = (int) ($row['jumlah'] ?? 0);
                if ($amt <= 0) {
                    continue;
                }
                $metLabel = match ($m) {
                    'cash', 'tunai' => 'Tunai',
                    'transfer' => 'Transfer',
                    'qris' => 'QRIS',
                    'edc' => 'EDC',
                    default => $m !== '' ? ucfirst($m) : '-',
                };
                $out[] = 'Bayar ('.$metLabel.') : '.$this->formatRupiahId($amt);
            }

            return $out !== [] ? $out : ['Bayar (Split bill) : '.$this->formatRupiahId($grand)];
        }

        $metLabel = match ($metRaw) {
            'cash', 'tunai' => 'Tunai',
            'transfer' => 'Transfer',
            'qris' => 'QRIS',
            'edc' => 'EDC',
            default => $metRaw !== '' ? ucfirst($metRaw) : 'Tunai',
        };

        return ['Bayar ('.$metLabel.') : '.$this->formatRupiahId($grand)];
    }

    /**
     * Teks struk untuk caption WhatsApp (PDF tetap dilampirkan). Mirip layout struk: header toko, data pelanggan, tabel item, ringkasan bayar.
     * Dibatasi agar aman untuk batas panjang caption API (~1024 karakter).
     */
    private function buildInvoiceWhatsAppCaption(ServiceOrder $serviceOrder, ?Speedshop $outlet): string
    {
        $serviceOrder->loadMissing(['pelanggan', 'kendaraan', 'mekanik', 'jasaServis', 'products', 'invoice']);
        $inv = $serviceOrder->invoice;
        $outlet?->loadMissing('kota');

        $namaToko = mb_strtoupper(trim((string) ($outlet?->nama ?: '77racing')));
        $hpToko = trim((string) ($outlet?->no_hp ?? ''));
        $alamat = trim(implode(' ', array_filter([
            trim((string) ($outlet?->alamat ?? '')),
            trim((string) ($outlet?->kota?->nama ?? '')),
        ])));

        $sep = str_repeat('·', 18);
        $lines = [];
        $lines[] = '*'.$namaToko.'*';
        if ($hpToko !== '') {
            $lines[] = 'No. HP/Telp.: '.$hpToko;
        }
        if ($alamat !== '') {
            $lines[] = 'Alamat: '.$alamat;
        }
        $lines[] = $sep;

        $noInvoice = (string) ($inv?->kode_invoice ?? ('WO-'.$serviceOrder->kode_servis));
        $tgl = $serviceOrder->tanggal_selesai ?? $inv?->tanggal ?? $serviceOrder->tanggal_masuk;
        $tglStr = $tgl ? Carbon::parse($tgl)->format('d/m/Y') : '-';
        $namaPel = trim((string) ($serviceOrder->pelanggan?->nama ?? '-'));
        $hpPel = trim((string) ($serviceOrder->pelanggan?->no_hp ?? '-'));
        if ($hpPel === '') {
            $hpPel = '-';
        }
        $namaMek = trim((string) ($serviceOrder->mekanik?->nama ?? '-'));
        $catInv = trim((string) ($inv?->catatan ?? ''));
        $catTampil = $catInv !== '' ? $catInv : '-';

        $lines[] = 'Customer : '.$namaPel;
        $lines[] = 'No. Invoice : '.$noInvoice;
        $lines[] = 'Nama Mekanik : '.$namaMek;
        $lines[] = 'No. HP : '.$hpPel;
        $lines[] = 'Tgl. : '.$tglStr;
        $lines[] = 'Catatan : '.$catTampil;
        $lines[] = $sep;

        // Item list dipisah seperti struk thermal: PEKERJAAN dan SPAREPART + total per section.
        $rows = [];
        $no = 0;
        $totalJasa = 0;
        $totalPart = 0;

        $rows[] = '*PEKERJAAN*';
        $rows[] = 'Item | Qty | Total';
        if ($serviceOrder->jasaServis->isEmpty()) {
            $rows[] = '1. - | 0 | 0';
        } else {
            foreach ($serviceOrder->jasaServis as $j) {
                $q = (int) ($j->pivot->qty ?? 1);
                $b = (int) ($j->pivot->biaya ?? 0);
                $d = (int) ($j->pivot->diskon ?? 0);
                $lineTotal = max(0, ($q * $b) - $d);
                $totalJasa += $lineTotal;
                $no++;
                $nm = $this->truncateForInvoiceCaption((string) $j->nama, 34);
                $suffix = $d > 0 ? ' (disc '.$this->formatRupiahId($d).')' : '';
                $rows[] = $no.'. '.$nm.' | '.$q.' | '.$this->formatRupiahId($lineTotal).$suffix;
            }
        }
        $rows[] = 'TOTAL PEKERJAAN : '.$this->formatRupiahId($totalJasa);
        $rows[] = $sep;

        $rows[] = '*SPAREPART*';
        $rows[] = 'Item | Qty | Total';
        if ($serviceOrder->products->isEmpty()) {
            $rows[] = '1. - | 0 | 0';
        } else {
            $noPart = 0;
            foreach ($serviceOrder->products as $p) {
                $q = (int) ($p->pivot->qty ?? 0);
                $h = (int) ($p->pivot->harga ?? 0);
                $d = (int) ($p->pivot->diskon ?? 0);
                $lineTotal = max(0, ($q * $h) - $d);
                $totalPart += $lineTotal;
                $noPart++;
                $nm = $this->truncateForInvoiceCaption((string) $p->nama_produk, 34);
                $suffix = $d > 0 ? ' (disc '.$this->formatRupiahId($d).')' : '';
                $rows[] = $noPart.'. '.$nm.' | '.$q.' | '.$this->formatRupiahId($lineTotal).$suffix;
            }
        }
        $rows[] = 'TOTAL SPAREPART : '.$this->formatRupiahId($totalPart);
        $rows[] = $sep;

        // Batasi jumlah baris agar aman untuk caption WhatsApp.
        $maxRows = 26;
        if (count($rows) > $maxRows) {
            $more = count($rows) - $maxRows + 1;
            $rows = array_slice($rows, 0, $maxRows - 1);
            $rows[] = '… +'.$more.' baris (lihat PDF)';
        }
        foreach ($rows as $r) {
            $lines[] = $r;
        }

        $diskon = (int) ($inv?->diskon ?? $serviceOrder->total_diskon);
        $subtotalBefore = (int) ($serviceOrder->subtotal_sebelum_diskon ?? ($serviceOrder->grand_total + $serviceOrder->total_diskon));
        $grand = (int) ($inv?->grand_total ?? $serviceOrder->grand_total);
        $baseGrand = (int) $serviceOrder->grand_total;
        $chargeAmt = $inv
            ? InvoiceSettingsService::resolveChargeAmountForInvoice($inv, $baseGrand)
            : 0;
        $ppnAmt = (int) ($inv?->ppn_amount ?? 0);
        $chargeMode = (string) ($inv?->charge_mode ?? '');
        $ppnMode = (string) ($inv?->ppn_mode ?? '');
        $chargePct = (float) ($inv?->charge_percent ?? 0);
        $ppnPct = (float) ($inv?->ppn_percent ?? 0);

        if ($diskon > 0) {
            $lines[] = 'Subtotal : '.$this->formatRupiahId($subtotalBefore);
            $lines[] = 'Diskon : '.$this->formatRupiahId($diskon);
        }
        if ($chargeAmt > 0) {
            $lines[] = $chargeMode === 'percent' && $chargePct > 0
                ? 'Charge ('.$this->formatPercentId($chargePct).') : '.$this->formatRupiahId($chargeAmt)
                : 'Charge : '.$this->formatRupiahId($chargeAmt);
        }
        if ($ppnAmt > 0) {
            $lines[] = $ppnMode === 'percent' && $ppnPct > 0
                ? 'PPN ('.$this->formatPercentId($ppnPct).') : '.$this->formatRupiahId($ppnAmt)
                : 'PPN : '.$this->formatRupiahId($ppnAmt);
        }
        $lines[] = 'Total : '.$this->formatRupiahId($grand);
        foreach ($this->invoiceCaptionBayarLines($serviceOrder) as $bayarLine) {
            $lines[] = $bayarLine;
        }
        $lines[] = 'Kembalian : '.$this->formatRupiahId(0);
        $lines[] = $sep;
        $lines[] = 'GARANSI SERVIS 1 MINGGU, TUNJUKKAN INVOICE INI UNTUK MENDAPATKAN DISKON SERVIS SELANJUTNYA RP.12.500,-';

        $text = implode("\n", $lines);
        $maxLen = 1000;
        if (mb_strlen($text) > $maxLen) {
            return $this->buildInvoiceWhatsAppCaptionCompact($serviceOrder, $outlet, $maxLen);
        }

        return $text;
    }

    private function buildInvoiceWhatsAppCaptionCompact(ServiceOrder $serviceOrder, ?Speedshop $outlet, int $maxLen): string
    {
        $serviceOrder->loadMissing(['pelanggan', 'mekanik', 'jasaServis', 'products', 'invoice']);
        $inv = $serviceOrder->invoice;
        $namaToko = mb_strtoupper(trim((string) ($outlet?->nama ?: '77racing')));
        $hpToko = trim((string) ($outlet?->no_hp ?? ''));
        $outlet?->loadMissing('kota');
        $alamat = trim(implode(' ', array_filter([
            trim((string) ($outlet?->alamat ?? '')),
            trim((string) ($outlet?->kota?->nama ?? '')),
        ])));
        $noInvoice = (string) ($inv?->kode_invoice ?? ('WO-'.$serviceOrder->kode_servis));
        $grand = (int) ($inv?->grand_total ?? $serviceOrder->grand_total);
        $totalJasa = 0;
        foreach ($serviceOrder->jasaServis as $j) {
            $q = (int) ($j->pivot->qty ?? 1);
            $b = (int) ($j->pivot->biaya ?? 0);
            $d = (int) ($j->pivot->diskon ?? 0);
            $totalJasa += max(0, ($q * $b) - $d);
        }
        $totalPart = 0;
        foreach ($serviceOrder->products as $p) {
            $q = (int) ($p->pivot->qty ?? 0);
            $h = (int) ($p->pivot->harga ?? 0);
            $d = (int) ($p->pivot->diskon ?? 0);
            $totalPart += max(0, ($q * $h) - $d);
        }
        $lines = [];
        $lines[] = '*'.$namaToko.'*';
        if ($hpToko !== '') {
            $lines[] = 'No. HP/Telp.: '.$hpToko;
        }
        if ($alamat !== '') {
            $lines[] = 'Alamat: '.$alamat;
        }
        $lines[] = str_repeat('·', 14);
        $lines[] = 'No. Invoice: '.$noInvoice;
        $lines[] = 'Customer: '.trim((string) ($serviceOrder->pelanggan?->nama ?? '-'));
        $lines[] = 'Total Pekerjaan: '.$this->formatRupiahId($totalJasa);
        $lines[] = 'Total Sparepart: '.$this->formatRupiahId($totalPart);
        $lines[] = 'Total: '.$this->formatRupiahId($grand);
        foreach ($this->invoiceCaptionBayarLines($serviceOrder) as $bayarLine) {
            $lines[] = $bayarLine;
        }
        $lines[] = str_repeat('·', 14);
        $lines[] = 'Daftar item terlalu panjang untuk caption — lihat lampiran PDF.';

        return mb_substr(implode("\n", $lines), 0, $maxLen);
    }

    private function formatRupiahId(int $amount): string
    {
        return number_format($amount, 0, ',', '.');
    }

    private function formatPercentId(float $pct): string
    {
        $s = number_format($pct, 2, '.', '');
        $s = rtrim(rtrim($s, '0'), '.');

        return $s.'%';
    }

    private function truncateForInvoiceCaption(string $text, int $maxChars): string
    {
        $t = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
        if (mb_strlen($t) <= $maxChars) {
            return $t;
        }

        return rtrim(mb_substr($t, 0, max(1, $maxChars - 1))).'…';
    }

    private function redirectUnlessInvoiceAllowed(ServiceOrder $service_order): ?\Illuminate\Http\RedirectResponse
    {
        $this->assertServiceOrderSpeedshopAccess($service_order);
        if ($service_order->status !== 'selesai') {
            return redirect()->route('speedshop.wip.show', $service_order)->with('error', 'Invoice hanya untuk work order yang sudah selesai.');
        }

        return null;
    }

    private function outletForWorkOrderInvoice(ServiceOrder $service_order): ?Speedshop
    {
        $service_order->loadMissing('pelanggan.speedshop');
        if ($service_order->pelanggan?->speedshop) {
            return $service_order->pelanggan->speedshop;
        }
        // Untuk kasus signed URL (WACLOUD) tidak ada auth user, jadi fallback ke speedshop_id pada service order.
        if (
            $service_order->getAttribute('speedshop_id') !== null
            && Schema::hasColumn('service_orders', 'speedshop_id')
        ) {
            $ssid = (int) $service_order->getAttribute('speedshop_id');
            if ($ssid > 0) {
                $soOutlet = Speedshop::query()->find($ssid);
                if ($soOutlet) {
                    return $soOutlet;
                }
            }
        }
        $ssid = auth()->user()?->activeSpeedshopId();

        return $ssid ? Speedshop::query()->find($ssid) : null;
    }

    public function selesaikanIndex(Request $request)
    {
        if ($request->boolean('verifikasi_diskon')) {
            if (auth()->user()?->isManager()) {
                return redirect()->route('speedshop.verifikasi.diskon-work-order.index', $request->except('verifikasi_diskon'));
            }

            return redirect()->route('speedshop.selesaikan-transaksi.index', $request->except('verifikasi_diskon'));
        }

        $period = $this->resolveWipPeriod($request);
        $baseQuery = $this->selesaikanTransaksiIndexBaseQuery(false, $period['dari'], $period['sampai']);
        $selesaikanSummaryProses = (clone $baseQuery)->count();
        $selesaikanSummaryEstimasi = (int) (clone $baseQuery)->sum('estimasi_biaya');

        $orders = $this->selesaikanTransaksiIndexApplyFilters(clone $baseQuery, $request, false)
            ->latest('tanggal_masuk')
            ->paginate(20)
            ->withQueryString();

        return view('speedshop.selesaikan-transaksi.index', [
            'orders' => $orders,
            'verifikasiDiskonWorkOrder' => false,
            'listAllSpeedshops' => auth()->user()?->isSpeedshopAllScopeSelected() ?? false,
            'selesaikanSummaryProses' => $selesaikanSummaryProses,
            'selesaikanSummaryEstimasi' => $selesaikanSummaryEstimasi,
            'mode' => $period['mode'],
            'selectedDate' => $period['selectedDate'],
            'selectedMonth' => $period['selectedMonth'],
            'selectedYear' => $period['selectedYear'],
            'selectedDari' => $period['selectedDari'],
            'selectedSampai' => $period['selectedSampai'],
            'dari' => $period['dari'],
            'sampai' => $period['sampai'],
        ]);
    }

    public function selesaikanIndexDiskonVerifikasi(Request $request)
    {
        if (! Schema::hasColumn('service_orders', 'diskon_verifikasi_status')) {
            return redirect()
                ->route('speedshop.selesaikan-transaksi.index')
                ->with('error', 'Fitur verifikasi diskon tidak tersedia.');
        }

        $speedshopIds = $this->diskonVerifikasiSpeedshopFilterIds();
        $period = $this->resolveWipPeriod($request, 'bulanan');
        $baseQuery = $this->selesaikanTransaksiIndexBaseQuery(true, $period['dari'], $period['sampai']);
        $totalAll = (clone $baseQuery)->count();
        $totalPending = (clone $baseQuery)->where('diskon_verifikasi_status', 'pending')->count();
        $totalApproved = (clone $baseQuery)->where('diskon_verifikasi_status', 'approved')->count();
        $totalRejected = (clone $baseQuery)->where('diskon_verifikasi_status', 'rejected')->count();

        $orders = $this->selesaikanTransaksiIndexApplyFilters(clone $baseQuery, $request, true)
            ->orderByRaw("CASE diskon_verifikasi_status WHEN 'pending' THEN 0 WHEN 'approved' THEN 1 WHEN 'rejected' THEN 2 ELSE 3 END")
            ->orderByDesc('diskon_verifikasi_requested_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('speedshop.selesaikan-transaksi.index', [
            'orders' => $orders,
            'verifikasiDiskonWorkOrder' => true,
            'listAllSpeedshops' => auth()->user()?->isSpeedshopAllScopeSelected()
                || count($speedshopIds) > 1,
            'totalAll' => $totalAll,
            'totalPending' => $totalPending,
            'totalApproved' => $totalApproved,
            'totalRejected' => $totalRejected,
            'mode' => $period['mode'],
            'selectedDate' => $period['selectedDate'],
            'selectedMonth' => $period['selectedMonth'],
            'selectedYear' => $period['selectedYear'],
            'selectedDari' => $period['selectedDari'],
            'selectedSampai' => $period['selectedSampai'],
            'dari' => $period['dari'],
            'sampai' => $period['sampai'],
        ]);
    }

    /**
     * @param  list<int>  $speedshopIds
     * @return \Illuminate\Database\Eloquent\Builder<ServiceOrder>
     */
    private function diskonVerifikasiBaseQuery(array $speedshopIds): \Illuminate\Database\Eloquent\Builder
    {
        $query = ServiceOrder::query();
        $this->applyDiskonVerifikasiSpeedshopFilter($query, $speedshopIds);

        return $query->whereNotNull('diskon_verifikasi_status');
    }

    /**
     * Outlet untuk halaman verifikasi diskon Manager: cabang speedshop akun tersebut.
     *
     * @return list<int>
     */
    private function diskonVerifikasiSpeedshopFilterIds(): array
    {
        $user = auth()->user();
        if (! $user || ! $user->isManager()) {
            return [];
        }

        return $user->reportSpeedshopFilterIds();
    }

    /** @return list<int> */
    private function diskonVerifikasiNotifiedServiceOrderIds(?User $user = null): array
    {
        $user = $user ?? auth()->user();
        if (! $user) {
            return [];
        }

        return AppNotification::query()
            ->where('user_id', $user->id)
            ->where('type', 'speedshop_diskon_verifikasi_pending')
            ->orderByDesc('id')
            ->get(['data'])
            ->map(fn (AppNotification $n) => is_array($n->data) ? ($n->data['service_order_id'] ?? null) : null)
            ->filter(fn ($id) => $id !== null && (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function managerHasDiskonVerifikasiNotification(User $user, ServiceOrder $service_order): bool
    {
        return AppNotification::query()
            ->where('user_id', $user->id)
            ->where('type', 'speedshop_diskon_verifikasi_pending')
            ->where('data->service_order_id', $service_order->id)
            ->exists();
    }

    private function assertDiskonVerifikasiViewable(ServiceOrder $service_order): void
    {
        $user = auth()->user();
        if (! $user?->isManager()) {
            abort(403, 'Hanya Manager Speedshop yang dapat mengakses verifikasi diskon.');
        }

        if (! Schema::hasColumn('service_orders', 'diskon_verifikasi_status')) {
            abort(403, 'Fitur verifikasi diskon tidak tersedia.');
        }

        if (! in_array($service_order->diskon_verifikasi_status, ['pending', 'approved', 'rejected'], true)) {
            abort(403, 'Work order ini tidak memiliki riwayat verifikasi diskon.');
        }

        if ($this->managerHasDiskonVerifikasiNotification($user, $service_order)) {
            return;
        }

        $speedshopIds = $this->diskonVerifikasiSpeedshopFilterIds();
        if ($this->resolveDiskonVerifikasiSpeedshopId($service_order, $speedshopIds) !== null) {
            return;
        }

        $service_order->loadMissing('diskonVerifikasiRequestedBy');
        $requester = $service_order->diskonVerifikasiRequestedBy;
        if ($requester && $user->warehouse_id) {
            if ((int) ($requester->warehouse_id ?? 0) === (int) $user->warehouse_id) {
                return;
            }
            $requesterOutlet = (int) ($requester->speedshop_id ?? 0);
            if ($requesterOutlet > 0 && in_array($requesterOutlet, $speedshopIds, true)) {
                return;
            }
        }

        if ($service_order->speedshop_id) {
            $outletManagers = User::speedshopManagersForOutlet((int) $service_order->speedshop_id);
            if ($outletManagers->contains('id', $user->id)) {
                return;
            }
        }

        abort(403, 'Akses ditolak.');
    }

    private function lockServiceOrderForDiskonVerifikasi(ServiceOrder $service_order): ServiceOrder
    {
        /** @var ServiceOrder $locked */
        $locked = ServiceOrder::query()->whereKey($service_order->id)->lockForUpdate()->firstOrFail();

        return $locked;
    }

    private function diskonVerifikasiShowUrl(ServiceOrder $service_order): string
    {
        return route('speedshop.verifikasi.diskon-work-order.show', $service_order);
    }

    /** @return array{message: string, already_processed: bool, status: ?string, redirect: string} */
    private function diskonVerifikasiAlreadyProcessedPayload(ServiceOrder $service_order): array
    {
        $status = $service_order->diskon_verifikasi_status;
        $label = match ($status) {
            'approved' => 'sudah disetujui',
            'rejected' => 'sudah ditolak',
            default => 'sudah tidak menunggu persetujuan',
        };

        return [
            'message' => 'Pengajuan diskon '.$label.'. Halaman akan diperbarui.',
            'already_processed' => true,
            'status' => is_string($status) && $status !== '' ? $status : null,
            'redirect' => $this->diskonVerifikasiShowUrl($service_order),
        ];
    }

    private function respondDiskonVerifikasiConflict(ServiceOrder $service_order, string $message): JsonResponse|RedirectResponse
    {
        $payload = $this->diskonVerifikasiAlreadyProcessedPayload($service_order);
        $payload['message'] = $message;

        if (request()->expectsJson() || request()->wantsJson() || request()->ajax()) {
            return response()->json($payload, 409);
        }

        return redirect($payload['redirect'])->with('error', $message);
    }

    /**
     * Cabang WO untuk verifikasi diskon: kolom speedshop_id atau outlet pengaju (pola Pembelian Luar).
     *
     * @param  list<int>|null  $allowedSpeedshopIds  Bila diisi, prioritaskan outlet yang termasuk scope manager.
     */
    private function resolveDiskonVerifikasiSpeedshopId(ServiceOrder $service_order, ?array $allowedSpeedshopIds = null): ?int
    {
        $candidates = [];

        if (Schema::hasColumn('service_orders', 'speedshop_id') && $service_order->speedshop_id) {
            $candidates[] = (int) $service_order->speedshop_id;
        }

        $service_order->loadMissing('diskonVerifikasiRequestedBy');
        $requesterOutletId = (int) ($service_order->diskonVerifikasiRequestedBy?->speedshop_id ?? 0);
        if ($requesterOutletId > 0) {
            $candidates[] = $requesterOutletId;
        }

        $candidates = array_values(array_unique($candidates));
        if ($candidates === []) {
            return null;
        }

        if ($allowedSpeedshopIds !== null && $allowedSpeedshopIds !== []) {
            foreach ($candidates as $candidate) {
                if (in_array($candidate, $allowedSpeedshopIds, true)) {
                    return $candidate;
                }
            }

            return null;
        }

        return $candidates[0];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<ServiceOrder>  $query
     * @param  list<int>  $speedshopIds
     */
    private function applyDiskonVerifikasiSpeedshopFilter($query, array $speedshopIds): void
    {
        $user = auth()->user();
        $notifiedIds = $this->diskonVerifikasiNotifiedServiceOrderIds($user);

        if ($speedshopIds === [] && $notifiedIds === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(function ($outer) use ($speedshopIds, $notifiedIds, $user) {
            if ($speedshopIds !== []) {
                $outer->where(function ($match) use ($speedshopIds) {
                    if (Schema::hasColumn('service_orders', 'speedshop_id')) {
                        if (count($speedshopIds) === 1) {
                            $match->where('speedshop_id', (int) $speedshopIds[0]);
                        } else {
                            $match->whereIn('speedshop_id', $speedshopIds);
                        }
                    }

                    $match->orWhereHas('diskonVerifikasiRequestedBy', function ($q) use ($speedshopIds) {
                        $q->where('section', 'speedshop');
                        if (count($speedshopIds) === 1) {
                            $q->where('speedshop_id', (int) $speedshopIds[0]);
                        } else {
                            $q->whereIn('speedshop_id', $speedshopIds);
                        }
                    });
                });
            }

            if ($notifiedIds !== []) {
                $outer->orWhereIn('id', $notifiedIds);
            }

            if ($speedshopIds === [] && $user?->warehouse_id) {
                $outer->orWhereHas('diskonVerifikasiRequestedBy', function ($q) use ($user) {
                    $q->where('section', 'speedshop')
                        ->where('warehouse_id', (int) $user->warehouse_id);
                });
            }
        });
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<ServiceOrder>  $query
     */
    private function applyReportSpeedshopFilterToQuery($query): void
    {
        $speedshopIds = auth()->user()?->reportSpeedshopFilterIds() ?? [];

        $query->when(
            Schema::hasColumn('service_orders', 'speedshop_id') && $speedshopIds !== [],
            function ($q) use ($speedshopIds) {
                if (count($speedshopIds) === 1) {
                    $q->where('speedshop_id', (int) $speedshopIds[0]);
                } else {
                    $q->whereIn('speedshop_id', $speedshopIds);
                }
            },
        );
    }

    private function assertServiceOrderAccessible(ServiceOrder $service_order): void
    {
        $user = auth()->user();
        $pendingDiskon = Schema::hasColumn('service_orders', 'diskon_verifikasi_status')
            && $service_order->diskon_verifikasi_status === 'pending';

        if ($pendingDiskon && $user && $user->isManager()) {
            $this->assertDiskonVerifikasiViewable($service_order);

            return;
        }

        $this->assertServiceOrderSpeedshopAccess($service_order);
    }

    private function resolveSpeedshopIdForServiceOrder(ServiceOrder $service_order): ?int
    {
        $outletId = $this->resolveDiskonVerifikasiSpeedshopId($service_order);
        if ($outletId !== null) {
            return $outletId;
        }

        $active = auth()->user()?->activeSpeedshopId();

        return $active ? (int) $active : null;
    }

    /**
     * @param  bool  $diskonVerifikasiPage  Halaman verifikasi diskon Manager (semua riwayat pending/approved/rejected).
     * @return \Illuminate\Database\Eloquent\Builder<ServiceOrder>
     */
    private function selesaikanTransaksiIndexBaseQuery(bool $diskonVerifikasiPage, Carbon $dari, Carbon $sampai): \Illuminate\Database\Eloquent\Builder
    {
        $query = ServiceOrder::with(['pelanggan', 'kendaraan', 'mekanik', 'speedshop', 'diskonVerifikasiRequestedBy']);

        if ($diskonVerifikasiPage) {
            $this->applyDiskonVerifikasiSpeedshopFilter($query, $this->diskonVerifikasiSpeedshopFilterIds());
            $query->whereNotNull('diskon_verifikasi_status')
                ->where(function ($q) use ($dari, $sampai) {
                    $q->where(function ($q2) use ($dari, $sampai) {
                        $q2->whereNotNull('diskon_verifikasi_requested_at')
                            ->whereDate('diskon_verifikasi_requested_at', '>=', $dari->toDateString())
                            ->whereDate('diskon_verifikasi_requested_at', '<=', $sampai->toDateString());
                    })->orWhere(function ($q2) use ($dari, $sampai) {
                        $q2->whereNull('diskon_verifikasi_requested_at')
                            ->whereDate('tanggal_masuk', '>=', $dari->toDateString())
                            ->whereDate('tanggal_masuk', '<=', $sampai->toDateString());
                    });
                });

            return $query;
        }

        $query->where('status', 'proses');
        $this->applyReportSpeedshopFilterToQuery($query);
        $query->when($warehouseId = $this->warehouseId(), fn ($q) => $q->where(function ($q2) use ($warehouseId) {
            $q2->where('warehouse_id', $warehouseId)->orWhereNull('warehouse_id');
        }));

        return $query->where(function ($q) use ($dari, $sampai) {
            $q->where(function ($q2) use ($dari, $sampai) {
                $q2->whereDate('tanggal_masuk', '>=', $dari->toDateString())
                    ->whereDate('tanggal_masuk', '<=', $sampai->toDateString());
            })->orWhere(function ($q2) use ($dari) {
                $q2->where('status', 'proses')
                    ->whereDate('tanggal_masuk', '<', $dari->toDateString());
            });
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<ServiceOrder>
     */
    private function selesaikanTransaksiIndexApplyFilters(
        \Illuminate\Database\Eloquent\Builder $query,
        Request $request,
        bool $diskonVerifikasiPage,
    ): \Illuminate\Database\Eloquent\Builder {
        if ($diskonVerifikasiPage && $request->filled('status') && in_array($request->status, ['pending', 'approved', 'rejected'], true)) {
            $query->where('diskon_verifikasi_status', $request->status);
        }

        if ($request->filled('kode')) {
            $query->where('kode_servis', 'like', '%'.$request->kode.'%');
        }

        if ($request->filled('pelanggan')) {
            $term = $request->pelanggan;
            $query->whereHas('pelanggan', fn ($q) => $q->where('nama', 'like', '%'.$term.'%'));
        }

        if ($request->filled('no_polisi')) {
            $term = $request->no_polisi;
            $query->whereHas('kendaraan', fn ($q) => $q->where('nomor_polisi', 'like', '%'.$term.'%'));
        }

        if ($request->filled('mekanik')) {
            $term = $request->mekanik;
            $query->whereHas('mekanik', fn ($q) => $q->where('nama', 'like', '%'.$term.'%'));
        }

        if (
            $request->filled('search')
            && ! $request->filled('kode')
            && ! $request->filled('pelanggan')
            && ! $request->filled('no_polisi')
            && ! $request->filled('mekanik')
        ) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('kode_servis', 'like', "%{$s}%")
                    ->orWhereHas('pelanggan', fn ($q) => $q->where('nama', 'like', "%{$s}%"))
                    ->orWhereHas('kendaraan', fn ($q) => $q->where('nomor_polisi', 'like', "%{$s}%"));
            });
        }

        return $query;
    }

    public function diskonVerifikasiShow(ServiceOrder $service_order)
    {
        $this->assertDiskonVerifikasiViewable($service_order);

        return $this->renderSelesaikanShow($service_order, true);
    }

    public function selesaikanShow(ServiceOrder $service_order)
    {
        if (
            Schema::hasColumn('service_orders', 'diskon_verifikasi_status')
            && in_array($service_order->diskon_verifikasi_status, ['pending', 'approved', 'rejected'], true)
            && auth()->user()?->isManager()
        ) {
            return redirect()->route('speedshop.verifikasi.diskon-work-order.show', $service_order);
        }

        $this->assertServiceOrderAccessible($service_order);

        return $this->renderSelesaikanShow($service_order, false);
    }

    private function renderSelesaikanShow(ServiceOrder $service_order, bool $diskonVerifikasiManagerPage): \Illuminate\View\View|\Illuminate\Http\RedirectResponse
    {
        $pendingDiskon = Schema::hasColumn('service_orders', 'diskon_verifikasi_status')
            && $service_order->diskon_verifikasi_status === 'pending';
        $managerViewingPendingDiskon = $pendingDiskon
            && auth()->user()?->isManager();

        if (! $diskonVerifikasiManagerPage && $service_order->status !== 'proses' && ! $managerViewingPendingDiskon) {
            // Notifikasi / tautan lama bisa mengarah ke sini setelah WO sudah selesai (approve diskon) atau masih antri.
            return match ($service_order->status) {
                'selesai' => redirect()
                    ->route('speedshop.wip.show', $service_order)
                    ->with('success', 'Work order ini sudah diselesaikan. Verifikasi diskon tidak lagi diperlukan.'),
                'antri' => redirect()
                    ->route('speedshop.wip.show', $service_order)
                    ->with('info', 'Work order masih antrian. Halaman selesaikan transaksi tersedia setelah status menjadi Proses (sedang dikerjakan).'),
                'dibatalkan' => redirect()
                    ->route('speedshop.wip.show', $service_order)
                    ->with('info', 'Work order ini sudah dibatalkan.'),
                default => redirect()
                    ->route('speedshop.wip')
                    ->with('error', 'Hanya work order dengan status Proses yang dapat diselesaikan di sini.'),
            };
        }

        $relations = ['pelanggan.kota', 'kendaraan', 'mekanik', 'jasaServis', 'products'];
        if ($diskonVerifikasiManagerPage) {
            $relations[] = 'diskonVerifikasiRequestedBy';
            $relations[] = 'diskonVerifikasiVerifiedBy';
            $relations[] = 'diskonVerifikasiRejectedBy';
        }
        $service_order->load($relations);

        $warehouseId = $this->warehouseId();
        $speedshopId = $this->resolveSpeedshopIdForServiceOrder($service_order);
        $productsForForm = $this->productsForWorkOrderSparepart($warehouseId, $speedshopId ? (int) $speedshopId : null);

        $jasaServisOptions = JasaServis::query()
            ->orderBy('nama')
            ->get(['id', 'nama', 'biaya']);

        return view('speedshop.selesaikan-transaksi.show', [
            'service_order' => $service_order,
            'productsForForm' => $productsForForm,
            'jasaServisOptions' => $jasaServisOptions,
            'invoiceSettings' => app(InvoiceSettingsService::class)->get(),
            'diskonVerifikasiManagerPage' => $diskonVerifikasiManagerPage,
        ]);
    }

    public function selesaikanSparepartStock(Request $request, ServiceOrder $service_order): JsonResponse
    {
        $this->assertServiceOrderAccessible($service_order);

        $speedshopId = $this->resolveSpeedshopIdForServiceOrder($service_order);
        if (! $speedshopId) {
            return response()->json(['message' => 'Cabang speedshop work order tidak terdeteksi.'], 422);
        }

        $validated = $request->validate([
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['integer'],
        ]);

        $ids = collect($validated['product_ids'])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values();

        if ($ids->isEmpty()) {
            return response()->json(['stocks' => []]);
        }

        $stocks = SpeedshopStock::query()
            ->where('speedshop_id', (int) $speedshopId)
            ->whereIn('product_id', $ids->all())
            ->pluck('jumlah', 'product_id')
            ->map(fn ($v) => (int) $v)
            ->all();

        return response()->json(['stocks' => $stocks]);
    }

    public function selesaikanJasaOptions(Request $request, ServiceOrder $service_order): JsonResponse
    {
        $this->assertServiceOrderAccessible($service_order);

        $query = JasaServis::query()->orderBy('nama');

        $items = $query->get(['id', 'nama', 'biaya'])->map(fn (JasaServis $j) => [
            'id' => (int) $j->id,
            'nama' => (string) $j->nama,
            'biaya' => (int) $j->biaya,
        ])->values()->all();

        return response()->json(['items' => $items]);
    }

    public function selesaikanStore(Request $request, ServiceOrder $service_order)
    {
        $this->assertServiceOrderAccessible($service_order);
        if ($service_order->status !== 'proses') {
            $msg = 'Hanya work order dengan status Proses yang dapat diselesaikan.';
            if ($request->wantsJson()) {
                return response()->json(['message' => $msg], 422);
            }

            return back()->with('error', $msg);
        }

        $user = auth()->user();
        $isManager = $user->isManager();

        $service_order->load(['jasaServis', 'products']);
        $jasaIds = $service_order->jasaServis->pluck('id')->toArray();
        $productIds = $service_order->products->pluck('id')->toArray();

        // Catatan: validasi qty/diskon untuk item existing harus mengabaikan item yang ditandai "hapus".
        // Kalau tidak, ketika user klik "Hapus" (row hilang dari DOM), field sparepart.{id}.qty akan hilang
        // sehingga validasi "required" akan gagal dan halaman terlihat seperti refresh.
        [$removeJasaIdsReq, $removeSparepartIdsReq] = $this->normalizeSelesaikanItemPayload(
            $request,
            $service_order,
            $jasaIds,
            $productIds
        );

        $rules = [];
        $rules['remove_jasa_ids'] = ['nullable', 'array'];
        $rules['remove_jasa_ids.*'] = ['integer', 'exists:jasa_servis,id'];
        $rules['add_jasa'] = ['nullable', 'array'];
        $rules['add_jasa.*.jasa_servis_id'] = ['required', 'integer', 'exists:jasa_servis,id'];
        $rules['add_jasa.*.qty'] = ['required', 'integer', 'min:1'];
        $rules['add_jasa.*.diskon'] = ['nullable', 'numeric', 'min:0'];
        $rules['add_jasa.*.catatan_diskon'] = ['nullable', 'string', 'max:500'];
        $rules['remove_sparepart_ids'] = ['nullable', 'array'];
        $rules['remove_sparepart_ids.*'] = ['integer', 'exists:products,id'];
        $rules['add_sparepart'] = ['nullable', 'array'];
        $rules['add_sparepart.*.product_id'] = ['required', 'integer', 'exists:products,id'];
        $rules['add_sparepart.*.qty'] = ['required', 'integer', 'min:1'];
        $rules['add_sparepart.*.diskon'] = ['nullable', 'numeric', 'min:0'];
        $rules['add_sparepart.*.catatan_diskon'] = ['nullable', 'string', 'max:500'];
        if (! $request->filled('pembayaran_mode')) {
            $request->merge(['pembayaran_mode' => 'single']);
        }
        $rules['pembayaran_mode'] = ['required', 'in:single,split'];
        $rules['jenis_pembayaran'] = ['required_if:pembayaran_mode,single', 'nullable', 'string', 'in:cash,transfer,qris,edc'];
        if (($request->input('pembayaran_mode') ?? 'single') === 'split') {
            $rules['pembayaran'] = ['required', 'array', 'min:2'];
            $rules['pembayaran.*.metode'] = ['nullable', 'string', 'in:cash,transfer,qris,edc'];
            $rules['pembayaran.*.jumlah'] = ['nullable', 'integer', 'min:0'];
        }

        foreach ($jasaIds as $i => $id) {
            if (in_array((int) $id, $removeJasaIdsReq, true)) {
                continue;
            }
            $rules["jasa.{$id}.qty"] = ['required', 'integer', 'min:1'];
            $rules["jasa.{$id}.diskon"] = ['nullable', 'numeric', 'min:0'];
            $rules["jasa.{$id}.catatan_diskon"] = ['nullable', 'string', 'max:500'];
        }
        foreach ($productIds as $i => $id) {
            if (in_array((int) $id, $removeSparepartIdsReq, true)) {
                continue;
            }
            $rules["sparepart.{$id}.qty"] = ['required', 'integer', 'min:1'];
            $rules["sparepart.{$id}.diskon"] = ['nullable', 'numeric', 'min:0'];
            $rules["sparepart.{$id}.catatan_diskon"] = ['nullable', 'string', 'max:500'];
        }
        $validated = $request->validate($rules);

        $removeJasaIds = collect($request->input('remove_jasa_ids', []))
            ->map(fn ($id) => (int) $id)
            ->intersect($jasaIds)
            ->values()
            ->all();
        if (! empty($removeJasaIds)) {
            $service_order->jasaServis()->detach($removeJasaIds);
        }

        $existingJasaIds = $service_order->jasaServis()->pluck('jasa_servis.id')->map(fn ($id) => (int) $id)->all();
        $addJasaRows = collect((array) $request->input('add_jasa', []))
            ->map(function ($row) {
                return [
                    'jasa_servis_id' => (int) ($row['jasa_servis_id'] ?? 0),
                    'qty' => (int) ($row['qty'] ?? 0),
                    'diskon' => (int) ($row['diskon'] ?? 0),
                ];
            })
            ->filter(fn ($row) => $row['jasa_servis_id'] > 0 && $row['qty'] > 0)
            ->values();

        $targetJasaQty = [];
        $existingJasaPivots = $service_order->jasaServis()->get()->keyBy('id');
        foreach ($existingJasaPivots as $jasaId => $jasa) {
            if (in_array((int) $jasaId, $removeJasaIds, true)) {
                continue;
            }
            $targetJasaQty[(int) $jasaId] = (int) ($validated['jasa'][(int) $jasaId]['qty'] ?? ($jasa->pivot->qty ?? 1));
        }

        foreach ($addJasaRows as $row) {
            $jid = (int) ($row['jasa_servis_id'] ?? 0);
            $qty = (int) ($row['qty'] ?? 0);
            if ($jid <= 0 || $qty <= 0) {
                continue;
            }
            $targetJasaQty[$jid] = (int) ($targetJasaQty[$jid] ?? 0) + $qty;
        }

        $addJasaDiskonById = $addJasaRows->keyBy('jasa_servis_id');
        $currentJasaIds = array_keys($targetJasaQty);
        if (! empty($currentJasaIds)) {
            $jasaMasterById = JasaServis::whereIn('id', $currentJasaIds)->get()->keyBy('id');
            foreach ($currentJasaIds as $jasaId) {
                $jasaMaster = $jasaMasterById->get($jasaId);
                if (! $jasaMaster) {
                    continue;
                }
                $existingPivot = $existingJasaPivots->get($jasaId)?->pivot;
                $qty = (int) ($targetJasaQty[$jasaId] ?? 1);
                $biaya = (int) ($existingPivot?->biaya ?? $jasaMaster->biaya ?? 0);
                $baseTotal = max(0, $qty * $biaya);
                $diskon = (int) ($request->input("jasa.{$jasaId}.diskon") ?? $addJasaDiskonById->get((int) $jasaId)['diskon'] ?? $existingPivot?->diskon ?? 0);
                $diskon = max(0, min($diskon, $baseTotal));

                $service_order->jasaServis()->syncWithoutDetaching([
                    $jasaId => [
                        'qty' => $qty,
                        'biaya' => $biaya,
                        'diskon' => $diskon,
                        'catatan_diskon' => $this->resolveCatatanDiskonForPivot($request, 'jasa', (int) $jasaId, $existingPivot?->catatan_diskon),
                        'diskon_verified_by' => null,
                        'diskon_verified_at' => null,
                    ],
                ]);
            }
        }

        $deleteJasaIds = collect($jasaIds)
            ->reject(fn ($id) => in_array((int) $id, $currentJasaIds, true))
            ->values()
            ->all();
        if (! empty($deleteJasaIds)) {
            $service_order->jasaServis()->detach($deleteJasaIds);
        }

        $removeSparepartIds = collect($request->input('remove_sparepart_ids', []))
            ->map(fn ($id) => (int) $id)
            ->intersect($productIds)
            ->values()
            ->all();

        $targetQty = [];
        $existingProducts = $service_order->products()->get()->keyBy('id');
        foreach ($existingProducts as $productId => $product) {
            if (in_array((int) $productId, $removeSparepartIds, true)) {
                continue;
            }
            $targetQty[(int) $productId] = (int) ($validated['sparepart'][(int) $productId]['qty'] ?? $product->pivot->qty);
        }

        $addSparepartRows = collect((array) $request->input('add_sparepart', []))
            ->map(function ($row) {
                return [
                    'product_id' => (int) ($row['product_id'] ?? 0),
                    'qty' => (int) ($row['qty'] ?? 0),
                    'diskon' => (int) ($row['diskon'] ?? 0),
                ];
            })
            ->filter(fn ($row) => $row['product_id'] > 0 && $row['qty'] > 0)
            ->values();

        foreach ($addSparepartRows as $row) {
            $pid = (int) ($row['product_id'] ?? 0);
            $qty = (int) ($row['qty'] ?? 0);
            if ($pid <= 0 || $qty <= 0) {
                continue;
            }
            $targetQty[$pid] = (int) ($targetQty[$pid] ?? 0) + $qty;
        }

        $speedshopId = $this->resolveSpeedshopIdForServiceOrder($service_order);
        if ($speedshopId) {
            foreach ($targetQty as $pid => $newQty) {
                $existingPivot = $existingProducts->get($pid)?->pivot;
                $oldQty = (int) ($existingPivot?->qty ?? 0);
                $delta = $newQty - $oldQty;
                if ($delta <= 0) {
                    continue;
                }
                $stockNow = (int) (SpeedshopStock::where('speedshop_id', $speedshopId)->where('product_id', $pid)->value('jumlah') ?? 0);
                if ($stockNow < $delta) {
                    $product = Product::find($pid);
                    $msg = 'Stok speedshop tidak mencukupi untuk '.($product->nama_produk ?? 'produk').'. Tambahan dibutuhkan: '.$delta.', tersedia: '.$stockNow;
                    if ($request->wantsJson()) {
                        return response()->json(['message' => $msg, 'errors' => ['sparepart' => [$msg]]], 422);
                    }

                    return back()->withErrors(['sparepart' => $msg])->withInput();
                }
            }
        }

        foreach ($targetQty as $pid => $newQty) {
            $product = Product::find($pid);
            if (! $product) {
                continue;
            }

            $existingPivot = $existingProducts->get($pid)?->pivot;
            $oldQty = (int) ($existingPivot?->qty ?? 0);
            $oldHarga = (int) ($existingPivot?->harga ?? 0);
            $harga = $oldHarga > 0 ? $oldHarga : $product->hargaJualCustomer();
            $delta = $newQty - $oldQty;

            if ($delta !== 0 && $speedshopId) {
                $row = SpeedshopStock::firstOrCreate(
                    ['speedshop_id' => $speedshopId, 'product_id' => $pid],
                    ['jumlah' => 0]
                );
                if ($delta > 0) {
                    $row->decrement('jumlah', $delta);
                } else {
                    $row->increment('jumlah', abs($delta));
                }
            }

            $service_order->products()->syncWithoutDetaching([
                $pid => [
                    'qty' => $newQty,
                    'harga' => $harga,
                    'diskon' => (int) ($existingPivot?->diskon ?? 0),
                    'catatan_diskon' => $this->resolveCatatanDiskonForPivot($request, 'sparepart', (int) $pid, $existingPivot?->catatan_diskon),
                    'diskon_verified_by' => null,
                    'diskon_verified_at' => null,
                ],
            ]);
        }

        $currentProductIds = array_keys($targetQty);
        $deleteProductIds = collect($productIds)->reject(fn ($id) => in_array((int) $id, $currentProductIds, true))->values()->all();
        if (! empty($deleteProductIds)) {
            if ($speedshopId) {
                foreach ($deleteProductIds as $pid) {
                    $oldQty = (int) ($existingProducts[$pid]->pivot->qty ?? 0);
                    if ($oldQty > 0) {
                        $row = SpeedshopStock::firstOrCreate(
                            ['speedshop_id' => $speedshopId, 'product_id' => (int) $pid],
                            ['jumlah' => 0]
                        );
                        $row->increment('jumlah', $oldQty);
                    }
                }
            }
            $service_order->products()->detach($deleteProductIds);
        }

        $service_order->load(['jasaServis', 'products']);
        $addJasaDiskonById = $addJasaRows->keyBy('jasa_servis_id');
        $addSparepartDiskonById = $addSparepartRows->keyBy('product_id');

        $needManagerVerify = false;
        foreach ($service_order->jasaServis as $j) {
            $biaya = (int) ($j->pivot->qty ?? 1) * (int) $j->pivot->biaya;
            $diskon = (int) ($request->input("jasa.{$j->id}.diskon") ?? $addJasaDiskonById->get((int) $j->id)['diskon'] ?? $j->pivot->diskon ?? 0);
            if ($this->lineDiskonNeedsManagerVerify($biaya, $diskon)) {
                $needManagerVerify = true;
                break;
            }
        }
        if (! $needManagerVerify) {
            foreach ($service_order->products as $p) {
                $subtotal = $p->pivot->qty * $p->pivot->harga;
                $diskon = (int) ($request->input("sparepart.{$p->id}.diskon") ?? $addSparepartDiskonById->get((int) $p->id)['diskon'] ?? $p->pivot->diskon ?? 0);
                if ($this->lineDiskonNeedsManagerVerify((int) $subtotal, $diskon)) {
                    $needManagerVerify = true;
                    break;
                }
            }
        }

        // Jika ada diskon > 10%, transaksi harus masuk tahap verifikasi (tidak boleh langsung selesai)
        // Verifikasi dilakukan lewat tombol Approve/Tolak oleh Manager Speedshop.

        foreach ($service_order->jasaServis as $j) {
            $subtotal = (int) ($j->pivot->qty ?? 1) * (int) $j->pivot->biaya;
                $diskon = (int) ($request->input("jasa.{$j->id}.diskon") ?? $addJasaDiskonById->get((int) $j->id)['diskon'] ?? $j->pivot->diskon ?? 0);
            $diskon = max(0, min($diskon, $subtotal));
            $catatanDiskon = $this->resolveCatatanDiskonForPivot($request, 'jasa', (int) $j->id, $j->pivot->catatan_diskon);
            $this->assertCatatanDiskonRequiredWhenDiskon($diskon, $catatanDiskon, "jasa.{$j->id}.catatan_diskon");
            $service_order->jasaServis()->updateExistingPivot($j->id, [
                'diskon' => $diskon,
                'catatan_diskon' => $catatanDiskon,
                'diskon_verified_by' => null,
                'diskon_verified_at' => null,
            ]);
        }
        foreach ($service_order->products as $p) {
            $subtotal = (int) ($p->pivot->qty * $p->pivot->harga);
            $diskon = (int) ($request->input("sparepart.{$p->id}.diskon") ?? $addSparepartDiskonById->get((int) $p->id)['diskon'] ?? $p->pivot->diskon ?? 0);
            $diskon = max(0, min($diskon, $subtotal));
            $catatanDiskon = $this->resolveCatatanDiskonForPivot($request, 'sparepart', (int) $p->id, $p->pivot->catatan_diskon);
            $this->assertCatatanDiskonRequiredWhenDiskon($diskon, $catatanDiskon, "sparepart.{$p->id}.catatan_diskon");
            $service_order->products()->updateExistingPivot($p->id, [
                'diskon' => $diskon,
                'catatan_diskon' => $catatanDiskon,
                'diskon_verified_by' => null,
                'diskon_verified_at' => null,
            ]);
        }

        $service_order->refresh();
        $service_order->load(['jasaServis', 'products']);

        $grandTotal = $this->invoiceGrandTotalFromBase((int) $service_order->grand_total);
        $paymentPayload = $this->resolvePembayaranPayload($request, $grandTotal);

        $service_order->update($paymentPayload);

        if ($needManagerVerify) {
            $this->markDiskonVerifikasiPending($service_order, $user, $speedshopId ? (int) $speedshopId : null, 'penyelesaian WO');

            $msg = 'Diskon > 10% diajukan. Menunggu verifikasi Manager Speedshop (Approve/Tolak).';
            $redirect = $user->isManager()
                ? route('speedshop.verifikasi.diskon-work-order.show', $service_order)
                : route('speedshop.selesaikan-transaksi.show', $service_order);
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'redirect' => $redirect,
                ]);
            }
            session()->flash('success', $msg);

            return redirect($redirect);
        }

        // Normal flow (<= 10%) langsung selesai
        $service_order->update([
            'diskon_verifikasi_status' => null,
            'diskon_verifikasi_requested_by' => null,
            'diskon_verifikasi_requested_at' => null,
            'diskon_verifikasi_verified_by' => null,
            'diskon_verifikasi_verified_at' => null,
            'diskon_verifikasi_rejected_by' => null,
            'diskon_verifikasi_rejected_at' => null,
            'diskon_verifikasi_rejected_reason' => null,
            'status' => 'selesai',
            'tanggal_selesai' => now(),
        ]);

        $service_order->refresh();
        $this->ensureInvoiceForCompletedWorkOrder($service_order);

        $msg = 'Work Order berhasil diselesaikan.';
        $this->broadcastQueueUpdated();
        $redirectTo = route('speedshop.wip.show', $service_order);
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'redirect' => $redirectTo,
            ]);
        }
        session()->flash('success', $msg);

        return redirect($redirectTo);
    }

    public function selesaikanApprove(ServiceOrder $service_order)
    {
        $this->assertDiskonVerifikasiViewable($service_order);
        $user = auth()->user();
        if (! $user?->isManager()) {
            abort(403, 'Hanya Manager Speedshop yang dapat meng-approve.');
        }

        return DB::transaction(function () use ($service_order, $user) {
            $service_order = $this->lockServiceOrderForDiskonVerifikasi($service_order);

            if ($service_order->diskon_verifikasi_status !== 'pending') {
                return $this->respondDiskonVerifikasiConflict(
                    $service_order,
                    $this->diskonVerifikasiAlreadyProcessedPayload($service_order)['message']
                );
            }
            if ($service_order->status !== 'proses') {
                return $this->respondDiskonVerifikasiConflict(
                    $service_order,
                    'Work order tidak dalam status Proses. Halaman akan diperbarui.'
                );
            }

            $service_order->load(['jasaServis', 'products']);
            foreach ($service_order->jasaServis as $j) {
                $service_order->jasaServis()->updateExistingPivot($j->id, [
                    'diskon_verified_by' => $user->id,
                    'diskon_verified_at' => now(),
                ]);
            }
            foreach ($service_order->products as $p) {
                $service_order->products()->updateExistingPivot($p->id, [
                    'diskon_verified_by' => $user->id,
                    'diskon_verified_at' => now(),
                ]);
            }

            $service_order->update([
                'diskon_verifikasi_status' => 'approved',
                'diskon_verifikasi_verified_by' => $user->id,
                'diskon_verifikasi_verified_at' => now(),
                'diskon_verifikasi_rejected_reason' => null,
                'status' => 'selesai',
                'tanggal_selesai' => now(),
            ]);

            $service_order->refresh();
            $this->ensureInvoiceForCompletedWorkOrder($service_order);

            $this->broadcastQueueUpdated();

            $redirectTo = ($service_order->tipe_pemesanan ?? ServiceOrder::TIPE_SERVIS) === ServiceOrder::TIPE_PEMBELIAN_PART
                ? route('speedshop.dashboard')
                : route('speedshop.wip.show', $service_order);
            $successMsg = ($service_order->tipe_pemesanan ?? ServiceOrder::TIPE_SERVIS) === ServiceOrder::TIPE_PEMBELIAN_PART
                ? 'Diskon di-approve. Pembelian part selesai dan invoice telah dibuat.'
                : 'Diskon di-approve. Work Order diselesaikan.';

            if (request()->expectsJson() || request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $successMsg,
                    'redirect' => $redirectTo,
                ]);
            }

            return redirect($redirectTo)->with('success', $successMsg);
        });
    }

    public function selesaikanReject(ServiceOrder $service_order)
    {
        $this->assertDiskonVerifikasiViewable($service_order);
        $user = auth()->user();
        if (! $user?->isManager()) {
            abort(403, 'Hanya Manager Speedshop yang dapat menolak.');
        }

        $validated = request()->validate([
            'rejected_reason' => ['required', 'string', 'max:500'],
        ]);

        return DB::transaction(function () use ($service_order, $user, $validated) {
            $service_order = $this->lockServiceOrderForDiskonVerifikasi($service_order);

            if ($service_order->diskon_verifikasi_status !== 'pending') {
                return $this->respondDiskonVerifikasiConflict(
                    $service_order,
                    $this->diskonVerifikasiAlreadyProcessedPayload($service_order)['message']
                );
            }
            if ($service_order->status !== 'proses') {
                return $this->respondDiskonVerifikasiConflict(
                    $service_order,
                    'Work order tidak dalam status Proses. Halaman akan diperbarui.'
                );
            }

            $service_order->load(['jasaServis', 'products']);
            foreach ($service_order->jasaServis as $j) {
                $service_order->jasaServis()->updateExistingPivot($j->id, [
                    'diskon' => 0,
                    'diskon_verified_by' => null,
                    'diskon_verified_at' => null,
                ]);
            }
            foreach ($service_order->products as $p) {
                $service_order->products()->updateExistingPivot($p->id, [
                    'diskon' => 0,
                    'diskon_verified_by' => null,
                    'diskon_verified_at' => null,
                ]);
            }

            $service_order->update([
                'diskon_verifikasi_status' => 'rejected',
                'diskon_verifikasi_rejected_by' => $user->id,
                'diskon_verifikasi_rejected_at' => now(),
                'diskon_verifikasi_rejected_reason' => trim((string) $validated['rejected_reason']),
                'diskon_verifikasi_verified_by' => null,
                'diskon_verifikasi_verified_at' => null,
            ]);

            $this->broadcastQueueUpdated();

            $successMsg = 'Diskon ditolak. Silakan isi ulang diskon (maks 10% per item) atau ajukan verifikasi lagi.';
            $redirectTo = route('speedshop.selesaikan-transaksi.show', $service_order);

            if (request()->expectsJson() || request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $successMsg,
                    'redirect' => $redirectTo,
                ]);
            }

            return redirect($redirectTo)->with('success', $successMsg);
        });
    }

    private function invoiceGrandTotalFromBase(int $baseGrandTotal): int
    {
        $invoiceSettings = app(InvoiceSettingsService::class)->get();
        $chargeMode = (string) ($invoiceSettings['invoice_charge_mode'] ?? 'percent');
        $chargePercent = (float) ($invoiceSettings['invoice_charge_percent'] ?? 0);
        $chargeRupiah = (int) ($invoiceSettings['invoice_charge_rupiah'] ?? 0);
        $chargeAmount = InvoiceSettingsService::calculateChargeAmount(
            $baseGrandTotal,
            $chargeMode,
            $chargePercent,
            $chargeRupiah,
        );

        $ppnMode = (string) ($invoiceSettings['invoice_ppn_mode'] ?? 'percent');
        $ppnPercent = (float) ($invoiceSettings['invoice_ppn_percent'] ?? 0);
        $ppnRupiah = (int) ($invoiceSettings['invoice_ppn_rupiah'] ?? 0);
        $ppnAmount = $ppnMode === 'rupiah'
            ? max(0, $ppnRupiah)
            : (int) round($baseGrandTotal * ($ppnPercent / 100));

        return $baseGrandTotal + $chargeAmount + $ppnAmount;
    }

    /**
     * @return array{jenis_pembayaran: string, pembayaran_split: ?array<int, array{metode: string, jumlah: int}>}
     */
    private function resolvePembayaranPayload(Request $request, int $grandTotal): array
    {
        $mode = (string) ($request->input('pembayaran_mode') ?? 'single');

        if ($mode === 'split') {
            $rows = collect((array) $request->input('pembayaran', []))
                ->map(fn ($r) => [
                    'metode' => strtolower(trim((string) ($r['metode'] ?? ''))),
                    'jumlah' => max(0, (int) ($r['jumlah'] ?? 0)),
                ])
                ->filter(fn ($r) => $r['jumlah'] > 0 && in_array($r['metode'], ['cash', 'transfer', 'qris', 'edc'], true))
                ->values();

            if ($rows->count() < 2) {
                throw ValidationException::withMessages([
                    'pembayaran' => 'Split bill minimal 2 baris pembayaran.',
                ]);
            }

            $sum = (int) $rows->sum('jumlah');
            if ($sum !== $grandTotal) {
                throw ValidationException::withMessages([
                    'pembayaran' => 'Total split pembayaran (Rp '.number_format($sum, 0, ',', '.').') harus sama dengan total transaksi (Rp '.number_format($grandTotal, 0, ',', '.').').',
                ]);
            }

            return [
                'jenis_pembayaran' => 'split',
                'pembayaran_split' => $rows->map(fn ($r) => [
                    'metode' => $r['metode'],
                    'jumlah' => $r['jumlah'],
                ])->all(),
            ];
        }

        $jenis = strtolower(trim((string) $request->input('jenis_pembayaran', '')));
        if (! in_array($jenis, ['cash', 'transfer', 'qris', 'edc'], true)) {
            throw ValidationException::withMessages([
                'jenis_pembayaran' => 'Jenis pembayaran wajib dipilih.',
            ]);
        }

        return [
            'jenis_pembayaran' => $jenis,
            'pembayaran_split' => null,
        ];
    }

    private function lineDiskonNeedsManagerVerify(int $subtotal, int $diskon): bool
    {
        if ($subtotal <= 0 || $diskon <= 0) {
            return false;
        }

        return $diskon > (int) floor($subtotal * 0.10);
    }

    private function normalizedCatatanDiskon(mixed $value): ?string
    {
        $trimmed = trim((string) $value);

        return $trimmed !== '' ? $trimmed : null;
    }

    private function assertCatatanDiskonRequiredWhenDiskon(int $diskon, ?string $catatan, string $errorKey): void
    {
        if ($diskon > 0 && trim((string) $catatan) === '') {
            throw ValidationException::withMessages([
                $errorKey => 'Catatan diskon wajib diisi jika ada diskon.',
            ]);
        }
    }

    /**
     * Normalisasi payload jasa/sparepart sebelum validasi selesaikan/WIP.
     * - Baris yang hilang dari form (tanpa qty) tapi belum ada di remove_* → dianggap dihapus.
     * - Baris yang masih ada (mis. hanya kirim diskon) tanpa qty → isi qty dari pivot.
     * - add_jasa/add_sparepart yatim (hanya diskon, tanpa id) dibuang.
     *
     * @param  list<int|string>  $jasaIds
     * @param  list<int|string>  $productIds
     * @return array{0: list<int>, 1: list<int>} [removeJasaIds, removeSparepartIds]
     */
    private function normalizeSelesaikanItemPayload(Request $request, ServiceOrder $service_order, array $jasaIds, array $productIds): array
    {
        $removeJasaIds = collect((array) $request->input('remove_jasa_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
        $removeSparepartIds = collect((array) $request->input('remove_sparepart_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $jasaPayload = (array) $request->input('jasa', []);
        $addJasaRaw = (array) $request->input('add_jasa', []);
        $hasJasaFormSignal = $jasaPayload !== [] || $removeJasaIds !== [] || $addJasaRaw !== [];
        $jasaById = $service_order->jasaServis->keyBy('id');
        foreach ($jasaIds as $rawId) {
            $id = (int) $rawId;
            if ($id < 1 || in_array($id, $removeJasaIds, true)) {
                continue;
            }
            $row = $jasaPayload[$id] ?? $jasaPayload[(string) $id] ?? null;
            $qtyRaw = is_array($row) ? ($row['qty'] ?? null) : null;
            if ($qtyRaw !== null && $qtyRaw !== '') {
                continue;
            }
            $fallbackQty = max(1, (int) ($jasaById->get($id)?->pivot?->qty ?? 1));
            if (! is_array($row)) {
                // Row tidak ikut terkirim. Jika form jasa lain tetap dikirim, anggap baris ini dihapus.
                // Jika sama sekali tidak ada sinyal form jasa, pertahankan qty existing (hindari wipe total).
                if ($hasJasaFormSignal) {
                    $removeJasaIds[] = $id;
                } else {
                    $jasaPayload[$id] = ['qty' => $fallbackQty];
                }
                continue;
            }
            $jasaPayload[$id] = array_merge($row, ['qty' => $fallbackQty]);
        }

        $sparepartPayload = (array) $request->input('sparepart', []);
        $addSparepartRaw = (array) $request->input('add_sparepart', []);
        $hasSparepartFormSignal = $sparepartPayload !== [] || $removeSparepartIds !== [] || $addSparepartRaw !== [];
        $productById = $service_order->products->keyBy('id');
        foreach ($productIds as $rawId) {
            $id = (int) $rawId;
            if ($id < 1 || in_array($id, $removeSparepartIds, true)) {
                continue;
            }
            $row = $sparepartPayload[$id] ?? $sparepartPayload[(string) $id] ?? null;
            $qtyRaw = is_array($row) ? ($row['qty'] ?? null) : null;
            if ($qtyRaw !== null && $qtyRaw !== '') {
                continue;
            }
            $fallbackQty = max(1, (int) ($productById->get($id)?->pivot?->qty ?? 1));
            if (! is_array($row)) {
                if ($hasSparepartFormSignal) {
                    $removeSparepartIds[] = $id;
                } else {
                    $sparepartPayload[$id] = ['qty' => $fallbackQty];
                }
                continue;
            }
            $sparepartPayload[$id] = array_merge($row, ['qty' => $fallbackQty]);
        }

        $addJasa = collect($addJasaRaw)
            ->filter(fn ($row) => is_array($row) && (int) ($row['jasa_servis_id'] ?? 0) > 0 && (int) ($row['qty'] ?? 0) > 0)
            ->values()
            ->all();
        $addSparepart = collect($addSparepartRaw)
            ->filter(fn ($row) => is_array($row) && (int) ($row['product_id'] ?? 0) > 0 && (int) ($row['qty'] ?? 0) > 0)
            ->values()
            ->all();

        $request->merge([
            'remove_jasa_ids' => array_values(array_unique($removeJasaIds)),
            'remove_sparepart_ids' => array_values(array_unique($removeSparepartIds)),
            'jasa' => $jasaPayload,
            'sparepart' => $sparepartPayload,
            'add_jasa' => $addJasa,
            'add_sparepart' => $addSparepart,
        ]);

        return [
            array_values(array_unique($removeJasaIds)),
            array_values(array_unique($removeSparepartIds)),
        ];
    }

    private function resolveCatatanDiskonForPivot(Request $request, string $prefix, int $entityId, mixed $existing = null): ?string
    {
        $fromMain = $this->normalizedCatatanDiskon($request->input("{$prefix}.{$entityId}.catatan_diskon"));
        if ($fromMain !== null) {
            return $fromMain;
        }

        $addPrefix = $prefix === 'jasa' ? 'add_jasa' : 'add_sparepart';
        $idKey = $prefix === 'jasa' ? 'jasa_servis_id' : 'product_id';
        foreach ((array) $request->input($addPrefix, []) as $idx => $row) {
            if ((int) ($row[$idKey] ?? 0) !== $entityId) {
                continue;
            }
            $fromAdd = $this->normalizedCatatanDiskon($request->input("{$addPrefix}.{$idx}.catatan_diskon"));
            if ($fromAdd !== null) {
                return $fromAdd;
            }
        }

        return $this->normalizedCatatanDiskon($existing);
    }

    private function sparepartRowsNeedManagerVerify(array $spareparts): bool
    {
        foreach ($spareparts as $sp) {
            $subtotal = (int) ($sp['qty'] ?? 0) * (int) ($sp['harga'] ?? 0);
            $diskon = max(0, (int) ($sp['diskon'] ?? 0));
            if ($this->lineDiskonNeedsManagerVerify($subtotal, $diskon)) {
                return true;
            }
        }

        return false;
    }

    private function markDiskonVerifikasiPending(
        ServiceOrder $serviceOrder,
        User $user,
        ?int $speedshopId,
        string $actionLabel = 'penyelesaian WO',
    ): void {
        if (
            Schema::hasColumn('service_orders', 'speedshop_id')
            && ! $serviceOrder->speedshop_id
            && $speedshopId
        ) {
            $serviceOrder->update(['speedshop_id' => (int) $speedshopId]);
            $serviceOrder->refresh();
        }

        $serviceOrder->update([
            'diskon_verifikasi_status' => 'pending',
            'diskon_verifikasi_requested_by' => $user->id,
            'diskon_verifikasi_requested_at' => now(),
            'diskon_verifikasi_verified_by' => null,
            'diskon_verifikasi_verified_at' => null,
            'diskon_verifikasi_rejected_by' => null,
            'diskon_verifikasi_rejected_at' => null,
            'diskon_verifikasi_rejected_reason' => null,
        ]);

        $notifyOutletId = $speedshopId;
        if (! $notifyOutletId && $serviceOrder->warehouse_id) {
            $notifyOutletId = Speedshop::query()
                ->where(function ($q) use ($serviceOrder) {
                    $q->where('mutasi_warehouse_id', $serviceOrder->warehouse_id)
                        ->orWhere('warehouse_id', $serviceOrder->warehouse_id);
                })
                ->orderBy('id')
                ->value('id');
        }
        $notifyOutletId = $notifyOutletId ? (int) $notifyOutletId : null;

        $woLabel = $serviceOrder->kode_servis ?? ('#'.$serviceOrder->id);
        $link = route('speedshop.verifikasi.diskon-work-order.show', $serviceOrder);
        if ($notifyOutletId) {
            foreach (User::speedshopManagersForOutlet($notifyOutletId) as $manager) {
                AppNotification::create([
                    'user_id' => $manager->id,
                    'type' => 'speedshop_diskon_verifikasi_pending',
                    'title' => 'Verifikasi Diskon Work Order',
                    'message' => $user->name.' mengajukan '.$actionLabel.' '.$woLabel.' dengan diskon > 10%. Buka daftar verifikasi atau halaman WO untuk Approve/Tolak.',
                    'data' => ['service_order_id' => $serviceOrder->id, 'speedshop_id' => $notifyOutletId],
                    'link' => $link,
                ]);
            }
        }
    }

    private function ensureInvoiceForCompletedWorkOrder(ServiceOrder $serviceOrder): void
    {
        if ($serviceOrder->status !== 'selesai') {
            return;
        }

        if (! $serviceOrder->tanggal_selesai) {
            $serviceOrder->update(['tanggal_selesai' => now()->toDateString()]);
            $serviceOrder->refresh();
        }

        $existing = Invoice::query()->where('service_order_id', $serviceOrder->id)->first();
        if ($existing) {
            Invoice::syncTanggalFromServiceOrder($existing, $serviceOrder);

            return;
        }

        Invoice::createForCompletedServiceOrder(
            $serviceOrder,
            $serviceOrder->jenis_pembayaran,
        );
    }

    public function cancelVerifikasiIndex(Request $request)
    {
        if (! Schema::hasColumn('service_orders', 'batal_verifikasi_status')) {
            return redirect()->route('speedshop.wip')->with('error', 'Fitur verifikasi pembatalan WO belum tersedia.');
        }
        if (! auth()->user()?->isAccounting()) {
            abort(403, 'Hanya Accounting yang dapat mengakses halaman ini.');
        }

        $period = $this->resolveWipPeriod($request, 'bulanan');
        $baseQuery = $this->batalVerifikasiIndexBaseQuery($period['dari'], $period['sampai']);
        $totalAll = (clone $baseQuery)->count();
        $totalPending = (clone $baseQuery)->where('batal_verifikasi_status', 'pending')->count();
        $totalApproved = (clone $baseQuery)->where('batal_verifikasi_status', 'approved')->count();
        $totalRejected = (clone $baseQuery)->where('batal_verifikasi_status', 'rejected')->count();

        $orders = $this->batalVerifikasiIndexApplyFilters(clone $baseQuery, $request)
            ->orderByRaw("CASE batal_verifikasi_status WHEN 'pending' THEN 0 WHEN 'approved' THEN 1 WHEN 'rejected' THEN 2 ELSE 3 END")
            ->orderByDesc('batal_verifikasi_requested_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('speedshop.wip.batal-verifikasi-index', [
            'orders' => $orders,
            'totalAll' => $totalAll,
            'totalPending' => $totalPending,
            'totalApproved' => $totalApproved,
            'totalRejected' => $totalRejected,
            'mode' => $period['mode'],
            'selectedDate' => $period['selectedDate'],
            'selectedMonth' => $period['selectedMonth'],
            'selectedYear' => $period['selectedYear'],
            'selectedDari' => $period['selectedDari'],
            'selectedSampai' => $period['selectedSampai'],
            'dari' => $period['dari'],
            'sampai' => $period['sampai'],
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<ServiceOrder>
     */
    private function batalVerifikasiIndexBaseQuery(Carbon $dari, Carbon $sampai): \Illuminate\Database\Eloquent\Builder
    {
        $ssid = auth()->user()?->activeSpeedshopId();

        return ServiceOrder::query()
            ->with(['pelanggan', 'kendaraan', 'mekanik', 'batalVerifikasiRequestedBy'])
            ->whereNotNull('batal_verifikasi_status')
            ->whereDate('batal_verifikasi_requested_at', '>=', $dari->toDateString())
            ->whereDate('batal_verifikasi_requested_at', '<=', $sampai->toDateString())
            ->when(
                $ssid && Schema::hasColumn('service_orders', 'speedshop_id'),
                fn ($q) => $q->where('speedshop_id', (int) $ssid),
            )
            ->when($warehouseId = $this->warehouseId(), fn ($q) => $q->where(function ($q2) use ($warehouseId) {
                $q2->where('warehouse_id', $warehouseId)->orWhereNull('warehouse_id');
            }));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<ServiceOrder>
     */
    private function batalVerifikasiIndexApplyFilters(
        \Illuminate\Database\Eloquent\Builder $query,
        Request $request,
    ): \Illuminate\Database\Eloquent\Builder {
        if ($request->filled('status') && in_array($request->status, ['pending', 'approved', 'rejected'], true)) {
            $query->where('batal_verifikasi_status', $request->status);
        }

        if ($request->filled('kode')) {
            $query->where('kode_servis', 'like', '%'.$request->kode.'%');
        }

        if ($request->filled('pelanggan')) {
            $term = $request->pelanggan;
            $query->whereHas('pelanggan', fn ($q) => $q->where('nama', 'like', '%'.$term.'%'));
        }

        if ($request->filled('no_polisi')) {
            $term = $request->no_polisi;
            $query->whereHas('kendaraan', fn ($q) => $q->where('nomor_polisi', 'like', '%'.$term.'%'));
        }

        return $query;
    }

    public function cancelVerifikasiShow(ServiceOrder $service_order)
    {
        if (! Schema::hasColumn('service_orders', 'batal_verifikasi_status')) {
            return redirect()->route('speedshop.verifikasi.batal-work-order.index')
                ->with('error', 'Fitur verifikasi pembatalan WO belum tersedia.');
        }
        if (! auth()->user()?->isAccounting()) {
            abort(403, 'Hanya Accounting yang dapat mengakses halaman ini.');
        }

        $this->assertServiceOrderSpeedshopAccess($service_order);

        $service_order->load([
            'pelanggan',
            'kendaraan',
            'mekanik',
            'jasaServis',
            'products',
            'batalVerifikasiRequestedBy',
            'batalVerifikasiVerifiedBy',
            'batalVerifikasiRejectedBy',
        ]);

        return view('speedshop.wip.batal-verifikasi-show', [
            'service_order' => $service_order,
        ]);
    }

    public function requestCancelWorkOrder(Request $request, ServiceOrder $service_order): JsonResponse|RedirectResponse
    {
        $this->assertServiceOrderSpeedshopAccess($service_order);

        if (! Schema::hasColumn('service_orders', 'batal_verifikasi_status')) {
            $msg = 'Fitur pembatalan work order belum tersedia.';
            if ($request->wantsJson()) {
                return response()->json(['message' => $msg], 422);
            }

            return back()->with('error', $msg);
        }

        if (! in_array($service_order->status, ['antri', 'proses'], true)) {
            $msg = 'Hanya work order berstatus Antri atau Proses yang dapat dibatalkan.';
            if ($request->wantsJson()) {
                return response()->json(['message' => $msg], 422);
            }

            return back()->with('error', $msg);
        }

        if (($service_order->batal_verifikasi_status ?? null) === 'pending') {
            $msg = 'Pengajuan pembatalan sudah menunggu verifikasi Accounting.';
            if ($request->wantsJson()) {
                return response()->json(['message' => $msg], 422);
            }

            return back()->with('error', $msg);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $speedshopId = (int) ($service_order->speedshop_id ?? auth()->user()?->activeSpeedshopId() ?? 0);
        $user = auth()->user();

        $service_order->update([
            'batal_verifikasi_status' => 'pending',
            'batal_verifikasi_requested_by' => $user->id,
            'batal_verifikasi_requested_at' => now(),
            'batal_verifikasi_reason' => trim((string) $validated['reason']),
            'batal_verifikasi_verified_by' => null,
            'batal_verifikasi_verified_at' => null,
            'batal_verifikasi_rejected_by' => null,
            'batal_verifikasi_rejected_at' => null,
            'batal_verifikasi_rejected_reason' => null,
        ]);

        if ($speedshopId > 0) {
            foreach (User::accountingUsersForSpeedshop($speedshopId) as $acct) {
                AppNotification::create([
                    'user_id' => $acct->id,
                    'type' => 'speedshop_wo_batal_pending',
                    'title' => 'Pembatalan WO Menunggu Verifikasi',
                    'message' => ($user->name ?? 'Staff').' mengajukan pembatalan WO '.$service_order->kode_servis,
                    'data' => ['service_order_id' => $service_order->id],
                    'link' => route('speedshop.verifikasi.batal-work-order.show', $service_order),
                ]);
            }
        }

        $this->broadcastQueueUpdated();

        $msg = 'Pengajuan pembatalan work order dikirim. Menunggu verifikasi Accounting.';
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'redirect' => route('speedshop.wip.show', $service_order),
            ]);
        }

        return redirect()->route('speedshop.wip.show', $service_order)->with('success', $msg);
    }

    public function approveCancelWorkOrder(Request $request, ServiceOrder $service_order): JsonResponse|RedirectResponse
    {
        $this->assertServiceOrderSpeedshopAccess($service_order);

        if (! auth()->user()?->isAccounting()) {
            abort(403, 'Hanya Accounting Speedshop yang dapat menyetujui pembatalan.');
        }

        if (! Schema::hasColumn('service_orders', 'batal_verifikasi_status')) {
            return back()->with('error', 'Fitur pembatalan work order belum tersedia.');
        }

        if (($service_order->batal_verifikasi_status ?? null) !== 'pending') {
            $msg = 'Tidak ada pengajuan pembatalan yang menunggu verifikasi.';
            if ($request->wantsJson()) {
                return response()->json(['message' => $msg], 422);
            }

            return back()->with('error', $msg);
        }

        if (! in_array($service_order->status, ['antri', 'proses'], true)) {
            $msg = 'Work order tidak dapat dibatalkan pada status saat ini.';
            if ($request->wantsJson()) {
                return response()->json(['message' => $msg], 422);
            }

            return back()->with('error', $msg);
        }

        $user = auth()->user();

        DB::transaction(function () use ($service_order, $user) {
            $service_order->load(['products']);
            $this->restoreSpeedshopStockForServiceOrder($service_order);

            $service_order->update([
                'status' => 'dibatalkan',
                'mekanik_id' => null,
                'batal_verifikasi_status' => 'approved',
                'batal_verifikasi_verified_by' => $user->id,
                'batal_verifikasi_verified_at' => now(),
                'batal_verifikasi_rejected_reason' => null,
            ]);
        });

        $this->broadcastQueueUpdated();

        $msg = 'Work order dibatalkan. Stok sparepart telah dikembalikan.';
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'redirect' => route('speedshop.verifikasi.batal-work-order.index'),
            ]);
        }

        return redirect()->route('speedshop.verifikasi.batal-work-order.index')->with('success', $msg);
    }

    public function rejectCancelWorkOrder(Request $request, ServiceOrder $service_order): JsonResponse|RedirectResponse
    {
        $this->assertServiceOrderSpeedshopAccess($service_order);

        if (! auth()->user()?->isAccounting()) {
            abort(403, 'Hanya Accounting Speedshop yang dapat menolak pembatalan.');
        }

        if (($service_order->batal_verifikasi_status ?? null) !== 'pending') {
            $msg = 'Tidak ada pengajuan pembatalan yang menunggu verifikasi.';
            if ($request->wantsJson()) {
                return response()->json(['message' => $msg], 422);
            }

            return back()->with('error', $msg);
        }

        $validated = $request->validate([
            'rejected_reason' => ['required', 'string', 'max:500'],
        ]);

        $user = auth()->user();
        $service_order->update([
            'batal_verifikasi_status' => 'rejected',
            'batal_verifikasi_rejected_by' => $user->id,
            'batal_verifikasi_rejected_at' => now(),
            'batal_verifikasi_rejected_reason' => trim((string) $validated['rejected_reason']),
            'batal_verifikasi_verified_by' => null,
            'batal_verifikasi_verified_at' => null,
        ]);

        $this->broadcastQueueUpdated();

        $msg = 'Pengajuan pembatalan work order ditolak.';
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'redirect' => route('speedshop.verifikasi.batal-work-order.index'),
            ]);
        }

        return redirect()->route('speedshop.verifikasi.batal-work-order.index')->with('success', $msg);
    }

    /**
     * Manager lintas cabang / "Semua Speedshop" tidak boleh ditolak hanya karena
     * navbar sedang di cabang lain, atau warehouse_id user berbeda dari WO.
     */
    private function assertServiceOrderSpeedshopAccess(ServiceOrder $service_order): void
    {
        $assignedIds = auth()->user()?->activeSpeedshopIds() ?? [];

        if (
            Schema::hasColumn('service_orders', 'speedshop_id')
            && $service_order->speedshop_id !== null
            && $assignedIds !== []
        ) {
            if (! in_array((int) $service_order->speedshop_id, $assignedIds, true)) {
                abort(403, 'Akses ditolak.');
            }

            return;
        }

        $warehouseId = $this->warehouseId();
        if ($warehouseId !== null && $service_order->warehouse_id !== null && (int) $service_order->warehouse_id !== (int) $warehouseId) {
            abort(403, 'Akses ditolak.');
        }
    }

    private function restoreSpeedshopStockForServiceOrder(ServiceOrder $service_order): void
    {
        $speedshopId = (int) ($service_order->speedshop_id ?? auth()->user()?->activeSpeedshopId() ?? 0);
        if ($speedshopId <= 0) {
            return;
        }

        foreach ($service_order->products as $product) {
            $qty = (int) ($product->pivot->qty ?? 0);
            if ($qty <= 0) {
                continue;
            }
            $row = SpeedshopStock::firstOrCreate(
                ['speedshop_id' => $speedshopId, 'product_id' => (int) $product->id],
                ['jumlah' => 0]
            );
            $row->increment('jumlah', $qty);
        }
    }
}
