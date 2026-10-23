<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi_offline', function (Blueprint $table) {
            $table->timestamp('rejected_at')->nullable()->after('verified_at');
            $table->text('rejected_reason')->nullable()->after('rejected_at');
            $table->foreignId('rejected_by')->nullable()->after('rejected_reason')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transaksi_offline', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rejected_by');
            $table->dropColumn(['rejected_at', 'rejected_reason']);
        });
    }
};
