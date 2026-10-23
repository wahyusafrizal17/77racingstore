<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('warehouse_supplier_tagihans')) {
            return;
        }

        if (! Schema::hasColumn('warehouse_supplier_tagihans', 'speedshop_id')) {
            Schema::table('warehouse_supplier_tagihans', function (Blueprint $table) {
                $table->unsignedBigInteger('speedshop_id')->nullable()->after('warehouse_id');
                $table->foreign('speedshop_id', 'wst_speedshop_fk')
                    ->references('id')->on('speedshops')->nullOnDelete();
                $table->index(['speedshop_id', 'status'], 'wst_ss_status_idx');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('warehouse_supplier_tagihans')) {
            return;
        }

        if (Schema::hasColumn('warehouse_supplier_tagihans', 'speedshop_id')) {
            Schema::table('warehouse_supplier_tagihans', function (Blueprint $table) {
                $table->dropForeign('wst_speedshop_fk');
                $table->dropIndex('wst_ss_status_idx');
                $table->dropColumn('speedshop_id');
            });
        }
    }
};
