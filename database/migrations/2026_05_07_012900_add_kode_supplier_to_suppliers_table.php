<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            if (! Schema::hasColumn('suppliers', 'kode_supplier')) {
                $table->string('kode_supplier', 50)->nullable()->after('section');
                $table->index('kode_supplier');
            }
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            if (Schema::hasColumn('suppliers', 'kode_supplier')) {
                $table->dropIndex(['kode_supplier']);
                $table->dropColumn('kode_supplier');
            }
        });
    }
};

