<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi_offline', function (Blueprint $table) {
            if (! Schema::hasColumn('transaksi_offline', 'manager_approval_at')) {
                $table->timestamp('manager_approval_at')->nullable()->after('petugas_id');
            }
            if (! Schema::hasColumn('transaksi_offline', 'manager_approval_by')) {
                $table->foreignId('manager_approval_by')->nullable()->after('manager_approval_at')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('transaksi_offline', 'manager_rejection_at')) {
                $table->timestamp('manager_rejection_at')->nullable()->after('manager_approval_by');
            }
            if (! Schema::hasColumn('transaksi_offline', 'manager_rejection_by')) {
                $table->foreignId('manager_rejection_by')->nullable()->after('manager_rejection_at')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('transaksi_offline', 'manager_rejection_reason')) {
                $table->text('manager_rejection_reason')->nullable()->after('manager_rejection_by');
            }
        });

        // Transaksi Reseller/Umum lama: stok sudah keluar & petugas_id terisi → anggap sudah disetujui Manager.
        if (Schema::hasColumn('transaksi_offline', 'manager_approval_at')) {
            $rows = DB::table('transaksi_offline')
                ->whereNull('speedshop_purchase_order_id')
                ->whereIn('tujuan', ['reseller', 'umum'])
                ->whereNotNull('petugas_id')
                ->whereNull('manager_approval_at')
                ->get(['id', 'petugas_id', 'created_at', 'updated_at']);

            foreach ($rows as $r) {
                DB::table('transaksi_offline')->where('id', $r->id)->update([
                    'manager_approval_at' => $r->updated_at ?? $r->created_at,
                    'manager_approval_by' => $r->petugas_id,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('transaksi_offline', function (Blueprint $table) {
            if (Schema::hasColumn('transaksi_offline', 'manager_rejection_reason')) {
                $table->dropColumn('manager_rejection_reason');
            }
            if (Schema::hasColumn('transaksi_offline', 'manager_rejection_by')) {
                $table->dropForeign(['manager_rejection_by']);
                $table->dropColumn('manager_rejection_by');
            }
            if (Schema::hasColumn('transaksi_offline', 'manager_rejection_at')) {
                $table->dropColumn('manager_rejection_at');
            }
            if (Schema::hasColumn('transaksi_offline', 'manager_approval_by')) {
                $table->dropForeign(['manager_approval_by']);
                $table->dropColumn('manager_approval_by');
            }
            if (Schema::hasColumn('transaksi_offline', 'manager_approval_at')) {
                $table->dropColumn('manager_approval_at');
            }
        });
    }
};
