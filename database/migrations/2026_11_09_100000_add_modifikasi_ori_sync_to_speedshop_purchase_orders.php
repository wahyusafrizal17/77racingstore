<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PO 77 Racing → warehouse Modifikasi Ori via API. Hanya menambah kolom (data lama aman).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('speedshop_purchase_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('speedshop_purchase_orders', 'mo_status')) {
                // null = PO lama (sebelum integrasi). Lihat App\Services\ModifikasiOri\MoPurchaseOrderSync.
                $table->string('mo_status', 40)->nullable()->after('status');
                $table->string('mo_kode', 100)->nullable()->after('mo_status');
                $table->string('mo_no_transaksi', 100)->nullable()->after('mo_kode');
                $table->text('mo_reason')->nullable()->after('mo_no_transaksi');
                $table->json('mo_tagihan')->nullable()->after('mo_reason');
                $table->timestamp('mo_synced_at')->nullable()->after('mo_tagihan');
                $table->text('mo_sync_error')->nullable()->after('mo_synced_at');
                $table->timestamp('received_at')->nullable()->after('mo_sync_error');
                $table->foreignId('received_by')->nullable()->after('received_at')->constrained('users')->nullOnDelete();
                $table->index('mo_status');
            }
        });

        Schema::table('speedshop_purchase_order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('speedshop_purchase_order_items', 'qty_approved')) {
                $table->unsignedInteger('qty_approved')->nullable()->after('qty');
                $table->unsignedBigInteger('harga_satuan')->nullable()->after('qty_approved');
            }
        });
    }

    public function down(): void
    {
        Schema::table('speedshop_purchase_order_items', function (Blueprint $table) {
            if (Schema::hasColumn('speedshop_purchase_order_items', 'qty_approved')) {
                $table->dropColumn(['qty_approved', 'harga_satuan']);
            }
        });

        Schema::table('speedshop_purchase_orders', function (Blueprint $table) {
            if (Schema::hasColumn('speedshop_purchase_orders', 'mo_status')) {
                $table->dropConstrainedForeignId('received_by');
                $table->dropIndex(['mo_status']);
                $table->dropColumn(['mo_status', 'mo_kode', 'mo_no_transaksi', 'mo_reason', 'mo_tagihan', 'mo_synced_at', 'mo_sync_error', 'received_at']);
            }
        });
    }
};
