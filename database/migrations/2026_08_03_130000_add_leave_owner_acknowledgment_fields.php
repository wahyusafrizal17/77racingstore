<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->foreignId('owner_approver_id')->nullable()->after('manager_approver_id')->constrained('users')->nullOnDelete();
            $table->timestamp('owner_signed_at')->nullable()->after('manager_signature_token');
            $table->uuid('owner_signature_token')->nullable()->after('owner_signed_at');
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('owner_approver_id');
            $table->dropColumn(['owner_signed_at', 'owner_signature_token']);
        });
    }
};
