<?php

use App\Models\SpeedshopPurchaseOrder;
use App\Models\TransaksiOffline;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi_offline', function (Blueprint $table) {
            $table->foreignId('speedshop_purchase_order_id')
                ->nullable()
                ->after('warehouse_id')
                ->constrained('speedshop_purchase_orders')
                ->nullOnDelete();
        });

        $this->backfillLinksFromExistingPurchaseOrders();
    }

    public function down(): void
    {
        Schema::table('transaksi_offline', function (Blueprint $table) {
            $table->dropConstrainedForeignId('speedshop_purchase_order_id');
        });
    }

    /**
     * Transaksi offline yang dibuat bersamaan dengan PO (mirror untuk warehouse)
     * tidak boleh tampil ganda di daftar gabungan — isi FK untuk data lama.
     */
    private function backfillLinksFromExistingPurchaseOrders(): void
    {
        foreach (SpeedshopPurchaseOrder::with('items')->orderBy('id')->cursor() as $po) {
            $candidates = TransaksiOffline::with('items')
                ->where('speedshop_id', $po->speedshop_id)
                ->where('warehouse_id', $po->warehouse_id)
                ->where('tujuan', 'speedshop')
                ->whereNull('speedshop_purchase_order_id')
                ->whereBetween('created_at', [
                    $po->created_at->copy()->subSecond(),
                    $po->created_at->copy()->addSecond(),
                ])
                ->get();

            foreach ($candidates as $to) {
                $poQty = (int) $po->items->sum('qty');
                $toQty = (int) $to->items->sum('qty');
                if ($poQty === $toQty && $to->items->count() === $po->items->count()) {
                    $to->update(['speedshop_purchase_order_id' => $po->id]);
                    break;
                }
            }
        }
    }
};
