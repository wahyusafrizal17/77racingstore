<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->timestamp('pemohon_signed_at')->nullable()->after('owner_signature_token');
            $table->uuid('pemohon_signature_token')->nullable()->after('pemohon_signed_at');
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn(['pemohon_signed_at', 'pemohon_signature_token']);
        });
    }
};
