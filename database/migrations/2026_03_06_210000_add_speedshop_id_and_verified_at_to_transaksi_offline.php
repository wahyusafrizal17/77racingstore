<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi_offline', function (Blueprint $table) {
            $table->foreignId('speedshop_id')->nullable()->after('pelanggan_id')->constrained('speedshops')->nullOnDelete();
            $table->timestamp('verified_at')->nullable()->after('warehouse_id');
        });
    }

    public function down(): void
    {
        Schema::table('transaksi_offline', function (Blueprint $table) {
            $table->dropForeign(['speedshop_id']);
            $table->dropColumn('verified_at');
        });
    }
};
