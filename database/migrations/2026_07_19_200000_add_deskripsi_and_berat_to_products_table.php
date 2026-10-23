<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'deskripsi')) {
                $table->text('deskripsi')->nullable()->after('nama_produk');
            }
            if (! Schema::hasColumn('products', 'berat')) {
                $table->unsignedInteger('berat')->nullable()->after('deskripsi')->comment('Berat produk dalam gram');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'berat')) {
                $table->dropColumn('berat');
            }
            if (Schema::hasColumn('products', 'deskripsi')) {
                $table->dropColumn('deskripsi');
            }
        });
    }
};
