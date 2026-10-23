<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Produk katalog gudang (speedshop_scope_id = 0) dapat disembunyikan dari master warehouse
     * tanpa soft delete — cabang Speedshop tetap memakai baris yang sama.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'hidden_from_warehouse_master_at')) {
                $table->timestamp('hidden_from_warehouse_master_at')->nullable()->after('deleted_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'hidden_from_warehouse_master_at')) {
                $table->dropColumn('hidden_from_warehouse_master_at');
            }
        });
    }
};
