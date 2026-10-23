<?php

namespace App\Support;

use App\Models\ServiceOrder;
use App\Models\SpeedshopFinancePermintaanKasKecil;
use App\Models\SpeedshopFinanceSetoran;
use App\Models\SpeedshopOwnerPengambilanDividen;
use App\Models\SpeedshopRincianBiayaEntry;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

/**
 * COUNT pending untuk menu speedshop Verifikasi (Accounting).
 * Hanya query COUNT — tidak mengambil baris penuh.
 * Memo per-request agar sidebar tidak mengulang query di view yang sama.
 */
final class SpeedshopVerifikasiPendingCounts
{
    /**
     * @var array<string, array{
     *     diskon_work_order: int,
     *     pembelian_part_luar: int,
     *     stok_in_supplier: int,
     *     batal_wo: int,
     *     setoran: int,
     *     kas_kecil: int,
     *     pengambilan_dividen: int,
     *     biaya_operasional: int,
     *     total: int
     * }>
     */
    private static array $requestMemo = [];

    /**
     * @return array{
     *     diskon_work_order: int,
     *     pembelian_part_luar: int,
     *     stok_in_supplier: int,
     *     batal_wo: int,
     *     setoran: int,
     *     kas_kecil: int,
     *     pengambilan_dividen: int,
     *     biaya_operasional: int,
     *     total: int
     * }
     */
    public static function forUser(User $user): array
    {
        if (! $user->isSpeedshop() || (! $user->isAccounting() && ! $user->isManager())) {
            return self::empty();
        }

        $speedshopIds = $user->reportSpeedshopFilterIds();
        if ($speedshopIds === []) {
            return self::empty();
        }

        $memoKey = 'ssv:'.($user->isAccounting() ? 'a' : 'm').':'.implode(',', $speedshopIds).':'.($user->activeWarehouseId() ?? 0);
        if (isset(self::$requestMemo[$memoKey])) {
            return self::$requestMemo[$memoKey];
        }

        if ($user->isManager() && ! $user->isAccounting()) {
            $p = SpeedshopPersetujuanPendingCounts::forUser($user);

            return self::$requestMemo[$memoKey] = [
                'diskon_work_order' => $p['diskon_work_order'],
                'pembelian_part_luar' => $p['pembelian_part_luar'],
                'stok_in_supplier' => 0,
                'batal_wo' => 0,
                'setoran' => 0,
                'kas_kecil' => 0,
                'pengambilan_dividen' => 0,
                'biaya_operasional' => $p['biaya_operasional'],
                'total' => $p['total'],
            ];
        }

        $pembelian = self::countPembelianPartLuar($user, $speedshopIds);
        $stokIn = self::countStokInSupplier($user, $speedshopIds);
        $batalWo = self::countBatalWo($user, $speedshopIds);
        $setoran = self::countSetoran($speedshopIds);
        $kasKecil = self::countKasKecil($speedshopIds);
        $dividen = self::countPengambilanDividen($speedshopIds);
        $biaya = self::countBiayaOperasionalAccounting($speedshopIds);

        return self::$requestMemo[$memoKey] = [
            'diskon_work_order' => 0,
            'pembelian_part_luar' => $pembelian,
            'stok_in_supplier' => $stokIn,
            'batal_wo' => $batalWo,
            'setoran' => $setoran,
            'kas_kecil' => $kasKecil,
            'pengambilan_dividen' => $dividen,
            'biaya_operasional' => $biaya,
            'total' => $pembelian + $stokIn + $batalWo + $setoran + $kasKecil + $dividen + $biaya,
        ];
    }

    /**
     * @return array{
     *     diskon_work_order: int,
     *     pembelian_part_luar: int,
     *     stok_in_supplier: int,
     *     batal_wo: int,
     *     setoran: int,
     *     kas_kecil: int,
     *     pengambilan_dividen: int,
     *     biaya_operasional: int,
     *     total: int
     * }
     */
    public static function empty(): array
    {
        return [
            'diskon_work_order' => 0,
            'pembelian_part_luar' => 0,
            'stok_in_supplier' => 0,
            'batal_wo' => 0,
            'setoran' => 0,
            'kas_kecil' => 0,
            'pengambilan_dividen' => 0,
            'biaya_operasional' => 0,
            'total' => 0,
        ];
    }

    /**
     * @param  list<int>  $speedshopIds
     */
    private static function countPembelianPartLuar(User $user, array $speedshopIds): int
    {
        return 0;
    }

    /**
     * @param  list<int>  $speedshopIds
     */
    private static function countStokInSupplier(User $user, array $speedshopIds): int
    {
        return 0;
    }

    /**
     * @param  list<int>  $speedshopIds
     */
    private static function countBatalWo(User $user, array $speedshopIds): int
    {
        if (! Schema::hasColumn('service_orders', 'batal_verifikasi_status')) {
            return 0;
        }

        $query = ServiceOrder::query()
            ->where('batal_verifikasi_status', 'pending');

        if (Schema::hasColumn('service_orders', 'speedshop_id')) {
            $query->whereIn('speedshop_id', $speedshopIds);
        }

        $warehouseId = $user->activeWarehouseId();
        if ($warehouseId !== null) {
            $query->where(function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId)->orWhereNull('warehouse_id');
            });
        }

        return (int) $query->count();
    }

    /**
     * @param  list<int>  $speedshopIds
     */
    private static function countSetoran(array $speedshopIds): int
    {
        if (! Schema::hasTable('speedshop_finance_setorans')) {
            return 0;
        }

        return (int) SpeedshopFinanceSetoran::query()
            ->where('status', 'pending')
            ->whereIn('speedshop_id', $speedshopIds)
            ->count();
    }

    /**
     * @param  list<int>  $speedshopIds
     */
    private static function countKasKecil(array $speedshopIds): int
    {
        if (! Schema::hasTable('speedshop_finance_permintaan_kas_kecil')) {
            return 0;
        }

        return (int) SpeedshopFinancePermintaanKasKecil::query()
            ->where('status', SpeedshopFinancePermintaanKasKecil::STATUS_PENDING)
            ->whereIn('speedshop_id', $speedshopIds)
            ->count();
    }

    /**
     * @param  list<int>  $speedshopIds
     */
    private static function countPengambilanDividen(array $speedshopIds): int
    {
        if (! Schema::hasTable('speedshop_owner_pengambilan_dividens')) {
            return 0;
        }

        return (int) SpeedshopOwnerPengambilanDividen::query()
            ->where('status', SpeedshopOwnerPengambilanDividen::STATUS_PENDING)
            ->whereIn('speedshop_id', $speedshopIds)
            ->count();
    }

    /**
     * @param  list<int>  $speedshopIds
     */
    private static function countBiayaOperasionalAccounting(array $speedshopIds): int
    {
        if (! Schema::hasTable('speedshop_rincian_biaya_entries')) {
            return 0;
        }

        $maxNominal = SpeedshopRincianBiayaEntry::KAS_KECIL_VERIFIKASI_ACCOUNTING_MAX_NOMINAL;
        $hasRekCol = Schema::hasColumn('speedshop_rincian_biaya_entries', 'speedshop_keuangan_rekening_id');

        $query = SpeedshopRincianBiayaEntry::query()
            ->where('approval_status', SpeedshopRincianBiayaEntry::STATUS_PENDING)
            ->whereIn('speedshop_id', $speedshopIds)
            ->where(function ($m) {
                $m->whereNull('metode')->orWhere('metode', 'tunai');
            })
            ->where('nominal', '<=', $maxNominal);

        if ($hasRekCol) {
            $query->where(function ($r) {
                $r->whereNull('speedshop_keuangan_rekening_id')
                    ->orWhere('speedshop_keuangan_rekening_id', 0);
            });
        }

        return (int) $query->count();
    }
}
