<?php

namespace App\Support;

use App\Models\ServiceOrder;
use App\Models\SpeedshopRincianBiayaEntry;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

/**
 * COUNT pending untuk menu speedshop Persetujuan (Manager).
 * Hanya query COUNT — tidak mengambil baris penuh.
 * Memo per-request agar sidebar tidak mengulang query di view yang sama.
 */
final class SpeedshopPersetujuanPendingCounts
{
    /** @var array<string, array{diskon_work_order: int, pembelian_part_luar: int, biaya_operasional: int, total: int}> */
    private static array $requestMemo = [];

    /**
     * @return array{diskon_work_order: int, pembelian_part_luar: int, biaya_operasional: int, total: int}
     */
    public static function forUser(User $user): array
    {
        if (! $user->isSpeedshop() || ! $user->isManager()) {
            return self::empty();
        }

        $speedshopIds = $user->reportSpeedshopFilterIds();
        if ($speedshopIds === []) {
            return self::empty();
        }

        $memoKey = 'ss:'.implode(',', $speedshopIds).':'.($user->activeWarehouseId() ?? 0);
        if (isset(self::$requestMemo[$memoKey])) {
            return self::$requestMemo[$memoKey];
        }

        $diskon = self::countDiskonWorkOrder($speedshopIds);
        $pembelian = self::countPembelianPartLuar($user, $speedshopIds);
        $biaya = self::countBiayaOperasional($speedshopIds);

        return self::$requestMemo[$memoKey] = [
            'diskon_work_order' => $diskon,
            'pembelian_part_luar' => $pembelian,
            'biaya_operasional' => $biaya,
            'total' => $diskon + $pembelian + $biaya,
        ];
    }

    /**
     * @return array{diskon_work_order: int, pembelian_part_luar: int, biaya_operasional: int, total: int}
     */
    private static function empty(): array
    {
        return [
            'diskon_work_order' => 0,
            'pembelian_part_luar' => 0,
            'biaya_operasional' => 0,
            'total' => 0,
        ];
    }

    /**
     * @param  list<int>  $speedshopIds
     */
    private static function countDiskonWorkOrder(array $speedshopIds): int
    {
        if (! Schema::hasColumn('service_orders', 'diskon_verifikasi_status')) {
            return 0;
        }

        return (int) ServiceOrder::query()
            ->where('diskon_verifikasi_status', 'pending')
            ->where(function ($outer) use ($speedshopIds) {
                if (Schema::hasColumn('service_orders', 'speedshop_id')) {
                    $outer->whereIn('speedshop_id', $speedshopIds);
                }

                $outer->orWhereHas('diskonVerifikasiRequestedBy', function ($q) use ($speedshopIds) {
                    $q->where('section', 'speedshop')
                        ->whereIn('speedshop_id', $speedshopIds);
                });
            })
            ->count();
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
    private static function countBiayaOperasional(array $speedshopIds): int
    {
        if (! Schema::hasTable('speedshop_rincian_biaya_entries')) {
            return 0;
        }

        $maxNominal = SpeedshopRincianBiayaEntry::KAS_KECIL_VERIFIKASI_ACCOUNTING_MAX_NOMINAL;
        $hasRekCol = Schema::hasColumn('speedshop_rincian_biaya_entries', 'speedshop_keuangan_rekening_id');
        $hasLedgerCol = Schema::hasColumn('speedshop_rincian_biaya_entries', 'finance_ledger_id');

        $query = SpeedshopRincianBiayaEntry::query()
            ->where('approval_status', SpeedshopRincianBiayaEntry::STATUS_PENDING)
            ->whereIn('speedshop_id', $speedshopIds)
            ->where(function ($q) use ($maxNominal, $hasRekCol, $hasLedgerCol) {
                // Kas kecil tunai (tanpa rekening) di atas batas → Manager
                $q->where(function ($tunai) use ($maxNominal, $hasRekCol) {
                    $tunai->where(function ($m) {
                        $m->whereNull('metode')->orWhere('metode', 'tunai');
                    })
                        ->where('nominal', '>', $maxNominal);
                    if ($hasRekCol) {
                        $tunai->where(function ($r) {
                            $r->whereNull('speedshop_keuangan_rekening_id')
                                ->orWhere('speedshop_keuangan_rekening_id', 0);
                        });
                    }
                });

                // Transfer rekening Accounting menunggu Manager (nominal > batas, belum ledger)
                if ($hasRekCol) {
                    $q->orWhere(function ($rek) use ($maxNominal, $hasLedgerCol) {
                        $rek->where('metode', 'transfer')
                            ->where('speedshop_keuangan_rekening_id', '>', 0)
                            ->where('nominal', '>', $maxNominal);
                        if ($hasLedgerCol) {
                            $rek->where(function ($l) {
                                $l->whereNull('finance_ledger_id')
                                    ->orWhere('finance_ledger_id', 0);
                            });
                        }
                    });
                }
            });

        return (int) $query->count();
    }
}
