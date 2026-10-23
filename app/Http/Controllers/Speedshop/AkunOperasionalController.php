<?php

namespace App\Http\Controllers\Speedshop;

use App\Http\Controllers\Concerns\ResolvesKeuanganContext;
use App\Http\Controllers\Controller;
use App\Models\AkunOperasional;
use App\Support\KeuanganScope;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AkunOperasionalController extends Controller
{
    use ResolvesKeuanganContext;

    protected function akunOperasionalView(array $data)
    {
        return view('speedshop.akun-operasional.index', $data);
    }

    private function ensureAllowed(): void
    {
        $u = auth()->user();
        if (! $u || (! $u->isManager() && ! $u->isAccounting())) {
            abort(403);
        }
    }

    protected function requireAkunOperasionalScope(): KeuanganScope
    {
        $this->ensureAllowed();

        return $this->requireKeuanganScope();
    }

    protected function authorizeAkunOperasional(AkunOperasional $akun): void
    {
        $scope = $this->resolveKeuanganScope();
        if (! $scope || ! $akun->belongsToKeuanganScope($scope)) {
            abort(403);
        }
    }

    private function uniqueNoAkunRule(KeuanganScope $scope, ?int $ignoreId = null): \Illuminate\Validation\Rules\Unique
    {
        $rule = Rule::unique('akun_operasionals', 'no_akun')
            ->where(function ($query) use ($scope) {
                if ($scope->isWarehouse()) {
                    $query->where('warehouse_id', $scope->warehouseId)->whereNull('speedshop_id');

                    return;
                }

                $query->whereNull('warehouse_id')
                    ->where(function ($q) use ($scope) {
                        $q->where('speedshop_id', $scope->speedshopId)
                            ->orWhereNull('speedshop_id');
                    });
            });

        if ($ignoreId) {
            $rule->ignore($ignoreId);
        }

        return $rule;
    }

    public function index()
    {
        $scope = $this->requireAkunOperasionalScope();

        $items = AkunOperasional::queryForKeuanganScope($scope)
            ->get()
            ->sort(fn ($a, $b) => \App\Support\SpeedshopLabaRugiFinancials::compareAccountNumbers(
                (string) $a->no_akun,
                (string) $b->no_akun,
            ))
            ->values();

        return $this->akunOperasionalView(compact('items'));
    }

    public function store(Request $request)
    {
        $scope = $this->requireAkunOperasionalScope();

        $validated = $request->validate([
            'no_akun' => ['required', 'string', 'max:50', $this->uniqueNoAkunRule($scope)],
            'nama_akun' => ['required', 'string', 'max:150'],
        ]);

        AkunOperasional::query()->create(array_merge(
            AkunOperasional::ownershipAttributesForScope($scope),
            [
                'no_akun' => trim($validated['no_akun']),
                'nama_akun' => trim($validated['nama_akun']),
            ],
        ));

        session()->flash('success', 'Akun operasional berhasil ditambahkan.');

        return response()->json(['success' => true]);
    }

    public function show(AkunOperasional $akun_operasional)
    {
        $this->ensureAllowed();
        $this->authorizeAkunOperasional($akun_operasional);

        return response()->json($akun_operasional);
    }

    public function update(Request $request, AkunOperasional $akun_operasional)
    {
        $scope = $this->requireAkunOperasionalScope();
        $this->authorizeAkunOperasional($akun_operasional);

        $validated = $request->validate([
            'no_akun' => ['required', 'string', 'max:50', $this->uniqueNoAkunRule($scope, $akun_operasional->id)],
            'nama_akun' => ['required', 'string', 'max:150'],
        ]);

        $akun_operasional->update([
            'no_akun' => trim($validated['no_akun']),
            'nama_akun' => trim($validated['nama_akun']),
        ]);

        session()->flash('success', 'Akun operasional berhasil diperbarui.');

        return response()->json(['success' => true]);
    }

    public function destroy(AkunOperasional $akun_operasional)
    {
        $this->ensureAllowed();
        $this->authorizeAkunOperasional($akun_operasional);

        $akun_operasional->delete();
        session()->flash('success', 'Akun operasional berhasil dihapus.');

        return response()->json(['success' => true]);
    }
}
