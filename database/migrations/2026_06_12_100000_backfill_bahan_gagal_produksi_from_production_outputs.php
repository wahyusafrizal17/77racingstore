<?php

use App\Models\BahanGagalProduksi;
use App\Models\BahanSiapProduksi;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $aggregates = DB::table('production_outputs')
            ->selectRaw('bahan_siap_produksi_id, SUM(COALESCE(jumlah_gagal, 0) + COALESCE(jumlah_gagal_bahan, 0)) as total_gagal')
            ->groupBy('bahan_siap_produksi_id')
            ->having('total_gagal', '>', 0)
            ->get();

        foreach ($aggregates as $row) {
            $bsp = BahanSiapProduksi::find($row->bahan_siap_produksi_id);
            if ($bsp === null) {
                continue;
            }

            $totalGagal = (int) $row->total_gagal;
            $existing = BahanGagalProduksi::query()
                ->where('bahan_siap_produksi_id', $bsp->id)
                ->first();

            if ($existing !== null) {
                if ((int) $existing->stok < $totalGagal) {
                    $existing->update(['stok' => $totalGagal]);
                }

                continue;
            }

            BahanGagalProduksi::create([
                'bahan_siap_produksi_id' => $bsp->id,
                'kode' => BahanGagalProduksi::generateKode(),
                'nama' => $bsp->nama.' (Gagal)',
                'stok' => $totalGagal,
            ]);
        }
    }

    public function down(): void
    {
        // Data backfill; tidak di-rollback otomatis.
    }
};
