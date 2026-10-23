<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('type', 32);
            $table->string('discount_type', 16)->nullable();
            $table->unsignedBigInteger('discount_value')->default(0);
            $table->unsignedBigInteger('bundle_price')->nullable();
            $table->unsignedInteger('buy_qty')->nullable();
            $table->unsignedInteger('get_qty')->nullable();
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['warehouse_id', 'is_active', 'starts_at', 'ends_at'], 'mp_promo_active_idx');
        });

        Schema::create('marketplace_promotion_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_promotion_id')
                ->constrained('marketplace_promotions')
                ->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->string('role', 16)->default('target');
            $table->unsignedInteger('qty')->default(1);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(
                ['marketplace_promotion_id', 'product_id', 'role'],
                'mp_promo_product_role_unique'
            );
        });

        Schema::table('marketplace_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('diskon_promo')->default(0)->after('subtotal');
        });

        Schema::table('marketplace_order_items', function (Blueprint $table) {
            $table->foreignId('marketplace_promotion_id')
                ->nullable()
                ->after('product_id')
                ->constrained('marketplace_promotions')
                ->nullOnDelete();
            $table->string('promotion_name')->nullable()->after('marketplace_promotion_id');
            $table->string('promotion_type', 32)->nullable()->after('promotion_name');
            $table->unsignedBigInteger('harga_normal')->default(0)->after('qty_scanned');
            $table->unsignedBigInteger('diskon_promo')->default(0)->after('harga_satuan');
            $table->boolean('is_bonus')->default(false)->after('diskon_promo');
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_order_items', function (Blueprint $table) {
            $table->dropForeign(['marketplace_promotion_id']);
            $table->dropColumn([
                'marketplace_promotion_id',
                'promotion_name',
                'promotion_type',
                'harga_normal',
                'diskon_promo',
                'is_bonus',
            ]);
        });

        Schema::table('marketplace_orders', function (Blueprint $table) {
            $table->dropColumn('diskon_promo');
        });

        Schema::dropIfExists('marketplace_promotion_products');
        Schema::dropIfExists('marketplace_promotions');
    }
};
