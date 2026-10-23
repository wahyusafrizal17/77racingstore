<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_transaksis', function (Blueprint $table) {
            if (! Schema::hasColumn('warehouse_transaksis', 'tipe')) {
                $table->string('tipe', 40)->nullable()->index()->after('kode');
            }
            if (! Schema::hasColumn('warehouse_transaksis', 'invoice_path')) {
                $table->string('invoice_path')->nullable()->after('catatan');
            }
        });
    }

    public function down(): void
    {
        Schema::table('warehouse_transaksis', function (Blueprint $table) {
            if (Schema::hasColumn('warehouse_transaksis', 'invoice_path')) {
                $table->dropColumn('invoice_path');
            }
            if (Schema::hasColumn('warehouse_transaksis', 'tipe')) {
                $table->dropIndex(['tipe']);
                $table->dropColumn('tipe');
            }
        });
    }
};

