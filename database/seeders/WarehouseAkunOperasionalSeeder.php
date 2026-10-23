<?php

namespace Database\Seeders;

use App\Models\AkunOperasional;
use App\Models\Warehouse;
use App\Support\WarehouseLabaRugiChannels;
use Illuminate\Database\Seeder;

class WarehouseAkunOperasionalSeeder extends Seeder
{
    /**
     * Master akun laba rugi warehouse per gudang (5.2 marketplace, 5.3 iklan, 5.4 operasional, 5.5 non operasional).
     */
    public function run(): void
    {
        $accounts = WarehouseLabaRugiChannels::seederAccounts();

        Warehouse::query()->orderBy('id')->each(function (Warehouse $warehouse) use ($accounts) {
            foreach ($accounts as [$noAkun, $namaAkun]) {
                AkunOperasional::query()->updateOrCreate(
                    [
                        'warehouse_id' => $warehouse->id,
                        'no_akun' => $noAkun,
                    ],
                    [
                        'speedshop_id' => null,
                        'nama_akun' => $namaAkun,
                    ],
                );
            }
        });
    }
}
