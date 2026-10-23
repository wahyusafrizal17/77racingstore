<?php

namespace App\Http\Controllers\Speedshop;

use App\Http\Controllers\Concerns\ResolvesKeuanganContext;
use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\SpeedshopOwnerPengambilanDividen;
use App\Services\SpeedshopFinanceService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class VerifikasiPengambilanDividenController extends Controller
{
    use ResolvesKeuanganContext;

    private function ensureAccounting(): void
    {
        if (! auth()->user()?->isAccounting()) {
            abort(403);
        }
    }

    public function index(Request $request): View|RedirectResponse
    {
        $this->ensureAccounting();

        if (! Schema::hasTable('speedshop_owner_pengambilan_dividens')) {
            abort(503, 'Migrasi pengambilan dividen belum dijalankan.');
        }

        $scope = $this->resolveKeuanganScope();
        if (! $scope || $scope->speedshopId === null) {
            return redirect()->route('speedshop.dashboard')
                ->with('error', 'Pilih cabang speedshop terlebih dahulu.');
        }

        $speedshopId = (int) $scope->speedshopId;
        $period = $this->resolveVerifikasiPeriod($request);
        $periodStart = $period['dari']->toDateString();
        $periodEnd = $period['sampai']->toDateString();

        $baseQuery = SpeedshopOwnerPengambilanDividen::query()
            ->with(['ownerUser', 'speedshopRekening', 'ownerRekening', 'requester', 'processor'])
            ->where('speedshop_id', $speedshopId)
            ->whereDate('tanggal', '>=', $periodStart)
            ->whereDate('tanggal', '<=', $periodEnd);

        $statsBase = clone $baseQuery;
        $dividenStats = [
            'total' => (clone $statsBase)->count(),
            'pending' => (clone $statsBase)->where('status', SpeedshopOwnerPengambilanDividen::STATUS_PENDING)->count(),
            'completed' => (clone $statsBase)->where('status', SpeedshopOwnerPengambilanDividen::STATUS_COMPLETED)->count(),
            'rejected' => (clone $statsBase)->where('status', SpeedshopOwnerPengambilanDividen::STATUS_REJECTED)->count(),
        ];

        $query = clone $baseQuery;

        if ($request->filled('status')) {
            $status = (string) $request->input('status');
            if (in_array($status, [
                SpeedshopOwnerPengambilanDividen::STATUS_PENDING,
                SpeedshopOwnerPengambilanDividen::STATUS_COMPLETED,
                SpeedshopOwnerPengambilanDividen::STATUS_REJECTED,
            ], true)) {
                $query->where('status', $status);
            }
        }

        if ($request->filled('tanggal_pengajuan')) {
            try {
                $query->whereDate('tanggal', Carbon::parse((string) $request->input('tanggal_pengajuan'))->toDateString());
            } catch (\Throwable) {
                // Abaikan filter tanggal tidak valid.
            }
        }

        if ($request->filled('owner')) {
            $term = '%'.addcslashes((string) $request->input('owner'), '%_\\').'%';
            $query->whereHas('ownerUser', fn ($q) => $q->where('name', 'like', $term));
        }

        if ($request->filled('nominal')) {
            $nominalFilter = (int) $request->input('nominal');
            if ($nominalFilter >= 0) {
                $query->where('nominal', $nominalFilter);
            }
        }

        $items = $query
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 WHEN status = 'completed' THEN 1 ELSE 2 END")
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('speedshop.verifikasi.pengambilan-dividen.index', [
            'items' => $items,
            'dividenStats' => $dividenStats,
            'filters' => $request->only(['status', 'tanggal_pengajuan', 'owner', 'nominal']),
            'mode' => $period['mode'],
            'selectedDate' => $period['selectedDate'],
            'selectedMonth' => $period['selectedMonth'],
            'selectedYear' => $period['selectedYear'],
            'selectedDari' => $period['selectedDari'],
            'selectedSampai' => $period['selectedSampai'],
            'dari' => $period['dari'],
            'sampai' => $period['sampai'],
        ]);
    }

    public function show(SpeedshopOwnerPengambilanDividen $pengambilan): View|RedirectResponse
    {
        $this->ensureAccounting();

        $scope = $this->resolveKeuanganScope();
        if (! $scope || (int) $pengambilan->speedshop_id !== (int) $scope->speedshopId) {
            abort(403);
        }

        $pengambilan->load(['ownerUser', 'speedshop', 'speedshopRekening', 'ownerRekening', 'requester', 'processor']);

        return view('speedshop.verifikasi.pengambilan-dividen.show', [
            'pengambilan' => $pengambilan,
        ]);
    }

    public function approve(Request $request, SpeedshopOwnerPengambilanDividen $pengambilan, SpeedshopFinanceService $finance): RedirectResponse
    {
        $this->ensureAccounting();

        $scope = $this->resolveKeuanganScope();
        if (! $scope || (int) $pengambilan->speedshop_id !== (int) $scope->speedshopId) {
            abort(403);
        }

        if ($pengambilan->status !== SpeedshopOwnerPengambilanDividen::STATUS_PENDING) {
            return back()->with('error', 'Pengajuan ini sudah diproses.');
        }

        $validated = $request->validate([
            'bukti_transfer' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        try {
            $buktiPath = $request->file('bukti_transfer')->store('owner-pengambilan-dividen/transfer', 'public');
        } catch (\Throwable) {
            return back()->with('error', 'Gagal mengunggah bukti transfer.')->withInput();
        }

        try {
            DB::transaction(function () use ($pengambilan, $finance, $buktiPath): void {
                $finance->completeOwnerPengambilanDividen(
                    $pengambilan,
                    (int) auth()->id(),
                    $buktiPath,
                );
            });
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        $pengambilan->refresh();
        $this->notifyOwnerCompleted($pengambilan);

        return redirect()
            ->route('speedshop.verifikasi.pengambilan-dividen.index')
            ->with('success', 'Pengambilan dividen disetujui. Saldo rekening speedshop berkurang dan rekening owner bertambah.');
    }

    public function reject(Request $request, SpeedshopOwnerPengambilanDividen $pengambilan): RedirectResponse
    {
        $this->ensureAccounting();

        $scope = $this->resolveKeuanganScope();
        if (! $scope || (int) $pengambilan->speedshop_id !== (int) $scope->speedshopId) {
            abort(403);
        }

        if ($pengambilan->status !== SpeedshopOwnerPengambilanDividen::STATUS_PENDING) {
            return back()->with('error', 'Pengajuan ini sudah diproses.');
        }

        $validated = $request->validate([
            'rejected_reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $pengambilan->update([
            'status' => SpeedshopOwnerPengambilanDividen::STATUS_REJECTED,
            'processed_by' => auth()->id(),
            'processed_at' => now(),
            'rejected_reason' => $validated['rejected_reason'],
        ]);

        AppNotification::create([
            'user_id' => $pengambilan->owner_user_id,
            'type' => 'speedshop_owner_pengambilan_dividen_rejected',
            'title' => 'Pengambilan dividen ditolak',
            'message' => 'Pengajuan dividen Anda ditolak: '.$validated['rejected_reason'],
            'data' => [
                'speedshop_owner_pengambilan_dividen_id' => $pengambilan->id,
            ],
            'link' => route('speedshop.keuangan.pengambilan-dividen.index'),
        ]);

        return redirect()
            ->route('speedshop.verifikasi.pengambilan-dividen.index')
            ->with('success', 'Pengajuan pengambilan dividen ditolak.');
    }

    private function notifyOwnerCompleted(SpeedshopOwnerPengambilanDividen $dividen): void
    {
        $nominalFormatted = number_format((int) $dividen->nominal, 0, ',', '.');

        AppNotification::create([
            'user_id' => $dividen->owner_user_id,
            'type' => 'speedshop_owner_pengambilan_dividen_completed',
            'title' => 'Pengambilan dividen disetujui',
            'message' => "Pengambilan dividen Rp {$nominalFormatted} telah diproses dan ditransfer ke rekening owner Anda.",
            'data' => [
                'speedshop_owner_pengambilan_dividen_id' => $dividen->id,
            ],
            'link' => route('speedshop.keuangan.pengambilan-dividen.index'),
        ]);
    }

    /**
     * @return array{dari: Carbon, sampai: Carbon, mode: string, selectedDate: string, selectedMonth: string, selectedYear: int, selectedDari: string, selectedSampai: string}
     */
    private function resolveVerifikasiPeriod(Request $request): array
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
            } catch (\Throwable) {
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
            } catch (\Throwable) {
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
            } catch (\Throwable) {
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
