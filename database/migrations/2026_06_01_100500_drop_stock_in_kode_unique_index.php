<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('stock_ins')) {
            return;
        }

        Schema::table('stock_ins', function (Blueprint $table) {
            try {
                $table->dropUnique('stock_ins_kode_unique');
            } catch (\Throwable $e) {
                // Index mungkin sudah tidak ada; biarkan migrasi tetap lanjut.
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('stock_ins')) {
            return;
        }

        Schema::table('stock_ins', function (Blueprint $table) {
            try {
                $table->unique('kode', 'stock_ins_kode_unique');
            } catch (\Throwable $e) {
                // Bisa gagal jika sudah ada data duplikat; biarkan rollback tetap aman.
            }
        });
    }
};

