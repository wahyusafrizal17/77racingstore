<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_transaksi_items', function (Blueprint $table) {
            $table->string('item_type', 30)->default('product')->after('supplier_id');
            $table->foreignId('perlengkapan_packing_id')->nullable()->after('product_id')
                ->constrained('perlengkapan_packings')->nullOnDelete();
        });

        Schema::table('warehouse_transaksi_items', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('warehouse_transaksi_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('perlengkapan_packing_id');
            $table->dropColumn('item_type');
        });

        Schema::table('warehouse_transaksi_items', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable(false)->change();
        });
    }
};
