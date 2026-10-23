<?php

namespace App\Support;

use App\Models\SpeedshopFinanceLedger;
use App\Models\SpeedshopFinancePermintaanKasKecil;
use App\Models\SpeedshopFinanceSetoran;
use App\Models\SpeedshopFinanceSetting;
use App\Models\SpeedshopKeuanganRekening;
use App\Models\SpeedshopRincianBiayaEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

final class KeuanganScope
{
    public function __construct(
        public readonly ?int $speedshopId,
        public readonly ?int $warehouseId,
        public readonly ?int $ownerUserId = null,
    ) {
        $scopes = [
            $speedshopId !== null,
            $warehouseId !== null,
            $ownerUserId !== null,
        ];

        if (count(array_filter($scopes)) !== 1) {
            throw new \InvalidArgumentException('KeuanganScope membutuhkan tepat satu dari speedshopId, warehouseId, atau ownerUserId.');
        }
    }

    public static function forSpeedshop(int $id): self
    {
        return new self($id, null, null);
    }

    public static function forWarehouse(int $id): self
    {
        return new self(null, $id, null);
    }

    public static function forOwner(int $userId): self
    {
        return new self(null, null, $userId);
    }

    public function isWarehouse(): bool
    {
        return $this->warehouseId !== null;
    }

    public function isSpeedshop(): bool
    {
        return $this->speedshopId !== null;
    }

    public function isOwner(): bool
    {
        return $this->ownerUserId !== null;
    }

    /** @return array<string, int|null> */
    public function ownershipAttributes(): array
    {
        if ($this->isOwner()) {
            return [
                'owner_user_id' => $this->ownerUserId,
                'speedshop_id' => null,
                'warehouse_id' => null,
            ];
        }

        if ($this->isWarehouse()) {
            return [
                'warehouse_id' => $this->warehouseId,
                'speedshop_id' => null,
                'owner_user_id' => null,
            ];
        }

        return [
            'speedshop_id' => $this->speedshopId,
            'warehouse_id' => null,
            'owner_user_id' => null,
        ];
    }

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public function applyTo(Builder $query, ?string $table = null): Builder
    {
        $table = $table ?? $query->getModel()->getTable();
        $prefix = $table.'.';

        if ($this->isOwner()) {
            if (! Schema::hasColumn($table, 'owner_user_id')) {
                return $query->whereRaw('1 = 0');
            }

            return $query
                ->where($prefix.'owner_user_id', $this->ownerUserId)
                ->whereNull($prefix.'speedshop_id')
                ->whereNull($prefix.'warehouse_id');
        }

        if (Schema::hasColumn($table, 'owner_user_id')) {
            $query = $query->whereNull($prefix.'owner_user_id');
        }

        if (! Schema::hasColumn($table, 'warehouse_id')) {
            if ($this->isWarehouse()) {
                return $query->whereRaw('1 = 0');
            }

            return $query->where($prefix.'speedshop_id', $this->speedshopId);
        }

        if ($this->isWarehouse()) {
            return $query
                ->where($prefix.'warehouse_id', $this->warehouseId)
                ->whereNull($prefix.'speedshop_id');
        }

        return $query
            ->where($prefix.'speedshop_id', $this->speedshopId)
            ->whereNull($prefix.'warehouse_id');
    }

    public function applyToRekening(Builder $query): Builder
    {
        return $this->applyTo($query, 'speedshop_keuangan_rekenings');
    }

    public function applyToLedger(Builder $query): Builder
    {
        return $this->applyTo($query, 'speedshop_finance_ledgers');
    }

    public function applyToSetoran(Builder $query): Builder
    {
        return $this->applyTo($query, 'speedshop_finance_setorans');
    }

    public function applyToSettings(Builder $query): Builder
    {
        return $this->applyTo($query, 'speedshop_finance_settings');
    }

    public function applyToPermintaan(Builder $query): Builder
    {
        return $this->applyTo($query, 'speedshop_finance_permintaan_kas_kecil');
    }

    public function applyToRincianBiaya(Builder $query): Builder
    {
        return $this->applyTo($query, 'speedshop_rincian_biaya_entries');
    }

    public function ownsRekening(SpeedshopKeuanganRekening $rekening): bool
    {
        if ($this->isOwner()) {
            return Schema::hasColumn('speedshop_keuangan_rekenings', 'owner_user_id')
                && (int) ($rekening->owner_user_id ?? 0) === $this->ownerUserId
                && $rekening->speedshop_id === null
                && $rekening->warehouse_id === null;
        }

        if ($rekening->isOwnerRekening()) {
            return false;
        }

        if ($this->isWarehouse()) {
            return (int) ($rekening->warehouse_id ?? 0) === $this->warehouseId
                && $rekening->speedshop_id === null;
        }

        return (int) $rekening->speedshop_id === $this->speedshopId
            && ($rekening->warehouse_id === null || ! Schema::hasColumn('speedshop_keuangan_rekenings', 'warehouse_id'));
    }

    public function ownsSetoran(SpeedshopFinanceSetoran $setoran): bool
    {
        if ($this->isWarehouse()) {
            return (int) ($setoran->warehouse_id ?? 0) === $this->warehouseId
                && $setoran->speedshop_id === null;
        }

        return (int) $setoran->speedshop_id === $this->speedshopId
            && ($setoran->warehouse_id === null || ! Schema::hasColumn('speedshop_finance_setorans', 'warehouse_id'));
    }

    public function ownsPermintaan(SpeedshopFinancePermintaanKasKecil $permintaan): bool
    {
        if ($this->isWarehouse()) {
            return (int) ($permintaan->warehouse_id ?? 0) === $this->warehouseId
                && $permintaan->speedshop_id === null;
        }

        return (int) $permintaan->speedshop_id === $this->speedshopId
            && ($permintaan->warehouse_id === null || ! Schema::hasColumn('speedshop_finance_permintaan_kas_kecil', 'warehouse_id'));
    }

    public function ownsRincianBiaya(SpeedshopRincianBiayaEntry $entry): bool
    {
        if ($this->isWarehouse()) {
            return Schema::hasColumn('speedshop_rincian_biaya_entries', 'warehouse_id')
                && (int) ($entry->warehouse_id ?? 0) === $this->warehouseId
                && $entry->speedshop_id === null;
        }

        return (int) $entry->speedshop_id === $this->speedshopId
            && ($entry->warehouse_id === null || ! Schema::hasColumn('speedshop_rincian_biaya_entries', 'warehouse_id'));
    }
}
