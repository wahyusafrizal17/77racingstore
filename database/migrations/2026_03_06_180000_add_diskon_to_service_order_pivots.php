<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_order_jasa', function (Blueprint $table) {
            $table->decimal('diskon', 15, 0)->default(0)->after('biaya');
            $table->foreignId('diskon_verified_by')->nullable()->after('diskon')->constrained('users')->nullOnDelete();
            $table->timestamp('diskon_verified_at')->nullable()->after('diskon_verified_by');
        });

        Schema::table('service_order_products', function (Blueprint $table) {
            $table->decimal('diskon', 15, 0)->default(0)->after('harga');
            $table->foreignId('diskon_verified_by')->nullable()->after('diskon')->constrained('users')->nullOnDelete();
            $table->timestamp('diskon_verified_at')->nullable()->after('diskon_verified_by');
        });
    }

    public function down(): void
    {
        Schema::table('service_order_jasa', function (Blueprint $table) {
            $table->dropForeign(['diskon_verified_by']);
            $table->dropColumn(['diskon', 'diskon_verified_by', 'diskon_verified_at']);
        });
        Schema::table('service_order_products', function (Blueprint $table) {
            $table->dropForeign(['diskon_verified_by']);
            $table->dropColumn(['diskon', 'diskon_verified_by', 'diskon_verified_at']);
        });
    }
};
