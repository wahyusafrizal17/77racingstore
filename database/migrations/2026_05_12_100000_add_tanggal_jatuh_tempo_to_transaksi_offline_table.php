<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transaksi_offline')) {
            return;
        }
        Schema::table('transaksi_offline', function (Blueprint $table) {
            if (! Schema::hasColumn('transaksi_offline', 'tanggal_jatuh_tempo')) {
                $table->date('tanggal_jatuh_tempo')->nullable()->after('jenis_pembayaran');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('transaksi_offline')) {
            return;
        }
        Schema::table('transaksi_offline', function (Blueprint $table) {
            if (Schema::hasColumn('transaksi_offline', 'tanggal_jatuh_tempo')) {
                $table->dropColumn('tanggal_jatuh_tempo');
            }
        });
    }
};
