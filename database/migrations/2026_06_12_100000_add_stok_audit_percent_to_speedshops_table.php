<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('speedshops', function (Blueprint $table) {
            if (! Schema::hasColumn('speedshops', 'stok_audit_percent')) {
                $table->decimal('stok_audit_percent', 5, 2)->default(15)->after('invoice_ppn_rupiah');
            }
        });
    }

    public function down(): void
    {
        Schema::table('speedshops', function (Blueprint $table) {
            if (Schema::hasColumn('speedshops', 'stok_audit_percent')) {
                $table->dropColumn('stok_audit_percent');
            }
        });
    }
};
