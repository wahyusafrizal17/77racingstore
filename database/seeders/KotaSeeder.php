<?php

namespace Database\Seeders;

use App\Models\Kota;
use Illuminate\Database\Seeder;

class KotaSeeder extends Seeder
{
    /**
     * Kota untuk aplikasi: Produksi & Warehouse di Bandung, Speedshop di Bandung/Depok/Bogor.
     */
    public function run(): void
    {
        $kotas = ['BANDUNG', 'DEPOK', 'BOGOR', 'JAKARTA', 'BEKASI'];

        foreach ($kotas as $nama) {
            Kota::firstOrCreate(['nama' => $nama]);
        }

        $this->command->info('Seeded: '.count($kotas).' kota (BANDUNG, DEPOK, BOGOR, JAKARTA, BEKASI).');
    }
}
