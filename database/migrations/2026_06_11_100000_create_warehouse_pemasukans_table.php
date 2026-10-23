<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('speedshop_finance_ledgers') && ! Schema::hasColumn('speedshop_finance_ledgers', 'warehouse_pemasukan_id')) {
            Schema::table('speedshop_finance_ledgers', function (Blueprint $table) {
                $table->unsignedBigInteger('warehouse_pemasukan_id')->nullable()->after('warehouse_piutang_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('speedshop_finance_ledgers') && Schema::hasColumn('speedshop_finance_ledgers', 'warehouse_pemasukan_id')) {
            Schema::table('speedshop_finance_ledgers', function (Blueprint $table) {
                $table->dropColumn('warehouse_pemasukan_id');
            });
        }
    }
};
