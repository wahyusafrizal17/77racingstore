<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('bahan_siap_produksis', 'harga')) {
            Schema::table('bahan_siap_produksis', function (Blueprint $table) {
                $table->dropColumn('harga');
            });
        }
    }

    public function down(): void
    {
        Schema::table('bahan_siap_produksis', function (Blueprint $table) {
            $table->unsignedBigInteger('harga')->default(0)->after('nama');
        });
    }
};
