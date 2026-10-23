<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->string('tipe_pemesanan', 32)->default('servis')->after('warehouse_id');
        });

        Schema::table('service_orders', function (Blueprint $table) {
            $table->dropForeign(['kendaraan_id']);
        });

        Schema::table('service_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('kendaraan_id')->nullable()->change();
        });

        Schema::table('service_orders', function (Blueprint $table) {
            $table->foreign('kendaraan_id')->references('id')->on('kendaraans')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->dropForeign(['kendaraan_id']);
        });

        Schema::table('service_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('kendaraan_id')->nullable(false)->change();
        });

        Schema::table('service_orders', function (Blueprint $table) {
            $table->foreign('kendaraan_id')->references('id')->on('kendaraans')->cascadeOnDelete();
        });

        Schema::table('service_orders', function (Blueprint $table) {
            $table->dropColumn('tipe_pemesanan');
        });
    }
};
