<?php

namespace App\Http\Controllers\Speedshop;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\Speedshop;
use App\Models\SpeedshopKeuanganRekening;
use App\Models\SpeedshopOwnerPengambilanDividen;
use App\Models\User;
use App\Support\KeuanganScope;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class OwnerPengambilanDividenController extends Controller
{
    private function ensureOwner(): void
    {
        if (! auth()->user()?->isOwner()) {
            abort(403, 'Hanya akun Owner yang dapat mengakses pengambilan dividen.');
        }
    }

    public function index(Request $request): View
    {
        $this->ensureOwner();
        $ownerId = (int) auth()->id();
        $ownerScope = KeuanganScope::forOwner($ownerId);

        $ownerRekenings = $ownerScope->applyToRekening(SpeedshopKeuanganRekening::query())
            ->orderBy('nama')
            ->orderBy('id')
            ->get();

        $speedshops = Speedshop::query()->orderBy('nama')->get(['id', 'nama']);

        $pengajuans = SpeedshopOwnerPengambilanDividen::query()
            ->with(['speedshop', 'speedshopRekening', 'ownerRekening', 'requester', 'processor'])
            ->where('owner_user_id', $ownerId)
            ->when($request->filled('speedshop_id'), fn ($q) => $q->where('speedshop_id', (int) $request->input('speedshop_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', (string) $request->input('status')))
            ->when($request->filled('dari'), fn ($q) => $q->whereDate('tanggal', '>=', $request->input('dari')))
            ->when($request->filled('sampai'), fn ($q) => $q->whereDate('tanggal', '<=', $request->input('sampai')))
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('speedshop.keuangan.pengambilan-dividen.index', [
            'ownerRekenings' => $ownerRekenings,
            'speedshops' => $speedshops,
            'pengajuans' => $pengajuans,
            'asOfYmd' => now()->toDateString(),
            'openModal' => $request->query('buka_modal') === '1' || $request->old('open_modal') === '1',
        ]);
    }

    public function rekeningsForSpeedshop(Request $request, Speedshop $speedshop): JsonResponse
    {
        $this->ensureOwner();

        $scope = KeuanganScope::forSpeedshop((int) $speedshop->id);
        $rekenings = $scope->applyToRekening(SpeedshopKeuanganRekening::query())
            ->orderBy('jenis')
            ->orderBy('nama')
            ->get(['id', 'nama', 'nama_bank', 'nomor_rekening', 'jenis']);

        return response()->json([
            'speedshop' => ['id' => $speedshop->id, 'nama' => $speedshop->nama],
            'rekenings' => $rekenings->map(fn (SpeedshopKeuanganRekening $r) => [
                'id' => $r->id,
                'label' => $r->nama.' — '.SpeedshopKeuanganRekening::jenisLabel($r->jenis)
                    .($r->nama_bank ? ' ('.$r->nama_bank.')' : ''),
                'jenis' => $r->jenis,
            ])->values(),
        ]);
    }

    public function saldosPreview(Request $request, \App\Services\SpeedshopFinanceService $finance): JsonResponse
    {
        $this->ensureOwner();

        $validated = $request->validate([
            'tanggal' => ['nullable', 'date', 'before_or_equal:today'],
            'speedshop_id' => ['nullable', 'integer', 'exists:speedshops,id'],
            'speedshop_rekening_id' => ['nullable', 'integer'],
            'owner_rekening_id' => ['nullable', 'integer'],
        ]);

        $asOfYmd = $this->resolveAsOfYmd($validated['tanggal'] ?? now()->toDateString());
        $ownerId = (int) auth()->id();
        $out = ['as_of' => $asOfYmd, 'speedshop' => null, 'owner' => null];

        if (! empty($validated['speedshop_rekening_id']) && ! empty($validated['speedshop_id'])) {
            $scope = KeuanganScope::forSpeedshop((int) $validated['speedshop_id']);
            $rek = $scope->applyToRekening(SpeedshopKeuanganRekening::query())
                ->where('id', (int) $validated['speedshop_rekening_id'])
                ->first();
            if ($rek) {
                $pocket = $finance->pocketForRekeningJenis($rek->jenis ?? null);
                $out['speedshop'] = $finance->balanceForBankPocketAsOfScope($scope, $pocket, (int) $rek->id, $asOfYmd);
            }
        }

        if (! empty($validated['owner_rekening_id'])) {
            $scope = KeuanganScope::forOwner($ownerId);
            $rek = $scope->applyToRekening(SpeedshopKeuanganRekening::query())
                ->where('id', (int) $validated['owner_rekening_id'])
                ->first();
            if ($rek) {
                $pocket = $finance->pocketForRekeningJenis($rek->jenis ?? null);
                $out['owner'] = $finance->balanceForBankPocketAsOfScope($scope, $pocket, (int) $rek->id, $asOfYmd);
            }
        }

        return response()->json($out);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureOwner();

        if (! Schema::hasTable('speedshop_owner_pengambilan_dividens')) {
            return back()->with('error', 'Migrasi pengambilan dividen belum dijalankan.')->withInput();
        }

        $validated = $request->validate([
            'speedshop_id' => ['required', 'integer', 'exists:speedshops,id'],
            'speedshop_rekening_id' => ['required', 'integer', 'exists:speedshop_keuangan_rekenings,id'],
            'owner_rekening_id' => ['required', 'integer', 'exists:speedshop_keuangan_rekenings,id'],
            'tanggal' => ['required', 'date', 'before_or_equal:today'],
            'nominal' => ['required', 'integer', 'min:1', 'max:999999999999'],
            'catatan' => ['nullable', 'string', 'max:2000'],
            'bukti_pengajuan' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $ownerId = (int) auth()->id();
        $speedshopId = (int) $validated['speedshop_id'];

        $speedshopRekening = KeuanganScope::forSpeedshop($speedshopId)
            ->applyToRekening(SpeedshopKeuanganRekening::query())
            ->where('id', (int) $validated['speedshop_rekening_id'])
            ->first();
        if (! $speedshopRekening) {
            return back()->withErrors(['speedshop_rekening_id' => 'Rekening speedshop tidak valid.'])->withInput();
        }

        $ownerRekening = KeuanganScope::forOwner($ownerId)
            ->applyToRekening(SpeedshopKeuanganRekening::query())
            ->where('id', (int) $validated['owner_rekening_id'])
            ->first();
        if (! $ownerRekening) {
            return back()->withErrors(['owner_rekening_id' => 'Rekening owner tidak valid.'])->withInput();
        }

        $buktiPath = null;
        try {
            $buktiPath = $request->file('bukti_pengajuan')->store('owner-pengambilan-dividen/pengajuan', 'public');
        } catch (\Throwable) {
            return back()->with('error', 'Gagal mengunggah bukti pengajuan.')->withInput();
        }

        $dividen = SpeedshopOwnerPengambilanDividen::query()->create([
            'owner_user_id' => $ownerId,
            'speedshop_id' => $speedshopId,
            'speedshop_rekening_id' => (int) $speedshopRekening->id,
            'owner_rekening_id' => (int) $ownerRekening->id,
            'tanggal' => Carbon::parse($validated['tanggal'])->toDateString(),
            'nominal' => (int) $validated['nominal'],
            'catatan' => filled($validated['catatan'] ?? null) ? trim((string) $validated['catatan']) : null,
            'bukti_pengajuan_path' => $buktiPath,
            'status' => SpeedshopOwnerPengambilanDividen::STATUS_PENDING,
            'requested_by' => $ownerId,
        ]);

        $this->notifyPending($dividen);

        return redirect()
            ->route('speedshop.keuangan.pengambilan-dividen.index')
            ->with('success', 'Pengajuan pengambilan dividen terkirim. Menunggu verifikasi Accounting speedshop.');
    }

    private function notifyPending(SpeedshopOwnerPengambilanDividen $dividen): void
    {
        $dividen->loadMissing(['speedshop', 'requester']);
        $speedshopId = (int) $dividen->speedshop_id;
        $speedshopNama = $dividen->speedshop?->nama ?? 'Speedshop';
        $nominalFormatted = number_format((int) $dividen->nominal, 0, ',', '.');
        $tanggalLabel = Carbon::parse($dividen->tanggal)->format('d/m/Y');
        $pemohon = $dividen->requester?->name ?? 'Owner';
        $link = route('speedshop.verifikasi.pengambilan-dividen.show', $dividen);

        $notifPayload = [
            'title' => 'Pengambilan dividen menunggu verifikasi',
            'message' => "{$pemohon} mengajukan pengambilan dividen Rp {$nominalFormatted} dari {$speedshopNama} (tanggal {$tanggalLabel}).",
            'type' => 'speedshop_owner_pengambilan_dividen_pending',
            'data' => [
                'speedshop_owner_pengambilan_dividen_id' => $dividen->id,
                'speedshop_id' => $speedshopId,
            ],
            'link' => $link,
        ];

        $recipientIds = [];

        foreach (User::accountingUsersForSpeedshop($speedshopId) as $user) {
            $recipientIds[$user->id] = $user;
        }
        foreach (User::speedshopManagersForOutlet($speedshopId) as $user) {
            $recipientIds[$user->id] = $user;
        }

        foreach ($recipientIds as $user) {
            AppNotification::create(array_merge($notifPayload, ['user_id' => $user->id]));
        }
    }

    private function resolveAsOfYmd(mixed $tanggal): string
    {
        try {
            return Carbon::parse($tanggal)->toDateString();
        } catch (\Throwable) {
            return now()->toDateString();
        }
    }
}
