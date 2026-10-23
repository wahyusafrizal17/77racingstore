<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi_penjualan', function (Blueprint $table) {
            $table->foreignId('pelanggan_id')->nullable()->after('warehouse_id')->constrained('pelanggan')->nullOnDelete();
            $table->foreignId('kendaraan_id')->nullable()->after('pelanggan_id')->constrained('kendaraans')->nullOnDelete();
            $table->date('tanggal_masuk')->nullable()->after('kendaraan_id');
            $table->unsignedInteger('kilometer')->nullable()->after('tanggal_masuk');
            $table->string('sumber_kedatangan')->nullable()->after('kilometer');
            $table->text('keluhan')->nullable()->after('sumber_kedatangan');
        });
    }

    public function down(): void
    {
        Schema::table('transaksi_penjualan', function (Blueprint $table) {
            $table->dropForeign(['pelanggan_id']);
            $table->dropForeign(['kendaraan_id']);
            $table->dropColumn(['tanggal_masuk', 'kilometer', 'sumber_kedatangan', 'keluhan']);
        });
    }
};
