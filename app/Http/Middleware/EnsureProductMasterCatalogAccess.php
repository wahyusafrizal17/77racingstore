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

        $canUploadMoImage = $user->canUploadMoProductImages()
            && $request->routeIs(
                'warehouse.master.produk',
                'warehouse.master.produk.brand',
                'warehouse.master.produk.unbranded',
                'warehouse.master.produk.brand.logo',
                'warehouse.master.produk.show',
                'warehouse.master.produk.gambar',
                'warehouse.master.produk.export',
                'warehouse.master.produk.pdf',
            );

        if (! $user->canManageProductMasterCatalog() && ! $canUploadMoImage) {
            abort(403, 'Anda tidak memiliki akses untuk mengelola master produk.');
        }

        return $next($request);
    }
}
