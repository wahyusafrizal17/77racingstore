<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach ([
            'dinas_report_evidences',
            'dinas_item_reports',
            'dinas_pengajuan_items',
            'dinas_pengajuan_assignees',
            'dinas_pengajuans',
            'dinas_perintah_items',
            'dinas_perintah_assignees',
            'dinas_perintahs',
        ] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void {}
};
