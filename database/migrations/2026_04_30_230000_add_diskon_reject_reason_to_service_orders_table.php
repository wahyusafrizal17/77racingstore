<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->text('diskon_verifikasi_rejected_reason')
                ->nullable()
                ->after('diskon_verifikasi_rejected_at');
        });
    }

    public function down(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->dropColumn('diskon_verifikasi_rejected_reason');
        });
    }
};

