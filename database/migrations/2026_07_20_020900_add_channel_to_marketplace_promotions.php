<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_promotions', function (Blueprint $table) {
            $table->string('channel', 16)->default('both')->after('type');
            $table->index(['warehouse_id', 'channel'], 'mp_promo_channel_idx');
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_promotions', function (Blueprint $table) {
            $table->dropIndex('mp_promo_channel_idx');
            $table->dropColumn('channel');
        });
    }
};
