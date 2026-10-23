<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('marketplace_orders')) {
            return;
        }

        DB::table('marketplace_orders')
            ->where('status', 'diproses')
            ->whereNotNull('packing_scanned_at')
            ->update([
                'status' => 'menunggu_dikirim',
                'packed_at' => DB::raw('COALESCE(packed_at, packing_scanned_at)'),
            ]);

        DB::table('marketplace_orders')
            ->where('status', 'menunggu_verifikasi')
            ->update([
                'status' => 'diproses',
                'packed_at' => null,
            ]);

        DB::table('marketplace_orders')
            ->where('status', 'diproses')
            ->update(['packed_at' => null]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('marketplace_orders')) {
            return;
        }

        DB::table('marketplace_orders')
            ->where('status', 'menunggu_dikirim')
            ->update(['status' => 'diproses']);
    }
};
