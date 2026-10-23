<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\ServiceOrder;
use Illuminate\Console\Command;

class SyncSpeedshopWorkOrderInvoiceDates extends Command
{
    protected $signature = 'speedshop:sync-work-order-invoice-dates {--dry-run : Hanya tampilkan jumlah yang akan diperbarui}';

    protected $description = 'Samakan tanggal invoice & ledger ke tanggal selesai work order (perbaikan data lama)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $updated = 0;
        $filledSelesai = 0;

        ServiceOrder::query()
            ->where('status', 'selesai')
            ->with('invoice')
            ->orderBy('id')
            ->chunkById(200, function ($orders) use ($dryRun, &$updated, &$filledSelesai) {
                foreach ($orders as $serviceOrder) {
                    if (! $serviceOrder->tanggal_selesai) {
                        $fallback = $serviceOrder->updated_at?->toDateString()
                            ?? $serviceOrder->invoice?->tanggal?->toDateString()
                            ?? now()->toDateString();
                        if (! $dryRun) {
                            $serviceOrder->update(['tanggal_selesai' => $fallback]);
                            $serviceOrder->refresh();
                        }
                        $filledSelesai++;
                    }

                    if (! $serviceOrder->invoice) {
                        continue;
                    }

                    $target = $serviceOrder->tanggal_selesai
                        ? \Carbon\Carbon::parse($serviceOrder->tanggal_selesai)->toDateString()
                        : now()->toDateString();

                    if ($serviceOrder->invoice->tanggal?->toDateString() === $target) {
                        continue;
                    }

                    if ($dryRun) {
                        $updated++;

                        continue;
                    }

                    Invoice::syncTanggalFromServiceOrder($serviceOrder->invoice, $serviceOrder->fresh());
                    $updated++;
                }
            });

        $this->info($dryRun ? '[dry-run] ' : ''."WO tanpa tanggal_selesai diisi: {$filledSelesai}");
        $this->info($dryRun ? '[dry-run] ' : ''."Invoice tanggal diselaraskan: {$updated}");

        return self::SUCCESS;
    }
}
