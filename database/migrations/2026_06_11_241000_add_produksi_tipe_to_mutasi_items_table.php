<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mutasi_items', function (Blueprint $table) {
            if (! Schema::hasColumn('mutasi_items', 'produksi_tipe')) {
                $table->string('produksi_tipe', 40)->nullable()->after('quantity');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mutasi_items', function (Blueprint $table) {
            if (Schema::hasColumn('mutasi_items', 'produksi_tipe')) {
                $table->dropColumn('produksi_tipe');
            }
        });
    }
};
