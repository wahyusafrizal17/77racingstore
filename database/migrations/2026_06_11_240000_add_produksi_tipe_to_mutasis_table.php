<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mutasis', function (Blueprint $table) {
            if (! Schema::hasColumn('mutasis', 'produksi_tipe')) {
                $table->string('produksi_tipe', 40)->nullable()->after('destination_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mutasis', function (Blueprint $table) {
            if (Schema::hasColumn('mutasis', 'produksi_tipe')) {
                $table->dropColumn('produksi_tipe');
            }
        });
    }
};
