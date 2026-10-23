<?php

namespace App\Http\Controllers\Speedshop;

use App\Http\Controllers\Controller;
use App\Models\Speedshop;
use App\Models\SpeedshopKeuanganRekening;
use App\Models\SpeedshopOwnerModalSetoran;
use App\Services\SpeedshopFinanceService;
use App\Support\KeuanganScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class OwnerModalSetoranController extends Controller
{
    private function ensureOwner(): void
    {
        if (! auth()->user()?->isOwner()) {
            abort(403, 'Hanya akun Owner yang dapat mengakses setoran modal.');
        }
    }

    public function index(Request $request, SpeedshopFinanceService $finance): View
    {
        $this->ensureOwner();
        $ownerId = (int) auth()->id();
        $ownerScope = KeuanganScope::forOwner($ownerId);
        $asOfYmd = now()->toDateString();

        $ownerRekenings = $ownerScope->applyToRekening(SpeedshopKeuanganRekening::query())
            ->orderBy('nama')
            ->orderBy('id')
            ->get();

        $saldoPerOwnerRekening = [];
        foreach ($ownerRekenings as $rek) {
            $pocket = $finance->pocketForRekeningJenis($rek->jenis ?? null);
            $saldoPerOwnerRekening[$rek->id] = $finance->balanceForBankPocketAsOfScope(
                $ownerScope,
                $pocket,
                (int) $rek->id,
                $asOfYmd,
            );
        }

        $speedshops = Speedshop::query()->orderBy('nama')->get(['id', 'nama']);

        $setorans = SpeedshopOwnerModalSetoran::query()
            ->with(['speedshop', 'sumberOwnerRekening', 'tujuanSpeedshopRekening', 'user'])
            ->where('owner_user_id', $ownerId)
            ->when($request->filled('speedshop_id'), fn ($q) => $q->where('speedshop_id', (int) $request->input('speedshop_id')))
            ->when($request->filled('dari'), fn ($q) => $q->whereDate('tanggal', '>=', $request->input('dari')))
            ->when($request->filled('sampai'), fn ($q) => $q->whereDate('tanggal', '<=', $request->input('sampai')))
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('speedshop.keuangan.setoran-modal.index', [
            'ownerRekenings' => $ownerRekenings,
            'saldoPerOwnerRekening' => $saldoPerOwnerRekening,
            'speedshops' => $speedshops,
            'setorans' => $setorans,
            'asOfYmd' => $asOfYmd,
            'openModal' => $request->query('buka_modal') === '1' || $request->old('open_modal') === '1',
        ]);
    }

    public function rekeningsForSpeedshop(Request $request, Speedshop $speedshop, SpeedshopFinanceService $finance): JsonResponse
    {
        $this->ensureOwner();

        $asOfYmd = $this->resolveAsOfYmd($request->input('tanggal'));
        $scope = KeuanganScope::forSpeedshop((int) $speedshop->id);

        $rekenings = $scope->applyToRekening(SpeedshopKeuanganRekening::query())
            ->orderBy('jenis')
            ->orderBy('nama')
            ->get(['id', 'nama', 'nama_bank', 'nomor_rekening', 'jenis']);

        return response()->json([
            'speedshop' => ['id' => $speedshop->id, 'nama' => $speedshop->nama],
            'as_of' => $asOfYmd,
            'rekenings' => $rekenings->map(fn (SpeedshopKeuanganRekening $r) => [
                'id' => $r->id,
                'label' => $r->nama.' — '.SpeedshopKeuanganRekening::jenisLabel($r->jenis)
                    .($r->nama_bank ? ' ('.$r->nama_bank.')' : ''),
                'jenis' => $r->jenis,
                'saldo' => $this->saldoRekeningAsOf($finance, $scope, $r, $asOfYmd),
            ])->values(),
        ]);
    }

    public function saldosPreview(Request $request, SpeedshopFinanceService $finance): JsonResponse
    {
        $this->ensureOwner();

        $validated = $request->validate([
            'tanggal' => ['nullable', 'date', 'before_or_equal:today'],
            'sumber_owner_rekening_id' => ['nullable', 'integer'],
            'tujuan_speedshop_rekening_id' => ['nullable', 'integer'],
            'speedshop_id' => ['nullable', 'integer', 'exists:speedshops,id'],
        ]);

        $asOfYmd = $this->resolveAsOfYmd($validated['tanggal'] ?? now()->toDateString());
        $ownerId = (int) auth()->id();
        $out = ['as_of' => $asOfYmd, 'sumber' => null, 'tujuan' => null];

        if (! empty($validated['sumber_owner_rekening_id'])) {
            $sumber = KeuanganScope::forOwner($ownerId)
                ->applyToRekening(SpeedshopKeuanganRekening::query())
                ->where('id', (int) $validated['sumber_owner_rekening_id'])
                ->first();
            if ($sumber) {
                $out['sumber'] = $this->saldoRekeningAsOf($finance, KeuanganScope::forOwner($ownerId), $sumber, $asOfYmd);
            }
        }

        if (! empty($validated['tujuan_speedshop_rekening_id']) && ! empty($validated['speedshop_id'])) {
            $scope = KeuanganScope::forSpeedshop((int) $validated['speedshop_id']);
            $tujuan = $scope->applyToRekening(SpeedshopKeuanganRekening::query())
                ->where('id', (int) $validated['tujuan_speedshop_rekening_id'])
                ->first();
            if ($tujuan) {
                $out['tujuan'] = $this->saldoRekeningAsOf($finance, $scope, $tujuan, $asOfYmd);
            }
        }

        return response()->json($out);
    }

    private function resolveAsOfYmd(mixed $tanggal): string
    {
        try {
            return \Carbon\Carbon::parse($tanggal)->toDateString();
        } catch (\Throwable) {
            return now()->toDateString();
        }
    }

    private function saldoRekeningAsOf(
        SpeedshopFinanceService $finance,
        KeuanganScope $scope,
        SpeedshopKeuanganRekening $rekening,
        string $asOfYmd,
    ): int {
        $pocket = $finance->pocketForRekeningJenis($rekening->jenis ?? null);

        return $finance->balanceForBankPocketAsOfScope($scope, $pocket, (int) $rekening->id, $asOfYmd);
    }

    public function store(Request $request, SpeedshopFinanceService $finance): RedirectResponse
    {
        $this->ensureOwner();

        if (! Schema::hasTable('speedshop_owner_modal_setorans')) {
            return back()->with('error', 'Migrasi setoran modal belum dijalankan.')->withInput();
        }

        $validated = $request->validate([
            'speedshop_id' => ['required', 'integer', 'exists:speedshops,id'],
            'sumber_owner_rekening_id' => ['required', 'integer', 'exists:speedshop_keuangan_rekenings,id'],
            'tujuan_speedshop_rekening_id' => ['required', 'integer', 'exists:speedshop_keuangan_rekenings,id'],
            'tanggal' => ['required', 'date', 'before_or_equal:today'],
            'nominal' => ['required', 'integer', 'min:1', 'max:999999999999'],
            'catatan' => ['nullable', 'string', 'max:2000'],
            'bukti' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $ownerId = (int) auth()->id();
        $ownerScope = KeuanganScope::forOwner($ownerId);

        $sumber = $ownerScope->applyToRekening(SpeedshopKeuanganRekening::query())
            ->where('id', (int) $validated['sumber_owner_rekening_id'])
            ->first();
        if (! $sumber) {
            return back()->withErrors(['sumber_owner_rekening_id' => 'Rekening sumber owner tidak valid.'])->withInput();
        }

        $speedshopId = (int) $validated['speedshop_id'];
        $tujuan = KeuanganScope::forSpeedshop($speedshopId)
            ->applyToRekening(SpeedshopKeuanganRekening::query())
            ->where('id', (int) $validated['tujuan_speedshop_rekening_id'])
            ->first();
        if (! $tujuan) {
            return back()->withErrors(['tujuan_speedshop_rekening_id' => 'Rekening tujuan tidak valid untuk speedshop ini.'])->withInput();
        }

        $buktiPath = null;
        try {
            $buktiPath = $request->file('bukti')->store('owner-modal-setoran/bukti', 'public');
        } catch (\Throwable) {
            return back()->with('error', 'Gagal mengunggah bukti transfer.')->withInput();
        }

        try {
            $tanggalYmd = \Carbon\Carbon::parse($validated['tanggal'])->toDateString();
            $finance->recordOwnerModalSetoran(
                $ownerId,
                $sumber,
                $tujuan,
                $speedshopId,
                $tanggalYmd,
                (int) $validated['nominal'],
                $ownerId,
                filled($validated['catatan'] ?? null) ? trim((string) $validated['catatan']) : null,
                $buktiPath,
            );
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()
            ->route('speedshop.keuangan.setoran-modal.index')
            ->with('success', 'Setoran modal berhasil. Saldo rekening owner berkurang dan rekening speedshop bertambah.');
    }
}
