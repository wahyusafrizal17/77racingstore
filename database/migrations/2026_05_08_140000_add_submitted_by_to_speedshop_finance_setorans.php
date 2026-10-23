<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('speedshop_finance_setorans')) {
            return;
        }

        if (! Schema::hasColumn('speedshop_finance_setorans', 'submitted_by')) {
            Schema::table('speedshop_finance_setorans', function (Blueprint $table) {
                $table->string('submitted_by', 24)->default('speedshop')->after('user_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('speedshop_finance_setorans') && Schema::hasColumn('speedshop_finance_setorans', 'submitted_by')) {
            Schema::table('speedshop_finance_setorans', function (Blueprint $table) {
                $table->dropColumn('submitted_by');
            });
        }
    }
};
