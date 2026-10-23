<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('kode_invoice', 30)->unique();
            $table->foreignId('service_order_id')->constrained('service_orders')->cascadeOnDelete();
            $table->decimal('total_jasa', 15, 0)->default(0);
            $table->decimal('total_sparepart', 15, 0)->default(0);
            $table->decimal('diskon', 15, 0)->default(0);
            $table->decimal('grand_total', 15, 0)->default(0);
            $table->enum('metode_pembayaran', ['cash', 'transfer'])->default('cash');
            $table->date('tanggal');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
