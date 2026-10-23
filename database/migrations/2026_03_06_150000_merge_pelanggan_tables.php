<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah kota_id dan buat tujuan nullable di tabel pelanggan
        Schema::table('pelanggan', function (Blueprint $table) {
            $table->foreignId('kota_id')->nullable()->after('no_hp')->constrained('kotas')->nullOnDelete();
        });

        \DB::statement("ALTER TABLE pelanggan MODIFY COLUMN tujuan ENUM('speedshop', 'reseller', 'umum') NULL");

        // 2. Lepas FK kendaraans dan service_orders dari pelanggans
        Schema::table('kendaraans', function (Blueprint $table) {
            $table->dropForeign(['pelanggan_id']);
        });
        Schema::table('service_orders', function (Blueprint $table) {
            $table->dropForeign(['pelanggan_id']);
        });

        // 3. Hapus tabel pelanggans
        Schema::dropIfExists('pelanggans');

        // 4. Tambah FK kendaraans dan service_orders ke pelanggan
        Schema::table('kendaraans', function (Blueprint $table) {
            $table->foreign('pelanggan_id')->references('id')->on('pelanggan')->cascadeOnDelete();
        });
        Schema::table('service_orders', function (Blueprint $table) {
            $table->foreign('pelanggan_id')->references('id')->on('pelanggan')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('kendaraans', function (Blueprint $table) {
            $table->dropForeign(['pelanggan_id']);
        });
        Schema::table('service_orders', function (Blueprint $table) {
            $table->dropForeign(['pelanggan_id']);
        });

        Schema::create('pelanggans', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('no_hp', 20)->nullable();
            $table->text('alamat')->nullable();
            $table->foreignId('kota_id')->nullable()->constrained('kotas')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('kendaraans', function (Blueprint $table) {
            $table->foreign('pelanggan_id')->references('id')->on('pelanggans')->cascadeOnDelete();
        });
        Schema::table('service_orders', function (Blueprint $table) {
            $table->foreign('pelanggan_id')->references('id')->on('pelanggans')->cascadeOnDelete();
        });

        Schema::table('pelanggan', function (Blueprint $table) {
            $table->dropForeign(['kota_id']);
        });
        \DB::statement("ALTER TABLE pelanggan MODIFY COLUMN tujuan ENUM('speedshop', 'reseller', 'umum') NOT NULL");
    }
};
