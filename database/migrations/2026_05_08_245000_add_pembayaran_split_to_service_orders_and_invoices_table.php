<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('service_orders', 'pembayaran_split')) {
                $table->json('pembayaran_split')->nullable()->after('jenis_pembayaran');
            }
        });

        Schema::table('invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('invoices', 'pembayaran_split')) {
                $table->json('pembayaran_split')->nullable()->after('metode_pembayaran');
            }
        });
    }

    public function down(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            if (Schema::hasColumn('service_orders', 'pembayaran_split')) {
                $table->dropColumn('pembayaran_split');
            }
        });

        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'pembayaran_split')) {
                $table->dropColumn('pembayaran_split');
            }
        });
    }
};
