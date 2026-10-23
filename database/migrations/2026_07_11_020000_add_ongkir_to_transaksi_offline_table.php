<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi_offline', function (Blueprint $table) {
            if (! Schema::hasColumn('transaksi_offline', 'ongkir')) {
                $table->unsignedBigInteger('ongkir')->default(0)->after('jenis_pembayaran');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transaksi_offline', function (Blueprint $table) {
            if (Schema::hasColumn('transaksi_offline', 'ongkir')) {
                $table->dropColumn('ongkir');
            }
        });
    }
};
