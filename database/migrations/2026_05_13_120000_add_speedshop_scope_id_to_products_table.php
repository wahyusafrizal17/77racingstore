<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cabang Speedshop satu gudang bisa punya SKU dengan kode sama (baris produk terpisah).
     * speedshop_scope_id = 0: katalog shared (master warehouse / legacy); >0: milik outlet tersebut.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('speedshop_scope_id')->default(0)->after('warehouse_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['kode_produk', 'warehouse_id']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unique(
                ['warehouse_id', 'speedshop_scope_id', 'kode_produk'],
                'products_warehouse_scope_kode_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_warehouse_scope_kode_unique');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unique(['kode_produk', 'warehouse_id']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('speedshop_scope_id');
        });
    }
};
