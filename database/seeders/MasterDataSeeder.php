<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\JasaServis;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    /**
     * Master data: kategori (per section produksi / gudang / speedshop), brand, jasa servis.
     * Supplier diisi oleh SupplierSeeder. Kota & Warehouse diisi oleh KotaSeeder & WarehouseSeeder.
     */
    public function run(): void
    {
        $categoryNames = ['Sparepart', 'Oli'];
        $brandNames = ['77racing', 'Speed Shop', 'Honda', 'Yamaha', 'Kawasaki'];

        $jasaServisList = [
            ['nama' => 'BENERIN TALI GAS', 'biaya' => 30000],
            ['nama' => 'BENSIN 1L', 'biaya' => 12000],
            ['nama' => 'BLEADING', 'biaya' => 35000],
            ['nama' => 'BONGKAR PASANG FUEL PUMP', 'biaya' => 30000],
            ['nama' => 'CEK KELISTRIKAN', 'biaya' => 30000],
            ['nama' => 'CEK REM', 'biaya' => 20000],
            ['nama' => 'FULL KOMPLIT SERVICE 110CC', 'biaya' => 260000],
            ['nama' => 'FULL KOMPLIT SERVICE 125CC +', 'biaya' => 270000],
            ['nama' => 'FULL SERVICE', 'biaya' => 175000],
        ];

        foreach ($categoryNames as $nama) {
            foreach ([Category::SECTION_WAREHOUSE, Category::SECTION_PRODUKSI, Category::SECTION_SPEEDSHOP] as $section) {
                Category::firstOrCreate(
                    ['nama' => $nama, 'section' => $section],
                    ['section' => $section],
                );
            }
        }

        foreach ($brandNames as $nama) {
            Brand::firstOrCreate(['nama' => $nama]);
        }

        foreach ($jasaServisList as $js) {
            JasaServis::firstOrCreate(
                ['nama' => $js['nama']],
                $js
            );
        }

        $this->command->info('Seeded: '.count($categoryNames).' categories, '.count($brandNames).' brands, '.count($jasaServisList).' jasa servis.');
    }
}
