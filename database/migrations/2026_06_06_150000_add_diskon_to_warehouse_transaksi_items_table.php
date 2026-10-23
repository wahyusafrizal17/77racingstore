<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_transaksi_items', function (Blueprint $table) {
            $table->unsignedBigInteger('diskon')->default(0)->after('harga_pembelian');
        });
    }

    public function down(): void
    {
        Schema::table('warehouse_transaksi_items', function (Blueprint $table) {
            $table->dropColumn('diskon');
        });
    }
};
