<?php

use App\Models\Speedshop;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jasa_servis', function (Blueprint $table) {
            if (! Schema::hasColumn('jasa_servis', 'speedshop_id')) {
                $table->foreignId('speedshop_id')->nullable()->after('id')->constrained('speedshops')->nullOnDelete();
                $table->index(['speedshop_id', 'nama']);
            }
        });

        // Best-effort backfill:
        // - For existing service order items, clone jasa into the service order's speedshop and repoint pivots.
        // - Leaves legacy jasa rows with NULL speedshop_id untouched (for legacy/unknown speedshop orders).
        if (! Schema::hasTable('service_orders') || ! Schema::hasTable('service_order_jasa')) {
            return;
        }

        if (! Schema::hasColumn('service_orders', 'speedshop_id')) {
            return;
        }

        // If there are no speedshops yet, skip (will be handled by user/seed/import).
        if (! class_exists(Speedshop::class) || Speedshop::query()->count() === 0) {
            return;
        }

        $pairs = DB::table('service_order_jasa as soj')
            ->join('service_orders as so', 'so.id', '=', 'soj.service_order_id')
            ->join('jasa_servis as js', 'js.id', '=', 'soj.jasa_servis_id')
            ->whereNotNull('so.speedshop_id')
            ->select([
                'so.speedshop_id as speedshop_id',
                'js.id as old_jasa_id',
                'js.nama as nama',
                'js.biaya as biaya',
            ])
            ->distinct()
            ->get();

        foreach ($pairs as $p) {
            $speedshopId = (int) $p->speedshop_id;
            $oldJasaId = (int) $p->old_jasa_id;
            $nama = (string) ($p->nama ?? '');
            $biaya = (int) ($p->biaya ?? 0);

            if ($speedshopId <= 0 || $nama === '') {
                continue;
            }

            $existing = DB::table('jasa_servis')
                ->where('speedshop_id', $speedshopId)
                ->where('nama', $nama)
                ->first();

            $newJasaId = $existing?->id;

            if (! $newJasaId) {
                $newJasaId = DB::table('jasa_servis')->insertGetId([
                    'speedshop_id' => $speedshopId,
                    'nama' => $nama,
                    'biaya' => $biaya,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('service_order_jasa as soj')
                ->join('service_orders as so', 'so.id', '=', 'soj.service_order_id')
                ->where('so.speedshop_id', $speedshopId)
                ->where('soj.jasa_servis_id', $oldJasaId)
                ->update(['soj.jasa_servis_id' => $newJasaId]);
        }
    }

    public function down(): void
    {
        Schema::table('jasa_servis', function (Blueprint $table) {
            if (Schema::hasColumn('jasa_servis', 'speedshop_id')) {
                $table->dropForeign(['speedshop_id']);
                $table->dropIndex(['speedshop_id', 'nama']);
                $table->dropColumn('speedshop_id');
            }
        });
    }
};

