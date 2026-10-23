<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UserRoleMigration
{
    public static function run(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        DB::table('users')->where('section', 'produksi')->update([
            'section' => User::SECTION_MODIFIKASI_ORI,
            'role' => User::ROLE_MODIFIKASI_STAF_PRODUKSI,
        ]);

        DB::table('users')->where('section', 'warehouse')->update([
            'section' => User::SECTION_MODIFIKASI_ORI,
            'role' => User::ROLE_MODIFIKASI_STAF_PROCESSING_WAREHOUSE,
        ]);

        DB::table('users')
            ->where('section', 'speedshop')
            ->where('role', 'Outlet')
            ->update(['role' => 'Staf']);

        DB::table('users')
            ->where('section', 'speedshop')
            ->where('role', 'Manager Regional')
            ->update(['role' => User::ROLE_SPEEDSHOP_HEAD_AREA]);

        DB::table('users')
            ->where('section', 'admin')
            ->where('role', 'SuperAdmin')
            ->update([
                'section' => User::SECTION_MODIFIKASI_ORI,
                'role' => User::ROLE_MODIFIKASI_HEAD_HR,
            ]);
    }

    public static function fixModifikasiOriLegacyRoles(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('warehouses') || ! Schema::hasColumn('users', 'warehouse_id')) {
            return;
        }

        $warehouseId = DB::table('warehouses')->orderBy('id')->value('id');
        if (! $warehouseId) {
            return;
        }

        DB::table('users')
            ->where('section', User::SECTION_MODIFIKASI_ORI)
            ->whereIn('role', AdminUserPayload::modifikasiOriWarehouseRoles())
            ->whereNull('warehouse_id')
            ->update(['warehouse_id' => $warehouseId]);
    }
}
