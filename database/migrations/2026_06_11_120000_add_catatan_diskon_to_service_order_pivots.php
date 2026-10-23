<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_order_jasa', function (Blueprint $table) {
            $table->text('catatan_diskon')->nullable()->after('diskon');
        });

        Schema::table('service_order_products', function (Blueprint $table) {
            $table->text('catatan_diskon')->nullable()->after('diskon');
        });
    }

    public function down(): void
    {
        Schema::table('service_order_jasa', function (Blueprint $table) {
            $table->dropColumn('catatan_diskon');
        });

        Schema::table('service_order_products', function (Blueprint $table) {
            $table->dropColumn('catatan_diskon');
        });
    }
};
