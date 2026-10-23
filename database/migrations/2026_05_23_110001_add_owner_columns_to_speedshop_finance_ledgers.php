<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('speedshop_finance_ledgers')) {
            return;
        }

        Schema::table('speedshop_finance_ledgers', function (Blueprint $table) {
            if (! Schema::hasColumn('speedshop_finance_ledgers', 'owner_user_id')) {
                $table->foreignId('owner_user_id')
                    ->nullable()
                    ->after('warehouse_id')
                    ->constrained('users')
                    ->cascadeOnDelete();
            }
            if (! Schema::hasColumn('speedshop_finance_ledgers', 'owner_modal_setoran_id')) {
                $table->foreignId('owner_modal_setoran_id')
                    ->nullable()
                    ->after('setoran_id')
                    ->constrained('speedshop_owner_modal_setorans')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('speedshop_finance_ledgers')) {
            return;
        }

        Schema::table('speedshop_finance_ledgers', function (Blueprint $table) {
            if (Schema::hasColumn('speedshop_finance_ledgers', 'owner_modal_setoran_id')) {
                $table->dropConstrainedForeignId('owner_modal_setoran_id');
            }
            if (Schema::hasColumn('speedshop_finance_ledgers', 'owner_user_id')) {
                $table->dropConstrainedForeignId('owner_user_id');
            }
        });
    }
};
