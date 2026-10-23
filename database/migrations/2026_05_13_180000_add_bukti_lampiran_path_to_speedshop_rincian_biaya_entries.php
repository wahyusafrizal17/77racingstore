<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('speedshop_rincian_biaya_entries')) {
            return;
        }
        if (Schema::hasColumn('speedshop_rincian_biaya_entries', 'bukti_lampiran_path')) {
            return;
        }
        Schema::table('speedshop_rincian_biaya_entries', function (Blueprint $table) {
            $table->string('bukti_lampiran_path', 512)->nullable()->after('keterangan');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('speedshop_rincian_biaya_entries')) {
            return;
        }
        if (! Schema::hasColumn('speedshop_rincian_biaya_entries', 'bukti_lampiran_path')) {
            return;
        }
        Schema::table('speedshop_rincian_biaya_entries', function (Blueprint $table) {
            $table->dropColumn('bukti_lampiran_path');
        });
    }
};
