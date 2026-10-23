<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('service_orders')) {
            return;
        }

        if (! Schema::hasColumn('service_orders', 'speedshop_id')) {
            Schema::table('service_orders', function (Blueprint $table) {
                // Diperlukan agar transaksi per cabang tidak tercampur (dashboard, service record, laporan).
                $table->foreignId('speedshop_id')
                    ->nullable()
                    ->after(Schema::hasColumn('service_orders', 'warehouse_id') ? 'warehouse_id' : 'id')
                    ->constrained('speedshops')
                    ->nullOnDelete();

                $table->index(['speedshop_id', 'status'], 'service_orders_speedshop_id_status_idx');
            });
        }

        // Backfill best-effort: baca kode invoice / kode servis untuk suffix cabang (KTP/CLK/CSK/UNK)
        // lalu mapping ke speedshops.nama yang berformat "...-KTP-01", "...-CLK-03", dst.
        if (! Schema::hasTable('speedshops')) {
            return;
        }
        if (! Schema::hasTable('invoices')) {
            return;
        }
        if (! Schema::hasColumn('invoices', 'kode_invoice')) {
            return;
        }

        /** @var array<int, object{ id:int, nama:string }> $speedshops */
        $speedshops = DB::table('speedshops')->select(['id', 'nama'])->get()->all();
        $suffixToId = [];

        foreach ($speedshops as $ss) {
            $nama = (string) ($ss->nama ?? '');
            if (preg_match('/-([A-Z]{3})-/', $nama, $m)) {
                $suffixToId[strtoupper($m[1])] = (int) $ss->id;
            }
        }

        foreach ($suffixToId as $suffix => $ssid) {
            // 1) Update dari invoice kode: INV/SS/{SUFFIX}/....
            DB::table('service_orders')
                ->join('invoices', 'invoices.service_order_id', '=', 'service_orders.id')
                ->whereNull('service_orders.speedshop_id')
                ->where('invoices.kode_invoice', 'like', 'INV/SS/'.$suffix.'/%')
                ->update(['service_orders.speedshop_id' => $ssid]);

            // 2) Fallback dari WO code: WO/SS/{SUFFIX}/....
            DB::table('service_orders')
                ->whereNull('speedshop_id')
                ->where('kode_servis', 'like', 'WO/SS/'.$suffix.'/%')
                ->update(['speedshop_id' => $ssid]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('service_orders')) {
            return;
        }

        if (Schema::hasColumn('service_orders', 'speedshop_id')) {
            Schema::table('service_orders', function (Blueprint $table) {
                $table->dropIndex('service_orders_speedshop_id_status_idx');
                $table->dropForeign(['speedshop_id']);
                $table->dropColumn('speedshop_id');
            });
        }
    }
};

