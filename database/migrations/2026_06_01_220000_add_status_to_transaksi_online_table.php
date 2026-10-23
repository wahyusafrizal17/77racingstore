<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transaksi_online')) {
            return;
        }

        if (! Schema::hasColumn('transaksi_online', 'status')) {
            Schema::table('transaksi_online', function (Blueprint $table) {
                $table->string('status', 20)->default('selesai')->after('warehouse_id');
            });

            DB::table('transaksi_online')->whereNull('status')->update(['status' => 'selesai']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('transaksi_online') && Schema::hasColumn('transaksi_online', 'status')) {
            Schema::table('transaksi_online', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};
