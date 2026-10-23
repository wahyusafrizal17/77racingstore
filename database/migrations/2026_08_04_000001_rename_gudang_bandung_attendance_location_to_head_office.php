<?php

use App\Models\Warehouse;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('attendance_locations')
            ->where('name', 'Gudang Bandung')
            ->update(['name' => Warehouse::HEAD_OFFICE_NAME]);

        DB::table('attendance_locations')
            ->where('code', 'warehouse')
            ->update(['name' => Warehouse::HEAD_OFFICE_NAME]);
    }

    public function down(): void
    {
        // Tidak mengembalikan ke "Gudang Bandung" — nama lama sudah diganti di seluruh sistem.
    }
};
