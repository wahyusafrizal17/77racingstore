<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bahan_bakus', function (Blueprint $table) {
            $table->dropUnique(['kode']);
            $table->unique(['kode', 'supplier_id']);
        });
    }

    public function down(): void
    {
        Schema::table('bahan_bakus', function (Blueprint $table) {
            $table->dropUnique(['kode', 'supplier_id']);
            $table->unique('kode');
        });
    }
};
