<?php

namespace Database\Seeders;

use App\Models\AkunOperasional;
use Illuminate\Database\Seeder;

class AkunOperasionalSeeder extends Seeder
{
    /**
     * Master akun operasional (No Akun + Nama Akun).
     */
    public function run(): void
    {
        $rows = [
            ['5.1.1', 'Sparepart'],
            ['5.1.2', 'Oli'],
            ['5.2.1', 'Gaji Karyawan'],
            ['5.2.2', 'THR'],
            ['5.2.3', 'Biaya Operasional Kantor'],
            ['5.2.4', 'Promosi'],
            ['5.2.5', 'Merchandise'],
            ['5.2.6', 'Pembelian Tools & Perlengkapan'],
            ['5.2.7', 'Perawatan Tools & Perlengkapan'],
            ['5.2.8', 'Depresiasi Tools & Pengembangan'],
            ['5.2.9', 'Renovasi Bangunan'],
            ['5.2.10', 'Sewa Bangunan'],
            ['5.2.11', 'Pembelian Part Luar'],
            ['5.2.12', 'Jasa Pekerjaan Luar'],
            ['5.2.13', 'Biaya Admin Bank'],
            ['5.2.14', 'Reimbursement'],
            ['5.3.1', 'Biaya Kerugian'],
            ['5.3.2', 'Pengembalian Dana'],
            ['5.3.3', 'Diskon'],
        ];

        foreach ($rows as [$noAkun, $namaAkun]) {
            AkunOperasional::query()->updateOrCreate(
                ['no_akun' => $noAkun],
                ['nama_akun' => $namaAkun],
            );
        }
    }
}
