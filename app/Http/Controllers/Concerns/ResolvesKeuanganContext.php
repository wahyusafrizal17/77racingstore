<?php

namespace App\Http\Controllers\Concerns;

use App\Models\SpeedshopFinancePermintaanKasKecil;
use App\Models\SpeedshopFinanceSetoran;
use App\Models\SpeedshopKeuanganRekening;
use App\Support\KeuanganScope;

trait ResolvesKeuanganContext
{
    protected function keuanganRoutePrefix(): string
    {
        return 'speedshop.keuangan';
    }

    protected function keuanganDashboardRoute(): string
    {
        if (auth()->user()?->isOwner()) {
            return 'owner.dashboard';
        }

        if ($this->isWarehouseKeuanganContext()) {
            return 'warehouse.dashboard';
        }

        return 'speedshop.dashboard';
    }

    protected function keuanganViewPrefix(): string
    {
        return 'speedshop.keuangan';
    }

    protected function keuanganRoute(string $name, mixed $parameters = [], bool $absolute = true): string
    {
        return route($this->keuanganRoutePrefix().'.'.$name, $parameters, $absolute);
    }

    protected function keuanganView(string $view, array $data = [])
    {
        return view($this->keuanganViewPrefix().'.'.$view, $data);
    }

    protected function isWarehouseKeuanganContext(): bool
    {
        return $this->keuanganRoutePrefix() === 'warehouse.keuangan';
    }

    protected function resolveKeuanganScope(): ?KeuanganScope
    {
        if ($this->isWarehouseKeuanganContext()) {
            $warehouseId = auth()->user()?->activeWarehouseId();

            return $warehouseId ? KeuanganScope::forWarehouse((int) $warehouseId) : null;
        }

        $user = auth()->user();
        if ($user?->isOwner()) {
            return KeuanganScope::forOwner((int) $user->id);
        }

        $speedshopId = $user?->activeSpeedshopId();

        return $speedshopId ? KeuanganScope::forSpeedshop((int) $speedshopId) : null;
    }

    protected function requireKeuanganScope(): KeuanganScope
    {
        $scope = $this->resolveKeuanganScope();
        if (! $scope) {
            abort(403, $this->keuanganScopeErrorMessage());
        }

        return $scope;
    }

    protected function resolveKeuanganSpeedshopId(): ?int
    {
        return $this->resolveKeuanganScope()?->speedshopId;
    }

    protected function resolveKeuanganWarehouseId(): ?int
    {
        return $this->resolveKeuanganScope()?->warehouseId;
    }

    protected function keuanganScopeErrorMessage(): string
    {
        if ($this->isWarehouseKeuanganContext()) {
            return 'Gudang aktif tidak terdeteksi. Hubungi admin jika akun warehouse belum terhubung ke gudang.';
        }

        return 'Pilih cabang speedshop terlebih dahulu.';
    }

    protected function authorizeKeuanganRekening(SpeedshopKeuanganRekening $rekening): void
    {
        $scope = $this->resolveKeuanganScope();
        if (! $scope || ! $scope->ownsRekening($rekening)) {
            abort(403);
        }
    }

    protected function authorizeKeuanganSetoran(SpeedshopFinanceSetoran $setoran): void
    {
        $scope = $this->resolveKeuanganScope();
        if (! $scope || ! $scope->ownsSetoran($setoran)) {
            abort(403);
        }
    }

    protected function authorizeKeuanganPermintaan(SpeedshopFinancePermintaanKasKecil $permintaan): void
    {
        $scope = $this->resolveKeuanganScope();
        if (! $scope || ! $scope->ownsPermintaan($permintaan)) {
            abort(403);
        }
    }
}
