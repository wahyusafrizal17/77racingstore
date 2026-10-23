<?php

use App\Models\BahanGagalProduksi;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $totals = DB::table('production_outputs')
            ->selectRaw('bahan_siap_produksi_id, SUM(COALESCE(jumlah_gagal, 0)) as total_gagal')
            ->groupBy('bahan_siap_produksi_id')
            ->pluck('total_gagal', 'bahan_siap_produksi_id');

        BahanGagalProduksi::query()->each(function (BahanGagalProduksi $bgp) use ($totals) {
            $stok = (int) ($totals[$bgp->bahan_siap_produksi_id] ?? 0);
            $bgp->update(['stok' => $stok]);
        });
    }

    public function down(): void
    {
        // Koreksi data; tidak di-rollback otomatis.
    }
};
