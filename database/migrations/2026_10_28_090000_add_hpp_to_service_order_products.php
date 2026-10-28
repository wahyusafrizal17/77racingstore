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
        // Katalog MO / merchandise / consumable memakai harga_jual_speedshop (HPP Speedshop), lainnya products.hpp.
        $isShared = "(products.jenis_produk IN ('merchandise', 'consumable')"
            ." OR (COALESCE(products.jenis_produk, '') <> 'part_luar' AND COALESCE(products.speedshop_scope_id, 0) = 0))";
        $productHpp = "(CASE WHEN {$isShared}"
            .' THEN COALESCE(NULLIF(products.harga_jual_speedshop, 0), NULLIF(products.hpp, 0), products.harga_pembelian, 0)'
            .' ELSE COALESCE(NULLIF(products.hpp, 0), products.harga_pembelian, 0) END)';

        DB::table('service_order_products')
            ->join('service_orders', 'service_order_products.service_order_id', '=', 'service_orders.id')
            ->join('products', 'service_order_products.product_id', '=', 'products.id')
            ->where('service_orders.status', 'selesai')
            ->whereNull('service_order_products.hpp')
            ->update([
                'service_order_products.hpp' => DB::raw($productHpp),
            ]);
    }

    public function down(): void
    {
        Schema::table('service_order_products', function (Blueprint $table) {
            $table->dropColumn('hpp');
        });
    }
};
