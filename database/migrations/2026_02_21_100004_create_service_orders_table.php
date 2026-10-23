<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_orders', function (Blueprint $table) {
            $table->id();
            $table->string('kode_servis', 30)->unique();
            $table->foreignId('pelanggan_id')->constrained('pelanggans')->cascadeOnDelete();
            $table->foreignId('kendaraan_id')->constrained('kendaraans')->cascadeOnDelete();
            $table->foreignId('mekanik_id')->nullable()->constrained('mekaniks')->nullOnDelete();
            $table->text('keluhan')->nullable();
            $table->decimal('estimasi_biaya', 15, 0)->default(0);
            $table->enum('status', ['antri', 'proses', 'selesai', 'dibatalkan'])->default('antri');
            $table->date('tanggal_masuk');
            $table->date('tanggal_selesai')->nullable();
            $table->date('next_service_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_orders');
    }
};
