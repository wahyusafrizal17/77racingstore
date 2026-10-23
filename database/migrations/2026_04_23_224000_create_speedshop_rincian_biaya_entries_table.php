<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('speedshop_rincian_biaya_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('speedshop_id')->constrained('speedshops')->cascadeOnDelete();
            $table->date('tanggal');
            $table->string('no_akun', 50);
            $table->string('nama_akun', 150);
            $table->string('keterangan', 255)->nullable();
            $table->string('pic', 100)->nullable();
            $table->enum('metode', ['tunai', 'transfer'])->default('tunai');
            $table->decimal('nominal', 15, 2)->default(0);
            $table->timestamps();

            $table->index(['speedshop_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('speedshop_rincian_biaya_entries');
    }
};

