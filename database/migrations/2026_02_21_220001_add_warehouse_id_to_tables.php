<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('warehouse_id')->nullable()->after('kota_id')->constrained()->nullOnDelete();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('warehouse_id')->nullable()->after('harga_jual')->constrained()->nullOnDelete();
        });

        Schema::table('jasa_servis', function (Blueprint $table) {
            $table->foreignId('warehouse_id')->nullable()->after('biaya')->constrained()->nullOnDelete();
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('warehouse_id')->nullable()->after('nama')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('warehouse_id');
        });

        Schema::table('jasa_servis', function (Blueprint $table) {
            $table->dropConstrainedForeignId('warehouse_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('warehouse_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('warehouse_id');
        });
    }
};
