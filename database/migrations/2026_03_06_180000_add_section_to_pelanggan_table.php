<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom section agar pelanggan bisa dibedakan untuk warehouse vs speedshop.
     * - warehouse: pelanggan dari modul warehouse (transaksi offline)
     * - speedshop: pelanggan dari modul speedshop (work order, transaksi penjualan)
     * - null: data lama / bisa dipakai di kedua modul
     */
    public function up(): void
    {
        Schema::table('pelanggan', function (Blueprint $table) {
            $table->enum('section', ['warehouse', 'speedshop'])->nullable()->after('warehouse_id');
        });
    }

    public function down(): void
    {
        Schema::table('pelanggan', function (Blueprint $table) {
            $table->dropColumn('section');
        });
    }
};
