<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('speedshop_purchase_orders', function (Blueprint $table) {
            $table->string('invoice_no', 100)->nullable()->after('kode');
        });
    }

    public function down(): void
    {
        Schema::table('speedshop_purchase_orders', function (Blueprint $table) {
            $table->dropColumn('invoice_no');
        });
    }
};
