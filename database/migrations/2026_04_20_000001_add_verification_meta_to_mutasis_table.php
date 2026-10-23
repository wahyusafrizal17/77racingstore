<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mutasis', function (Blueprint $table) {
            $table->timestamp('verified_at')->nullable()->after('catatan');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete()->after('verified_at');

            $table->timestamp('rejected_at')->nullable()->after('verified_by');
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete()->after('rejected_at');
        });
    }

    public function down(): void
    {
        Schema::table('mutasis', function (Blueprint $table) {
            $table->dropConstrainedForeignId('verified_by');
            $table->dropColumn('verified_at');

            $table->dropConstrainedForeignId('rejected_by');
            $table->dropColumn('rejected_at');
        });
    }
};
