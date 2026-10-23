<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('speedshop_keuangan_rekenings') || ! Schema::hasColumn('speedshop_keuangan_rekenings', 'jenis')) {
            return;
        }

        DB::table('speedshop_keuangan_rekenings')
            ->whereNotNull('warehouse_id')
            ->where('jenis', 'kas_besar')
            ->update(['jenis' => 'rekening_reguler']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('speedshop_keuangan_rekenings') || ! Schema::hasColumn('speedshop_keuangan_rekenings', 'jenis')) {
            return;
        }

        DB::table('speedshop_keuangan_rekenings')
            ->whereNotNull('warehouse_id')
            ->where('jenis', 'rekening_reguler')
            ->update(['jenis' => 'kas_besar']);
    }
};
