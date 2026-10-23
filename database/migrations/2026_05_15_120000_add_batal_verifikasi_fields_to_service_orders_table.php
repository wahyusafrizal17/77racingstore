<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->string('batal_verifikasi_status', 20)->nullable()->after('diskon_verifikasi_rejected_reason');
            $table->foreignId('batal_verifikasi_requested_by')->nullable()->after('batal_verifikasi_status')->constrained('users')->nullOnDelete();
            $table->timestamp('batal_verifikasi_requested_at')->nullable()->after('batal_verifikasi_requested_by');
            $table->text('batal_verifikasi_reason')->nullable()->after('batal_verifikasi_requested_at');
            $table->foreignId('batal_verifikasi_verified_by')->nullable()->after('batal_verifikasi_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('batal_verifikasi_verified_at')->nullable()->after('batal_verifikasi_verified_by');
            $table->foreignId('batal_verifikasi_rejected_by')->nullable()->after('batal_verifikasi_verified_at')->constrained('users')->nullOnDelete();
            $table->timestamp('batal_verifikasi_rejected_at')->nullable()->after('batal_verifikasi_rejected_by');
            $table->text('batal_verifikasi_rejected_reason')->nullable()->after('batal_verifikasi_rejected_at');
        });
    }

    public function down(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('batal_verifikasi_requested_by');
            $table->dropConstrainedForeignId('batal_verifikasi_verified_by');
            $table->dropConstrainedForeignId('batal_verifikasi_rejected_by');
            $table->dropColumn([
                'batal_verifikasi_status',
                'batal_verifikasi_requested_at',
                'batal_verifikasi_reason',
                'batal_verifikasi_verified_at',
                'batal_verifikasi_rejected_at',
                'batal_verifikasi_rejected_reason',
            ]);
        });
    }
};
