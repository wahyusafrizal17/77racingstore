<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('speedshop_finance_setorans') && ! Schema::hasColumn('speedshop_finance_setorans', 'bukti_path')) {
            Schema::table('speedshop_finance_setorans', function (Blueprint $table) {
                $table->string('bukti_path', 512)->nullable()->after('catatan');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('speedshop_finance_setorans') && Schema::hasColumn('speedshop_finance_setorans', 'bukti_path')) {
            Schema::table('speedshop_finance_setorans', function (Blueprint $table) {
                $table->dropColumn('bukti_path');
            });
        }
    }
};
