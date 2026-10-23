<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->foreignId('head_approver_id')->nullable()->after('attendance_validator_id')->constrained('users')->nullOnDelete();
            $table->foreignId('manager_approver_id')->nullable()->after('head_approver_id')->constrained('users')->nullOnDelete();
            $table->timestamp('head_signed_at')->nullable()->after('manager_approver_id');
            $table->uuid('head_signature_token')->nullable()->after('head_signed_at');
            $table->timestamp('manager_signed_at')->nullable()->after('head_signature_token');
            $table->uuid('manager_signature_token')->nullable()->after('manager_signed_at');
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('head_approver_id');
            $table->dropConstrainedForeignId('manager_approver_id');
            $table->dropColumn([
                'head_signed_at',
                'head_signature_token',
                'manager_signed_at',
                'manager_signature_token',
            ]);
        });
    }
};
