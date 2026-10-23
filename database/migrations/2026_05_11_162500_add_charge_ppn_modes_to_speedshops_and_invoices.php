<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('speedshops')) {
            Schema::table('speedshops', function (Blueprint $table) {
                if (! Schema::hasColumn('speedshops', 'invoice_charge_mode')) {
                    $table->string('invoice_charge_mode', 10)->default('percent')->after('invoice_charge_percent');
                }
                if (! Schema::hasColumn('speedshops', 'invoice_charge_rupiah')) {
                    $table->decimal('invoice_charge_rupiah', 15, 0)->default(0)->after('invoice_charge_mode');
                }
                if (! Schema::hasColumn('speedshops', 'invoice_ppn_mode')) {
                    $table->string('invoice_ppn_mode', 10)->default('percent')->after('invoice_ppn_percent');
                }
                if (! Schema::hasColumn('speedshops', 'invoice_ppn_rupiah')) {
                    $table->decimal('invoice_ppn_rupiah', 15, 0)->default(0)->after('invoice_ppn_mode');
                }
            });
        }

        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                if (! Schema::hasColumn('invoices', 'charge_mode')) {
                    $table->string('charge_mode', 10)->default('percent')->after('charge_percent');
                }
                if (! Schema::hasColumn('invoices', 'charge_rupiah')) {
                    $table->decimal('charge_rupiah', 15, 0)->default(0)->after('charge_mode');
                }
                if (! Schema::hasColumn('invoices', 'ppn_mode')) {
                    $table->string('ppn_mode', 10)->default('percent')->after('ppn_percent');
                }
                if (! Schema::hasColumn('invoices', 'ppn_rupiah')) {
                    $table->decimal('ppn_rupiah', 15, 0)->default(0)->after('ppn_mode');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                foreach (['ppn_rupiah', 'ppn_mode', 'charge_rupiah', 'charge_mode'] as $col) {
                    if (Schema::hasColumn('invoices', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('speedshops')) {
            Schema::table('speedshops', function (Blueprint $table) {
                foreach (['invoice_ppn_rupiah', 'invoice_ppn_mode', 'invoice_charge_rupiah', 'invoice_charge_mode'] as $col) {
                    if (Schema::hasColumn('speedshops', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
