<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('warehouse_supplier_tagihans')
            && ! Schema::hasColumn('warehouse_supplier_tagihans', 'speedshop_keuangan_rekening_id')) {
            Schema::table('warehouse_supplier_tagihans', function (Blueprint $table) {
                $table->unsignedBigInteger('speedshop_keuangan_rekening_id')->nullable()->after('bukti_bayar_path');
                $table->foreign('speedshop_keuangan_rekening_id', 'wst_rekening_fk')
                    ->references('id')->on('speedshop_keuangan_rekenings')->nullOnDelete();
            });
        }

        if (Schema::hasTable('speedshop_finance_ledgers')
            && ! Schema::hasColumn('speedshop_finance_ledgers', 'warehouse_supplier_tagihan_id')) {
            Schema::table('speedshop_finance_ledgers', function (Blueprint $table) {
                $table->foreignId('warehouse_supplier_tagihan_id')
                    ->nullable()
                    ->after('warehouse_pemasukan_id')
                    ->constrained('warehouse_supplier_tagihans')
                    ->nullOnDelete();
                $table->index(['warehouse_supplier_tagihan_id', 'reason'], 'sfl_wst_reason_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('speedshop_finance_ledgers')
            && Schema::hasColumn('speedshop_finance_ledgers', 'warehouse_supplier_tagihan_id')) {
            Schema::table('speedshop_finance_ledgers', function (Blueprint $table) {
                $table->dropIndex('sfl_wst_reason_idx');
                $table->dropConstrainedForeignId('warehouse_supplier_tagihan_id');
            });
        }

        if (Schema::hasTable('warehouse_supplier_tagihans')
            && Schema::hasColumn('warehouse_supplier_tagihans', 'speedshop_keuangan_rekening_id')) {
            Schema::table('warehouse_supplier_tagihans', function (Blueprint $table) {
                $table->dropForeign('wst_rekening_fk');
                $table->dropColumn('speedshop_keuangan_rekening_id');
            });
        }
    }
};
