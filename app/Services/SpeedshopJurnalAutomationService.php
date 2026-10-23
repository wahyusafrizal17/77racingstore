<?php

namespace App\Services;

use App\Models\SpeedshopFinanceLedger;
use App\Models\SpeedshopJurnalBluEntry;
use App\Models\SpeedshopJurnalSaquEntry;
use App\Models\SpeedshopRincianBiayaEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

/**
 * Jurnal kas kecil (tabel speedshop_jurnal_blu_entries) dan jurnal kas digital gabungan (speedshop_jurnal_saqu_entries).
 * Baris otomatis dari biaya operasional (rincian biaya); pendapatan invoice dicatat di ledger keuangan, bukan di tabel jurnal ini.
 */
class SpeedshopJurnalAutomationService
{
    public const AKUN_SALDO_AWAL = 'SALDO AKHIR';

    public const AKUN_BIAYA_OP = 'BIAYA OPERASIONAL';

    public function syncRincianBiayaPengeluaranJurnal(SpeedshopRincianBiayaEntry $entry): void
    {
        if ((int) ($entry->warehouse_id ?? 0) > 0) {
            $ledgerId = (int) ($entry->finance_ledger_id ?? 0);
            if ($ledgerId > 0) {
                $ledger = SpeedshopFinanceLedger::query()->find($ledgerId);
                if ($ledger) {
                    app(WarehouseJurnalAutomationService::class)->syncFromLedger($ledger);
                }
            }

            return;
        }

        if (! Schema::hasTable('speedshop_jurnal_blu_entries')) {
            return;
        }
        if (! Schema::hasColumn('speedshop_jurnal_blu_entries', 'speedshop_rincian_biaya_entry_id')) {
            return;
        }

        $this->deleteJurnalForRincianBiaya((int) $entry->id);

        $nominal = (float) $entry->nominalEfektif();
        if ($nominal <= 0) {
            $this->deleteJurnalForRincianBiaya((int) $entry->id);

            return;
        }

        $tanggal = $entry->tanggal instanceof Carbon
            ? $entry->tanggal->toDateString()
            : Carbon::parse($entry->tanggal)->toDateString();

        $parts = array_filter([
            (string) ($entry->nama_akun ?? ''),
            (string) ($entry->no_akun ?? ''),
            (string) ($entry->keterangan ?? ''),
        ], fn ($s) => trim($s) !== '');
        $ket = $parts !== [] ? implode(' — ', $parts) : 'Pengeluaran biaya operasional';

        $pic = (string) ($entry->pic ?? '-');
        $isFromRekening = (int) ($entry->speedshop_keuangan_rekening_id ?? 0) > 0;

        $payload = [
            'speedshop_id' => (int) $entry->speedshop_id,
            'tanggal' => $tanggal,
            'akun' => self::AKUN_BIAYA_OP,
            'keterangan' => $ket,
            'pic' => $pic !== '' ? $pic : '-',
            'kredit' => 0,
            'debet' => $nominal,
            'speedshop_rincian_biaya_entry_id' => (int) $entry->id,
        ];

        if ($isFromRekening) {
            SpeedshopJurnalSaquEntry::query()->create($payload);
        } else {
            SpeedshopJurnalBluEntry::query()->create($payload);
        }
    }

    public function deleteJurnalForRincianBiaya(int $rincianBiayaEntryId): void
    {
        if (! Schema::hasTable('speedshop_jurnal_blu_entries')) {
            return;
        }
        if (Schema::hasColumn('speedshop_jurnal_blu_entries', 'speedshop_rincian_biaya_entry_id')) {
            SpeedshopJurnalBluEntry::query()
                ->where('speedshop_rincian_biaya_entry_id', $rincianBiayaEntryId)
                ->delete();
        }
        if (Schema::hasTable('speedshop_jurnal_saqu_entries') && Schema::hasColumn('speedshop_jurnal_saqu_entries', 'speedshop_rincian_biaya_entry_id')) {
            SpeedshopJurnalSaquEntry::query()
                ->where('speedshop_rincian_biaya_entry_id', $rincianBiayaEntryId)
                ->delete();
        }
    }

    /** Saldo laci s.d. hari sebelum $dari (pembuka periode). */
    public function openingLaciBalanceBefore(string $dariYmd, int $speedshopId): float
    {
        if (! Schema::hasTable('speedshop_finance_ledgers')) {
            return 0.0;
        }
        $prev = Carbon::parse($dariYmd)->subDay()->toDateString();
        $finance = app(SpeedshopFinanceService::class);

        return (float) $finance->balancePocketAsOf($speedshopId, SpeedshopFinanceService::POCKET_LACI, $prev);
    }

    /** Saldo buku digital (kas besar + rekening utama agregat) s.d. hari sebelum $dari. */
    public function openingDigitalPoolBalanceBefore(string $dariYmd, int $speedshopId): float
    {
        if (! Schema::hasTable('speedshop_finance_ledgers')) {
            return 0.0;
        }
        $prev = Carbon::parse($dariYmd)->subDay()->toDateString();
        $finance = app(SpeedshopFinanceService::class);
        $kb = (float) $finance->balancePocketAsOf($speedshopId, SpeedshopFinanceService::POCKET_CASH_BESAR, $prev);
        $ru = (float) $finance->balancePocketAsOf($speedshopId, SpeedshopFinanceService::POCKET_REKENING, $prev);

        return $kb + $ru;
    }
}
