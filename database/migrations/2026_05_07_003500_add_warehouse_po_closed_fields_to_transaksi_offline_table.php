<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi_offline', function (Blueprint $table) {
            if (! Schema::hasColumn('transaksi_offline', 'warehouse_po_closed_at')) {
                $table->timestamp('warehouse_po_closed_at')->nullable()->after('warehouse_po_review_rejected_reason');
            }
            if (! Schema::hasColumn('transaksi_offline', 'warehouse_po_closed_by')) {
                $table->foreignId('warehouse_po_closed_by')->nullable()->after('warehouse_po_closed_at')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('transaksi_offline', 'warehouse_po_closed_reason')) {
                $table->text('warehouse_po_closed_reason')->nullable()->after('warehouse_po_closed_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transaksi_offline', function (Blueprint $table) {
            if (Schema::hasColumn('transaksi_offline', 'warehouse_po_closed_by')) {
                $table->dropConstrainedForeignId('warehouse_po_closed_by');
            }
            if (Schema::hasColumn('transaksi_offline', 'warehouse_po_closed_reason')) {
                $table->dropColumn('warehouse_po_closed_reason');
            }
            if (Schema::hasColumn('transaksi_offline', 'warehouse_po_closed_at')) {
                $table->dropColumn('warehouse_po_closed_at');
            }
        });
    }
};

