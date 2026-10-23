<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_anggota', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_id')->constrained('productions')->cascadeOnDelete();
            $table->foreignId('anggota_id')->constrained('anggotas')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['production_id', 'anggota_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_anggota');
    }
};
