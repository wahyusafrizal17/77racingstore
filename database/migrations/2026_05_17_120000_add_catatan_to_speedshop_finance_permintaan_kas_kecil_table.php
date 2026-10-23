<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('speedshop_finance_permintaan_kas_kecil')) {
            return;
        }
        if (Schema::hasColumn('speedshop_finance_permintaan_kas_kecil', 'catatan')) {
            return;
        }
        Schema::table('speedshop_finance_permintaan_kas_kecil', function (Blueprint $table) {
            $table->text('catatan')->nullable()->after('nominal');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('speedshop_finance_permintaan_kas_kecil')) {
            return;
        }
        if (Schema::hasColumn('speedshop_finance_permintaan_kas_kecil', 'catatan')) {
            Schema::table('speedshop_finance_permintaan_kas_kecil', function (Blueprint $table) {
                $table->dropColumn('catatan');
            });
        }
    }
};
