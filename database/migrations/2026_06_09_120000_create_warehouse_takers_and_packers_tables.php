<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transaksi_online')) {
            return;
        }

        Schema::table('transaksi_online', function (Blueprint $table) {
            if (! Schema::hasColumn('transaksi_online', 'warehouse_taker_id')) {
                $table->unsignedBigInteger('warehouse_taker_id')->nullable()->after('warehouse_reseller_id');
            }
        });

        Schema::table('transaksi_online', function (Blueprint $table) {
            if (! Schema::hasColumn('transaksi_online', 'warehouse_packer_id')) {
                $table->unsignedBigInteger('warehouse_packer_id')->nullable()->after('warehouse_taker_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('transaksi_online')) {
            return;
        }

        Schema::table('transaksi_online', function (Blueprint $table) {
            if (Schema::hasColumn('transaksi_online', 'warehouse_packer_id')) {
                $table->dropColumn('warehouse_packer_id');
            }
            if (Schema::hasColumn('transaksi_online', 'warehouse_taker_id')) {
                $table->dropColumn('warehouse_taker_id');
            }
        });
    }
};
