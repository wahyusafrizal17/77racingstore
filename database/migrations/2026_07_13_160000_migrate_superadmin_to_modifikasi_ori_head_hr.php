<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('section', 'admin')
            ->where('role', 'SuperAdmin')
            ->update([
                'section' => User::SECTION_MODIFIKASI_ORI,
                'role' => User::ROLE_MODIFIKASI_HEAD_HR,
                'warehouse_id' => null,
                'speedshop_id' => null,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('section', User::SECTION_MODIFIKASI_ORI)
            ->where('role', User::ROLE_MODIFIKASI_HEAD_HR)
            ->whereNull('warehouse_id')
            ->whereNull('speedshop_id')
            ->update([
                'section' => 'admin',
                'role' => 'SuperAdmin',
                'updated_at' => now(),
            ]);
    }
};
