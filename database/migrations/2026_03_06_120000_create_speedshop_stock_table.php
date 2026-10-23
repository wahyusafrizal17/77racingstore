<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('speedshop_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('speedshop_id')->constrained('speedshops')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->unsignedInteger('jumlah')->default(0);
            $table->timestamps();

            $table->unique(['speedshop_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('speedshop_stock');
    }
};
