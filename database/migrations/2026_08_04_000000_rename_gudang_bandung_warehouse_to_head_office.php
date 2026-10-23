<?php

use App\Models\Warehouse;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('warehouses')
            ->where('nama', 'Gudang Bandung')
            ->update(['nama' => Warehouse::HEAD_OFFICE_NAME]);
    }

    public function down(): void
    {
        DB::table('warehouses')
            ->where('nama', Warehouse::HEAD_OFFICE_NAME)
            ->update(['nama' => 'Gudang Bandung']);
    }
};
