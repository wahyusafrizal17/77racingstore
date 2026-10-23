<?php

namespace App\Models;

use App\Support\KeuanganScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class AkunOperasional extends Model
{
    use HasFactory;

    protected $fillable = [
        'speedshop_id',
        'warehouse_id',
        'no_akun',
        'nama_akun',
    ];

    /**
     * @return Builder<self>
     */
    public static function queryForScope(?int $speedshopId, ?int $warehouseId): Builder
    {
        $query = static::query();

        if ($warehouseId) {
            return $query
                ->where('warehouse_id', $warehouseId)
                ->whereNull('speedshop_id');
        }

        if ($speedshopId) {
            return $query->where(function (Builder $q) use ($speedshopId) {
                $q->where(function (Builder $q2) use ($speedshopId) {
                    $q2->where('speedshop_id', $speedshopId)->whereNull('warehouse_id');
                });
                if (Schema::hasColumn('akun_operasionals', 'speedshop_id')) {
                    $q->orWhere(function (Builder $q2) {
                        $q2->whereNull('speedshop_id')->whereNull('warehouse_id');
                    });
                }
            });
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * @param  list<int>  $speedshopIds
     * @return Builder<self>
     */
    public static function queryForSpeedshopIds(array $speedshopIds): Builder
    {
        $ids = array_values(array_unique(array_map('intval', $speedshopIds)));

        if ($ids === []) {
            return static::query()->whereRaw('1 = 0');
        }

        if (count($ids) === 1) {
            return static::queryForScope($ids[0], null);
        }

        return static::query()->where(function (Builder $q) use ($ids) {
            $q->where(function (Builder $q2) use ($ids) {
                $q2->whereIn('speedshop_id', $ids)->whereNull('warehouse_id');
            });
            if (Schema::hasColumn('akun_operasionals', 'speedshop_id')) {
                $q->orWhere(function (Builder $q2) {
                    $q2->whereNull('speedshop_id')->whereNull('warehouse_id');
                });
            }
        });
    }

    /**
     * @return Builder<self>
     */
    public static function queryForKeuanganScope(KeuanganScope $scope): Builder
    {
        return static::queryForScope($scope->speedshopId, $scope->warehouseId);
    }

    /** No. akun biaya operasional yang wajib alokasi rekening sumber → tujuan. */
    public const NO_AKUN_REQUIRING_REKENING_TUJUAN = [
        '5.2.1', // Gaji Karyawan
        '5.2.2', // THR Karyawan
        '5.2.3', // Bonus Karyawan
        '5.2.4', // Gaji Management
        '5.2.5', // THR Management
        '5.2.8', // Promosi
        '5.2.12', // Depresiasi Tools & Perlengkapan
        '5.2.14', // Sewa Bangunan
    ];

    /** Akun yang mengalokasikan dana ke rekening internal (gaji, sewa, promosi, dll.). */
    public static function requiresRekeningTujuanForNoAkun(?string $noAkun): bool
    {
        $no = trim((string) $noAkun);

        return in_array($no, self::NO_AKUN_REQUIRING_REKENING_TUJUAN, true);
    }

    public function requiresRekeningTujuan(): bool
    {
        return self::requiresRekeningTujuanForNoAkun($this->no_akun);
    }

    public function belongsToKeuanganScope(KeuanganScope $scope): bool
    {
        if ($scope->isWarehouse()) {
            return (int) ($this->warehouse_id ?? 0) === $scope->warehouseId
                && $this->speedshop_id === null;
        }

        if ((int) ($this->speedshop_id ?? 0) === (int) $scope->speedshopId
            && ($this->warehouse_id === null || ! Schema::hasColumn('akun_operasionals', 'warehouse_id'))) {
            return true;
        }

        return $this->speedshop_id === null && $this->warehouse_id === null;
    }

    /** @return array<string, int|null> */
    public static function ownershipAttributesForScope(KeuanganScope $scope): array
    {
        if ($scope->isWarehouse()) {
            return [
                'warehouse_id' => $scope->warehouseId,
                'speedshop_id' => null,
            ];
        }

        return [
            'speedshop_id' => $scope->speedshopId,
            'warehouse_id' => null,
        ];
    }
}
