<?php

namespace Database\Seeders;

use App\Models\Kota;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

/**
 * Supplier per bagian: Produksi, Warehouse, dan Speedshop (data terpisah).
 * Urutan supplier Produksi mengikuti kebutuhan ProduksiMasterSeeder (3 supplier pertama dipakai bahan baku/kemasan).
 */
class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedProduksi();
        $this->seedWarehouse();
        $this->seedSpeedshop();

        $this->command->info('Seeded suppliers: Produksi, Warehouse, Speedshop.');
    }

    private function seedProduksi(): void
    {
        $rows = [
            ['nama' => 'PT Sumber Jaya Motor', 'no_hp' => '08111222333', 'email' => 'admin@sumberjayamotor.co.id', 'alamat' => 'Jl. Industri No. 10, Bandung', 'kota' => 'BANDUNG', 'jenis_rekening' => 'BCA', 'nama_rekening' => 'PT Sumber Jaya Motor', 'nomor_rekening' => '1234567890'],
            ['nama' => 'CV Mitra Sparepart', 'no_hp' => '08222333444', 'email' => 'cs@mitrasparepart.com', 'alamat' => 'Jl. Otista No. 25, Bandung', 'kota' => 'BANDUNG', 'jenis_rekening' => 'Mandiri', 'nama_rekening' => 'CV Mitra Sparepart', 'nomor_rekening' => '9876543210'],
            ['nama' => 'UD Berkah Oli', 'no_hp' => '08333444555', 'email' => 'berkah.oli@gmail.com', 'alamat' => 'Jl. Raya Depok No. 5', 'kota' => 'DEPOK', 'jenis_rekening' => 'BRI', 'nama_rekening' => 'UD Berkah Oli', 'nomor_rekening' => '5555666677'],
            ['nama' => 'Toko Bearing Jaya', 'no_hp' => '08444555666', 'email' => 'info@bearingjaya.co.id', 'alamat' => 'Jl. Soekarno Hatta No. 88, Bandung', 'kota' => 'BANDUNG', 'jenis_rekening' => 'BCA', 'nama_rekening' => 'Toko Bearing Jaya', 'nomor_rekening' => '1111222233'],
            ['nama' => 'CV Abadi Motor Parts', 'no_hp' => '08555666777', 'email' => 'order@abadi-motor.co.id', 'alamat' => 'Jl. Pajajaran No. 12, Bogor', 'kota' => 'BOGOR', 'jenis_rekening' => 'BNI', 'nama_rekening' => 'CV Abadi Motor Parts', 'nomor_rekening' => '4444555566'],
        ];

        foreach ($rows as $s) {
            $kotaModel = Kota::where('nama', $s['kota'])->first();
            Supplier::updateOrCreate(
                ['nama' => $s['nama'], 'section' => Supplier::SECTION_PRODUKSI],
                [
                    'no_hp' => $s['no_hp'],
                    'email' => $s['email'] ?? null,
                    'alamat' => $s['alamat'],
                    'kota_id' => $kotaModel?->id,
                    'jenis_rekening' => $s['jenis_rekening'] ?? null,
                    'nama_rekening' => $s['nama_rekening'] ?? null,
                    'nomor_rekening' => $s['nomor_rekening'] ?? null,
                ]
            );
        }
    }

    private function seedWarehouse(): void
    {
        $rows = [
            ['nama' => 'PT Gudang Utama Sparepart', 'no_hp' => '08120001111', 'email' => 'purchase@gudangutama.id', 'alamat' => 'Jl. Gatot Subroto No. 100, Bandung', 'kota' => 'BANDUNG', 'jenis_rekening' => 'BCA', 'nama_rekening' => 'PT Gudang Utama Sparepart', 'nomor_rekening' => '7011223344'],
            ['nama' => 'CV Distribusi Part Nasional', 'no_hp' => '08120002222', 'email' => 'order@dpn-distribution.co.id', 'alamat' => 'Jl. Soekarno-Hatta KM 3, Bandung', 'kota' => 'BANDUNG', 'jenis_rekening' => 'Mandiri', 'nama_rekening' => 'CV Distribusi Part Nasional', 'nomor_rekening' => '7022334455'],
            ['nama' => 'UD Stok Motor Warehouse', 'no_hp' => '08120003333', 'email' => 'stok@udmotorwh.id', 'alamat' => 'Jl. Raya Bogor KM 28, Depok', 'kota' => 'DEPOK', 'jenis_rekening' => 'BRI', 'nama_rekening' => 'UD Stok Motor Warehouse', 'nomor_rekening' => '7033445566'],
            ['nama' => 'Toko Oli & Part Grosir BD', 'no_hp' => '08120004444', 'email' => 'grosir@opgb-bandung.com', 'alamat' => 'Jl. Asia Afrika No. 55, Bandung', 'kota' => 'BANDUNG', 'jenis_rekening' => 'BNI', 'nama_rekening' => 'Toko Oli & Part Grosir BD', 'nomor_rekening' => '7044556677'],
            ['nama' => 'PT Import Part Center', 'no_hp' => '08120005555', 'email' => 'import@ipc-logistics.id', 'alamat' => 'Kawasan Industri Cimahi, Bogor', 'kota' => 'BOGOR', 'jenis_rekening' => 'BCA', 'nama_rekening' => 'PT Import Part Center', 'nomor_rekening' => '7055667788'],
        ];

        foreach ($rows as $s) {
            $kotaModel = Kota::where('nama', $s['kota'])->first();
            Supplier::updateOrCreate(
                ['nama' => $s['nama'], 'section' => Supplier::SECTION_WAREHOUSE],
                [
                    'no_hp' => $s['no_hp'],
                    'email' => $s['email'] ?? null,
                    'alamat' => $s['alamat'],
                    'kota_id' => $kotaModel?->id,
                    'jenis_rekening' => $s['jenis_rekening'] ?? null,
                    'nama_rekening' => $s['nama_rekening'] ?? null,
                    'nomor_rekening' => $s['nomor_rekening'] ?? null,
                ]
            );
        }
    }

    private function seedSpeedshop(): void
    {
        $rows = [
            ['nama' => 'CV Bengkel Supply Jaya', 'no_hp' => '08130001111', 'email' => 'supply@bengkelsupply.id', 'alamat' => 'Jl. Sudirman No. 88, Bandung', 'kota' => 'BANDUNG', 'jenis_rekening' => 'BCA', 'nama_rekening' => 'CV Bengkel Supply Jaya', 'nomor_rekening' => '8011223344'],
            ['nama' => 'UD Part Racing & Oli', 'no_hp' => '08130002222', 'email' => 'order@partracing-oli.com', 'alamat' => 'Jl. Margonda Raya, Depok', 'kota' => 'DEPOK', 'jenis_rekening' => 'Mandiri', 'nama_rekening' => 'UD Part Racing & Oli', 'nomor_rekening' => '8022334455'],
            ['nama' => 'Toko Fast Moving Part SS', 'no_hp' => '08130003333', 'email' => 'fast@speedshop-part.id', 'alamat' => 'Jl. Pajajaran No. 200, Bogor', 'kota' => 'BOGOR', 'jenis_rekening' => 'BRI', 'nama_rekening' => 'Toko Fast Moving Part SS', 'nomor_rekening' => '8033445566'],
            ['nama' => 'PT Retail Motor Accessories', 'no_hp' => '08130004444', 'email' => 'retail@pma-speedshop.id', 'alamat' => 'Jl. Dago No. 15, Bandung', 'kota' => 'BANDUNG', 'jenis_rekening' => 'BNI', 'nama_rekening' => 'PT Retail Motor Accessories', 'nomor_rekening' => '8044556677'],
            ['nama' => 'CV Oli & Chemical Service', 'no_hp' => '08130005555', 'email' => 'chemical@ocs-bengkel.id', 'alamat' => 'Jl. Cinere No. 10, Depok', 'kota' => 'DEPOK', 'jenis_rekening' => 'BCA', 'nama_rekening' => 'CV Oli & Chemical Service', 'nomor_rekening' => '8055667788'],
        ];

        foreach ($rows as $s) {
            $kotaModel = Kota::where('nama', $s['kota'])->first();
            Supplier::updateOrCreate(
                ['nama' => $s['nama'], 'section' => Supplier::SECTION_SPEEDSHOP],
                [
                    'no_hp' => $s['no_hp'],
                    'email' => $s['email'] ?? null,
                    'alamat' => $s['alamat'],
                    'kota_id' => $kotaModel?->id,
                    'jenis_rekening' => $s['jenis_rekening'] ?? null,
                    'nama_rekening' => $s['nama_rekening'] ?? null,
                    'nomor_rekening' => $s['nomor_rekening'] ?? null,
                ]
            );
        }
    }
}
