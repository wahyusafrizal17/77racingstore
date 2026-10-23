<?php

namespace App\Services;

use App\Models\SpeedshopFinanceLedger;
use App\Models\SpeedshopJurnalBluEntry;
use App\Models\SpeedshopJurnalSaquEntry;
use App\Support\WarehouseJurnalReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

/**
 * Jurnal otomatis Accounting Warehouse — Kas Tunai (blu), Rekening Utama & Reguler (saqu).
 * Disinkronkan dari baris {@see SpeedshopFinanceLedger} scope gudang.
 */
class WarehouseJurnalAutomationService
{
    public const AKUN_PIUTANG = 'PIUTANG';

    public const AKUN_TRANSFER_KAS = 'TRANSFER KAS';

    public const AKUN_KOREKSI_SALDO = 'KOREKSI SALDO';

    public const AKUN_KAS_TUNAI = 'KAS TUNAI';

    public const AKUN_PEMASUKAN = 'PEMASUKAN';

    public const AKUN_HUTANG_SUPPLIER = 'HUTANG SUPPLIER';

    public function syncFromLedger(SpeedshopFinanceLedger $ledger): void
    {
        if ((int) ($ledger->warehouse_id ?? 0) <= 0) {
            return;
        }
        if ((int) ($ledger->speedshop_id ?? 0) > 0) {
            return;
        }
        if (! $this->jurnalWarehouseColumnsReady()) {
            return;
        }
        if (! $this->isJurnalEligibleLedger($ledger)) {
            return;
        }

        $target = $this->resolveJurnalTarget($ledger);
        if ($target === null) {
            return;
        }

        $payload = $this->buildPayloadFromLedger($ledger);
        if ($payload === null) {
            return;
        }

        if ($target === 'blu') {
            $this->upsertBlu($ledger, $payload);
        } else {
            $this->upsertSaqu($ledger, $payload);
        }
    }

    public function deleteForLedger(SpeedshopFinanceLedger $ledger): void
    {
        if (! Schema::hasColumn('speedshop_jurnal_blu_entries', 'speedshop_finance_ledger_id')) {
            return;
        }

        SpeedshopJurnalBluEntry::query()
            ->where('speedshop_finance_ledger_id', $ledger->id)
            ->delete();
        SpeedshopJurnalSaquEntry::query()
            ->where('speedshop_finance_ledger_id', $ledger->id)
            ->delete();
    }

    public function jurnalWarehouseColumnsReady(): bool
    {
        return Schema::hasTable('speedshop_jurnal_blu_entries')
            && Schema::hasTable('speedshop_jurnal_saqu_entries')
            && Schema::hasColumn('speedshop_jurnal_blu_entries', 'warehouse_id')
            && Schema::hasColumn('speedshop_jurnal_blu_entries', 'speedshop_finance_ledger_id');
    }

    private function isJurnalEligibleLedger(SpeedshopFinanceLedger $ledger): bool
    {
        $pocket = (string) ($ledger->pocket ?? '');
        if (! in_array($pocket, [
            SpeedshopFinanceService::POCKET_LACI,
            SpeedshopFinanceService::POCKET_CASH_BESAR,
            SpeedshopFinanceService::POCKET_REKENING,
        ], true)) {
            return false;
        }

        return in_array((string) ($ledger->reason ?? ''), [
            SpeedshopFinanceService::REASON_PERMINTAAN_KAS_KECIL,
            SpeedshopFinanceService::REASON_KAS_TUNAI_DARI_REGULER_KELUAR,
            SpeedshopFinanceService::REASON_KAS_TUNAI_DARI_REGULER_MASUK,
            SpeedshopFinanceService::REASON_BIAYA_OPERASIONAL_KAS_KECIL,
            SpeedshopFinanceService::REASON_BIAYA_OPERASIONAL_REKENING,
            SpeedshopFinanceService::REASON_BIAYA_OPERASIONAL_REKENING_TUJUAN,
            SpeedshopFinanceService::REASON_SALDO_KOREKSI_MANAGER,
            SpeedshopFinanceService::REASON_PIUTANG_KELUAR,
            SpeedshopFinanceService::REASON_PIUTANG_MASUK,
            SpeedshopFinanceService::REASON_PEMASUKAN_KELUAR,
            SpeedshopFinanceService::REASON_PEMASUKAN_MASUK,
            SpeedshopFinanceService::REASON_HUTANG_SUPPLIER_KELUAR,
        ], true);
    }

    private function resolveJurnalTarget(SpeedshopFinanceLedger $ledger): ?string
    {
        $pocket = (string) ($ledger->pocket ?? '');

        if ($pocket === SpeedshopFinanceService::POCKET_LACI) {
            return 'blu';
        }

        if (in_array($pocket, [SpeedshopFinanceService::POCKET_CASH_BESAR, SpeedshopFinanceService::POCKET_REKENING], true)) {
            if (! Schema::hasColumn('speedshop_finance_ledgers', 'speedshop_keuangan_rekening_id')) {
                return null;
            }
            if ((int) ($ledger->speedshop_keuangan_rekening_id ?? 0) <= 0) {
                return null;
            }

            return 'saqu';
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildPayloadFromLedger(SpeedshopFinanceLedger $ledger): ?array
    {
        $ledger->loadMissing(['user:id,name', 'rincianBiayaEntry', 'warehousePemasukan', 'warehouseSupplierTagihan.supplier']);

        $amt = (int) ($ledger->amount ?? 0);
        if ($amt === 0) {
            return null;
        }

        $tanggal = $ledger->tanggal instanceof Carbon
            ? $ledger->tanggal->toDateString()
            : Carbon::parse($ledger->tanggal)->toDateString();

        $payload = [
            'speedshop_id' => null,
            'warehouse_id' => (int) $ledger->warehouse_id,
            'tanggal' => $tanggal,
            'akun' => $this->resolveAkun($ledger),
            'keterangan' => $this->resolveKeterangan($ledger),
            'pic' => trim((string) ($ledger->user?->name ?? '')) !== '' ? (string) $ledger->user->name : '-',
            'kredit' => $amt > 0 ? (float) $amt : 0.0,
            'debet' => $amt < 0 ? (float) (-$amt) : 0.0,
        ];

        if (Schema::hasColumn('speedshop_jurnal_blu_entries', 'speedshop_rincian_biaya_entry_id')
            && (int) ($ledger->speedshop_rincian_biaya_entry_id ?? 0) > 0) {
            $payload['speedshop_rincian_biaya_entry_id'] = (int) $ledger->speedshop_rincian_biaya_entry_id;
        }

        if (Schema::hasColumn('speedshop_jurnal_saqu_entries', 'speedshop_keuangan_rekening_id')
            && (int) ($ledger->speedshop_keuangan_rekening_id ?? 0) > 0) {
            $payload['speedshop_keuangan_rekening_id'] = (int) $ledger->speedshop_keuangan_rekening_id;
        }

        return $payload;
    }

    private function resolveAkun(SpeedshopFinanceLedger $ledger): string
    {
        return match ((string) ($ledger->reason ?? '')) {
            SpeedshopFinanceService::REASON_BIAYA_OPERASIONAL_KAS_KECIL,
            SpeedshopFinanceService::REASON_BIAYA_OPERASIONAL_REKENING => SpeedshopJurnalAutomationService::AKUN_BIAYA_OP,
            SpeedshopFinanceService::REASON_PIUTANG_KELUAR,
            SpeedshopFinanceService::REASON_PIUTANG_MASUK => self::AKUN_PIUTANG,
            SpeedshopFinanceService::REASON_KAS_TUNAI_DARI_REGULER_KELUAR,
            SpeedshopFinanceService::REASON_KAS_TUNAI_DARI_REGULER_MASUK => self::AKUN_TRANSFER_KAS,
            SpeedshopFinanceService::REASON_BIAYA_OPERASIONAL_REKENING_TUJUAN => self::AKUN_TRANSFER_KAS,
            SpeedshopFinanceService::REASON_SALDO_KOREKSI_MANAGER => self::AKUN_KOREKSI_SALDO,
            SpeedshopFinanceService::REASON_PERMINTAAN_KAS_KECIL => self::AKUN_KAS_TUNAI,
            SpeedshopFinanceService::REASON_PEMASUKAN_MASUK => self::AKUN_PEMASUKAN,
            SpeedshopFinanceService::REASON_PEMASUKAN_KELUAR => self::AKUN_TRANSFER_KAS,
            SpeedshopFinanceService::REASON_HUTANG_SUPPLIER_KELUAR => self::AKUN_HUTANG_SUPPLIER,
            default => 'MUTASI',
        };
    }

    private function resolveKeterangan(SpeedshopFinanceLedger $ledger): string
    {
        $hutang = $ledger->warehouseSupplierTagihan;
        if ($hutang) {
            $supplier = trim((string) ($hutang->supplier?->nama ?? ''));
            $parts = array_filter([
                'Pembayaran hutang supplier',
                $supplier !== '' ? $supplier : null,
                $hutang->kode ? '('.$hutang->kode.')' : null,
            ]);

            return implode(' — ', $parts);
        }

        $pemasukan = $ledger->warehousePemasukan;
        if ($pemasukan) {
            $ket = trim((string) ($pemasukan->keterangan ?? ''));
            if ($ket !== '') {
                return $ket;
            }
        }

        $rincian = $ledger->rincianBiayaEntry;
        if ($rincian) {
            $parts = array_filter([
                (string) ($rincian->nama_akun ?? ''),
                (string) ($rincian->no_akun ?? ''),
                (string) ($rincian->keterangan ?? ''),
            ], fn ($s) => trim($s) !== '');
            if ($parts !== []) {
                return implode(' — ', $parts);
            }
        }

        return WarehouseJurnalReport::humanizeLedgerReason($ledger->reason);
    }

    /** @param  array<string, mixed>  $payload */
    private function upsertBlu(SpeedshopFinanceLedger $ledger, array $payload): void
    {
        SpeedshopJurnalSaquEntry::query()
            ->where('speedshop_finance_ledger_id', $ledger->id)
            ->delete();

        $existing = SpeedshopJurnalBluEntry::query()
            ->where('speedshop_finance_ledger_id', $ledger->id)
            ->first();

        if ($existing) {
            $existing->update($payload);

            return;
        }

        SpeedshopJurnalBluEntry::query()->create(array_merge($payload, [
            'speedshop_finance_ledger_id' => $ledger->id,
        ]));
    }

    /** @param  array<string, mixed>  $payload */
    private function upsertSaqu(SpeedshopFinanceLedger $ledger, array $payload): void
    {
        SpeedshopJurnalBluEntry::query()
            ->where('speedshop_finance_ledger_id', $ledger->id)
            ->delete();

        $existing = SpeedshopJurnalSaquEntry::query()
            ->where('speedshop_finance_ledger_id', $ledger->id)
            ->first();

        if ($existing) {
            $existing->update($payload);

            return;
        }

        SpeedshopJurnalSaquEntry::query()->create(array_merge($payload, [
            'speedshop_finance_ledger_id' => $ledger->id,
        ]));
    }
}
