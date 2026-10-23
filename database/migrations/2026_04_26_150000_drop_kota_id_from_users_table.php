<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['kota_id']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('kota_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('kota_id')->nullable()->after('section')->constrained('kotas')->nullOnDelete();
        });
    }
};
