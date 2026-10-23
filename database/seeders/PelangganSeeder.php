<?php

namespace Database\Seeders;

use App\Models\Kendaraan;
use App\Models\Kota;
use App\Models\Pelanggan;
use App\Models\Speedshop;
use Illuminate\Database\Seeder;

class PelangganSeeder extends Seeder
{
    /**
     * Pelanggan untuk Warehouse (transaksi offline: speedshop/reseller/umum) dan Speedshop (work order/transaksi penjualan).
     */
    public function run(): void
    {
        $bandung = Kota::where('nama', 'BANDUNG')->first();
        $depok = Kota::where('nama', 'DEPOK')->first();
        $bogor = Kota::where('nama', 'BOGOR')->first();

        $ss = Speedshop::where('nama', SpeedshopSeeder::NAMA)->first();

        // Pelanggan Warehouse (transaksi offline) — speedshop_id mengikat ke master speedshops (notifikasi & verifikasi)
        $warehouseData = [
            ['tujuan' => 'speedshop', 'nama' => 'Speed Shop Bandung', 'alamat' => 'Jl. Pasir Panjang, Bandung', 'no_hp' => '085353300103', 'kota_id' => $bandung?->id, 'speedshop_id' => $ss?->id],
            ['tujuan' => 'speedshop', 'nama' => 'Speed Shop Depok', 'alamat' => 'Jl. Raya Cisalak KM 31, Depok', 'no_hp' => '085353300102', 'kota_id' => $depok?->id, 'speedshop_id' => null],
            ['tujuan' => 'speedshop', 'nama' => 'Speed Shop Bogor', 'alamat' => 'Jl. Brigjen Saptadji Hadiprawira No. 9, Bogor', 'no_hp' => '081328773881', 'kota_id' => $bogor?->id, 'speedshop_id' => null],
            ['tujuan' => 'reseller', 'nama' => 'Toko Motor Jaya', 'alamat' => 'Jl. Ahmad Yani No. 45, Bandung', 'no_hp' => '081234567801', 'kota_id' => $bandung?->id, 'speedshop_id' => null],
            ['tujuan' => 'reseller', 'nama' => 'Bengkel Sejahtera', 'alamat' => 'Jl. Gatot Subroto 12, Depok', 'no_hp' => '081234567802', 'kota_id' => $depok?->id, 'speedshop_id' => null],
            ['tujuan' => 'umum', 'nama' => 'Budi Santoso', 'alamat' => 'Jl. Melati No. 5, Bandung', 'no_hp' => '082111223344', 'kota_id' => $bandung?->id, 'speedshop_id' => null],
            ['tujuan' => 'umum', 'nama' => 'Siti Aminah', 'alamat' => 'Jl. Anggrek 10, Depok', 'no_hp' => '082122334455', 'kota_id' => $depok?->id, 'speedshop_id' => null],
            ['tujuan' => 'umum', 'nama' => 'Customer Walk-in', 'alamat' => '-', 'no_hp' => '080000000000', 'kota_id' => null, 'speedshop_id' => null],
        ];

        foreach ($warehouseData as $row) {
            Pelanggan::updateOrCreate(
                [
                    'section' => Pelanggan::SECTION_WAREHOUSE,
                    'tujuan' => $row['tujuan'],
                    'nama' => $row['nama'],
                ],
                [
                    'warehouse_id' => null,
                    'section' => Pelanggan::SECTION_WAREHOUSE,
                    'tujuan' => $row['tujuan'],
                    'nama' => $row['nama'],
                    'alamat' => $row['alamat'],
                    'no_hp' => $row['no_hp'],
                    'kota_id' => $row['kota_id'],
                    'speedshop_id' => $row['speedshop_id'] ?? null,
                ]
            );
        }

        // Pelanggan Speedshop (work order / transaksi penjualan) + sample kendaraan
        $speedshopData = [
            ['nama' => 'Budi Santoso', 'no_hp' => '081234567890', 'alamat' => 'Jl. Sudirman No. 10, Bandung', 'kota_id' => $bandung?->id],
            ['nama' => 'Siti Rahayu', 'no_hp' => '082345678901', 'alamat' => 'Jl. Asia Afrika No. 5, Bandung', 'kota_id' => $bandung?->id],
            ['nama' => 'Ahmad Fauzi', 'no_hp' => '083456789012', 'alamat' => 'Jl. Pajajaran No. 15, Bogor', 'kota_id' => $bogor?->id],
            ['nama' => 'Dewi Lestari', 'no_hp' => '084567890123', 'alamat' => 'Jl. Margonda No. 20, Depok', 'kota_id' => $depok?->id],
            ['nama' => 'Rudi Hermawan', 'no_hp' => '085678901234', 'alamat' => 'Jl. Dago No. 8, Bandung', 'kota_id' => $bandung?->id],
        ];

        $kendaraans = [
            ['pelanggan_nama' => 'Budi Santoso', 'nomor_polisi' => 'B 1234 ABC', 'merk' => 'Honda', 'tipe' => 'Beat', 'tahun' => 2022],
            ['pelanggan_nama' => 'Budi Santoso', 'nomor_polisi' => 'B 5678 DEF', 'merk' => 'Yamaha', 'tipe' => 'NMAX', 'tahun' => 2023],
            ['pelanggan_nama' => 'Siti Rahayu', 'nomor_polisi' => 'D 9012 GHI', 'merk' => 'Honda', 'tipe' => 'Vario 160', 'tahun' => 2024],
            ['pelanggan_nama' => 'Ahmad Fauzi', 'nomor_polisi' => 'F 3456 JKL', 'merk' => 'Yamaha', 'tipe' => 'Aerox 155', 'tahun' => 2021],
            ['pelanggan_nama' => 'Dewi Lestari', 'nomor_polisi' => 'B 7890 MNO', 'merk' => 'Honda', 'tipe' => 'PCX 160', 'tahun' => 2023],
        ];

        foreach ($speedshopData as $row) {
            Pelanggan::firstOrCreate(
                [
                    'section' => Pelanggan::SECTION_SPEEDSHOP,
                    'nama' => $row['nama'],
                ],
                [
                    'warehouse_id' => null,
                    'section' => Pelanggan::SECTION_SPEEDSHOP,
                    'tujuan' => null,
                    'nama' => $row['nama'],
                    'alamat' => $row['alamat'],
                    'no_hp' => $row['no_hp'],
                    'kota_id' => $row['kota_id'],
                ]
            );
        }

        foreach ($kendaraans as $k) {
            $pel = Pelanggan::where('section', Pelanggan::SECTION_SPEEDSHOP)->where('nama', $k['pelanggan_nama'])->first();
            if ($pel) {
                Kendaraan::firstOrCreate(
                    ['nomor_polisi' => $k['nomor_polisi']],
                    [
                        'pelanggan_id' => $pel->id,
                        'nomor_polisi' => $k['nomor_polisi'],
                        'merk' => $k['merk'],
                        'tipe' => $k['tipe'],
                        'tahun' => $k['tahun'],
                    ]
                );
            }
        }

        $this->command->info('Seeded: '.count($warehouseData).' pelanggan warehouse, '.count($speedshopData).' pelanggan speedshop + '.count($kendaraans).' kendaraan.');
    }
}
