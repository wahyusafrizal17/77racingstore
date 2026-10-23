<?php

namespace Database\Seeders;

use App\Models\Kota;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    /**
     * Satu gudang pusat (Head Office) di Bandung — produksi & supply ke speedshop.
     */
    public function run(): void
    {
        $bandung = Kota::where('nama', 'BANDUNG')->first();
        if (! $bandung) {
            $this->command->error('Kota BANDUNG belum ada. Jalankan KotaSeeder dulu.');

            return;
        }

        $legacy = Warehouse::query()->where('nama', 'Gudang Bandung')->first();
        if ($legacy) {
            $legacy->update(['nama' => Warehouse::HEAD_OFFICE_NAME]);
        }

        $headOfficeAddress = 'Jl. Pasir Panjang, Cilampeni, Kec. Katapang, Kabupaten Bandung, Jawa Barat 40921';

        Warehouse::query()->updateOrCreate(
            ['nama' => Warehouse::HEAD_OFFICE_NAME],
            [
                'no_hp' => '085353300103',
                'alamat' => $headOfficeAddress,
                'kota_id' => $bandung->id,
            ]
        );

        $this->command->info('Seeded: 1 warehouse ('.Warehouse::HEAD_OFFICE_NAME.').');
    }
}
