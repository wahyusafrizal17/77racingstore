<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi_online', function (Blueprint $table) {
            if (Schema::hasColumn('transaksi_online', 'no_resi')) {
                $table->string('no_resi', 100)->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('transaksi_online', function (Blueprint $table) {
            if (Schema::hasColumn('transaksi_online', 'no_resi')) {
                $table->string('no_resi', 100)->nullable(false)->change();
            }
        });
    }
};
