<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('mutasis') || ! Schema::hasColumn('mutasis', 'destination_type')) {
            return;
        }

        DB::statement("ALTER TABLE mutasis MODIFY destination_type ENUM('warehouse', 'speedshop', 'produksi') NOT NULL DEFAULT 'warehouse'");
    }

    public function down(): void
    {
        if (! Schema::hasTable('mutasis') || ! Schema::hasColumn('mutasis', 'destination_type')) {
            return;
        }

        DB::table('mutasis')->where('destination_type', 'produksi')->update(['destination_type' => 'warehouse']);

        DB::statement("ALTER TABLE mutasis MODIFY destination_type ENUM('warehouse', 'speedshop') NOT NULL DEFAULT 'warehouse'");
    }
};
