<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('invoices')) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('invoices', 'charge_percent')) {
                $table->decimal('charge_percent', 5, 2)->default(0)->after('diskon');
            }
            if (! Schema::hasColumn('invoices', 'charge_amount')) {
                $table->decimal('charge_amount', 15, 0)->default(0)->after('charge_percent');
            }
            if (! Schema::hasColumn('invoices', 'ppn_percent')) {
                $table->decimal('ppn_percent', 5, 2)->default(0)->after('charge_amount');
            }
            if (! Schema::hasColumn('invoices', 'ppn_amount')) {
                $table->decimal('ppn_amount', 15, 0)->default(0)->after('ppn_percent');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('invoices')) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'ppn_amount')) {
                $table->dropColumn('ppn_amount');
            }
            if (Schema::hasColumn('invoices', 'ppn_percent')) {
                $table->dropColumn('ppn_percent');
            }
            if (Schema::hasColumn('invoices', 'charge_amount')) {
                $table->dropColumn('charge_amount');
            }
            if (Schema::hasColumn('invoices', 'charge_percent')) {
                $table->dropColumn('charge_percent');
            }
        });
    }
};
