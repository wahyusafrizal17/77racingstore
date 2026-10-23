<?php

namespace Database\Seeders;

use App\Models\Mekanik;
use Illuminate\Database\Seeder;

class MekanikSeeder extends Seeder
{
    /**
     * Mekanik untuk speedshop (work order).
     */
    public function run(): void
    {
        $data = [
            ['nama' => 'Joko Widodo', 'no_hp' => '081111222333', 'spesialisasi' => 'Mesin', 'status' => 'aktif'],
            ['nama' => 'Agus Prasetyo', 'no_hp' => '082222333444', 'spesialisasi' => 'Kelistrikan', 'status' => 'aktif'],
            ['nama' => 'Bambang Surya', 'no_hp' => '083333444555', 'spesialisasi' => 'Body & Cat', 'status' => 'aktif'],
            ['nama' => 'Deni Irawan', 'no_hp' => '084444555666', 'spesialisasi' => 'AC & Cooling', 'status' => 'aktif'],
            ['nama' => 'Eko Saputra', 'no_hp' => '085555666777', 'spesialisasi' => 'Transmisi', 'status' => 'aktif'],
        ];

        foreach ($data as $m) {
            Mekanik::firstOrCreate(['nama' => $m['nama']], $m);
        }

        $this->command->info('Seeded: ' . count($data) . ' mekanik.');
    }
}
