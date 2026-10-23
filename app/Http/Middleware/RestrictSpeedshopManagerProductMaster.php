<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictSpeedshopManagerProductMaster
{
    /** @var list<string> */
    private const ACCOUNTING_BLOCKED_ROUTE_PREFIXES = [
        'speedshop.master.produk-mo',
        'speedshop.master.produk-speedshop',
        'speedshop.master.produk',
        'speedshop.master.part-luar',
        'speedshop.categories',
        'speedshop.brands',
        'speedshop.jasa-servis',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->isSpeedshop()) {
            return $next($request);
        }

        $name = $request->route()?->getName();
        if ($name === null) {
            return $next($request);
        }

        if (! $user->isAccounting()) {
            return $next($request);
        }

        foreach (self::ACCOUNTING_BLOCKED_ROUTE_PREFIXES as $prefix) {
            if ($name === $prefix || str_starts_with($name, $prefix.'.')) {
                abort(403, 'Master produk dikelola oleh Manager Speedshop atau Head Warehouse.');
            }
        }

        return $next($request);
    }
}
