<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->string('diskon_verifikasi_status', 20)->nullable()->after('status'); // pending|approved|rejected
            $table->foreignId('diskon_verifikasi_requested_by')->nullable()->after('diskon_verifikasi_status')->constrained('users')->nullOnDelete();
            $table->timestamp('diskon_verifikasi_requested_at')->nullable()->after('diskon_verifikasi_requested_by');
            $table->foreignId('diskon_verifikasi_verified_by')->nullable()->after('diskon_verifikasi_requested_at')->constrained('users')->nullOnDelete();
            $table->timestamp('diskon_verifikasi_verified_at')->nullable()->after('diskon_verifikasi_verified_by');
            $table->foreignId('diskon_verifikasi_rejected_by')->nullable()->after('diskon_verifikasi_verified_at')->constrained('users')->nullOnDelete();
            $table->timestamp('diskon_verifikasi_rejected_at')->nullable()->after('diskon_verifikasi_rejected_by');
        });
    }

    public function down(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('diskon_verifikasi_requested_by');
            $table->dropConstrainedForeignId('diskon_verifikasi_verified_by');
            $table->dropConstrainedForeignId('diskon_verifikasi_rejected_by');
            $table->dropColumn([
                'diskon_verifikasi_status',
                'diskon_verifikasi_requested_at',
                'diskon_verifikasi_verified_at',
                'diskon_verifikasi_rejected_at',
            ]);
        });
    }
};
