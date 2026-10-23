<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('warehouse_supplier_tagihans')) {
            Schema::create('warehouse_supplier_tagihans', function (Blueprint $table) {
                $table->id();
                $table->string('kode', 100);
                $table->unsignedBigInteger('warehouse_transaksi_id');
                $table->unsignedBigInteger('warehouse_id');
                $table->unsignedBigInteger('supplier_id');
                $table->unsignedBigInteger('total_nominal')->default(0);
                $table->date('tanggal_tagihan');
                $table->date('tanggal_jatuh_tempo')->nullable();
                $table->string('status', 20)->default('belum_lunas');
                $table->timestamp('dibayar_at')->nullable();
                $table->unsignedBigInteger('dibayar_by')->nullable();
                $table->string('catatan_pembayaran', 1000)->nullable();
                $table->string('bukti_bayar_path', 500)->nullable();
                $table->timestamps();

                $table->foreign('warehouse_transaksi_id', 'wst_transaksi_fk')
                    ->references('id')->on('warehouse_transaksis')->cascadeOnDelete();
                $table->foreign('warehouse_id', 'wst_warehouse_fk')
                    ->references('id')->on('warehouses')->cascadeOnDelete();
                $table->foreign('supplier_id', 'wst_supplier_fk')
                    ->references('id')->on('suppliers')->restrictOnDelete();
                $table->foreign('dibayar_by', 'wst_dibayar_by_fk')
                    ->references('id')->on('users')->nullOnDelete();

                $table->unique('warehouse_transaksi_id', 'wst_transaksi_id_uniq');
                $table->index(['warehouse_id', 'status'], 'wst_wh_status_idx');
                $table->index(['supplier_id', 'tanggal_jatuh_tempo'], 'wst_supplier_jt_idx');
            });
        }

        if (! Schema::hasTable('warehouse_supplier_tagihan_items')) {
            Schema::create('warehouse_supplier_tagihan_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tagihan_id');
                $table->unsignedBigInteger('warehouse_transaksi_item_id')->nullable();
                $table->string('item_type', 30)->default('product');
                $table->unsignedBigInteger('product_id')->nullable();
                $table->unsignedBigInteger('perlengkapan_packing_id')->nullable();
                $table->string('nama_item', 255);
                $table->unsignedInteger('qty')->default(0);
                $table->unsignedBigInteger('harga_satuan')->default(0);
                $table->unsignedBigInteger('diskon')->default(0);
                $table->unsignedBigInteger('subtotal')->default(0);
                $table->timestamps();

                $table->foreign('tagihan_id', 'wsti_tagihan_fk')
                    ->references('id')->on('warehouse_supplier_tagihans')->cascadeOnDelete();
                $table->foreign('warehouse_transaksi_item_id', 'wsti_transaksi_item_fk')
                    ->references('id')->on('warehouse_transaksi_items')->nullOnDelete();
                $table->foreign('product_id', 'wsti_product_fk')
                    ->references('id')->on('products')->nullOnDelete();
                $table->foreign('perlengkapan_packing_id', 'wsti_packing_fk')
                    ->references('id')->on('perlengkapan_packings')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_supplier_tagihan_items');
        Schema::dropIfExists('warehouse_supplier_tagihans');
    }
};
