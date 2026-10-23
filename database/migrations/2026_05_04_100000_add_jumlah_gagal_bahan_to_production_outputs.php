<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_outputs', function (Blueprint $table) {
            $table->unsignedInteger('jumlah_gagal_bahan')->default(0)->after('jumlah_gagal');
        });
    }

    public function down(): void
    {
        Schema::table('production_outputs', function (Blueprint $table) {
            $table->dropColumn('jumlah_gagal_bahan');
        });
    }
};
