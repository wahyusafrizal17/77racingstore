<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSection
{
    public function handle(Request $request, Closure $next, string ...$sections): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (
            in_array('warehouse', $sections, true)
            && $request->routeIs('warehouse.resellers.*', 'warehouse.mo-operators.*')
        ) {
            if ($user->canManageMarketingResellers()) {
                return $next($request);
            }

            abort(403, 'Halaman ini hanya untuk Head Marketing.');
        }

        if (in_array($user->section, $sections, true)) {
            return $next($request);
        }

        if (
            in_array('warehouse', $sections, true)
            && $user->canUploadMoProductImages()
            && $request->routeIs(
                'warehouse.master.produk',
                'warehouse.master.produk.brand',
                'warehouse.master.produk.unbranded',
                'warehouse.master.produk.brand.logo',
                'warehouse.master.produk.show',
                'warehouse.master.produk.gambar',
                'warehouse.master.produk.export',
                'warehouse.master.produk.pdf',
            )
        ) {
            return $next($request);
        }

        if (
            in_array('warehouse', $sections, true)
            && $user->canHandleExternalCustomerChats()
            && $request->routeIs(
                'warehouse.chat-reseller.*',
                'warehouse.chat-marketplace.*',
            )
        ) {
            return $next($request);
        }

        foreach ($sections as $section) {
            if ($user->canAccessOperationalSection($section)) {
                return $next($request);
            }
        }

        if ($user->isOwner() && count(array_intersect($sections, ['speedshop', 'warehouse', 'produksi', User::SECTION_MODIFIKASI_ORI])) > 0) {
            return $next($request);
        }

        abort(403, 'Anda tidak memiliki akses ke halaman ini.');
    }
}
