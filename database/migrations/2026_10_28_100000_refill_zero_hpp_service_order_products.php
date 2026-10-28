<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Snapshot HPP 0 tidak bermakna (produk MO tidak punya products.hpp); isi ulang dengan HPP Speedshop.
        $isShared = "(products.jenis_produk IN ('merchandise', 'consumable')"
            ." OR (COALESCE(products.jenis_produk, '') <> 'part_luar' AND COALESCE(products.speedshop_scope_id, 0) = 0))";
        $productHpp = "(CASE WHEN {$isShared}"
            .' THEN COALESCE(NULLIF(products.harga_jual_speedshop, 0), NULLIF(products.hpp, 0), products.harga_pembelian, 0)'
            .' ELSE COALESCE(NULLIF(products.hpp, 0), products.harga_pembelian, 0) END)';

        DB::table('service_order_products')
            ->join('service_orders', 'service_order_products.service_order_id', '=', 'service_orders.id')
            ->join('products', 'service_order_products.product_id', '=', 'products.id')
            ->where('service_orders.status', 'selesai')
            ->where(fn ($q) => $q->whereNull('service_order_products.hpp')->orWhere('service_order_products.hpp', 0))
            ->update([
                'service_order_products.hpp' => DB::raw("NULLIF({$productHpp}, 0)"),
            ]);
    }

    public function down(): void
    {
    }
};
