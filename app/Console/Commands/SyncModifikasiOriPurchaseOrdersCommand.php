<?php

namespace App\Console\Commands;

use App\Models\SpeedshopPurchaseOrder;
use App\Services\ModifikasiOri\ModifikasiOriApiException;
use App\Services\ModifikasiOri\MoPurchaseOrderService;
use Illuminate\Console\Command;

/**
 * Tarik status PO dari warehouse Modifikasi Ori (diterima / ditolak / dikirim + qty disetujui).
 * Dijadwalkan tiap 5 menit di routes/console.php; aktifkan cron `php artisan schedule:run`.
 */
class SyncModifikasiOriPurchaseOrdersCommand extends Command
{
    protected $signature = 'mo-po:sync {--resend : Kirim ulang PO yang berstatus gagal terkirim}';

    protected $description = 'Sinkronkan status Purchase Order 77 Racing dari API Modifikasi Ori';

    public function handle(MoPurchaseOrderService $service): int
    {
        if (! $service->client()->isConfigured()) {
            $this->warn('MO_PARTNER_API_URL / MO_PARTNER_API_TOKEN belum diisi — dilewati.');

            return self::SUCCESS;
        }

        $pending = SpeedshopPurchaseOrder::query()
            ->whereNotNull('mo_status')
            ->whereNotIn('mo_status', SpeedshopPurchaseOrder::MO_FINAL_STATUSES)
            ->get();

        try {
            $updated = $service->syncMany($pending);
        } catch (ModifikasiOriApiException $e) {
            $this->error('Sinkron gagal: '.$e->getMessage());

            return self::FAILURE;
        }
        $this->info("{$updated} PO diperbarui dari Modifikasi Ori.");

        if ($this->option('resend')) {
            $failed = SpeedshopPurchaseOrder::query()
                ->where('mo_status', SpeedshopPurchaseOrder::MO_STATUS_SEND_FAILED)
                ->get();
            foreach ($failed as $po) {
                try {
                    $service->submit($po);
                    $this->info("PO {$po->kode} terkirim.");
                } catch (ModifikasiOriApiException $e) {
                    $this->warn("PO {$po->kode} gagal dikirim: {$e->getMessage()}");
                }
            }
        }

        return self::SUCCESS;
    }
}
