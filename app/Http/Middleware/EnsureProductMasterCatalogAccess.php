<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Head Warehouse / Manager Speedshop mengelola master produk penuh.
 * Head/Staf Marketing boleh melihat katalog MO, unggah gambar, dan export.
 */
class EnsureProductMasterCatalogAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(403);
        }

        $canUploadMoImage = $user->canAccessMoProductImageCatalogRoute(
            $request->route()?->getName()
        );

        if (! $user->canManageProductMasterCatalog() && ! $canUploadMoImage) {
            abort(403, 'Anda tidak memiliki akses untuk mengelola master produk.');
        }

        return $next($request);
    }
}
