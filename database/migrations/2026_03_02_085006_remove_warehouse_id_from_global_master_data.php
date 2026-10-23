<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = ['categories', 'brands', 'suppliers', 'jasa_servis'];

        foreach ($tables as $table) {
            if (Schema::hasColumn($table, 'warehouse_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropConstrainedForeignId('warehouse_id');
                });
            }
        }
    }

    public function down(): void
    {
        $tables = ['categories', 'brands', 'suppliers', 'jasa_servis'];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            });
        }
    }
};
