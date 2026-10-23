<?php

namespace Database\Seeders;

use App\Models\Kota;
use App\Models\Speedshop;
use Illuminate\Database\Seeder;

class SpeedshopSeeder extends Seeder
{
    public const NAMA = 'BENGKEL 77 RACING';

    public function run(): void
    {
        $kota = Kota::query()->where('nama', 'BANDUNG')->first();

        Speedshop::query()->updateOrCreate(
            ['nama' => self::NAMA],
            [
                'no_hp' => null,
                'alamat' => 'Jl. Terusan Kopo Jl. Kp. Pamoyanan No.200 km 13, RT.003/RW.005, Katapang, Kec. Katapang, Kabupaten Bandung, Jawa Barat 40921',
                'kota_id' => $kota?->id,
                'warehouse_id' => null,
                'mutasi_warehouse_id' => null,
            ],
        );

        $this->command->info('Seeded: '.self::NAMA);
    }
}
