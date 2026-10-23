<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi_online', function (Blueprint $table) {
            if (! Schema::hasColumn('transaksi_online', 'warehouse_reseller_id')) {
                $table->unsignedBigInteger('warehouse_reseller_id')
                    ->nullable()
                    ->after('warehouse_id');
            }
            if (! Schema::hasColumn('transaksi_online', 'no_urut_harian')) {
                $table->unsignedInteger('no_urut_harian')->nullable()->after('warehouse_reseller_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transaksi_online', function (Blueprint $table) {
            if (Schema::hasColumn('transaksi_online', 'no_urut_harian')) {
                $table->dropColumn('no_urut_harian');
            }
            if (Schema::hasColumn('transaksi_online', 'warehouse_reseller_id')) {
                $table->dropColumn('warehouse_reseller_id');
            }
        });
    }
};
