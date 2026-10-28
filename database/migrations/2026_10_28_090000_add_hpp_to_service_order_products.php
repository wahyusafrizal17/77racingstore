<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_order_products', function (Blueprint $table) {
            $table->decimal('hpp', 15, 2)->nullable()->after('harga');
        });

        // Backfill WO selesai dengan HPP master saat ini (data historis terbaik yang tersedia).
        DB::table('service_order_products')
            ->join('service_orders', 'service_order_products.service_order_id', '=', 'service_orders.id')
            ->join('products', 'service_order_products.product_id', '=', 'products.id')
            ->where('service_orders.status', 'selesai')
            ->whereNull('service_order_products.hpp')
            ->update([
                'service_order_products.hpp' => DB::raw('COALESCE(NULLIF(products.hpp, 0), products.harga_pembelian, 0)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('service_order_products', function (Blueprint $table) {
            $table->dropColumn('hpp');
        });
    }
};
