<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('stock_movements')) {
            return;
        }

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->index(['product_id', 'created_at'], 'stock_movements_product_created_idx');
            $table->index('created_at', 'stock_movements_created_at_idx');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('stock_movements')) {
            return;
        }

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex('stock_movements_product_created_idx');
            $table->dropIndex('stock_movements_created_at_idx');
        });
    }
};
