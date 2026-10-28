<?php

namespace App\Http\Controllers\Speedshop;

use App\Exports\Speedshop\SetoranSpeedshopExport;
use App\Http\Controllers\Concerns\ResolvesKeuanganContext;
use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\Speedshop;
use App\Models\SpeedshopFinanceSetoran;
use App\Models\SpeedshopFinanceSetoranItem;
use App\Models\SpeedshopKeuanganRekening;
use App\Models\User;
use App\Services\SpeedshopFinanceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use League\Flysystem\UnableToWriteFile;

class KeuanganSetoranController extends Controller
{
    use ResolvesKeuanganContext;

    public function index(Request $request, SpeedshopFinanceService $finance): View|RedirectResponse
    {
        if (! Schema::hasTable('speedshop_finance_setorans')) {
            abort(503, 'Migrasi keuangan belum dijalankan.');
        }

        $scope = $this->resolveKeuanganScope();
        if (! $scope) {
            return redirect()->route($this->keuanganDashboardRoute())
                ->with('error', $this->keuanganScopeErrorMessage());
        }

        $pendingDates = $scope->isWarehouse()
            ? []
            : array_slice($finance->pendingSetoranDatesBeforeToday((int) $scope->speedshopId), 0, 120);

        $ctx = $this->buildSetoranListContext($request, $scope);

        $setorans = (clone $ctx['listQuery'])
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'completed' THEN 1 ELSE 2 END")
            ->orderByDesc('tanggal_setor')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return $this->keuanganView('setoran.index', [
            'setorans' => $setorans,
            'pendingDates' => $pendingDates,
            'filters' => $ctx['filters'],
            'setoranStats' => $ctx['setoranStats'],
            'exportQuery' => $ctx['exportQuery'],
            'mode' => $ctx['mode'],
            'selectedDate' => $ctx['selectedDate'],
            'selectedMonth' => $ctx['selectedMonth'],
            'selectedYear' => $ctx['selectedYear'],
            'selectedDari' => $ctx['selectedDari'],
            'selectedSampai' => $ctx['selectedSampai'],
            'dari' => $ctx['dari'],
            'sampai' => $ctx['sampai'],
        ]);
    }

    public function exportExcel(Request $request): BinaryFileResponse|RedirectResponse
    {
        if (! Schema::hasTable('speedshop_finance_setorans')) {
            abort(503, 'Migrasi keuangan belum dijalankan.');
        }

        $scope = $this->resolveKeuanganScope();
        if (! $scope) {
            return redirect()->route($this->keuanganDashboardRoute())
                ->with('error', $this->keuanganScopeErrorMessage());
        }

        $ctx = $this->buildSetoranListContext($request, $scope);
        $rows = (clone $ctx['listQuery'])
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'completed' THEN 1 ELSE 2 END")
            ->orderByDesc('tanggal_setor')
            ->orderByDesc('id')
            ->get();

        $cabangLabel = $scope->isWarehouse()
            ? 'Gudang'
            : (Speedshop::query()->whereKey($scope->speedshopId)->value('nama') ?? 'Speedshop');

        return Excel::download(
            new SetoranSpeedshopExport($rows, $ctx['periodeLabel'], $ctx['setoranStats'], (string) $cabangLabel),
            'setoran-speedshop-'.now()->format('Y-m-d-His').'.xlsx'
        );
    }

    public function exportPdf(Request $request): StreamedResponse|RedirectResponse
    {
        if (! Schema::hasTable('speedshop_finance_setorans')) {
            abort(503, 'Migrasi keuangan belum dijalankan.');
        }

        $scope = $this->resolveKeuanganScope();
        if (! $scope) {
            return redirect()->route($this->keuanganDashboardRoute())
                ->with('error', $this->keuanganScopeErrorMessage());
        }

        $ctx = $this->buildSetoranListContext($request, $scope);
        $rows = (clone $ctx['listQuery'])
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'completed' THEN 1 ELSE 2 END")
            ->orderByDesc('tanggal_setor')
            ->orderByDesc('id')
            ->get();
        $filename = 'setoran-speedshop-'.now()->format('Y-m-d-His').'.pdf';

        return Pdf::loadView($this->keuanganViewPrefix().'.setoran.pdf', [
            'rows' => $rows,
            'periodeLabel' => $ctx['periodeLabel'],
            'stats' => $ctx['setoranStats'],
            'filters' => $ctx['filters'],
            'printedAt' => now(),
        ])->setPaper('a4', 'landscape')->stream($filename);
    }

    public function snapshot(Request $request, SpeedshopFinanceService $finance): JsonResponse
    {
        if (! Schema::hasTable('speedshop_finance_setorans')) {
            return response()->json(['message' => 'Migrasi belum dijalankan.'], 503);
        }

        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
        ]);

        $scope = $this->resolveKeuanganScope();
        if (! $scope) {
            return response()->json(['message' => $this->keuanganScopeErrorMessage()], 403);
        }

        $tanggal = Carbon::parse($validated['tanggal'])->toDateString();

        $snap = $finance->computeDaySetoranSnapshotForScope($scope, $tanggal);
        $missingBefore = $scope->isWarehouse()
            ? []
            : $finance->blockingSetoranDatesBeforeDate((int) $scope->speedshopId, $tanggal);

        return response()->json([
            'tanggal' => $tanggal,
            'snap' => $snap,
            'missing_before' => $missingBefore,
        ]);
    }

    public function create(Request $request, SpeedshopFinanceService $finance): View|RedirectResponse
    {
        if (! Schema::hasTable('speedshop_finance_setorans')) {
            abort(503, 'Migrasi keuangan belum dijalankan.');
        }

        $scope = $this->resolveKeuanganScope();
        if (! $scope) {
            return redirect()->route($this->keuanganDashboardRoute())
                ->with('error', $this->keuanganScopeErrorMessage());
        }

        $defaultTanggal = old('tanggal_setor', $request->input('setoran_tanggal', now()->toDateString()));
        if (is_string($defaultTanggal)) {
            try {
                $defaultTanggal = Carbon::parse($defaultTanggal)->toDateString();
            } catch (\Throwable $e) {
                $defaultTanggal = now()->toDateString();
            }
        } else {
            $defaultTanggal = now()->toDateString();
        }

        $rekeningsUtama = collect();
        $rekeningsKasBesar = collect();
        $rekeningsUtamaJson = [];
        $rekeningsKasBesarJson = [];
        $banksRequired = false;
        $hasKasBesarBanks = false;
        $kasBesarUsesUtamaFallback = false;
        if (Schema::hasTable('speedshop_keuangan_rekenings')) {
            $hasJenisCol = Schema::hasColumn('speedshop_keuangan_rekenings', 'jenis');
            $asOfSaldo = $defaultTanggal;

            $utamaRows = $scope->applyToRekening(SpeedshopKeuanganRekening::query())
                ->when($hasJenisCol, fn ($q) => $q->where('jenis', SpeedshopKeuanganRekening::JENIS_REKENING_UTAMA))
                ->orderBy('nama')
                ->orderBy('id')
                ->get();

            $kasRowsDedicated = collect();
            if ($hasJenisCol) {
                $kasRowsDedicated = $scope->applyToRekening(SpeedshopKeuanganRekening::query())
                    ->whereIn('jenis', SpeedshopKeuanganRekening::secondaryJenisValues())
                    ->orderBy('nama')
                    ->orderBy('id')
                    ->get();
            }

            // Bila belum ada satupun rekening berjenis Kas Besar (semua masih Rekening Utama / skema tanpa jenis),
            // tampilkan daftar Kas Besar yang sama dengan Rekening Utama agar sumber digital tetap bisa dipilih.
            $kasRowsForSetoran = $kasRowsDedicated->isNotEmpty()
                ? $kasRowsDedicated
                : $utamaRows;

            $mapRek = function (SpeedshopKeuanganRekening $r, bool $isKasBesar) use ($scope, $finance, $asOfSaldo) {
                $saldoBuku = $isKasBesar
                    ? $finance->balanceKasBesarForBankAsOfScope($scope, (int) $r->id, $asOfSaldo)
                    : $finance->balanceRekeningUtamaForBankAsOfScope($scope, (int) $r->id, $asOfSaldo);

                return [
                    'id' => $r->id,
                    'nama' => $r->nama,
                    'nama_bank' => $r->nama_bank ?? '',
                    'nomor_rekening' => $r->nomor_rekening ?? '',
                    'saldo_buku' => (int) $saldoBuku,
                ];
            };

            $rekeningsUtama = $utamaRows->map(fn (SpeedshopKeuanganRekening $r) => $mapRek($r, false))->values();
            $rekeningsKasBesar = $kasRowsForSetoran->map(fn (SpeedshopKeuanganRekening $r) => $mapRek($r, true))->values();
            $rekeningsUtamaJson = $rekeningsUtama->all();
            $rekeningsKasBesarJson = $rekeningsKasBesar->all();
            $banksRequired = $rekeningsUtama->isNotEmpty();
            $hasKasBesarBanks = $rekeningsKasBesar->isNotEmpty();
            $kasBesarUsesUtamaFallback = $hasJenisCol && $kasRowsDedicated->isEmpty() && $utamaRows->isNotEmpty();
        }

        return $this->keuanganView('setoran.create', [
            'snapshotUrl' => $this->keuanganRoute('setoran.snapshot'),
            'defaultTanggal' => $defaultTanggal,
            'rekeningsUtama' => $rekeningsUtama,
            'rekeningsUtamaJson' => $rekeningsUtamaJson,
            'rekeningsKasBesarJson' => $rekeningsKasBesarJson,
            'hasKasBesarBanks' => $hasKasBesarBanks,
            'kasBesarUsesUtamaFallback' => $kasBesarUsesUtamaFallback,
            'hasKasBesarSetoranCol' => Schema::hasColumn('speedshop_finance_setorans', 'speedshop_keuangan_kas_besar_id'),
            'hasSetorKkKeKbCol' => Schema::hasColumn('speedshop_finance_setorans', 'setor_kk_ke_kb_actual'),
            'banksRequired' => $banksRequired,
            'buktiSplitEnabled' => Schema::hasColumn('speedshop_finance_setorans', 'bukti_kas_kecil_path'),
            'isAccounting' => auth()->user()->isAccounting(),
        ]);
    }

    public function show(SpeedshopFinanceSetoran $setoran, SpeedshopFinanceService $finance): View|RedirectResponse
    {
        if (! Schema::hasTable('speedshop_finance_setorans')) {
            abort(503, 'Migrasi keuangan belum dijalankan.');
        }

        $scope = $this->resolveKeuanganScope();
        if (! $scope) {
            return redirect()->route($this->keuanganDashboardRoute())
                ->with('error', $this->keuanganScopeErrorMessage());
        }

        $this->authorizeKeuanganSetoran($setoran);

        $with = ['user', 'keuanganRekening', 'keuanganKasBesar', 'approver'];
        if (Schema::hasTable('speedshop_finance_setoran_items')) {
            $with[] = 'items';
            $with[] = 'items.sumberKasBesar';
            $with[] = 'items.sumberRekeningUtama';
            $with[] = 'items.tujuanRekening';
        }
        $setoran->load($with);

        $saldoByRekId = [];
        if (Schema::hasTable('speedshop_finance_setoran_items')) {
            $tanggal = $setoran->tanggal_setor?->toDateString() ?? now()->toDateString();
            $items = $setoran->items ?? collect();
            $rekIdsKasBesar = $items->pluck('sumber_kas_besar_id')->filter()->merge(
                $items->where('tujuan_type', 'kas_besar')->pluck('tujuan_rekening_id')->filter()
            )->unique()->values();
            $rekIdsUtama = $items->pluck('sumber_rekening_id')->filter()->merge(
                $items->where('tujuan_type', 'rekening_utama')->pluck('tujuan_rekening_id')->filter()
            )->unique()->values();

            foreach ($rekIdsKasBesar as $rid) {
                $saldoByRekId[(int) $rid] = (int) $finance->balanceKasBesarForBankAsOfScope($scope, (int) $rid, $tanggal);
            }
            foreach ($rekIdsUtama as $rid) {
                $saldoByRekId[(int) $rid] = (int) $finance->balanceRekeningUtamaForBankAsOfScope($scope, (int) $rid, $tanggal);
            }
        }

        return $this->keuanganView('setoran.show', [
            'setoran' => $setoran,
            'saldoByRekId' => $saldoByRekId,
        ]);
    }

    public function store(Request $request, SpeedshopFinanceService $finance): RedirectResponse
    {
        if (! Schema::hasTable('speedshop_finance_setorans')) {
            abort(503, 'Migrasi keuangan belum dijalankan.');
        }

        $scope = $this->resolveKeuanganScope();
        if (! $scope) {
            return back()->with('error', $this->keuanganScopeErrorMessage());
        }

        $hasSetorKkKeKbCol = Schema::hasColumn('speedshop_finance_setorans', 'setor_kk_ke_kb_actual');

        $hasBankTable = Schema::hasTable('speedshop_keuangan_rekenings');
        $anyBank = $hasBankTable && $scope->applyToRekening(SpeedshopKeuanganRekening::query())->exists();
        $hasJenisCol = $hasBankTable && Schema::hasColumn('speedshop_keuangan_rekenings', 'jenis');
        $hasRekeningUtama = $hasBankTable && (
            ! $hasJenisCol
                ? $anyBank
                : $scope->applyToRekening(SpeedshopKeuanganRekening::query())
                    ->where('jenis', SpeedshopKeuanganRekening::JENIS_REKENING_UTAMA)
                    ->exists()
        );

        $kbDedicated = $hasJenisCol && $scope->applyToRekening(SpeedshopKeuanganRekening::query())
            ->whereIn('jenis', SpeedshopKeuanganRekening::secondaryJenisValues())
            ->exists();

        $hasKasBesarSetoranCol = Schema::hasColumn('speedshop_finance_setorans', 'speedshop_keuangan_kas_besar_id');

        if ($hasBankTable && ! $anyBank) {
            return back()->withInput()->with('error', 'Tambah minimal satu rekening bank di menu Keuangan > Rekening terlebih dahulu untuk mencatat setoran.');
        }
        if ($hasBankTable && $anyBank && ! $hasRekeningUtama) {
            return back()->withInput()->with('error', 'Untuk setoran diperlukan minimal satu rekening berjenis Rekening Utama. Atur jenis rekening di menu Keuangan > Rekening.');
        }

        // Form setoran sekarang memakai transaksi dinamis per baris (transactions[]),
        // jadi tidak lagi membutuhkan field statis speedshop_keuangan_rekening_id / bukti_kas_kecil_path, dst.
        $banksExist = $hasRekeningUtama;
        $fileMime = ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'];

        $rules = [
            'tanggal_setor' => ['required', 'date'],
            'catatan' => ['nullable', 'string', 'max:2000'],
            'transactions' => ['required', 'array', 'min:1'],
            'transactions.*.source' => ['required', 'in:kas_kecil,kas_besar,rekening_utama'],
            'transactions.*.target' => ['required', 'in:kas_kecil,kas_besar,rekening_utama'],
            'transactions.*.amount' => ['required', 'integer', 'min:0'],
            'transactions.*.note' => ['nullable', 'string', 'max:255'],
            // Wajib ada di rules agar tidak ter-strip oleh Validator (dipakai untuk indeks bukti[]).
            'transactions.*._idx' => ['nullable', 'integer', 'min:0'],
            'transactions.*.source_bank_id' => ['nullable', 'integer'],
            'transactions.*.target_bank_id' => ['nullable', 'integer'],
        ];

        $txnInput = (array) $request->input('transactions', []);
        $txnCount = count($txnInput);
        for ($i = 0; $i < $txnCount; $i++) {
            $amount = (int) ($txnInput[$i]['amount'] ?? 0);
            $rules['bukti.'.$i] = $amount > 0
                ? array_merge(['required'], $fileMime)
                : array_merge(['nullable'], $fileMime);
        }

        $validated = $request->validate($rules);

        $tanggal = Carbon::parse($validated['tanggal_setor'])->toDateString();
        $transactions = (array) ($validated['transactions'] ?? []);
        $totalNominalInput = (int) collect($transactions)->sum(fn ($t) => (int) ($t['amount'] ?? 0));

        $pairSeen = [];
        foreach ($transactions as $t) {
            $src = (string) ($t['source'] ?? '');
            $dst = (string) ($t['target'] ?? '');
            $srcBank = (int) ($t['source_bank_id'] ?? 0);
            $dstBank = (int) ($t['target_bank_id'] ?? 0);
            if ($src === 'kas_kecil') {
                $srcBank = 0;
            }
            if ($dst === 'kas_kecil') {
                $dstBank = 0;
            }
            $sig = $src."\0".$srcBank."\0".$dst."\0".$dstBank;
            if (isset($pairSeen[$sig])) {
                return back()->withInput()->with(
                    'error',
                    'Pasangan sumber dan tujuan yang sama tidak boleh dipakai lebih dari satu baris. Gabungkan nominal dalam satu baris atau ubah kombinasinya.',
                );
            }
            $pairSeen[$sig] = true;
        }

        $pendingApproval = ! auth()->user()->isAccounting();

        try {
            if ($scope->isWarehouse()) {
                $buktiStorePrefix = 'warehouse/gudang-'.$scope->warehouseId.'/setoran/files';
            } else {
                $speedshopRow = Speedshop::query()->find($scope->speedshopId);
                if (! $speedshopRow) {
                    return back()->withInput()->with('error', 'Cabang speedshop tidak ditemukan.');
                }
                $outletCode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $speedshopRow->invoiceOutletCode()) ?: 'UNK');
                $buktiStorePrefix = 'speedshop/'.$outletCode.'-'.$scope->speedshopId.'/setoran/files';
            }

            $uploadDisk = (string) config('filesystems.upload_disk', 'public');
            $storedBukti = [];
            $files = $request->file('bukti', []);
            foreach ((array) $files as $idx => $file) {
                if (! $file) {
                    continue;
                }
                if (method_exists($file, 'isValid') && ! $file->isValid()) {
                    throw new \InvalidArgumentException(
                        'Bukti transaksi baris #'.((int) $idx + 1).' gagal diunggah. Periksa ukuran/format file atau batas upload server (php.ini).',
                    );
                }
                $path = $file->store($buktiStorePrefix, $uploadDisk);
                if (! is_string($path) || $path === '') {
                    throw new \InvalidArgumentException(
                        'Bukti transaksi baris #'.((int) $idx + 1).' gagal disimpan ke penyimpanan ('.$uploadDisk.').',
                    );
                }
                $storedBukti[(int) $idx] = $path;
            }

            $row = DB::transaction(function () use ($finance, $scope, $tanggal, $pendingApproval, $validated, $transactions, $storedBukti) {
                $row = $finance->submitSetoranForScope(
                    $scope,
                    $tanggal,
                    0,
                    0,
                    $validated['catatan'] ?? null,
                    null,
                    null,
                    null,
                    null,
                    $pendingApproval,
                    0,
                );

                $sumKkKb = 0;
                $sumKkRu = 0;
                $sumKbRu = 0;

                foreach (array_values($transactions) as $rowIdx => $t) {
                    // Selaraskan dengan input name bukti[rowIdx]. Field _idx ikut rules agar tidak ter-strip
                    // oleh Validator (tanpa itu, bukti_path selalu null meskipun file ter-upload).
                    $src = (string) ($t['source'] ?? '');
                    $dst = (string) ($t['target'] ?? '');
                    $amt = (int) ($t['amount'] ?? 0);
                    if ($amt <= 0) {
                        continue;
                    }
                    if ($src === $dst) {
                        throw new \InvalidArgumentException('Sumber dan tujuan tidak boleh sama.');
                    }

                    $srcBank = (int) ($t['source_bank_id'] ?? 0);
                    $dstBank = (int) ($t['target_bank_id'] ?? 0);
                    if (in_array($src, ['kas_besar', 'rekening_utama'], true) && $srcBank <= 0) {
                        throw new \InvalidArgumentException('Rekening sumber wajib dipilih untuk transaksi Bank.');
                    }
                    if (in_array($dst, ['kas_besar', 'rekening_utama'], true) && $dstBank <= 0) {
                        throw new \InvalidArgumentException('Rekening tujuan wajib dipilih untuk transaksi Bank.');
                    }

                    if ($src === 'kas_kecil' && $dst === 'kas_besar') {
                        $sumKkKb += $amt;
                    }
                    if ($src === 'kas_kecil' && $dst === 'rekening_utama') {
                        $sumKkRu += $amt;
                    }
                    if ($src === 'kas_besar' && $dst === 'rekening_utama') {
                        $sumKbRu += $amt;
                    }

                    SpeedshopFinanceSetoranItem::query()->create([
                        'setoran_id' => $row->id,
                        'sumber' => $src,
                        'nominal' => $amt,
                        'sumber_kas_besar_id' => $src === 'kas_besar' ? $srcBank : null,
                        'sumber_rekening_id' => $src === 'rekening_utama' ? $srcBank : null,
                        'tujuan_type' => $dst,
                        'tujuan_rekening_id' => in_array($dst, ['kas_besar', 'rekening_utama'], true) ? $dstBank : null,
                        'keterangan' => $t['note'] ?? null,
                        'bukti_path' => $storedBukti[$rowIdx] ?? null,
                    ]);
                }

                $row->update([
                    'setor_tunai_actual' => $sumKkRu,
                    'setor_digital_actual' => $sumKbRu,
                    'setor_kk_ke_kb_actual' => $sumKkKb,
                ]);

                $row = $row->fresh();
                if (! $pendingApproval) {
                    $finance->postSetoranLedgersAfterItemsSaved($row);
                }

                return $row->fresh();
            });
        } catch (\InvalidArgumentException $e) {
            foreach (array_values($storedBukti ?? []) as $p) {
                Storage::disk((string) config('filesystems.upload_disk', 'public'))->delete($p);
            }

            return back()->withInput()->with('error', $e->getMessage());
        } catch (UnableToWriteFile $e) {
            foreach (array_values($storedBukti ?? []) as $p) {
                Storage::disk((string) config('filesystems.upload_disk', 'public'))->delete($p);
            }

            return back()->withInput()->with(
                'error',
                'Gagal mengunggah bukti ke penyimpanan cloud. Server tidak bisa menjangkau host penyimpanan (biasanya masalah DNS atau koneksi internet). Coba lagi, ganti jaringan/VPN, atau hubungi IT. Untuk pengembangan di mesin lokal tanpa akses ke cloud, sementara set FILES_UPLOAD_DISK=public di .env.',
            );
        } catch (\Throwable $e) {
            foreach (array_values($storedBukti ?? []) as $p) {
                Storage::disk((string) config('filesystems.upload_disk', 'public'))->delete($p);
            }
            throw $e;
        }

        $msg = $pendingApproval
            ? ($totalNominalInput > 0
                ? 'Setoran diajukan. Menunggu verifikasi Accounting.'
                : 'Setoran nol (tanpa tunai) diajukan. Menunggu verifikasi Accounting.')
            : ($totalNominalInput > 0
                ? 'Setoran dicatat selesai dan jurnal kas telah diposting.'
                : 'Setoran nol (tanpa tunai) dicatat selesai.');

        if ($pendingApproval) {
            $speedshopLabel = $scope->isWarehouse()
                ? 'Gudang'
                : (Speedshop::query()->whereKey($scope->speedshopId)->value('nama') ?? 'Cabang');
            $pemohon = auth()->user()?->name ?? 'Pengguna';
            $tanggalLabel = Carbon::parse($tanggal)->format('d/m/Y');
            $totalNominal = $totalNominalInput;
            $nominalFormatted = number_format($totalNominal, 0, ',', '.');
            $nominalPhrase = $totalNominal > 0
                ? "Rp {$nominalFormatted}"
                : 'Rp 0 (tanpa setoran tunai)';
            // Untuk user Accounting: arahkan ke URL Verifikasi agar sidebar active tidak salah.
            $link = route('speedshop.verifikasi.setoran.show', $row);

            $accountingUsers = $scope->isWarehouse()
                ? User::accountingUsersForWarehouse((int) $scope->warehouseId)
                : User::accountingUsersForSpeedshop((int) $scope->speedshopId);

            foreach ($accountingUsers as $acct) {
                AppNotification::create([
                    'user_id' => $acct->id,
                    'type' => 'speedshop_setoran_pending',
                    'title' => 'Setoran menunggu verifikasi',
                    'message' => "{$pemohon} mengajukan setoran {$nominalPhrase} (tanggal setor {$tanggalLabel}) — {$speedshopLabel}.",
                    'data' => array_filter([
                        'speedshop_finance_setoran_id' => $row->id,
                        'speedshop_id' => $scope->speedshopId,
                        'warehouse_id' => $scope->warehouseId,
                    ]),
                    'link' => $link,
                ]);
            }
        }

        return redirect()->to($this->keuanganRoute('setoran.show', $row))
            ->with('success', $msg);
    }

    public function approve(Request $request, SpeedshopFinanceSetoran $setoran, SpeedshopFinanceService $finance): RedirectResponse
    {
        abort_unless(auth()->user()->isAccounting(), 403);

        if (! Schema::hasTable('speedshop_finance_setorans')) {
            abort(503);
        }

        $scope = $this->resolveKeuanganScope();
        if (! $scope || ! $scope->ownsSetoran($setoran)) {
            abort(403);
        }

        try {
            $finance->approveSetoran($setoran);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Setoran disetujui dan jurnal kas telah diposting.');
    }

    public function reject(Request $request, SpeedshopFinanceSetoran $setoran, SpeedshopFinanceService $finance): RedirectResponse
    {
        abort_unless(auth()->user()->isAccounting(), 403);

        if (! Schema::hasTable('speedshop_finance_setorans')) {
            abort(503);
        }

        $scope = $this->resolveKeuanganScope();
        if (! $scope || ! $scope->ownsSetoran($setoran)) {
            abort(403);
        }

        $validated = $request->validate([
            'rejected_reason' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        try {
            $finance->rejectSetoran($setoran, $validated['rejected_reason']);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Setoran ditolak.');
    }

    /**
     * @return array{
     *     filters: array<string, mixed>,
     *     setoranStats: array{total: int, pending: int, completed: int, rejected: int},
     *     listQuery: \Illuminate\Database\Eloquent\Builder,
     *     periodeLabel: string,
     *     exportQuery: array<string, mixed>,
     *     mode: string,
     *     selectedDate: string,
     *     selectedMonth: string,
     *     selectedYear: int,
     *     selectedDari: string,
     *     selectedSampai: string,
     *     dari: \Carbon\Carbon,
     *     sampai: \Carbon\Carbon,
     * }
     */
    private function buildSetoranListContext(Request $request, \App\Support\KeuanganScope $scope): array
    {
        $clean = $request->only(['status', 'tanggal_setor', 'pengaju', 'rekening']);
        foreach (['status', 'tanggal_setor', 'pengaju', 'rekening'] as $k) {
            if (($clean[$k] ?? '') === '') {
                unset($clean[$k]);
            }
        }
        $filters = Validator::make($clean, [
            'status' => ['nullable', 'string', 'in:pending,completed,rejected'],
            'tanggal_setor' => ['nullable', 'date'],
            'pengaju' => ['nullable', 'string', 'max:120'],
            'rekening' => ['nullable', 'string', 'max:120'],
        ])->validate();

        $period = $this->resolveSetoranPeriod($request);
        $periodStart = $period['dari']->toDateString();
        $periodEnd = $period['sampai']->toDateString();

        $q = $scope->applyToSetoran(SpeedshopFinanceSetoran::query())
            ->whereIn('status', ['pending', 'completed', 'rejected'])
            ->with(['user', 'keuanganRekening', 'approver'])
            ->whereDate('tanggal_setor', '>=', $periodStart)
            ->whereDate('tanggal_setor', '<=', $periodEnd);

        if (! empty($filters['tanggal_setor'])) {
            $q->whereDate('tanggal_setor', Carbon::parse($filters['tanggal_setor'])->toDateString());
        }
        if (! empty($filters['pengaju'])) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $filters['pengaju']).'%';
            $q->whereHas('user', fn ($uq) => $uq->where('name', 'like', $term));
        }
        if (! empty($filters['rekening'])) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $filters['rekening']).'%';
            $q->whereHas('keuanganRekening', function ($kq) use ($term) {
                $kq->where('nama', 'like', $term)
                    ->orWhere('nama_bank', 'like', $term)
                    ->orWhere('nomor_rekening', 'like', $term);
            });
        }

        $statsQuery = clone $q;
        $setoranStats = [
            'total' => (int) (clone $statsQuery)->count(),
            'pending' => (int) (clone $statsQuery)->where('status', 'pending')->count(),
            'completed' => (int) (clone $statsQuery)->where('status', 'completed')->count(),
            'rejected' => (int) (clone $statsQuery)->where('status', 'rejected')->count(),
        ];

        if (! empty($filters['status'])) {
            $q->where('status', $filters['status']);
        }

        $periodeLabel = match ($period['mode']) {
            'harian' => Carbon::parse($period['selectedDate'])->translatedFormat('d M Y'),
            'bulanan' => Carbon::parse($period['selectedMonth'].'-01')->translatedFormat('F Y'),
            'tahunan' => (string) $period['selectedYear'],
            'range' => $period['dari']->translatedFormat('d M Y').' – '.$period['sampai']->translatedFormat('d M Y'),
            default => now()->translatedFormat('F Y'),
        };

        $exportQuery = array_filter([
            'mode' => $period['mode'],
            'tanggal' => $period['mode'] === 'harian' ? $period['selectedDate'] : null,
            'bulan' => $period['mode'] === 'bulanan' ? $period['selectedMonth'] : null,
            'tahun' => $period['mode'] === 'tahunan' ? $period['selectedYear'] : null,
            'dari' => $period['mode'] === 'range' ? $period['selectedDari'] : null,
            'sampai' => $period['mode'] === 'range' ? $period['selectedSampai'] : null,
            'status' => $filters['status'] ?? null,
            'tanggal_setor' => $filters['tanggal_setor'] ?? null,
            'pengaju' => $filters['pengaju'] ?? null,
            'rekening' => $filters['rekening'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        return [
            'filters' => $filters,
            'setoranStats' => $setoranStats,
            'listQuery' => $q,
            'periodeLabel' => $periodeLabel,
            'exportQuery' => $exportQuery,
            'mode' => $period['mode'],
            'selectedDate' => $period['selectedDate'],
            'selectedMonth' => $period['selectedMonth'],
            'selectedYear' => $period['selectedYear'],
            'selectedDari' => $period['selectedDari'],
            'selectedSampai' => $period['selectedSampai'],
            'dari' => $period['dari'],
            'sampai' => $period['sampai'],
        ];
    }

    /**
     * @return array{dari: \Carbon\Carbon, sampai: \Carbon\Carbon, mode: string, selectedDate: string, selectedMonth: string, selectedYear: int, selectedDari: string, selectedSampai: string}
     */
    private function resolveSetoranPeriod(Request $request): array
    {
        $mode = in_array($request->input('mode'), ['harian', 'bulanan', 'tahunan', 'range'], true)
            ? $request->input('mode')
            : 'bulanan';

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
                $dari = Carbon::parse($selectedDari)->startOfDay();
                $sampai = Carbon::parse($selectedSampai)->endOfDay();
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
                $dari = Carbon::parse($selectedDate)->startOfDay();
                $sampai = Carbon::parse($selectedDate)->endOfDay();
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
                $monthBase = Carbon::parse($selectedMonth.'-01');
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
}
