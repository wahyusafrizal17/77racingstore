<?php

namespace App\Imports\Speedshop;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\SpeedshopMasterProductArchive;
use App\Models\SpeedshopStock;
use App\Models\StockMovement;
use App\Services\Warehouse\WarehouseSpeedshopCatalogSyncService;
use App\Support\ProductMasterCatalog;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProdukSpeedshopPriceImport implements ToCollection, WithHeadingRow
{
    public int $updated = 0;

    public int $created = 0;

    public int $skippedUnknownKode = 0;

    public int $skippedEmpty = 0;

    public int $skippedWrongCatalog = 0;

    public int $duplicateRows = 0;

    /** @var list<string> */
    public array $skippedWrongCatalogKodes = [];

    /** Baris di mana stok masuk berhasil diterapkan (qty > 0). */
    public int $stockMasukBaris = 0;

    /** Total unit stok masuk. */
    public int $stockMasukUnit = 0;

    /** @var array<int, true> */
    private array $touchedProductIds = [];

    /** @var array<string, true> */
    private array $seenKodesInFile = [];

    public function __construct(
        private readonly string $catalogKind = ProductMasterCatalog::SPEEDSHOP_SUPPLIER,
    ) {}

    private function isPartLuarImport(): bool
    {
        return $this->catalogKind === ProductMasterCatalog::PART_LUAR;
    }

    private function isSpeedshopSupplierImport(): bool
    {
        return $this->catalogKind === ProductMasterCatalog::SPEEDSHOP_SUPPLIER;
    }

    private function isWarehouseSharedImport(): bool
    {
        return ProductMasterCatalog::isWarehouseSharedKind($this->catalogKind);
    }

    public function uniqueProductCount(): int
    {
        return count($this->touchedProductIds);
    }

    public function collection(Collection $rows): void
    {
        $warehouseId = Auth::user()?->activeWarehouseId();
        if ($warehouseId === null && ! $this->isWarehouseSharedImport()) {
            return;
        }

        $ssid = Auth::user()?->activeSpeedshopId();
        $outletIds = $this->importOutletIds($ssid ? (int) $ssid : null);
        if (($this->isPartLuarImport() || $this->isSpeedshopSupplierImport()) && $outletIds === []) {
            throw new \RuntimeException('Pilih cabang speedshop di navbar terlebih dahulu.');
        }

        // Kode duplikat di Excel: pakai baris terakhir saja (jangan gabung stok).
        $rows = $this->keepLatestRowPerKode($rows);

        foreach ($rows as $row) {
            $kode = trim((string) ($row['kode_produk'] ?? ''));
            $kodeUpper = mb_strtoupper($kode);
            $kodeSupplier = mb_strtoupper(trim((string) ($row['kode_supplier'] ?? $row['kode_supplier '] ?? '')));
            $kodeSupplierLen = mb_strlen($kodeSupplier);
            $namaProduk = trim((string) ($row['nama_produk'] ?? ''));
            $kategori = trim((string) ($row['kategori'] ?? ''));
            $brandName = trim((string) ($row['brand'] ?? ''));
            $hargaBeli = (float) ($this->numericCell($row, [
                'harga_pembelian', 'harga_pembelian_rp', 'hpp', 'hpp_rp', 'hpp_mo', 'hpp_mo_rp',
            ]));
            $hargaJual = (float) ($this->numericCell($row, [
                'harga_jual', 'harga_jual_rp', 'harga_jual_speedshop', 'harga_jual_speedshop_rp', 'hpp_speedshop', 'hpp_speedshop_rp',
            ]));
            $hargaCust = (float) ($this->numericCell($row, [
                'harga_jual_cust', 'harga_jual_cust_rp', 'harga_eceran_terendah', 'harga_eceran_terendah_rp', 'harga_jual_eceran', 'harga_jual_eceran_rp',
            ]));

            if ($kode === '') {
                if ($namaProduk === '' && $kategori === '' && $hargaBeli == 0.0 && $hargaJual == 0.0 && $brandName === '') {
                    continue;
                }
                $this->skippedEmpty++;

                continue;
            }

            foreach ($outletIds as $outletId) {
                $product = $this->findProductForImport(
                    (int) $warehouseId,
                    $kodeUpper,
                    $outletId !== null ? (int) $outletId : null,
                );

                if (
                    $product
                    && ProductMasterCatalog::kindForProduct($product) !== $this->catalogKind
                ) {
                    $this->skippedWrongCatalog++;
                    $this->skippedWrongCatalogKodes[] = $kodeUpper;

                    continue;
                }

                $createdNow = false;
                if (! $product) {
                    if ($namaProduk === '' || $kategori === '') {
                        $this->skippedUnknownKode++;

                        continue;
                    }

                    $catAttrs = ['nama' => $kategori, 'section' => Category::SECTION_SPEEDSHOP];
                    $catVals = ['section' => Category::SECTION_SPEEDSHOP];
                    $category = Category::firstOrCreate($catAttrs, $catVals);
                    $brandId = $this->resolveBrandIdForImport($brandName);

                    try {
                        $product = Product::create([
                            'kode_produk' => $kodeUpper,
                            'kode_supplier' => ($kodeSupplierLen >= 2 && $kodeSupplierLen <= 3 ? $kodeSupplier : null),
                            'nama_produk' => $namaProduk,
                            'category_id' => $category->id,
                            'brand_id' => $brandId,
                            'warehouse_id' => $warehouseId,
                            'speedshop_scope_id' => $this->isWarehouseSharedImport()
                                ? 0
                                : (int) $outletId,
                            'jenis_produk' => ProductMasterCatalog::jenisProdukFor($this->catalogKind),
                            'jumlah' => 0,
                            'hpp' => $hargaBeli,
                            'harga_pembelian' => $hargaBeli,
                            'harga_jual_speedshop' => $hargaJual,
                            'harga_jual' => $this->isWarehouseSharedImport() ? $hargaCust : $hargaJual,
                            'harga_jual_reseler' => 0,
                            'harga_eceran_terendah' => $this->isWarehouseSharedImport() ? $hargaCust : 0,
                        ]);
                        $createdNow = true;
                    } catch (QueryException $e) {
                        if (! $this->isDuplicateKeyException($e)) {
                            throw $e;
                        }
                        $product = $this->findProductForImport(
                            (int) $warehouseId,
                            $kodeUpper,
                            $outletId !== null ? (int) $outletId : null,
                        );
                        if (! $product) {
                            throw $e;
                        }
                        if (ProductMasterCatalog::kindForProduct($product) !== $this->catalogKind) {
                            $this->skippedWrongCatalog++;
                            $this->skippedWrongCatalogKodes[] = $kodeUpper;

                            continue;
                        }
                    }

                    if ($createdNow) {
                        $this->afterProductCreated($product, $outletId !== null ? (int) $outletId : null);
                        if ($this->isWarehouseSharedImport()) {
                            app(WarehouseSpeedshopCatalogSyncService::class)->afterWarehouseSharedCatalogSaved($product->fresh());
                        }
                        $this->touchedProductIds[(int) $product->id] = true;
                        $this->created++;

                        continue;
                    }
                }

                $this->reactivateImportedProduct($product, $outletId !== null ? (int) $outletId : null);

                $hasHargaBeli = $this->cellPresent($row, [
                    'harga_pembelian', 'harga_pembelian_rp', 'hpp', 'hpp_rp', 'hpp_mo', 'hpp_mo_rp',
                ]);
                $hasHargaJual = $this->cellPresent($row, [
                    'harga_jual', 'harga_jual_rp', 'harga_jual_speedshop', 'harga_jual_speedshop_rp', 'hpp_speedshop', 'hpp_speedshop_rp',
                ]);
                $hasHargaCust = $this->cellPresent($row, [
                    'harga_jual_cust', 'harga_jual_cust_rp', 'harga_eceran_terendah', 'harga_eceran_terendah_rp', 'harga_jual_eceran', 'harga_jual_eceran_rp',
                ]);

                $payload = [];
                $payload['kode_produk'] = $kodeUpper;
                if ($kodeSupplierLen >= 2 && $kodeSupplierLen <= 3) {
                    $payload['kode_supplier'] = $kodeSupplier;
                } elseif ($this->cellPresent($row, ['kode_supplier'])) {
                    $payload['kode_supplier'] = null;
                }
                if ($namaProduk !== '') {
                    $payload['nama_produk'] = $namaProduk;
                }
                if ($kategori !== '') {
                    $attrs = ['nama' => $kategori, 'section' => Category::SECTION_SPEEDSHOP];
                    $vals = ['section' => Category::SECTION_SPEEDSHOP];
                    $payload['category_id'] = Category::firstOrCreate($attrs, $vals)->id;
                }
                if ($brandName !== '' && ! $this->isPartLuarImport()) {
                    $payload['brand_id'] = Brand::firstOrCreate(
                        ['nama' => $brandName],
                    )->id;
                }
                if ($this->isPartLuarImport()) {
                    $payload['brand_id'] = $this->resolveBrandIdForImport('');
                }
                if ($hasHargaBeli && ! $this->isWarehouseSharedImport()) {
                    $payload['hpp'] = $hargaBeli;
                    $payload['harga_pembelian'] = $hargaBeli;
                }
                if ($hasHargaJual) {
                    $payload['harga_jual_speedshop'] = $hargaJual;
                    if (! $this->isWarehouseSharedImport()) {
                        $payload['harga_jual'] = $hargaJual;
                    }
                }
                if ($hasHargaCust && $this->isWarehouseSharedImport()) {
                    $payload['harga_eceran_terendah'] = $hargaCust;
                    $payload['harga_jual'] = $hargaCust;
                }
                $payload['jenis_produk'] = ProductMasterCatalog::jenisProdukFor($this->catalogKind);

                if ($payload === []) {
                    continue;
                }

                $product->update($payload);
                if ($this->isPartLuarImport() && $outletId) {
                    $this->afterProductCreated($product, (int) $outletId);
                }
                if ($this->isWarehouseSharedImport()) {
                    app(WarehouseSpeedshopCatalogSyncService::class)->afterWarehouseSharedCatalogSaved($product->fresh());
                }
                $this->touchedProductIds[(int) $product->id] = true;
                $this->updated++;
            }
        }
    }

    /**
     * Outlet tujuan import: semua cabang jika filter Semua Speedshop, selain itu cabang aktif.
     *
     * @return list<int|null>
     */
    private function importOutletIds(?int $activeSpeedshopId): array
    {
        if ($this->isWarehouseSharedImport()) {
            return [null];
        }

        if (Auth::user()?->isSpeedshopAllScopeSelected()) {
            $ids = array_values(array_unique(array_filter(
                array_map('intval', Auth::user()?->reportSpeedshopFilterIds() ?? []),
                fn (int $id) => $id > 0,
            )));
            if ($ids !== []) {
                return $ids;
            }
        }

        return $activeSpeedshopId ? [(int) $activeSpeedshopId] : [];
    }

    private function findProductForImport(?int $warehouseId, string $kodeUpper, ?int $speedshopId): ?Product
    {
        $scopeId = $this->isWarehouseSharedImport() ? 0 : (int) $speedshopId;
        if (! $this->isWarehouseSharedImport() && $scopeId <= 0) {
            return null;
        }

        $query = Product::withTrashed()
            ->where('speedshop_scope_id', $scopeId)
            ->where(function ($q) use ($kodeUpper) {
                $q->where('kode_produk', $kodeUpper)
                    ->orWhereRaw('UPPER(TRIM(kode_produk)) = ?', [$kodeUpper]);
            });
        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        } else {
            $query->whereNull('warehouse_id');
        }

        return $query
            ->orderByRaw('CASE WHEN deleted_at IS NULL THEN 0 ELSE 1 END')
            ->first();
    }

    private function isDuplicateKeyException(QueryException $e): bool
    {
        if ($e instanceof UniqueConstraintViolationException) {
            return true;
        }

        $driverCode = (int) ($e->errorInfo[1] ?? 0);
        $message = $e->getMessage();

        return $driverCode === 1062
            || str_contains($message, 'Duplicate entry')
            || str_contains($message, 'UNIQUE constraint failed');
    }

    private function reactivateImportedProduct(Product $product, ?int $speedshopId): void
    {
        if ($product->trashed()) {
            $product->restore();
        }

        if ($product->hidden_from_warehouse_master_at !== null) {
            $product->forceFill(['hidden_from_warehouse_master_at' => null])->save();
        }

        if ($speedshopId) {
            SpeedshopMasterProductArchive::restoreForOutlet($speedshopId, (int) $product->id);
            SpeedshopStock::firstOrCreate([
                'speedshop_id' => $speedshopId,
                'product_id' => (int) $product->id,
            ], [
                'jumlah' => 0,
            ]);
        }
    }

    private function resolveBrandIdForImport(string $brandName): ?int
    {
        if ($this->isPartLuarImport()) {
            return (int) Brand::query()->firstOrCreate(['nama' => 'Part Luar'])->id;
        }

        if ($brandName === '') {
            return null;
        }

        return Brand::firstOrCreate(['nama' => $brandName])->id;
    }

    private function afterProductCreated(Product $product, ?int $speedshopId): void
    {
        if (! $speedshopId) {
            return;
        }

        SpeedshopMasterProductArchive::restoreForOutlet($speedshopId, (int) $product->id);
        SpeedshopStock::firstOrCreate([
            'speedshop_id' => $speedshopId,
            'product_id' => (int) $product->id,
        ], [
            'jumlah' => 0,
        ]);
    }

    /**
     * Jika kode_produk muncul lebih dari sekali, hanya baris paling bawah (terbaru di file) yang dipakai.
     */
    private function keepLatestRowPerKode(Collection $rows): Collection
    {
        $latestByKode = [];
        $emptyKodeRows = [];

        foreach ($rows as $row) {
            $row = $row instanceof Collection ? $row : collect($row);
            $kode = trim((string) ($row['kode_produk'] ?? ''));

            if ($kode === '') {
                $emptyKodeRows[] = $row;

                continue;
            }

            $kodeKey = mb_strtoupper($kode);
            if (isset($latestByKode[$kodeKey])) {
                $this->duplicateRows++;
            }
            $latestByKode[$kodeKey] = $row;
            $this->seenKodesInFile[$kodeKey] = true;
        }

        return collect(array_values($latestByKode))->merge($emptyKodeRows);
    }

    private function cellPresent(Collection $row, array $keys): bool
    {
        foreach ($keys as $key) {
            if (! $row->has($key)) {
                continue;
            }
            if (trim((string) $row[$key]) !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * Set stok cabang ke nilai absolut dari Excel (bukan ditambah).
     * Contoh: stok lama 23, Excel 25 → hasil 25.
     */
    private function applySpeedshopStokSet(Product $product, int $qty): void
    {
        $speedshopId = Auth::user()?->activeSpeedshopId();
        if (! $speedshopId) {
            return;
        }

        // Jangan restore arsip di sini — produk arsip memang sengaja di-skip saat import
        // (dibuat baris baru), agar tidak mengaktifkan arsip lagi.

        $after = max(0, $qty);
        $stock = SpeedshopStock::firstOrNew([
            'speedshop_id' => $speedshopId,
            'product_id' => $product->id,
        ]);
        $before = (int) ($stock->jumlah ?? 0);
        $stock->jumlah = $after;
        $stock->save();

        $delta = $after - $before;
        if ($delta !== 0) {
            StockMovement::create([
                'product_id' => $product->id,
                'type' => $delta > 0 ? 'masuk' : 'keluar',
                'qty' => abs($delta),
                'keterangan' => 'Penyesuaian stok import database produk Speedshop (set ke '.$after.')',
                'reference' => 'speedshop_master_produk_import',
            ]);
        }

        $this->stockMasukBaris++;
        $this->stockMasukUnit += $after;
    }

    private function numericCell(Collection $row, array $keys): float|string
    {
        foreach ($keys as $key) {
            if (! $row->has($key)) {
                continue;
            }
            $v = $row[$key];
            if ($v === '' || $v === null) {
                continue;
            }

            return $this->parseMoney($v);
        }

        return 0;
    }

    private function parseMoney(mixed $value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $s = preg_replace('/[^\d,.-]/', '', trim((string) $value)) ?? '';
        if ($s === '' || $s === '-' || $s === '.' || $s === ',') {
            return 0.0;
        }

        $lastComma = strrpos($s, ',');
        $lastDot = strrpos($s, '.');
        if ($lastComma !== false && $lastDot !== false) {
            if ($lastComma > $lastDot) {
                $s = str_replace('.', '', $s);
                $s = str_replace(',', '.', $s);
            } else {
                $s = str_replace(',', '', $s);
            }
        } elseif ($lastDot !== false && preg_match('/\.\d{3}$/', $s)) {
            $s = str_replace('.', '', $s);
        } elseif ($lastComma !== false && preg_match('/,\d{3}$/', $s)) {
            $s = str_replace(',', '', $s);
        } else {
            $s = str_replace(',', '.', $s);
        }

        return (float) $s;
    }

    private function intCell(Collection $row, array $keys): int
    {
        $raw = $this->numericCell($row, $keys);

        return max(0, (int) round((float) $raw));
    }
}
