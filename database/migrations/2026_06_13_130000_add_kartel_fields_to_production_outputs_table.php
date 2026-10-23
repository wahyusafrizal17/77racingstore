<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_outputs', function (Blueprint $table) {
            $table->boolean('perlu_kartel')->default(false)->after('anggota_id');
            $table->foreignId('kartel_anggota_id')
                ->nullable()
                ->after('perlu_kartel')
                ->constrained('anggotas')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('production_outputs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kartel_anggota_id');
            $table->dropColumn('perlu_kartel');
        });
    }
};
