<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('warehouse_supplier_tagihan_pembayarans')) {
            return;
        }

        if (! Schema::hasColumn('warehouse_supplier_tagihan_pembayarans', 'jenis_pembayaran')) {
            Schema::table('warehouse_supplier_tagihan_pembayarans', function (Blueprint $table) {
                $table->string('jenis_pembayaran', 20)->nullable()->after('nominal');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('warehouse_supplier_tagihan_pembayarans')) {
            return;
        }

        if (Schema::hasColumn('warehouse_supplier_tagihan_pembayarans', 'jenis_pembayaran')) {
            Schema::table('warehouse_supplier_tagihan_pembayarans', function (Blueprint $table) {
                $table->dropColumn('jenis_pembayaran');
            });
        }
    }
};
