<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Invoice extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::created(function (Invoice $invoice) {
            if (! Schema::hasTable('speedshop_finance_ledgers')) {
                return;
            }
            app(\App\Services\SpeedshopFinanceService::class)->recordInvoiceIncome($invoice);
        });
    }

    protected $fillable = [
        'kode_invoice', 'service_order_id', 'total_jasa', 'total_sparepart',
        'diskon',
        'charge_percent',
        'charge_mode',
        'charge_rupiah',
        'charge_amount',
        'ppn_percent',
        'ppn_mode',
        'ppn_rupiah',
        'ppn_amount',
        'grand_total',
        'metode_pembayaran',
        'pembayaran_split',
        'tanggal',
        'catatan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'pembayaran_split' => 'array',
    ];

    /**
     * @param  list<array{metode?: string, jumlah?: int}>|null  $split
     * @return array{0: int, 1: int} [tunai, digital]
     */
    public static function splitAmountsTunaiDigital(?array $split): array
    {
        if ($split === null || $split === []) {
            return [0, 0];
        }
        /** @var \App\Services\SpeedshopFinanceService $finance */
        $finance = app(\App\Services\SpeedshopFinanceService::class);
        $tunai = 0;
        $digital = 0;
        foreach ($split as $row) {
            $metode = strtolower(trim((string) ($row['metode'] ?? '')));
            $jumlah = (int) ($row['jumlah'] ?? 0);
            if ($jumlah <= 0) {
                continue;
            }
            if ($finance->isMetodeTunai($metode)) {
                $tunai += $jumlah;
            } else {
                $digital += $jumlah;
            }
        }

        return [$tunai, $digital];
    }

    public function serviceOrder()
    {
        return $this->belongsTo(ServiceOrder::class);
    }

    public function financeLedgers()
    {
        return $this->hasMany(SpeedshopFinanceLedger::class);
    }

    /**
     * Nomor invoice speedshop: INV/SS/{KTP|CLK|CSK|UNK}/{MM}/#### (4 digit, reset per bulan berdasarkan tanggal invoice).
     */
    public static function generateNextKode(ServiceOrder $serviceOrder, ?Carbon $tanggalInvoice = null): string
    {
        $tanggal = $tanggalInvoice ? Carbon::parse($tanggalInvoice) : now();
        $branch = self::resolveOutletSuffixFromServiceOrder($serviceOrder);
        $bulan = $tanggal->format('m');
        $lockKey = 'invoice-gen:'.strtoupper($branch).':'.$tanggal->format('Y-m');

        return Cache::lock($lockKey, 15)->block(8, function () use ($tanggal, $branch, $bulan) {
            return DB::transaction(function () use ($tanggal, $branch, $bulan) {
                $base = 'INV/SS/'.$branch.'/'.$bulan.'/';
                $like = $base.str_repeat('_', 4);
                $monthStart = $tanggal->copy()->startOfMonth()->toDateString();
                $monthEnd = $tanggal->copy()->endOfMonth()->toDateString();

                $last = static::query()
                    ->where('kode_invoice', 'like', $like)
                    ->whereBetween('tanggal', [$monthStart, $monthEnd])
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->first();

                $next = 1;
                if ($last !== null) {
                    $suffix = substr($last->kode_invoice, strlen($base));
                    $next = max(1, (int) $suffix) + 1;
                }

                if ($next > 9999) {
                    throw new \RuntimeException('Nomor invoice untuk cabang ini sudah mencapai batas periode.');
                }

                return $base.sprintf('%04d', $next);
            });
        });
    }

    public static function resolveOutletSuffixFromServiceOrder(ServiceOrder $serviceOrder): string
    {
        return Speedshop::resolveOutletSuffixFromPelangganId($serviceOrder->pelanggan_id);
    }

    /**
     * Label metode pembayaran untuk UI (split bill → "QRIS & Tunai", bukan "Split").
     */
    public static function formatMetodePembayaranLabel(?string $metode, mixed $split = null): string
    {
        $jenis = strtolower(trim((string) ($metode ?? '')));
        if (is_string($split)) {
            $decoded = json_decode($split, true);
            $split = is_array($decoded) ? $decoded : null;
        }

        $map = [
            'cash' => 'Tunai',
            'tunai' => 'Tunai',
            'transfer' => 'Transfer',
            'qris' => 'QRIS',
            'edc' => 'EDC',
        ];

        if ($jenis === 'split' && is_array($split) && $split !== []) {
            $parts = [];
            foreach ($split as $row) {
                $m = strtolower(trim((string) ($row['metode'] ?? $row['jenis'] ?? $row['method'] ?? '')));
                $jumlah = (int) ($row['jumlah'] ?? 0);
                if ($jumlah <= 0) {
                    continue;
                }
                $label = $map[$m] ?? ($m !== '' ? ucfirst($m) : null);
                if ($label !== null) {
                    $parts[] = $label;
                }
            }
            $parts = array_values(array_unique($parts));
            if ($parts !== []) {
                return implode(' & ', $parts);
            }

            return 'Split bill';
        }

        return match ($jenis) {
            'cash', 'tunai' => 'Tunai',
            'transfer' => 'Transfer',
            'qris' => 'QRIS',
            'edc' => 'EDC',
            default => $jenis !== '' ? ucfirst($jenis) : '-',
        };
    }

    public function metodePembayaranDisplayLabel(): string
    {
        return static::formatMetodePembayaranLabel($this->metode_pembayaran, $this->pembayaran_split);
    }

    /**
     * Samakan tanggal invoice & ledger ke tanggal selesai WO (tanggal transaksi).
     */
    public static function syncTanggalFromServiceOrder(self $invoice, ServiceOrder $serviceOrder): void
    {
        $tanggalYmd = $serviceOrder->tanggal_selesai
            ? Carbon::parse($serviceOrder->tanggal_selesai)->toDateString()
            : now()->toDateString();

        if ($invoice->tanggal?->toDateString() === $tanggalYmd) {
            return;
        }

        $invoice->update(['tanggal' => $tanggalYmd]);

        if (Schema::hasTable('speedshop_finance_ledgers')) {
            SpeedshopFinanceLedger::query()
                ->where('invoice_id', $invoice->id)
                ->update(['tanggal' => $tanggalYmd]);
        }
    }

    public static function resolveTanggalTransaksiFromServiceOrder(ServiceOrder $serviceOrder): Carbon
    {
        return $serviceOrder->tanggal_selesai
            ? Carbon::parse($serviceOrder->tanggal_selesai)
            : now();
    }

    public static function createForCompletedServiceOrder(
        ServiceOrder $serviceOrder,
        ?string $metodePembayaran = null,
        ?string $catatan = null,
    ): self {
        $existing = static::query()->where('service_order_id', $serviceOrder->id)->first();
        if ($existing) {
            static::syncTanggalFromServiceOrder($existing, $serviceOrder);

            return $existing->fresh();
        }

        $serviceOrder->loadMissing(['jasaServis', 'products', 'pelanggan.speedshop.kota']);

        $tanggal = static::resolveTanggalTransaksiFromServiceOrder($serviceOrder);

        $kode = static::generateNextKode($serviceOrder, $tanggal);

        $metode = $metodePembayaran !== null && trim($metodePembayaran) !== ''
            ? strtolower(trim($metodePembayaran))
            : strtolower(trim((string) ($serviceOrder->jenis_pembayaran ?? 'cash')));
        if ($metode === '') {
            $metode = 'cash';
        }

        $split = $serviceOrder->pembayaran_split;
        if (! is_array($split)) {
            $split = null;
        }

        $settings = app(\App\Services\InvoiceSettingsService::class)->get();
        $baseTotal = (int) $serviceOrder->grand_total;

        $chargeMode = (string) ($settings['invoice_charge_mode'] ?? 'percent');
        $chargePercent = (float) ($settings['invoice_charge_percent'] ?? 0);
        $chargeRupiah = (int) ($settings['invoice_charge_rupiah'] ?? 0);
        $chargeAmount = \App\Services\InvoiceSettingsService::calculateChargeAmount(
            $baseTotal,
            $chargeMode,
            $chargePercent,
            $chargeRupiah,
        );

        $ppnMode = (string) ($settings['invoice_ppn_mode'] ?? 'percent');
        $ppnPercent = (float) ($settings['invoice_ppn_percent'] ?? 0);
        $ppnRupiah = (int) ($settings['invoice_ppn_rupiah'] ?? 0);
        $ppnAmount = $ppnMode === 'rupiah'
            ? max(0, $ppnRupiah)
            : (int) round($baseTotal * ($ppnPercent / 100));

        $grandFinal = $baseTotal + $chargeAmount + $ppnAmount;

        return static::query()->create([
            'kode_invoice' => $kode,
            'service_order_id' => $serviceOrder->id,
            'total_jasa' => $serviceOrder->total_jasa,
            'total_sparepart' => $serviceOrder->total_sparepart,
            'diskon' => $serviceOrder->total_diskon,
            'charge_percent' => $chargePercent,
            'charge_mode' => $chargeMode,
            'charge_rupiah' => $chargeRupiah,
            'charge_amount' => $chargeAmount,
            'ppn_percent' => $ppnPercent,
            'ppn_mode' => $ppnMode,
            'ppn_rupiah' => $ppnRupiah,
            'ppn_amount' => $ppnAmount,
            'grand_total' => $grandFinal,
            'metode_pembayaran' => $metode,
            'pembayaran_split' => $split,
            'tanggal' => $tanggal->toDateString(),
            'catatan' => $catatan !== null && trim($catatan) !== '' ? trim($catatan) : null,
        ]);
    }
}
