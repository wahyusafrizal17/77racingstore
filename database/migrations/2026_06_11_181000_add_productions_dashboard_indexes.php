<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('productions')) {
            return;
        }

        Schema::table('productions', function (Blueprint $table) {
            $table->index('tanggal', 'productions_tanggal_idx');
            $table->index(['status', 'tanggal'], 'productions_status_tanggal_idx');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('productions')) {
            return;
        }

        Schema::table('productions', function (Blueprint $table) {
            $table->dropIndex('productions_tanggal_idx');
            $table->dropIndex('productions_status_tanggal_idx');
        });
    }
};
