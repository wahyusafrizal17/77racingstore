<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('speedshop_finance_setorans')) {
            return;
        }

        // InnoDB: FK speedshop_id → speedshops memakai indeks awalan unique (speedshop_id, tanggal_setor).
        // Tambah indeks pada speedshop_id dulu, baru unique bisa dihapus.
        Schema::table('speedshop_finance_setorans', function (Blueprint $table) {
            try {
                $table->index('speedshop_id', 'speedshop_finance_setorans_speedshop_id_index');
            } catch (\Throwable $e) {
            }
        });

        Schema::table('speedshop_finance_setorans', function (Blueprint $table) {
            try {
                $table->dropUnique(['speedshop_id', 'tanggal_setor']);
            } catch (\Throwable $e) {
            }
        });

        Schema::table('speedshop_finance_setorans', function (Blueprint $table) {
            try {
                $table->index(['speedshop_id', 'tanggal_setor'], 'speedshop_finance_setorans_ssid_tgl_idx');
            } catch (\Throwable $e) {
            }
        });

        Schema::table('speedshop_finance_setorans', function (Blueprint $table) {
            if (! Schema::hasColumn('speedshop_finance_setorans', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('speedshop_finance_setorans', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
            if (! Schema::hasColumn('speedshop_finance_setorans', 'rejected_reason')) {
                $table->text('rejected_reason')->nullable()->after('approved_at');
            }
        });

        DB::table('speedshop_finance_setorans')->whereNull('status')->update(['status' => 'completed']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('speedshop_finance_setorans')) {
            return;
        }

        Schema::table('speedshop_finance_setorans', function (Blueprint $table) {
            if (Schema::hasColumn('speedshop_finance_setorans', 'rejected_reason')) {
                $table->dropColumn('rejected_reason');
            }
            if (Schema::hasColumn('speedshop_finance_setorans', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
            if (Schema::hasColumn('speedshop_finance_setorans', 'approved_by')) {
                $table->dropConstrainedForeignId('approved_by');
            }
        });

        Schema::table('speedshop_finance_setorans', function (Blueprint $table) {
            try {
                $table->dropIndex('speedshop_finance_setorans_ssid_tgl_idx');
            } catch (\Throwable $e) {
            }
        });

        Schema::table('speedshop_finance_setorans', function (Blueprint $table) {
            try {
                $table->unique(['speedshop_id', 'tanggal_setor']);
            } catch (\Throwable $e) {
            }
        });

        Schema::table('speedshop_finance_setorans', function (Blueprint $table) {
            try {
                $table->dropIndex('speedshop_finance_setorans_speedshop_id_index');
            } catch (\Throwable $e) {
            }
        });
    }
};
