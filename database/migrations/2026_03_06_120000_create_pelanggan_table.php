<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pelanggan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->enum('tujuan', ['speedshop', 'reseller', 'umum']);
            $table->string('nama');
            $table->text('alamat')->nullable();
            $table->string('no_hp')->nullable();
            $table->timestamps();
        });

        Schema::table('transaksi_offline', function (Blueprint $table) {
            $table->foreignId('pelanggan_id')->nullable()->after('tujuan')->constrained('pelanggan')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transaksi_offline', function (Blueprint $table) {
            $table->dropForeign(['pelanggan_id']);
        });
        Schema::dropIfExists('pelanggan');
    }
};
