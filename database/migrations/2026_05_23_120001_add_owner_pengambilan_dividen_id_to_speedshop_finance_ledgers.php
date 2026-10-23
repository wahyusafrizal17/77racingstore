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

        if (! Schema::hasColumn('speedshop_finance_ledgers', 'owner_pengambilan_dividen_id')) {
            Schema::table('speedshop_finance_ledgers', function (Blueprint $table) {
                $table->foreignId('owner_pengambilan_dividen_id')
                    ->nullable()
                    ->after('owner_modal_setoran_id')
                    ->constrained('speedshop_owner_pengambilan_dividens')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('speedshop_finance_ledgers')
            || ! Schema::hasColumn('speedshop_finance_ledgers', 'owner_pengambilan_dividen_id')) {
            return;
        }

        Schema::table('speedshop_finance_ledgers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('owner_pengambilan_dividen_id');
        });
    }
};
