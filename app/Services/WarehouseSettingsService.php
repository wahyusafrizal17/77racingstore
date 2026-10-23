<?php

namespace App\Services;

class WarehouseSettingsService
{
    public const STOK_AUDIT_MIN_PRODUCTS = InvoiceSettingsService::STOK_AUDIT_MIN_PRODUCTS;

    public const STOK_AUDIT_DEFAULT_PERCENT = InvoiceSettingsService::STOK_AUDIT_DEFAULT_PERCENT;

    public function getStokAuditPercent(?int $warehouseId): float
    {
        if ($warehouseId === null) {
            return self::STOK_AUDIT_DEFAULT_PERCENT;
        }

        return (float) self::STOK_AUDIT_DEFAULT_PERCENT;
    }

    public function resolveStokAuditSampleSize(int $totalProducts, ?int $warehouseId): int
    {
        if ($totalProducts <= 0) {
            return 0;
        }

        $percent = $this->getStokAuditPercent($warehouseId);
        $fromPercent = (int) ceil($totalProducts * $percent / 100);

        return min($totalProducts, max(self::STOK_AUDIT_MIN_PRODUCTS, $fromPercent));
    }

    /** @param  array{stok_audit_percent: float|int|string}  $data */
    public function updateStokAudit(int $warehouseId, array $data): void
    {
        return;
    }
}
