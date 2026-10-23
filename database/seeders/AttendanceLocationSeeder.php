<?php

namespace Database\Seeders;

use App\Models\AttendanceLocation;
use App\Models\Speedshop;
use Illuminate\Database\Seeder;

class AttendanceLocationSeeder extends Seeder
{
    public function run(): void
    {
        $speedshop = Speedshop::query()->orderBy('id')->first();

        AttendanceLocation::query()->updateOrCreate(
            ['code' => AttendanceLocation::CODE_BANDUNG],
            [
                'name' => $speedshop?->nama ?? 'BENGKEL 77 RACING',
                'locatable_type' => $speedshop ? Speedshop::class : null,
                'locatable_id' => $speedshop?->id,
                'radius_meters' => 50,
                'is_active' => true,
            ],
        );
    }
}
