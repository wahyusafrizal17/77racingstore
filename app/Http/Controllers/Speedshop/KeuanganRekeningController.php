<?php

namespace App\Http\Controllers\Speedshop;

use App\Http\Controllers\Concerns\ResolvesKeuanganContext;
use App\Http\Controllers\Controller;
use App\Models\SpeedshopKeuanganRekening;
use App\Services\SpeedshopFinanceService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class KeuanganRekeningController extends Controller
{
    use ResolvesKeuanganContext;

    public function index(Request $request, SpeedshopFinanceService $finance)
    {
        if (! Schema::hasTable('speedshop_finance_settings')) {
            abort(503, 'Migrasi keuangan belum dijalankan.');
        }

        $scope = $this->resolveKeuanganScope();
        if ($scope === null) {
            return redirect()->route($this->keuanganDashboardRoute())
                ->with('error', $this->keuanganScopeErrorMessage());
        }

        $asOf = $request->input('sampai_tanggal');
        try {
            $asOfYmd = $asOf ? \Carbon\Carbon::parse($asOf)->toDateString() : now()->toDateString();
        } catch (\Throwable $e) {
            $asOfYmd = now()->toDateString();
        }

        $rekenings = collect();
        $rekeningBankTableReady = Schema::hasTable('speedshop_keuangan_rekenings');
        $hasJenisCol = $rekeningBankTableReady && Schema::hasColumn('speedshop_keuangan_rekenings', 'jenis');
        $saldoBukuPerRekening = [];
        $orphanBalances = ['rekening_utama' => 0, 'cash_besar' => 0];
        if ($rekeningBankTableReady) {
            $q = $scope->applyToRekening(SpeedshopKeuanganRekening::query());
            if ($hasJenisCol && $request->filled('jenis')) {
                $jFilter = (string) $request->input('jenis');
                if (in_array($jFilter, $this->allowedJenisValues(), true)) {
                    $q->where('jenis', $jFilter);
                }
            }
            if ($request->filled('nama')) {
                $term = '%'.$this->escapeLike((string) $request->input('nama')).'%';
                $q->where('nama', 'like', $term);
            }
            if ($request->filled('nama_bank')) {
                $term = '%'.$this->escapeLike((string) $request->input('nama_bank')).'%';
                $q->where('nama_bank', 'like', $term);
            }
            if ($request->filled('nomor')) {
                $term = '%'.$this->escapeLike((string) $request->input('nomor')).'%';
                $q->where('nomor_rekening', 'like', $term);
            }
            $rekenings = $q
                ->orderBy('jenis')
                ->orderBy('nama')
                ->orderBy('id')
                ->get();
            foreach ($rekenings as $r) {
                $isKb = $hasJenisCol && SpeedshopKeuanganRekening::isSecondaryRekeningJenis($r->jenis ?? SpeedshopKeuanganRekening::JENIS_REKENING_UTAMA);
                $saldoBukuPerRekening[$r->id] = $isKb
                    ? $finance->balanceKasBesarForBankAsOfScope($scope, (int) $r->id, $asOfYmd)
                    : $finance->balanceRekeningUtamaForBankAsOfScope($scope, (int) $r->id, $asOfYmd);
            }
        }

        if ($scope->isWarehouse()) {
            $warehouseBalances = $finance->warehouseSummaryBalancesAsOfScope($scope, $asOfYmd, $rekenings);
            $balances = [
                'laci' => $warehouseBalances['laci'],
                'cash_besar' => $warehouseBalances['cash_besar'],
                'rekening_utama' => $warehouseBalances['rekening_utama'],
            ];
            $orphanBalances = [
                'rekening_utama' => $warehouseBalances['orphan_rekening_utama'],
                'cash_besar' => $warehouseBalances['orphan_cash_besar'],
            ];
        } else {
            $balances = $finance->balancesAsOfScope($scope, $asOfYmd);
        }

        $rekeningsJson = [];
        $ledgerRekeningCol = Schema::hasColumn('speedshop_finance_ledgers', 'speedshop_keuangan_rekening_id');
        $includeSaldoKoreksiMeta = $rekeningBankTableReady && $ledgerRekeningCol && auth()->user()?->isAccounting();
        if ($rekeningBankTableReady) {
            $rekeningsJson = $rekenings->map(function (SpeedshopKeuanganRekening $r) use ($hasJenisCol, $includeSaldoKoreksiMeta, $saldoBukuPerRekening, $scope) {
                $row = [
                    'id' => $r->id,
                    'jenis' => $hasJenisCol ? ($r->jenis ?? SpeedshopKeuanganRekening::JENIS_REKENING_UTAMA) : SpeedshopKeuanganRekening::JENIS_REKENING_UTAMA,
                    'nama' => $r->nama,
                    'nama_bank' => $r->nama_bank ?? '',
                    'nomor_rekening' => $r->nomor_rekening,
                    'keterangan' => Schema::hasColumn('speedshop_keuangan_rekenings', 'keterangan') ? (string) ($r->keterangan ?? '') : '',
                    'updateUrl' => $this->keuanganRoute('rekening.update', $r),
                ];
                if ($includeSaldoKoreksiMeta) {
                    $row['saldoBukuUrl'] = $this->keuanganRoute('rekening.saldo-buku', $r);
                    $row['saldoBuku'] = (int) ($saldoBukuPerRekening[$r->id] ?? 0);
                }
                if ($scope->isWarehouse()) {
                    $row['transaksiUrl'] = $this->keuanganRoute('rekening.transaksi', $r);
                }

                return $row;
            })->values()->all();
        }

        return $this->keuanganView('rekening.index', [
            'asOfYmd' => $asOfYmd,
            'isOwnerKeuangan' => $scope->isOwner(),
            'balances' => $balances,
            'rekenings' => $rekenings,
            'rekeningBankTableReady' => $rekeningBankTableReady,
            'saldoBukuPerRekening' => $saldoBukuPerRekening,
            'rekeningsJson' => $rekeningsJson,
            'bukaModalTambah' => $request->query('buka_modal') === 'tambah',
            'bukaModalUbahId' => $request->query('buka_modal') === 'ubah' && $request->filled('rekening')
                ? (int) $request->query('rekening')
                : null,
            'hasRekeningJenisColumn' => $hasJenisCol,
            'jenisOptions' => $this->jenisOptionsMap(),
            'orphanBalances' => $orphanBalances,
        ]);
    }

    /**
     * @return list<string>
     */
    protected function allowedJenisValues(): array
    {
        return [
            SpeedshopKeuanganRekening::JENIS_REKENING_UTAMA,
            SpeedshopKeuanganRekening::JENIS_KAS_BESAR,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function jenisOptionsMap(): array
    {
        return SpeedshopKeuanganRekening::jenisOptions();
    }

    public function create(): RedirectResponse
    {
        if (! Schema::hasTable('speedshop_keuangan_rekenings')) {
            abort(503, 'Migrasi rekening belum dijalankan.');
        }

        return redirect()->to($this->keuanganRoute('rekening', ['buka_modal' => 'tambah']));
    }

    public function store(Request $request)
    {
        if (! Schema::hasTable('speedshop_keuangan_rekenings')) {
            abort(503, 'Migrasi rekening belum dijalankan.');
        }

        $scope = $this->resolveKeuanganScope();
        if ($scope === null) {
            return redirect()->route($this->keuanganDashboardRoute())
                ->with('error', $this->keuanganScopeErrorMessage());
        }
        if (! $this->canManageRekening()) {
            abort(403);
        }
        $data = $this->validatedRekeningPayload($request);

        SpeedshopKeuanganRekening::query()->create(array_merge($data, $scope->ownershipAttributes()));

        return redirect()->to($this->keuanganRoute('rekening'))
            ->with('success', 'Rekening berhasil ditambahkan.');
    }

    public function edit(SpeedshopKeuanganRekening $rekening): RedirectResponse
    {
        if (! Schema::hasTable('speedshop_keuangan_rekenings')) {
            abort(503, 'Migrasi rekening belum dijalankan.');
        }

        if ($this->resolveKeuanganScope() === null) {
            return redirect()->route($this->keuanganDashboardRoute())
                ->with('error', $this->keuanganScopeErrorMessage());
        }
        $this->authorizeKeuanganRekening($rekening);

        return redirect()->to($this->keuanganRoute('rekening', [
            'buka_modal' => 'ubah',
            'rekening' => $rekening->id,
        ]));
    }

    public function update(Request $request, SpeedshopKeuanganRekening $rekening)
    {
        if (! Schema::hasTable('speedshop_keuangan_rekenings')) {
            abort(503, 'Migrasi rekening belum dijalankan.');
        }

        if ($this->resolveKeuanganScope() === null) {
            return redirect()->route($this->keuanganDashboardRoute())
                ->with('error', $this->keuanganScopeErrorMessage());
        }
        if (! $this->canManageRekening()) {
            abort(403);
        }
        $this->authorizeKeuanganRekening($rekening);

        $rekening->update($this->validatedRekeningPayload($request));

        return redirect()->to($this->keuanganRoute('rekening'))
            ->with('success', 'Rekening berhasil diperbarui.');
    }

    public function destroy(SpeedshopKeuanganRekening $rekening)
    {
        if (! Schema::hasTable('speedshop_keuangan_rekenings')) {
            abort(503, 'Migrasi rekening belum dijalankan.');
        }

        if ($this->resolveKeuanganScope() === null) {
            return redirect()->route($this->keuanganDashboardRoute())
                ->with('error', $this->keuanganScopeErrorMessage());
        }
        if (! $this->canManageRekening()) {
            abort(403);
        }
        $this->authorizeKeuanganRekening($rekening);

        $scope = $this->resolveKeuanganScope();
        $finance = app(SpeedshopFinanceService::class);
        $asOfYmd = now()->toDateString();
        $isSecondary = SpeedshopKeuanganRekening::isSecondaryRekeningJenis($rekening->jenis ?? null);
        $saldoBuku = $isSecondary
            ? $finance->balanceKasBesarForBankAsOfScope($scope, (int) $rekening->id, $asOfYmd)
            : $finance->balanceRekeningUtamaForBankAsOfScope($scope, (int) $rekening->id, $asOfYmd);

        if ($saldoBuku !== 0) {
            return redirect()->to($this->keuanganRoute('rekening'))
                ->with('error', 'Rekening tidak dapat dihapus karena masih memiliki saldo buku Rp '
                    .number_format($saldoBuku, 0, ',', '.')
                    .'. Kosongkan saldo terlebih dahulu (transfer, pemasukan, atau koreksi saldo) lalu coba lagi.');
        }

        $rekening->delete();

        return redirect()->to($this->keuanganRoute('rekening'))
            ->with('success', 'Rekening berhasil dihapus.');
    }

    public function updateModal(Request $request, SpeedshopFinanceService $finance)
    {
        $request->validate([
            'modal_laci_per_hari' => ['required', 'integer', 'min:0', 'max:999999999'],
        ]);

        $scope = $this->resolveKeuanganScope();
        if (! $scope) {
            return back()->with('error', $this->keuanganScopeErrorMessage());
        }

        if (! auth()->user()->isAccounting()) {
            abort(403);
        }

        $finance->updateModalLaciPerHariForScope($scope, (int) $request->input('modal_laci_per_hari'));

        return back()->with('success', 'Modal pegangan Kas Kecil per hari diperbarui.');
    }

    public function adjustSaldoBuku(Request $request, SpeedshopKeuanganRekening $rekening, SpeedshopFinanceService $finance): RedirectResponse
    {
        if (! Schema::hasTable('speedshop_finance_ledgers')) {
            abort(503, 'Migrasi keuangan belum dijalankan.');
        }
        if (! auth()->user()->isAccounting()) {
            abort(403);
        }

        $scope = $this->resolveKeuanganScope();
        if ($scope === null) {
            return redirect()->route($this->keuanganDashboardRoute())
                ->with('error', $this->keuanganScopeErrorMessage());
        }
        $this->authorizeKeuanganRekening($rekening);

        $validated = $request->validate([
            'saldo_buku_target' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'sampai_tanggal' => ['required', 'date'],
        ]);

        try {
            $effectiveYmd = Carbon::parse($validated['sampai_tanggal'])->toDateString();
        } catch (\Throwable $e) {
            return back()->withErrors(['sampai_tanggal' => 'Tanggal tidak valid.'])->withInput();
        }

        if ($effectiveYmd > now()->toDateString()) {
            return back()->withErrors(['sampai_tanggal' => 'Tanggal cutoff tidak boleh di masa depan.'])->withInput();
        }

        try {
            $out = $finance->applyManagerBankBookSaldoKoreksiForScope(
                $scope,
                $rekening,
                $effectiveYmd,
                (int) $validated['saldo_buku_target'],
                (int) auth()->id(),
            );
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        $label = $rekening->nama;
        if ($out['delta'] === 0) {
            return back()->with('success', 'Saldo buku '.$label.' sudah sesuai target (Rp '.number_format($out['target'], 0, ',', '.').').');
        }

        $sign = $out['delta'] > 0 ? '+' : '';
        $msg = 'Saldo buku '.$label.' diperbarui: '.$sign.'Rp '.number_format($out['delta'], 0, ',', '.').
            ' (sebelum Rp '.number_format($out['current'], 0, ',', '.').
            ' → target Rp '.number_format($out['target'], 0, ',', '.').
            ', per tanggal '.$effectiveYmd.').';

        return back()->with('success', $msg);
    }

    /**
     * @return array{nama: string, nama_bank: ?string, nomor_rekening: string, atas_nama: string, keterangan: ?string, jenis?: string}
     */
    private function validatedRekeningPayload(Request $request): array
    {
        $rules = [
            'nama' => ['required', 'string', 'max:160'],
            'nama_bank' => ['nullable', 'string', 'max:120'],
            'nomor_rekening' => ['required', 'string', 'max:64'],
        ];
        if (Schema::hasColumn('speedshop_keuangan_rekenings', 'jenis')) {
            $rules['jenis'] = ['required', 'string', 'in:'.implode(',', $this->allowedJenisValues())];
        }
        if (Schema::hasColumn('speedshop_keuangan_rekenings', 'keterangan')) {
            $rules['keterangan'] = ['nullable', 'string', 'max:5000'];
        }

        $validated = $request->validate($rules);

        $nama = trim($validated['nama']);
        $out = [
            'nama' => $nama,
            'nama_bank' => isset($validated['nama_bank']) && trim((string) $validated['nama_bank']) !== ''
                ? trim((string) $validated['nama_bank'])
                : null,
            'nomor_rekening' => trim($validated['nomor_rekening']),
            'atas_nama' => $nama,
        ];
        if (Schema::hasColumn('speedshop_keuangan_rekenings', 'keterangan')) {
            $ket = isset($validated['keterangan']) ? trim((string) $validated['keterangan']) : '';
            $out['keterangan'] = $ket === '' ? null : $ket;
        }
        if (Schema::hasColumn('speedshop_keuangan_rekenings', 'jenis')) {
            $out['jenis'] = (string) $validated['jenis'];
        }

        return $out;
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    protected function canManageRekening(): bool
    {
        $user = auth()->user();

        return $user && ($user->isAccounting() || $user->isOwner());
    }
}
