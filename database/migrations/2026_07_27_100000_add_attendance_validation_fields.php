<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('attendance_validator_id')
                ->nullable()
                ->after('speedshop_id')
                ->constrained('users')
                ->nullOnDelete();
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->foreignId('validated_by')
                ->nullable()
                ->after('status')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('validated_at')->nullable()->after('validated_by');
            $table->text('rejection_reason')->nullable()->after('validated_at');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropConstrainedForeignId('validated_by');
            $table->dropColumn(['validated_at', 'rejection_reason']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('attendance_validator_id');
        });
    }
};
