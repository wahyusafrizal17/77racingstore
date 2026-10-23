<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Produk untuk Head Office (satu warehouse). Dipakai warehouse & speedshop.
     */
    public function run(): void
    {
        $warehouse = Warehouse::headOffice();
        if (! $warehouse) {
            $this->command->warn('Warehouse "'.Warehouse::HEAD_OFFICE_NAME.'" belum ada. Jalankan WarehouseSeeder terlebih dahulu.');

            return;
        }

        $productTemplates = [
            ['kode_produk' => 'CH-K16-M1', 'nama_produk' => 'CUSTOM LUBANG-MANGKOK GANDA CUSTOM', 'category' => 'Sparepart', 'brand' => '77racing', 'harga_pembelian' => 93375, 'harga_jual' => 210000],
            ['kode_produk' => 'CH-KVV-M1', 'nama_produk' => 'CUSTOM LUBANG-MANGKOK GANDA CUSTOM', 'category' => 'Sparepart', 'brand' => '77racing', 'harga_pembelian' => 90250, 'harga_jual' => 210000],
            ['kode_produk' => 'SP-BRK-001', 'nama_produk' => 'KAMPAS REM DEPAN RACING', 'category' => 'Sparepart', 'brand' => 'Speed Shop', 'harga_pembelian' => 45000, 'harga_jual' => 85000],
            ['kode_produk' => 'SP-BRK-002', 'nama_produk' => 'KAMPAS REM BELAKANG RACING', 'category' => 'Sparepart', 'brand' => 'Speed Shop', 'harga_pembelian' => 40000, 'harga_jual' => 75000],
            ['kode_produk' => 'SP-CLT-001', 'nama_produk' => 'PER KOPLING RACING 1500 RPM', 'category' => 'Sparepart', 'brand' => '77racing', 'harga_pembelian' => 65000, 'harga_jual' => 130000],
            ['kode_produk' => 'SP-RLR-001', 'nama_produk' => 'ROLLER SET 8 GRAM', 'category' => 'Sparepart', 'brand' => '77racing', 'harga_pembelian' => 35000, 'harga_jual' => 70000],
            ['kode_produk' => 'SP-BLT-001', 'nama_produk' => 'V-BELT RACING KEVLAR', 'category' => 'Sparepart', 'brand' => 'Speed Shop', 'harga_pembelian' => 120000, 'harga_jual' => 250000],
            ['kode_produk' => 'SP-BRG-001', 'nama_produk' => 'BEARING RODA DEPAN SET', 'category' => 'Sparepart', 'brand' => 'Honda', 'harga_pembelian' => 55000, 'harga_jual' => 110000],
            ['kode_produk' => 'OL-MTR-001', 'nama_produk' => 'OLI MESIN 10W-40 MATIC 0.8L', 'category' => 'Oli', 'brand' => 'Honda', 'harga_pembelian' => 38000, 'harga_jual' => 55000],
            ['kode_produk' => 'OL-MTR-002', 'nama_produk' => 'OLI MESIN 10W-40 SPORT 1L', 'category' => 'Oli', 'brand' => 'Yamaha', 'harga_pembelian' => 52000, 'harga_jual' => 75000],
            ['kode_produk' => 'OL-GRB-001', 'nama_produk' => 'OLI GARDAN MATIC 120ML', 'category' => 'Oli', 'brand' => 'Honda', 'harga_pembelian' => 22000, 'harga_jual' => 35000],
        ];

        $categories = Category::query()->forWarehouse()->pluck('id', 'nama');
        $brands = Brand::query()->forWarehouse()->pluck('id', 'nama');

        foreach ($productTemplates as $p) {
            Product::updateOrCreate(
                [
                    'kode_produk' => $p['kode_produk'],
                    'warehouse_id' => $warehouse->id,
                ],
                [
                    'nama_produk' => $p['nama_produk'],
                    'category_id' => $categories[$p['category']] ?? null,
                    'brand_id' => $brands[$p['brand']] ?? null,
                    'harga_pembelian' => $p['harga_pembelian'],
                    'harga_jual' => $p['harga_jual'],
                    'warehouse_id' => $warehouse->id,
                    'jumlah' => 0,
                ]
            );
        }

        $this->command->info('Seeded: '.count($productTemplates).' produk di '.Warehouse::HEAD_OFFICE_NAME.'.');
    }
}
