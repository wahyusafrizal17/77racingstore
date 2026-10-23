<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mutasis', function (Blueprint $table) {
            if (! Schema::hasColumn('mutasis', 'rejected_reason')) {
                $table->text('rejected_reason')->nullable()->after('rejected_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mutasis', function (Blueprint $table) {
            if (Schema::hasColumn('mutasis', 'rejected_reason')) {
                $table->dropColumn('rejected_reason');
            }
        });
    }
};

