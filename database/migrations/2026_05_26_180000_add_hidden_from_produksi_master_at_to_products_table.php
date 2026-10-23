<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'hidden_from_produksi_master_at')) {
                $table->timestamp('hidden_from_produksi_master_at')->nullable()->after('hidden_from_warehouse_master_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'hidden_from_produksi_master_at')) {
                $table->dropColumn('hidden_from_produksi_master_at');
            }
        });
    }
};
