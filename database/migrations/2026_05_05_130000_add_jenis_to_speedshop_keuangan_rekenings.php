<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('speedshop_keuangan_rekenings') && ! Schema::hasColumn('speedshop_keuangan_rekenings', 'jenis')) {
            Schema::table('speedshop_keuangan_rekenings', function (Blueprint $table) {
                $table->string('jenis', 24)->default('rekening_utama')->after('speedshop_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('speedshop_keuangan_rekenings') && Schema::hasColumn('speedshop_keuangan_rekenings', 'jenis')) {
            Schema::table('speedshop_keuangan_rekenings', function (Blueprint $table) {
                $table->dropColumn('jenis');
            });
        }
    }
};
