<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('section', 20)->default('produksi')->after('id');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->index('section');
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropIndex(['section']);
            $table->dropColumn('section');
        });
    }
};
