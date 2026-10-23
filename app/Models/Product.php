<?php

namespace App\Models;

use App\Support\ProductMasterCatalog;
use App\Support\WarehouseMasterCleanableProductQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    public const JENIS_ORI = 'ori';

    public const JENIS_PART_LUAR = 'part_luar';

    public const JENIS_MERCHANDISE = 'merchandise';

    public const JENIS_CONSUMABLE = 'consumable';

    /** Jumlah produk per halaman di katalog portal (reseller, MO operator, marketplace). */
    public const PORTAL_CATALOG_PER_PAGE = 25;

    protected $casts = [
        'hidden_from_warehouse_master_at' => 'datetime',
        'hidden_from_produksi_master_at' => 'datetime',
    ];

    protected $fillable = [
        'kode_produk',
        'kode_supplier',
        'nama_produk',
        'deskripsi',
        'berat',
        'gambar_path',
        'category_id',
        'brand_id',
        'jumlah',
        'harga_pembelian',
        'harga_jual',
        'hpp',
        'harga_jual_speedshop',
        'harga_jual_reseler',
        'harga_eceran_terendah',
        'warehouse_id',
        /** 0 = katalog shared gudang cabang; >0 = SKU milik outlet speedshop tersebut */
        'speedshop_scope_id',
        /** ori / part_luar / merchandise / consumable — pemisah katalog master */
        'jenis_produk',
        /** Disembunyikan dari master warehouse saja; bukan hapus data (Speedshop tetap memakai baris ini). */
        'hidden_from_warehouse_master_at',
        /** Disembunyikan dari master produksi saja; riwayat packing/produksi tetap memakai baris ini. */
        'hidden_from_produksi_master_at',
    ];

    public function isPartLuar(): bool
    {
        return ($this->jenis_produk ?? self::JENIS_ORI) === self::JENIS_PART_LUAR;
    }

    public function scopeJenisOri(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('jenis_produk', self::JENIS_ORI)
                ->orWhereNull('jenis_produk');
        });
    }

    public function scopeJenisPartLuar(Builder $query): Builder
    {
        return $query->where('jenis_produk', self::JENIS_PART_LUAR);
    }

    public function scopeForCatalogJenis(Builder $query, string $catalogKind): Builder
    {
        $jenis = ProductMasterCatalog::jenisProdukFor($catalogKind);
        if ($jenis === self::JENIS_ORI) {
            return $query->jenisOri();
        }

        return $query->where('jenis_produk', $jenis);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function scopeForUser($query, $user = null)
    {
        $user ??= auth()->user();
        if ($user) {
            $warehouseId = $user->activeWarehouseId();
            if ($warehouseId !== null) {
                $query->where('warehouse_id', $warehouseId);
            } elseif ($user->isSpeedshopRegionalManager()) {
                $warehouseIds = collect($user->managedSpeedshopIds())
                    ->map(function (int $speedshopId) {
                        $speedshop = Speedshop::query()->find($speedshopId);

                        return $speedshop?->mutasi_warehouse_id ?? $speedshop?->warehouse_id;
                    })
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values()
                    ->all();

                if ($warehouseIds !== []) {
                    $query->whereIn('warehouse_id', $warehouseIds);
                }
            }
        }

        return $query;
    }

    /**
     * Tampil di halaman master produk warehouse (katalog shared saja; scope 0 dipanggil terpisah di controller).
     */
    public function scopeVisibleInWarehouseMasterKatalog(Builder $query): Builder
    {
        return $query->whereNull('hidden_from_warehouse_master_at');
    }

    /**
     * Produk produksi (warehouse_id null, scope 0).
     */
    public function scopeForProduksiMaster(Builder $query): Builder
    {
        return $query
            ->whereNull('warehouse_id')
            ->where('speedshop_scope_id', 0);
    }

    /**
     * Baris stok produksi (packing / mutasi) untuk product_id dari form atau riwayat lama.
     */
    public static function resolveForProduksiStock(?int $productId): ?self
    {
        if (! $productId) {
            return null;
        }

        $direct = static::forProduksiMaster()->find($productId);
        if ($direct) {
            return $direct;
        }

        $source = static::query()->find($productId);
        if (! $source) {
            return null;
        }

        $kode = mb_strtoupper(trim((string) $source->kode_produk));
        if ($kode === '') {
            return null;
        }

        return static::forProduksiMaster()->where('kode_produk', $kode)->first();
    }

    /**
     * Hitung stok produksi dari ledger (movement masuk/keluar − mutasi keluar produksi).
     */
    public static function expectedProduksiJumlahFromLedger(self $product): int
    {
        if ($product->warehouse_id !== null || (int) $product->speedshop_scope_id !== 0) {
            return (int) $product->jumlah;
        }

        $productId = (int) $product->id;
        $masuk = (int) StockMovement::query()
            ->where('product_id', $productId)
            ->where('type', 'masuk')
            ->sum('qty');
        $keluar = (int) StockMovement::query()
            ->where('product_id', $productId)
            ->where('type', 'keluar')
            ->sum('qty');
        $mutasiOut = (int) MutasiItem::query()
            ->where('product_id', $productId)
            ->whereHas('mutasi', fn ($q) => $q
                ->where('sumber', 'produksi')
                ->whereNotIn('status', ['ditolak']))
            ->sum('quantity');

        return max(0, $masuk - $keluar - $mutasiOut);
    }

    /**
     * Tampil di halaman master produk produksi.
     */
    public function scopeVisibleInProduksiMasterKatalog(Builder $query): Builder
    {
        return $query->whereNull('hidden_from_produksi_master_at');
    }

    /**
     * Belum dipakai speedshop (riwayat warehouse boleh dibersihkan terpisah).
     */
    public function scopeNotUsedBySpeedshop(Builder $query): Builder
    {
        return WarehouseMasterCleanableProductQuery::applyNotUsedBySpeedshopScope($query);
    }

    /**
     * Produk yang boleh dipilih di form operasional warehouse (pembelian, transaksi offline/online).
     * Selaras dengan master katalog: katalog shared gudang, tidak diarsipkan.
     */
    public function scopeSelectableInWarehouseCatalog(Builder $query): Builder
    {
        return $query
            ->where('speedshop_scope_id', 0)
            ->visibleInWarehouseMasterKatalog();
    }

    /** Stok warehouse tersedia (> 0). */
    public function scopeInStock(Builder $query): Builder
    {
        return $query->where('jumlah', '>', 0);
    }

    /** Katalog portal reseller: shared gudang, punya harga reseller (termasuk stok habis). */
    public function scopeForResellerPortalCatalog(Builder $query, int $warehouseId): Builder
    {
        return $query
            ->where('warehouse_id', $warehouseId)
            ->selectableInWarehouseCatalog()
            ->where('harga_jual_reseler', '>', 0);
    }

    /** Katalog operator MO: shared gudang, punya HPP (termasuk stok habis). */
    public function scopeForMoOperatorPortalCatalog(Builder $query, int $warehouseId): Builder
    {
        return $query
            ->where('warehouse_id', $warehouseId)
            ->selectableInWarehouseCatalog()
            ->where('hpp', '>', 0);
    }

    /** Katalog marketplace: shared gudang, punya harga eceran (termasuk stok habis). */
    public function scopeForMarketplacePortalCatalog(Builder $query, int $warehouseId): Builder
    {
        return $query
            ->where('warehouse_id', $warehouseId)
            ->selectableInWarehouseCatalog()
            ->where('harga_eceran_terendah', '>', 0);
    }

    public function isAvailableInResellerPortalCatalog(int $warehouseId): bool
    {
        return (int) $this->warehouse_id === $warehouseId
            && (int) $this->speedshop_scope_id === 0
            && $this->hidden_from_warehouse_master_at === null
            && (int) $this->harga_jual_reseler > 0;
    }

    public function isAvailableInMoOperatorPortalCatalog(int $warehouseId): bool
    {
        return (int) $this->warehouse_id === $warehouseId
            && (int) $this->speedshop_scope_id === 0
            && $this->hidden_from_warehouse_master_at === null
            && (float) $this->hpp > 0;
    }

    public function isAvailableInMarketplacePortalCatalog(int $warehouseId): bool
    {
        return (int) $this->warehouse_id === $warehouseId
            && (int) $this->speedshop_scope_id === 0
            && $this->hidden_from_warehouse_master_at === null
            && (int) $this->harga_eceran_terendah > 0;
    }

    public function isInStock(): bool
    {
        return (int) $this->jumlah > 0;
    }

    /**
     * Harga jual ke customer (HARGA JUAL CUST / HARGA JUAL ECERAN).
     */
    public function hargaJualCustomer(): int
    {
        $eceran = (int) ($this->harga_eceran_terendah ?? 0);
        if ($eceran > 0) {
            return $eceran;
        }

        $jual = (int) ($this->harga_jual ?? 0);
        if ($jual > 0) {
            return $jual;
        }

        return (int) ($this->harga_jual_speedshop ?? 0);
    }

    /**
     * Batasi ke katalog shared (0) dan/atau baris milik outlet tertentu.
     */
    public function scopeForSpeedshopCatalogScope(Builder $query, ?int $speedshopId): Builder
    {
        if (! $speedshopId) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($speedshopId) {
            $q->where('speedshop_scope_id', 0)
                ->orWhere('speedshop_scope_id', (int) $speedshopId);
        });
    }

    /**
     * Cari produk di gudang: prioritas baris milik outlet, lalu katalog shared.
     */
    public static function findByKodeForWarehouseAndSpeedshopOutlet(int $warehouseId, string $kodeProduk, int $speedshopId): ?self
    {
        $kode = mb_strtoupper(trim($kodeProduk));
        $base = static::query()
            ->where('warehouse_id', $warehouseId)
            ->where('kode_produk', $kode);

        return (clone $base)->where('speedshop_scope_id', (int) $speedshopId)->first()
            ?? (clone $base)->where('speedshop_scope_id', 0)->first();
    }

    /**
     * Untuk sinkron dari mutasi/transaksi warehouse: prioritaskan katalog shared.
     */
    public static function findByKodeForWarehouseMutasiTarget(int $warehouseId, string $kodeProduk, int $speedshopId = 0): ?self
    {
        $kode = mb_strtoupper(trim($kodeProduk));
        $base = static::query()
            ->where('warehouse_id', $warehouseId)
            ->where('kode_produk', $kode);

        $shared = (clone $base)->where('speedshop_scope_id', 0)->first();
        if ($shared) {
            return $shared;
        }

        if ($speedshopId > 0) {
            return (clone $base)->where('speedshop_scope_id', (int) $speedshopId)->first();
        }

        return (clone $base)->orderBy('speedshop_scope_id')->first();
    }

    /**
     * Stok yang tampil di Inventori Warehouse (katalog shared), bukan baris outlet-scoped.
     */
    public function warehouseInventoryStock(?int $warehouseId = null): int
    {
        $warehouseId = $warehouseId ?? (int) ($this->warehouse_id ?? 0);
        if ($warehouseId <= 0 || trim((string) $this->kode_produk) === '') {
            return (int) ($this->jumlah ?? 0);
        }

        if ((int) ($this->speedshop_scope_id ?? 0) === 0 && (int) $this->warehouse_id === $warehouseId) {
            return (int) ($this->jumlah ?? 0);
        }

        $catalog = static::findByKodeForWarehouseMutasiTarget($warehouseId, (string) $this->kode_produk);

        return (int) ($catalog?->jumlah ?? $this->jumlah ?? 0);
    }

    public function scopeForSpeedshop($query, ?int $speedshopId)
    {
        if (! $speedshopId) {
            return $query;
        }

        return $query->whereHas('speedshopStocks', fn ($q) => $q->where('speedshop_id', (int) $speedshopId));
    }

    /**
     * Inventori Speedshop → Produk: selaras Database Produk master outlet (ORI aktif).
     * Part luar tidak ikut di sini — ada di Master Part Luar.
     */
    public function scopeForSpeedshopStokProdukInventory(Builder $query, int $speedshopId): Builder
    {
        return $query
            ->forUser()
            ->jenisOri()
            ->forSpeedshopCatalogScope($speedshopId)
            ->forSpeedshopInventoryUi($speedshopId)
            ->visibleInSpeedshopMasterKatalog($speedshopId);
    }

    /**
     * Katalog MO shared gudang — dipakai master warehouse & inventori stok MO (head warehouse + FLP).
     */
    public function scopeForSharedMoMasterKatalog(Builder $query): Builder
    {
        return $query->forWarehouseSharedCatalogBase(ProductMasterCatalog::MO);
    }

    /**
     * Baris katalog gudang (scope 0) per jenis: MO / merchandise / consumable.
     */
    public function scopeForWarehouseSharedCatalogBase(Builder $query, string $catalogKind): Builder
    {
        return $query
            ->forCatalogJenis($catalogKind)
            ->where('speedshop_scope_id', 0)
            ->visibleInWarehouseMasterKatalog();
    }

    /**
     * Katalog gudang shared untuk satu warehouse: SKU warehouse itu, plus SKU tanpa warehouse_id
     * yang kategorinya bukan produksi.
     */
    public function scopeForWarehouseSharedCatalog(Builder $query, string $catalogKind, ?int $warehouseId): Builder
    {
        $query->forWarehouseSharedCatalogBase($catalogKind);

        $nonProduksiOrphan = function (Builder $inner) {
            $inner->whereNull('warehouse_id')
                ->whereHas('category', fn (Builder $c) => $c->where('section', '!=', Category::SECTION_PRODUKSI));
        };

        if ($warehouseId === null) {
            return $query->where(function (Builder $q) use ($nonProduksiOrphan) {
                $q->whereNotNull('warehouse_id')
                    ->orWhere($nonProduksiOrphan);
            });
        }

        return $query->where(function (Builder $q) use ($warehouseId, $nonProduksiOrphan) {
            $q->where('warehouse_id', (int) $warehouseId)
                ->orWhere($nonProduksiOrphan);
        });
    }

    /**
     * Katalog MO untuk satu gudang: SKU warehouse itu, plus SKU shared tanpa warehouse_id
     * yang kategorinya bukan produksi (mis. merchandise yang salah masuk master produksi).
     */
    public function scopeForWarehouseSharedMoCatalog(Builder $query, ?int $warehouseId): Builder
    {
        return $query->forWarehouseSharedCatalog(ProductMasterCatalog::MO, $warehouseId);
    }

    /**
     * Filter inventori stok speedshop per jenis katalog master.
     */
    public function scopeForSpeedshopStokCatalog(Builder $query, int $speedshopId, string $catalogKind): Builder
    {
        if (ProductMasterCatalog::isWarehouseSharedKind($catalogKind)) {
            return $query->forWarehouseSharedCatalog(
                $catalogKind,
                $this->warehouseIdForSpeedshopCatalog((int) $speedshopId)
            );
        }

        $query->forCatalogJenis($catalogKind);

        if (in_array($catalogKind, [ProductMasterCatalog::SPEEDSHOP_SUPPLIER, ProductMasterCatalog::PART_LUAR], true)) {
            return $query
                ->where('speedshop_scope_id', (int) $speedshopId)
                ->forSpeedshopInventoryUi($speedshopId)
                ->visibleInSpeedshopMasterKatalog($speedshopId);
        }

        return $query
            ->forSpeedshopCatalogScope($speedshopId)
            ->forSpeedshopInventoryUi($speedshopId)
            ->visibleInSpeedshopMasterKatalog($speedshopId);
    }

    /**
     * Gabungan katalog outlet untuk beberapa cabang (filter Semua Speedshop).
     *
     * @param  list<int>  $speedshopIds
     */
    public function scopeForSpeedshopStokCatalogAny(Builder $query, array $speedshopIds, string $catalogKind): Builder
    {
        $ids = array_values(array_unique(array_filter(
            array_map('intval', $speedshopIds),
            fn (int $id) => $id > 0,
        )));

        if ($ids === []) {
            return $query->whereRaw('0 = 1');
        }

        if (count($ids) === 1) {
            return $query->forSpeedshopStokCatalog($ids[0], $catalogKind);
        }

        return $query->where(function (Builder $outer) use ($ids, $catalogKind) {
            foreach ($ids as $speedshopId) {
                $outer->orWhere(function (Builder $q) use ($speedshopId, $catalogKind) {
                    $q->forSpeedshopStokCatalog($speedshopId, $catalogKind);
                });
            }
        });
    }

    /**
     * Satu baris per kode produk (id terkecil) dari query katalog yang sudah ter-scope.
     */
    public function scopeCollapseToCanonicalKode(Builder $query): Builder
    {
        $sub = (clone $query)
            ->toBase()
            ->cloneWithout(['orders', 'columns', 'groups', 'havings', 'offset', 'limit', 'unions', 'lock'])
            ->cloneWithoutBindings(['select', 'order', 'union', 'group']);
        $sub->selectRaw('MIN(products.id)');
        $sub->groupByRaw('UPPER(TRIM(products.kode_produk))');

        return $query->whereIn('products.id', $sub);
    }

    /** @var array<int, int|null> */
    private static array $warehouseIdBySpeedshop = [];

    private function warehouseIdForSpeedshopCatalog(int $speedshopId): ?int
    {
        if (! array_key_exists($speedshopId, self::$warehouseIdBySpeedshop)) {
            $shop = Speedshop::query()->find($speedshopId);
            $warehouseId = $shop?->mutasi_warehouse_id ?? $shop?->warehouse_id;
            self::$warehouseIdBySpeedshop[$speedshopId] = $warehouseId ? (int) $warehouseId : null;
        }

        return self::$warehouseIdBySpeedshop[$speedshopId];
    }

    /**
     * Batasi katalog ke kategori section speedshop (master global semua cabang).
     */
    public function scopeWhereSpeedshopOutletCategory(Builder $query, ?int $speedshopId = null): Builder
    {
        return $query->whereHas('category', fn (Builder $q) => $q->forSpeedshop());
    }

    /**
     * UI inventori cabang: produk berkategori speedshop ATAU punya baris stok di outlet.
     */
    public function scopeForSpeedshopInventoryUi(Builder $query, ?int $speedshopId): Builder
    {
        if (! $speedshopId) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($speedshopId) {
            $q->whereHas('category', fn (Builder $c) => $c->forSpeedshop())
                ->orWhereHas('speedshopStocks', fn (Builder $s) => $s->where('speedshop_id', (int) $speedshopId));
        });
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function speedshopStocks()
    {
        return $this->hasMany(SpeedshopStock::class);
    }

    public function speedshopMasterArchives()
    {
        return $this->hasMany(SpeedshopMasterProductArchive::class);
    }

    /**
     * Tampil di master produk speedshop untuk satu outlet (belum diarsipkan outlet tersebut).
     */
    public function scopeVisibleInSpeedshopMasterKatalog(Builder $query, ?int $speedshopId): Builder
    {
        if (! $speedshopId) {
            return $query;
        }

        return $query->whereDoesntHave('speedshopMasterArchives', function (Builder $q) use ($speedshopId) {
            $q->where('speedshop_id', (int) $speedshopId);
        });
    }

    public function gambarUrl(): ?string
    {
        if (! $this->gambar_path) {
            return null;
        }

        $disk = (string) config('filesystems.upload_disk', 'public');

        if ($disk === 'public') {
            return '/storage/'.ltrim($this->gambar_path, '/');
        }

        return Storage::disk($disk)->url($this->gambar_path);
    }

    /** @return list<string> */
    public function resellerCatalogDescriptionParagraphs(): array
    {
        $stok = number_format((int) $this->jumlah, 0, ',', '.');
        $brand = $this->brand?->nama;
        $category = $this->category?->nama;
        $name = $this->nama_produk;

        $intro = match (true) {
            $brand && $category => "{$name} adalah produk {$brand} untuk kategori {$category}",
            $brand !== null && $brand !== '' => "{$name} adalah produk merk {$brand}",
            $category !== null && $category !== '' => "{$name} termasuk kategori {$category}",
            default => "{$name} tersedia di katalog reseller warehouse",
        };

        return [
            $intro.'.',
            'Produk ini dirancang untuk memberikan performa optimal, daya tahan tinggi, dan kepercayaan di lapangan.',
            "Saat ini tersedia {$stok} unit di gudang warehouse dan dapat dipesan langsung.",
        ];
    }
}
