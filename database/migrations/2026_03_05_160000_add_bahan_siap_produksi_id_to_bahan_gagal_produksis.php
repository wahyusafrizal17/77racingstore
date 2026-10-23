<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bahan_gagal_produksis', function (Blueprint $table) {
            $table->foreignId('bahan_siap_produksi_id')->nullable()->after('id')->constrained('bahan_siap_produksis')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bahan_gagal_produksis', function (Blueprint $table) {
            $table->dropForeign(['bahan_siap_produksi_id']);
        });
    }
};
