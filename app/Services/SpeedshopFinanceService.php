<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\ServiceOrder;
use App\Models\Speedshop;
use App\Models\SpeedshopFinanceLedger;
use App\Models\SpeedshopFinancePermintaanKasKecil;
use App\Models\SpeedshopFinanceSetoran;
use App\Models\SpeedshopFinanceSetoranItem;
use App\Models\SpeedshopFinanceSetting;
use App\Models\SpeedshopKeuanganRekening;
use App\Models\SpeedshopOwnerModalSetoran;
use App\Models\SpeedshopOwnerPengambilanDividen;
use App\Models\SpeedshopRincianBiayaEntry;
use App\Models\WarehousePemasukan;
use App\Models\WarehousePiutang;
use App\Models\WarehouseResellerPiutangPembayaran;
use App\Models\WarehouseResellerTagihan;
use App\Models\WarehouseResellerTagihanPembayaran;
use App\Models\WarehouseSupplierTagihan;
use App\Models\WarehouseSupplierTagihanPembayaran;
use App\Support\KeuanganScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SpeedshopFinanceService
{
    public const DEFAULT_MODAL_LACI_PER_HARI = 2_000_000;
    public const POCKET_LACI = 'laci';

    public const POCKET_CASH_BESAR = 'cash_besar';

    public const POCKET_REKENING = 'rekening_utama';

    /** Baris jurnal koreksi saldo buku per rekening (Manager). Max 64 char untuk kolom reason. */
    public const REASON_SALDO_KOREKSI_MANAGER = 'saldo_koreksi_manager';

    /** Tambahan saldo Kas Kecil (pocket laci) lewat menu permintaan cabang. Max 64 char untuk kolom reason. */
    public const REASON_PERMINTAAN_KAS_KECIL = 'permintaan_kas_kecil';

    public const REASON_KAS_TUNAI_DARI_REGULER_KELUAR = 'kas_tunai_reguler_keluar';

    public const REASON_KAS_TUNAI_DARI_REGULER_MASUK = 'kas_tunai_laci_masuk';

    /** Pengeluaran kas kecil setelah biaya operasional disetujui Accounting. */
    public const REASON_BIAYA_OPERASIONAL_KAS_KECIL = 'biaya_operasional_kas_kecil';

    /** Pengeluaran Kas Besar / Rekening Utama untuk biaya operasional input Accounting. */
    public const REASON_BIAYA_OPERASIONAL_REKENING = 'biaya_operasional_rekening';

    /** Alokasi masuk ke rekening tujuan dari pengajuan BOP (mis. rekening gaji karyawan). */
    public const REASON_BIAYA_OPERASIONAL_REKENING_TUJUAN = 'biaya_operasional_rekening_tujuan';

    /** Pengembalian dana / penyesuaian BOP — saldo rekening/tunai masuk kembali. */
    public const REASON_BOP_PENGEMBALIAN_MASUK = 'bop_pengembalian_masuk';

    /** Transfer modal owner ke rekening speedshop (keluar dari rekening owner). */
    public const REASON_OWNER_MODAL_KELUAR = 'owner_modal_keluar';

    /** Transfer modal owner ke rekening speedshop (masuk ke rekening cabang). */
    public const REASON_OWNER_MODAL_MASUK = 'owner_modal_masuk';

    /** Pengambilan dividen: keluar dari rekening speedshop. */
    public const REASON_OWNER_DIVIDEN_KELUAR = 'owner_dividen_keluar';

    /** Pengambilan dividen: masuk ke rekening owner. */
    public const REASON_OWNER_DIVIDEN_MASUK = 'owner_dividen_masuk';

    /** Dana talang warehouse: keluar dari rekening sumber saat dicatat. */
    public const REASON_PIUTANG_KELUAR = 'piutang_keluar';

    /** Dana talang warehouse: masuk kembali ke rekening sumber saat dilunasi. */
    public const REASON_PIUTANG_MASUK = 'piutang_masuk';

    /** Pemasukan warehouse — keluar dari rekening sumber. */
    public const REASON_PEMASUKAN_KELUAR = 'pemasukan_keluar';

    /** Pemasukan warehouse — masuk ke rekening tujuan (kredit jurnal). */
    public const REASON_PEMASUKAN_MASUK = 'pemasukan_masuk';

    /** Pembayaran hutang supplier warehouse — keluar dari rekening sumber. */
    public const REASON_HUTANG_SUPPLIER_KELUAR = 'hutang_supplier_keluar';

    /** Pembayaran tagihan reseller warehouse — masuk ke rekening penerima. */
    public const REASON_TAGIHAN_RESELLER_MASUK = 'tagihan_reseller_masuk';

    /** Refund kelebihan bayar tagihan reseller — keluar dari rekening sumber. */
    public const REASON_TAGIHAN_RESELLER_KELUAR = 'tagihan_reseller_keluar';

    /** Pelunasan piutang tagihan reseller — masuk ke rekening penerima. */
    public const REASON_PIUTANG_RESELLER_MASUK = 'piutang_reseller_masuk';

    public function resolveSpeedshopIdFromInvoice(Invoice $invoice): ?int
    {
        $invoice->loadMissing('serviceOrder');
        $so = $invoice->serviceOrder;
        if (! $so) {
            return null;
        }

        // Prioritaskan speedshop_id (per outlet) jika tersedia agar ledger tidak tercampur lintas cabang.
        if (Schema::hasColumn('service_orders', 'speedshop_id') && (int) ($so->speedshop_id ?? 0) > 0) {
            return (int) $so->speedshop_id;
        }

        $wid = $so->warehouse_id;
        if (! $wid) {
            return null;
        }

        return Speedshop::query()->where('warehouse_id', $wid)->value('id');
    }

    public function resolveSpeedshopIdFromServiceOrder(ServiceOrder $serviceOrder): ?int
    {
        // Prioritaskan speedshop_id (per outlet) jika tersedia.
        if (Schema::hasColumn('service_orders', 'speedshop_id') && (int) ($serviceOrder->speedshop_id ?? 0) > 0) {
            return (int) $serviceOrder->speedshop_id;
        }

        $wid = $serviceOrder->warehouse_id;
        if (! $wid) {
            return null;
        }

        return Speedshop::query()->where('warehouse_id', $wid)->value('id');
    }

    public function isMetodeTunai(?string $metode): bool
    {
        $m = strtolower(trim((string) $metode));

        return $m === '' || $m === 'cash' || $m === 'tunai';
    }

    public function ensureSettings(int $speedshopId): SpeedshopFinanceSetting
    {
        return $this->ensureSettingsForScope(KeuanganScope::forSpeedshop($speedshopId));
    }

    public function ensureSettingsForScope(KeuanganScope $scope): SpeedshopFinanceSetting
    {
        if ($scope->isWarehouse()) {
            return SpeedshopFinanceSetting::query()->firstOrCreate(
                ['warehouse_id' => $scope->warehouseId, 'speedshop_id' => null],
                ['modal_laci_per_hari' => self::DEFAULT_MODAL_LACI_PER_HARI],
            );
        }

        return SpeedshopFinanceSetting::query()->firstOrCreate(
            ['speedshop_id' => $scope->speedshopId, 'warehouse_id' => null],
            ['modal_laci_per_hari' => self::DEFAULT_MODAL_LACI_PER_HARI],
        );
    }

    public function recordInvoiceIncome(Invoice $invoice): void
    {
        $speedshopId = $this->resolveSpeedshopIdFromInvoice($invoice);
        if (! $speedshopId) {
            return;
        }

        $this->ensureSettings($speedshopId);

        // Jika ledger invoice sudah ada namun tersimpan di speedshop yang salah (akibat mapping lama berbasis warehouse),
        // koreksi supaya saldo buku & validasi setoran akurat per outlet.
        $existing = SpeedshopFinanceLedger::query()->where('invoice_id', $invoice->id)->get();
        if ($existing->isNotEmpty()) {
            if ($existing->every(fn ($l) => (int) $l->speedshop_id === (int) $speedshopId)) {
                return;
            }
            SpeedshopFinanceLedger::query()
                ->where('invoice_id', $invoice->id)
                ->update(['speedshop_id' => $speedshopId]);

            return;
        }

        $tanggal = $invoice->tanggal instanceof Carbon
            ? $invoice->tanggal->toDateString()
            : Carbon::parse($invoice->tanggal)->toDateString();

        $split = $invoice->pembayaran_split;
        if (is_array($split) && $split !== []) {
            $byPocket = [];
            foreach ($split as $row) {
                $metode = strtolower(trim((string) ($row['metode'] ?? '')));
                $lineAmt = (int) round((float) ($row['jumlah'] ?? 0));
                if ($lineAmt <= 0) {
                    continue;
                }
                $pocket = $this->isMetodeTunai($metode)
                    ? self::POCKET_LACI
                    : self::POCKET_CASH_BESAR;
                $byPocket[$pocket] = ($byPocket[$pocket] ?? 0) + $lineAmt;
            }
            foreach ($byPocket as $pocket => $amount) {
                if ($amount <= 0) {
                    continue;
                }
                $reason = $pocket === self::POCKET_LACI ? 'invoice_tunai' : 'invoice_digital';
                SpeedshopFinanceLedger::query()->create([
                    'speedshop_id' => $speedshopId,
                    'tanggal' => $tanggal,
                    'pocket' => $pocket,
                    'amount' => $amount,
                    'reason' => $reason,
                    'invoice_id' => $invoice->id,
                    'setoran_id' => null,
                    'user_id' => auth()->id(),
                ]);
            }

            return;
        }

        $amount = (int) round((float) $invoice->grand_total);
        if ($amount <= 0) {
            return;
        }

        $pocket = $this->isMetodeTunai($invoice->metode_pembayaran)
            ? self::POCKET_LACI
            : self::POCKET_CASH_BESAR;

        $reason = $pocket === self::POCKET_LACI ? 'invoice_tunai' : 'invoice_digital';

        SpeedshopFinanceLedger::query()->create([
            'speedshop_id' => $speedshopId,
            'tanggal' => $tanggal,
            'pocket' => $pocket,
            'amount' => $amount,
            'reason' => $reason,
            'invoice_id' => $invoice->id,
            'setoran_id' => null,
            'user_id' => auth()->id(),
        ]);
    }

    /**
     * Pastikan setiap invoice pada tanggal & cabang punya baris ledger (untuk data sebelum modul keuangan).
     */
    public function ensureInvoiceLedgersForSpeedshopDay(int $speedshopId, string $dateYmd): void
    {
        $sp = Speedshop::query()->find($speedshopId);
        if (! $sp?->warehouse_id) {
            return;
        }

        $invoiceIds = Invoice::query()
            ->whereDate('tanggal', $dateYmd)
            ->whereHas('serviceOrder', function ($q) use ($speedshopId, $sp) {
                // Untuk outlet: scope ke speedshop_id bila ada.
                if (Schema::hasColumn('service_orders', 'speedshop_id')) {
                    $q->where('speedshop_id', $speedshopId);

                    return;
                }
                $q->where('warehouse_id', $sp->warehouse_id);
            })
            ->pluck('id');

        foreach ($invoiceIds as $iid) {
            $inv = Invoice::query()->find($iid);
            if ($inv) {
                $this->recordInvoiceIncome($inv);
            }
        }
    }

    public function balancePocketAsOf(int $speedshopId, string $pocket, string $asOfYmd): int
    {
        return $this->balancePocketAsOfScope(KeuanganScope::forSpeedshop($speedshopId), $pocket, $asOfYmd);
    }

    public function balancePocketAsOfScope(KeuanganScope $scope, string $pocket, string $asOfYmd): int
    {
        return (int) $this->ledgerQueryForScope($scope)
            ->where('pocket', $pocket)
            ->whereDate('tanggal', '<=', $asOfYmd)
            ->sum('amount');
    }

    /** Saldo buku untuk satu pocket & rekening bank (alokasi lewat ledger). */
    public function balanceForBankPocketAsOf(int $speedshopId, string $pocket, int $keuanganRekeningId, string $asOfYmd): int
    {
        return $this->balanceForBankPocketAsOfScope(
            KeuanganScope::forSpeedshop($speedshopId),
            $pocket,
            $keuanganRekeningId,
            $asOfYmd,
        );
    }

    public function balanceForBankPocketAsOfScope(
        KeuanganScope $scope,
        string $pocket,
        int $keuanganRekeningId,
        string $asOfYmd,
    ): int {
        if (! Schema::hasColumn('speedshop_finance_ledgers', 'speedshop_keuangan_rekening_id')) {
            return 0;
        }

        return (int) $this->ledgerQueryForScope($scope)
            ->where('pocket', $pocket)
            ->where('speedshop_keuangan_rekening_id', $keuanganRekeningId)
            ->whereDate('tanggal', '<=', $asOfYmd)
            ->sum('amount');
    }

    /** Saldo buku Rekening Utama yang dialokasikan ke satu rekening bank (setoran masuk). */
    public function balanceRekeningUtamaForBankAsOf(int $speedshopId, int $keuanganRekeningId, string $asOfYmd): int
    {
        return $this->balanceRekeningUtamaForBankAsOfScope(
            KeuanganScope::forSpeedshop($speedshopId),
            $keuanganRekeningId,
            $asOfYmd,
        );
    }

    public function balanceRekeningUtamaForBankAsOfScope(KeuanganScope $scope, int $keuanganRekeningId, string $asOfYmd): int
    {
        return $this->balanceForBankPocketAsOfScope($scope, self::POCKET_REKENING, $keuanganRekeningId, $asOfYmd);
    }

    /** Saldo buku Kas Besar per rekening (jika ledger memakai alokasi rekening). */
    public function balanceKasBesarForBankAsOf(int $speedshopId, int $keuanganRekeningId, string $asOfYmd): int
    {
        return $this->balanceKasBesarForBankAsOfScope(
            KeuanganScope::forSpeedshop($speedshopId),
            $keuanganRekeningId,
            $asOfYmd,
        );
    }

    public function balanceKasBesarForBankAsOfScope(KeuanganScope $scope, int $keuanganRekeningId, string $asOfYmd): int
    {
        return $this->balanceForBankPocketAsOfScope($scope, self::POCKET_CASH_BESAR, $keuanganRekeningId, $asOfYmd);
    }

    /**
     * @return array{laci: int, cash_besar: int, rekening_utama: int}
     */
    public function balancesAsOf(int $speedshopId, string $asOfYmd): array
    {
        return $this->balancesAsOfScope(KeuanganScope::forSpeedshop($speedshopId), $asOfYmd);
    }

    /**
     * @return array{laci: int, cash_besar: int, rekening_utama: int}
     */
    public function balancesAsOfScope(KeuanganScope $scope, string $asOfYmd): array
    {
        return [
            'laci' => $this->balancePocketAsOfScope($scope, self::POCKET_LACI, $asOfYmd),
            'cash_besar' => $this->balancePocketAsOfScope($scope, self::POCKET_CASH_BESAR, $asOfYmd),
            'rekening_utama' => $this->balancePocketAsOfScope($scope, self::POCKET_REKENING, $asOfYmd),
        ];
    }

    /** Saldo buku pocket tanpa alokasi ke rekening bank (mis. setelah rekening dihapus). */
    public function balanceUnallocatedPocketAsOfScope(KeuanganScope $scope, string $pocket, string $asOfYmd): int
    {
        if (! Schema::hasColumn('speedshop_finance_ledgers', 'speedshop_keuangan_rekening_id')) {
            return 0;
        }

        return (int) $this->ledgerQueryForScope($scope)
            ->where('pocket', $pocket)
            ->whereNull('speedshop_keuangan_rekening_id')
            ->whereDate('tanggal', '<=', $asOfYmd)
            ->sum('amount');
    }

    /**
     * Ringkasan saldo warehouse dari rekening aktif (selaras dengan tabel), plus deteksi saldo yatim.
     *
     * @param  \Illuminate\Support\Collection<int, SpeedshopKeuanganRekening>  $rekenings
     * @return array{laci: int, cash_besar: int, rekening_utama: int, orphan_rekening_utama: int, orphan_cash_besar: int}
     */
    public function warehouseSummaryBalancesAsOfScope(KeuanganScope $scope, string $asOfYmd, $rekenings): array
    {
        $rekeningUtama = 0;
        $cashBesar = 0;

        foreach ($rekenings as $r) {
            $isSecondary = SpeedshopKeuanganRekening::isSecondaryRekeningJenis($r->jenis ?? null);
            if ($isSecondary) {
                $cashBesar += $this->balanceKasBesarForBankAsOfScope($scope, (int) $r->id, $asOfYmd);
            } else {
                $rekeningUtama += $this->balanceRekeningUtamaForBankAsOfScope($scope, (int) $r->id, $asOfYmd);
            }
        }

        return [
            'laci' => $this->balancePocketAsOfScope($scope, self::POCKET_LACI, $asOfYmd),
            'cash_besar' => $cashBesar,
            'rekening_utama' => $rekeningUtama,
            'orphan_rekening_utama' => $this->balanceUnallocatedPocketAsOfScope($scope, self::POCKET_REKENING, $asOfYmd),
            'orphan_cash_besar' => $this->balanceUnallocatedPocketAsOfScope($scope, self::POCKET_CASH_BESAR, $asOfYmd),
        ];
    }

    /** @return Builder<SpeedshopFinanceLedger> */
    private function ledgerQueryForScope(KeuanganScope $scope): Builder
    {
        return $scope->applyToLedger(SpeedshopFinanceLedger::query());
    }

    /** @return array<string, int|null> */
    public function ledgerOwnershipForScope(KeuanganScope $scope): array
    {
        return $scope->ownershipAttributes();
    }

    /**
     * @return array{
     *   pendapatan_tunai: int,
     *   pendapatan_digital: int,
     *   permintaan_kas: int,
     *   bop_kas_tunai: int,
     *   total_kas_tunai: int,
     *   sisa_setelah_bop: int,
     *   modal_laci: int,
     *   kas_disimpan_besok: int,
     *   wajib_setor_tunai: int,
     *   wajib_setor_digital: int,
     *   sisa_laci_setelah_setor: int,
     *   saldo_kas_kecil: int,
     *   saldo_kas_besar: int
     * }
     */
    public function computeDaySetoranSnapshot(int $speedshopId, string $dateYmd): array
    {
        return $this->computeDaySetoranSnapshotForScope(KeuanganScope::forSpeedshop($speedshopId), $dateYmd);
    }

    public function computeDaySetoranSnapshotForScope(KeuanganScope $scope, string $dateYmd): array
    {
        $empty = [
            'pendapatan_tunai' => 0,
            'pendapatan_digital' => 0,
            'permintaan_kas' => 0,
            'bop_kas_tunai' => 0,
            'total_kas_tunai' => 0,
            'sisa_setelah_bop' => 0,
            'modal_laci' => 0,
            'kas_disimpan_besok' => 0,
            'wajib_setor_tunai' => 0,
            'wajib_setor_digital' => 0,
            'sisa_laci_setelah_setor' => 0,
            'saldo_kas_kecil' => 0,
            'saldo_kas_besar' => 0,
        ];

        if ($scope->isWarehouse()) {
            $settings = $this->ensureSettingsForScope($scope);

            return array_merge($empty, [
                'modal_laci' => (int) $settings->modal_laci_per_hari,
                'kas_disimpan_besok' => (int) $settings->modal_laci_per_hari,
                'saldo_kas_kecil' => $this->balancePocketAsOfScope($scope, self::POCKET_LACI, $dateYmd),
                'saldo_kas_besar' => $this->balancePocketAsOfScope($scope, self::POCKET_CASH_BESAR, $dateYmd),
            ]);
        }

        $speedshopId = (int) $scope->speedshopId;
        $sp = Speedshop::query()->find($speedshopId);
        if (! $sp?->warehouse_id) {
            return $empty;
        }

        $settings = $this->ensureSettings($speedshopId);
        $modal = (int) $settings->modal_laci_per_hari;

        $base = Invoice::query()
            ->whereDate('tanggal', $dateYmd)
            ->whereHas('serviceOrder', function ($q) use ($speedshopId, $sp) {
                // Untuk outlet: scope ke speedshop_id agar tidak tercampur lintas cabang (warehouse_id bisa sama).
                if (Schema::hasColumn('service_orders', 'speedshop_id')) {
                    $q->where('speedshop_id', $speedshopId);

                    return;
                }
                $q->where('warehouse_id', $sp->warehouse_id);
            });

        $tunai = (int) round((float) (clone $base)
            ->where('metode_pembayaran', '!=', 'split')
            ->whereRaw('LOWER(TRIM(COALESCE(metode_pembayaran, ?))) IN (?, ?)', ['', 'cash', 'tunai'])
            ->sum('grand_total'));

        $digital = (int) round((float) (clone $base)
            ->where('metode_pembayaran', '!=', 'split')
            ->whereRaw('LOWER(TRIM(COALESCE(metode_pembayaran, ?))) NOT IN (?, ?)', ['', 'cash', 'tunai'])
            ->sum('grand_total'));

        if (Schema::hasColumn('invoices', 'pembayaran_split')) {
            $splitInvoices = (clone $base)
                ->where('metode_pembayaran', 'split')
                ->get(['id', 'pembayaran_split']);
            foreach ($splitInvoices as $invRow) {
                $split = $invRow->pembayaran_split;
                if (is_string($split)) {
                    $split = json_decode($split, true);
                }
                if (! is_array($split)) {
                    continue;
                }
                [$t, $d] = Invoice::splitAmountsTunaiDigital($split);
                $tunai += $t;
                $digital += $d;
            }
        }

        $permintaanKas = $this->sumPermintaanKasCompletedForDay($speedshopId, $dateYmd);
        $bopKasTunai = $this->sumBopKasTunaiForDay($speedshopId, $dateYmd);

        // Gabungan: transaksi tunai + permintaan kas, dikurangi BOP tunai.
        $totalKasTunai = $tunai + $permintaanKas;
        $sisaSetelahBop = max(0, $totalKasTunai - $bopKasTunai);

        // Simpan kas untuk besok = nominal permintaan hari ini (agar tidak perlu minta lagi),
        // atau modal laci default jika tidak ada permintaan.
        $targetSimpan = $permintaanKas > 0 ? $permintaanKas : $modal;
        $kasDisimpanBesok = min($sisaSetelahBop, $targetSimpan);
        $wajibTunai = max(0, $sisaSetelahBop - $kasDisimpanBesok);
        $wajibDigital = $digital;
        $sisaLaci = $kasDisimpanBesok;

        $bal = $this->balancesAsOf($speedshopId, $dateYmd);
        // Saldo buku setoran = sisa kas tunai setelah BOP (boleh disetor sebagian, sisanya modal besok).
        $saldoKasKecilBuku = $sisaSetelahBop;

        return [
            'pendapatan_tunai' => $tunai,
            'pendapatan_digital' => $digital,
            'permintaan_kas' => $permintaanKas,
            'bop_kas_tunai' => $bopKasTunai,
            'total_kas_tunai' => $totalKasTunai,
            'sisa_setelah_bop' => $sisaSetelahBop,
            'modal_laci' => $modal,
            'kas_disimpan_besok' => $kasDisimpanBesok,
            'wajib_setor_tunai' => $wajibTunai,
            'wajib_setor_digital' => $wajibDigital,
            'sisa_laci_setelah_setor' => $sisaLaci,
            'saldo_kas_kecil' => $saldoKasKecilBuku,
            'saldo_kas_besar' => (int) $bal['cash_besar'],
        ];
    }

    /**
     * Ringkasan kas tunai gabungan untuk dashboard (periode filter).
     *
     * @return array{
     *   pendapatan_tunai: int,
     *   permintaan_kas: int,
     *   bop_kas_tunai: int,
     *   total_kas_tunai: int,
     *   sisa_setelah_bop: int,
     *   kas_disimpan_besok: int,
     *   wajib_setor_tunai: int,
     *   modal_laci: int,
     *   period_label: string,
     *   is_single_day: bool
     * }|null
     */
    public function computeKasTunaiRingkasanForSpeedshop(
        int $speedshopId,
        Carbon $dari,
        Carbon $sampai,
        string $periodLabel,
    ): ?array {
        $sp = Speedshop::query()->find($speedshopId);
        if (! $sp) {
            return null;
        }

        $start = $dari->copy()->startOfDay();
        $end = $sampai->copy()->startOfDay();
        $isSingleDay = $start->toDateString() === $end->toDateString();

        if ($isSingleDay) {
            $snap = $this->computeDaySetoranSnapshot($speedshopId, $start->toDateString());

            return [
                'pendapatan_tunai' => (int) $snap['pendapatan_tunai'],
                'permintaan_kas' => (int) $snap['permintaan_kas'],
                'bop_kas_tunai' => (int) $snap['bop_kas_tunai'],
                'total_kas_tunai' => (int) $snap['total_kas_tunai'],
                'sisa_setelah_bop' => (int) $snap['sisa_setelah_bop'],
                'kas_disimpan_besok' => (int) $snap['kas_disimpan_besok'],
                'wajib_setor_tunai' => (int) $snap['wajib_setor_tunai'],
                'modal_laci' => (int) $snap['modal_laci'],
                'period_label' => $periodLabel,
                'is_single_day' => true,
            ];
        }

        $settings = $this->ensureSettings($speedshopId);
        $modal = (int) $settings->modal_laci_per_hari;
        $dayCount = max(1, $start->diffInDays($end) + 1);

        $tunai = $this->sumPendapatanTunaiBetween($speedshopId, $start->toDateString(), $end->toDateString());
        $permintaanKas = $this->sumPermintaanKasCompletedBetween($speedshopId, $start->toDateString(), $end->toDateString());
        $bopKasTunai = $this->sumBopKasTunaiBetween($speedshopId, $start->toDateString(), $end->toDateString());

        $totalKasTunai = $tunai + $permintaanKas;
        $sisaSetelahBop = max(0, $totalKasTunai - $bopKasTunai);
        $targetSimpan = $permintaanKas > 0 ? $permintaanKas : ($modal * $dayCount);
        $kasDisimpanBesok = min($sisaSetelahBop, $targetSimpan);
        $wajibTunai = max(0, $sisaSetelahBop - $kasDisimpanBesok);

        return [
            'pendapatan_tunai' => $tunai,
            'permintaan_kas' => $permintaanKas,
            'bop_kas_tunai' => $bopKasTunai,
            'total_kas_tunai' => $totalKasTunai,
            'sisa_setelah_bop' => $sisaSetelahBop,
            'kas_disimpan_besok' => $kasDisimpanBesok,
            'wajib_setor_tunai' => $wajibTunai,
            'modal_laci' => $modal,
            'period_label' => $periodLabel,
            'is_single_day' => false,
        ];
    }

    private function sumPendapatanTunaiBetween(int $speedshopId, string $startYmd, string $endYmd): int
    {
        $sp = Speedshop::query()->find($speedshopId);
        if (! $sp?->warehouse_id) {
            return 0;
        }

        $base = Invoice::query()
            ->whereDate('tanggal', '>=', $startYmd)
            ->whereDate('tanggal', '<=', $endYmd)
            ->whereHas('serviceOrder', function ($q) use ($speedshopId, $sp) {
                if (Schema::hasColumn('service_orders', 'speedshop_id')) {
                    $q->where('speedshop_id', $speedshopId);

                    return;
                }
                $q->where('warehouse_id', $sp->warehouse_id);
            });

        $tunai = (int) round((float) (clone $base)
            ->where('metode_pembayaran', '!=', 'split')
            ->whereRaw('LOWER(TRIM(COALESCE(metode_pembayaran, ?))) IN (?, ?)', ['', 'cash', 'tunai'])
            ->sum('grand_total'));

        if (Schema::hasColumn('invoices', 'pembayaran_split')) {
            $splitInvoices = (clone $base)
                ->where('metode_pembayaran', 'split')
                ->get(['id', 'pembayaran_split']);
            foreach ($splitInvoices as $invRow) {
                $split = $invRow->pembayaran_split;
                if (is_string($split)) {
                    $split = json_decode($split, true);
                }
                if (! is_array($split)) {
                    continue;
                }
                [$t] = Invoice::splitAmountsTunaiDigital($split);
                $tunai += $t;
            }
        }

        return $tunai;
    }

    private function sumPermintaanKasCompletedForDay(int $speedshopId, string $dateYmd): int
    {
        return $this->sumPermintaanKasCompletedBetween($speedshopId, $dateYmd, $dateYmd);
    }

    private function sumPermintaanKasCompletedBetween(int $speedshopId, string $startYmd, string $endYmd): int
    {
        if (! Schema::hasTable('speedshop_finance_permintaan_kas_kecil')) {
            return 0;
        }

        return (int) SpeedshopFinancePermintaanKasKecil::query()
            ->where('speedshop_id', $speedshopId)
            ->where('status', SpeedshopFinancePermintaanKasKecil::STATUS_COMPLETED)
            ->whereDate('tanggal', '>=', $startYmd)
            ->whereDate('tanggal', '<=', $endYmd)
            ->sum('nominal');
    }

    private function sumBopKasTunaiForDay(int $speedshopId, string $dateYmd): int
    {
        return $this->sumBopKasTunaiBetween($speedshopId, $dateYmd, $dateYmd);
    }

    private function sumBopKasTunaiBetween(int $speedshopId, string $startYmd, string $endYmd): int
    {
        if (! Schema::hasTable('speedshop_rincian_biaya_entries')) {
            return 0;
        }

        $nominalSql = SpeedshopRincianBiayaEntry::sqlNominalEfektif();

        $total = SpeedshopRincianBiayaEntry::query()
            ->where('speedshop_id', $speedshopId)
            ->where('jenis_operasional', SpeedshopRincianBiayaEntry::JENIS_BIAYA_OPERASIONAL)
            ->where(function ($q) {
                $q->whereNull('approval_status')
                    ->orWhere('approval_status', SpeedshopRincianBiayaEntry::STATUS_APPROVED);
            })
            ->where(function ($q) {
                $q->whereNull('metode')
                    ->orWhere('metode', 'tunai')
                    ->orWhere('metode', 'cash');
            })
            ->whereDate('tanggal', '>=', $startYmd)
            ->whereDate('tanggal', '<=', $endYmd)
            ->selectRaw("COALESCE(SUM({$nominalSql}), 0) as total")
            ->value('total');

        return (int) round((float) $total);
    }

    /**
     * Tanggal kalender (Y-m-d) sebelum hari ini yang masih punya invoice namun belum setoran selesai.
     *
     * @return list<string>
     */
    public function pendingSetoranDatesBeforeToday(int $speedshopId): array
    {
        return $this->pendingSetoranDatesBeforeDate($speedshopId, now()->toDateString());
    }

    /**
     * Tanggal kalender (Y-m-d) sebelum tanggal tertentu yang masih punya invoice namun belum setoran selesai.
     * Dipakai untuk memblokir pengajuan tanggal baru bila ada tanggal sebelumnya yang belum disetor.
     *
     * @return list<string>
     */
    public function pendingSetoranDatesBeforeDate(int $speedshopId, string $dateYmdExclusive): array
    {
        $sp = Speedshop::query()->find($speedshopId);
        if (! $sp?->warehouse_id) {
            return [];
        }

        // Hanya tanggal dalam jendela ini yang wajib setoran; menghindari blokir total karena invoice lawas sebelum modul keuangan.
        $cutoff = now()->subDays(90)->toDateString();

        $dateExpr = Schema::getConnection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m-%d', tanggal)"
            : 'DATE(tanggal)';

        $dates = Invoice::query()
            ->whereDate('tanggal', '<', $dateYmdExclusive)
            ->whereDate('tanggal', '>=', $cutoff)
            ->whereHas('serviceOrder', function ($q) use ($speedshopId, $sp) {
                // Untuk outlet: scope ke speedshop_id bila ada.
                if (Schema::hasColumn('service_orders', 'speedshop_id')) {
                    $q->where('speedshop_id', $speedshopId);

                    return;
                }
                $q->where('warehouse_id', $sp->warehouse_id);
            })
            ->selectRaw("{$dateExpr} as d")
            ->groupBy(DB::raw($dateExpr))
            ->orderBy('d')
            ->pluck('d')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->values()
            ->all();

        $out = [];
        foreach ($dates as $d) {
            $hasSetoran = SpeedshopFinanceSetoran::query()
                ->where('speedshop_id', $speedshopId)
                ->whereDate('tanggal_setor', $d)
                ->where('status', 'completed')
                ->exists();

            if (! $hasSetoran) {
                $out[] = $d;
            }
        }

        return $out;
    }

    public function canTransactToday(?int $speedshopId): bool
    {
        if (! $speedshopId) {
            return true;
        }

        return $this->pendingSetoranDatesBeforeToday($speedshopId) === [];
    }

    /**
     * @param  bool  $pendingApproval  true = Speedshop (Manager/Staf): catat tanpa jurnal sampai Accounting menyetujui.
     *                                 Jika tabel speedshop_finance_setoran_items ada, jurnal completed diposting lewat {@see postSetoranLedgersAfterItemsSaved} setelah item disimpan.
     * @param  ?string  $buktiKasKecilPath  file disimpan di disk public, path relatif
     * @param  ?string  $buktiKasBesarPath  file disimpan di disk public, path relatif
     */
    public function submitSetoran(
        int $speedshopId,
        string $tanggalSetorYmd,
        int $setorTunaiActual,
        int $setorDigitalActual,
        ?string $catatan = null,
        ?int $keuanganRekeningId = null,
        ?int $kasBesarRekeningId = null,
        ?string $buktiKasKecilPath = null,
        ?string $buktiKasBesarPath = null,
        bool $pendingApproval = false,
        int $setorKkKeKbActual = 0,
    ): SpeedshopFinanceSetoran {
        return DB::transaction(function () use ($speedshopId, $tanggalSetorYmd, $setorTunaiActual, $setorDigitalActual, $catatan, $keuanganRekeningId, $kasBesarRekeningId, $buktiKasKecilPath, $buktiKasBesarPath, $pendingApproval, $setorKkKeKbActual) {
            $snap = $this->validateSetoranSubmission(
                $speedshopId,
                $tanggalSetorYmd,
                $setorTunaiActual,
                $setorDigitalActual,
                $keuanganRekeningId,
                $kasBesarRekeningId,
                $setorKkKeKbActual,
            );

            $this->assertNoBlockingSetoranRowForDate($speedshopId, $tanggalSetorYmd);

            $uid = (int) auth()->id();

            $setoranAttrs = array_merge(
                ['speedshop_id' => $speedshopId, 'warehouse_id' => null],
                [
                    'tanggal_setor' => $tanggalSetorYmd,
                    'pendapatan_tunai_hari' => $snap['pendapatan_tunai'],
                    'pendapatan_digital_hari' => $snap['pendapatan_digital'],
                    'modal_laci_snapshot' => $snap['modal_laci'],
                    'wajib_setor_tunai' => $snap['wajib_setor_tunai'],
                    'wajib_setor_digital' => $snap['wajib_setor_digital'],
                    'setor_tunai_actual' => $setorTunaiActual,
                    'setor_digital_actual' => $setorDigitalActual,
                    'status' => $pendingApproval ? 'pending' : 'completed',
                    'completed_at' => $pendingApproval ? null : now(),
                    'user_id' => $uid,
                    'catatan' => $catatan,
                ],
            );
            if (Schema::hasColumn('speedshop_finance_setorans', 'submitted_by')) {
                $setoranAttrs['submitted_by'] = auth()->user()->isAccounting()
                    ? SpeedshopFinanceSetoran::SUBMITTED_BY_ACCOUNTING
                    : SpeedshopFinanceSetoran::SUBMITTED_BY_SPEEDSHOP;
            }
            if (! $pendingApproval && Schema::hasColumn('speedshop_finance_setorans', 'approved_by')) {
                $setoranAttrs['approved_by'] = $uid;
                $setoranAttrs['approved_at'] = now();
            }
            if (Schema::hasColumn('speedshop_finance_setorans', 'speedshop_keuangan_rekening_id')) {
                $setoranAttrs['speedshop_keuangan_rekening_id'] = $keuanganRekeningId;
            }
            if (Schema::hasColumn('speedshop_finance_setorans', 'speedshop_keuangan_kas_besar_id')) {
                $setoranAttrs['speedshop_keuangan_kas_besar_id'] = $kasBesarRekeningId;
            }
            if ($buktiKasKecilPath !== null && Schema::hasColumn('speedshop_finance_setorans', 'bukti_kas_kecil_path')) {
                $setoranAttrs['bukti_kas_kecil_path'] = $buktiKasKecilPath;
            }
            if ($buktiKasBesarPath !== null && Schema::hasColumn('speedshop_finance_setorans', 'bukti_kas_besar_path')) {
                $setoranAttrs['bukti_kas_besar_path'] = $buktiKasBesarPath;
            }
            if (Schema::hasColumn('speedshop_finance_setorans', 'setor_kk_ke_kb_actual')) {
                $setoranAttrs['setor_kk_ke_kb_actual'] = max(0, $setorKkKeKbActual);
            }

            $row = SpeedshopFinanceSetoran::query()->create($setoranAttrs);

            // Jurnal per transaksi diisi lewat speedshop_finance_setoran_items setelah baris header dibuat
            // (lihat KeuanganSetoranController + postSetoranLedgersAfterItemsSaved). Hindari post ganda / nominal nol.
            if (! $pendingApproval && ! Schema::hasTable('speedshop_finance_setoran_items')) {
                $this->postSetoranLedgers(
                    $row->fresh(),
                    $setorTunaiActual,
                    $setorDigitalActual,
                    $setorKkKeKbActual,
                    $keuanganRekeningId,
                    $uid,
                );
            }

            return $row->fresh();
        });
    }

    /**
     * Posting baris jurnal (speedshop_finance_ledgers) untuk setoran yang statusnya sudah completed:
     * - bila ada speedshop_finance_setoran_items, pakai alur transaksi dinamis;
     * - selain itu pakai agregat header (setor_tunai_actual / setor_digital_actual / setor_kk_ke_kb_actual).
     */
    public function postSetoranLedgersAfterItemsSaved(SpeedshopFinanceSetoran $setoran): void
    {
        $uid = (int) auth()->id();
        $hasItemsTable = Schema::hasTable('speedshop_finance_setoran_items');
        $items = $hasItemsTable
            ? SpeedshopFinanceSetoranItem::query()->where('setoran_id', $setoran->id)->get()
            : collect();

        if ($items->isNotEmpty()) {
            $tgl = $setoran->tanggal_setor instanceof Carbon
                ? $setoran->tanggal_setor->toDateString()
                : Carbon::parse($setoran->tanggal_setor)->toDateString();
            $this->validateSetoranTransactions((int) $setoran->speedshop_id, $tgl, $items->all());
            $this->postSetoranLedgersFromTransactions($setoran->fresh(), $items->all(), $uid);

            return;
        }

        $tunai = (int) $setoran->setor_tunai_actual;
        $digital = (int) $setoran->setor_digital_actual;
        $kkKeKb = Schema::hasColumn('speedshop_finance_setorans', 'setor_kk_ke_kb_actual')
            ? (int) ($setoran->setor_kk_ke_kb_actual ?? 0)
            : 0;
        $rekId = $setoran->speedshop_keuangan_rekening_id !== null ? (int) $setoran->speedshop_keuangan_rekening_id : null;
        $this->postSetoranLedgers($setoran->fresh(), $tunai, $digital, $kkKeKb, $rekId, $uid);
    }

    public function approveSetoran(SpeedshopFinanceSetoran $setoran): SpeedshopFinanceSetoran
    {
        return DB::transaction(function () use ($setoran) {
            /** @var SpeedshopFinanceSetoran $locked */
            $locked = SpeedshopFinanceSetoran::query()->whereKey($setoran->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'pending') {
                throw new \InvalidArgumentException('Hanya setoran berstatus menunggu verifikasi yang dapat disetujui.');
            }

            $hasItemsTable = Schema::hasTable('speedshop_finance_setoran_items');
            $items = $hasItemsTable
                ? SpeedshopFinanceSetoranItem::query()->where('setoran_id', $locked->id)->get()
                : collect();

            if ($items->isNotEmpty()) {
                $this->validateSetoranTransactions((int) $locked->speedshop_id, $locked->tanggal_setor->toDateString(), $items->all());
            } else {
                $tunai = (int) $locked->setor_tunai_actual;
                $digital = (int) $locked->setor_digital_actual;
                $kkKeKb = Schema::hasColumn('speedshop_finance_setorans', 'setor_kk_ke_kb_actual')
                    ? (int) $locked->setor_kk_ke_kb_actual
                    : 0;
                $rekId = $locked->speedshop_keuangan_rekening_id !== null ? (int) $locked->speedshop_keuangan_rekening_id : null;
                $kasBesarId = Schema::hasColumn('speedshop_finance_setorans', 'speedshop_keuangan_kas_besar_id') && $locked->speedshop_keuangan_kas_besar_id !== null
                    ? (int) $locked->speedshop_keuangan_kas_besar_id
                    : null;

                $this->validateSetoranSubmission(
                    (int) $locked->speedshop_id,
                    $locked->tanggal_setor->toDateString(),
                    $tunai,
                    $digital,
                    $rekId,
                    $kasBesarId,
                    $kkKeKb,
                );
            }

            $approverId = (int) auth()->id();
            $locked->update([
                'status' => 'completed',
                'completed_at' => now(),
                'approved_by' => $approverId,
                'approved_at' => now(),
                'rejected_reason' => null,
            ]);

            if ($items->isNotEmpty()) {
                $this->postSetoranLedgersFromTransactions($locked->fresh(), $items->all(), $approverId);
            } else {
                $this->postSetoranLedgers(
                    $locked->fresh(),
                    $tunai,
                    $digital,
                    $kkKeKb,
                    $rekId,
                    $approverId,
                );
            }

            return $locked->fresh();
        });
    }

    /**
     * @param  SpeedshopFinanceSetoranItem[]  $items
     */
    private function validateSetoranTransactions(int $speedshopId, string $tanggalSetorYmd, array $items): void
    {
        $this->ensureSettings($speedshopId);
        $this->ensureInvoiceLedgersForSpeedshopDay($speedshopId, $tanggalSetorYmd);

        $saldoLaci = (int) $this->balancePocketAsOf($speedshopId, self::POCKET_LACI, $tanggalSetorYmd);
        $outLaci = 0;
        $outKasBesarByBank = [];
        $outRekeningByBank = [];

        foreach ($items as $it) {
            $amt = (int) ($it->nominal ?? 0);
            if ($amt <= 0) {
                continue;
            }

            $src = (string) ($it->sumber ?? '');
            $dst = (string) ($it->tujuan_type ?? '');
            if ($src === '' || $dst === '') {
                throw new \InvalidArgumentException('Sumber dan tujuan wajib dipilih.');
            }
            if ($src === $dst) {
                throw new \InvalidArgumentException('Sumber dan tujuan tidak boleh sama.');
            }

            if ($src === 'kas_kecil') {
                $outLaci += $amt;
            } elseif ($src === 'kas_besar') {
                $bid = (int) ($it->sumber_kas_besar_id ?? 0);
                if ($bid <= 0) {
                    throw new \InvalidArgumentException('Sumber Kas Besar wajib memilih rekening.');
                }
                $outKasBesarByBank[$bid] = (int) ($outKasBesarByBank[$bid] ?? 0) + $amt;
            } elseif ($src === 'rekening_utama') {
                $bid = (int) ($it->sumber_rekening_id ?? 0);
                if ($bid <= 0) {
                    throw new \InvalidArgumentException('Sumber Rekening Utama wajib memilih rekening.');
                }
                $outRekeningByBank[$bid] = (int) ($outRekeningByBank[$bid] ?? 0) + $amt;
            } else {
                throw new \InvalidArgumentException('Sumber tidak valid.');
            }

            if (in_array($dst, ['kas_besar', 'rekening_utama'], true)) {
                if (! $it->tujuan_rekening_id) {
                    throw new \InvalidArgumentException('Rekening tujuan wajib dipilih.');
                }
            }
        }

        if ($outLaci > $saldoLaci) {
            throw new \InvalidArgumentException('Total transaksi dari Kas Kecil melebihi saldo buku (Rp '.number_format($saldoLaci, 0, ',', '.').').');
        }

        foreach ($outKasBesarByBank as $bid => $out) {
            $saldo = (int) $this->balanceKasBesarForBankAsOf($speedshopId, (int) $bid, $tanggalSetorYmd);
            if ((int) $out > $saldo) {
                throw new \InvalidArgumentException('Transaksi dari Kas Besar melebihi saldo buku rekening terpilih (Rp '.number_format($saldo, 0, ',', '.').').');
            }
        }

        foreach ($outRekeningByBank as $bid => $out) {
            $saldo = (int) $this->balanceRekeningUtamaForBankAsOf($speedshopId, (int) $bid, $tanggalSetorYmd);
            if ((int) $out > $saldo) {
                throw new \InvalidArgumentException('Transaksi dari Rekening Utama melebihi saldo buku rekening terpilih (Rp '.number_format($saldo, 0, ',', '.').').');
            }
        }
    }

    /**
     * @param  SpeedshopFinanceSetoranItem[]  $items
     */
    private function postSetoranLedgersFromTransactions(SpeedshopFinanceSetoran $setoran, array $items, int $ledgerUserId): void
    {
        if ($setoran->ledgers()->exists()) {
            return;
        }

        $speedshopId = (int) $setoran->speedshop_id;
        $sid = (int) $setoran->id;
        $tgl = $setoran->tanggal_setor instanceof Carbon
            ? $setoran->tanggal_setor->toDateString()
            : Carbon::parse($setoran->tanggal_setor)->toDateString();

        $ledgerHasRekCol = Schema::hasColumn('speedshop_finance_ledgers', 'speedshop_keuangan_rekening_id');

        foreach ($items as $it) {
            $amt = (int) ($it->nominal ?? 0);
            if ($amt <= 0) {
                continue;
            }

            $src = (string) $it->sumber;
            $dst = (string) $it->tujuan_type;

            $srcPocket = $src === 'kas_kecil'
                ? self::POCKET_LACI
                : ($src === 'kas_besar' ? self::POCKET_CASH_BESAR : self::POCKET_REKENING);

            $dstPocket = $dst === 'kas_kecil'
                ? self::POCKET_LACI
                : ($dst === 'kas_besar' ? self::POCKET_CASH_BESAR : self::POCKET_REKENING);

            $srcBankId = $src === 'kas_besar'
                ? (int) ($it->sumber_kas_besar_id ?? 0)
                : ($src === 'rekening_utama' ? (int) ($it->sumber_rekening_id ?? 0) : 0);

            $dstBankId = in_array($dst, ['kas_besar', 'rekening_utama'], true)
                ? (int) ($it->tujuan_rekening_id ?? 0)
                : 0;

            $attrsOut = [
                'speedshop_id' => $speedshopId,
                'tanggal' => $tgl,
                'pocket' => $srcPocket,
                'amount' => -$amt,
                'reason' => 'setoran_tx_out',
                'invoice_id' => null,
                'setoran_id' => $sid,
                'user_id' => $ledgerUserId,
            ];
            if ($ledgerHasRekCol && $srcPocket !== self::POCKET_LACI) {
                $attrsOut['speedshop_keuangan_rekening_id'] = $srcBankId ?: null;
            }
            SpeedshopFinanceLedger::query()->create($attrsOut);

            $attrsIn = [
                'speedshop_id' => $speedshopId,
                'tanggal' => $tgl,
                'pocket' => $dstPocket,
                'amount' => $amt,
                'reason' => 'setoran_tx_in',
                'invoice_id' => null,
                'setoran_id' => $sid,
                'user_id' => $ledgerUserId,
            ];
            if ($ledgerHasRekCol && $dstPocket !== self::POCKET_LACI) {
                $attrsIn['speedshop_keuangan_rekening_id'] = $dstBankId ?: null;
            }
            SpeedshopFinanceLedger::query()->create($attrsIn);
        }
    }

    public function rejectSetoran(SpeedshopFinanceSetoran $setoran, string $reason): SpeedshopFinanceSetoran
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw new \InvalidArgumentException('Alasan penolakan wajib diisi.');
        }

        return DB::transaction(function () use ($setoran, $reason) {
            /** @var SpeedshopFinanceSetoran $locked */
            $locked = SpeedshopFinanceSetoran::query()->whereKey($setoran->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'pending') {
                throw new \InvalidArgumentException('Hanya setoran menunggu verifikasi yang dapat ditolak.');
            }

            $locked->update([
                'status' => 'rejected',
                'completed_at' => null,
                'rejected_reason' => $reason,
            ]);

            return $locked->fresh();
        });
    }

    /**
     * @return array<string, mixed> snapshot computeDaySetoranSnapshot
     */
    private function validateSetoranSubmission(
        int $speedshopId,
        string $tanggalSetorYmd,
        int $setorTunaiActual,
        int $setorDigitalActual,
        ?int $keuanganRekeningId,
        ?int $kasBesarRekeningId = null,
        int $setorKkKeKbActual = 0,
    ): array {
        $this->ensureSettings($speedshopId);
        $this->ensureInvoiceLedgersForSpeedshopDay($speedshopId, $tanggalSetorYmd);

        if ($keuanganRekeningId !== null) {
            $q = SpeedshopKeuanganRekening::query()
                ->where('id', $keuanganRekeningId)
                ->where('speedshop_id', $speedshopId);
            if (Schema::hasColumn('speedshop_keuangan_rekenings', 'jenis')) {
                $q->where('jenis', SpeedshopKeuanganRekening::JENIS_REKENING_UTAMA);
            }
            if (! $q->exists()) {
                throw new \InvalidArgumentException('Rekening tujuan setoran harus berjenis Rekening Utama dan valid untuk cabang ini.');
            }
        }

        $fkKbCol = Schema::hasColumn('speedshop_finance_setorans', 'speedshop_keuangan_kas_besar_id');
        $cabangHasRekening = SpeedshopKeuanganRekening::query()
            ->where('speedshop_id', $speedshopId)
            ->exists();
        $kbDedicated = Schema::hasColumn('speedshop_keuangan_rekenings', 'jenis')
            && SpeedshopKeuanganRekening::query()
                ->where('speedshop_id', $speedshopId)
                ->whereIn('jenis', SpeedshopKeuanganRekening::secondaryJenisValues())
                ->exists();

        if ($setorDigitalActual > 0 && $fkKbCol && $cabangHasRekening && $kasBesarRekeningId === null) {
            throw new \InvalidArgumentException('Pilih rekening untuk sumber setoran digital.');
        }

        $setorKkKeKbActual = max(0, $setorKkKeKbActual);
        if ($setorKkKeKbActual > 0 && $fkKbCol && $cabangHasRekening && $kasBesarRekeningId === null) {
            throw new \InvalidArgumentException('Pilih rekening Kas Besar untuk pindah saldo dari Kas Kecil.');
        }

        if ($kasBesarRekeningId !== null) {
            $qKb = SpeedshopKeuanganRekening::query()
                ->where('id', $kasBesarRekeningId)
                ->where('speedshop_id', $speedshopId);
            if (Schema::hasColumn('speedshop_keuangan_rekenings', 'jenis')) {
                if ($kbDedicated) {
                    $qKb->whereIn('jenis', SpeedshopKeuanganRekening::secondaryJenisValues());
                } else {
                    $qKb->where('jenis', SpeedshopKeuanganRekening::JENIS_REKENING_UTAMA);
                }
            }
            if (! $qKb->exists()) {
                throw new \InvalidArgumentException('Rekening sumber Kas Besar tidak valid untuk cabang ini.');
            }
        }

        $snap = $this->computeDaySetoranSnapshot($speedshopId, $tanggalSetorYmd);

        $saldoLaci = (int) $snap['saldo_kas_kecil'];
        $saldoBesar = (int) $snap['saldo_kas_besar'];

        if ($setorTunaiActual < 0 || $setorDigitalActual < 0) {
            throw new \InvalidArgumentException('Nominal setoran tidak boleh negatif.');
        }

        $totalKeluarLaci = $setorTunaiActual + $setorKkKeKbActual;
        if ($totalKeluarLaci > $saldoLaci) {
            throw new \InvalidArgumentException(
                'Total keluar Kas Kecil (setor ke Rekening Utama + pindah ke Kas Besar) melebihi saldo buku (Rp '.number_format($saldoLaci, 0, ',', '.').
                '). Total diajukan: Rp '.number_format($totalKeluarLaci, 0, ',', '.').'.'
            );
        }

        // Nominal bebas diinput manual, tapi tidak boleh melebihi saldo buku.
        if ($setorTunaiActual > $saldoLaci) {
            throw new \InvalidArgumentException(
                'Setor tunai (Kas Kecil) melebihi saldo buku tanggal tersebut (Rp '.number_format($saldoLaci, 0, ',', '.').').'
            );
        }
        if ($setorDigitalActual > $saldoBesar) {
            throw new \InvalidArgumentException(
                'Setor digital (Kas Besar) melebihi saldo buku tanggal tersebut (Rp '.number_format($saldoBesar, 0, ',', '.').').'
            );
        }

        return $snap;
    }

    private function assertNoBlockingSetoranRowForDate(int $speedshopId, string $tanggalSetorYmd): void
    {
        // Setoran per tanggal boleh lebih dari satu kali.
        // Yang diblokir: bila ada tanggal sebelumnya yang punya invoice namun belum ada setoran completed.
        $missing = $this->pendingSetoranDatesBeforeDate($speedshopId, $tanggalSetorYmd);
        if ($missing !== []) {
            $first = $missing[0] ?? null;
            $firstLabel = $first ? Carbon::parse($first)->format('d/m/Y') : null;
            throw new \InvalidArgumentException(
                $firstLabel
                    ? "Tidak dapat membuat setoran untuk tanggal ini. Masih ada tanggal sebelumnya yang belum disetor: {$firstLabel}."
                    : 'Tidak dapat membuat setoran untuk tanggal ini. Masih ada tanggal sebelumnya yang belum disetor.'
            );
        }
    }

    private function postSetoranLedgers(
        SpeedshopFinanceSetoran $row,
        int $setorTunaiActual,
        int $setorDigitalActual,
        int $setorKkKeKbActual,
        ?int $keuanganRekeningId,
        int $ledgerUserId,
    ): void {
        if ($row->ledgers()->exists()) {
            return;
        }

        $ownership = $this->ledgerOwnershipFromSetoran($row);
        $sid = (int) $row->id;
        $tgl = $row->tanggal_setor instanceof Carbon
            ? $row->tanggal_setor->toDateString()
            : Carbon::parse($row->tanggal_setor)->toDateString();

        $ledgerHasRekeningCol = Schema::hasColumn('speedshop_finance_ledgers', 'speedshop_keuangan_rekening_id');

        if ($setorTunaiActual > 0) {
            $tunaiKeluar = array_merge($ownership, [
                'tanggal' => $tgl,
                'pocket' => self::POCKET_LACI,
                'amount' => -$setorTunaiActual,
                'reason' => 'setoran_tunai_keluar',
                'invoice_id' => null,
                'setoran_id' => $sid,
                'user_id' => $ledgerUserId,
            ]);
            $tunaiMasuk = array_merge($ownership, [
                'tanggal' => $tgl,
                'pocket' => self::POCKET_REKENING,
                'amount' => $setorTunaiActual,
                'reason' => 'setoran_tunai_masuk_rekening',
                'invoice_id' => null,
                'setoran_id' => $sid,
                'user_id' => $ledgerUserId,
            ]);
            if ($ledgerHasRekeningCol) {
                $tunaiKeluar['speedshop_keuangan_rekening_id'] = null;
                $tunaiMasuk['speedshop_keuangan_rekening_id'] = $keuanganRekeningId;
            }
            SpeedshopFinanceLedger::query()->create($tunaiKeluar);
            SpeedshopFinanceLedger::query()->create($tunaiMasuk);
        }

        if ($setorDigitalActual > 0) {
            $digitalKeluar = array_merge($ownership, [
                'tanggal' => $tgl,
                'pocket' => self::POCKET_CASH_BESAR,
                'amount' => -$setorDigitalActual,
                'reason' => 'setoran_digital_keluar',
                'invoice_id' => null,
                'setoran_id' => $sid,
                'user_id' => $ledgerUserId,
            ]);
            $digitalMasuk = array_merge($ownership, [
                'tanggal' => $tgl,
                'pocket' => self::POCKET_REKENING,
                'amount' => $setorDigitalActual,
                'reason' => 'setoran_digital_masuk_rekening',
                'invoice_id' => null,
                'setoran_id' => $sid,
                'user_id' => $ledgerUserId,
            ]);
            $kasBesarLedgerId = null;
            if (Schema::hasColumn('speedshop_finance_setorans', 'speedshop_keuangan_kas_besar_id') && $row->speedshop_keuangan_kas_besar_id !== null) {
                $kasBesarLedgerId = (int) $row->speedshop_keuangan_kas_besar_id;
            }
            if ($ledgerHasRekeningCol) {
                $digitalKeluar['speedshop_keuangan_rekening_id'] = $kasBesarLedgerId;
                $digitalMasuk['speedshop_keuangan_rekening_id'] = $keuanganRekeningId;
            }
            SpeedshopFinanceLedger::query()->create($digitalKeluar);
            SpeedshopFinanceLedger::query()->create($digitalMasuk);
        }

        $setorKkKeKbActual = max(0, $setorKkKeKbActual);
        if ($setorKkKeKbActual > 0) {
            $kasBesarLedgerId = null;
            if (Schema::hasColumn('speedshop_finance_setorans', 'speedshop_keuangan_kas_besar_id') && $row->speedshop_keuangan_kas_besar_id !== null) {
                $kasBesarLedgerId = (int) $row->speedshop_keuangan_kas_besar_id;
            }
            $kkKeluar = array_merge($ownership, [
                'tanggal' => $tgl,
                'pocket' => self::POCKET_LACI,
                'amount' => -$setorKkKeKbActual,
                'reason' => 'setoran_kk_ke_kas_besar_keluar',
                'invoice_id' => null,
                'setoran_id' => $sid,
                'user_id' => $ledgerUserId,
            ]);
            $kkMasukKb = array_merge($ownership, [
                'tanggal' => $tgl,
                'pocket' => self::POCKET_CASH_BESAR,
                'amount' => $setorKkKeKbActual,
                'reason' => 'setoran_kk_ke_kas_besar_masuk',
                'invoice_id' => null,
                'setoran_id' => $sid,
                'user_id' => $ledgerUserId,
            ]);
            if ($ledgerHasRekeningCol) {
                $kkKeluar['speedshop_keuangan_rekening_id'] = null;
                $kkMasukKb['speedshop_keuangan_rekening_id'] = $kasBesarLedgerId;
            }
            SpeedshopFinanceLedger::query()->create($kkKeluar);
            SpeedshopFinanceLedger::query()->create($kkMasukKb);
        }
    }

    /** @return array<string, int|null> */
    private function ledgerOwnershipFromSetoran(SpeedshopFinanceSetoran $row): array
    {
        if (Schema::hasColumn('speedshop_finance_setorans', 'warehouse_id')
            && (int) ($row->warehouse_id ?? 0) > 0) {
            return [
                'warehouse_id' => (int) $row->warehouse_id,
                'speedshop_id' => null,
            ];
        }

        return [
            'speedshop_id' => (int) $row->speedshop_id,
            'warehouse_id' => null,
        ];
    }

    public function updateModalLaciPerHari(int $speedshopId, int $modal): SpeedshopFinanceSetting
    {
        return $this->updateModalLaciPerHariForScope(KeuanganScope::forSpeedshop($speedshopId), $modal);
    }

    public function updateModalLaciPerHariForScope(KeuanganScope $scope, int $modal): SpeedshopFinanceSetting
    {
        $row = $this->ensureSettingsForScope($scope);
        $row->update(['modal_laci_per_hari' => max(0, $modal)]);

        return $row->fresh();
    }

    /**
     * Catat penambahan saldo Kas Kecil (uang tunai masuk ke laci) sebagai satu baris jurnal pocket {@see self::POCKET_LACI}.
     */
    public function recordPermintaanKasKecil(int $speedshopId, string $tanggalYmd, int $nominal, int $userId): SpeedshopFinanceLedger
    {
        return $this->recordPermintaanKasKecilForScope(
            KeuanganScope::forSpeedshop($speedshopId),
            $tanggalYmd,
            $nominal,
            $userId,
        );
    }

    public function recordPermintaanKasKecilForScope(
        KeuanganScope $scope,
        string $tanggalYmd,
        int $nominal,
        int $userId,
    ): SpeedshopFinanceLedger {
        if ($nominal <= 0) {
            throw new \InvalidArgumentException('Nominal permintaan harus lebih dari Rp 0.');
        }

        $this->ensureSettingsForScope($scope);

        $attrs = array_merge($this->ledgerOwnershipForScope($scope), [
            'tanggal' => $tanggalYmd,
            'pocket' => self::POCKET_LACI,
            'amount' => $nominal,
            'reason' => self::REASON_PERMINTAAN_KAS_KECIL,
            'invoice_id' => null,
            'setoran_id' => null,
            'user_id' => $userId,
        ]);
        if (Schema::hasColumn('speedshop_finance_ledgers', 'speedshop_keuangan_rekening_id')) {
            $attrs['speedshop_keuangan_rekening_id'] = null;
        }

        return DB::transaction(fn () => SpeedshopFinanceLedger::query()->create($attrs));
    }

    /**
     * Transfer saldo dari Rekening Reguler (pocket cash_besar) ke Kas Tunai / laci — dua baris jurnal sekaligus.
     *
     * @return array{laci: SpeedshopFinanceLedger, keluar: SpeedshopFinanceLedger}
     */
    public function recordKasTunaiDariRekeningRegulerForScope(
        KeuanganScope $scope,
        SpeedshopKeuanganRekening $rekening,
        string $tanggalYmd,
        int $nominal,
        int $userId,
    ): array {
        if ($nominal <= 0) {
            throw new \InvalidArgumentException('Nominal harus lebih dari Rp 0.');
        }
        if (! $scope->ownsRekening($rekening)) {
            throw new \InvalidArgumentException('Rekening tidak termasuk scope keuangan aktif.');
        }
        if (! SpeedshopKeuanganRekening::isSecondaryRekeningJenis($rekening->jenis ?? null)) {
            throw new \InvalidArgumentException('Hanya Rekening Reguler yang dapat digunakan sebagai sumber kas tunai.');
        }
        if (! Schema::hasColumn('speedshop_finance_ledgers', 'speedshop_keuangan_rekening_id')) {
            throw new \InvalidArgumentException('Kolom alokasi rekening pada jurnal belum tersedia. Jalankan migrasi.');
        }

        $this->ensureSettingsForScope($scope);
        $rekId = (int) $rekening->id;
        // Cek saldo saat ini (bukan per tanggal jurnal) agar selaras dengan tampilan form & realitas kas.
        $saldoReguler = $this->balanceForBankPocketAsOfScope(
            $scope,
            self::POCKET_CASH_BESAR,
            $rekId,
            Carbon::today()->toDateString(),
        );
        if ($saldoReguler < $nominal) {
            throw new \InvalidArgumentException(
                'Saldo Rekening Reguler saat ini tidak mencukupi (tersedia Rp '.number_format($saldoReguler, 0, ',', '.').').',
            );
        }

        return DB::transaction(function () use ($scope, $tanggalYmd, $nominal, $userId, $rekId) {
            $keluar = SpeedshopFinanceLedger::query()->create(array_merge($this->ledgerOwnershipForScope($scope), [
                'tanggal' => $tanggalYmd,
                'pocket' => self::POCKET_CASH_BESAR,
                'amount' => -$nominal,
                'reason' => self::REASON_KAS_TUNAI_DARI_REGULER_KELUAR,
                'invoice_id' => null,
                'setoran_id' => null,
                'speedshop_keuangan_rekening_id' => $rekId,
                'user_id' => $userId,
            ]));
            $laci = SpeedshopFinanceLedger::query()->create(array_merge($this->ledgerOwnershipForScope($scope), [
                'tanggal' => $tanggalYmd,
                'pocket' => self::POCKET_LACI,
                'amount' => $nominal,
                'reason' => self::REASON_KAS_TUNAI_DARI_REGULER_MASUK,
                'invoice_id' => null,
                'setoran_id' => null,
                'speedshop_keuangan_rekening_id' => null,
                'user_id' => $userId,
            ]));

            return ['laci' => $laci, 'keluar' => $keluar];
        });
    }

    /**
     * Tambah satu baris ledger agar saldo buku (per rekening & pocket) pada tanggal cutoff menjadi {@see $targetSaldo}.
     *
     * @return array{current: int, target: int, delta: int, pocket: string}
     */
    public function applyManagerBankBookSaldoKoreksi(
        int $speedshopId,
        SpeedshopKeuanganRekening $rekening,
        string $effectiveDateYmd,
        int $targetSaldo,
        int $userId,
    ): array {
        return $this->applyManagerBankBookSaldoKoreksiForScope(
            KeuanganScope::forSpeedshop($speedshopId),
            $rekening,
            $effectiveDateYmd,
            $targetSaldo,
            $userId,
        );
    }

    public function applyManagerBankBookSaldoKoreksiForScope(
        KeuanganScope $scope,
        SpeedshopKeuanganRekening $rekening,
        string $effectiveDateYmd,
        int $targetSaldo,
        int $userId,
    ): array {
        if (! Schema::hasColumn('speedshop_finance_ledgers', 'speedshop_keuangan_rekening_id')) {
            throw new \InvalidArgumentException('Kolom alokasi rekening pada jurnal belum tersedia. Jalankan migrasi.');
        }
        if (! $scope->ownsRekening($rekening)) {
            throw new \InvalidArgumentException('Rekening tidak termasuk scope keuangan aktif.');
        }

        $jenis = Schema::hasColumn('speedshop_keuangan_rekenings', 'jenis')
            ? ($rekening->jenis ?? SpeedshopKeuanganRekening::JENIS_REKENING_UTAMA)
            : SpeedshopKeuanganRekening::JENIS_REKENING_UTAMA;
        $pocket = SpeedshopKeuanganRekening::isSecondaryRekeningJenis($jenis)
            ? self::POCKET_CASH_BESAR
            : self::POCKET_REKENING;

        $rekId = (int) $rekening->id;

        return DB::transaction(function () use ($scope, $effectiveDateYmd, $targetSaldo, $userId, $pocket, $rekId) {
            $current = $this->balanceForBankPocketAsOfScope($scope, $pocket, $rekId, $effectiveDateYmd);
            $delta = $targetSaldo - $current;
            if ($delta !== 0) {
                SpeedshopFinanceLedger::query()->create(array_merge($this->ledgerOwnershipForScope($scope), [
                    'tanggal' => $effectiveDateYmd,
                    'pocket' => $pocket,
                    'amount' => $delta,
                    'reason' => self::REASON_SALDO_KOREKSI_MANAGER,
                    'invoice_id' => null,
                    'setoran_id' => null,
                    'speedshop_keuangan_rekening_id' => $rekId,
                    'user_id' => $userId,
                ]));
            }

            return [
                'current' => $current,
                'target' => $targetSaldo,
                'delta' => $delta,
                'pocket' => $pocket,
            ];
        });
    }

    /**
     * Catat pengeluaran Kas Kecil (pocket laci) untuk biaya operasional yang sudah disetujui.
     * Idempoten per entri bila kolom {@see SpeedshopFinanceLedger} `speedshop_rincian_biaya_entry_id` ada.
     */
    public function recordBiayaOperasionalKasKecilKeluar(
        int $speedshopId,
        string $tanggalYmd,
        int $nominal,
        int $userId,
        int $rincianBiayaEntryId,
    ): SpeedshopFinanceLedger {
        return $this->recordBiayaOperasionalKasKecilKeluarForScope(
            KeuanganScope::forSpeedshop($speedshopId),
            $tanggalYmd,
            $nominal,
            $userId,
            $rincianBiayaEntryId,
        );
    }

    public function recordBiayaOperasionalKasKecilKeluarForScope(
        KeuanganScope $scope,
        string $tanggalYmd,
        int $nominal,
        int $userId,
        int $rincianBiayaEntryId,
    ): SpeedshopFinanceLedger {
        if ($nominal <= 0) {
            throw new \InvalidArgumentException('Nominal biaya harus lebih dari Rp 0.');
        }

        $this->ensureSettingsForScope($scope);

        if (Schema::hasColumn('speedshop_finance_ledgers', 'speedshop_rincian_biaya_entry_id')) {
            $existing = SpeedshopFinanceLedger::query()
                ->where('speedshop_rincian_biaya_entry_id', $rincianBiayaEntryId)
                ->first();
            if ($existing) {
                return $existing;
            }
        }

        $attrs = array_merge($this->ledgerOwnershipForScope($scope), [
            'tanggal' => $tanggalYmd,
            'pocket' => self::POCKET_LACI,
            'amount' => -$nominal,
            'reason' => self::REASON_BIAYA_OPERASIONAL_KAS_KECIL,
            'invoice_id' => null,
            'setoran_id' => null,
            'user_id' => $userId,
        ]);
        if (Schema::hasColumn('speedshop_finance_ledgers', 'speedshop_keuangan_rekening_id')) {
            $attrs['speedshop_keuangan_rekening_id'] = null;
        }
        if (Schema::hasColumn('speedshop_finance_ledgers', 'speedshop_rincian_biaya_entry_id')) {
            $attrs['speedshop_rincian_biaya_entry_id'] = $rincianBiayaEntryId;
        }

        return SpeedshopFinanceLedger::query()->create($attrs);
    }

    /**
     * Catat pengeluaran biaya operasional dari saldo buku per rekening (Kas Besar atau Rekening Utama).
     * Idempoten per entri bila kolom {@see SpeedshopFinanceLedger} `speedshop_rincian_biaya_entry_id` ada.
     *
     * @throws \InvalidArgumentException Jika saldo tidak mencukupi atau rekening tidak konsisten.
     */
    public function recordBiayaOperasionalDariRekening(
        int $speedshopId,
        string $tanggalYmd,
        int $nominal,
        int $userId,
        int $rincianBiayaEntryId,
        SpeedshopKeuanganRekening $rekening,
    ): SpeedshopFinanceLedger {
        return $this->recordBiayaOperasionalDariRekeningForScope(
            KeuanganScope::forSpeedshop($speedshopId),
            $tanggalYmd,
            $nominal,
            $userId,
            $rincianBiayaEntryId,
            $rekening,
        );
    }

    public function recordBiayaOperasionalDariRekeningForScope(
        KeuanganScope $scope,
        string $tanggalYmd,
        int $nominal,
        int $userId,
        int $rincianBiayaEntryId,
        SpeedshopKeuanganRekening $rekening,
    ): SpeedshopFinanceLedger {
        if ($nominal <= 0) {
            throw new \InvalidArgumentException('Nominal biaya harus lebih dari Rp 0.');
        }
        if (! $scope->ownsRekening($rekening)) {
            throw new \InvalidArgumentException('Rekening tidak termasuk scope keuangan aktif.');
        }
        if (! Schema::hasColumn('speedshop_finance_ledgers', 'speedshop_keuangan_rekening_id')) {
            throw new \InvalidArgumentException('Jurnal per rekening belum didukung. Jalankan migrasi keuangan.');
        }

        $this->ensureSettingsForScope($scope);

        if (Schema::hasColumn('speedshop_finance_ledgers', 'speedshop_rincian_biaya_entry_id')) {
            $existing = SpeedshopFinanceLedger::query()
                ->where('speedshop_rincian_biaya_entry_id', $rincianBiayaEntryId)
                ->where('reason', self::REASON_BIAYA_OPERASIONAL_REKENING)
                ->first();
            if ($existing) {
                return $existing;
            }
        }

        $pocket = $rekening->isRekeningUtama()
            ? self::POCKET_REKENING
            : self::POCKET_CASH_BESAR;
        $rekId = (int) $rekening->id;

        // Cek saldo saat ini (bukan per tanggal transaksi) agar selaras dengan tampilan form & realitas kas.
        $saldo = $this->balanceForBankPocketAsOfScope($scope, $pocket, $rekId, Carbon::today()->toDateString());
        if ($saldo < $nominal) {
            throw new \InvalidArgumentException(
                'Saldo buku rekening saat ini tidak mencukupi (tersedia Rp '.number_format($saldo, 0, ',', '.').').'
            );
        }

        $attrs = array_merge($this->ledgerOwnershipForScope($scope), [
            'tanggal' => $tanggalYmd,
            'pocket' => $pocket,
            'amount' => -$nominal,
            'reason' => self::REASON_BIAYA_OPERASIONAL_REKENING,
            'invoice_id' => null,
            'setoran_id' => null,
            'speedshop_keuangan_rekening_id' => $rekId,
            'user_id' => $userId,
        ]);
        if (Schema::hasColumn('speedshop_finance_ledgers', 'speedshop_rincian_biaya_entry_id')) {
            $attrs['speedshop_rincian_biaya_entry_id'] = $rincianBiayaEntryId;
        }

        return SpeedshopFinanceLedger::query()->create($attrs);
    }

    /**
     * Catat alokasi masuk ke rekening tujuan dari pengajuan BOP (mis. rekening gaji karyawan).
     * Idempoten via kolom {@see SpeedshopRincianBiayaEntry} `finance_ledger_tujuan_id`.
     */
    public function recordBiayaOperasionalKeRekeningTujuanForScope(
        KeuanganScope $scope,
        string $tanggalYmd,
        int $nominal,
        int $userId,
        SpeedshopRincianBiayaEntry $entry,
        SpeedshopKeuanganRekening $rekeningTujuan,
    ): SpeedshopFinanceLedger {
        if ($nominal <= 0) {
            throw new \InvalidArgumentException('Nominal biaya harus lebih dari Rp 0.');
        }
        if (! $scope->ownsRekening($rekeningTujuan)) {
            throw new \InvalidArgumentException('Rekening tujuan tidak termasuk scope keuangan aktif.');
        }
        if (! Schema::hasColumn('speedshop_finance_ledgers', 'speedshop_keuangan_rekening_id')) {
            throw new \InvalidArgumentException('Jurnal per rekening belum didukung. Jalankan migrasi keuangan.');
        }

        $this->ensureSettingsForScope($scope);

        if (Schema::hasColumn('speedshop_rincian_biaya_entries', 'finance_ledger_tujuan_id')) {
            $existingId = (int) ($entry->finance_ledger_tujuan_id ?? 0);
            if ($existingId > 0) {
                $existing = SpeedshopFinanceLedger::query()->find($existingId);
                if ($existing) {
                    return $existing;
                }
            }
        }

        $pocket = $this->pocketForRekeningJenis($rekeningTujuan->jenis ?? null);
        $rekId = (int) $rekeningTujuan->id;

        $attrs = array_merge($this->ledgerOwnershipForScope($scope), [
            'tanggal' => $tanggalYmd,
            'pocket' => $pocket,
            'amount' => $nominal,
            'reason' => self::REASON_BIAYA_OPERASIONAL_REKENING_TUJUAN,
            'invoice_id' => null,
            'setoran_id' => null,
            'speedshop_keuangan_rekening_id' => $rekId,
            'user_id' => $userId,
        ]);

        return SpeedshopFinanceLedger::query()->create($attrs);
    }

    /**
     * Catat pengembalian dana BOP — saldo rekening/tunai masuk kembali setelah penyesuaian nominal.
     */
    public function recordBopPengembalianMasukForScope(
        KeuanganScope $scope,
        SpeedshopRincianBiayaEntry $entry,
        \App\Models\SpeedshopRincianBiayaAdjustment $adjustment,
        string $tanggalYmd,
        int $nominal,
        int $userId,
    ): SpeedshopFinanceLedger {
        if ($nominal <= 0) {
            throw new \InvalidArgumentException('Nominal pengembalian harus lebih dari Rp 0.');
        }
        if (! $scope->ownsRincianBiaya($entry)) {
            throw new \InvalidArgumentException('Entri biaya tidak termasuk scope keuangan aktif.');
        }

        $this->ensureSettingsForScope($scope);

        if (Schema::hasColumn('speedshop_finance_ledgers', 'speedshop_rincian_biaya_adjustment_id')) {
            $existing = SpeedshopFinanceLedger::query()
                ->where('speedshop_rincian_biaya_adjustment_id', $adjustment->id)
                ->where('reason', self::REASON_BOP_PENGEMBALIAN_MASUK)
                ->first();
            if ($existing) {
                return $existing;
            }
        }

        $sourceLedger = SpeedshopFinanceLedger::query()->find((int) ($entry->finance_ledger_id ?? 0));
        if (! $sourceLedger) {
            throw new \InvalidArgumentException('Jurnal biaya asli tidak ditemukan.');
        }

        $pocket = (string) ($sourceLedger->pocket ?? self::POCKET_LACI);
        $rekId = (int) ($sourceLedger->speedshop_keuangan_rekening_id ?? 0);

        $attrs = array_merge($this->ledgerOwnershipForScope($scope), [
            'tanggal' => $tanggalYmd,
            'pocket' => $pocket,
            'amount' => $nominal,
            'reason' => self::REASON_BOP_PENGEMBALIAN_MASUK,
            'invoice_id' => null,
            'setoran_id' => null,
            'user_id' => $userId,
        ]);

        if (Schema::hasColumn('speedshop_finance_ledgers', 'speedshop_keuangan_rekening_id')) {
            $attrs['speedshop_keuangan_rekening_id'] = $rekId > 0 ? $rekId : null;
        }
        // entry_id unik per BOP (jurnal keluar); pengembalian hanya di-link lewat adjustment_id.
        if (Schema::hasColumn('speedshop_finance_ledgers', 'speedshop_rincian_biaya_adjustment_id')) {
            $attrs['speedshop_rincian_biaya_adjustment_id'] = (int) $adjustment->id;
        }

        return SpeedshopFinanceLedger::query()->create($attrs);
    }

    /**
     * Catat pengeluaran rekening sumber saat hutang supplier warehouse dilunasi.
     * Idempoten per tagihan bila kolom warehouse_supplier_tagihan_id tersedia.
     */
    public function recordHutangSupplierKeluarForScope(
        KeuanganScope $scope,
        WarehouseSupplierTagihan $tagihan,
        SpeedshopKeuanganRekening $rekening,
        string $tanggalYmd,
        int $nominal,
        int $userId,
        ?WarehouseSupplierTagihanPembayaran $pembayaran = null,
    ): SpeedshopFinanceLedger {
        if (! $scope->isWarehouse() && ! $scope->isSpeedshop()) {
            throw new \InvalidArgumentException('Hutang supplier hanya untuk scope gudang atau speedshop.');
        }
        if ($nominal <= 0) {
            throw new \InvalidArgumentException('Nominal pembayaran harus lebih dari Rp 0.');
        }
        if (! $scope->ownsRekening($rekening)) {
            throw new \InvalidArgumentException('Rekening tidak termasuk scope keuangan aktif.');
        }
        $allowedJenis = $scope->isWarehouse()
            ? [
                SpeedshopKeuanganRekening::JENIS_REKENING_UTAMA,
                SpeedshopKeuanganRekening::JENIS_REKENING_REGULER,
            ]
            : [
                SpeedshopKeuanganRekening::JENIS_REKENING_UTAMA,
                SpeedshopKeuanganRekening::JENIS_KAS_BESAR,
            ];
        if (! in_array($rekening->jenis ?? '', $allowedJenis, true)) {
            throw new \InvalidArgumentException('Pilih rekening yang valid untuk pembayaran hutang.');
        }
        if (! Schema::hasColumn('speedshop_finance_ledgers', 'speedshop_keuangan_rekening_id')) {
            throw new \InvalidArgumentException('Jurnal per rekening belum didukung. Jalankan migrasi keuangan.');
        }

        $this->ensureSettingsForScope($scope);
        $rekId = (int) $rekening->id;
        $pocket = $this->pocketForRekeningJenis($rekening->jenis ?? null);
        $jenisLabel = $scope->isWarehouse()
            ? SpeedshopKeuanganRekening::warehouseJenisLabel($rekening->jenis)
            : SpeedshopKeuanganRekening::jenisLabel($rekening->jenis);
        $rekeningLabel = $jenisLabel.' — '.$rekening->nama;

        if (Schema::hasColumn('speedshop_finance_ledgers', 'warehouse_supplier_tagihan_pembayaran_id') && $pembayaran !== null) {
            $existing = SpeedshopFinanceLedger::query()
                ->where('warehouse_supplier_tagihan_pembayaran_id', $pembayaran->id)
                ->where('reason', self::REASON_HUTANG_SUPPLIER_KELUAR)
                ->first();
            if ($existing) {
                return $existing;
            }
        } elseif (Schema::hasColumn('speedshop_finance_ledgers', 'warehouse_supplier_tagihan_id')) {
            $existing = SpeedshopFinanceLedger::query()
                ->where('warehouse_supplier_tagihan_id', $tagihan->id)
                ->where('reason', self::REASON_HUTANG_SUPPLIER_KELUAR)
                ->when(
                    Schema::hasColumn('speedshop_finance_ledgers', 'warehouse_supplier_tagihan_pembayaran_id'),
                    fn ($q) => $q->whereNull('warehouse_supplier_tagihan_pembayaran_id'),
                )
                ->first();
            if ($existing) {
                return $existing;
            }
        }

        $saldo = $this->balanceForBankPocketAsOfScope($scope, $pocket, $rekId, $tanggalYmd);
        if ($saldo < $nominal) {
            throw new \InvalidArgumentException(
                'Saldo '.$rekeningLabel.' tidak mencukupi (tersedia Rp '.number_format($saldo, 0, ',', '.').').'
            );
        }

        $attrs = array_merge($this->ledgerOwnershipForScope($scope), [
            'tanggal' => $tanggalYmd,
            'pocket' => $pocket,
            'amount' => -$nominal,
            'reason' => self::REASON_HUTANG_SUPPLIER_KELUAR,
            'invoice_id' => null,
            'setoran_id' => null,
            'speedshop_keuangan_rekening_id' => $rekId,
            'user_id' => $userId,
        ]);
        if (Schema::hasColumn('speedshop_finance_ledgers', 'warehouse_supplier_tagihan_id')) {
            $attrs['warehouse_supplier_tagihan_id'] = $tagihan->id;
        }
        if (Schema::hasColumn('speedshop_finance_ledgers', 'warehouse_supplier_tagihan_pembayaran_id') && $pembayaran !== null) {
            $attrs['warehouse_supplier_tagihan_pembayaran_id'] = $pembayaran->id;
        }

        return SpeedshopFinanceLedger::query()->create($attrs);
    }

    /**
     * Catat penerimaan rekening saat tagihan reseller warehouse dibayar (cicil/lunas).
     */
    public function recordTagihanResellerMasukForScope(
        KeuanganScope $scope,
        WarehouseResellerTagihan $tagihan,
        SpeedshopKeuanganRekening $rekening,
        string $tanggalYmd,
        int $nominal,
        int $userId,
        ?WarehouseResellerTagihanPembayaran $pembayaran = null,
    ): SpeedshopFinanceLedger {
        if (! $scope->isWarehouse()) {
            throw new \InvalidArgumentException('Tagihan reseller hanya untuk scope gudang.');
        }
        if ($nominal <= 0) {
            throw new \InvalidArgumentException('Nominal pembayaran harus lebih dari Rp 0.');
        }
        if (! $scope->ownsRekening($rekening)) {
            throw new \InvalidArgumentException('Rekening tidak termasuk scope keuangan aktif.');
        }
        if (! in_array($rekening->jenis ?? '', [
            SpeedshopKeuanganRekening::JENIS_REKENING_UTAMA,
            SpeedshopKeuanganRekening::JENIS_REKENING_REGULER,
        ], true)) {
            throw new \InvalidArgumentException('Pilih Rekening Utama atau Rekening Reguler yang valid.');
        }
        if (! Schema::hasColumn('speedshop_finance_ledgers', 'speedshop_keuangan_rekening_id')) {
            throw new \InvalidArgumentException('Jurnal per rekening belum didukung. Jalankan migrasi keuangan.');
        }

        $this->ensureSettingsForScope($scope);
        $rekId = (int) $rekening->id;
        $pocket = $this->pocketForRekeningJenis($rekening->jenis ?? null);

        if (Schema::hasColumn('speedshop_finance_ledgers', 'warehouse_reseller_tagihan_pembayaran_id') && $pembayaran !== null) {
            $existing = SpeedshopFinanceLedger::query()
                ->where('warehouse_reseller_tagihan_pembayaran_id', $pembayaran->id)
                ->where('reason', self::REASON_TAGIHAN_RESELLER_MASUK)
                ->first();
            if ($existing) {
                return $existing;
            }
        }

        $attrs = array_merge($this->ledgerOwnershipForScope($scope), [
            'tanggal' => $tanggalYmd,
            'pocket' => $pocket,
            'amount' => $nominal,
            'reason' => self::REASON_TAGIHAN_RESELLER_MASUK,
            'invoice_id' => null,
            'setoran_id' => null,
            'speedshop_keuangan_rekening_id' => $rekId,
            'user_id' => $userId,
        ]);
        if (Schema::hasColumn('speedshop_finance_ledgers', 'warehouse_reseller_tagihan_pembayaran_id') && $pembayaran !== null) {
            $attrs['warehouse_reseller_tagihan_pembayaran_id'] = $pembayaran->id;
        }

        return SpeedshopFinanceLedger::query()->create($attrs);
    }

    /**
     * Catat pengeluaran rekening saat kelebihan bayar tagihan reseller dikembalikan.
     */
    public function recordTagihanResellerKeluarForScope(
        KeuanganScope $scope,
        WarehouseResellerTagihan $tagihan,
        SpeedshopKeuanganRekening $rekening,
        string $tanggalYmd,
        int $nominal,
        int $userId,
        ?\App\Models\WarehouseResellerTagihanRefund $refund = null,
    ): SpeedshopFinanceLedger {
        if (! $scope->isWarehouse()) {
            throw new \InvalidArgumentException('Refund tagihan reseller hanya untuk scope gudang.');
        }
        if ($nominal <= 0) {
            throw new \InvalidArgumentException('Nominal refund harus lebih dari Rp 0.');
        }
        if (! $scope->ownsRekening($rekening)) {
            throw new \InvalidArgumentException('Rekening tidak termasuk scope keuangan aktif.');
        }
        if (! in_array($rekening->jenis ?? '', [
            SpeedshopKeuanganRekening::JENIS_REKENING_UTAMA,
            SpeedshopKeuanganRekening::JENIS_REKENING_REGULER,
        ], true)) {
            throw new \InvalidArgumentException('Pilih Rekening Utama atau Rekening Reguler yang valid.');
        }
        if (! Schema::hasColumn('speedshop_finance_ledgers', 'speedshop_keuangan_rekening_id')) {
            throw new \InvalidArgumentException('Jurnal per rekening belum didukung. Jalankan migrasi keuangan.');
        }

        $this->ensureSettingsForScope($scope);
        $rekId = (int) $rekening->id;
        $pocket = $this->pocketForRekeningJenis($rekening->jenis ?? null);
        $rekeningLabel = SpeedshopKeuanganRekening::warehouseJenisLabel($rekening->jenis).' — '.$rekening->nama;

        if (Schema::hasColumn('speedshop_finance_ledgers', 'warehouse_reseller_tagihan_refund_id') && $refund !== null) {
            $existing = SpeedshopFinanceLedger::query()
                ->where('warehouse_reseller_tagihan_refund_id', $refund->id)
                ->where('reason', self::REASON_TAGIHAN_RESELLER_KELUAR)
                ->first();
            if ($existing) {
                return $existing;
            }
        }

        $saldo = $this->balanceForBankPocketAsOfScope($scope, $pocket, $rekId, $tanggalYmd);
        if ($saldo < $nominal) {
            throw new \InvalidArgumentException(
                'Saldo '.$rekeningLabel.' tidak mencukupi (tersedia Rp '.number_format($saldo, 0, ',', '.').').'
            );
        }

        $attrs = array_merge($this->ledgerOwnershipForScope($scope), [
            'tanggal' => $tanggalYmd,
            'pocket' => $pocket,
            'amount' => -$nominal,
            'reason' => self::REASON_TAGIHAN_RESELLER_KELUAR,
            'invoice_id' => null,
            'setoran_id' => null,
            'speedshop_keuangan_rekening_id' => $rekId,
            'user_id' => $userId,
        ]);
        if (Schema::hasColumn('speedshop_finance_ledgers', 'warehouse_reseller_tagihan_refund_id') && $refund !== null) {
            $attrs['warehouse_reseller_tagihan_refund_id'] = $refund->id;
        }

        return SpeedshopFinanceLedger::query()->create($attrs);
    }

    /**
     * Catat penerimaan rekening saat piutang tagihan reseller dilunasi.
     */
    public function recordPiutangResellerMasukForScope(
        KeuanganScope $scope,
        \App\Models\WarehouseResellerPiutang $piutang,
        SpeedshopKeuanganRekening $rekening,
        string $tanggalYmd,
        int $nominal,
        int $userId,
        ?WarehouseResellerPiutangPembayaran $pembayaran = null,
    ): SpeedshopFinanceLedger {
        if (! $scope->isWarehouse()) {
            throw new \InvalidArgumentException('Piutang reseller hanya untuk scope gudang.');
        }
        if ($nominal <= 0) {
            throw new \InvalidArgumentException('Nominal pembayaran harus lebih dari Rp 0.');
        }
        if (! $scope->ownsRekening($rekening)) {
            throw new \InvalidArgumentException('Rekening tidak termasuk scope keuangan aktif.');
        }
        if (! in_array($rekening->jenis ?? '', [
            SpeedshopKeuanganRekening::JENIS_REKENING_UTAMA,
            SpeedshopKeuanganRekening::JENIS_REKENING_REGULER,
        ], true)) {
            throw new \InvalidArgumentException('Pilih Rekening Utama atau Rekening Reguler yang valid.');
        }
        if (! Schema::hasColumn('speedshop_finance_ledgers', 'speedshop_keuangan_rekening_id')) {
            throw new \InvalidArgumentException('Jurnal per rekening belum didukung. Jalankan migrasi keuangan.');
        }

        $this->ensureSettingsForScope($scope);
        $rekId = (int) $rekening->id;
        $pocket = $this->pocketForRekeningJenis($rekening->jenis ?? null);

        if (Schema::hasColumn('speedshop_finance_ledgers', 'warehouse_reseller_piutang_pembayaran_id') && $pembayaran !== null) {
            $existing = SpeedshopFinanceLedger::query()
                ->where('warehouse_reseller_piutang_pembayaran_id', $pembayaran->id)
                ->where('reason', self::REASON_PIUTANG_RESELLER_MASUK)
                ->first();
            if ($existing) {
                return $existing;
            }
        }

        $attrs = array_merge($this->ledgerOwnershipForScope($scope), [
            'tanggal' => $tanggalYmd,
            'pocket' => $pocket,
            'amount' => $nominal,
            'reason' => self::REASON_PIUTANG_RESELLER_MASUK,
            'invoice_id' => null,
            'setoran_id' => null,
            'speedshop_keuangan_rekening_id' => $rekId,
            'user_id' => $userId,
        ]);
        if (Schema::hasColumn('speedshop_finance_ledgers', 'warehouse_reseller_piutang_pembayaran_id') && $pembayaran !== null) {
            $attrs['warehouse_reseller_piutang_pembayaran_id'] = $pembayaran->id;
        }

        return SpeedshopFinanceLedger::query()->create($attrs);
    }

    /**
     * Catat pengeluaran Kas Tunai (laci) saat dana talang warehouse dibuat.
     */
    public function recordPiutangKeluarDariKasTunaiForScope(
        KeuanganScope $scope,
        WarehousePiutang $piutang,
        string $tanggalYmd,
        int $nominal,
        int $userId,
    ): SpeedshopFinanceLedger {
        if (! $scope->isWarehouse()) {
            throw new \InvalidArgumentException('Dana talang hanya untuk scope gudang.');
        }
        if ($nominal <= 0) {
            throw new \InvalidArgumentException('Nominal dana talang harus lebih dari Rp 0.');
        }

        $this->ensureSettingsForScope($scope);

        if (Schema::hasColumn('speedshop_finance_ledgers', 'warehouse_piutang_id')) {
            $existing = SpeedshopFinanceLedger::query()
                ->where('warehouse_piutang_id', $piutang->id)
                ->where('reason', self::REASON_PIUTANG_KELUAR)
                ->first();
            if ($existing) {
                return $existing;
            }
        }

        $saldo = $this->balancePocketAsOfScope($scope, self::POCKET_LACI, Carbon::today()->toDateString());
        if ($saldo < $nominal) {
            throw new \InvalidArgumentException(
                'Saldo Kas Tunai saat ini tidak mencukupi (tersedia Rp '.number_format($saldo, 0, ',', '.').').'
            );
        }

        $attrs = array_merge($this->ledgerOwnershipForScope($scope), [
            'tanggal' => $tanggalYmd,
            'pocket' => self::POCKET_LACI,
            'amount' => -$nominal,
            'reason' => self::REASON_PIUTANG_KELUAR,
            'invoice_id' => null,
            'setoran_id' => null,
            'speedshop_keuangan_rekening_id' => null,
            'user_id' => $userId,
        ]);
        if (Schema::hasColumn('speedshop_finance_ledgers', 'warehouse_piutang_id')) {
            $attrs['warehouse_piutang_id'] = $piutang->id;
        }

        return SpeedshopFinanceLedger::query()->create($attrs);
    }

    /**
     * Catat pengeluaran rekening sumber saat dana talang warehouse dibuat.
     * Idempoten per piutang bila kolom warehouse_piutang_id tersedia.
     */
    public function recordPiutangKeluarForScope(
        KeuanganScope $scope,
        WarehousePiutang $piutang,
        SpeedshopKeuanganRekening $rekening,
        string $tanggalYmd,
        int $nominal,
        int $userId,
    ): SpeedshopFinanceLedger {
        if (! $scope->isWarehouse()) {
            throw new \InvalidArgumentException('Dana talang hanya untuk scope gudang.');
        }
        if ($nominal <= 0) {
            throw new \InvalidArgumentException('Nominal dana talang harus lebih dari Rp 0.');
        }
        if (! $scope->ownsRekening($rekening)) {
            throw new \InvalidArgumentException('Rekening tidak termasuk scope keuangan aktif.');
        }
        if (! in_array($rekening->jenis ?? '', [
            SpeedshopKeuanganRekening::JENIS_REKENING_UTAMA,
            SpeedshopKeuanganRekening::JENIS_REKENING_REGULER,
        ], true)) {
            throw new \InvalidArgumentException('Pilih Rekening Utama atau Rekening Reguler yang valid.');
        }
        if (! Schema::hasColumn('speedshop_finance_ledgers', 'speedshop_keuangan_rekening_id')) {
            throw new \InvalidArgumentException('Jurnal per rekening belum didukung. Jalankan migrasi keuangan.');
        }

        $this->ensureSettingsForScope($scope);
        $rekId = (int) $rekening->id;
        $pocket = $this->pocketForRekeningJenis($rekening->jenis ?? null);
        $rekeningLabel = SpeedshopKeuanganRekening::warehouseJenisLabel($rekening->jenis).' — '.$rekening->nama;

        if (Schema::hasColumn('speedshop_finance_ledgers', 'warehouse_piutang_id')) {
            $existing = SpeedshopFinanceLedger::query()
                ->where('warehouse_piutang_id', $piutang->id)
                ->where('reason', self::REASON_PIUTANG_KELUAR)
                ->first();
            if ($existing) {
                return $existing;
            }
        }

        $saldo = $this->balanceForBankPocketAsOfScope($scope, $pocket, $rekId, Carbon::today()->toDateString());
        if ($saldo < $nominal) {
            throw new \InvalidArgumentException(
                'Saldo '.$rekeningLabel.' saat ini tidak mencukupi (tersedia Rp '.number_format($saldo, 0, ',', '.').').'
            );
        }

        $attrs = array_merge($this->ledgerOwnershipForScope($scope), [
            'tanggal' => $tanggalYmd,
            'pocket' => $pocket,
            'amount' => -$nominal,
            'reason' => self::REASON_PIUTANG_KELUAR,
            'invoice_id' => null,
            'setoran_id' => null,
            'speedshop_keuangan_rekening_id' => $rekId,
            'user_id' => $userId,
        ]);
        if (Schema::hasColumn('speedshop_finance_ledgers', 'warehouse_piutang_id')) {
            $attrs['warehouse_piutang_id'] = $piutang->id;
        }

        return SpeedshopFinanceLedger::query()->create($attrs);
    }

    /**
     * Catat penerimaan kembali ke rekening sumber saat dana talang warehouse dilunasi.
     * Idempoten per piutang bila kolom warehouse_piutang_id tersedia.
     */
    public function recordPiutangMasukForScope(
        KeuanganScope $scope,
        WarehousePiutang $piutang,
        string $tanggalYmd,
        int $nominal,
        int $userId,
    ): SpeedshopFinanceLedger {
        if (! $scope->isWarehouse()) {
            throw new \InvalidArgumentException('Dana talang hanya untuk scope gudang.');
        }
        if ($nominal <= 0) {
            throw new \InvalidArgumentException('Nominal pelunasan harus lebih dari Rp 0.');
        }

        $this->ensureSettingsForScope($scope);

        if (! $piutang->speedshop_keuangan_rekening_id) {
            if (Schema::hasColumn('speedshop_finance_ledgers', 'warehouse_piutang_id')) {
                $existing = SpeedshopFinanceLedger::query()
                    ->where('warehouse_piutang_id', $piutang->id)
                    ->where('reason', self::REASON_PIUTANG_MASUK)
                    ->first();
                if ($existing) {
                    return $existing;
                }
            }

            $attrs = array_merge($this->ledgerOwnershipForScope($scope), [
                'tanggal' => $tanggalYmd,
                'pocket' => self::POCKET_LACI,
                'amount' => $nominal,
                'reason' => self::REASON_PIUTANG_MASUK,
                'invoice_id' => null,
                'setoran_id' => null,
                'speedshop_keuangan_rekening_id' => null,
                'user_id' => $userId,
            ]);
            if (Schema::hasColumn('speedshop_finance_ledgers', 'warehouse_piutang_id')) {
                $attrs['warehouse_piutang_id'] = $piutang->id;
            }

            return SpeedshopFinanceLedger::query()->create($attrs);
        }

        if (! Schema::hasColumn('speedshop_finance_ledgers', 'speedshop_keuangan_rekening_id')) {
            throw new \InvalidArgumentException('Jurnal per rekening belum didukung. Jalankan migrasi keuangan.');
        }

        $rekId = (int) $piutang->speedshop_keuangan_rekening_id;
        $rekening = $scope->applyToRekening(SpeedshopKeuanganRekening::query())
            ->whereKey($rekId)
            ->first();
        if (! $rekening) {
            throw new \InvalidArgumentException('Rekening sumber dana talang tidak ditemukan.');
        }
        $pocket = $this->pocketForRekeningJenis($rekening->jenis ?? null);

        if (Schema::hasColumn('speedshop_finance_ledgers', 'warehouse_piutang_id')) {
            $existing = SpeedshopFinanceLedger::query()
                ->where('warehouse_piutang_id', $piutang->id)
                ->where('reason', self::REASON_PIUTANG_MASUK)
                ->first();
            if ($existing) {
                return $existing;
            }
        }

        $attrs = array_merge($this->ledgerOwnershipForScope($scope), [
            'tanggal' => $tanggalYmd,
            'pocket' => $pocket,
            'amount' => $nominal,
            'reason' => self::REASON_PIUTANG_MASUK,
            'invoice_id' => null,
            'setoran_id' => null,
            'speedshop_keuangan_rekening_id' => $rekId,
            'user_id' => $userId,
        ]);
        if (Schema::hasColumn('speedshop_finance_ledgers', 'warehouse_piutang_id')) {
            $attrs['warehouse_piutang_id'] = $piutang->id;
        }

        return SpeedshopFinanceLedger::query()->create($attrs);
    }

    public function voidPiutangLedgersForScope(KeuanganScope $scope, WarehousePiutang $piutang, ?string $reason = null): void
    {
        if (! Schema::hasColumn('speedshop_finance_ledgers', 'warehouse_piutang_id')) {
            return;
        }

        $query = $scope->applyToLedger(SpeedshopFinanceLedger::query())
            ->where('warehouse_piutang_id', $piutang->id);

        if ($reason !== null) {
            $query->where('reason', $reason);
        } else {
            $query->whereIn('reason', [self::REASON_PIUTANG_KELUAR, self::REASON_PIUTANG_MASUK]);
        }

        $query->delete();
    }

    /**
     * Transfer pemasukan antar rekening/tunai warehouse (dua baris jurnal).
     *
     * @return array{keluar: SpeedshopFinanceLedger, masuk: SpeedshopFinanceLedger}
     */
    public function recordPemasukanTransferForScope(
        KeuanganScope $scope,
        WarehousePemasukan $pemasukan,
        string $sumberValue,
        string $tujuanValue,
        string $tanggalYmd,
        int $nominal,
        int $userId,
    ): array {
        if (! $scope->isWarehouse()) {
            throw new \InvalidArgumentException('Pemasukan hanya untuk scope gudang.');
        }
        if ($nominal <= 0) {
            throw new \InvalidArgumentException('Nominal pemasukan harus lebih dari Rp 0.');
        }
        if ($sumberValue === $tujuanValue) {
            throw new \InvalidArgumentException('Rekening sumber dan tujuan tidak boleh sama.');
        }
        if (! Schema::hasColumn('speedshop_finance_ledgers', 'warehouse_pemasukan_id')) {
            throw new \InvalidArgumentException('Jurnal pemasukan belum didukung. Jalankan migrasi.');
        }

        $this->ensureSettingsForScope($scope);

        $sumber = $this->resolveWarehouseRekeningEndpoint($scope, $sumberValue);
        $tujuan = $this->resolveWarehouseRekeningEndpoint($scope, $tujuanValue);
        $isPihakLain = (bool) ($sumber['is_pihak_lain'] ?? false);

        if (! $isPihakLain) {
            // Cek saldo saat ini (bukan per tanggal transaksi) agar selaras dengan tampilan form & realitas kas.
            $saldoAsOfYmd = Carbon::today()->toDateString();
            $saldoSumber = $sumber['is_tunai']
                ? $this->balancePocketAsOfScope($scope, self::POCKET_LACI, $saldoAsOfYmd)
                : $this->balanceForBankPocketAsOfScope($scope, $sumber['pocket'], (int) $sumber['rekening_id'], $saldoAsOfYmd);

            if ($saldoSumber < $nominal) {
                throw new \InvalidArgumentException(
                    'Saldo sumber saat ini tidak mencukupi (tersedia Rp '.number_format($saldoSumber, 0, ',', '.').').'
                );
            }
        }

        return DB::transaction(function () use ($scope, $pemasukan, $sumber, $tujuan, $isPihakLain, $tanggalYmd, $nominal, $userId) {
            $keluar = null;
            if (! $isPihakLain) {
                $keluarAttrs = array_merge($this->ledgerOwnershipForScope($scope), [
                    'tanggal' => $tanggalYmd,
                    'pocket' => $sumber['pocket'],
                    'amount' => -$nominal,
                    'reason' => self::REASON_PEMASUKAN_KELUAR,
                    'invoice_id' => null,
                    'setoran_id' => null,
                    'speedshop_keuangan_rekening_id' => $sumber['rekening_id'],
                    'warehouse_pemasukan_id' => $pemasukan->id,
                    'user_id' => $userId,
                ]);
                $keluar = SpeedshopFinanceLedger::query()->create($keluarAttrs);
            }
            $masukAttrs = array_merge($this->ledgerOwnershipForScope($scope), [
                'tanggal' => $tanggalYmd,
                'pocket' => $tujuan['pocket'],
                'amount' => $nominal,
                'reason' => self::REASON_PEMASUKAN_MASUK,
                'invoice_id' => null,
                'setoran_id' => null,
                'speedshop_keuangan_rekening_id' => $tujuan['rekening_id'],
                'warehouse_pemasukan_id' => $pemasukan->id,
                'user_id' => $userId,
            ]);

            $masuk = SpeedshopFinanceLedger::query()->create($masukAttrs);

            return ['keluar' => $keluar, 'masuk' => $masuk];
        });
    }

    public function voidPemasukanLedgersForScope(KeuanganScope $scope, WarehousePemasukan $pemasukan): void
    {
        if (! Schema::hasColumn('speedshop_finance_ledgers', 'warehouse_pemasukan_id')) {
            return;
        }

        $scope->applyToLedger(SpeedshopFinanceLedger::query())
            ->where('warehouse_pemasukan_id', $pemasukan->id)
            ->whereIn('reason', [self::REASON_PEMASUKAN_KELUAR, self::REASON_PEMASUKAN_MASUK])
            ->delete();
    }

    /**
     * @return array{pocket: ?string, rekening_id: ?int, is_tunai: bool, is_pihak_lain: bool, tipe: string}
     */
    public function resolveWarehouseRekeningEndpoint(KeuanganScope $scope, string $value): array
    {
        if ($value === SpeedshopKeuanganRekening::SUMBER_TUNAI_VALUE) {
            return [
                'pocket' => self::POCKET_LACI,
                'rekening_id' => null,
                'is_tunai' => true,
                'is_pihak_lain' => false,
                'tipe' => WarehousePemasukan::TIPE_TUNAI,
            ];
        }

        if ($value === SpeedshopKeuanganRekening::SUMBER_PIHAK_LAIN_VALUE) {
            return [
                'pocket' => null,
                'rekening_id' => null,
                'is_tunai' => false,
                'is_pihak_lain' => true,
                'tipe' => WarehousePemasukan::TIPE_PIHAK_LAIN,
            ];
        }

        if (! is_numeric($value) || (int) $value < 1) {
            throw new \InvalidArgumentException('Rekening tidak valid.');
        }

        $rekening = $scope->applyToRekening(SpeedshopKeuanganRekening::query())
            ->whereKey((int) $value)
            ->first();

        if (! $rekening) {
            throw new \InvalidArgumentException('Rekening tidak ditemukan pada gudang ini.');
        }

        return [
            'pocket' => $this->pocketForRekeningJenis($rekening->jenis ?? null),
            'rekening_id' => (int) $rekening->id,
            'is_tunai' => false,
            'is_pihak_lain' => false,
            'tipe' => WarehousePemasukan::TIPE_REKENING,
        ];
    }

    public function submitSetoranForScope(
        KeuanganScope $scope,
        string $tanggalSetorYmd,
        int $setorTunaiActual,
        int $setorDigitalActual,
        ?string $catatan = null,
        ?int $keuanganRekeningId = null,
        ?int $kasBesarRekeningId = null,
        ?string $buktiKasKecilPath = null,
        ?string $buktiKasBesarPath = null,
        bool $pendingApproval = false,
        int $setorKkKeKbActual = 0,
    ): SpeedshopFinanceSetoran {
        if ($scope->isSpeedshop()) {
            return $this->submitSetoran(
                (int) $scope->speedshopId,
                $tanggalSetorYmd,
                $setorTunaiActual,
                $setorDigitalActual,
                $catatan,
                $keuanganRekeningId,
                $kasBesarRekeningId,
                $buktiKasKecilPath,
                $buktiKasBesarPath,
                $pendingApproval,
                $setorKkKeKbActual,
            );
        }

        return DB::transaction(function () use (
            $scope,
            $tanggalSetorYmd,
            $setorTunaiActual,
            $setorDigitalActual,
            $catatan,
            $keuanganRekeningId,
            $kasBesarRekeningId,
            $buktiKasKecilPath,
            $buktiKasBesarPath,
            $pendingApproval,
            $setorKkKeKbActual,
        ) {
            $snap = $this->validateSetoranSubmissionForScope(
                $scope,
                $tanggalSetorYmd,
                $setorTunaiActual,
                $setorDigitalActual,
                $keuanganRekeningId,
                $kasBesarRekeningId,
                $setorKkKeKbActual,
            );

            $uid = (int) auth()->id();

            $setoranAttrs = array_merge($scope->ownershipAttributes(), [
                'tanggal_setor' => $tanggalSetorYmd,
                'pendapatan_tunai_hari' => $snap['pendapatan_tunai'],
                'pendapatan_digital_hari' => $snap['pendapatan_digital'],
                'modal_laci_snapshot' => $snap['modal_laci'],
                'wajib_setor_tunai' => $snap['wajib_setor_tunai'],
                'wajib_setor_digital' => $snap['wajib_setor_digital'],
                'setor_tunai_actual' => $setorTunaiActual,
                'setor_digital_actual' => $setorDigitalActual,
                'status' => $pendingApproval ? 'pending' : 'completed',
                'completed_at' => $pendingApproval ? null : now(),
                'user_id' => $uid,
                'catatan' => $catatan,
            ]);
            if (Schema::hasColumn('speedshop_finance_setorans', 'submitted_by')) {
                $setoranAttrs['submitted_by'] = SpeedshopFinanceSetoran::SUBMITTED_BY_ACCOUNTING;
            }
            if (! $pendingApproval && Schema::hasColumn('speedshop_finance_setorans', 'approved_by')) {
                $setoranAttrs['approved_by'] = $uid;
                $setoranAttrs['approved_at'] = now();
            }
            if (Schema::hasColumn('speedshop_finance_setorans', 'speedshop_keuangan_rekening_id')) {
                $setoranAttrs['speedshop_keuangan_rekening_id'] = $keuanganRekeningId;
            }
            if (Schema::hasColumn('speedshop_finance_setorans', 'speedshop_keuangan_kas_besar_id')) {
                $setoranAttrs['speedshop_keuangan_kas_besar_id'] = $kasBesarRekeningId;
            }
            if ($buktiKasKecilPath !== null && Schema::hasColumn('speedshop_finance_setorans', 'bukti_kas_kecil_path')) {
                $setoranAttrs['bukti_kas_kecil_path'] = $buktiKasKecilPath;
            }
            if ($buktiKasBesarPath !== null && Schema::hasColumn('speedshop_finance_setorans', 'bukti_kas_besar_path')) {
                $setoranAttrs['bukti_kas_besar_path'] = $buktiKasBesarPath;
            }
            if (Schema::hasColumn('speedshop_finance_setorans', 'setor_kk_ke_kb_actual')) {
                $setoranAttrs['setor_kk_ke_kb_actual'] = max(0, $setorKkKeKbActual);
            }

            $row = SpeedshopFinanceSetoran::query()->create($setoranAttrs);

            if (! $pendingApproval && ! Schema::hasTable('speedshop_finance_setoran_items')) {
                $this->postSetoranLedgers(
                    $row->fresh(),
                    $setorTunaiActual,
                    $setorDigitalActual,
                    $setorKkKeKbActual,
                    $keuanganRekeningId,
                    $uid,
                );
            }

            return $row->fresh();
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function validateSetoranSubmissionForScope(
        KeuanganScope $scope,
        string $tanggalSetorYmd,
        int $setorTunaiActual,
        int $setorDigitalActual,
        ?int $keuanganRekeningId,
        ?int $kasBesarRekeningId = null,
        int $setorKkKeKbActual = 0,
    ): array {
        $this->ensureSettingsForScope($scope);

        if ($keuanganRekeningId !== null) {
            $q = $scope->applyToRekening(SpeedshopKeuanganRekening::query())
                ->where('id', $keuanganRekeningId);
            if (Schema::hasColumn('speedshop_keuangan_rekenings', 'jenis')) {
                $q->where('jenis', SpeedshopKeuanganRekening::JENIS_REKENING_UTAMA);
            }
            if (! $q->exists()) {
                throw new \InvalidArgumentException('Rekening tujuan setoran harus berjenis Rekening Utama dan valid untuk gudang ini.');
            }
        }

        $fkKbCol = Schema::hasColumn('speedshop_finance_setorans', 'speedshop_keuangan_kas_besar_id');
        $cabangHasRekening = $scope->applyToRekening(SpeedshopKeuanganRekening::query())->exists();
        $kbDedicated = Schema::hasColumn('speedshop_keuangan_rekenings', 'jenis')
            && $scope->applyToRekening(SpeedshopKeuanganRekening::query())
                ->whereIn('jenis', SpeedshopKeuanganRekening::secondaryJenisValues())
                ->exists();

        if ($setorDigitalActual > 0 && $fkKbCol && $cabangHasRekening && $kasBesarRekeningId === null) {
            throw new \InvalidArgumentException('Pilih rekening untuk sumber setoran digital.');
        }

        $setorKkKeKbActual = max(0, $setorKkKeKbActual);
        if ($setorKkKeKbActual > 0 && $fkKbCol && $cabangHasRekening && $kasBesarRekeningId === null) {
            throw new \InvalidArgumentException('Pilih rekening Kas Besar untuk pindah saldo dari Kas Kecil.');
        }

        if ($kasBesarRekeningId !== null) {
            $qKb = $scope->applyToRekening(SpeedshopKeuanganRekening::query())
                ->where('id', $kasBesarRekeningId);
            if (Schema::hasColumn('speedshop_keuangan_rekenings', 'jenis')) {
                if ($kbDedicated) {
                    $qKb->whereIn('jenis', SpeedshopKeuanganRekening::secondaryJenisValues());
                } else {
                    $qKb->where('jenis', SpeedshopKeuanganRekening::JENIS_REKENING_UTAMA);
                }
            }
            if (! $qKb->exists()) {
                throw new \InvalidArgumentException('Rekening sumber Kas Besar tidak valid untuk gudang ini.');
            }
        }

        $snap = $this->computeDaySetoranSnapshotForScope($scope, $tanggalSetorYmd);

        $saldoLaci = (int) $snap['saldo_kas_kecil'];
        $saldoBesar = (int) $snap['saldo_kas_besar'];

        if ($setorTunaiActual < 0 || $setorDigitalActual < 0) {
            throw new \InvalidArgumentException('Nominal setoran tidak boleh negatif.');
        }

        $totalKeluarLaci = $setorTunaiActual + $setorKkKeKbActual;
        if ($totalKeluarLaci > $saldoLaci) {
            throw new \InvalidArgumentException(
                'Total keluar Kas Kecil melebihi saldo buku (Rp '.number_format($saldoLaci, 0, ',', '.').').'
            );
        }
        if ($setorTunaiActual > $saldoLaci) {
            throw new \InvalidArgumentException(
                'Setor tunai (Kas Kecil) melebihi saldo buku tanggal tersebut (Rp '.number_format($saldoLaci, 0, ',', '.').').'
            );
        }
        if ($setorDigitalActual > $saldoBesar) {
            throw new \InvalidArgumentException(
                'Setor digital (Kas Besar) melebihi saldo buku tanggal tersebut (Rp '.number_format($saldoBesar, 0, ',', '.').').'
            );
        }

        return $snap;
    }

    public function pocketForRekeningJenis(?string $jenis): string
    {
        return SpeedshopKeuanganRekening::isSecondaryRekeningJenis($jenis ?? SpeedshopKeuanganRekening::JENIS_REKENING_UTAMA)
            ? self::POCKET_CASH_BESAR
            : self::POCKET_REKENING;
    }

    /**
     * Transfer modal dari rekening owner ke rekening speedshop (dua baris jurnal).
     */
    public function recordOwnerModalSetoran(
        int $ownerUserId,
        SpeedshopKeuanganRekening $sumberOwnerRekening,
        SpeedshopKeuanganRekening $tujuanSpeedshopRekening,
        int $speedshopId,
        string $tanggalYmd,
        int $nominal,
        int $userId,
        ?string $catatan = null,
        ?string $buktiPath = null,
    ): SpeedshopOwnerModalSetoran {
        if (! Schema::hasTable('speedshop_owner_modal_setorans')) {
            throw new \InvalidArgumentException('Fitur setoran modal owner belum tersedia. Jalankan migrasi.');
        }
        if (! Schema::hasColumn('speedshop_finance_ledgers', 'speedshop_keuangan_rekening_id')) {
            throw new \InvalidArgumentException('Kolom jurnal rekening belum tersedia. Jalankan migrasi.');
        }

        $ownerScope = KeuanganScope::forOwner($ownerUserId);
        $speedshopScope = KeuanganScope::forSpeedshop($speedshopId);

        if (! $ownerScope->ownsRekening($sumberOwnerRekening)) {
            throw new \InvalidArgumentException('Rekening sumber bukan milik owner.');
        }
        if (! $speedshopScope->ownsRekening($tujuanSpeedshopRekening)) {
            throw new \InvalidArgumentException('Rekening tujuan tidak valid untuk speedshop yang dipilih.');
        }
        if ((int) $tujuanSpeedshopRekening->speedshop_id !== $speedshopId) {
            throw new \InvalidArgumentException('Rekening tujuan tidak sesuai speedshop.');
        }

        $nominal = max(0, $nominal);
        if ($nominal <= 0) {
            throw new \InvalidArgumentException('Nominal setoran modal harus lebih dari nol.');
        }

        $pocketSumber = $this->pocketForRekeningJenis($sumberOwnerRekening->jenis ?? null);
        $pocketTujuan = $this->pocketForRekeningJenis($tujuanSpeedshopRekening->jenis ?? null);

        $saldoSumber = $this->balanceForBankPocketAsOfScope(
            $ownerScope,
            $pocketSumber,
            (int) $sumberOwnerRekening->id,
            $tanggalYmd,
        );
        if ($saldoSumber < $nominal) {
            throw new \InvalidArgumentException(
                'Saldo rekening owner tidak mencukupi (tersedia Rp '.number_format($saldoSumber, 0, ',', '.').').'
            );
        }

        return DB::transaction(function () use (
            $ownerUserId,
            $speedshopId,
            $sumberOwnerRekening,
            $tujuanSpeedshopRekening,
            $tanggalYmd,
            $nominal,
            $userId,
            $catatan,
            $buktiPath,
            $pocketSumber,
            $pocketTujuan,
            $ownerScope,
            $speedshopScope,
        ) {
            $setoran = SpeedshopOwnerModalSetoran::query()->create([
                'owner_user_id' => $ownerUserId,
                'speedshop_id' => $speedshopId,
                'sumber_owner_rekening_id' => (int) $sumberOwnerRekening->id,
                'tujuan_speedshop_rekening_id' => (int) $tujuanSpeedshopRekening->id,
                'tanggal' => $tanggalYmd,
                'nominal' => $nominal,
                'catatan' => $catatan,
                'bukti_path' => $buktiPath,
                'user_id' => $userId,
            ]);

            $ledgerBase = [
                'tanggal' => $tanggalYmd,
                'invoice_id' => null,
                'setoran_id' => null,
                'owner_modal_setoran_id' => $setoran->id,
                'user_id' => $userId,
            ];

            SpeedshopFinanceLedger::query()->create(array_merge($ledgerBase, [
                'owner_user_id' => $ownerUserId,
                'speedshop_id' => null,
                'warehouse_id' => null,
                'pocket' => $pocketSumber,
                'amount' => -$nominal,
                'reason' => self::REASON_OWNER_MODAL_KELUAR,
                'speedshop_keuangan_rekening_id' => (int) $sumberOwnerRekening->id,
            ]));

            SpeedshopFinanceLedger::query()->create(array_merge($ledgerBase, [
                'owner_user_id' => null,
                'speedshop_id' => $speedshopId,
                'warehouse_id' => null,
                'pocket' => $pocketTujuan,
                'amount' => $nominal,
                'reason' => self::REASON_OWNER_MODAL_MASUK,
                'speedshop_keuangan_rekening_id' => (int) $tujuanSpeedshopRekening->id,
            ]));

            return $setoran;
        });
    }

    /**
     * Posting jurnal pengambilan dividen setelah accounting menyetujui (keluar speedshop, masuk owner).
     */
    public function completeOwnerPengambilanDividen(
        SpeedshopOwnerPengambilanDividen $dividen,
        int $processorUserId,
        string $buktiTransferPath,
    ): void {
        if (! Schema::hasTable('speedshop_owner_pengambilan_dividens')) {
            throw new \InvalidArgumentException('Fitur pengambilan dividen belum tersedia. Jalankan migrasi.');
        }
        if (! Schema::hasColumn('speedshop_finance_ledgers', 'owner_pengambilan_dividen_id')) {
            throw new \InvalidArgumentException('Kolom jurnal pengambilan dividen belum tersedia. Jalankan migrasi.');
        }

        if ($dividen->status !== SpeedshopOwnerPengambilanDividen::STATUS_PENDING) {
            throw new \InvalidArgumentException('Pengajuan dividen sudah diproses.');
        }

        $dividen->loadMissing(['speedshopRekening', 'ownerRekening']);

        $speedshopRekening = $dividen->speedshopRekening;
        $ownerRekening = $dividen->ownerRekening;
        if (! $speedshopRekening || ! $ownerRekening) {
            throw new \InvalidArgumentException('Data rekening tidak lengkap.');
        }

        $ownerUserId = (int) $dividen->owner_user_id;
        $speedshopId = (int) $dividen->speedshop_id;
        $ownerScope = KeuanganScope::forOwner($ownerUserId);
        $speedshopScope = KeuanganScope::forSpeedshop($speedshopId);

        if (! $ownerScope->ownsRekening($ownerRekening)) {
            throw new \InvalidArgumentException('Rekening owner tidak valid.');
        }
        if (! $speedshopScope->ownsRekening($speedshopRekening)) {
            throw new \InvalidArgumentException('Rekening speedshop tidak valid untuk cabang ini.');
        }

        $nominal = max(0, (int) $dividen->nominal);
        if ($nominal <= 0) {
            throw new \InvalidArgumentException('Nominal dividen harus lebih dari nol.');
        }

        $tanggalYmd = Carbon::parse($dividen->tanggal)->toDateString();
        $pocketSpeedshop = $this->pocketForRekeningJenis($speedshopRekening->jenis ?? null);
        $pocketOwner = $this->pocketForRekeningJenis($ownerRekening->jenis ?? null);

        $saldoSpeedshop = $this->balanceForBankPocketAsOfScope(
            $speedshopScope,
            $pocketSpeedshop,
            (int) $speedshopRekening->id,
            $tanggalYmd,
        );
        if ($saldoSpeedshop < $nominal) {
            throw new \InvalidArgumentException(
                'Saldo rekening speedshop tidak mencukupi (tersedia Rp '.number_format($saldoSpeedshop, 0, ',', '.').').'
            );
        }

        DB::transaction(function () use (
            $dividen,
            $processorUserId,
            $buktiTransferPath,
            $ownerUserId,
            $speedshopId,
            $speedshopRekening,
            $ownerRekening,
            $tanggalYmd,
            $nominal,
            $pocketSpeedshop,
            $pocketOwner,
        ): void {
            $locked = SpeedshopOwnerPengambilanDividen::query()->lockForUpdate()->findOrFail($dividen->id);
            if ($locked->status !== SpeedshopOwnerPengambilanDividen::STATUS_PENDING) {
                throw new \InvalidArgumentException('Pengajuan dividen sudah diproses.');
            }

            $ledgerBase = [
                'tanggal' => $tanggalYmd,
                'invoice_id' => null,
                'setoran_id' => null,
                'owner_modal_setoran_id' => null,
                'owner_pengambilan_dividen_id' => $locked->id,
                'user_id' => $processorUserId,
            ];

            SpeedshopFinanceLedger::query()->create(array_merge($ledgerBase, [
                'owner_user_id' => null,
                'speedshop_id' => $speedshopId,
                'warehouse_id' => null,
                'pocket' => $pocketSpeedshop,
                'amount' => -$nominal,
                'reason' => self::REASON_OWNER_DIVIDEN_KELUAR,
                'speedshop_keuangan_rekening_id' => (int) $speedshopRekening->id,
            ]));

            SpeedshopFinanceLedger::query()->create(array_merge($ledgerBase, [
                'owner_user_id' => $ownerUserId,
                'speedshop_id' => null,
                'warehouse_id' => null,
                'pocket' => $pocketOwner,
                'amount' => $nominal,
                'reason' => self::REASON_OWNER_DIVIDEN_MASUK,
                'speedshop_keuangan_rekening_id' => (int) $ownerRekening->id,
            ]));

            $locked->update([
                'status' => SpeedshopOwnerPengambilanDividen::STATUS_COMPLETED,
                'bukti_transfer_path' => $buktiTransferPath,
                'processed_by' => $processorUserId,
                'processed_at' => now(),
                'rejected_reason' => null,
            ]);
        });
    }
}
