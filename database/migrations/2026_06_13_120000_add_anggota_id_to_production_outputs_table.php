<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_outputs', function (Blueprint $table) {
            $table->foreignId('anggota_id')
                ->nullable()
                ->after('bahan_siap_produksi_id')
                ->constrained('anggotas')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('production_outputs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('anggota_id');
        });
    }
};
