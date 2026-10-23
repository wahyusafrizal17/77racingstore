<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('speedshop_master_product_archives')) {
            return;
        }

        Schema::create('speedshop_master_product_archives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('speedshop_id')->constrained('speedshops')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->timestamp('archived_at');
            $table->timestamps();

            $table->unique(['speedshop_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('speedshop_master_product_archives');
    }
};
