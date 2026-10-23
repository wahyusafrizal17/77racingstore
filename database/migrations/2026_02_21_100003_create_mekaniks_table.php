<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mekaniks', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('no_hp', 20)->nullable();
            $table->string('spesialisasi')->nullable();
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mekaniks');
    }
};
