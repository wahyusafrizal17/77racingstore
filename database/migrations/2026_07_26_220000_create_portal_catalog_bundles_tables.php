<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portal_catalog_bundles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->unsignedBigInteger('warehouse_reseller_id');
            $table->string('owner_portal', 20);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['warehouse_id', 'warehouse_reseller_id', 'owner_portal'], 'portal_bundle_owner_idx');
        });

        Schema::create('portal_catalog_bundle_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portal_catalog_bundle_id')
                ->constrained('portal_catalog_bundles')
                ->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->unsignedInteger('qty')->default(1);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['portal_catalog_bundle_id', 'product_id'], 'portal_bundle_product_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_catalog_bundle_items');
        Schema::dropIfExists('portal_catalog_bundles');
    }
};
