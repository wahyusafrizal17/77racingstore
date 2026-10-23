<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->foreignId('warehouse_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('sumber_kedatangan', 50)->nullable()->after('keluhan');
            $table->string('kategori_service', 30)->nullable()->after('sumber_kedatangan');
        });
    }

    public function down(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('warehouse_id');
            $table->dropColumn(['sumber_kedatangan', 'kategori_service']);
        });
    }
};
