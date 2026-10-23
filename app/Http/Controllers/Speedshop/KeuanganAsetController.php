<?php

namespace App\Http\Controllers\Speedshop;

use App\Http\Controllers\Concerns\ResolvesKeuanganContext;
use App\Http\Controllers\Controller;
use App\Models\Speedshop;
use App\Models\SpeedshopAsetTetap;
use App\Support\KeuanganScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KeuanganAsetController extends Controller
{
    use ResolvesKeuanganContext;

    private function ensureAllowed(): void
    {
        $user = auth()->user();
        abort_unless(
            $user && ($user->isManager() || $user->isAccounting()),
            403
        );
    }

    private function requireScope(): KeuanganScope
    {
        $this->ensureAllowed();

        $user = auth()->user();
        abort_if(
            $user?->isSpeedshopAllScopeSelected(),
            403,
            'Pilih satu cabang Speedshop di navbar sebelum mengelola aset.'
        );

        $scope = $this->requireKeuanganScope();
        abort_unless($scope->isSpeedshop() && $scope->speedshopId, 403, 'Pilih cabang Speedshop terlebih dahulu.');

        return $scope;
    }

    private function authorizeAset(SpeedshopAsetTetap $aset, KeuanganScope $scope): void
    {
        abort_unless((int) $aset->speedshop_id === (int) $scope->speedshopId, 404);
    }

    public function index(Request $request): View
    {
        $scope = $this->requireScope();
        $speedshopId = (int) $scope->speedshopId;

        $items = $this->filteredQuery($request, $speedshopId)
            ->orderBy('kategori')
            ->orderBy('nama')
            ->paginate(20)
            ->withQueryString();

        $totalsBase = SpeedshopAsetTetap::query()->where('speedshop_id', $speedshopId);
        $totals = [
            'rak' => (float) (clone $totalsBase)->where('kategori', SpeedshopAsetTetap::KATEGORI_RAK)->sum('nilai_buku'),
            'inventaris' => (float) (clone $totalsBase)->where('kategori', SpeedshopAsetTetap::KATEGORI_INVENTARIS)->sum('nilai_buku'),
        ];
        $totals['semua'] = $totals['rak'] + $totals['inventaris'];

        return $this->keuanganView('aset.index', [
            'items' => $items,
            'totals' => $totals,
            'kategoriOptions' => SpeedshopAsetTetap::kategoriOptions(),
            'speedshop' => Speedshop::query()->find($speedshopId),
        ]);
    }

    public function show(SpeedshopAsetTetap $aset): JsonResponse
    {
        $scope = $this->requireScope();
        $this->authorizeAset($aset, $scope);

        return response()->json([
            'id' => $aset->id,
            'kategori' => $aset->kategori,
            'kode' => $aset->kode,
            'nama' => $aset->nama,
            'tanggal_perolehan' => $aset->tanggal_perolehan?->format('Y-m-d'),
            'nilai_perolehan' => (float) $aset->nilai_perolehan,
            'nilai_buku' => (float) $aset->nilai_buku,
            'keterangan' => $aset->keterangan,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $scope = $this->requireScope();
        $speedshopId = (int) $scope->speedshopId;
        $validated = $this->validatePayload($request, $speedshopId);

        SpeedshopAsetTetap::query()->create($validated + ['speedshop_id' => $speedshopId]);

        return response()->json(['success' => true, 'message' => 'Aset berhasil ditambahkan.']);
    }

    public function update(Request $request, SpeedshopAsetTetap $aset): JsonResponse
    {
        $scope = $this->requireScope();
        $this->authorizeAset($aset, $scope);

        $validated = $this->validatePayload($request, (int) $aset->speedshop_id, $aset->id);
        $aset->update($validated);

        return response()->json(['success' => true, 'message' => 'Aset berhasil diperbarui.']);
    }

    public function destroy(SpeedshopAsetTetap $aset): JsonResponse
    {
        $scope = $this->requireScope();
        $this->authorizeAset($aset, $scope);

        $aset->delete();

        return response()->json(['success' => true, 'message' => 'Aset berhasil dihapus.']);
    }

    /** @return array<string, mixed> */
    private function validatePayload(Request $request, int $speedshopId, ?int $ignoreId = null): array
    {
        $validated = $request->validate([
            'kategori' => ['required', Rule::in(SpeedshopAsetTetap::kategoriOptions())],
            'kode' => [
                'required',
                'string',
                'max:50',
                Rule::unique('speedshop_aset_tetaps', 'kode')
                    ->where('speedshop_id', $speedshopId)
                    ->ignore($ignoreId),
            ],
            'nama' => ['required', 'string', 'max:255'],
            'tanggal_perolehan' => ['nullable', 'date'],
            'nilai_perolehan' => ['required', 'numeric', 'min:0'],
            'nilai_buku' => ['nullable', 'numeric', 'min:0'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ], [
            'kategori.required' => 'Kategori wajib dipilih.',
            'kode.required' => 'Kode wajib diisi.',
            'kode.unique' => 'Kode aset sudah digunakan di cabang ini.',
            'nama.required' => 'Nama aset wajib diisi.',
            'nilai_perolehan.required' => 'Nilai perolehan wajib diisi.',
        ]);

        $validated['kode'] = strtoupper(trim($validated['kode']));
        $validated['nama'] = trim($validated['nama']);
        $validated['nilai_perolehan'] = round((float) $validated['nilai_perolehan'], 2);
        $validated['nilai_buku'] = round(
            (float) ($validated['nilai_buku'] ?? $validated['nilai_perolehan']),
            2
        );
        $validated['keterangan'] = filled($validated['keterangan'] ?? null)
            ? trim((string) $validated['keterangan'])
            : null;

        return $validated;
    }

    /** @return Builder<SpeedshopAsetTetap> */
    private function filteredQuery(Request $request, int $speedshopId): Builder
    {
        $query = SpeedshopAsetTetap::query()->where('speedshop_id', $speedshopId);

        if ($request->filled('kategori')) {
            $kategori = $request->string('kategori')->toString();
            if (in_array($kategori, SpeedshopAsetTetap::kategoriOptions(), true)) {
                $query->where('kategori', $kategori);
            }
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('kode', 'like', '%'.$search.'%')
                    ->orWhere('nama', 'like', '%'.$search.'%');
            });
        }

        return $query;
    }
}
