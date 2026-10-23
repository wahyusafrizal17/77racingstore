<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'packing_items',
            'packing_details',
            'packings',
            'speedshop_chat_messages',
            'speedshop_chats',
            'stock_mutation_items',
            'stock_mutations',
            'indonesia_villages',
            'indonesia_districts',
            'indonesia_regencies',
            'indonesia_provinces',
            'marketplace_customer_addresses',
            'marketplace_product_likes',
            'portal_home_banners',
            'reseller_laporan_daily_fees',
            'reseller_product_likes',
            'reseller_sales_targets',
            'server_subscription_items',
            'warehouse_status_resi',
            'website_visits',
            'wips',
            'biaya_produksis',
            'produksi_settings',
        ];

        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }

        foreach (['anggotas', 'productions'] as $name) {
            if (Schema::hasTable($name) && Schema::hasColumn($name, 'team_produksi_id')) {
                Schema::table($name, function (Blueprint $table) {
                    $table->dropForeign(['team_produksi_id']);
                    $table->dropColumn('team_produksi_id');
                });
            }
        }

        Schema::dropIfExists('team_produksis');
    }

    public function down(): void
    {
    }
};
