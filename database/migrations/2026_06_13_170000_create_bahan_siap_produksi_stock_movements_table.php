<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bahan_siap_produksi_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bahan_siap_produksi_id');
            $table->string('type', 10);
            $table->unsignedInteger('qty');
            $table->string('keterangan')->nullable();
            $table->string('reference')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('bahan_siap_produksi_id', 'bsp_stock_movements_bsp_id_fk')
                ->references('id')
                ->on('bahan_siap_produksis')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bahan_siap_produksi_stock_movements');
    }
};
