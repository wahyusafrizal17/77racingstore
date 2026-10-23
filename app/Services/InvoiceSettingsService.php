<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Speedshop;

class InvoiceSettingsService
{
    /** Charge mode persen: nominal hasil % dengan lantai minimal ini (hanya transaksi baru). */
    public const CHARGE_PERCENT_MINIMUM = 1500;

    /** Minimal jumlah produk acak pada audit stok speedshop. */
    public const STOK_AUDIT_MIN_PRODUCTS = 10;

    /** Default persentase produk yang diaudit jika belum diset manager. */
    public const STOK_AUDIT_DEFAULT_PERCENT = 15.0;

    /**
     * Hitung nominal charge untuk transaksi baru / preview WO yang belum jadi invoice.
     * Mode persen: max(CHARGE_PERCENT_MINIMUM, round(base × %)) bila % > 0.
     */
    public static function calculateChargeAmount(
        int $baseTotal,
        string $chargeMode,
        float $chargePercent,
        int $chargeRupiah,
        bool $applyMinimum = true,
    ): int {
        if ($chargeMode === 'rupiah') {
            return max(0, $chargeRupiah);
        }

        if ($chargePercent <= 0) {
            return 0;
        }

        $fromPercent = (int) round($baseTotal * ($chargePercent / 100));

        if (! $applyMinimum) {
            return max(0, $fromPercent);
        }

        return max(self::CHARGE_PERCENT_MINIMUM, $fromPercent);
    }

    /**
     * Charge untuk invoice yang sudah tersimpan: pakai nilai tersimpan,
     * jangan terapkan minimal baru ke transaksi lama.
     */
    public static function resolveChargeAmountForInvoice(Invoice $invoice, int $baseTotal): int
    {
        $stored = (int) ($invoice->charge_amount ?? 0);
        if ($stored > 0) {
            return $stored;
        }

        $ppn = (int) ($invoice->ppn_amount ?? 0);
        $grand = (int) ($invoice->grand_total ?? 0);
        if ($grand > 0) {
            return max(0, $grand - $baseTotal - $ppn);
        }

        return self::calculateChargeAmount(
            $baseTotal,
            (string) ($invoice->charge_mode ?? 'percent'),
            (float) ($invoice->charge_percent ?? 0),
            (int) ($invoice->charge_rupiah ?? 0),
            applyMinimum: false,
        );
    }

    /** @return array{
     *     invoice_charge_mode: string,
     *     invoice_charge_percent: float,
     *     invoice_charge_rupiah: int,
     *     invoice_ppn_mode: string,
     *     invoice_ppn_percent: float,
     *     invoice_ppn_rupiah: int
     * }
     */
    public function get(): array
    {
        $row = Speedshop::query()->orderBy('id')->first();

        if (! $row) {
            return $this->defaults();
        }

        return $this->fromModel($row);
    }

    public function resolveStokAuditSampleSize(int $totalProducts): int
    {
        if ($totalProducts <= 0) {
            return 0;
        }

        $percent = (float) ($this->get()['stok_audit_percent'] ?? self::STOK_AUDIT_DEFAULT_PERCENT);
        $fromPercent = (int) ceil($totalProducts * $percent / 100);

        return min($totalProducts, max(self::STOK_AUDIT_MIN_PRODUCTS, $fromPercent));
    }

    /** @param  array{stok_audit_percent: float|int|string}  $data */
    public function updateStokAudit(array $data): void
    {
        if (! Speedshop::query()->exists()) {
            return;
        }

        Speedshop::query()->update([
            'stok_audit_percent' => (float) ($data['stok_audit_percent'] ?? self::STOK_AUDIT_DEFAULT_PERCENT),
        ]);
    }

    /** @param  array<string, mixed>  $data */
    public function update(array $data): void
    {
        $payload = [
            'invoice_charge_mode' => (string) ($data['invoice_charge_mode'] ?? 'percent'),
            'invoice_charge_percent' => (float) ($data['invoice_charge_percent'] ?? 0),
            'invoice_charge_rupiah' => (int) ($data['invoice_charge_rupiah'] ?? 0),
            'invoice_ppn_mode' => (string) ($data['invoice_ppn_mode'] ?? 'percent'),
            'invoice_ppn_percent' => (float) ($data['invoice_ppn_percent'] ?? 0),
            'invoice_ppn_rupiah' => (int) ($data['invoice_ppn_rupiah'] ?? 0),
        ];

        if (Speedshop::query()->exists()) {
            Speedshop::query()->update($payload);
        }
    }

    /** @return array<string, mixed> */
    private function fromModel(Speedshop $row): array
    {
        return [
            'invoice_charge_mode' => (string) ($row->invoice_charge_mode ?? 'percent'),
            'invoice_charge_percent' => (float) ($row->invoice_charge_percent ?? 0),
            'invoice_charge_rupiah' => (int) ($row->invoice_charge_rupiah ?? 0),
            'invoice_ppn_mode' => (string) ($row->invoice_ppn_mode ?? 'percent'),
            'invoice_ppn_percent' => (float) ($row->invoice_ppn_percent ?? 0),
            'invoice_ppn_rupiah' => (int) ($row->invoice_ppn_rupiah ?? 0),
            'stok_audit_percent' => (float) ($row->stok_audit_percent ?? self::STOK_AUDIT_DEFAULT_PERCENT),
        ];
    }

    /** @return array<string, mixed> */
    private function defaults(): array
    {
        return [
            'invoice_charge_mode' => 'percent',
            'invoice_charge_percent' => 0.0,
            'invoice_charge_rupiah' => 0,
            'invoice_ppn_mode' => 'percent',
            'invoice_ppn_percent' => 0.0,
            'invoice_ppn_rupiah' => 0,
            'stok_audit_percent' => self::STOK_AUDIT_DEFAULT_PERCENT,
        ];
    }
}
