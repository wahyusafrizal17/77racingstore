<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('speedshop_finance_setoran_items')) {
            return;
        }

        Schema::table('speedshop_finance_setoran_items', function (Blueprint $table) {
            if (! Schema::hasColumn('speedshop_finance_setoran_items', 'keterangan')) {
                $table->string('keterangan')->nullable()->after('bukti_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('speedshop_finance_setoran_items', function (Blueprint $table) {
            if (Schema::hasColumn('speedshop_finance_setoran_items', 'keterangan')) {
                $table->dropColumn('keterangan');
            }
        });
    }
};

