<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_transaksis', function (Blueprint $table) {
            if (! Schema::hasColumn('warehouse_transaksis', 'supplier_id')) {
                $table->foreignId('supplier_id')->nullable()->after('warehouse_id')->constrained('suppliers')->nullOnDelete();
            }
            if (! Schema::hasColumn('warehouse_transaksis', 'tanggal_jatuh_tempo')) {
                $table->date('tanggal_jatuh_tempo')->nullable()->after('catatan');
            }
        });
    }

    public function down(): void
    {
        Schema::table('warehouse_transaksis', function (Blueprint $table) {
            if (Schema::hasColumn('warehouse_transaksis', 'tanggal_jatuh_tempo')) {
                $table->dropColumn('tanggal_jatuh_tempo');
            }
            if (Schema::hasColumn('warehouse_transaksis', 'supplier_id')) {
                $table->dropConstrainedForeignId('supplier_id');
            }
        });
    }
};
