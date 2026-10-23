<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('speedshop_po_tagihans', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 64)->unique();
            $table->foreignId('speedshop_purchase_order_id')->unique()->constrained('speedshop_purchase_orders')->cascadeOnDelete();
            $table->foreignId('transaksi_offline_id')->nullable()->constrained('transaksi_offline')->nullOnDelete();
            $table->foreignId('speedshop_id')->constrained('speedshops')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->unsignedBigInteger('total_nominal')->default(0);
            $table->date('tanggal_tagihan');
            $table->string('status', 32)->default('belum_lunas');
            $table->timestamp('dibayar_at')->nullable();
            $table->foreignId('dibayar_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('catatan_pembayaran')->nullable();
            $table->string('bukti_bayar_path')->nullable();
            $table->timestamp('lunas_at')->nullable();
            $table->foreignId('lunas_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('catatan_konfirmasi')->nullable();
            $table->timestamps();

            $table->index(['warehouse_id', 'status']);
            $table->index(['speedshop_id', 'status']);
        });

        Schema::create('speedshop_po_tagihan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tagihan_id')->constrained('speedshop_po_tagihans')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->unsignedInteger('qty');
            $table->unsignedBigInteger('harga_satuan')->default(0);
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('speedshop_po_tagihan_items');
        Schema::dropIfExists('speedshop_po_tagihans');
    }
};
