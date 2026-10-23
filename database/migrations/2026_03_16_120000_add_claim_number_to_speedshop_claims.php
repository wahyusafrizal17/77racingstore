<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('speedshop_claims', function (Blueprint $table) {
            $table->string('claim_number', 50)->nullable()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('speedshop_claims', function (Blueprint $table) {
            $table->dropColumn('claim_number');
        });
    }
};
