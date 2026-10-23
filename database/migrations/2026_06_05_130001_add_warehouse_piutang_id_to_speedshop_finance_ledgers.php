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
            if (! Schema::hasColumn('speedshop_finance_ledgers', 'warehouse_piutang_id')) {
                $table->unsignedBigInteger('warehouse_piutang_id')
                    ->nullable()
                    ->after('speedshop_rincian_biaya_entry_id');
                $table->index(['warehouse_piutang_id', 'reason']);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('speedshop_finance_ledgers')) {
            return;
        }

        Schema::table('speedshop_finance_ledgers', function (Blueprint $table) {
            if (Schema::hasColumn('speedshop_finance_ledgers', 'warehouse_piutang_id')) {
                $table->dropIndex(['warehouse_piutang_id', 'reason']);
                $table->dropColumn('warehouse_piutang_id');
            }
        });
    }
};
