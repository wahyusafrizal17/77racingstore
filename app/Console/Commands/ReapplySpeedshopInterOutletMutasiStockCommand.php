<?php

namespace App\Console\Commands;

use App\Models\Mutasi;
use App\Support\Speedshop\SpeedshopMutasiStockReceive;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReapplySpeedshopInterOutletMutasiStockCommand extends Command
{
    protected $signature = 'speedshop:reapply-inter-outlet-mutasi-stock
                            {--kode= : Batasi ke kode mutasi (contoh MTS/SS/KTP/07/0006)}
                            {--dry-run : Hanya daftar mutasi yang akan diproses}';

    protected $description = 'Terapkan ulang stok masuk cabang tujuan untuk mutasi antar speedshop berstatus Diterima';

    public function handle(): int
    {
        $kode = $this->option('kode') ? trim((string) $this->option('kode')) : null;
        $dryRun = (bool) $this->option('dry-run');

        $query = Mutasi::query()
            ->where('sumber', 'speedshop')
            ->where('destination_type', 'speedshop')
            ->where('status', 'diterima')
            ->whereNotNull('destination_speedshop_id')
            ->with(['items.product', 'destinationSpeedshop'])
            ->orderBy('id');

        if ($kode !== null && $kode !== '') {
            $query->where('kode', $kode);
        }

        $count = 0;
        $query->chunkById(25, function ($mutasis) use ($dryRun, &$count) {
            foreach ($mutasis as $mutasi) {
                $destId = (int) $mutasi->destination_speedshop_id;
                $destName = $mutasi->destinationSpeedshop?->nama ?? ('#'.$destId);
                $this->line("{$mutasi->kode} → {$destName} (id {$destId})");

                if (! $dryRun) {
                    DB::transaction(fn () => SpeedshopMutasiStockReceive::apply($mutasi, $destId));
                }

                $count++;
            }
        });

        $this->info($dryRun
            ? "Dry-run: {$count} mutasi akan diproses."
            : "Selesai: stok mutasi diproses untuk {$count} mutasi (baris yang sudah ada movement dilewati).");

        return self::SUCCESS;
    }
}
