<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_order_jasa', function (Blueprint $table) {
            $table->unsignedInteger('qty')->default(1)->after('jasa_servis_id');
        });
    }

    public function down(): void
    {
        Schema::table('service_order_jasa', function (Blueprint $table) {
            $table->dropColumn('qty');
        });
    }
};

