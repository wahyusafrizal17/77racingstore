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
        if (Schema::hasColumn('speedshop_finance_setorans', 'setor_kk_ke_kb_actual')) {
            return;
        }
        Schema::table('speedshop_finance_setorans', function (Blueprint $table) {
            $table->unsignedBigInteger('setor_kk_ke_kb_actual')->default(0)->after('setor_digital_actual');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('speedshop_finance_setorans')) {
            return;
        }
        if (Schema::hasColumn('speedshop_finance_setorans', 'setor_kk_ke_kb_actual')) {
            Schema::table('speedshop_finance_setorans', function (Blueprint $table) {
                $table->dropColumn('setor_kk_ke_kb_actual');
            });
        }
    }
};
