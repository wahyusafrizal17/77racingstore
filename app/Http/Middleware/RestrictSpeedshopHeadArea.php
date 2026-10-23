<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RestrictSpeedshopHeadArea
{
    /** @var list<string> */
    private const ALLOWED_ROUTE_PATTERNS = [
        'speedshop.dashboard*',
        'speedshop.scope.update',
        'speedshop.history',
        'speedshop.service-record*',
        'speedshop.mekanik-record',
        'speedshop.wip.show',
        'speedshop.verifikasi.diskon-work-order*',
        'speedshop.verifikasi.pembelian-luar*',
        'speedshop.stock-in.pembelian-luar.approve',
        'speedshop.stock-in.pembelian-luar.reject',
        'speedshop.stock-in.pembelian-luar.pdf',
        'speedshop.selesaikan-transaksi.approve',
        'speedshop.selesaikan-transaksi.reject',
        'speedshop.work-orders.*',
        'speedshop.pelanggans*',
        'speedshop.kendaraans.quick-add',
        'speedshop.suppliers*',
        'speedshop.categories*',
        'speedshop.brands*',
        'speedshop.jasa-servis*',
        'speedshop.mekaniks*',
        'speedshop.mekanik-levels*',
        'speedshop.stok-produk*',
        'speedshop.stok-part-luar*',
        'speedshop.products.stock-history',
        'speedshop.laporan.laba-rugi*',
        'speedshop.laporan.biaya*',
        'speedshop.laporan.summary*',
        'speedshop.laporan.rekap-pemisahan-dana*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->isSpeedshopHeadArea()) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();
        if ($routeName === null || ! $this->isAllowedRoute($routeName)) {
            abort(403, 'Akun Head Area tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }

    private function isAllowedRoute(string $routeName): bool
    {
        foreach (self::ALLOWED_ROUTE_PATTERNS as $pattern) {
            if (Str::is($pattern, $routeName)) {
                return true;
            }
        }

        return false;
    }
}
