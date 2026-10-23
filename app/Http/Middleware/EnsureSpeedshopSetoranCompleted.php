<?php

namespace App\Http\Middleware;

use App\Services\SpeedshopFinanceService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class EnsureSpeedshopSetoranCompleted
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Schema::hasTable('speedshop_finance_setorans')) {
            return $next($request);
        }

        if ($request->user()?->isAccounting()) {
            return $next($request);
        }

        $ssid = $request->user()?->activeSpeedshopId();
        if (! $ssid) {
            return $next($request);
        }

        $service = app(SpeedshopFinanceService::class);
        if ($service->canTransactToday((int) $ssid)) {
            return $next($request);
        }

        $pending = $service->pendingSetoranDatesBeforeToday((int) $ssid);
        $first = $pending[0] ?? null;

        return redirect()
            ->route('speedshop.keuangan.setoran.create', array_filter([
                'setoran_tanggal' => $first,
            ]))
            ->with('error', 'Ada hari kerja yang belum diselesaikan setorannya ke Rekening Utama. Selesaikan setoran harian di menu Keuangan > Setoran terlebih dahulu.');
    }
}
