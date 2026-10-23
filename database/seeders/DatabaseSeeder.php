<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seeder aman production — idempotent, tidak menghapus data operasional.
     *
     * Hanya menyinkronkan referensi: kota, gudang, cabang speedshop, master akun operasional.
     * TIDAK menyentuh: transaksi, stok, work order, pesanan reseller/marketplace, invoice, user.
     *
     * Setelah migrate di server:
     *   php artisan migrate --force
     *   php artisan db:seed --force
     *
     * Akun demo (local): php artisan db:seed --class=DivisionRoleUsersSeeder --force
     */
    public function run(): void
    {
        $this->call([
            KotaSeeder::class,
            SpeedshopSeeder::class,
            AttendanceLocationSeeder::class,
            AkunOperasionalSeeder::class,
            DivisionRoleUsersSeeder::class,
        ]);
    }
}
