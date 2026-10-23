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
        if (Schema::hasColumn('speedshop_finance_permintaan_kas_kecil', 'keuangan_rekening_id')) {
            return;
        }

        // Nama FK default Laravel melebihi batas 64 karakter MySQL pada nama tabel ini.
        $fkName = 'sf_perm_kk_keuangan_rekening_fk';

        Schema::table('speedshop_finance_permintaan_kas_kecil', function (Blueprint $table) use ($fkName) {
            $table->foreignId('keuangan_rekening_id')
                ->nullable()
                ->after('nominal')
                ->constrained('speedshop_keuangan_rekenings', 'id', $fkName)
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('speedshop_finance_permintaan_kas_kecil')) {
            return;
        }
        if (! Schema::hasColumn('speedshop_finance_permintaan_kas_kecil', 'keuangan_rekening_id')) {
            return;
        }

        $fkName = 'sf_perm_kk_keuangan_rekening_fk';

        Schema::table('speedshop_finance_permintaan_kas_kecil', function (Blueprint $table) use ($fkName) {
            $table->dropForeign($fkName);
            $table->dropColumn('keuangan_rekening_id');
        });
    }
};
