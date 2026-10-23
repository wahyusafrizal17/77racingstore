<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi_offline_items', function (Blueprint $table) {
            if (! Schema::hasColumn('transaksi_offline_items', 'harga')) {
                $table->decimal('harga', 15, 2)->default(0)->after('qty');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transaksi_offline_items', function (Blueprint $table) {
            if (Schema::hasColumn('transaksi_offline_items', 'harga')) {
                $table->dropColumn('harga');
            }
        });
    }
};
