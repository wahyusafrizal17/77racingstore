<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_transaksis', function (Blueprint $table) {
            if (! Schema::hasColumn('warehouse_transaksis', 'ppn')) {
                $table->unsignedBigInteger('ppn')->default(0)->after('catatan');
            }
            if (! Schema::hasColumn('warehouse_transaksis', 'ongkir')) {
                $table->unsignedBigInteger('ongkir')->default(0)->after('ppn');
            }
        });
    }

    public function down(): void
    {
        Schema::table('warehouse_transaksis', function (Blueprint $table) {
            if (Schema::hasColumn('warehouse_transaksis', 'ongkir')) {
                $table->dropColumn('ongkir');
            }
            if (Schema::hasColumn('warehouse_transaksis', 'ppn')) {
                $table->dropColumn('ppn');
            }
        });
    }
};
