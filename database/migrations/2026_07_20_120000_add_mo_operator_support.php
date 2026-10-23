<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('mo_operator_channels')) {
            Schema::create('mo_operator_channels', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->unsignedBigInteger('warehouse_reseller_id');
                $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['user_id', 'warehouse_reseller_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mo_operator_channels');
    }
};
