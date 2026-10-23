<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transaksi_online')) {
            return;
        }

        if (! Schema::hasColumn('transaksi_online', 'catatan')) {
            Schema::table('transaksi_online', function (Blueprint $table) {
                $table->text('catatan')->nullable()->after('no_resi');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('transaksi_online') && Schema::hasColumn('transaksi_online', 'catatan')) {
            Schema::table('transaksi_online', function (Blueprint $table) {
                $table->dropColumn('catatan');
            });
        }
    }
};
