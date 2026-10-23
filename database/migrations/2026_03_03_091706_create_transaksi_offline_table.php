<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transaksi_offline', function (Blueprint $table) {
            $table->id();
            $table->string('no_transaksi')->unique();
            $table->enum('tujuan', ['speedshop', 'reseller', 'umum']);
            $table->string('nama_toko')->nullable();
            $table->text('alamat')->nullable();
            $table->string('no_hp')->nullable();
            $table->string('petugas')->nullable();
            $table->string('jenis_pembayaran')->nullable();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('transaksi_offline_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaksi_offline_id')->constrained('transaksi_offline')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->unsignedInteger('qty');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksi_offline_items');
        Schema::dropIfExists('transaksi_offline');
    }
};
