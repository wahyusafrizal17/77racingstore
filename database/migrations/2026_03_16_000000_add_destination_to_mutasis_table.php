<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mutasis', function (Blueprint $table) {
            if (!Schema::hasColumn('mutasis', 'destination_type')) {
                $table->enum('destination_type', ['warehouse', 'speedshop'])
                    ->default('warehouse')
                    ->after('sumber');
            }
            if (!Schema::hasColumn('mutasis', 'destination_speedshop_id')) {
                $table->foreignId('destination_speedshop_id')
                    ->nullable()
                    ->after('destination_type')
                    ->constrained('speedshops')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('mutasis', function (Blueprint $table) {
            if (Schema::hasColumn('mutasis', 'destination_speedshop_id')) {
                $table->dropForeign(['destination_speedshop_id']);
                $table->dropColumn('destination_speedshop_id');
            }
            if (Schema::hasColumn('mutasis', 'destination_type')) {
                $table->dropColumn('destination_type');
            }
        });
    }
};

