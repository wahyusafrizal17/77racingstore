<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perlengkapan_produksi_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('perlengkapan_produksi_id');
            $table->string('type', 10);
            $table->unsignedInteger('qty');
            $table->string('keterangan')->nullable();
            $table->string('reference')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('perlengkapan_produksi_id', 'pp_stock_movements_pp_id_fk')
                ->references('id')
                ->on('perlengkapan_produksis')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perlengkapan_produksi_stock_movements');
    }
};
