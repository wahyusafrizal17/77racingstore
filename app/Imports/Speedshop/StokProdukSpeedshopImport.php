<?php

namespace App\Imports\Speedshop;

use App\Models\Product;
use App\Models\SpeedshopStock;
use App\Models\StockMovement;
use App\Support\ProductMasterCatalog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Import stok speedshop: kolom Kode Produk + Stok (nilai absolut di cabang aktif).
 * Tidak mengubah data master produk.
 */
class StokProdukSpeedshopImport implements ToCollection, WithHeadingRow
{
    public int $rowsApplied = 0;

    public int $rowsSkipped = 0;

    public function __construct(
        private string $catalogKind = ProductMasterCatalog::MO,
        private ?int $speedshopId = null,
    ) {}

    public function collection(Collection $rows): void
    {
        $speedshopId = $this->speedshopId ?? Auth::user()?->activeSpeedshopId();
        if (! $speedshopId) {
            throw new \RuntimeException('Pilih cabang speedshop di navbar terlebih dahulu.');
        }

        foreach ($rows as $row) {
            $data = $row instanceof Collection ? $row->toArray() : (array) $row;
            $kode = mb_strtoupper(trim((string) ($data['kode_produk'] ?? $data['kode'] ?? '')));
            if ($kode === '') {
                continue;
            }

            $stokKeys = ['stok', 'stock', 'qty', 'stok_speedshop'];
            if (! $this->cellPresent($data, $stokKeys)) {
                $this->rowsSkipped++;

                continue;
            }

            $qtyRaw = 0;
            foreach ($stokKeys as $key) {
                if (! array_key_exists($key, $data) || $data[$key] === null || $data[$key] === '') {
                    continue;
                }
                $qtyRaw = $data[$key];
                break;
            }
            $qty = max(0, (int) round((float) $qtyRaw));

            $query = Product::query()
                ->forUser()
                ->where('kode_produk', $kode)
                ->forSpeedshopStokCatalog((int) $speedshopId, $this->catalogKind);

            $product = $query->first();
            if (! $product) {
                $this->rowsSkipped++;

                continue;
            }

            $stock = SpeedshopStock::firstOrNew([
                'speedshop_id' => (int) $speedshopId,
                'product_id' => $product->id,
            ]);
            $before = (int) ($stock->jumlah ?? 0);
            $stock->jumlah = $qty;
            $stock->save();

            $delta = $qty - $before;
            if ($delta !== 0) {
                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => $delta > 0 ? 'masuk' : 'keluar',
                    'qty' => abs($delta),
                    'keterangan' => 'Penyesuaian stok import Inventori Produk speedshop (set ke '.$qty.')',
                    'reference' => 'speedshop_inventori_produk_import',
                ]);
            }

            $this->rowsApplied++;
        }
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
