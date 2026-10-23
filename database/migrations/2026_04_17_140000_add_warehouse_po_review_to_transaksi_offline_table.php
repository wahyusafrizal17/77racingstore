<?php

use App\Models\TransaksiOffline;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi_offline', function (Blueprint $table) {
            $table->timestamp('warehouse_po_reviewed_at')->nullable()->after('speedshop_purchase_order_id');
            $table->foreignId('warehouse_po_reviewed_by')->nullable()->after('warehouse_po_reviewed_at')->constrained('users')->nullOnDelete();
            $table->timestamp('warehouse_po_review_rejected_at')->nullable()->after('warehouse_po_reviewed_by');
            $table->foreignId('warehouse_po_review_rejected_by')->nullable()->after('warehouse_po_review_rejected_at')->constrained('users')->nullOnDelete();
            $table->text('warehouse_po_review_rejected_reason')->nullable()->after('warehouse_po_review_rejected_by');
        });

        // Sudah ada mutasi: anggap pengajuan sudah disetujui manager (data lama).
        foreach (TransaksiOffline::whereNotNull('speedshop_purchase_order_id')->whereNotNull('petugas_id')->cursor() as $t) {
            $t->update([
                'warehouse_po_reviewed_at' => $t->created_at,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('transaksi_offline', function (Blueprint $table) {
            $table->dropConstrainedForeignId('warehouse_po_review_rejected_by');
            $table->dropColumn('warehouse_po_review_rejected_reason');
            $table->dropColumn('warehouse_po_review_rejected_at');
            $table->dropConstrainedForeignId('warehouse_po_reviewed_by');
            $table->dropColumn('warehouse_po_reviewed_at');
        });
    }
};
