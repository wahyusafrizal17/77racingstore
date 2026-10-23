<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('speedshop_rincian_biaya_entries')) {
            return;
        }
        if (! Schema::hasTable('speedshop_keuangan_rekenings')) {
            return;
        }
        if (Schema::hasColumn('speedshop_rincian_biaya_entries', 'speedshop_keuangan_rekening_id')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        $afterMetode = ($driver === 'mysql' || $driver === 'mariadb')
            && Schema::hasColumn('speedshop_rincian_biaya_entries', 'metode');

        // MySQL identifier limit 64 chars; default Laravel FK name exceeds it for these table names.
        $fkName = 'ss_rbe_keuangan_rekening_fk';

        Schema::table('speedshop_rincian_biaya_entries', function (Blueprint $table) use ($afterMetode, $fkName) {
            if ($afterMetode) {
                $table->unsignedBigInteger('speedshop_keuangan_rekening_id')
                    ->nullable()
                    ->after('metode');
            } else {
                $table->unsignedBigInteger('speedshop_keuangan_rekening_id')->nullable();
            }

            $table->foreign('speedshop_keuangan_rekening_id', $fkName)
                ->references('id')
                ->on('speedshop_keuangan_rekenings')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('speedshop_rincian_biaya_entries')) {
            return;
        }
        if (! Schema::hasColumn('speedshop_rincian_biaya_entries', 'speedshop_keuangan_rekening_id')) {
            return;
        }

        Schema::table('speedshop_rincian_biaya_entries', function (Blueprint $table) {
            $table->dropForeign('ss_rbe_keuangan_rekening_fk');
            $table->dropColumn('speedshop_keuangan_rekening_id');
        });
    }
};
