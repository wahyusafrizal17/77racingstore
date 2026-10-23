<?php

namespace App\Http\Controllers\Speedshop;

use App\Exports\Speedshop\BiayaOperasionalSpeedshopExport;
use App\Exports\Speedshop\JurnalDigitalExport;
use App\Exports\Speedshop\JurnalKasKecilExport;
use App\Http\Controllers\Concerns\ResolvesKeuanganContext;
use App\Http\Controllers\Controller;
use App\Models\AkunOperasional;
use App\Models\AppNotification;
use App\Models\ServiceOrder;
use App\Models\Speedshop;
use App\Models\SpeedshopFinanceLedger;
use App\Models\SpeedshopJurnalBluEntry;
use App\Models\SpeedshopJurnalSaquEntry;
use App\Models\SpeedshopKeuanganRekening;
use App\Models\SpeedshopRincianBiayaEntry;
use App\Models\User;
use App\Services\SpeedshopFinanceService;
use App\Services\SpeedshopJurnalAutomationService;
use App\Support\KeuanganScope;
use App\Support\SpeedshopLabaRugiFinancials;
use App\Support\SpeedshopPemisahanDanaReport;
use App\Support\WarehouseJurnalReport;
use App\Support\SpeedshopPendapatanBreakdown;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanController extends Controller
{
    use ResolvesKeuanganContext;

    protected function rincianBiayaViewName(): string
    {
        return 'speedshop.laporan.rincian-biaya';
    }

    protected function verifikasiBiayaOperasionalRoute(): string
    {
        return 'speedshop.verifikasi.biaya-operasional.index';
    }

    protected function rincianBiayaPostActionRedirectRoute(): string
    {
        return $this->verifikasiBiayaOperasionalRoute();
    }

    protected function rincianBiayaPendingListUrl(): string
    {
        return route($this->verifikasiBiayaOperasionalRoute());
    }

    protected function rincianBiayaAccountingPendingListUrl(): string
    {
        return route($this->keuanganBiayaOperasionalRoute(), [
            'approval_status' => SpeedshopRincianBiayaEntry::STATUS_PENDING,
            'mode' => 'bulanan',
            'bulan' => now()->format('Y-m'),
        ]);
    }

    protected function keuanganBiayaOperasionalRoute(): string
    {
        return $this->keuanganRoutePrefix().'.biaya-operasional';
    }

    /** @return list<string> */
    protected function rincianBiayaTableColumnFilterKeys(): array
    {
        return [
            'filter_jenis',
            'filter_no_akun',
            'filter_nama_akun',
            'filter_keterangan',
            'filter_pic',
            'filter_pengaju',
            'filter_tbl_status',
            'filter_metode',
            'filter_nominal',
        ];
    }

    protected function applyRincianBiayaTableColumnFilters(
        \Illuminate\Database\Eloquent\Builder $query,
        Request $request,
        bool $hasRekeningCol,
    ): void {
        if ($request->filled('filter_jenis')) {
            $jenis = $request->string('filter_jenis')->trim()->toString();
            if (in_array($jenis, [
                SpeedshopRincianBiayaEntry::JENIS_BIAYA_OPERASIONAL,
                SpeedshopRincianBiayaEntry::JENIS_BIAYA_NON_OPERASIONAL,
            ], true)) {
                $query->where('jenis_operasional', $jenis);
            }
        }

        if ($request->filled('filter_no_akun')) {
            $query->where('no_akun', 'like', '%'.$request->string('filter_no_akun')->trim().'%');
        }

        if ($request->filled('filter_nama_akun')) {
            $query->where('nama_akun', 'like', '%'.$request->string('filter_nama_akun')->trim().'%');
        }

        if ($request->filled('filter_keterangan')) {
            $query->where('keterangan', 'like', '%'.$request->string('filter_keterangan')->trim().'%');
        }

        if ($request->filled('filter_pic')) {
            $query->where('pic', 'like', '%'.$request->string('filter_pic')->trim().'%');
        }

        if ($request->filled('filter_pengaju')) {
            $needle = '%'.$request->string('filter_pengaju')->trim().'%';
            $query->whereHas('submittedBy', fn ($q) => $q->where('name', 'like', $needle));
        }

        if ($request->filled('filter_tbl_status')) {
            $st = $request->string('filter_tbl_status')->trim()->toString();
            if ($st === SpeedshopRincianBiayaEntry::STATUS_PENDING) {
                $query->where('approval_status', SpeedshopRincianBiayaEntry::STATUS_PENDING);
            } elseif ($st === SpeedshopRincianBiayaEntry::STATUS_REJECTED) {
                $query->where('approval_status', SpeedshopRincianBiayaEntry::STATUS_REJECTED);
            } elseif ($st === SpeedshopRincianBiayaEntry::STATUS_APPROVED) {
                $query->where(function ($q) {
                    $q->whereNull('approval_status')
                        ->orWhere('approval_status', SpeedshopRincianBiayaEntry::STATUS_APPROVED);
                });
            }
        }

        if ($request->filled('filter_metode')) {
            $metode = $request->string('filter_metode')->trim()->toString();
            if ($metode === 'tunai') {
                $query->where('metode', 'tunai');
                if ($hasRekeningCol) {
                    $query->where(function ($q) {
                        $q->whereNull('speedshop_keuangan_rekening_id')
                            ->orWhere('speedshop_keuangan_rekening_id', 0);
                    });
                }
            } elseif ($metode === 'transfer') {
                if ($hasRekeningCol) {
                    $query->where(function ($q) {
                        $q->where('metode', 'transfer')
                            ->orWhere('speedshop_keuangan_rekening_id', '>', 0);
                    });
                } else {
                    $query->where('metode', 'transfer');
                }
            }
        }

        if ($request->filled('filter_nominal')) {
            $digits = preg_replace('/\D+/', '', $request->string('filter_nominal')->toString()) ?? '';
            if ($digits !== '') {
                $query->whereRaw('CAST(nominal AS CHAR) LIKE ?', ['%'.$digits.'%']);
            }
        }
    }

    /** PIC otomatis untuk input rincian biaya dari akun speedshop. */
    private const RINCIAN_BIAYA_PIC_KASIR = 'KASIR SPEEDSHOP';

    protected function laporanViewPrefix(): string
    {
        return 'speedshop.laporan.';
    }

    protected function laporanDashboardRoute(): string
    {
        return auth()->user()?->isOwner() ? 'owner.dashboard' : 'speedshop.dashboard';
    }

    protected function laporanJurnalRoute(): string
    {
        return 'speedshop.laporan.jurnal';
    }

    protected function laporanView(string $view, array $data = [])
    {
        return view($this->laporanViewPrefix().$view, $data);
    }

    /** @return array{speedshop_id: ?int, warehouse_id: ?int} */
    protected function financialReportScope(): array
    {
        return SpeedshopLabaRugiFinancials::resolveScopeIds();
    }

    /** @return array{laporanSpeedshopOptions: \Illuminate\Support\Collection, selectedLaporanSpeedshopId: ?int, selectedLaporanSpeedshopName: ?string} */
    protected function ownerLaporanSpeedshopFilterContext(Request $request): array
    {
        $options = collect();
        $selectedId = null;
        $selectedName = null;

        if (auth()->user()?->isOwner()) {
            $options = Speedshop::query()->orderBy('nama')->get(['id', 'nama']);
            if ($request->filled('speedshop_id') && (int) $request->input('speedshop_id') > 0) {
                $candidateId = (int) $request->input('speedshop_id');
                $match = $options->firstWhere('id', $candidateId);
                if ($match) {
                    $selectedId = $candidateId;
                    $selectedName = $match->nama;
                }
            }
        }

        return [
            'laporanSpeedshopOptions' => $options,
            'selectedLaporanSpeedshopId' => $selectedId,
            'selectedLaporanSpeedshopName' => $selectedName,
        ];
    }

    protected function akunMatchesOperasionalBiaya(string $noAkun): bool
    {
        return str_starts_with($noAkun, '5.2.');
    }

    protected function akunMatchesNonOperasionalBiaya(string $noAkun): bool
    {
        return str_starts_with($noAkun, '5.3.');
    }

    /**
     * @return array{
     *     rows: list<array{no: string, label: string, kind: string, amount: ?float, is_percent: bool}>,
     *     summary: array<string, float>
     * }
     */
    protected function buildLabaRugiPresentation(
        \Carbon\Carbon $dari,
        \Carbon\Carbon $sampai,
        ?int $speedshopId,
        ?int $warehouseId,
        array $speedshopIds = [],
    ): array {
        return SpeedshopLabaRugiFinancials::labaRugiPresentation($dari, $sampai, $speedshopId, $warehouseId, $speedshopIds);
    }

    protected function validateRincianBiayaAkun(string $noAkun, bool $isNon): ?string
    {
        if ($isNon && ! $this->akunMatchesNonOperasionalBiaya($noAkun)) {
            return 'Untuk biaya non operasional, pilih akun dengan kode 5.3.x (master).';
        }
        if (! $isNon && ! $this->akunMatchesOperasionalBiaya($noAkun)) {
            return 'Untuk biaya operasional, pilih akun dengan kode 5.2.x (master).';
        }

        return null;
    }

    protected function resolveAccountingBiayaRekeningTujuan(
        Request $request,
        AkunOperasional $akun,
        ?KeuanganScope $keuanganScope,
        ?int $speedshopId,
        bool $isTunaiSource,
        ?SpeedshopKeuanganRekening $rekeningSumber,
    ): array {
        $requiresTujuan = $akun->requiresRekeningTujuan();
        $tujuanRaw = $request->input('keuangan_rekening_tujuan_id');

        if (! $requiresTujuan) {
            if (filled($tujuanRaw)) {
                return ['error' => 'Rekening tujuan tidak diperlukan untuk akun ini.', 'field' => 'keuangan_rekening_tujuan_id'];
            }

            return ['rekening' => null];
        }

        if (! filled($tujuanRaw) || ! is_numeric($tujuanRaw) || (int) $tujuanRaw < 1) {
            return ['error' => 'Rekening tujuan wajib dipilih untuk akun '.$akun->no_akun.' — '.$akun->nama_akun.'.', 'field' => 'keuangan_rekening_tujuan_id'];
        }

        $tujuanQuery = SpeedshopKeuanganRekening::query()->whereKey((int) $tujuanRaw);
        if ($keuanganScope) {
            $keuanganScope->applyToRekening($tujuanQuery);
        } else {
            $tujuanQuery->where('speedshop_id', $speedshopId)->whereNull('warehouse_id');
        }
        $rekeningTujuan = $tujuanQuery->first();
        if (! $rekeningTujuan) {
            return ['error' => 'Rekening tujuan tidak ditemukan.', 'field' => 'keuangan_rekening_tujuan_id'];
        }

        return ['rekening' => $rekeningTujuan];
    }

    /**
     * @return array{keluar: \App\Models\SpeedshopFinanceLedger, tujuan: ?\App\Models\SpeedshopFinanceLedger}
     */
    protected function postApprovedAccountingRekeningBiayaLedgers(
        SpeedshopFinanceService $finance,
        ?KeuanganScope $keuanganScope,
        ?int $speedshopId,
        SpeedshopRincianBiayaEntry $entry,
        bool $isTunaiSource,
        ?SpeedshopKeuanganRekening $rekeningSumber,
        ?SpeedshopKeuanganRekening $rekeningTujuan,
        string $tanggalYmd,
        int $nominalInt,
    ): array {
        $scope = $keuanganScope ?? KeuanganScope::forSpeedshop((int) $speedshopId);
        $userId = (int) auth()->id();
        $entryId = (int) $entry->id;

        $keluar = $isTunaiSource
            ? $finance->recordBiayaOperasionalKasKecilKeluarForScope($scope, $tanggalYmd, $nominalInt, $userId, $entryId)
            : ($keuanganScope
                ? $finance->recordBiayaOperasionalDariRekeningForScope($scope, $tanggalYmd, $nominalInt, $userId, $entryId, $rekeningSumber)
                : $finance->recordBiayaOperasionalDariRekening((int) $speedshopId, $tanggalYmd, $nominalInt, $userId, $entryId, $rekeningSumber));

        $tujuanLedger = null;
        if ($rekeningTujuan) {
            $tujuanLedger = $finance->recordBiayaOperasionalKeRekeningTujuanForScope(
                $scope,
                $tanggalYmd,
                $nominalInt,
                $userId,
                $entry,
                $rekeningTujuan,
            );
        }

        return ['keluar' => $keluar, 'tujuan' => $tujuanLedger];
    }

    protected function speedshopScopeId(): ?int
    {
        $user = auth()->user();
        if ($user?->isOwner() && request()->filled('speedshop_id')) {
            $speedshopId = (int) request()->input('speedshop_id');
            if ($speedshopId > 0 && in_array($speedshopId, $user->activeSpeedshopIds(), true)) {
                return $speedshopId;
            }
        }

        if ($user?->isWarehouse()) {
            $ids = $this->speedshopScopeIds();

            return count($ids) === 1 ? $ids[0] : null;
        }

        return $user?->speedshopScopeId();
    }

    protected function speedshopScopeIds(): array
    {
        $user = auth()->user();
        if ($user?->isOwner() && request()->filled('speedshop_id')) {
            $speedshopId = (int) request()->input('speedshop_id');
            $allowedIds = $user->activeSpeedshopIds();
            if ($speedshopId > 0 && in_array($speedshopId, $allowedIds, true)) {
                return [$speedshopId];
            }
        }

        if ($user?->isWarehouse()) {
            $warehouseId = $user->activeWarehouseId();
            if (! $warehouseId) {
                return [];
            }

            return Speedshop::query()
                ->where(function ($q) use ($warehouseId) {
                    $q->where('mutasi_warehouse_id', $warehouseId)
                        ->orWhere('warehouse_id', $warehouseId);
                })
                ->orderBy('id')
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        $scopeId = $user?->speedshopScopeId();
        if ($scopeId) {
            return [$scopeId];
        }

        return $user?->activeSpeedshopIds() ?? [];
    }

    protected function writableSpeedshopId(): ?int
    {
        $user = auth()->user();
        if ($user?->isWarehouse()) {
            $ids = $this->speedshopScopeIds();

            return $ids[0] ?? null;
        }

        $scopeId = $user?->speedshopScopeId();
        if ($scopeId) {
            return $scopeId;
        }

        $allIds = $user?->activeSpeedshopIds() ?? [];
        if (count($allIds) > 1) {
            return null; // Multi-outlet user must choose one outlet before creating/updating outlet-bound data.
        }

        return $user?->activeSpeedshopId();
    }

    private function saldoKasKecilLedgerBuku(?int $speedshopId): ?int
    {
        if (! $speedshopId || ! Schema::hasTable('speedshop_finance_ledgers')) {
            return null;
        }

        $finance = app(SpeedshopFinanceService::class);

        return $finance->balancePocketAsOf(
            $speedshopId,
            SpeedshopFinanceService::POCKET_LACI,
            now()->toDateString()
        );
    }

    private function saldoKasKecilLedgerBukuForScope(?KeuanganScope $scope): ?int
    {
        if (! $scope || ! Schema::hasTable('speedshop_finance_ledgers')) {
            return null;
        }

        $finance = app(SpeedshopFinanceService::class);

        return $finance->balancePocketAsOfScope(
            $scope,
            SpeedshopFinanceService::POCKET_LACI,
            now()->toDateString(),
        );
    }

    private function verifierCanApproveRejectKasKecilBiayaPending(SpeedshopRincianBiayaEntry $entry): bool
    {
        if (! $entry->isPendingKasKecilPengajuan()) {
            return false;
        }
        if (! $this->entryInSpeedshopScope($entry)) {
            return false;
        }
        $user = auth()->user();
        if (! $user) {
            return false;
        }
        $nominal = (int) round((float) $entry->nominal);
        if ($nominal <= SpeedshopRincianBiayaEntry::KAS_KECIL_VERIFIKASI_ACCOUNTING_MAX_NOMINAL) {
            return $user->isAccounting();
        }

        return $user->isManager();
    }

    private function verifierCanApproveRejectAccountingRekeningBiayaPending(SpeedshopRincianBiayaEntry $entry): bool
    {
        if (! $entry->isPendingAccountingRekeningAwaitingManager()) {
            return false;
        }
        if (! $this->entryInSpeedshopScope($entry)) {
            return false;
        }

        return auth()->user()?->isManager() ?? false;
    }

    /** Satu cabang untuk saldo pembuka jurnal (laci / digital); null jika scope multi-outlet. */
    private function singleSpeedshopIdForJurnalOpening(?int $scopeId, array $speedshopIds): ?int
    {
        if ($scopeId) {
            return (int) $scopeId;
        }
        if (count($speedshopIds) === 1) {
            return (int) $speedshopIds[0];
        }

        return null;
    }

    /**
     * @return array{type: 'kas_kecil'|'digital'|'rekening', rekening_id: ?int, sumber_query: string}
     */
    private function parseJurnalSumberFromRequest(Request $request): array
    {
        $raw = trim((string) $request->query('sumber', 'kas_kecil'));
        if ($raw === '' || $raw === '0') {
            return ['type' => 'kas_kecil', 'rekening_id' => null, 'sumber_query' => 'kas_kecil'];
        }
        if ($raw === 'saqu' || $raw === 'digital') {
            return ['type' => 'digital', 'rekening_id' => null, 'sumber_query' => 'digital'];
        }
        if ($raw === 'blu' || $raw === 'kas_kecil' || $raw === 'laci') {
            return ['type' => 'kas_kecil', 'rekening_id' => null, 'sumber_query' => 'kas_kecil'];
        }
        if (preg_match('/^(kas_besar|rekening_utama|rekening):(\d+)$/', $raw, $m)) {
            $id = (int) $m[2];

            return ['type' => 'rekening', 'rekening_id' => $id > 0 ? $id : null, 'sumber_query' => $raw];
        }

        return ['type' => 'kas_kecil', 'rekening_id' => null, 'sumber_query' => 'kas_kecil'];
    }

    private function humanizeFinanceLedgerReason(?string $reason): string
    {
        $r = (string) ($reason ?? '');

        return match ($r) {
            'invoice_tunai' => 'Pendapatan tunai (invoice)',
            'invoice_digital' => 'Pendapatan digital (invoice)',
            'setoran_tx_out' => 'Setoran — keluar',
            'setoran_tx_in' => 'Setoran — masuk',
            'setoran_tunai_keluar' => 'Setoran tunai — keluar kas kecil',
            'setoran_tunai_masuk_rekening' => 'Setoran tunai — masuk rekening',
            'setoran_digital_keluar' => 'Setoran digital — keluar kas besar',
            'setoran_digital_masuk_rekening' => 'Setoran digital — masuk rekening utama',
            'setoran_kk_ke_kas_besar_keluar' => 'Setoran Kas Kecil → Kas Besar (keluar laci)',
            'setoran_kk_ke_kas_besar_masuk' => 'Setoran Kas Kecil → Kas Besar (masuk)',
            SpeedshopFinanceService::REASON_PERMINTAAN_KAS_KECIL => 'Permintaan kas kecil (disetujui)',
            SpeedshopFinanceService::REASON_KAS_TUNAI_DARI_REGULER_KELUAR => 'Kas tunai — keluar dari Rekening Reguler',
            SpeedshopFinanceService::REASON_KAS_TUNAI_DARI_REGULER_MASUK => 'Kas tunai — masuk laci',
            SpeedshopFinanceService::REASON_SALDO_KOREKSI_MANAGER => 'Koreksi saldo buku (Manager)',
            SpeedshopFinanceService::REASON_BIAYA_OPERASIONAL_KAS_KECIL => 'Biaya operasional (kas kecil)',
            SpeedshopFinanceService::REASON_BIAYA_OPERASIONAL_REKENING => 'Biaya operasional (rekening)',
            default => $r !== '' ? $r : '-',
        };
    }

    /**
     * Opsi select Sumber jurnal — struktur dan label selaras form Sumber di setoran/create.
     *
     * @return array{
     *     top_options: list<array{value: string, label: string}>,
     *     optgroups: list<array{label: string, options: list<array{value: string, label: string}>}>
     * }
     */
    private function jurnalSumberSelectSpecForOutlet(int $speedshopId): array
    {
        $labelRekening = static function (SpeedshopKeuanganRekening $r): string {
            $parts = array_filter([
                trim((string) $r->nama),
                trim((string) ($r->nama_bank ?? '')),
                trim((string) ($r->nomor_rekening ?? '')),
            ], fn ($s) => $s !== '');

            return $parts !== [] ? implode(' · ', $parts) : (string) $r->nama;
        };

        $top = [
            ['value' => '', 'label' => '— pilih sumber —'],
            ['value' => 'kas_kecil', 'label' => 'Kas Kecil (Tunai)'],
            ['value' => 'digital', 'label' => 'Kas digital (gabungan Kas Besar + Rek. Utama)'],
        ];

        if (! Schema::hasTable('speedshop_keuangan_rekenings')) {
            return ['top_options' => $top, 'optgroups' => []];
        }

        $hasJenis = Schema::hasColumn('speedshop_keuangan_rekenings', 'jenis');
        $utamaRows = SpeedshopKeuanganRekening::query()
            ->where('speedshop_id', $speedshopId)
            ->when($hasJenis, fn ($q) => $q->where('jenis', SpeedshopKeuanganRekening::JENIS_REKENING_UTAMA))
            ->orderBy('nama')
            ->orderBy('id')
            ->get();

        $kasDedicated = collect();
        if ($hasJenis) {
            $kasDedicated = SpeedshopKeuanganRekening::query()
                ->where('speedshop_id', $speedshopId)
                ->whereIn('jenis', SpeedshopKeuanganRekening::secondaryJenisValues())
                ->orderBy('nama')
                ->orderBy('id')
                ->get();
        }
        $kasRowsForUi = $kasDedicated->isNotEmpty() ? $kasDedicated : $utamaRows;

        $kbOpts = [];
        foreach ($kasRowsForUi as $r) {
            $kbOpts[] = [
                'value' => 'kas_besar:'.(int) $r->id,
                'label' => $labelRekening($r),
            ];
        }

        $ruOpts = [];
        foreach ($utamaRows as $r) {
            $ruOpts[] = [
                'value' => 'rekening_utama:'.(int) $r->id,
                'label' => $labelRekening($r),
            ];
        }

        $optgroups = [];
        if ($kbOpts !== []) {
            $optgroups[] = ['label' => 'Kas Besar (Bank)', 'options' => $kbOpts];
        }
        if ($ruOpts !== []) {
            $optgroups[] = ['label' => 'Rekening Utama', 'options' => $ruOpts];
        }

        return ['top_options' => $top, 'optgroups' => $optgroups];
    }

    /**
     * Mutasi buku besar (ledger) untuk satu rekening bank & pocket terkait.
     *
     * @return array<string, mixed>|null
     */
    private function buildJurnalLedgerRekeningReportData(Request $request, int $rekeningId): ?array
    {
        $period = $this->resolveLaporanPeriodeMode($request, 'bulanan');
        $dari = $period['dari'];
        $sampai = $period['sampai'];
        $mode = $period['mode'];
        $selectedDate = $period['selectedDate'];
        $selectedMonth = $period['selectedMonth'];
        $selectedYear = $period['selectedYear'];

        $speedshopIds = $this->speedshopScopeIds();
        if ($speedshopIds === []) {
            return null;
        }
        $scopeId = $this->speedshopScopeId();
        $openingShopId = $this->singleSpeedshopIdForJurnalOpening($scopeId, $speedshopIds);
        if (! $openingShopId) {
            return null;
        }

        $rek = SpeedshopKeuanganRekening::query()
            ->whereIn('speedshop_id', $speedshopIds)
            ->whereKey($rekeningId)
            ->first();
        if (! $rek) {
            return null;
        }

        $speedshop = Speedshop::find((int) $rek->speedshop_id);
        $pocket = $rek->isRekeningUtama()
            ? SpeedshopFinanceService::POCKET_REKENING
            : SpeedshopFinanceService::POCKET_CASH_BESAR;

        $finance = app(SpeedshopFinanceService::class);
        $prevDay = $dari->copy()->subDay()->toDateString();
        $openingBal = 0.0;
        if (Schema::hasTable('speedshop_finance_ledgers') && Schema::hasColumn('speedshop_finance_ledgers', 'speedshop_keuangan_rekening_id')) {
            $openingBal = (float) $finance->balanceForBankPocketAsOf((int) $openingShopId, $pocket, (int) $rek->id, $prevDay);
        }

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

        $dbRows = collect();
        if (Schema::hasTable('speedshop_finance_ledgers') && Schema::hasColumn('speedshop_finance_ledgers', 'speedshop_keuangan_rekening_id')) {
            $ledgers = SpeedshopFinanceLedger::query()
                ->with(['user:id,name'])
                ->where('speedshop_id', (int) $openingShopId)
                ->where('pocket', $pocket)
                ->where('speedshop_keuangan_rekening_id', (int) $rek->id)
                ->whereBetween('tanggal', [$dari->toDateString(), $sampai->toDateString()])
                ->orderBy('tanggal')
                ->orderBy('id')
                ->get();

            foreach ($ledgers as $L) {
                $amt = (int) ($L->amount ?? 0);
                $kredit = $amt > 0 ? (float) $amt : 0.0;
                $debet = $amt < 0 ? (float) (-$amt) : 0.0;
                $pic = $L->user?->name ?? '-';
                $dbRows->push((object) [
                    'id' => (int) $L->id,
                    'tanggal' => $L->tanggal,
                    'akun' => 'LEDGER',
                    'keterangan' => $this->humanizeFinanceLedgerReason($L->reason),
                    'pic' => $pic,
                    'kredit' => $kredit,
                    'debet' => $debet,
                    'allow_delete' => false,
                ]);
            }
        }

        $entriesMerged = $openingRows->concat($dbRows);
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

        $pemasukan = (float) $rows->sum('kredit');
        $pengeluaran = (float) $rows->sum('debet');
        $sisaSaldo = $rows->isNotEmpty() ? (float) $rows->last()->saldo : 0.0;

        return [
            'speedshop' => $speedshop,
            'rows' => $rows,
            'pemasukan' => $pemasukan,
            'pengeluaran' => $pengeluaran,
            'sisaSaldo' => $sisaSaldo,
            'dari' => $dari,
            'sampai' => $sampai,
            'mode' => $mode,
            'selectedDate' => $selectedDate,
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
            'selectedDari' => $period['selectedDari'],
            'selectedSampai' => $period['selectedSampai'],
            'monitorOnly' => true,
            'jurnalKind' => 'ledger_rekening',
            'ledgerRekening' => $rek,
            'canManualEntry' => false,
            'thirdCardTitle' => 'Saldo akhir (buku)',
            'emptyMessage' => 'Belum ada mutasi ledger untuk rekening ini pada periode ini.',
        ];
    }

    private function entryInSpeedshopScope(SpeedshopRincianBiayaEntry $entry): bool
    {
        if ($this->isWarehouseKeuanganContext()) {
            $scope = $this->resolveKeuanganScope();

            return $scope !== null && $scope->ownsRincianBiaya($entry);
        }

        return in_array((int) $entry->speedshop_id, $this->speedshopScopeIds(), true);
    }

    private function resolveKeuanganScopeForEntry(SpeedshopRincianBiayaEntry $entry): ?KeuanganScope
    {
        if (Schema::hasColumn('speedshop_rincian_biaya_entries', 'warehouse_id')
            && (int) ($entry->warehouse_id ?? 0) > 0) {
            return KeuanganScope::forWarehouse((int) $entry->warehouse_id);
        }

        if ((int) ($entry->speedshop_id ?? 0) > 0) {
            return KeuanganScope::forSpeedshop((int) $entry->speedshop_id);
        }

        return null;
    }

    private function storedBuktiLampiranPathOrNull(Request $request, ?int $speedshopId, ?KeuanganScope $scope = null): ?string
    {
        if (! Schema::hasColumn('speedshop_rincian_biaya_entries', 'bukti_lampiran_path')) {
            return null;
        }
        if (! $request->hasFile('bukti_lampiran')) {
            return null;
        }
        $file = $request->file('bukti_lampiran');
        if (! $file || ! $file->isValid()) {
            throw new \InvalidArgumentException('File lampiran tidak valid atau gagal diunggah.');
        }
        if ($scope?->isWarehouse()) {
            $prefix = 'warehouse/gudang-'.$scope->warehouseId.'/biaya-operasional';
        } else {
            $sp = Speedshop::query()->find($speedshopId);
            $outletCode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $sp?->invoiceOutletCode() ?? '') ?: 'UNK');
            $prefix = 'speedshop/'.$outletCode.'-'.$speedshopId.'/biaya-operasional';
        }
        $disk = (string) config('filesystems.upload_disk', 'public');
        $stored = $file->store($prefix, $disk);
        if (! is_string($stored) || $stored === '') {
            throw new \InvalidArgumentException('Gagal menyimpan file lampiran.');
        }

        return $stored;
    }

    private function deleteRincianBiayaBuktiFileIfAny(SpeedshopRincianBiayaEntry $entry): void
    {
        if (! Schema::hasColumn('speedshop_rincian_biaya_entries', 'bukti_lampiran_path')) {
            return;
        }
        $path = $entry->bukti_lampiran_path ?? null;
        if (! is_string($path) || $path === '') {
            return;
        }
        Storage::disk((string) config('filesystems.upload_disk', 'public'))->delete($path);
    }

    private function notifyRincianBiayaPendingVerifiers(
        SpeedshopRincianBiayaEntry $entry,
        ?int $speedshopId,
        string $pemohon,
        string $nominalFormatted,
        string $tanggalLabel,
        string $speedshopLabel,
        ?KeuanganScope $keuanganScope = null,
    ): void {
        if (! Schema::hasTable('app_notifications')) {
            return;
        }

        $link = $this->rincianBiayaPendingListUrl();
        $baseMessage = "{$pemohon} mengajukan biaya dari Kas Kecil Rp {$nominalFormatted} ({$tanggalLabel}) — {$speedshopLabel}.";

        if ($entry->pendingKasKecilRequiresManagerVerification()) {
            $title = 'Biaya operasional menunggu verifikasi Manager';
            $message = $baseMessage.' Nominal di atas Rp '.number_format(SpeedshopRincianBiayaEntry::KAS_KECIL_VERIFIKASI_ACCOUNTING_MAX_NOMINAL, 0, ',', '.').' wajib diverifikasi Manager.';
            $managers = $keuanganScope?->isWarehouse()
                ? User::warehouseManagersForWarehouse($keuanganScope->warehouseId)
                : User::speedshopManagersForOutlet($speedshopId);
            foreach ($managers as $mgr) {
                AppNotification::create([
                    'user_id' => $mgr->id,
                    'type' => 'speedshop_rincian_biaya_pending_manager',
                    'title' => $title,
                    'message' => $message,
                    'data' => [
                        'speedshop_rincian_biaya_entry_id' => $entry->id,
                        'speedshop_id' => $speedshopId,
                        'warehouse_id' => $keuanganScope?->warehouseId,
                    ],
                    'link' => $link,
                ]);
            }

            return;
        }

        $accountingLink = $this->rincianBiayaAccountingPendingListUrl();
        $accountingUsers = $keuanganScope?->isWarehouse()
            ? User::accountingUsersForWarehouse($keuanganScope->warehouseId)
            : User::accountingUsersForSpeedshop((int) $speedshopId);

        foreach ($accountingUsers as $acct) {
            AppNotification::create([
                'user_id' => $acct->id,
                'type' => 'speedshop_rincian_biaya_pending',
                'title' => 'Biaya operasional menunggu verifikasi Accounting',
                'message' => $baseMessage,
                'data' => [
                    'speedshop_rincian_biaya_entry_id' => $entry->id,
                    'speedshop_id' => $speedshopId,
                    'warehouse_id' => $keuanganScope?->warehouseId,
                ],
                'link' => $accountingLink,
            ]);
        }
    }

    private function notifyManagersAccountingRekeningBiayaPending(
        SpeedshopRincianBiayaEntry $entry,
        int $speedshopId,
        SpeedshopKeuanganRekening $rekening,
    ): void {
        if (! Schema::hasTable('app_notifications')) {
            return;
        }

        $speedshopLabel = Speedshop::query()->whereKey($speedshopId)->value('nama') ?? 'Cabang';
        $nominalFormatted = number_format((int) round((float) ($entry->nominal ?? 0)), 0, ',', '.');
        $tanggalStr = $entry->tanggal instanceof \Carbon\Carbon
            ? $entry->tanggal->format('d/m/Y')
            : \Carbon\Carbon::parse($entry->tanggal)->format('d/m/Y');
        $pemohon = auth()->user()?->name ?? 'Accounting';
        $rekLabel = trim(($rekening->nama_bank ? (string) $rekening->nama_bank.' ' : '').(string) $rekening->nama);
        $link = $this->rincianBiayaPendingListUrl();
        $title = 'Biaya (rekening) menunggu persetujuan Manager';
        $message = "{$pemohon} mengajukan biaya dari rekening ({$rekLabel}) Rp {$nominalFormatted} ({$tanggalStr}) — {$speedshopLabel}. Nominal di atas Rp "
            .number_format(SpeedshopRincianBiayaEntry::KAS_KECIL_VERIFIKASI_ACCOUNTING_MAX_NOMINAL, 0, ',', '.')
            .' wajib disetujui Manager sebelum saldo buku dipotong.';

        foreach (User::speedshopManagersForOutlet($speedshopId) as $mgr) {
            AppNotification::create([
                'user_id' => $mgr->id,
                'type' => 'speedshop_rincian_biaya_pending_manager_rekening',
                'title' => $title,
                'message' => $message,
                'data' => [
                    'speedshop_rincian_biaya_entry_id' => $entry->id,
                    'speedshop_id' => $speedshopId,
                ],
                'link' => $link,
            ]);
        }
    }

    private function notifySpeedshopRincianBiayaDecision(
        SpeedshopRincianBiayaEntry $entry,
        bool $approved,
        ?string $rejectionNote,
    ): void {
        if (! Schema::hasTable('app_notifications')) {
            return;
        }

        $speedshopId = (int) $entry->speedshop_id;
        if ($speedshopId <= 0) {
            return;
        }

        $speedshopLabel = Speedshop::query()->whereKey($speedshopId)->value('nama') ?? 'Cabang';
        $nominalFormatted = number_format((int) round((float) ($entry->nominal ?? 0)), 0, ',', '.');
        $tanggalStr = $entry->tanggal instanceof \Carbon\Carbon
            ? $entry->tanggal->format('d/m/Y')
            : \Carbon\Carbon::parse($entry->tanggal)->format('d/m/Y');
        $verifier = auth()->user()?->name ?? 'Accounting';

        $recipientIds = collect();
        $submitterId = (int) ($entry->submitted_by ?? 0);
        if ($submitterId > 0) {
            $recipientIds->push($submitterId);
        } else {
            $recipientIds = User::speedshopApproversForOutlet($speedshopId)->pluck('id');
        }

        $recipientIds = $recipientIds->unique()->filter()->values();
        if ($recipientIds->isEmpty()) {
            return;
        }

        $link = route($this->keuanganBiayaOperasionalRoute());

        $isBiayaDariRekening = Schema::hasColumn($entry->getTable(), 'speedshop_keuangan_rekening_id')
            && (int) ($entry->speedshop_keuangan_rekening_id ?? 0) > 0
            && ($entry->metode ?? '') === 'transfer';

        if ($approved) {
            $type = 'speedshop_rincian_biaya_approved';
            $title = 'Biaya operasional disetujui';
            if ($isBiayaDariRekening) {
                $message = "{$verifier} menyetujui pengajuan biaya (rekening) Rp {$nominalFormatted} (tanggal transaksi {$tanggalStr}) — {$speedshopLabel}. Saldo buku rekening telah dikurangi.";
            } else {
                $message = "{$verifier} menyetujui pengajuan biaya Kas Kecil Rp {$nominalFormatted} (tanggal transaksi {$tanggalStr}) — {$speedshopLabel}. Saldo Kas Kecil (buku) telah dikurangi.";
            }
        } else {
            $type = 'speedshop_rincian_biaya_rejected';
            $title = 'Biaya operasional ditolak';
            $trimNote = $rejectionNote ? trim($rejectionNote) : '';
            $suffix = $trimNote !== '' ? ' Catatan: '.mb_strimwidth($trimNote, 0, 180, '…', 'UTF-8') : '';
            if ($isBiayaDariRekening) {
                $message = "{$verifier} menolak pengajuan biaya (rekening) Rp {$nominalFormatted} ({$tanggalStr}) — {$speedshopLabel}.{$suffix}";
            } else {
                $message = "{$verifier} menolak pengajuan biaya Kas Kecil Rp {$nominalFormatted} ({$tanggalStr}) — {$speedshopLabel}.{$suffix}";
            }
        }

        foreach ($recipientIds as $userId) {
            AppNotification::create([
                'user_id' => (int) $userId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'data' => [
                    'speedshop_rincian_biaya_entry_id' => $entry->id,
                    'speedshop_id' => $speedshopId,
                    'decision' => $approved ? 'approved' : 'rejected',
                ],
                'link' => $link,
            ]);
        }
    }

    private function speedshopId(): ?int
    {
        $speedshopId = auth()->user()->activeSpeedshopId();
        if ($speedshopId) {
            return $speedshopId;
        }

        $warehouseId = auth()->user()->activeWarehouseId();
        if (! $warehouseId) {
            return null;
        }

        return Speedshop::query()
            ->where(function ($q) use ($warehouseId) {
                $q->where('mutasi_warehouse_id', $warehouseId)
                    ->orWhere('warehouse_id', $warehouseId);
            })
            ->value('id');
    }

    private function warehouseId(): ?int
    {
        return auth()->user()->activeWarehouseId();
    }

    private function dateRange(Request $request): array
    {
        $dari = $request->get('dari') ? \Carbon\Carbon::parse($request->dari)->startOfDay() : now()->startOfMonth();
        $sampai = $request->get('sampai') ? \Carbon\Carbon::parse($request->sampai)->endOfDay() : now()->endOfDay();

        return [$dari, $sampai];
    }

    /**
     * Periode laporan seperti dashboard speedshop (harian / bulanan / tahunan).
     *
     * @return array{dari: \Carbon\Carbon, sampai: \Carbon\Carbon, mode: string, selectedDate: string, selectedMonth: string, selectedYear: int, selectedDari: string, selectedSampai: string}
     */
    protected function resolveLaporanPeriodeMode(Request $request, string $defaultMode = 'harian'): array
    {
        if (! in_array($defaultMode, ['harian', 'bulanan', 'tahunan', 'range'], true)) {
            $defaultMode = 'harian';
        }

        $mode = in_array($request->input('mode'), ['harian', 'bulanan', 'tahunan', 'range'], true)
            ? $request->input('mode')
            : $defaultMode;

        if ($request->filled('dari') && $request->filled('sampai') && ! $request->filled('mode')) {
            $mode = 'range';
        }

        $selectedDate = $request->input('tanggal') ?: now()->format('Y-m-d');
        $selectedMonth = $request->input('bulan') ?: now()->format('Y-m');
        $selectedYear = (int) ($request->input('tahun') ?: now()->year);
        $selectedDari = $request->input('dari') ?: now()->startOfMonth()->format('Y-m-d');
        $selectedSampai = $request->input('sampai') ?: now()->format('Y-m-d');

        if ($mode === 'range') {
            try {
                $dari = \Carbon\Carbon::parse($selectedDari)->startOfDay();
                $sampai = \Carbon\Carbon::parse($selectedSampai)->endOfDay();
            } catch (\Throwable $e) {
                $selectedDari = now()->startOfMonth()->format('Y-m-d');
                $selectedSampai = now()->format('Y-m-d');
                $dari = now()->startOfMonth()->startOfDay();
                $sampai = now()->endOfDay();
            }

            if ($dari->gt($sampai)) {
                [$dari, $sampai] = [$sampai->copy()->startOfDay(), $dari->copy()->endOfDay()];
                $selectedDari = $dari->format('Y-m-d');
                $selectedSampai = $sampai->format('Y-m-d');
            }

            return [
                'dari' => $dari,
                'sampai' => $sampai,
                'mode' => 'range',
                'selectedDate' => $selectedDari,
                'selectedMonth' => $dari->format('Y-m'),
                'selectedYear' => (int) $dari->format('Y'),
                'selectedDari' => $selectedDari,
                'selectedSampai' => $selectedSampai,
            ];
        }

        if ($mode === 'harian') {
            try {
                $dari = \Carbon\Carbon::parse($selectedDate)->startOfDay();
                $sampai = \Carbon\Carbon::parse($selectedDate)->endOfDay();
            } catch (\Throwable $e) {
                $selectedDate = now()->format('Y-m-d');
                $dari = now()->startOfDay();
                $sampai = now()->endOfDay();
            }
        } elseif ($mode === 'tahunan') {
            $selectedYear = max(2000, min(2100, $selectedYear));
            $dari = now()->setYear($selectedYear)->startOfYear();
            $sampai = now()->setYear($selectedYear)->endOfYear();
        } else {
            try {
                $monthBase = \Carbon\Carbon::parse($selectedMonth.'-01');
            } catch (\Throwable $e) {
                $selectedMonth = now()->format('Y-m');
                $monthBase = now()->startOfMonth();
            }
            $dari = $monthBase->copy()->startOfMonth();
            $sampai = $monthBase->copy()->endOfMonth();
        }

        return [
            'dari' => $dari,
            'sampai' => $sampai,
            'mode' => $mode,
            'selectedDate' => $selectedDate,
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
            'selectedDari' => $dari->format('Y-m-d'),
            'selectedSampai' => $sampai->format('Y-m-d'),
        ];
    }

    /** @return array<string, mixed> */
    protected function rincianBiayaRedirectQuery(Request $request, string $tanggalYmd, ?SpeedshopRincianBiayaEntry $entry = null): array
    {
        $filterQuery = array_filter(
            $request->only(['mode', 'tanggal', 'bulan', 'tahun', 'dari', 'sampai', 'approval_status']),
            fn ($v) => $v !== null && $v !== ''
        );

        $entryDate = \Carbon\Carbon::parse($tanggalYmd)->startOfDay();
        $period = $this->resolveLaporanPeriodeMode(
            Request::create('/', 'GET', $filterQuery),
            'bulanan',
        );

        if (! $entryDate->between($period['dari']->copy()->startOfDay(), $period['sampai']->copy()->endOfDay())) {
            $filterQuery = [
                'mode' => 'bulanan',
                'bulan' => $entryDate->format('Y-m'),
            ];
        }

        if ($entry !== null) {
            $approvalFilter = (string) ($filterQuery['approval_status'] ?? '');
            if ($approvalFilter !== '') {
                $visible = match ($approvalFilter) {
                    SpeedshopRincianBiayaEntry::STATUS_PENDING => $entry->isPending(),
                    SpeedshopRincianBiayaEntry::STATUS_REJECTED => $entry->approval_status === SpeedshopRincianBiayaEntry::STATUS_REJECTED,
                    SpeedshopRincianBiayaEntry::STATUS_APPROVED => $entry->isApprovedForReporting(),
                    default => true,
                };
                if (! $visible) {
                    unset($filterQuery['approval_status']);
                }
            }
        }

        return $filterQuery;
    }

    public function biaya(Request $request)
    {
        $ctx = $this->buildBiayaReportContext($request);

        return $this->laporanView('biaya', array_merge($ctx, $this->ownerLaporanSpeedshopFilterContext($request)));
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildBiayaReportContext(Request $request): array
    {
        $scope = $this->financialReportScope();
        $speedshopId = $scope['speedshop_id'];
        $warehouseId = $scope['warehouse_id'];
        $speedshopIds = $scope['speedshop_ids'] ?? [];
        $laporanAllSpeedshops = $scope['all_speedshops'] ?? false;

        $period = $this->resolveLaporanPeriodeMode($request, 'bulanan');
        $dari = $period['dari'];
        $sampai = $period['sampai'];
        $mode = $period['mode'];
        $selectedDate = $period['selectedDate'];
        $selectedMonth = $period['selectedMonth'];
        $selectedYear = $period['selectedYear'];
        $speedshop = $laporanAllSpeedshops
            ? null
            : Speedshop::find($speedshopId ?? $this->speedshopId());

        $periode = SpeedshopLabaRugiFinancials::pendapatanBiayaRow($dari, $sampai, $speedshopId, $warehouseId, $speedshopIds);

        $realtimeDate = $sampai->copy()->startOfDay();
        if ($mode === 'harian') {
            $realtimeDate = \Carbon\Carbon::parse($selectedDate)->startOfDay();
            $realtime = SpeedshopLabaRugiFinancials::pendapatanBiayaRow(
                $realtimeDate->copy()->startOfDay(),
                $realtimeDate->copy()->endOfDay(),
                $speedshopId,
                $warehouseId,
                $speedshopIds,
            );
        } else {
            $realtime = $periode;
        }

        $financials = SpeedshopLabaRugiFinancials::compute($dari, $sampai, $speedshopId, $warehouseId, $speedshopIds);
        $totalBiaya = (float) $financials['total_biaya'];
        $totalBiayaSparepart = (float) $financials['biaya_hpp_sparepart'];
        $totalBiayaOperasional = (float) $financials['biaya_operasional'];
        $totalBiayaNonOperasional = (float) $financials['biaya_non_operasional'];

        return compact(
            'periode', 'realtime', 'realtimeDate',
            'totalBiaya', 'totalBiayaSparepart', 'totalBiayaOperasional', 'totalBiayaNonOperasional',
            'dari', 'sampai',
            'mode', 'selectedDate', 'selectedMonth', 'selectedYear',
            'speedshop', 'laporanAllSpeedshops',
        ) + [
            'selectedDari' => $period['selectedDari'],
            'selectedSampai' => $period['selectedSampai'],
        ];
    }

    public function labaRugi(Request $request)
    {
        $scope = $this->financialReportScope();
        $speedshopId = $scope['speedshop_id'];
        $warehouseId = $scope['warehouse_id'];
        $speedshopIds = $scope['speedshop_ids'] ?? [];
        $laporanAllSpeedshops = $scope['all_speedshops'] ?? false;

        $period = $this->resolveLaporanPeriodeMode($request, 'bulanan');
        $dari = $period['dari'];
        $sampai = $period['sampai'];
        $mode = $period['mode'];
        $selectedDate = $period['selectedDate'];
        $selectedMonth = $period['selectedMonth'];
        $selectedYear = $period['selectedYear'];

        $presentation = $this->buildLabaRugiPresentation($dari, $sampai, $speedshopId, $warehouseId, $speedshopIds);
        $speedshop = $laporanAllSpeedshops
            ? null
            : Speedshop::find($speedshopId ?? $this->speedshopId());

        return $this->laporanView('laba-rugi', array_merge([
            'labaRugiRows' => $presentation['rows'],
            'labaRugiSummary' => $presentation['summary'],
            'speedshop' => $speedshop,
            'laporanAllSpeedshops' => $laporanAllSpeedshops,
            'dari' => $dari,
            'sampai' => $sampai,
            'mode' => $mode,
            'selectedDate' => $selectedDate,
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
            'selectedDari' => $period['selectedDari'],
            'selectedSampai' => $period['selectedSampai'],
        ], $this->ownerLaporanSpeedshopFilterContext($request)));
    }

    public function summary(Request $request)
    {
        $ctx = $this->buildSummaryReportContext($request);

        return $this->laporanView('summary', array_merge($ctx, $this->ownerLaporanSpeedshopFilterContext($request)));
    }

    public function rekapPemisahanDana(Request $request)
    {
        return $this->laporanView('rekap-pemisahan-dana', array_merge(
            $this->buildRekapPemisahanDanaContext($request),
            $this->ownerLaporanSpeedshopFilterContext($request),
        ));
    }

    public function exportRekapPemisahanDana(Request $request)
    {
        $ctx = $this->resolveRekapPemisahanDanaExportContext($request);

        return Excel::download(
            new \App\Exports\Speedshop\LaporanRekapPemisahanDanaExport($ctx),
            'rekap-pemisahan-dana-'.$ctx['dari']->format('Ymd').'-'.$ctx['sampai']->format('Ymd').'-'.now()->format('His').'.xlsx'
        );
    }

    public function exportRekapPemisahanDanaPdf(Request $request): Response
    {
        $ctx = $this->resolveRekapPemisahanDanaExportContext($request);
        $filename = 'rekap-pemisahan-dana-'.$ctx['dari']->format('Ymd').'-'.$ctx['sampai']->format('Ymd').'-'.now()->format('His').'.pdf';

        return Pdf::loadView('speedshop.laporan.rekap-pemisahan-dana-pdf', $ctx)
            ->setPaper([0, 0, 841.89, 595.28])
            ->stream($filename);
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildRekapPemisahanDanaContext(Request $request): array
    {
        $scope = $this->financialReportScope();
        $speedshopId = $scope['speedshop_id'];
        $warehouseId = $scope['warehouse_id'];
        $speedshopIds = $scope['speedshop_ids'] ?? [];
        $laporanAllSpeedshops = $scope['all_speedshops'] ?? false;

        $period = $this->resolveLaporanPeriodeMode($request, 'bulanan');
        $dari = $period['dari'];
        $sampai = $period['sampai'];

        $report = SpeedshopPemisahanDanaReport::build(
            $dari,
            $sampai,
            $speedshopId,
            $warehouseId,
            $speedshopIds,
        );

        $speedshop = $laporanAllSpeedshops
            ? null
            : Speedshop::find($speedshopId ?? $this->speedshopId());

        $rowByKey = collect($report['rows'] ?? [])->keyBy('key');

        return array_merge($report, [
            'speedshop' => $speedshop,
            'laporanAllSpeedshops' => $laporanAllSpeedshops,
            'dari' => $dari,
            'sampai' => $sampai,
            'mode' => $period['mode'],
            'selectedDate' => $period['selectedDate'],
            'selectedMonth' => $period['selectedMonth'],
            'selectedYear' => $period['selectedYear'],
            'selectedDari' => $period['selectedDari'],
            'selectedSampai' => $period['selectedSampai'],
            'cardOmset' => (int) ($rowByKey->get('omset')['cells']['total']['nominal'] ?? 0),
            'cardHpp' => (int) ($rowByKey->get('hpp')['cells']['total']['nominal'] ?? 0),
            'cardSisaProfit' => (int) ($rowByKey->get('sisa_profit')['cells']['total']['nominal'] ?? 0),
            'cardAlokasiBiaya' => collect($report['rows'] ?? [])
                ->whereNotIn('key', ['omset', 'hpp', 'sisa_profit'])
                ->sum(fn (array $r) => (int) ($r['cells']['total']['nominal'] ?? 0)),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function resolveRekapPemisahanDanaExportContext(Request $request): array
    {
        $ctx = array_merge(
            $this->buildRekapPemisahanDanaContext($request),
            $this->ownerLaporanSpeedshopFilterContext($request),
        );

        $periodeLabel = $ctx['dari']->locale('id')->translatedFormat('d M Y')
            .' – '
            .$ctx['sampai']->locale('id')->translatedFormat('d M Y');
        $speedshopLabel = ! empty($ctx['selectedLaporanSpeedshopName'])
            ? strtoupper($ctx['selectedLaporanSpeedshopName'])
            : (! empty($ctx['laporanAllSpeedshops'])
                ? 'SEMUA SPEEDSHOP'
                : strtoupper($ctx['speedshop']?->nama ?? 'SPEEDSHOP'));
        $fmt = fn (int $n): string => number_format($n, 0, ',', '.');
        $cardTotalBiaya = (int) ($ctx['cardHpp'] ?? 0) + (int) ($ctx['cardAlokasiBiaya'] ?? 0);

        $metaLines = array_values(array_filter([
            'Periode: '.$periodeLabel,
            'Bulan: '.($ctx['monthLabel'] ?? ''),
            'Outlet: '.$speedshopLabel,
            'Omset: Rp '.$fmt((int) ($ctx['cardOmset'] ?? 0)),
            'Total Biaya & Alokasi: Rp '.$fmt($cardTotalBiaya),
            'Sisa Profit: Rp '.$fmt((int) ($ctx['cardSisaProfit'] ?? 0)),
        ]));

        return array_merge($ctx, [
            'periodeLabel' => $periodeLabel,
            'speedshopLabel' => $speedshopLabel,
            'metaLines' => $metaLines,
            'printedAt' => now(),
            'cardTotalBiaya' => $cardTotalBiaya,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildSummaryReportContext(Request $request): array
    {
        $scope = $this->financialReportScope();
        $speedshopId = $scope['speedshop_id'];
        $warehouseId = $scope['warehouse_id'];
        $speedshopIds = $scope['speedshop_ids'] ?? [];
        $laporanAllSpeedshops = $scope['all_speedshops'] ?? false;
        $period = $this->resolveLaporanPeriodeMode($request, 'bulanan');
        $dari = $period['dari'];
        $sampai = $period['sampai'];
        $mode = $period['mode'];
        $selectedDate = $period['selectedDate'];
        $selectedMonth = $period['selectedMonth'];
        $selectedYear = $period['selectedYear'];

        $speedshop = $laporanAllSpeedshops
            ? null
            : Speedshop::find($speedshopId ?? $this->speedshopId());

        $paymentDefs = [
            'cash' => 'PEMBAYARAN VIA TUNAI',
            'qris' => 'PEMBAYARAN VIA QRIS',
            'transfer' => 'PEMBAYARAN VIA TRANSFER',
            'edc' => 'PEMBAYARAN VIA EDC',
        ];

        $days = collect();
        $cursor = $dari->copy()->startOfDay();
        $end = $sampai->copy()->startOfDay();
        while ($cursor->lte($end)) {
            $days->push($cursor->toDateString());
            $cursor->addDay();
        }

        $dailyReports = $days->map(function (string $day) use ($paymentDefs, $speedshopId, $warehouseId, $speedshopIds) {
            $dayStart = \Carbon\Carbon::parse($day)->startOfDay();
            $dayEnd = \Carbon\Carbon::parse($day)->endOfDay();

            $orderIds = $this->summaryServiceOrderBaseQuery($speedshopId, $warehouseId, $speedshopIds)
                ->where('status', 'selesai')
                ->whereTransaksiDateBetween($day, $day)
                ->pluck('id')
                ->all();

            $paymentTotals = collect(
                SpeedshopPendapatanBreakdown::toReportPaymentKeys(
                    SpeedshopPendapatanBreakdown::byMetodeFromServiceOrderIds($orderIds),
                ),
            );

            $pendapatanKotor = (float) $paymentTotals->sum();

            $biayaBreakdown = SpeedshopLabaRugiFinancials::biayaBreakdown($dayStart, $dayEnd, $speedshopId, $warehouseId, $speedshopIds);
            $hppPart = (float) $biayaBreakdown['hpp_sparepart'];
            $biayaOperasional = (float) $biayaBreakdown['biaya_operasional'];
            $biayaNonOperasional = (float) $biayaBreakdown['biaya_non_operasional'];
            $totalBiaya = $hppPart + $biayaOperasional + $biayaNonOperasional;
            $totalGrossProfitBeforeHpp = $pendapatanKotor - $biayaOperasional - $biayaNonOperasional;

            $biayaOpByMetode = SpeedshopLabaRugiFinancials::rincianBiayaByPaymentKey(
                $dayStart,
                $dayEnd,
                $speedshopId,
                $warehouseId,
                SpeedshopRincianBiayaEntry::JENIS_BIAYA_OPERASIONAL,
                $speedshopIds,
            );
            $biayaNonOpByMetode = SpeedshopLabaRugiFinancials::rincianBiayaByPaymentKey(
                $dayStart,
                $dayEnd,
                $speedshopId,
                $warehouseId,
                SpeedshopRincianBiayaEntry::JENIS_BIAYA_NON_OPERASIONAL,
                $speedshopIds,
            );

            $rows = collect($paymentDefs)->map(function (string $label, string $key) use (
                $paymentTotals,
                $biayaOpByMetode,
                $biayaNonOpByMetode,
            ) {
                $pendapatan = (float) ($paymentTotals[$key] ?? 0);
                $biayaOpRow = (float) ($biayaOpByMetode[$key] ?? 0);
                $biayaNonOpRow = (float) ($biayaNonOpByMetode[$key] ?? 0);
                $biayaRow = $biayaOpRow + $biayaNonOpRow;
                $gross = $pendapatan - $biayaRow;

                return (object) [
                    'label' => $label,
                    'pendapatan' => $pendapatan,
                    'biaya_operasional' => $biayaOpRow,
                    'biaya_non_operasional' => $biayaNonOpRow,
                    'biaya_total' => $biayaRow,
                    'gross_profit' => $gross,
                ];
            })->values();

            return (object) [
                'tanggal' => $day,
                'rows' => $rows,
                'pendapatan_kotor' => $pendapatanKotor,
                'hpp_part' => $hppPart,
                'biaya_operasional' => $biayaOperasional,
                'biaya_non_operasional' => $biayaNonOperasional,
                'total_biaya' => $totalBiaya,
                'total_gross_profit_before_hpp' => $totalGrossProfitBeforeHpp,
            ];
        })->values();

        $periodeBiaya = SpeedshopLabaRugiFinancials::pendapatanBiayaRow($dari, $sampai, $speedshopId, $warehouseId, $speedshopIds);
        $financials = SpeedshopLabaRugiFinancials::compute($dari, $sampai, $speedshopId, $warehouseId, $speedshopIds);

        return compact(
            'speedshop', 'dailyReports', 'dari', 'sampai',
            'mode', 'selectedDate', 'selectedMonth', 'selectedYear', 'laporanAllSpeedshops',
        ) + [
            'selectedDari' => $period['selectedDari'],
            'selectedSampai' => $period['selectedSampai'],
            'summaryOmzetKotor' => (float) $periodeBiaya['omzet_kotor'],
            'summaryTotalBiaya' => (float) $financials['total_biaya'],
            'summaryTotalBiayaSparepart' => (float) $financials['biaya_hpp_sparepart'],
            'summaryTotalBiayaBop' => (float) $financials['biaya_operasional'] + (float) $financials['biaya_non_operasional'],
            'summaryGrossProfit' => (float) $periodeBiaya['profit_bersih'],
        ];
    }

    /**
     * @param  list<int>  $speedshopIds
     * @return \Illuminate\Database\Eloquent\Builder<ServiceOrder>
     */
    protected function summaryServiceOrderBaseQuery(?int $speedshopId, ?int $warehouseId, array $speedshopIds = [])
    {
        $ids = $speedshopIds !== []
            ? array_values(array_map('intval', $speedshopIds))
            : ($speedshopId ? [(int) $speedshopId] : []);

        return ServiceOrder::query()
            ->when(
                $ids !== [] && Schema::hasColumn('service_orders', 'speedshop_id'),
                function ($q) use ($ids) {
                    if (count($ids) === 1) {
                        $q->where('speedshop_id', $ids[0]);
                    } else {
                        $q->whereIn('speedshop_id', $ids);
                    }
                },
                fn ($q) => $q->when($warehouseId, fn ($q2) => $q2->where('warehouse_id', $warehouseId)),
            );
    }

    public function rincianBiaya(Request $request)
    {
        $period = $this->resolveLaporanPeriodeMode($request, 'bulanan');
        $dari = $period['dari'];
        $sampai = $period['sampai'];
        $mode = $period['mode'];
        $selectedDate = $period['selectedDate'];
        $selectedMonth = $period['selectedMonth'];
        $selectedYear = $period['selectedYear'];
        $selectedDari = $period['selectedDari'];
        $selectedSampai = $period['selectedSampai'];

        $keuanganScope = $this->isWarehouseKeuanganContext() ? $this->resolveKeuanganScope() : null;
        if ($this->isWarehouseKeuanganContext()) {
            if (! $keuanganScope) {
                return redirect()->route($this->laporanDashboardRoute())->with('error', $this->keuanganScopeErrorMessage());
            }
            $speedshopIds = [];
        } else {
            $speedshopIds = $this->speedshopScopeIds();
            if ($speedshopIds === []) {
                return redirect()->route($this->laporanDashboardRoute())->with('error', 'Speedshop tidak terdeteksi.');
            }
        }

        $writableSpeedshopId = $this->isWarehouseKeuanganContext() ? null : $this->writableSpeedshopId();
        $saldoKasKecilBuku = $keuanganScope
            ? $this->saldoKasKecilLedgerBukuForScope($keuanganScope)
            : $this->saldoKasKecilLedgerBuku($writableSpeedshopId);
        $isAccounting = auth()->user()?->isAccounting() ?? false;
        $hasRekeningCol = Schema::hasColumn('speedshop_rincian_biaya_entries', 'speedshop_keuangan_rekening_id');
        $actor = auth()->user();
        // Manager / Outlet / Staf / FLP / Mekanik (isOutlet mencakup FLP & Staf cabang)
        $canSubmitBiaya = ! $isAccounting
            && $actor
            && ($actor->isManager() || $actor->isOutlet())
            && ! $actor->isModifikasiOriHeadAudit()
            && ($writableSpeedshopId !== null || $keuanganScope !== null);
        $canSubmitBiayaAccounting = $isAccounting
            && ! ($actor?->isModifikasiOriHeadAudit() ?? false)
            && ($writableSpeedshopId !== null || $keuanganScope !== null)
            && $hasRekeningCol
            && Schema::hasTable('speedshop_finance_ledgers');
        $canEditAuditTemuan = (bool) ($actor?->isModifikasiOriHeadAudit());
        $hasAuditTemuanColumns = Schema::hasColumn('speedshop_rincian_biaya_entries', 'audit_has_temuan');

        $keuanganRekeningsForBiaya = collect();
        $rekeningSaldoMap = [];
        if ($canSubmitBiayaAccounting && ($keuanganScope || $writableSpeedshopId)) {
            $finance = app(SpeedshopFinanceService::class);
            $asOfSaldo = now()->toDateString();
            $rekQuery = $keuanganScope
                ? $keuanganScope->applyToRekening(SpeedshopKeuanganRekening::query())
                : SpeedshopKeuanganRekening::query()->where('speedshop_id', $writableSpeedshopId);
            $keuanganRekeningsForBiaya = $rekQuery
                ->orderBy('jenis')
                ->orderBy('nama')
                ->get();
            foreach ($keuanganRekeningsForBiaya as $r) {
                $pocket = $r->isRekeningUtama()
                    ? SpeedshopFinanceService::POCKET_REKENING
                    : SpeedshopFinanceService::POCKET_CASH_BESAR;
                $rekeningSaldoMap[(int) $r->id] = $keuanganScope
                    ? $finance->balanceForBankPocketAsOfScope($keuanganScope, $pocket, (int) $r->id, $asOfSaldo)
                    : $finance->balanceForBankPocketAsOf((int) $writableSpeedshopId, $pocket, (int) $r->id, $asOfSaldo);
            }
            if ($keuanganScope?->isWarehouse()) {
                $rekeningSaldoMap[SpeedshopKeuanganRekening::SUMBER_TUNAI_VALUE] = $finance->balancePocketAsOfScope(
                    $keuanganScope,
                    SpeedshopFinanceService::POCKET_LACI,
                    $asOfSaldo,
                );
            }
        }

        $approvalStatusFilter = (string) $request->query('approval_status', '');
        $isVerifikasiBiayaOp = $request->routeIs($this->verifikasiBiayaOperasionalRoute());
        if ($isVerifikasiBiayaOp && $approvalStatusFilter === '') {
            $approvalStatusFilter = SpeedshopRincianBiayaEntry::STATUS_PENDING;
        }

        $entriesQuery = SpeedshopRincianBiayaEntry::query()
            ->whereBetween('tanggal', [$dari->toDateString(), $sampai->toDateString()]);
        if ($keuanganScope) {
            $keuanganScope->applyToRincianBiaya($entriesQuery);
        } else {
            $entriesQuery->whereIn('speedshop_id', $speedshopIds);
        }

        if ($approvalStatusFilter === SpeedshopRincianBiayaEntry::STATUS_PENDING) {
            $entriesQuery->where('approval_status', SpeedshopRincianBiayaEntry::STATUS_PENDING);
        } elseif ($approvalStatusFilter === SpeedshopRincianBiayaEntry::STATUS_REJECTED) {
            $entriesQuery->where('approval_status', SpeedshopRincianBiayaEntry::STATUS_REJECTED);
        } elseif ($approvalStatusFilter === SpeedshopRincianBiayaEntry::STATUS_APPROVED) {
            $entriesQuery->where(function ($q) {
                $q->whereNull('approval_status')
                    ->orWhere('approval_status', SpeedshopRincianBiayaEntry::STATUS_APPROVED);
            });
        }

        $this->applyRincianBiayaTableColumnFilters($entriesQuery, $request, $hasRekeningCol);

        $entries = $entriesQuery
            ->with(['keuanganRekening', 'submittedBy', 'adjustments'])
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get();

        $mappedRows = $entries
            ->map(function (SpeedshopRincianBiayaEntry $row) use ($hasRekeningCol, $keuanganScope, $speedshopIds, $hasAuditTemuanColumns) {
                $isAccountingRekening = $hasRekeningCol && (int) ($row->speedshop_keuangan_rekening_id ?? 0) > 0;
                $isAccountingDirectPosted = $isAccountingRekening && (int) ($row->finance_ledger_id ?? 0) > 0;
                $isCash = ($row->metode ?? 'tunai') === 'tunai' && ! $isAccountingRekening;
                $isAccountingTunaiDirect = $isCash
                    && (int) ($row->finance_ledger_id ?? 0) > 0
                    && $row->isApprovedForReporting();

                $jenis = (string) ($row->jenis_operasional ?? SpeedshopRincianBiayaEntry::JENIS_BIAYA_OPERASIONAL);
                $jenisLabel = $jenis === SpeedshopRincianBiayaEntry::JENIS_BIAYA_NON_OPERASIONAL
                    ? 'Biaya Non Operasional'
                    : 'Biaya Operasional';

                $st = $row->approval_status;
                $pendingVerifier = null;
                if ($row->isPending()) {
                    if ($row->isPendingAccountingRekeningAwaitingManager()) {
                        $pendingVerifier = 'manager_accounting_rekening';
                    } elseif ($isCash && ! $isAccountingRekening) {
                        $pendingVerifier = $row->pendingKasKecilRequiresManagerVerification() ? 'manager' : 'accounting';
                    }
                }
                $statusLabel = match ($st) {
                    SpeedshopRincianBiayaEntry::STATUS_PENDING => $pendingVerifier === 'manager' || $pendingVerifier === 'manager_accounting_rekening'
                        ? 'Menunggu Manager'
                        : ($pendingVerifier === 'accounting' ? 'Menunggu Accounting' : 'Menunggu verifikasi'),
                    SpeedshopRincianBiayaEntry::STATUS_REJECTED => 'Ditolak',
                    SpeedshopRincianBiayaEntry::STATUS_APPROVED => $isAccountingRekening
                        ? 'Tercatat (rekening)'
                        : ($isAccountingTunaiDirect ? 'Tercatat (tunai)' : 'Disetujui'),
                    default => 'Disetujui',
                };

                $rek = $row->keuanganRekening;
                $rekeningLabel = $rek
                    ? trim(($rek->nama_bank ? (string) $rek->nama_bank.' ' : '').(string) $rek->nama)
                    : ($isAccountingTunaiDirect ? 'Tunai' : null);

                $countsTowardTotal = $row->isApprovedForReporting();
                $uid = auth()->id();
                $canDeletePending = $row->isPending() && (
                    auth()->user()?->isManager()
                    || (int) ($row->submitted_by ?? 0) === (int) $uid
                );
                $ledgerId = (int) ($row->finance_ledger_id ?? 0);

                // Samakan dengan entryInSpeedshopScope / approve: boleh aksi jika entri
                // masuk scope laporan (termasuk filter "Semua Speedshop"), bukan hanya cabang aktif.
                $entryInActiveScope = $keuanganScope
                    ? $keuanganScope->ownsRincianBiaya($row)
                    : in_array((int) $row->speedshop_id, $speedshopIds, true);

                $showKasKecilVerifikasi = $st === SpeedshopRincianBiayaEntry::STATUS_PENDING
                    && $isCash
                    && ! $isAccountingRekening
                    && $entryInActiveScope;
                if ($showKasKecilVerifikasi) {
                    $nom = (int) round((float) ($row->nominal ?? 0));
                    $u = auth()->user();
                    if ($nom <= SpeedshopRincianBiayaEntry::KAS_KECIL_VERIFIKASI_ACCOUNTING_MAX_NOMINAL) {
                        $showKasKecilVerifikasi = $u?->isAccounting() ?? false;
                    } else {
                        $showKasKecilVerifikasi = $u?->isManager() ?? false;
                    }
                }

                $showAccountingRekeningVerifikasi = $st === SpeedshopRincianBiayaEntry::STATUS_PENDING
                    && $row->isPendingAccountingRekeningAwaitingManager()
                    && $entryInActiveScope
                    && (auth()->user()?->isManager() ?? false);

                $showVerifikasiActions = $showKasKecilVerifikasi || $showAccountingRekeningVerifikasi;

                $canDeleteAccountingPosted = $row->isApprovedForReporting()
                    && ! $row->isPending()
                    && $ledgerId > 0
                    && (auth()->user()?->isAccounting() || auth()->user()?->isManager())
                    && (
                        ($hasRekeningCol && $isAccountingRekening)
                        || $isAccountingTunaiDirect
                    );
                $canDelete = $canDeletePending || $canDeleteAccountingPosted;

                $buktiLampiranUrl = null;
                if (Schema::hasColumn('speedshop_rincian_biaya_entries', 'bukti_lampiran_path')) {
                    $bp = $row->bukti_lampiran_path ?? null;
                    if (is_string($bp) && $bp !== '') {
                        $buktiLampiranUrl = $row->buktiLampiranPublicUrl();
                    }
                }

                $nominalAsli = (float) ($row->nominal ?? 0);
                $nominalEfektif = (float) $row->nominalEfektif();
                $displayNominal = $nominalEfektif > 0 ? $nominalEfektif : $nominalAsli;

                return (object) [
                    'entry_id' => $row->id,
                    'tanggal' => (string) $row->tanggal,
                    'jenis_operasional' => $jenis,
                    'jenis_label' => $jenisLabel,
                    'no_akun' => (string) $row->no_akun,
                    'nama_akun' => (string) $row->nama_akun,
                    'keterangan' => (string) ($row->keterangan ?? '-'),
                    'pic' => (string) ($row->pic ?? '-'),
                    'pengaju' => (string) ($row->submittedBy?->name ?? '-'),
                    'tunai' => $isCash ? $displayNominal : 0.0,
                    'transfer' => $isCash ? 0.0 : $displayNominal,
                    'nominal_asli' => $nominalAsli,
                    'nominal_efektif' => $nominalEfektif,
                    'has_penyesuaian' => $nominalEfektif < $nominalAsli,
                    'rekening_label' => $rekeningLabel,
                    'is_accounting_direct' => $isAccountingDirectPosted,
                    'approval_status' => $st,
                    'status_label' => $statusLabel,
                    'pending_verifier' => $pendingVerifier,
                    'counts_toward_total' => $countsTowardTotal,
                    'show_accounting_actions' => $showVerifikasiActions,
                    'approve_url' => $showVerifikasiActions
                        ? $this->keuanganRoute('biaya-operasional.approve', ['entry' => $row->id])
                        : null,
                    'reject_url' => $showVerifikasiActions
                        ? $this->keuanganRoute('biaya-operasional.reject', ['entry' => $row->id])
                        : null,
                    'can_delete' => $canDelete,
                    'delete_url' => $canDelete
                        ? $this->keuanganRoute('biaya-operasional.destroy', ['entry' => $row->id])
                        : null,
                    'bukti_lampiran_url' => $buktiLampiranUrl,
                    'audit_has_temuan' => $hasAuditTemuanColumns ? (bool) ($row->audit_has_temuan ?? false) : false,
                    'audit_deskripsi' => $hasAuditTemuanColumns ? (string) ($row->audit_deskripsi ?? '') : '',
                    'audit_penyelesaian' => $hasAuditTemuanColumns ? (string) ($row->audit_penyelesaian ?? '') : '',
                    'audit_temuan_url' => $hasAuditTemuanColumns
                        ? $this->keuanganRoute('biaya-operasional.audit-temuan', ['entry' => $row->id])
                        : null,
                ];
            })
            ->sortBy(['tanggal', 'jenis_operasional', 'no_akun', 'keterangan'])
            ->values();

        $dailyGroups = $mappedRows
            ->groupBy('tanggal')
            ->map(function ($rows, $tanggal) {
                $rows = $rows->values();

                return (object) [
                    'tanggal' => $tanggal,
                    'rows' => $rows,
                    'total_harian' => $rows->where('counts_toward_total', true)->sum(fn ($r) => $r->tunai + $r->transfer),
                ];
            })
            ->values();

        $biayaStats = [
            'pending' => $mappedRows->where('approval_status', SpeedshopRincianBiayaEntry::STATUS_PENDING)->count(),
            'disetujui' => $mappedRows->where('counts_toward_total', true)->count(),
            'total_nominal' => (int) round($mappedRows->where('counts_toward_total', true)->sum(fn ($r) => $r->tunai + $r->transfer)),
        ];

        $akunAll = ($keuanganScope
            ? AkunOperasional::queryForKeuanganScope($keuanganScope)
            : AkunOperasional::queryForScope($writableSpeedshopId, null))
            ->orderBy('no_akun')
            ->get();
        $akunOperasionalList = $akunAll->filter(fn (AkunOperasional $a) => $this->akunMatchesOperasionalBiaya((string) $a->no_akun))->values();
        $akunNonOperasionalList = $akunAll->filter(fn (AkunOperasional $a) => $this->akunMatchesNonOperasionalBiaya((string) $a->no_akun))->values();
        $hasAnyAkunForBiaya = $akunOperasionalList->isNotEmpty() || $akunNonOperasionalList->isNotEmpty();
        $akunByJenis = [
            'operasional' => $akunOperasionalList->map(fn (AkunOperasional $a) => [
                'id' => $a->id,
                'no' => $a->no_akun,
                'nama' => $a->nama_akun,
                'requires_rekening_tujuan' => $a->requiresRekeningTujuan(),
            ])->values()->all(),
            'non_operasional' => $akunNonOperasionalList->map(fn (AkunOperasional $a) => [
                'id' => $a->id,
                'no' => $a->no_akun,
                'nama' => $a->nama_akun,
                'requires_rekening_tujuan' => $a->requiresRekeningTujuan(),
            ])->values()->all(),
        ];

        return view($this->rincianBiayaViewName(), compact(
            'dailyGroups',
            'biayaStats',
            'dari',
            'sampai',
            'akunByJenis',
            'hasAnyAkunForBiaya',
            'mode',
            'selectedDate',
            'selectedMonth',
            'selectedYear',
            'selectedDari',
            'selectedSampai',
            'saldoKasKecilBuku',
            'isAccounting',
            'canSubmitBiaya',
            'canSubmitBiayaAccounting',
            'canEditAuditTemuan',
            'hasAuditTemuanColumns',
            'keuanganRekeningsForBiaya',
            'rekeningSaldoMap',
            'approvalStatusFilter',
        ));
    }

    public function updateRincianBiayaAuditTemuan(Request $request, SpeedshopRincianBiayaEntry $entry)
    {
        $user = auth()->user();
        if (! $user?->isModifikasiOriHeadAudit()) {
            abort(403, 'Hanya Head Audit yang dapat mengisi temuan kasus.');
        }

        if (! Schema::hasColumn('speedshop_rincian_biaya_entries', 'audit_has_temuan')) {
            return back()->with('error', 'Kolom temuan audit belum tersedia. Jalankan migrasi database terlebih dahulu.');
        }

        if (! $this->entryInSpeedshopScope($entry)) {
            return back()->with('error', 'Data rincian biaya tidak ditemukan untuk scope ini.');
        }

        $validated = $request->validate([
            'audit_deskripsi' => ['required', 'string', 'max:2000'],
            'audit_penyelesaian' => ['required', 'string', 'max:2000'],
        ], [
            'audit_deskripsi.required' => 'Deskripsi wajib diisi.',
            'audit_penyelesaian.required' => 'Penyelesaian wajib diisi.',
        ]);

        $deskripsi = trim((string) $validated['audit_deskripsi']);
        $penyelesaian = trim((string) $validated['audit_penyelesaian']);

        $entry->update([
            'audit_has_temuan' => true,
            'audit_deskripsi' => $deskripsi,
            'audit_penyelesaian' => $penyelesaian,
            'audit_by' => $user->id,
            'audit_at' => now(),
        ]);

        $filterQuery = collect($request->only(array_merge(
            ['mode', 'tanggal', 'bulan', 'tahun', 'dari', 'sampai', 'approval_status'],
            $this->rincianBiayaTableColumnFilterKeys(),
        )))->filter(fn ($v) => $v !== null && $v !== '')->all();

        return redirect()->route($this->keuanganBiayaOperasionalRoute(), $filterQuery)
            ->with('success', 'Temuan kasus berhasil disimpan (Deskripsi & Penyelesaian).');
    }

    public function storeRincianBiaya(Request $request, SpeedshopFinanceService $finance)
    {
        $keuanganScope = $this->isWarehouseKeuanganContext() ? $this->resolveKeuanganScope() : null;
        $speedshopId = $keuanganScope ? null : $this->writableSpeedshopId();
        if (! $keuanganScope && ! $speedshopId) {
            return back()->with('error', $this->isWarehouseKeuanganContext()
                ? $this->keuanganScopeErrorMessage()
                : 'Pilih speedshop spesifik dulu sebelum menambah rincian biaya.');
        }

        if (auth()->user()?->isAccounting()) {
            if (! Schema::hasColumn('speedshop_rincian_biaya_entries', 'speedshop_keuangan_rekening_id')) {
                return back()->with('error', 'Kolom rekening belum tersedia. Jalankan migrasi database terlebih dahulu.');
            }
            if (! Schema::hasTable('speedshop_finance_ledgers')) {
                return back()->with('error', 'Modul jurnal keuangan belum tersedia.');
            }

            $validated = $request->validate([
                'tanggal' => ['required', 'date'],
                'jenis_operasional' => ['required', 'in:operasional,non_operasional'],
                'akun_operasional_id' => [
                    'required',
                    'integer',
                    Rule::exists('akun_operasionals', 'id')->where(function ($q) use ($keuanganScope, $speedshopId) {
                        if ($keuanganScope) {
                            $keuanganScope->isWarehouse()
                                ? $q->where('warehouse_id', $keuanganScope->warehouseId)->whereNull('speedshop_id')
                                : $q->whereNull('warehouse_id')->where(function ($q2) use ($keuanganScope) {
                                    $q2->where('speedshop_id', $keuanganScope->speedshopId)->orWhereNull('speedshop_id');
                                });
                        } else {
                            $q->whereNull('warehouse_id')->where(function ($q2) use ($speedshopId) {
                                $q2->where('speedshop_id', $speedshopId)->orWhereNull('speedshop_id');
                            });
                        }
                    }),
                ],
                'keuangan_rekening_id' => [
                    'required',
                    function (string $attribute, mixed $value, \Closure $fail) use ($keuanganScope, $speedshopId): void {
                        if ($value === SpeedshopKeuanganRekening::SUMBER_TUNAI_VALUE) {
                            if (! $keuanganScope?->isWarehouse()) {
                                $fail('Pembayaran tunai hanya tersedia untuk gudang.');
                            }

                            return;
                        }
                        if (! is_numeric($value) || (int) $value < 1) {
                            $fail('Rekening sumber tidak valid.');

                            return;
                        }
                        $q = SpeedshopKeuanganRekening::query()->where('id', (int) $value);
                        if ($keuanganScope) {
                            $keuanganScope->applyToRekening($q);
                        } else {
                            $q->where('speedshop_id', $speedshopId)->whereNull('warehouse_id');
                        }
                        if (! $q->exists()) {
                            $fail('Rekening sumber tidak ditemukan.');
                        }
                    },
                ],
                'keterangan' => ['nullable', 'string', 'max:255'],
                'pic' => $keuanganScope?->isWarehouse()
                    ? ['required', 'string', 'max:100']
                    : ['nullable', 'string', 'max:100'],
                'nominal' => ['required', 'numeric', 'min:0.01'],
                'bukti_lampiran' => Schema::hasColumn('speedshop_rincian_biaya_entries', 'bukti_lampiran_path')
                    ? ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120']
                    : ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            ]);

            $akun = AkunOperasional::query()->findOrFail((int) $validated['akun_operasional_id']);
            $noAkun = (string) $akun->no_akun;
            $isNon = $validated['jenis_operasional'] === SpeedshopRincianBiayaEntry::JENIS_BIAYA_NON_OPERASIONAL;
            $akunError = $this->validateRincianBiayaAkun($noAkun, $isNon);
            if ($akunError !== null) {
                return back()
                    ->withErrors(['akun_operasional_id' => $akunError])
                    ->withInput();
            }

            $tanggalYmd = \Carbon\Carbon::parse($validated['tanggal'])->toDateString();
            if ($tanggalYmd > now()->toDateString()) {
                return back()->withErrors(['tanggal' => 'Tanggal tidak boleh di masa depan.'])->withInput();
            }

            $nominalInt = (int) round((float) $validated['nominal']);
            if ($nominalInt < 1) {
                return back()->withErrors(['nominal' => 'Nominal tidak valid.'])->withInput();
            }

            $picName = filled($validated['pic'] ?? null)
                ? trim((string) $validated['pic'])
                : (string) (auth()->user()?->name ?? 'Accounting');

            $isTunaiSource = $validated['keuangan_rekening_id'] === SpeedshopKeuanganRekening::SUMBER_TUNAI_VALUE;
            $rekening = $isTunaiSource
                ? null
                : SpeedshopKeuanganRekening::query()->findOrFail((int) $validated['keuangan_rekening_id']);

            $tujuanResolved = $this->resolveAccountingBiayaRekeningTujuan(
                $request,
                $akun,
                $keuanganScope,
                $speedshopId,
                $isTunaiSource,
                $rekening,
            );
            if (isset($tujuanResolved['error'])) {
                return back()
                    ->withErrors([$tujuanResolved['field'] ?? 'keuangan_rekening_tujuan_id' => $tujuanResolved['error']])
                    ->withInput();
            }
            $rekeningTujuan = $tujuanResolved['rekening'] ?? null;

            $buktiPath = null;
            try {
                $buktiPath = $this->storedBuktiLampiranPathOrNull($request, $speedshopId, $keuanganScope);
            } catch (\InvalidArgumentException $e) {
                return back()->withInput()->with('error', $e->getMessage());
            }

            $needsManagerApproval = ! $isTunaiSource
                && $nominalInt > SpeedshopRincianBiayaEntry::KAS_KECIL_VERIFIKASI_ACCOUNTING_MAX_NOMINAL;

            $createdEntry = null;
            try {
                DB::transaction(function () use (
                    $finance,
                    $keuanganScope,
                    $speedshopId,
                    $tanggalYmd,
                    $nominalInt,
                    $validated,
                    $akun,
                    $rekening,
                    $rekeningTujuan,
                    $buktiPath,
                    $needsManagerApproval,
                    $isTunaiSource,
                    $picName,
                    &$createdEntry,
                ) {
                    $attrs = array_merge(
                        $keuanganScope ? $keuanganScope->ownershipAttributes() : ['speedshop_id' => $speedshopId, 'warehouse_id' => null],
                        [
                        'tanggal' => $tanggalYmd,
                        'jenis_operasional' => $validated['jenis_operasional'],
                        'no_akun' => $akun->no_akun,
                        'nama_akun' => $akun->nama_akun,
                        'keterangan' => $validated['keterangan'] ?? null,
                        'pic' => $picName,
                        'metode' => $isTunaiSource ? 'tunai' : 'transfer',
                        'speedshop_keuangan_rekening_id' => $isTunaiSource ? null : (int) $rekening->id,
                        'nominal' => (float) $validated['nominal'],
                        'rejection_note' => null,
                        ],
                    );
                    if (Schema::hasColumn('speedshop_rincian_biaya_entries', 'speedshop_keuangan_rekening_tujuan_id')) {
                        $attrs['speedshop_keuangan_rekening_tujuan_id'] = $rekeningTujuan ? (int) $rekeningTujuan->id : null;
                    }
                    if ($needsManagerApproval) {
                        $attrs['approval_status'] = SpeedshopRincianBiayaEntry::STATUS_PENDING;
                        $attrs['submitted_by'] = auth()->id();
                        $attrs['approved_by'] = null;
                        $attrs['approved_at'] = null;
                    } else {
                        $attrs['approval_status'] = SpeedshopRincianBiayaEntry::STATUS_APPROVED;
                        $attrs['submitted_by'] = auth()->id();
                        $attrs['approved_by'] = auth()->id();
                        $attrs['approved_at'] = now();
                    }
                    if (Schema::hasColumn('speedshop_rincian_biaya_entries', 'bukti_lampiran_path')) {
                        $attrs['bukti_lampiran_path'] = $buktiPath;
                    }
                    $entry = SpeedshopRincianBiayaEntry::query()->create($attrs);

                    if (! $needsManagerApproval) {
                        $ledgers = $this->postApprovedAccountingRekeningBiayaLedgers(
                            $finance,
                            $keuanganScope,
                            $speedshopId,
                            $entry,
                            $isTunaiSource,
                            $rekening,
                            $rekeningTujuan,
                            $tanggalYmd,
                            $nominalInt,
                        );

                        $updateLedger = ['finance_ledger_id' => $ledgers['keluar']->id];
                        if (Schema::hasColumn('speedshop_rincian_biaya_entries', 'finance_ledger_tujuan_id') && $ledgers['tujuan']) {
                            $updateLedger['finance_ledger_tujuan_id'] = $ledgers['tujuan']->id;
                        }
                        $entry->update($updateLedger);
                    }
                    $createdEntry = $entry;
                });
            } catch (\InvalidArgumentException $e) {
                if ($buktiPath) {
                    Storage::disk((string) config('filesystems.upload_disk', 'public'))->delete($buktiPath);
                }

                return back()->withErrors(['nominal' => $e->getMessage()])->withInput();
            } catch (\Throwable $e) {
                if ($buktiPath) {
                    Storage::disk((string) config('filesystems.upload_disk', 'public'))->delete($buktiPath);
                }
                report($e);

                return back()->with('error', 'Gagal mencatat biaya: '.$e->getMessage());
            }

            if ($createdEntry) {
                $fresh = $createdEntry->fresh();
                $pendingAccountingRek = $fresh->isPendingAccountingRekeningAwaitingManager();
                if ($pendingAccountingRek) {
                    $this->notifyManagersAccountingRekeningBiayaPending($fresh, $speedshopId, $rekening);
                } else {
                    app(SpeedshopJurnalAutomationService::class)->syncRincianBiayaPengeluaranJurnal($fresh);
                }
            }

            $filterQuery = $this->rincianBiayaRedirectQuery(
                $request,
                $tanggalYmd,
                $createdEntry?->fresh(),
            );

            $pendingAccountingRek = $createdEntry && $createdEntry->fresh()->isPendingAccountingRekeningAwaitingManager();
            $successMsg = $pendingAccountingRek
                ? 'Pengajuan biaya (rekening) tersimpan dan menunggu persetujuan Manager cabang (nominal di atas Rp '.number_format(SpeedshopRincianBiayaEntry::KAS_KECIL_VERIFIKASI_ACCOUNTING_MAX_NOMINAL, 0, ',', '.').'). Saldo buku belum dipotong hingga disetujui.'
                : ($rekeningTujuan
                    ? 'Biaya operasional tercatat. Saldo rekening sumber berkurang dan saldo rekening tujuan bertambah.'
                    : ($isTunaiSource
                        ? 'Biaya operasional tercatat dan saldo Tunai telah dikurangi.'
                        : 'Biaya operasional tercatat dan saldo buku rekening telah dikurangi.'));

            return redirect()->route($this->keuanganBiayaOperasionalRoute(), $filterQuery)
                ->with('success', $successMsg);
        }

        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
            'jenis_operasional' => ['required', 'in:operasional,non_operasional'],
            'akun_operasional_id' => [
                'required',
                'integer',
                Rule::exists('akun_operasionals', 'id')->where(function ($q) use ($keuanganScope, $speedshopId) {
                    if ($keuanganScope) {
                        $keuanganScope->isWarehouse()
                            ? $q->where('warehouse_id', $keuanganScope->warehouseId)->whereNull('speedshop_id')
                            : $q->whereNull('warehouse_id')->where(function ($q2) use ($keuanganScope) {
                                $q2->where('speedshop_id', $keuanganScope->speedshopId)->orWhereNull('speedshop_id');
                            });
                    } else {
                        $q->whereNull('warehouse_id')->where(function ($q2) use ($speedshopId) {
                            $q2->where('speedshop_id', $speedshopId)->orWhereNull('speedshop_id');
                        });
                    }
                }),
            ],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'nominal' => ['required', 'numeric', 'min:0.01'],
            'bukti_lampiran' => Schema::hasColumn('speedshop_rincian_biaya_entries', 'bukti_lampiran_path')
                ? ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120']
                : ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);

        $akun = AkunOperasional::query()->findOrFail((int) $validated['akun_operasional_id']);
        $noAkun = (string) $akun->no_akun;
        $isNon = $validated['jenis_operasional'] === SpeedshopRincianBiayaEntry::JENIS_BIAYA_NON_OPERASIONAL;
        $akunError = $this->validateRincianBiayaAkun($noAkun, $isNon);
        if ($akunError !== null) {
            return back()
                ->withErrors(['akun_operasional_id' => $akunError])
                ->withInput();
        }

        $tanggalYmd = \Carbon\Carbon::parse($validated['tanggal'])->toDateString();
        if ($tanggalYmd > now()->toDateString()) {
            return back()->withErrors(['tanggal' => 'Tanggal tidak boleh di masa depan.'])->withInput();
        }

        $nominalInt = (int) round((float) $validated['nominal']);
        if ($nominalInt < 1) {
            return back()->withErrors(['nominal' => 'Nominal tidak valid.'])->withInput();
        }

        if (Schema::hasTable('speedshop_finance_ledgers')) {
            $saldo = $keuanganScope
                ? $finance->balancePocketAsOfScope(
                    $keuanganScope,
                    SpeedshopFinanceService::POCKET_LACI,
                    now()->toDateString(),
                )
                : $finance->balancePocketAsOf(
                    (int) $speedshopId,
                    SpeedshopFinanceService::POCKET_LACI,
                    now()->toDateString(),
                );
            if ($saldo < $nominalInt) {
                return back()
                    ->withErrors(['nominal' => 'Saldo Kas Kecil (buku) tidak mencukupi untuk nominal ini (tersedia Rp '.number_format($saldo, 0, ',', '.').').'])
                    ->withInput();
            }
        }

        $buktiPath = null;
        try {
            $buktiPath = $this->storedBuktiLampiranPathOrNull($request, $speedshopId, $keuanganScope);
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $entryAttrs = array_merge(
            $keuanganScope
                ? $keuanganScope->ownershipAttributes()
                : ['speedshop_id' => $speedshopId, 'warehouse_id' => null],
            [
            'tanggal' => $tanggalYmd,
            'jenis_operasional' => $validated['jenis_operasional'],
            'no_akun' => $akun->no_akun,
            'nama_akun' => $akun->nama_akun,
            'keterangan' => $validated['keterangan'] ?? null,
            'pic' => $keuanganScope ? (string) (auth()->user()?->name ?? 'Warehouse') : self::RINCIAN_BIAYA_PIC_KASIR,
            'metode' => 'tunai',
            'nominal' => (float) $validated['nominal'],
            'approval_status' => SpeedshopRincianBiayaEntry::STATUS_PENDING,
            'submitted_by' => auth()->id(),
            ],
        );
        if (Schema::hasColumn('speedshop_rincian_biaya_entries', 'bukti_lampiran_path')) {
            $entryAttrs['bukti_lampiran_path'] = $buktiPath;
        }

        try {
            $entry = SpeedshopRincianBiayaEntry::query()->create($entryAttrs);
        } catch (\Throwable $e) {
            if ($buktiPath) {
                Storage::disk((string) config('filesystems.upload_disk', 'public'))->delete($buktiPath);
            }
            report($e);

            return back()->withInput()->with('error', 'Gagal menyimpan pengajuan biaya.');
        }

        $cabangLabel = Speedshop::query()->whereKey($speedshopId ?: $keuanganScope?->speedshopId)->value('nama') ?? 'Cabang';
        $nominalFormatted = number_format($nominalInt, 0, ',', '.');
        $tanggalLabel = \Carbon\Carbon::parse($tanggalYmd)->format('d/m/Y');
        $pemohon = auth()->user()?->name ?? 'Pengguna';

        $this->notifyRincianBiayaPendingVerifiers(
            $entry,
            $keuanganScope ? null : $speedshopId,
            $pemohon,
            $nominalFormatted,
            $tanggalLabel,
            $cabangLabel,
            $keuanganScope,
        );

        $filterQuery = $this->rincianBiayaRedirectQuery($request, $tanggalYmd, $entry->fresh());

        return redirect()->route($this->keuanganBiayaOperasionalRoute(), $filterQuery)
            ->with('success', $nominalInt > SpeedshopRincianBiayaEntry::KAS_KECIL_VERIFIKASI_ACCOUNTING_MAX_NOMINAL
                ? 'Pengajuan biaya Kas Kecil tersimpan dan menunggu verifikasi Manager cabang (nominal di atas Rp '.number_format(SpeedshopRincianBiayaEntry::KAS_KECIL_VERIFIKASI_ACCOUNTING_MAX_NOMINAL, 0, ',', '.').').'
                : 'Pengajuan biaya Kas Kecil tersimpan dan menunggu verifikasi Accounting.');
    }

    public function destroyRincianBiaya(SpeedshopRincianBiayaEntry $entry)
    {
        if (! $this->entryInSpeedshopScope($entry)) {
            return back()->with('error', 'Data rincian biaya tidak ditemukan untuk scope ini.');
        }

        if ($entry->isPending()) {
            $uid = auth()->id();
            if (! auth()->user()?->isManager() && (int) ($entry->submitted_by ?? 0) !== (int) $uid) {
                return back()->with('error', 'Anda tidak dapat menghapus pengajuan ini.');
            }
            $this->deleteRincianBiayaBuktiFileIfAny($entry);
            $entry->delete();

            return back()->with('success', 'Pengajuan biaya berhasil dibatalkan.');
        }

        $hasRek = Schema::hasColumn('speedshop_rincian_biaya_entries', 'speedshop_keuangan_rekening_id')
            && (int) ($entry->speedshop_keuangan_rekening_id ?? 0) > 0;
        $isAccountingTunaiPosted = ($entry->metode ?? '') === 'tunai'
            && ! $hasRek
            && $entry->isApprovedForReporting();
        $ledgerId = (int) ($entry->finance_ledger_id ?? 0);
        if (($hasRek || $isAccountingTunaiPosted) && $ledgerId > 0 && $entry->isApprovedForReporting()) {
            if (! auth()->user()?->isAccounting() && ! auth()->user()?->isManager()) {
                return back()->with('error', 'Anda tidak berhak menghapus entri ini.');
            }
            if (! Schema::hasTable('speedshop_finance_ledgers')) {
                return back()->with('error', 'Modul jurnal keuangan belum tersedia.');
            }
            $fromTunai = $isAccountingTunaiPosted;
            try {
                DB::transaction(function () use ($entry, $ledgerId) {
                    $locked = SpeedshopRincianBiayaEntry::query()->lockForUpdate()->findOrFail($entry->id);
                    $lid = (int) ($locked->finance_ledger_id ?? 0);
                    if ($lid !== $ledgerId || $lid < 1) {
                        throw new \RuntimeException('Entri tidak konsisten dengan jurnal.');
                    }
                    app(SpeedshopJurnalAutomationService::class)->deleteJurnalForRincianBiaya((int) $locked->id);
                    $ledger = SpeedshopFinanceLedger::query()->find($lid);
                    if ($ledger) {
                        app(\App\Services\WarehouseJurnalAutomationService::class)->deleteForLedger($ledger);
                        $ledger->delete();
                    }
                    $tujuanLid = (int) ($locked->finance_ledger_tujuan_id ?? 0);
                    if ($tujuanLid > 0) {
                        $ledgerTujuan = SpeedshopFinanceLedger::query()->find($tujuanLid);
                        if ($ledgerTujuan) {
                            app(\App\Services\WarehouseJurnalAutomationService::class)->deleteForLedger($ledgerTujuan);
                            $ledgerTujuan->delete();
                        }
                    }
                    $this->deleteRincianBiayaBuktiFileIfAny($locked);
                    $locked->delete();
                });
            } catch (\Throwable $e) {
                report($e);

                return back()->with('error', 'Gagal membatalkan entri: '.$e->getMessage());
            }

            return back()->with('success', $fromTunai
                ? 'Biaya tunai dan baris jurnal terkait telah dihapus. Saldo Tunai dikembalikan.'
                : 'Biaya dari rekening dan baris jurnal terkait telah dihapus.');
        }

        return back()->with('error', 'Hanya pengajuan yang masih menunggu persetujuan yang dapat dihapus.');
    }

    public function approveRincianBiaya(Request $request, SpeedshopRincianBiayaEntry $entry, SpeedshopFinanceService $finance): \Illuminate\Http\RedirectResponse
    {
        if (! $this->entryInSpeedshopScope($entry)) {
            return back()->with('error', 'Data rincian biaya tidak ditemukan untuk scope ini.');
        }

        $entryScope = $this->resolveKeuanganScopeForEntry($entry);

        if ($entry->isPendingAccountingRekeningAwaitingManager()) {
            if (! $this->verifierCanApproveRejectAccountingRekeningBiayaPending($entry)) {
                return back()->with('error', 'Hanya Manager cabang yang dapat menyetujui pengajuan biaya dari rekening dengan nominal di atas Rp '.number_format(SpeedshopRincianBiayaEntry::KAS_KECIL_VERIFIKASI_ACCOUNTING_MAX_NOMINAL, 0, ',', '.').'.');
            }

            if (! $entry->isPending()) {
                return back()->with('error', 'Entri ini tidak dalam status menunggu persetujuan.');
            }

            if (! Schema::hasTable('speedshop_finance_ledgers')) {
                return back()->with('error', 'Modul jurnal keuangan belum tersedia.');
            }

            $rekeningId = (int) ($entry->speedshop_keuangan_rekening_id ?? 0);
            $rekening = SpeedshopKeuanganRekening::query()->find($rekeningId);
            $rekeningValid = $entryScope
                ? ($rekening && $entryScope->ownsRekening($rekening))
                : ($rekening && (int) $rekening->speedshop_id === (int) $entry->speedshop_id);
            if (! $rekeningValid) {
                return back()->with('error', 'Rekening tidak valid untuk entri ini.');
            }

            $rekeningTujuanId = (int) ($entry->speedshop_keuangan_rekening_tujuan_id ?? 0);
            $rekeningTujuan = $rekeningTujuanId > 0
                ? SpeedshopKeuanganRekening::query()->find($rekeningTujuanId)
                : null;
            if ($rekeningTujuanId > 0) {
                $tujuanValid = $entryScope
                    ? ($rekeningTujuan && $entryScope->ownsRekening($rekeningTujuan))
                    : ($rekeningTujuan && (int) $rekeningTujuan->speedshop_id === (int) $entry->speedshop_id);
                if (! $tujuanValid) {
                    return back()->with('error', 'Rekening tujuan tidak valid untuk entri ini.');
                }
            } elseif (AkunOperasional::requiresRekeningTujuanForNoAkun((string) $entry->no_akun)) {
                return back()->with('error', 'Pengajuan akun '.$entry->no_akun.' memerlukan rekening tujuan alokasi dana.');
            }

            $nominalInt = (int) round((float) $entry->nominal);
            if ($nominalInt < 1) {
                return back()->with('error', 'Nominal tidak valid.');
            }

            $tanggalYmd = $entry->tanggal instanceof \Carbon\Carbon
                ? $entry->tanggal->toDateString()
                : \Carbon\Carbon::parse($entry->tanggal)->toDateString();

            $pocket = $rekening->isRekeningUtama()
                ? SpeedshopFinanceService::POCKET_REKENING
                : SpeedshopFinanceService::POCKET_CASH_BESAR;
            $saldoAsOf = \Carbon\Carbon::today()->toDateString();
            $saldoRek = $entryScope
                ? $finance->balanceForBankPocketAsOfScope($entryScope, $pocket, (int) $rekening->id, $saldoAsOf)
                : $finance->balanceForBankPocketAsOf(
                    (int) $entry->speedshop_id,
                    $pocket,
                    (int) $rekening->id,
                    $saldoAsOf,
                );
            if ($saldoRek < $nominalInt) {
                return back()->with('error', 'Saldo buku rekening saat ini tidak mencukupi (tersedia Rp '.number_format($saldoRek, 0, ',', '.').').');
            }

            try {
                DB::transaction(function () use ($entry, $finance, $nominalInt, $rekening, $rekeningTujuan, $tanggalYmd, $entryScope) {
                    $locked = SpeedshopRincianBiayaEntry::query()->lockForUpdate()->findOrFail($entry->id);
                    if (! $locked->isPendingAccountingRekeningAwaitingManager()) {
                        throw new \RuntimeException('Entri sudah diproses atau tidak memerlukan persetujuan Manager (rekening).');
                    }
                    if ((int) ($locked->finance_ledger_id ?? 0) > 0) {
                        throw new \RuntimeException('Jurnal untuk entri ini sudah ada.');
                    }

                    $ledgers = $this->postApprovedAccountingRekeningBiayaLedgers(
                        $finance,
                        $entryScope,
                        $entryScope ? null : (int) $locked->speedshop_id,
                        $locked,
                        false,
                        $rekening,
                        $rekeningTujuan,
                        $tanggalYmd,
                        $nominalInt,
                    );

                    $update = [
                        'approval_status' => SpeedshopRincianBiayaEntry::STATUS_APPROVED,
                        'approved_by' => auth()->id(),
                        'approved_at' => now(),
                        'finance_ledger_id' => $ledgers['keluar']->id,
                        'rejection_note' => null,
                    ];
                    if (Schema::hasColumn('speedshop_rincian_biaya_entries', 'finance_ledger_tujuan_id') && $ledgers['tujuan']) {
                        $update['finance_ledger_tujuan_id'] = $ledgers['tujuan']->id;
                    }
                    $locked->update($update);
                });
            } catch (\Throwable $e) {
                report($e);

                return back()->with('error', 'Gagal memposting biaya dari rekening: '.$e->getMessage());
            }

            $fresh = SpeedshopRincianBiayaEntry::query()->find($entry->id);
            if ($fresh) {
                $this->notifySpeedshopRincianBiayaDecision($fresh, true, null);
                app(SpeedshopJurnalAutomationService::class)->syncRincianBiayaPengeluaranJurnal($fresh);
            }

            $filterQuery = array_filter(
                array_merge(
                    $request->only(['mode', 'tanggal', 'bulan', 'tahun']),
                    ['approval_status' => SpeedshopRincianBiayaEntry::STATUS_PENDING]
                ),
                fn ($v) => $v !== null && $v !== ''
            );

            return redirect()->route($this->rincianBiayaPostActionRedirectRoute(), $filterQuery)
                ->with('success', $rekeningTujuan
                    ? 'Biaya (rekening) disetujui. Saldo rekening sumber berkurang dan saldo rekening tujuan bertambah.'
                    : 'Biaya (rekening) disetujui dan saldo buku rekening telah dikurangi.');
        }

        if (! $this->verifierCanApproveRejectKasKecilBiayaPending($entry)) {
            return back()->with('error', 'Anda tidak berhak memproses pengajuan ini. Nominal hingga Rp '.number_format(SpeedshopRincianBiayaEntry::KAS_KECIL_VERIFIKASI_ACCOUNTING_MAX_NOMINAL, 0, ',', '.').' diverifikasi Accounting; di atasnya diverifikasi Manager.');
        }

        if (! $entry->isPending()) {
            return back()->with('error', 'Entri ini tidak dalam status menunggu persetujuan.');
        }

        if (! Schema::hasTable('speedshop_finance_ledgers')) {
            return back()->with('error', 'Modul jurnal keuangan belum tersedia.');
        }

        $nominalInt = (int) round((float) $entry->nominal);
        if ($nominalInt < 1) {
            return back()->with('error', 'Nominal tidak valid.');
        }

        $asOf = now()->toDateString();
        $saldo = $entryScope
            ? $finance->balancePocketAsOfScope($entryScope, SpeedshopFinanceService::POCKET_LACI, $asOf)
            : $finance->balancePocketAsOf(
                (int) $entry->speedshop_id,
                SpeedshopFinanceService::POCKET_LACI,
                $asOf,
            );
        if ($saldo < $nominalInt) {
            return back()->with('error', 'Saldo Kas Kecil (buku) tidak mencukupi (tersedia Rp '.number_format($saldo, 0, ',', '.').').');
        }

        try {
            DB::transaction(function () use ($entry, $finance, $nominalInt, $entryScope) {
                $locked = SpeedshopRincianBiayaEntry::query()->lockForUpdate()->findOrFail($entry->id);
                if (! $locked->isPending()) {
                    throw new \RuntimeException('Entri sudah diproses sebelumnya.');
                }
                if ((int) ($locked->finance_ledger_id ?? 0) > 0) {
                    throw new \RuntimeException('Jurnal untuk entri ini sudah ada.');
                }

                $tanggalYmd = $locked->tanggal instanceof \Carbon\Carbon
                    ? $locked->tanggal->toDateString()
                    : \Carbon\Carbon::parse($locked->tanggal)->toDateString();

                $ledger = $entryScope
                    ? $finance->recordBiayaOperasionalKasKecilKeluarForScope(
                        $entryScope,
                        $tanggalYmd,
                        $nominalInt,
                        (int) auth()->id(),
                        (int) $locked->id,
                    )
                    : $finance->recordBiayaOperasionalKasKecilKeluar(
                        (int) $locked->speedshop_id,
                        $tanggalYmd,
                        $nominalInt,
                        (int) auth()->id(),
                        (int) $locked->id,
                    );

                $locked->update([
                    'approval_status' => SpeedshopRincianBiayaEntry::STATUS_APPROVED,
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                    'finance_ledger_id' => $ledger->id,
                    'rejection_note' => null,
                ]);
            });
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Gagal memposting jurnal Kas Kecil: '.$e->getMessage());
        }

        $fresh = SpeedshopRincianBiayaEntry::query()->find($entry->id);
        if ($fresh) {
            $this->notifySpeedshopRincianBiayaDecision($fresh, true, null);
            app(SpeedshopJurnalAutomationService::class)->syncRincianBiayaPengeluaranJurnal($fresh);
        }

        $filterQuery = array_filter(
            array_merge(
                $request->only(['mode', 'tanggal', 'bulan', 'tahun']),
                ['approval_status' => SpeedshopRincianBiayaEntry::STATUS_PENDING]
            ),
            fn ($v) => $v !== null && $v !== ''
        );

        return redirect()->route($this->rincianBiayaPostActionRedirectRoute(), $filterQuery)
            ->with('success', 'Biaya disetujui dan saldo Kas Kecil (buku) telah dikurangi.');
    }

    public function rejectRincianBiaya(Request $request, SpeedshopRincianBiayaEntry $entry): \Illuminate\Http\RedirectResponse
    {
        if (! $this->entryInSpeedshopScope($entry)) {
            return back()->with('error', 'Data rincian biaya tidak ditemukan untuk scope ini.');
        }

        if ($entry->isPendingAccountingRekeningAwaitingManager()) {
            if (! $this->verifierCanApproveRejectAccountingRekeningBiayaPending($entry)) {
                return back()->with('error', 'Hanya Manager cabang yang dapat menolak pengajuan biaya dari rekening dengan nominal di atas Rp '.number_format(SpeedshopRincianBiayaEntry::KAS_KECIL_VERIFIKASI_ACCOUNTING_MAX_NOMINAL, 0, ',', '.').'.');
            }

            if (! $entry->isPending()) {
                return back()->with('error', 'Entri ini tidak dalam status menunggu persetujuan.');
            }

            $validated = $request->validate([
                'rejection_note' => ['nullable', 'string', 'max:2000'],
            ]);

            $entry->update([
                'approval_status' => SpeedshopRincianBiayaEntry::STATUS_REJECTED,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'rejection_note' => $validated['rejection_note'] ?? null,
            ]);

            $fresh = SpeedshopRincianBiayaEntry::query()->find($entry->id);
            if ($fresh) {
                $this->notifySpeedshopRincianBiayaDecision(
                    $fresh,
                    false,
                    $validated['rejection_note'] ?? null,
                );
            }

            $filterQuery = array_filter(
                array_merge(
                    $request->only(['mode', 'tanggal', 'bulan', 'tahun']),
                    ['approval_status' => SpeedshopRincianBiayaEntry::STATUS_PENDING]
                ),
                fn ($v) => $v !== null && $v !== ''
            );

            return redirect()->route($this->rincianBiayaPostActionRedirectRoute(), $filterQuery)
                ->with('success', 'Pengajuan biaya (rekening) ditolak.');
        }

        if (! $this->verifierCanApproveRejectKasKecilBiayaPending($entry)) {
            return back()->with('error', 'Anda tidak berhak memproses pengajuan ini. Nominal hingga Rp '.number_format(SpeedshopRincianBiayaEntry::KAS_KECIL_VERIFIKASI_ACCOUNTING_MAX_NOMINAL, 0, ',', '.').' diverifikasi Accounting; di atasnya diverifikasi Manager.');
        }

        if (! $entry->isPending()) {
            return back()->with('error', 'Entri ini tidak dalam status menunggu persetujuan.');
        }

        $validated = $request->validate([
            'rejection_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $entry->update([
            'approval_status' => SpeedshopRincianBiayaEntry::STATUS_REJECTED,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'rejection_note' => $validated['rejection_note'] ?? null,
        ]);

        $fresh = SpeedshopRincianBiayaEntry::query()->find($entry->id);
        if ($fresh) {
            $this->notifySpeedshopRincianBiayaDecision(
                $fresh,
                false,
                $validated['rejection_note'] ?? null,
            );
        }

        $filterQuery = array_filter(
            array_merge(
                $request->only(['mode', 'tanggal', 'bulan', 'tahun']),
                ['approval_status' => SpeedshopRincianBiayaEntry::STATUS_PENDING]
            ),
            fn ($v) => $v !== null && $v !== ''
        );

        return redirect()->route($this->rincianBiayaPostActionRedirectRoute(), $filterQuery)
            ->with('success', 'Pengajuan biaya ditolak.');
    }

    public function penjualanPartOli(Request $request)
    {
        return redirect()->route($this->keuanganBiayaOperasionalRoute(), $request->only(['dari', 'sampai']));
    }

    public function mekanikPerformance(Request $request)
    {
        $warehouseId = $this->warehouseId();
        [$dari, $sampai] = $this->dateRange($request);

        $performances = ServiceOrder::with(['mekanik', 'jasaServis', 'products', 'invoice'])
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->whereBetween('tanggal_masuk', [$dari, $sampai])
            ->whereIn('status', ['proses', 'selesai'])
            ->whereNotNull('mekanik_id')
            ->get()
            ->groupBy('mekanik_id')
            ->map(function ($orders, $mekanikId) {
                $mekanik = $orders->first()->mekanik;
                $totalJasa = 0;
                $totalSparepart = 0;
                $totalFinal = 0;
                foreach ($orders as $so) {
                    $totalJasa += $so->jasaServis->sum('pivot.biaya');
                    $totalSparepart += $so->products->sum(fn ($p) => $p->pivot->qty * $p->pivot->harga);
                    $totalFinal += $so->finalGrandTotal();
                }

                return (object) [
                    'mekanik' => $mekanik,
                    'total_order' => $orders->count(),
                    'total_jasa' => $totalJasa,
                    'total_sparepart' => $totalSparepart,
                    'total' => $totalFinal,
                ];
            })
            ->sortByDesc('total_order')
            ->values();

        return view('speedshop.laporan.mekanik-performance', compact('performances', 'dari', 'sampai'));
    }

    /**
     * Data laporan jurnal kas kecil / laci (sama untuk halaman web dan export Excel).
     *
     * @return array{
     *     speedshop: Speedshop|null,
     *     rows: \Illuminate\Support\Collection,
     *     pemasukan: float,
     *     pengeluaran: float,
     *     sisaSaldo: float,
     *     dari: \Carbon\Carbon,
     *     sampai: \Carbon\Carbon,
     *     mode: string,
     *     selectedDate: string,
     *     selectedMonth: string,
     *     selectedYear: int
     * }|null
     */
    private function buildJurnalKasKecilReportData(Request $request): ?array
    {
        $period = $this->resolveLaporanPeriodeMode($request, 'bulanan');
        $dari = $period['dari'];
        $sampai = $period['sampai'];
        $mode = $period['mode'];
        $selectedDate = $period['selectedDate'];
        $selectedMonth = $period['selectedMonth'];
        $selectedYear = $period['selectedYear'];

        $speedshopIds = $this->speedshopScopeIds();
        if ($speedshopIds === []) {
            return null;
        }
        $scopeId = $this->speedshopScopeId();
        $speedshop = $scopeId ? Speedshop::find($scopeId) : null;

        $jurnalSvc = app(SpeedshopJurnalAutomationService::class);

        $dbEntries = SpeedshopJurnalBluEntry::query()
            ->whereIn('speedshop_id', $speedshopIds)
            ->whereBetween('tanggal', [$dari->toDateString(), $sampai->toDateString()])
            ->whereRaw('UPPER(TRIM(akun)) != ?', ['SALDO AKHIR'])
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get()
            ->map(function (SpeedshopJurnalBluEntry $entry) {
                return (object) [
                    'id' => $entry->id,
                    'tanggal' => $entry->tanggal,
                    'akun' => $entry->akun,
                    'keterangan' => $entry->keterangan,
                    'pic' => $entry->pic,
                    'kredit' => (float) ($entry->kredit ?? 0),
                    'debet' => (float) ($entry->debet ?? 0),
                    'allow_delete' => ! (Schema::hasColumn('speedshop_jurnal_blu_entries', 'speedshop_rincian_biaya_entry_id') && $entry->speedshop_rincian_biaya_entry_id),
                ];
            });

        $openingShopId = $this->singleSpeedshopIdForJurnalOpening($scopeId, $speedshopIds);
        $openingRows = collect();
        if ($openingShopId && Schema::hasTable('speedshop_finance_ledgers')) {
            $openingBal = $jurnalSvc->openingLaciBalanceBefore($dari->toDateString(), $openingShopId);
            $prevLabel = \Carbon\Carbon::parse($dari->toDateString())->subDay()->translatedFormat('d F Y');
            $openingRows->push((object) [
                'id' => null,
                'tanggal' => $dari->copy(),
                'akun' => SpeedshopJurnalAutomationService::AKUN_SALDO_AWAL,
                'keterangan' => 'Saldo awal Kas Kecil (laci) per '.$prevLabel,
                'pic' => '-',
                'kredit' => (float) $openingBal,
                'debet' => 0.0,
                'allow_delete' => false,
            ]);
        }

        $entriesMerged = $openingRows->concat($dbEntries);

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

        $pemasukan = (float) $rows->sum('kredit');
        $pengeluaran = (float) $rows->sum('debet');
        $sisaSaldo = $rows->isNotEmpty() ? (float) $rows->last()->saldo : 0.0;

        return [
            'speedshop' => $speedshop,
            'rows' => $rows,
            'pemasukan' => $pemasukan,
            'pengeluaran' => $pengeluaran,
            'sisaSaldo' => $sisaSaldo,
            'dari' => $dari,
            'sampai' => $sampai,
            'mode' => $mode,
            'selectedDate' => $selectedDate,
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
            'selectedDari' => $period['selectedDari'],
            'selectedSampai' => $period['selectedSampai'],
        ];
    }

    private function jurnalExcelPeriodSubtitle(
        string $mode,
        string $selectedDate,
        string $selectedMonth,
        int $selectedYear,
        \Carbon\Carbon $dari,
        \Carbon\Carbon $sampai,
    ): string {
        return match ($mode) {
            'harian' => strtoupper(\Carbon\Carbon::parse($selectedDate)->locale('id')->translatedFormat('d F Y')),
            'bulanan' => strtoupper(\Carbon\Carbon::parse($selectedMonth.'-01')->locale('id')->translatedFormat('F Y')),
            'tahunan' => 'TAHUN '.$selectedYear,
            'range' => strtoupper($dari->locale('id')->translatedFormat('d F Y')).' – '.strtoupper($sampai->locale('id')->translatedFormat('d F Y')),
            default => strtoupper($sampai->locale('id')->translatedFormat('F Y')),
        };
    }

    private function jurnalForWarehouse(Request $request)
    {
        $scope = $this->resolveKeuanganScope();
        if (! $scope) {
            return redirect()->route($this->laporanDashboardRoute())
                ->with('error', $this->keuanganScopeErrorMessage());
        }

        $parsed = $this->parseJurnalSumberFromRequest($request);
        if ($parsed['type'] === 'digital') {
            return redirect()->route($this->laporanJurnalRoute(), array_merge($request->query(), ['sumber' => 'kas_kecil']))
                ->with('error', 'Jurnal kas digital tidak berlaku untuk gudang. Pilih Kas Tunai atau rekening.');
        }

        $resolvePeriod = fn (Request $r) => $this->resolveLaporanPeriodeMode($r, 'bulanan');

        if ($parsed['type'] === 'rekening') {
            if (! $parsed['rekening_id']) {
                return redirect()->route($this->laporanJurnalRoute(), array_merge($request->query(), ['sumber' => 'kas_kecil']))
                    ->with('error', 'Rekening untuk jurnal tidak valid.');
            }
            $data = WarehouseJurnalReport::buildRekeningReport($request, $scope, (int) $parsed['rekening_id'], $resolvePeriod);
            if ($data === null) {
                return redirect()->route($this->laporanJurnalRoute(), array_merge($request->query(), ['sumber' => 'kas_kecil']))
                    ->with('error', 'Rekening tidak ditemukan untuk gudang ini.');
            }
        } else {
            $data = WarehouseJurnalReport::buildKasTunaiReport($request, $scope, $resolvePeriod);
        }

        $sumberSelected = $parsed['sumber_query'];
        if ($parsed['type'] === 'rekening' && $parsed['rekening_id'] && str_starts_with($parsed['sumber_query'], 'rekening:')) {
            $rek = $scope->applyToRekening(SpeedshopKeuanganRekening::query())->find((int) $parsed['rekening_id']);
            if ($rek) {
                $sumberSelected = $rek->isRekeningUtama()
                    ? 'rekening_utama:'.$rek->id
                    : 'kas_besar:'.$rek->id;
            }
        }

        return $this->laporanView('jurnal', array_merge($data, [
            'sumber_query' => $parsed['sumber_query'],
            'sumber_selected' => $sumberSelected,
            'sumberSelectSpec' => WarehouseJurnalReport::sumberSelectSpec($scope),
        ]));
    }

    private function exportJurnalForWarehouse(Request $request)
    {
        $scope = $this->resolveKeuanganScope();
        if (! $scope) {
            return redirect()->route($this->laporanDashboardRoute())
                ->with('error', $this->keuanganScopeErrorMessage());
        }

        $parsed = $this->parseJurnalSumberFromRequest($request);
        if ($parsed['type'] === 'digital') {
            return redirect()->route($this->laporanJurnalRoute(), $request->query())
                ->with('error', 'Export jurnal kas digital tidak berlaku untuk gudang.');
        }

        $resolvePeriod = fn (Request $r) => $this->resolveLaporanPeriodeMode($r, 'bulanan');

        if ($parsed['type'] === 'rekening' && $parsed['rekening_id']) {
            $data = WarehouseJurnalReport::buildRekeningReport($request, $scope, (int) $parsed['rekening_id'], $resolvePeriod);
            $titleLine1 = 'JURNAL REKENING '.($data['scopeLabel'] ?? 'GUDANG');
        } else {
            $data = WarehouseJurnalReport::buildKasTunaiReport($request, $scope, $resolvePeriod);
            $titleLine1 = 'JURNAL KAS TUNAI '.($data['scopeLabel'] ?? 'GUDANG');
        }

        if ($data === null) {
            return redirect()->route($this->laporanJurnalRoute(), $request->query())
                ->with('error', 'Data jurnal tidak tersedia.');
        }

        $titleLine2 = $this->jurnalExcelPeriodSubtitle(
            $data['mode'],
            $data['selectedDate'],
            $data['selectedMonth'],
            (int) $data['selectedYear'],
            $data['dari'],
            $data['sampai'],
        );
        $ringkasanTanggal = $data['dari']->toDateString() === $data['sampai']->toDateString()
            ? $data['sampai']->locale('id')->translatedFormat('d M Y')
            : $data['dari']->locale('id')->translatedFormat('d M Y').' – '.$data['sampai']->locale('id')->translatedFormat('d M Y');

        $slug = Str::slug(preg_replace('/[^A-Za-z0-9]+/', '-', $data['scopeLabel'] ?? 'gudang') ?: 'gudang', '-');

        return Excel::download(
            new JurnalKasKecilExport(
                $data['rows'],
                $titleLine1,
                $titleLine2,
                $ringkasanTanggal,
                $data['pemasukan'],
                $data['pengeluaran'],
                $data['sisaSaldo'],
            ),
            'jurnal-gudang-'.$slug.'-'.$data['dari']->format('Ymd').'-'.$data['sampai']->format('Ymd').'-'.now()->format('His').'.xlsx'
        );
    }

    public function jurnal(Request $request)
    {
        if ($this->isWarehouseKeuanganContext()) {
            return $this->jurnalForWarehouse($request);
        }

        $parsed = $this->parseJurnalSumberFromRequest($request);
        $ssid = $this->writableSpeedshopId();

        if ($parsed['type'] === 'rekening') {
            if (! $parsed['rekening_id']) {
                return redirect()->route($this->laporanJurnalRoute(), array_merge($request->query(), ['sumber' => 'kas_kecil']))
                    ->with('error', 'Rekening untuk jurnal tidak valid.');
            }
            if (! $ssid) {
                return redirect()->route($this->laporanDashboardRoute())
                    ->with('error', 'Buku per rekening memerlukan satu cabang speedshop yang dipilih.');
            }
            $data = $this->buildJurnalLedgerRekeningReportData($request, (int) $parsed['rekening_id']);
            if ($data === null) {
                return redirect()->route($this->laporanJurnalRoute(), array_merge($request->query(), ['sumber' => 'kas_kecil']))
                    ->with('error', 'Data jurnal rekening tidak tersedia.');
            }
        } elseif ($parsed['type'] === 'digital') {
            $data = $this->buildJurnalDigitalReportData($request);
            if ($data === null) {
                return redirect()->route($this->laporanDashboardRoute())->with('error', 'Speedshop tidak terdeteksi.');
            }
            $data['jurnalKind'] = 'digital';
            $data['canManualEntry'] = true;
            $data['thirdCardTitle'] = 'Sisa saldo digital';
            $data['ledgerRekening'] = null;
            $data['emptyMessage'] = 'Belum ada data jurnal digital pada periode ini.';
        } else {
            $data = $this->buildJurnalKasKecilReportData($request);
            if ($data === null) {
                return redirect()->route($this->laporanDashboardRoute())->with('error', 'Speedshop tidak terdeteksi.');
            }
            $data['jurnalKind'] = 'kas_kecil';
            $data['canManualEntry'] = true;
            $data['thirdCardTitle'] = 'Sisa saldo kas kecil';
            $data['ledgerRekening'] = null;
            $data['emptyMessage'] = 'Belum ada data jurnal kas kecil pada periode ini.';
            $data['monitorOnly'] = false;
        }

        $sumberSelected = $parsed['sumber_query'];
        if ($parsed['type'] === 'rekening' && $parsed['rekening_id'] && str_starts_with($parsed['sumber_query'], 'rekening:')) {
            $rek = SpeedshopKeuanganRekening::query()->find((int) $parsed['rekening_id']);
            if ($rek) {
                if (Schema::hasColumn('speedshop_keuangan_rekenings', 'jenis')
                    && SpeedshopKeuanganRekening::isSecondaryRekeningJenis($rek->jenis ?? SpeedshopKeuanganRekening::JENIS_REKENING_UTAMA)) {
                    $sumberSelected = 'kas_besar:'.$rek->id;
                } else {
                    $sumberSelected = 'rekening_utama:'.$rek->id;
                }
            }
        }

        $sumberSelectSpec = $ssid
            ? $this->jurnalSumberSelectSpecForOutlet((int) $ssid)
            : [
                'top_options' => [
                    ['value' => '', 'label' => '— pilih sumber —'],
                    ['value' => 'kas_kecil', 'label' => 'Kas Kecil (Tunai)'],
                    ['value' => 'digital', 'label' => 'Kas digital (gabungan Kas Besar + Rek. Utama)'],
                ],
                'optgroups' => [],
            ];

        if (auth()->user()?->isOwner()) {
            $data['canManualEntry'] = false;
        }

        $scopeIds = $this->speedshopScopeIds();

        return $this->laporanView('jurnal', array_merge($data, [
            'sumber_query' => $parsed['sumber_query'],
            'sumber_selected' => $sumberSelected,
            'sumberSelectSpec' => $sumberSelectSpec,
            'laporanAllSpeedshops' => count($scopeIds) > 1,
        ], $this->ownerLaporanSpeedshopFilterContext($request)));
    }

    public function exportJurnal(Request $request)
    {
        if ($this->isWarehouseKeuanganContext()) {
            return $this->exportJurnalForWarehouse($request);
        }

        $parsed = $this->parseJurnalSumberFromRequest($request);
        if ($parsed['type'] === 'rekening') {
            return redirect()->route($this->laporanJurnalRoute(), $request->query())
                ->with('error', 'Export Excel belum tersedia untuk tampilan buku per rekening.');
        }
        if ($parsed['type'] === 'digital') {
            return $this->downloadJurnalDigitalSpreadsheet($request);
        }

        return $this->downloadJurnalKasKecilSpreadsheet($request);
    }

    public function exportJurnalPdf(Request $request): Response|RedirectResponse
    {
        if ($this->isWarehouseKeuanganContext()) {
            return redirect()->route($this->laporanJurnalRoute(), $request->query())
                ->with('error', 'Export PDF belum tersedia untuk konteks gudang.');
        }

        $ctx = $this->resolveJurnalExportContext($request);
        if ($ctx instanceof RedirectResponse) {
            return $ctx;
        }

        $slug = Str::slug(preg_replace('/[^A-Za-z0-9]+/', '-', $ctx['speedshopLabel']) ?: 'outlet', '-');
        $filename = $ctx['filePrefix'].'-'.$slug.'-'.$ctx['dari']->format('Ymd').'-'.$ctx['sampai']->format('Ymd').'-'.now()->format('His').'.pdf';

        return Pdf::loadView('speedshop.laporan.jurnal-pdf', $ctx)
            ->setPaper([0, 0, 841.89, 595.28])
            ->stream($filename);
    }

    /**
     * @return array<string, mixed>|RedirectResponse
     */
    private function resolveJurnalExportContext(Request $request): array|RedirectResponse
    {
        $parsed = $this->parseJurnalSumberFromRequest($request);
        if ($parsed['type'] === 'rekening') {
            return redirect()->route($this->laporanJurnalRoute(), $request->query())
                ->with('error', 'Export belum tersedia untuk tampilan buku per rekening.');
        }

        if ($parsed['type'] === 'digital') {
            $data = $this->buildJurnalDigitalReportData($request);
            $jurnalKind = 'digital';
            $listTitle = 'Jurnal Kas Digital';
            $titleLine1 = 'JURNAL KAS DIGITAL (KAS BESAR + REK. UTAMA) ';
            $filePrefix = 'jurnal-digital';
            $thirdCardTitle = 'Sisa saldo digital';
        } else {
            $data = $this->buildJurnalKasKecilReportData($request);
            $jurnalKind = 'kas_kecil';
            $listTitle = 'Jurnal Kas Kecil (Laci)';
            $titleLine1 = 'JURNAL KAS KECIL (LACI) ';
            $filePrefix = 'jurnal-kas-kecil';
            $thirdCardTitle = 'Sisa saldo kas kecil';
        }

        if ($data === null) {
            return redirect()->route($this->laporanDashboardRoute())->with('error', 'Speedshop tidak terdeteksi.');
        }

        $ownerCtx = $this->ownerLaporanSpeedshopFilterContext($request);
        $speedshopLabel = ! empty($ownerCtx['selectedLaporanSpeedshopName'])
            ? strtoupper($ownerCtx['selectedLaporanSpeedshopName'])
            : strtoupper($data['speedshop']->nama ?? 'SEMUA OUTLET');
        $titleLine2 = $this->jurnalExcelPeriodSubtitle(
            $data['mode'],
            $data['selectedDate'],
            $data['selectedMonth'],
            (int) $data['selectedYear'],
            $data['dari'],
            $data['sampai'],
        );
        $ringkasanTanggal = $data['dari']->toDateString() === $data['sampai']->toDateString()
            ? $data['sampai']->locale('id')->translatedFormat('d M Y')
            : $data['dari']->locale('id')->translatedFormat('d M Y').' – '.$data['sampai']->locale('id')->translatedFormat('d M Y');
        $fmt = fn (float $n): string => number_format($n, 0, ',', '.');

        $metaLines = [
            'Periode: '.$ringkasanTanggal,
            'Outlet: '.$speedshopLabel,
            'Pemasukan: Rp '.$fmt((float) $data['pemasukan']),
            'Pengeluaran: Rp '.$fmt((float) $data['pengeluaran']),
            $thirdCardTitle.': Rp '.$fmt((float) $data['sisaSaldo']),
        ];

        return array_merge($data, [
            'jurnalKind' => $jurnalKind,
            'listTitle' => $listTitle,
            'titleLine1' => $titleLine1.$speedshopLabel,
            'titleLine2' => $titleLine2,
            'speedshopLabel' => $speedshopLabel,
            'thirdCardTitle' => $thirdCardTitle,
            'metaLines' => $metaLines,
            'filePrefix' => $filePrefix,
            'printedAt' => now(),
        ]);
    }

    public function storeJurnal(Request $request)
    {
        if ($this->isWarehouseKeuanganContext()) {
            return back()->with('error', 'Input manual jurnal tidak tersedia untuk gudang. Mutasi tercatat otomatis dari Kas Tunai, biaya operasional, dan transaksi rekening.');
        }

        $sumber = $request->validate([
            'sumber' => ['required', 'string', 'in:kas_kecil,digital'],
        ])['sumber'];

        if ($sumber === 'digital') {
            return $this->storeJurnalDigital($request);
        }

        return $this->storeJurnalKasKecil($request);
    }

    public function destroyJurnalEntry(string $sumber, int $entry): \Illuminate\Http\RedirectResponse
    {
        if ($this->isWarehouseKeuanganContext()) {
            return back()->with('error', 'Baris jurnal gudang berasal dari ledger keuangan dan tidak dapat dihapus dari halaman ini.');
        }

        if ($sumber === 'digital') {
            $model = SpeedshopJurnalSaquEntry::query()->findOrFail($entry);

            return $this->destroyJurnalDigitalEntry($model);
        }
        if ($sumber === 'kas_kecil') {
            $model = SpeedshopJurnalBluEntry::query()->findOrFail($entry);

            return $this->destroyJurnalKasKecilEntry($model);
        }

        abort(404);
    }

    private function downloadJurnalKasKecilSpreadsheet(Request $request)
    {
        $data = $this->buildJurnalKasKecilReportData($request);
        if ($data === null) {
            return redirect()->route($this->laporanDashboardRoute())->with('error', 'Speedshop tidak terdeteksi.');
        }

        $speedshopLabel = strtoupper($data['speedshop']->nama ?? 'SEMUA OUTLET');
        $titleLine1 = 'JURNAL KAS KECIL (LACI) '.$speedshopLabel;
        $titleLine2 = $this->jurnalExcelPeriodSubtitle(
            $data['mode'],
            $data['selectedDate'],
            $data['selectedMonth'],
            (int) $data['selectedYear'],
            $data['dari'],
            $data['sampai'],
        );
        $ringkasanTanggal = $data['dari']->toDateString() === $data['sampai']->toDateString()
            ? $data['sampai']->locale('id')->translatedFormat('d M Y')
            : $data['dari']->locale('id')->translatedFormat('d M Y').' – '.$data['sampai']->locale('id')->translatedFormat('d M Y');

        $slug = Str::slug(preg_replace('/[^A-Za-z0-9]+/', '-', $speedshopLabel) ?: 'outlet', '-');

        return Excel::download(
            new JurnalKasKecilExport(
                $data['rows'],
                $titleLine1,
                $titleLine2,
                $ringkasanTanggal,
                $data['pemasukan'],
                $data['pengeluaran'],
                $data['sisaSaldo'],
            ),
            'jurnal-kas-kecil-'.$slug.'-'.$data['dari']->format('Ymd').'-'.$data['sampai']->format('Ymd').'-'.now()->format('His').'.xlsx'
        );
    }

    private function storeJurnalKasKecil(Request $request)
    {
        $speedshopId = $this->writableSpeedshopId();
        if (! $speedshopId) {
            return back()->with('error', 'Pilih speedshop spesifik dulu sebelum menambah jurnal kas kecil.');
        }

        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
            'akun' => ['required', 'string', 'max:100'],
            'keterangan' => ['nullable', 'string'],
            'pic' => ['nullable', 'string', 'max:100'],
            'kredit' => ['nullable', 'numeric', 'min:0'],
            'debet' => ['nullable', 'numeric', 'min:0'],
        ]);

        $kredit = (float) ($validated['kredit'] ?? 0);
        $debet = (float) ($validated['debet'] ?? 0);
        if ($kredit <= 0 && $debet <= 0) {
            return back()->with('error', 'Minimal isi salah satu nominal Kredit atau Debet.');
        }

        $attrs = [
            'speedshop_id' => $speedshopId,
            'tanggal' => $validated['tanggal'],
            'akun' => $validated['akun'],
            'keterangan' => $validated['keterangan'] ?? null,
            'pic' => $validated['pic'] ?? null,
            'kredit' => $kredit,
            'debet' => $debet,
        ];
        if (Schema::hasColumn('speedshop_jurnal_blu_entries', 'speedshop_rincian_biaya_entry_id')) {
            $attrs['speedshop_rincian_biaya_entry_id'] = null;
        }
        SpeedshopJurnalBluEntry::create($attrs);

        return back()->with('success', 'Jurnal kas kecil berhasil ditambahkan.');
    }

    private function destroyJurnalKasKecilEntry(SpeedshopJurnalBluEntry $entry)
    {
        $speedshopId = $this->writableSpeedshopId();
        if (! $speedshopId || (int) $entry->speedshop_id !== (int) $speedshopId) {
            return back()->with('error', 'Data jurnal tidak ditemukan untuk speedshop ini.');
        }

        if (
            Schema::hasColumn('speedshop_jurnal_blu_entries', 'speedshop_rincian_biaya_entry_id') && $entry->speedshop_rincian_biaya_entry_id
        ) {
            return back()->with('error', 'Baris jurnal yang tercatat otomatis tidak dapat dihapus manual.');
        }

        $entry->delete();

        return back()->with('success', 'Jurnal kas kecil berhasil dihapus.');
    }

    /**
     * Data laporan jurnal kas digital — Kas Besar + Rekening Utama (satu buku).
     *
     * @return array{
     *     speedshop: Speedshop|null,
     *     rows: \Illuminate\Support\Collection,
     *     pemasukan: float,
     *     pengeluaran: float,
     *     sisaSaldo: float,
     *     dari: \Carbon\Carbon,
     *     sampai: \Carbon\Carbon,
     *     mode: string,
     *     selectedDate: string,
     *     selectedMonth: string,
     *     selectedYear: int,
     *     monitorOnly: bool
     * }|null
     */
    private function buildJurnalDigitalReportData(Request $request): ?array
    {
        $period = $this->resolveLaporanPeriodeMode($request, 'bulanan');
        $dari = $period['dari'];
        $sampai = $period['sampai'];
        $mode = $period['mode'];
        $selectedDate = $period['selectedDate'];
        $selectedMonth = $period['selectedMonth'];
        $selectedYear = $period['selectedYear'];

        $speedshopIds = $this->speedshopScopeIds();
        if ($speedshopIds === []) {
            return null;
        }
        $scopeId = $this->speedshopScopeId();
        $speedshop = $scopeId ? Speedshop::find($scopeId) : null;

        $jurnalSvc = app(SpeedshopJurnalAutomationService::class);

        $dbEntries = SpeedshopJurnalSaquEntry::query()
            ->whereIn('speedshop_id', $speedshopIds)
            ->whereBetween('tanggal', [$dari->toDateString(), $sampai->toDateString()])
            ->whereRaw('UPPER(TRIM(akun)) != ?', ['SALDO AKHIR'])
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get()
            ->map(function (SpeedshopJurnalSaquEntry $entry) {
                return (object) [
                    'id' => $entry->id,
                    'tanggal' => $entry->tanggal,
                    'akun' => $entry->akun,
                    'keterangan' => $entry->keterangan,
                    'pic' => $entry->pic,
                    'kredit' => (float) ($entry->kredit ?? 0),
                    'debet' => (float) ($entry->debet ?? 0),
                    'allow_delete' => ! (Schema::hasColumn('speedshop_jurnal_saqu_entries', 'speedshop_rincian_biaya_entry_id') && $entry->speedshop_rincian_biaya_entry_id),
                ];
            });

        $openingShopId = $this->singleSpeedshopIdForJurnalOpening($scopeId, $speedshopIds);
        $openingRows = collect();
        if ($openingShopId && Schema::hasTable('speedshop_finance_ledgers')) {
            $openingBal = $jurnalSvc->openingDigitalPoolBalanceBefore($dari->toDateString(), $openingShopId);
            $prevLabel = \Carbon\Carbon::parse($dari->toDateString())->subDay()->translatedFormat('d F Y');
            $openingRows->push((object) [
                'id' => null,
                'tanggal' => $dari->copy(),
                'akun' => SpeedshopJurnalAutomationService::AKUN_SALDO_AWAL,
                'keterangan' => 'Saldo awal kas digital (Kas Besar + Rek. Utama) per '.$prevLabel,
                'pic' => '-',
                'kredit' => (float) $openingBal,
                'debet' => 0.0,
                'allow_delete' => false,
            ]);
        }

        $entriesMerged = $openingRows->concat($dbEntries);

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

        $pemasukan = (float) $rows->sum('kredit');
        $pengeluaran = (float) $rows->sum('debet');
        $sisaSaldo = $rows->isNotEmpty() ? (float) $rows->last()->saldo : 0.0;
        $monitorOnly = false;

        return [
            'speedshop' => $speedshop,
            'rows' => $rows,
            'pemasukan' => $pemasukan,
            'pengeluaran' => $pengeluaran,
            'sisaSaldo' => $sisaSaldo,
            'dari' => $dari,
            'sampai' => $sampai,
            'mode' => $mode,
            'selectedDate' => $selectedDate,
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
            'selectedDari' => $period['selectedDari'],
            'selectedSampai' => $period['selectedSampai'],
            'monitorOnly' => $monitorOnly,
        ];
    }

    private function downloadJurnalDigitalSpreadsheet(Request $request)
    {
        $data = $this->buildJurnalDigitalReportData($request);
        if ($data === null) {
            return redirect()->route($this->laporanDashboardRoute())->with('error', 'Speedshop tidak terdeteksi.');
        }

        $speedshopLabel = strtoupper($data['speedshop']->nama ?? 'SEMUA OUTLET');
        $titleLine1 = 'JURNAL KAS DIGITAL (KAS BESAR + REK. UTAMA) '.$speedshopLabel;
        $titleLine2 = $this->jurnalExcelPeriodSubtitle(
            $data['mode'],
            $data['selectedDate'],
            $data['selectedMonth'],
            (int) $data['selectedYear'],
            $data['dari'],
            $data['sampai'],
        );
        $ringkasanTanggal = $data['dari']->toDateString() === $data['sampai']->toDateString()
            ? $data['sampai']->locale('id')->translatedFormat('d M Y')
            : $data['dari']->locale('id')->translatedFormat('d M Y').' – '.$data['sampai']->locale('id')->translatedFormat('d M Y');

        $slug = Str::slug(preg_replace('/[^A-Za-z0-9]+/', '-', $speedshopLabel) ?: 'outlet', '-');

        return Excel::download(
            new JurnalDigitalExport(
                $data['rows'],
                $titleLine1,
                $titleLine2,
                $ringkasanTanggal,
                $data['pemasukan'],
                $data['pengeluaran'],
                $data['sisaSaldo'],
            ),
            'jurnal-digital-'.$slug.'-'.$data['dari']->format('Ymd').'-'.$data['sampai']->format('Ymd').'-'.now()->format('His').'.xlsx'
        );
    }

    private function storeJurnalDigital(Request $request)
    {
        $speedshopId = $this->writableSpeedshopId();
        if (! $speedshopId) {
            return back()->with('error', 'Pilih speedshop spesifik dulu sebelum menambah jurnal kas digital.');
        }

        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
            'akun' => ['required', 'string', 'max:100'],
            'keterangan' => ['nullable', 'string'],
            'pic' => ['nullable', 'string', 'max:100'],
            'kredit' => ['nullable', 'numeric', 'min:0'],
            'debet' => ['nullable', 'numeric', 'min:0'],
        ]);

        $kredit = (float) ($validated['kredit'] ?? 0);
        $debet = (float) ($validated['debet'] ?? 0);
        if ($kredit <= 0 && $debet <= 0) {
            return back()->with('error', 'Minimal isi salah satu nominal Kredit atau Debet.');
        }

        $attrs = [
            'speedshop_id' => $speedshopId,
            'tanggal' => $validated['tanggal'],
            'akun' => $validated['akun'],
            'keterangan' => $validated['keterangan'] ?? null,
            'pic' => $validated['pic'] ?? null,
            'kredit' => $kredit,
            'debet' => $debet,
        ];
        if (Schema::hasColumn('speedshop_jurnal_saqu_entries', 'speedshop_rincian_biaya_entry_id')) {
            $attrs['speedshop_rincian_biaya_entry_id'] = null;
        }
        SpeedshopJurnalSaquEntry::create($attrs);

        return back()->with('success', 'Jurnal kas digital berhasil ditambahkan.');
    }

    private function destroyJurnalDigitalEntry(SpeedshopJurnalSaquEntry $entry)
    {
        $speedshopId = $this->writableSpeedshopId();
        if (! $speedshopId || (int) $entry->speedshop_id !== (int) $speedshopId) {
            return back()->with('error', 'Data jurnal tidak ditemukan untuk speedshop ini.');
        }

        if (
            Schema::hasColumn('speedshop_jurnal_saqu_entries', 'speedshop_rincian_biaya_entry_id') && $entry->speedshop_rincian_biaya_entry_id
        ) {
            return back()->with('error', 'Baris jurnal yang tercatat otomatis tidak dapat dihapus manual.');
        }

        $entry->delete();

        return back()->with('success', 'Jurnal kas digital berhasil dihapus.');
    }

    public function exportBiaya(Request $request)
    {
        $ctx = $this->resolveBiayaExportContext($request);

        return Excel::download(
            new \App\Exports\Speedshop\LaporanBiayaExport($ctx),
            'laporan-biaya-'.$ctx['dari']->format('Ymd').'-'.$ctx['sampai']->format('Ymd').'-'.now()->format('His').'.xlsx'
        );
    }

    public function exportBiayaPdf(Request $request): Response
    {
        $ctx = $this->resolveBiayaExportContext($request);
        $filename = 'laporan-biaya-'.$ctx['dari']->format('Ymd').'-'.$ctx['sampai']->format('Ymd').'-'.now()->format('His').'.pdf';

        return Pdf::loadView('speedshop.laporan.biaya-pdf', $ctx)
            ->setPaper([0, 0, 841.89, 595.28])
            ->stream($filename);
    }

    /**
     * @return array<string, mixed>
     */
    protected function resolveBiayaExportContext(Request $request): array
    {
        $ctx = $this->buildBiayaReportContext($request);
        $ownerCtx = $this->ownerLaporanSpeedshopFilterContext($request);
        $periodeLabel = $ctx['dari']->locale('id')->translatedFormat('d M Y')
            .' – '
            .$ctx['sampai']->locale('id')->translatedFormat('d M Y');
        $fmt = fn (float $n): string => number_format($n, 0, ',', '.');
        $periode = $ctx['periode'];

        $metaLines = array_values(array_filter([
            'Periode: '.$periodeLabel,
            ! empty($ownerCtx['selectedLaporanSpeedshopName'])
                ? 'Cabang: '.$ownerCtx['selectedLaporanSpeedshopName']
                : null,
            'Omzet Kotor: Rp '.$fmt((float) ($periode['omzet_kotor'] ?? 0)),
            'Total Biaya (HPP): Rp '.$fmt((float) ($ctx['totalBiaya'] ?? 0)),
            'Gross Profit: Rp '.$fmt((float) ($periode['profit_bersih'] ?? 0)),
        ]));

        return array_merge($ctx, [
            'periodeLabel' => $periodeLabel,
            'metaLines' => $metaLines,
            'printedAt' => now(),
        ]);
    }

    public function exportLabaRugi(Request $request)
    {
        $period = $this->resolveLaporanPeriodeMode($request, 'bulanan');

        return Excel::download(
            new \App\Exports\Speedshop\LaporanLabaRugiExport([
                'dari' => $period['dari']->toDateString(),
                'sampai' => $period['sampai']->toDateString(),
                'mode' => $period['mode'],
            ]),
            'laporan-laba-rugi-'.$period['dari']->format('Ymd').'-'.$period['sampai']->format('Ymd').'-'.now()->format('His').'.xlsx'
        );
    }

    public function exportLabaRugiPdf(Request $request): Response
    {
        $ctx = $this->resolveLabaRugiExportContext($request);
        $filename = 'laporan-laba-rugi-'.$ctx['dari']->format('Ymd').'-'.$ctx['sampai']->format('Ymd').'-'.now()->format('His').'.pdf';

        return Pdf::loadView('speedshop.laporan.laba-rugi-pdf', $ctx)
            ->setPaper('a4', 'portrait')
            ->stream($filename);
    }

    /**
     * @return array{
     *     rows: list<array<string, mixed>>,
     *     summary: array<string, float>,
     *     dari: \Carbon\Carbon,
     *     sampai: \Carbon\Carbon,
     *     periodeLabel: string,
     *     metaLines: list<string>,
     *     printedAt: \Carbon\Carbon
     * }
     */
    protected function resolveLabaRugiExportContext(Request $request): array
    {
        $scope = $this->financialReportScope();
        $period = $this->resolveLaporanPeriodeMode($request, 'bulanan');
        $dari = $period['dari'];
        $sampai = $period['sampai'];
        $presentation = $this->buildLabaRugiPresentation(
            $dari,
            $sampai,
            $scope['speedshop_id'],
            $scope['warehouse_id'],
            $scope['speedshop_ids'] ?? [],
        );
        $ownerCtx = $this->ownerLaporanSpeedshopFilterContext($request);
        $laporanAllSpeedshops = $scope['all_speedshops'] ?? false;
        $speedshop = $laporanAllSpeedshops
            ? null
            : Speedshop::find($scope['speedshop_id'] ?? $this->speedshopId());
        $summary = $presentation['summary'];
        $periodeLabel = $dari->locale('id')->translatedFormat('d M Y')
            .' – '
            .$sampai->locale('id')->translatedFormat('d M Y');
        $fmt = fn (float $n): string => number_format($n, 0, ',', '.');
        $speedshopLabel = ! empty($ownerCtx['selectedLaporanSpeedshopName'])
            ? strtoupper($ownerCtx['selectedLaporanSpeedshopName'])
            : (! empty($laporanAllSpeedshops)
                ? 'SEMUA SPEEDSHOP'
                : strtoupper($speedshop?->nama ?? 'SEMUA OUTLET'));

        $metaLines = array_values(array_filter([
            'Periode: '.$periodeLabel,
            'Outlet: '.$speedshopLabel,
            'Total Pendapatan: Rp '.$fmt((float) ($summary['total_pendapatan'] ?? 0)),
            'Total Biaya: Rp '.$fmt((float) ($summary['total_biaya'] ?? 0)),
            'Gross Profit: Rp '.$fmt((float) ($summary['gross_profit'] ?? 0)),
            'Nett Profit: Rp '.$fmt((float) ($summary['nett_profit'] ?? 0)),
        ]));

        return [
            'rows' => $presentation['rows'],
            'summary' => $summary,
            'dari' => $dari,
            'sampai' => $sampai,
            'periodeLabel' => $periodeLabel,
            'speedshopLabel' => $speedshopLabel,
            'metaLines' => $metaLines,
            'printedAt' => now(),
        ];
    }

    public function exportSummary(Request $request)
    {
        $ctx = $this->resolveSummaryExportContext($request);

        return Excel::download(
            new \App\Exports\Speedshop\LaporanSummaryExport($ctx),
            'laporan-summary-'.$ctx['dari']->format('Ymd').'-'.$ctx['sampai']->format('Ymd').'-'.now()->format('His').'.xlsx'
        );
    }

    public function exportSummaryPdf(Request $request): Response
    {
        $ctx = $this->resolveSummaryExportContext($request);
        $filename = 'laporan-summary-'.$ctx['dari']->format('Ymd').'-'.$ctx['sampai']->format('Ymd').'-'.now()->format('His').'.pdf';

        return Pdf::loadView('speedshop.laporan.summary-pdf', $ctx)
            ->setPaper([0, 0, 841.89, 595.28])
            ->stream($filename);
    }

    /**
     * @return array<string, mixed>
     */
    protected function resolveSummaryExportContext(Request $request): array
    {
        $ctx = $this->buildSummaryReportContext($request);
        $ownerCtx = $this->ownerLaporanSpeedshopFilterContext($request);
        $periodeLabel = $ctx['dari']->locale('id')->translatedFormat('d M Y')
            .' – '
            .$ctx['sampai']->locale('id')->translatedFormat('d M Y');
        $speedshopLabel = ! empty($ownerCtx['selectedLaporanSpeedshopName'])
            ? strtoupper($ownerCtx['selectedLaporanSpeedshopName'])
            : (! empty($ctx['laporanAllSpeedshops'])
                ? 'SEMUA SPEEDSHOP'
                : strtoupper($ctx['speedshop']?->nama ?? 'SEMUA OUTLET'));
        $fmt = fn (float $n): string => number_format($n, 0, ',', '.');

        $metaLines = array_values(array_filter([
            'Periode: '.$periodeLabel,
            'Outlet: '.$speedshopLabel,
            'Pendapatan Kotor: Rp '.$fmt((float) ($ctx['summaryOmzetKotor'] ?? 0)),
            'Total Biaya: Rp '.$fmt((float) ($ctx['summaryTotalBiaya'] ?? 0)),
            'Gross Profit Before HPP: Rp '.$fmt((float) ($ctx['summaryGrossProfit'] ?? 0)),
        ]));

        return array_merge($ctx, [
            'periodeLabel' => $periodeLabel,
            'speedshopLabel' => $speedshopLabel,
            'metaLines' => $metaLines,
            'printedAt' => now(),
        ]);
    }

    public function exportPenjualanPartOli(Request $request)
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\Speedshop\LaporanPenjualanPartOliExport($request->only(['dari', 'sampai'])),
            'laporan-penjualan-part-oli-'.now()->format('Y-m-d-His').'.xlsx'
        );
    }

    public function exportRincianBiaya(Request $request): BinaryFileResponse|RedirectResponse
    {
        if (! Schema::hasTable('speedshop_rincian_biaya_entries')) {
            abort(503, 'Migrasi rincian biaya belum dijalankan.');
        }

        $speedshopIds = $this->speedshopScopeIds();
        if ($speedshopIds === []) {
            return redirect()->route($this->laporanDashboardRoute())->with('error', 'Speedshop tidak terdeteksi.');
        }

        $ctx = $this->resolveSpeedshopRincianBiayaExportContext($request);

        return Excel::download(
            new BiayaOperasionalSpeedshopExport(
                $ctx['rows'],
                $ctx['periodeLabel'],
                $ctx['stats'],
                $ctx['cabangLabel'],
            ),
            'biaya-operasional-speedshop-'.now()->format('Y-m-d-His').'.xlsx'
        );
    }

    public function exportRincianBiayaPdf(Request $request): Response|RedirectResponse
    {
        if (! Schema::hasTable('speedshop_rincian_biaya_entries')) {
            abort(503, 'Migrasi rincian biaya belum dijalankan.');
        }

        $speedshopIds = $this->speedshopScopeIds();
        if ($speedshopIds === []) {
            return redirect()->route($this->laporanDashboardRoute())->with('error', 'Speedshop tidak terdeteksi.');
        }

        $ctx = $this->resolveSpeedshopRincianBiayaExportContext($request);
        $filename = 'biaya-operasional-speedshop-'.now()->format('Y-m-d-His').'.pdf';

        return Pdf::loadView('speedshop.laporan.rincian-biaya-pdf', [
            'rows' => $ctx['rows'],
            'periodeLabel' => $ctx['periodeLabel'],
            'stats' => $ctx['stats'],
            'filters' => $ctx['filters'],
            'printedAt' => now(),
        ])->setPaper([0, 0, 841.89, 595.28])->stream($filename);
    }

    /**
     * @return array{
     *     rows: \Illuminate\Support\Collection<int, object>,
     *     stats: array{pending: int, disetujui: int, total_nominal: int},
     *     filters: array<string, mixed>,
     *     periodeLabel: string,
     *     cabangLabel: string,
     * }
     */
    protected function resolveSpeedshopRincianBiayaExportContext(Request $request): array
    {
        $period = $this->resolveLaporanPeriodeMode($request, 'bulanan');
        $hasRekeningCol = Schema::hasColumn('speedshop_rincian_biaya_entries', 'speedshop_keuangan_rekening_id');
        $speedshopIds = $this->speedshopScopeIds();
        $approvalStatusFilter = (string) $request->query('approval_status', '');

        $entriesQuery = SpeedshopRincianBiayaEntry::query()
            ->whereBetween('tanggal', [$period['dari']->toDateString(), $period['sampai']->toDateString()])
            ->whereIn('speedshop_id', $speedshopIds);

        if ($approvalStatusFilter === SpeedshopRincianBiayaEntry::STATUS_PENDING) {
            $entriesQuery->where('approval_status', SpeedshopRincianBiayaEntry::STATUS_PENDING);
        } elseif ($approvalStatusFilter === SpeedshopRincianBiayaEntry::STATUS_REJECTED) {
            $entriesQuery->where('approval_status', SpeedshopRincianBiayaEntry::STATUS_REJECTED);
        } elseif ($approvalStatusFilter === SpeedshopRincianBiayaEntry::STATUS_APPROVED) {
            $entriesQuery->where(function ($q) {
                $q->whereNull('approval_status')
                    ->orWhere('approval_status', SpeedshopRincianBiayaEntry::STATUS_APPROVED);
            });
        }

        $this->applyRincianBiayaTableColumnFilters($entriesQuery, $request, $hasRekeningCol);

        $entries = $entriesQuery
            ->with(['keuanganRekening', 'submittedBy', 'adjustments'])
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get();

        $rows = $entries
            ->map(fn (SpeedshopRincianBiayaEntry $row) => $this->mapSpeedshopRincianBiayaExportRow($row, $hasRekeningCol))
            ->values();

        $writableSpeedshopId = $this->writableSpeedshopId();
        $cabangLabel = $writableSpeedshopId
            ? (Speedshop::query()->whereKey($writableSpeedshopId)->value('nama') ?? 'Speedshop')
            : 'Speedshop';

        return [
            'rows' => $rows,
            'stats' => [
                'pending' => $rows->where('approval_status', SpeedshopRincianBiayaEntry::STATUS_PENDING)->count(),
                'disetujui' => $rows->where('counts_toward_total', true)->count(),
                'total_nominal' => (int) round($rows->where('counts_toward_total', true)->sum(fn ($r) => (float) $r->total)),
            ],
            'filters' => $request->only(array_merge(
                ['mode', 'tanggal', 'bulan', 'tahun', 'dari', 'sampai', 'approval_status'],
                $this->rincianBiayaTableColumnFilterKeys(),
            )),
            'periodeLabel' => $this->speedshopRincianBiayaPeriodeLabel($period),
            'cabangLabel' => (string) $cabangLabel,
        ];
    }

    protected function mapSpeedshopRincianBiayaExportRow(SpeedshopRincianBiayaEntry $row, bool $hasRekeningCol): object
    {
        $isAccountingRekening = $hasRekeningCol && (int) ($row->speedshop_keuangan_rekening_id ?? 0) > 0;
        $isAccountingTunaiDirect = ($row->metode ?? 'tunai') === 'tunai'
            && ! $isAccountingRekening
            && (int) ($row->finance_ledger_id ?? 0) > 0
            && $row->isApprovedForReporting();
        $isCash = ($row->metode ?? 'tunai') === 'tunai' && ! $isAccountingRekening;

        $jenis = (string) ($row->jenis_operasional ?? SpeedshopRincianBiayaEntry::JENIS_BIAYA_OPERASIONAL);
        $jenisLabel = $jenis === SpeedshopRincianBiayaEntry::JENIS_BIAYA_NON_OPERASIONAL
            ? 'Biaya Non Operasional'
            : 'Biaya Operasional';

        $st = $row->approval_status;
        $pendingVerifier = null;
        if ($row->isPending()) {
            if ($row->isPendingAccountingRekeningAwaitingManager()) {
                $pendingVerifier = 'manager_accounting_rekening';
            } elseif ($isCash && ! $isAccountingRekening) {
                $pendingVerifier = $row->pendingKasKecilRequiresManagerVerification() ? 'manager' : 'accounting';
            }
        }

        $statusLabel = match ($st) {
            SpeedshopRincianBiayaEntry::STATUS_PENDING => $pendingVerifier === 'manager' || $pendingVerifier === 'manager_accounting_rekening'
                ? 'Menunggu Manager'
                : ($pendingVerifier === 'accounting' ? 'Menunggu Accounting' : 'Menunggu verifikasi'),
            SpeedshopRincianBiayaEntry::STATUS_REJECTED => 'Ditolak',
            SpeedshopRincianBiayaEntry::STATUS_APPROVED => $isAccountingRekening
                ? 'Tercatat (rekening)'
                : ($isAccountingTunaiDirect ? 'Tercatat (tunai)' : 'Disetujui'),
            default => 'Disetujui',
        };

        $rek = $row->keuanganRekening;
        $rekeningLabel = $rek
            ? trim(($rek->nama_bank ? (string) $rek->nama_bank.' ' : '').(string) $rek->nama)
            : ($isAccountingTunaiDirect ? 'Tunai' : null);

        $nominalAsli = (float) ($row->nominal ?? 0);
        $nominalEfektif = (float) $row->nominalEfektif();
        $displayNominal = $nominalEfektif > 0 ? $nominalEfektif : $nominalAsli;
        $tunai = $isCash ? $displayNominal : 0.0;
        $transfer = $isCash ? 0.0 : $displayNominal;
        $total = $tunai + $transfer;

        return (object) [
            'tanggal_label' => $row->tanggal?->format('d/m/Y') ?? '—',
            'jenis_label' => $jenisLabel,
            'no_akun' => (string) $row->no_akun,
            'nama_akun' => (string) $row->nama_akun,
            'keterangan' => (string) ($row->keterangan ?? '-'),
            'pic' => (string) ($row->pic ?? '-'),
            'pengaju' => (string) ($row->submittedBy?->name ?? '-'),
            'status_label' => $statusLabel,
            'approval_status' => $st,
            'tunai' => (int) round($tunai),
            'transfer' => (int) round($transfer),
            'total' => (int) round($total),
            'rekening_label' => $rekeningLabel,
            'counts_toward_total' => $row->isApprovedForReporting(),
        ];
    }

    /** @param array{dari: \Carbon\Carbon, sampai: \Carbon\Carbon, mode: string, selectedDate: string, selectedMonth: string, selectedYear: int, selectedDari: string, selectedSampai: string} $period */
    protected function speedshopRincianBiayaPeriodeLabel(array $period): string
    {
        return match ($period['mode']) {
            'harian' => \Carbon\Carbon::parse($period['selectedDate'])->translatedFormat('d M Y'),
            'bulanan' => \Carbon\Carbon::parse($period['selectedMonth'].'-01')->translatedFormat('F Y'),
            'tahunan' => (string) $period['selectedYear'],
            'range' => $period['dari']->translatedFormat('d M Y').' – '.$period['sampai']->translatedFormat('d M Y'),
            default => $period['dari']->translatedFormat('d M Y'),
        };
    }

    public function exportMekanikPerformance(Request $request)
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\Speedshop\LaporanMekanikPerformanceExport($request->only(['dari', 'sampai'])),
            'laporan-mekanik-performance-'.now()->format('Y-m-d-His').'.xlsx'
        );
    }
}
