<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('speedshop_finance_setoran_items')) {
            return;
        }

        Schema::create('speedshop_finance_setoran_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('setoran_id')->constrained('speedshop_finance_setorans')->cascadeOnDelete();

            // kas_kecil | kas_besar | rekening_utama
            $table->string('sumber', 20);
            $table->unsignedBigInteger('nominal')->default(0);

            // Sumber rekening (untuk kas_besar / rekening_utama)
            $table->foreignId('sumber_kas_besar_id')->nullable()->constrained('speedshop_keuangan_rekenings')->nullOnDelete();
            $table->foreignId('sumber_rekening_id')->nullable()->constrained('speedshop_keuangan_rekenings')->nullOnDelete();

            // Tujuan
            $table->string('tujuan_type', 20); // kas_kecil | kas_besar | rekening_utama
            $table->foreignId('tujuan_rekening_id')->nullable()->constrained('speedshop_keuangan_rekenings')->nullOnDelete();

            $table->string('bukti_path')->nullable();
            $table->string('keterangan')->nullable();
            $table->timestamps();

            $table->index(['setoran_id', 'sumber']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('speedshop_finance_setoran_items');
    }
};

