<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_items', function (Blueprint $table) {
            $table->unsignedInteger('jumlah_cacat')->default(0)->after('jumlah');
        });

        Schema::table('production_outputs', function (Blueprint $table) {
            $table->text('alasan_gagal_bahan')->nullable()->after('alasan_gagal');
        });
    }

    public function down(): void
    {
        Schema::table('production_items', function (Blueprint $table) {
            $table->dropColumn('jumlah_cacat');
        });

        Schema::table('production_outputs', function (Blueprint $table) {
            $table->dropColumn('alasan_gagal_bahan');
        });
    }
};
