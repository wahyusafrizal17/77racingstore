<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('speedshop_finance_ledgers') && Schema::hasTable('speedshop_keuangan_rekenings')
            && ! Schema::hasColumn('speedshop_finance_ledgers', 'speedshop_keuangan_rekening_id')) {
            Schema::table('speedshop_finance_ledgers', function (Blueprint $table) {
                $table->unsignedBigInteger('speedshop_keuangan_rekening_id')->nullable()->after('setoran_id');
            });
            Schema::table('speedshop_finance_ledgers', function (Blueprint $table) {
                $table->foreign('speedshop_keuangan_rekening_id', 'sf_led_kr_fk')
                    ->references('id')->on('speedshop_keuangan_rekenings')->nullOnDelete();
            });
        }

        if (Schema::hasTable('speedshop_finance_setorans') && Schema::hasTable('speedshop_keuangan_rekenings')
            && ! Schema::hasColumn('speedshop_finance_setorans', 'speedshop_keuangan_rekening_id')) {
            Schema::table('speedshop_finance_setorans', function (Blueprint $table) {
                $table->unsignedBigInteger('speedshop_keuangan_rekening_id')->nullable()->after('speedshop_id');
            });
            Schema::table('speedshop_finance_setorans', function (Blueprint $table) {
                $table->foreign('speedshop_keuangan_rekening_id', 'sf_set_kr_fk')
                    ->references('id')->on('speedshop_keuangan_rekenings')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('speedshop_finance_setorans') && Schema::hasColumn('speedshop_finance_setorans', 'speedshop_keuangan_rekening_id')) {
            Schema::table('speedshop_finance_setorans', function (Blueprint $table) {
                $table->dropForeign('sf_set_kr_fk');
                $table->dropColumn('speedshop_keuangan_rekening_id');
            });
        }

        if (Schema::hasTable('speedshop_finance_ledgers') && Schema::hasColumn('speedshop_finance_ledgers', 'speedshop_keuangan_rekening_id')) {
            Schema::table('speedshop_finance_ledgers', function (Blueprint $table) {
                $table->dropForeign('sf_led_kr_fk');
                $table->dropColumn('speedshop_keuangan_rekening_id');
            });
        }
    }
};
