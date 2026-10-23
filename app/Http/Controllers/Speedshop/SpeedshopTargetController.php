<?php

namespace App\Http\Controllers\Speedshop;

use App\Http\Controllers\Controller;
use App\Models\Mekanik;
use App\Models\Speedshop;
use App\Models\SpeedshopBranchTarget;
use App\Models\SpeedshopMekanikTarget;
use App\Models\User;
use App\Support\Speedshop\SpeedshopTargetMath;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SpeedshopTargetController extends Controller
{
    public function index(Request $request): View
    {
        $user = $this->managerOrAbort($request);
        $periode = $this->periodeBulan($request);
        $ids = $user->activeSpeedshopIds();

        $cabangTargets = SpeedshopBranchTarget::query()
            ->where('periode_bulan', $periode)
            ->whereIn('speedshop_id', $ids)
            ->pluck('target_bulanan', 'speedshop_id');

        $cabang = Speedshop::query()
            ->whereIn('id', $ids)
            ->orderBy('nama')
            ->get(['id', 'nama'])
            ->map(function (Speedshop $shop) use ($cabangTargets) {
                $target = SpeedshopTargetMath::cabangTarget(
                    isset($cabangTargets[$shop->id]) ? (int) $cabangTargets[$shop->id] : null
                );
                $split = SpeedshopTargetMath::split($target);

                return [
                    'id' => (int) $shop->id,
                    'nama' => (string) $shop->nama,
                    'target_bulanan' => $target,
                    'sparepart' => $split['sparepart'],
                    'jasa' => $split['jasa'],
                ];
            });

        $mekanikTargets = SpeedshopMekanikTarget::query()
            ->where('periode_bulan', $periode)
            ->whereIn('mekanik_id', Mekanik::query()->whereIn('speedshop_id', $ids)->select('id'))
            ->pluck('target_bulanan', 'mekanik_id');

        $mekaniks = Mekanik::query()
            ->with(['speedshop:id,nama', 'mekanikLevel'])
            ->aktif()
            ->whereIn('speedshop_id', $ids)
            ->orderBy('nama')
            ->get(['id', 'nama', 'speedshop_id', 'mekanik_level_id'])
            ->map(fn (Mekanik $mekanik) => [
                'id' => (int) $mekanik->id,
                'nama' => (string) $mekanik->nama,
                'cabang' => (string) ($mekanik->speedshop?->nama ?? '-'),
                'jabatan' => $mekanik->levelName() ?: 'Mekanik',
                'target_bulanan' => SpeedshopTargetMath::mekanikTarget(
                    isset($mekanikTargets[$mekanik->id]) ? (int) $mekanikTargets[$mekanik->id] : null
                ),
            ]);

        return view('speedshop.targets.index', [
            'periode' => $periode,
            'cabang' => $cabang,
            'mekaniks' => $mekaniks,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $this->managerOrAbort($request);
        $periode = $this->periodeBulan($request);
        $ids = $user->activeSpeedshopIds();

        $data = $request->validate([
            'cabang' => ['nullable', 'array'],
            'cabang.*' => ['nullable', 'integer', 'min:0'],
            'mekanik' => ['nullable', 'array'],
            'mekanik.*' => ['nullable', 'integer', 'min:0'],
        ]);

        foreach ($data['cabang'] ?? [] as $speedshopId => $amount) {
            $speedshopId = (int) $speedshopId;
            if (! in_array($speedshopId, $ids, true)) {
                continue;
            }

            SpeedshopBranchTarget::query()->updateOrCreate(
                ['speedshop_id' => $speedshopId, 'periode_bulan' => $periode],
                ['target_bulanan' => (int) $amount, 'updated_by' => $user->id],
            );
        }

        $allowedMekanikIds = Mekanik::query()
            ->whereIn('speedshop_id', $ids)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        foreach ($data['mekanik'] ?? [] as $mekanikId => $amount) {
            $mekanikId = (int) $mekanikId;
            if (! in_array($mekanikId, $allowedMekanikIds, true)) {
                continue;
            }

            SpeedshopMekanikTarget::query()->updateOrCreate(
                ['mekanik_id' => $mekanikId, 'periode_bulan' => $periode],
                ['target_bulanan' => (int) $amount, 'updated_by' => $user->id],
            );
        }

        return redirect()
            ->route('speedshop.targets.index', ['bulan' => $periode])
            ->with('success', 'Target berhasil disimpan.');
    }

    private function managerOrAbort(Request $request): User
    {
        $user = $request->user();
        if (
            ! $user instanceof User
            || ! $user->isSpeedshop()
            || ! $user->isManager()
            || $user->isSpeedshopHeadArea()
        ) {
            abort(403, 'Hanya Manager Speedshop yang dapat mengatur target.');
        }

        return $user;
    }

    private function periodeBulan(Request $request): string
    {
        $raw = (string) $request->input('bulan', now()->format('Y-m'));

        try {
            return Carbon::createFromFormat('Y-m', $raw)->format('Y-m');
        } catch (\Throwable) {
            return now()->format('Y-m');
        }
    }
}
