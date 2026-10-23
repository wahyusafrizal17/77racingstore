<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            // sebelumnya sudah ada index biasa dari migrasi add_kode_supplier.
            if (Schema::hasColumn('suppliers', 'kode_supplier')) {
                $table->dropIndex(['kode_supplier']);
            }
        });

        Schema::table('suppliers', function (Blueprint $table) {
            if (Schema::hasColumn('suppliers', 'kode_supplier')) {
                $table->unique('kode_supplier');
            }
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            if (Schema::hasColumn('suppliers', 'kode_supplier')) {
                $table->dropUnique(['kode_supplier']);
                $table->index('kode_supplier');
            }
        });
    }
};

