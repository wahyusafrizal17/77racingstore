<?php

namespace App\Imports\Warehouse;

use App\Models\Product;
use App\Models\StockMovement;
use App\Support\ProductMasterCatalog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Import stok warehouse: kolom Kode Produk + Stok (nilai absolut).
 * Tidak mengubah data master produk.
 */
class StokProdukWarehouseImport implements ToCollection, WithHeadingRow
{
    public int $rowsApplied = 0;

    public int $rowsSkipped = 0;

    public function __construct(
        private string $catalogKind = ProductMasterCatalog::MO,
    ) {}

    public function collection(Collection $rows): void
    {
        $warehouseId = Auth::user()?->activeWarehouseId();
        if ($warehouseId === null) {
            return;
        }

        foreach ($rows as $row) {
            $data = $row instanceof Collection ? $row->toArray() : (array) $row;
            $kode = mb_strtoupper(trim((string) ($data['kode_produk'] ?? $data['kode'] ?? '')));
            if ($kode === '') {
                continue;
            }

            if (! $this->cellPresent($data, ['stok', 'stock', 'qty'])) {
                $this->rowsSkipped++;

                continue;
            }

            $qty = max(0, (int) round((float) ($data['stok'] ?? $data['stock'] ?? $data['qty'] ?? 0)));

            $query = Product::query()
                ->forUser()
                ->where('warehouse_id', (int) $warehouseId)
                ->where('kode_produk', $kode);

            $this->applyCatalogFilter($query);

            $product = $query->first();
            if (! $product) {
                $this->rowsSkipped++;

                continue;
            }

            $beforeQty = (int) ($product->jumlah ?? 0);
            $delta = $qty - $beforeQty;
            if ($delta !== 0) {
                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => $delta > 0 ? 'masuk' : 'keluar',
                    'qty' => abs($delta),
                    'keterangan' => 'Penyesuaian stok import Inventori Produk warehouse',
                    'reference' => 'warehouse_inventori_produk_import',
                ]);
            }

            $product->update(['jumlah' => $qty]);
            $this->rowsApplied++;
        }
    }

    private function applyCatalogFilter($query): void
    {
        match ($this->catalogKind) {
            ProductMasterCatalog::SPEEDSHOP_SUPPLIER => $query
                ->jenisOri()
                ->where('speedshop_scope_id', '>', 0),
            ProductMasterCatalog::PART_LUAR => $query->jenisPartLuar(),
            default => $query->forWarehouseSharedCatalogBase($this->catalogKind),
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $keys
     */
    private function cellPresent(array $data, array $keys): bool
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }
            $v = $data[$key];
            if ($v === null || $v === '') {
                continue;
            }

            return true;
        }

        return false;
    }
}
