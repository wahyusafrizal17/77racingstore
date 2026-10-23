<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('akun_operasionals', function (Blueprint $table) {
            $table->id();
            $table->string('no_akun', 50)->unique();
            $table->string('nama_akun', 150);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('akun_operasionals');
    }
};
