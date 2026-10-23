<?php

namespace App\Support;

use App\Models\SpeedshopFinanceLedger;
use App\Models\SpeedshopJurnalBluEntry;
use App\Models\SpeedshopJurnalSaquEntry;
use App\Models\SpeedshopKeuanganRekening;
use App\Models\Warehouse;
use App\Services\WarehouseJurnalAutomationService;
use App\Services\SpeedshopFinanceService;
use App\Services\SpeedshopJurnalAutomationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

final class WarehouseJurnalReport
{
    /**
     * @return array{
     *     top_options: list<array{value: string, label: string}>,
     *     optgroups: list<array{label: string, options: list<array{value: string, label: string}>}>
     * }
     */
    public static function sumberSelectSpec(KeuanganScope $scope): array
    {
        $top = [
            ['value' => 'kas_kecil', 'label' => 'Kas Tunai (Laci)'],
        ];

        if (! Schema::hasTable('speedshop_keuangan_rekenings')) {
            return ['top_options' => $top, 'optgroups' => []];
        }

        $labelRekening = static function (SpeedshopKeuanganRekening $r): string {
            $jenis = SpeedshopKeuanganRekening::jenisLabel($r->jenis ?? null);
            $parts = array_filter([
                $jenis,
                trim((string) $r->nama),
                trim((string) ($r->nama_bank ?? '')),
                trim((string) ($r->nomor_rekening ?? '')),
            ], fn ($s) => $s !== '');

            return $parts !== [] ? implode(' · ', $parts) : (string) $r->nama;
        };

        $rekenings = $scope->applyToRekening(SpeedshopKeuanganRekening::query())
            ->orderBy('jenis')
            ->orderBy('nama')
            ->orderBy('id')
            ->get();

        $opts = [];
        foreach ($rekenings as $r) {
            $prefix = $r->isRekeningUtama() ? 'rekening_utama' : 'kas_besar';
            $opts[] = [
                'value' => $prefix.':'.(int) $r->id,
                'label' => $labelRekening($r),
            ];
        }

        $optgroups = $opts !== []
            ? [['label' => 'Rekening gudang', 'options' => $opts]]
            : [];

        return ['top_options' => $top, 'optgroups' => $optgroups];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function buildKasTunaiReport(
        Request $request,
        KeuanganScope $scope,
        callable $resolvePeriod,
    ): ?array {
        $period = $resolvePeriod($request);
        $dari = $period['dari'];
        $sampai = $period['sampai'];

        $finance = app(SpeedshopFinanceService::class);
        $prevDay = $dari->copy()->subDay()->toDateString();
        $openingBal = Schema::hasTable('speedshop_finance_ledgers')
            ? (float) $finance->balancePocketAsOfScope($scope, SpeedshopFinanceService::POCKET_LACI, $prevDay)
            : 0.0;

        $dbRows = self::kasTunaiJurnalRows($scope, $dari, $sampai);

        $openingRows = collect();
        if ($dbRows->isNotEmpty() || abs($openingBal) > 0.0001) {
            $openingRows = collect([
                (object) [
                    'id' => null,
                    'tanggal' => $dari->copy(),
                    'akun' => SpeedshopJurnalAutomationService::AKUN_SALDO_AWAL,
                    'keterangan' => 'Saldo awal Kas Tunai per '.\Carbon\Carbon::parse($prevDay)->translatedFormat('d F Y'),
                    'pic' => '-',
                    'kredit' => $openingBal,
                    'debet' => 0.0,
                    'allow_delete' => false,
                ],
            ]);
        }

        return self::finalizeReport($scope, $openingRows->concat($dbRows), $period, [
            'jurnalKind' => 'kas_kecil',
            'monitorOnly' => true,
            'canManualEntry' => false,
            'thirdCardTitle' => 'Sisa saldo kas tunai',
            'emptyMessage' => 'Belum ada mutasi kas tunai pada periode ini.',
            'ledgerRekening' => null,
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function buildRekeningReport(
        Request $request,
        KeuanganScope $scope,
        int $rekeningId,
        callable $resolvePeriod,
    ): ?array {
        $period = $resolvePeriod($request);
        $dari = $period['dari'];
        $sampai = $period['sampai'];

        $rek = $scope->applyToRekening(SpeedshopKeuanganRekening::query())
            ->whereKey($rekeningId)
            ->first();
        if (! $rek) {
            return null;
        }

        $pocket = $rek->isRekeningUtama()
            ? SpeedshopFinanceService::POCKET_REKENING
            : SpeedshopFinanceService::POCKET_CASH_BESAR;

        $finance = app(SpeedshopFinanceService::class);
        $prevDay = $dari->copy()->subDay()->toDateString();
        $openingBal = Schema::hasTable('speedshop_finance_ledgers')
            && Schema::hasColumn('speedshop_finance_ledgers', 'speedshop_keuangan_rekening_id')
            ? (float) $finance->balanceForBankPocketAsOfScope($scope, $pocket, (int) $rek->id, $prevDay)
            : 0.0;

        $openingRows = collect([
            (object) [
                'id' => null,
                'tanggal' => $dari->copy(),
                'akun' => SpeedshopJurnalAutomationService::AKUN_SALDO_AWAL,
                'keterangan' => 'Saldo awal buku per '.\Carbon\Carbon::parse($prevDay)->translatedFormat('d F Y'),
                'pic' => '-',
                'kredit' => $openingBal > 0 ? $openingBal : 0.0,
                'debet' => $openingBal < 0 ? -$openingBal : 0.0,
                'allow_delete' => false,
            ],
        ]);

        $dbRows = self::rekeningJurnalRows($scope, $rek, $dari, $sampai);

        return self::finalizeReport($scope, $openingRows->concat($dbRows), $period, [
            'jurnalKind' => 'ledger_rekening',
            'monitorOnly' => true,
            'canManualEntry' => false,
            'thirdCardTitle' => 'Saldo akhir (buku)',
            'emptyMessage' => 'Belum ada mutasi ledger untuk rekening ini pada periode ini.',
            'ledgerRekening' => $rek,
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $entriesMerged
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private static function finalizeReport(
        KeuanganScope $scope,
        \Illuminate\Support\Collection $entriesMerged,
        array $period,
        array $extra,
    ): array {
        $running = 0.0;
        $rows = $entriesMerged->values()->map(function ($row, $idx) use (&$running) {
            $running += (float) ($row->kredit ?? 0);
            $running -= (float) ($row->debet ?? 0);

            return (object) [
                'no' => $idx + 1,
                'tanggal' => $row->tanggal,
                'akun' => $row->akun,
                'keterangan' => $row->keterangan,
                'pic' => $row->pic,
                'entry_id' => $row->id ?? null,
                'allow_delete' => (bool) ($row->allow_delete ?? false),
                'kredit' => (float) ($row->kredit ?? 0),
                'debet' => (float) ($row->debet ?? 0),
                'saldo' => $running,
            ];
        });

        $warehouse = null;
        $scopeLabel = strtoupper($warehouse?->nama ?? 'GUDANG');

        return array_merge([
            'speedshop' => null,
            'scopeLabel' => $scopeLabel,
            'warehouse' => $warehouse,
            'rows' => $rows,
            'pemasukan' => (float) $rows->sum('kredit'),
            'pengeluaran' => (float) $rows->sum('debet'),
            'sisaSaldo' => $rows->isNotEmpty() ? (float) $rows->last()->saldo : 0.0,
            'dari' => $period['dari'],
            'sampai' => $period['sampai'],
            'mode' => $period['mode'],
            'selectedDate' => $period['selectedDate'],
            'selectedMonth' => $period['selectedMonth'],
            'selectedYear' => $period['selectedYear'],
            'selectedDari' => $period['selectedDari'],
            'selectedSampai' => $period['selectedSampai'],
        ], $extra);
    }

    /**
     * @return \Illuminate\Support\Collection<int, object>
     */
    private static function kasTunaiJurnalRows(KeuanganScope $scope, \Carbon\Carbon $dari, \Carbon\Carbon $sampai): \Illuminate\Support\Collection
    {
        $jurnalReady = app(WarehouseJurnalAutomationService::class)->jurnalWarehouseColumnsReady();

        if ($jurnalReady) {
            self::purgeOrphanWarehouseBluJurnal($scope);

            return SpeedshopJurnalBluEntry::query()
                ->where('warehouse_id', $scope->warehouseId)
                ->whereNull('speedshop_id')
                ->whereBetween('tanggal', [$dari->toDateString(), $sampai->toDateString()])
                ->whereRaw('UPPER(TRIM(akun)) != ?', [SpeedshopJurnalAutomationService::AKUN_SALDO_AWAL])
                ->orderBy('tanggal')
                ->orderBy('id')
                ->get()
                ->map(fn (SpeedshopJurnalBluEntry $entry) => (object) [
                    'id' => (int) $entry->id,
                    'tanggal' => $entry->tanggal,
                    'akun' => $entry->akun,
                    'keterangan' => $entry->keterangan,
                    'pic' => $entry->pic,
                    'kredit' => (float) ($entry->kredit ?? 0),
                    'debet' => (float) ($entry->debet ?? 0),
                    'allow_delete' => false,
                ]);
        }

        $dbRows = collect();
        if (! Schema::hasTable('speedshop_finance_ledgers')) {
            return $dbRows;
        }

        $ledgers = $scope->applyToLedger(SpeedshopFinanceLedger::query())
            ->with(['user:id,name'])
            ->where('pocket', SpeedshopFinanceService::POCKET_LACI)
            ->whereBetween('tanggal', [$dari->toDateString(), $sampai->toDateString()])
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get();

        foreach ($ledgers as $L) {
            $amt = (int) ($L->amount ?? 0);
            $dbRows->push((object) [
                'id' => (int) $L->id,
                'tanggal' => $L->tanggal,
                'akun' => 'LEDGER',
                'keterangan' => self::humanizeLedgerReason($L->reason),
                'pic' => $L->user?->name ?? '-',
                'kredit' => $amt > 0 ? (float) $amt : 0.0,
                'debet' => $amt < 0 ? (float) (-$amt) : 0.0,
                'allow_delete' => false,
            ]);
        }

        return $dbRows;
    }

    /**
     * @return \Illuminate\Support\Collection<int, object>
     */
    private static function rekeningJurnalRows(
        KeuanganScope $scope,
        SpeedshopKeuanganRekening $rek,
        \Carbon\Carbon $dari,
        \Carbon\Carbon $sampai,
    ): \Illuminate\Support\Collection {
        $jurnalReady = app(WarehouseJurnalAutomationService::class)->jurnalWarehouseColumnsReady()
            && Schema::hasColumn('speedshop_jurnal_saqu_entries', 'speedshop_keuangan_rekening_id');

        if ($jurnalReady) {
            return SpeedshopJurnalSaquEntry::query()
                ->where('warehouse_id', $scope->warehouseId)
                ->whereNull('speedshop_id')
                ->where('speedshop_keuangan_rekening_id', (int) $rek->id)
                ->whereBetween('tanggal', [$dari->toDateString(), $sampai->toDateString()])
                ->whereRaw('UPPER(TRIM(akun)) != ?', [SpeedshopJurnalAutomationService::AKUN_SALDO_AWAL])
                ->orderBy('tanggal')
                ->orderBy('id')
                ->get()
                ->map(fn (SpeedshopJurnalSaquEntry $entry) => (object) [
                    'id' => (int) $entry->id,
                    'tanggal' => $entry->tanggal,
                    'akun' => $entry->akun,
                    'keterangan' => $entry->keterangan,
                    'pic' => $entry->pic,
                    'kredit' => (float) ($entry->kredit ?? 0),
                    'debet' => (float) ($entry->debet ?? 0),
                    'allow_delete' => false,
                ]);
        }

        $pocket = $rek->isRekeningUtama()
            ? SpeedshopFinanceService::POCKET_REKENING
            : SpeedshopFinanceService::POCKET_CASH_BESAR;

        $dbRows = collect();
        if (! Schema::hasTable('speedshop_finance_ledgers')
            || ! Schema::hasColumn('speedshop_finance_ledgers', 'speedshop_keuangan_rekening_id')) {
            return $dbRows;
        }

        $ledgers = $scope->applyToLedger(SpeedshopFinanceLedger::query())
            ->with(['user:id,name'])
            ->where('pocket', $pocket)
            ->where('speedshop_keuangan_rekening_id', (int) $rek->id)
            ->whereBetween('tanggal', [$dari->toDateString(), $sampai->toDateString()])
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get();

        foreach ($ledgers as $L) {
            $amt = (int) ($L->amount ?? 0);
            $dbRows->push((object) [
                'id' => (int) $L->id,
                'tanggal' => $L->tanggal,
                'akun' => 'LEDGER',
                'keterangan' => self::humanizeLedgerReason($L->reason),
                'pic' => $L->user?->name ?? '-',
                'kredit' => $amt > 0 ? (float) $amt : 0.0,
                'debet' => $amt < 0 ? (float) (-$amt) : 0.0,
                'allow_delete' => false,
            ]);
        }

        return $dbRows;
    }

    /** Hapus baris jurnal blu gudang yang ledger-nya sudah tidak ada (sisa sinkronisasi). */
    private static function purgeOrphanWarehouseBluJurnal(KeuanganScope $scope): void
    {
        if (! Schema::hasTable('speedshop_jurnal_blu_entries')
            || ! Schema::hasColumn('speedshop_jurnal_blu_entries', 'speedshop_finance_ledger_id')
            || ! Schema::hasTable('speedshop_finance_ledgers')) {
            return;
        }

        $ledgerIds = SpeedshopFinanceLedger::query()->select('id');

        SpeedshopJurnalBluEntry::query()
            ->where('warehouse_id', $scope->warehouseId)
            ->whereNull('speedshop_id')
            ->whereNotNull('speedshop_finance_ledger_id')
            ->whereNotIn('speedshop_finance_ledger_id', $ledgerIds)
            ->delete();
    }

    public static function humanizeLedgerReason(?string $reason): string
    {
        $r = (string) ($reason ?? '');

        return match ($r) {
            SpeedshopFinanceService::REASON_KAS_TUNAI_DARI_REGULER_KELUAR => 'Kas tunai — keluar dari Rekening Reguler',
            SpeedshopFinanceService::REASON_KAS_TUNAI_DARI_REGULER_MASUK => 'Kas tunai — masuk laci',
            SpeedshopFinanceService::REASON_BIAYA_OPERASIONAL_KAS_KECIL => 'Biaya operasional (kas tunai)',
            SpeedshopFinanceService::REASON_BIAYA_OPERASIONAL_REKENING => 'Biaya operasional (rekening)',
            SpeedshopFinanceService::REASON_SALDO_KOREKSI_MANAGER => 'Koreksi saldo buku (Manager)',
            SpeedshopFinanceService::REASON_PERMINTAAN_KAS_KECIL => 'Penambahan kas tunai',
            SpeedshopFinanceService::REASON_PIUTANG_KELUAR => 'Dana talang — keluar rekening sumber',
            SpeedshopFinanceService::REASON_PIUTANG_MASUK => 'Dana talang — masuk kembali rekening sumber',
            SpeedshopFinanceService::REASON_PEMASUKAN_KELUAR => 'Pemasukan — keluar dari rekening sumber',
            SpeedshopFinanceService::REASON_PEMASUKAN_MASUK => 'Pemasukan — masuk rekening tujuan',
            SpeedshopFinanceService::REASON_HUTANG_SUPPLIER_KELUAR => 'Pembayaran hutang supplier — keluar rekening sumber',
            default => $r !== '' ? $r : '-',
        };
    }
}
