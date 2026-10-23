<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'gambar_path')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('gambar_path')->nullable()->after('nama_produk');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'gambar_path')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('gambar_path');
            });
        }
    }
};
