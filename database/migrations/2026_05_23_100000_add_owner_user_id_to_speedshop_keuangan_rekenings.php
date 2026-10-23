<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('speedshop_keuangan_rekenings')) {
            return;
        }

        if (! Schema::hasColumn('speedshop_keuangan_rekenings', 'owner_user_id')) {
            Schema::table('speedshop_keuangan_rekenings', function (Blueprint $table) {
                $table->foreignId('owner_user_id')
                    ->nullable()
                    ->after('warehouse_id')
                    ->constrained('users')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('speedshop_keuangan_rekenings')
            || ! Schema::hasColumn('speedshop_keuangan_rekenings', 'owner_user_id')) {
            return;
        }

        Schema::table('speedshop_keuangan_rekenings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('owner_user_id');
        });
    }
};
