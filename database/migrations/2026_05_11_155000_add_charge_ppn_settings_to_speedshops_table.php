<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('speedshops')) {
            return;
        }

        Schema::table('speedshops', function (Blueprint $table) {
            if (! Schema::hasColumn('speedshops', 'invoice_charge_percent')) {
                $table->decimal('invoice_charge_percent', 5, 2)->default(0)->after('warehouse_id');
            }
            if (! Schema::hasColumn('speedshops', 'invoice_ppn_percent')) {
                $table->decimal('invoice_ppn_percent', 5, 2)->default(0)->after('invoice_charge_percent');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('speedshops')) {
            return;
        }

        Schema::table('speedshops', function (Blueprint $table) {
            if (Schema::hasColumn('speedshops', 'invoice_ppn_percent')) {
                $table->dropColumn('invoice_ppn_percent');
            }
            if (Schema::hasColumn('speedshops', 'invoice_charge_percent')) {
                $table->dropColumn('invoice_charge_percent');
            }
        });
    }
};
