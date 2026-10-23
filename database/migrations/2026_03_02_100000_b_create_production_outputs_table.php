<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_outputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bahan_siap_produksi_id')->constrained('bahan_siap_produksis')->cascadeOnDelete();
            $table->integer('jumlah_target')->default(0);
            $table->integer('jumlah_selesai')->nullable();
            $table->integer('jumlah_gagal')->nullable();
            $table->text('alasan_gagal')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_outputs');
    }
};
