<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('hpp', 15, 0)->default(0)->after('harga_jual');
            $table->decimal('harga_jual_speedshop', 15, 0)->default(0)->after('hpp');
            $table->decimal('harga_jual_reseler', 15, 0)->default(0)->after('harga_jual_speedshop');
            $table->decimal('harga_eceran_terendah', 15, 0)->default(0)->after('harga_jual_reseler');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['hpp', 'harga_jual_speedshop', 'harga_jual_reseler', 'harga_eceran_terendah']);
        });
    }
};
