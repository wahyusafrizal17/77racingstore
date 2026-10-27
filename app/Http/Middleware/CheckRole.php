<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (
            $user->canHandleExternalCustomerChats()
            && $request->routeIs(
                'warehouse.chat-reseller.*',
                'warehouse.chat-marketplace.*',
            )
        ) {
            return $next($request);
        }

        if ($user->canAccessMoProductImageCatalogRoute($request->route()?->getName())) {
            return $next($request);
        }

        if (count(array_intersect($user->routeRoleAliases(), $roles)) > 0) {
            return $next($request);
        }

        if ($user->isOwner()) {
            $routeName = $request->route()?->getName();
            if ($routeName !== null && (
                str_starts_with($routeName, 'owner.')
                || str_starts_with($routeName, 'speedshop.')
                || str_starts_with($routeName, 'warehouse.')
                || str_starts_with($routeName, 'produksi.')
                || str_starts_with($routeName, 'modifikasi-ori.')
            )) {
                return $next($request);
            }
        }

        abort(403, 'Anda tidak memiliki akses ke halaman ini.');
    }
}
