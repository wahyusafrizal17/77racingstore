<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Sebelumnya enum('cash','transfer'); WO memakai qris/edc juga.
            $table->string('metode_pembayaran', 32)->default('cash')->change();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->enum('metode_pembayaran', ['cash', 'transfer'])->default('cash')->change();
        });
    }
};
