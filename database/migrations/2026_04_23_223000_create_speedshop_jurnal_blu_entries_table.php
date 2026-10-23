<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('speedshop_jurnal_blu_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('speedshop_id')->constrained('speedshops')->cascadeOnDelete();
            $table->date('tanggal');
            $table->string('akun', 100);
            $table->text('keterangan')->nullable();
            $table->string('pic', 100)->nullable();
            $table->decimal('kredit', 15, 2)->default(0);
            $table->decimal('debet', 15, 2)->default(0);
            $table->timestamps();

            $table->index(['speedshop_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('speedshop_jurnal_blu_entries');
    }
};

