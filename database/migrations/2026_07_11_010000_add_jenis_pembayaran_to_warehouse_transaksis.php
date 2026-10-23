<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_transaksis', function (Blueprint $table) {
            if (! Schema::hasColumn('warehouse_transaksis', 'jenis_pembayaran')) {
                $table->string('jenis_pembayaran', 32)->default('hutang')->after('catatan');
            }
        });
    }

    public function down(): void
    {
        Schema::table('warehouse_transaksis', function (Blueprint $table) {
            if (Schema::hasColumn('warehouse_transaksis', 'jenis_pembayaran')) {
                $table->dropColumn('jenis_pembayaran');
            }
        });
    }
};
