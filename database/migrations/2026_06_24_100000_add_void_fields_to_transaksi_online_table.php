<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transaksi_online')) {
            return;
        }

        Schema::table('transaksi_online', function (Blueprint $table) {
            if (! Schema::hasColumn('transaksi_online', 'voided_at')) {
                $table->timestamp('voided_at')->nullable()->after('status');
            }
            if (! Schema::hasColumn('transaksi_online', 'voided_by')) {
                $table->unsignedBigInteger('voided_by')->nullable()->after('voided_at');
            }
            if (! Schema::hasColumn('transaksi_online', 'void_reason')) {
                $table->text('void_reason')->nullable()->after('voided_by');
            }
        });

        if (Schema::hasColumn('transaksi_online', 'voided_by')) {
            Schema::table('transaksi_online', function (Blueprint $table) {
                $table->foreign('voided_by', 'to_voided_by_fk')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('transaksi_online')) {
            return;
        }

        Schema::table('transaksi_online', function (Blueprint $table) {
            if (Schema::hasColumn('transaksi_online', 'voided_by')) {
                $table->dropForeign('to_voided_by_fk');
            }
        });

        Schema::table('transaksi_online', function (Blueprint $table) {
            foreach (['void_reason', 'voided_by', 'voided_at'] as $column) {
                if (Schema::hasColumn('transaksi_online', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
