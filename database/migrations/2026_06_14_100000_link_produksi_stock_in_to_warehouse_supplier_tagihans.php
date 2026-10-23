<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('warehouse_supplier_tagihans')) {
            return;
        }

        Schema::table('warehouse_supplier_tagihans', function (Blueprint $table) {
            if (Schema::hasColumn('warehouse_supplier_tagihans', 'warehouse_transaksi_id')) {
                $table->unsignedBigInteger('warehouse_transaksi_id')->nullable()->change();
            }

            if (! Schema::hasColumn('warehouse_supplier_tagihans', 'stock_in_id')) {
                $table->unsignedBigInteger('stock_in_id')->nullable()->after('warehouse_transaksi_id');
                $table->foreign('stock_in_id', 'wst_stock_in_fk')
                    ->references('id')->on('stock_ins')
                    ->cascadeOnDelete();
                $table->unique('stock_in_id', 'wst_stock_in_id_uniq');
            }
        });

        if (! Schema::hasTable('warehouse_supplier_tagihan_items')) {
            return;
        }

        Schema::table('warehouse_supplier_tagihan_items', function (Blueprint $table) {
            if (! Schema::hasColumn('warehouse_supplier_tagihan_items', 'stock_in_item_id')) {
                $table->unsignedBigInteger('stock_in_item_id')->nullable()->after('warehouse_transaksi_item_id');
                $table->foreign('stock_in_item_id', 'wsti_stock_in_item_fk')
                    ->references('id')->on('stock_in_items')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('warehouse_supplier_tagihan_items')
            && Schema::hasColumn('warehouse_supplier_tagihan_items', 'stock_in_item_id')) {
            Schema::table('warehouse_supplier_tagihan_items', function (Blueprint $table) {
                $table->dropForeign('wsti_stock_in_item_fk');
                $table->dropColumn('stock_in_item_id');
            });
        }

        if (! Schema::hasTable('warehouse_supplier_tagihans')) {
            return;
        }

        Schema::table('warehouse_supplier_tagihans', function (Blueprint $table) {
            if (Schema::hasColumn('warehouse_supplier_tagihans', 'stock_in_id')) {
                $table->dropForeign('wst_stock_in_fk');
                $table->dropUnique('wst_stock_in_id_uniq');
                $table->dropColumn('stock_in_id');
            }
        });
    }
};
