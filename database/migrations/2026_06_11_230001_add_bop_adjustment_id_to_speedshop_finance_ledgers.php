<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('speedshop_finance_ledgers')) {
            return;
        }

        Schema::table('speedshop_finance_ledgers', function (Blueprint $table) {
            if (! Schema::hasColumn('speedshop_finance_ledgers', 'speedshop_rincian_biaya_adjustment_id')) {
                $table->unsignedBigInteger('speedshop_rincian_biaya_adjustment_id')
                    ->nullable()
                    ->after('speedshop_rincian_biaya_entry_id');
                $table->index('speedshop_rincian_biaya_adjustment_id', 'sfl_bop_adjustment_idx');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('speedshop_finance_ledgers')) {
            return;
        }

        Schema::table('speedshop_finance_ledgers', function (Blueprint $table) {
            if (Schema::hasColumn('speedshop_finance_ledgers', 'speedshop_rincian_biaya_adjustment_id')) {
                $table->dropIndex('sfl_bop_adjustment_idx');
                $table->dropColumn('speedshop_rincian_biaya_adjustment_id');
            }
        });
    }
};
