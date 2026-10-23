<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo('/management/login');

        $middleware->validateCsrfTokens(except: [
            'midtrans/notification',
        ]);

        $middleware->alias([
            'abilities' => \Laravel\Sanctum\Http\Middleware\CheckAbilities::class,
            'ability' => \Laravel\Sanctum\Http\Middleware\CheckForAnyAbility::class,
            'section' => \App\Http\Middleware\CheckSection::class,
            'role' => \App\Http\Middleware\CheckRole::class,
            'speedshop.setoran' => \App\Http\Middleware\EnsureSpeedshopSetoranCompleted::class,
            'warehouse.staf-inventori' => \App\Http\Middleware\RestrictWarehouseStafInventori::class,
            'warehouse.staf-packing' => \App\Http\Middleware\RestrictWarehouseStafPacking::class,
            'warehouse.accounting' => \App\Http\Middleware\RestrictWarehouseAccounting::class,
            'warehouse.staff-accounting-reseller' => \App\Http\Middleware\RestrictWarehouseStaffAccountingReseller::class,
            'warehouse.head-warehouse' => \App\Http\Middleware\RestrictHeadWarehouseProducts::class,
            'product-master.catalog' => \App\Http\Middleware\EnsureProductMasterCatalogAccess::class,
            'speedshop.no-product-master' => \App\Http\Middleware\RestrictSpeedshopManagerProductMaster::class,
            'speedshop.head-area' => \App\Http\Middleware\RestrictSpeedshopHeadArea::class,
            'prevent.browser.cache' => \App\Http\Middleware\PreventBrowserCache::class,
            'admin.panel' => \App\Http\Middleware\EnsureAdminPanelAccess::class,
            'modifikasi-ori.redirect-head-audit' => \App\Http\Middleware\RedirectModifikasiOriHeadAudit::class,
            'head-audit.finance-readonly' => \App\Http\Middleware\RestrictHeadAuditFinanceMutations::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\PreventBrowserCache::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->renderable(function (PostTooLargeException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Ukuran unggahan melebihi batas server (misalnya PHP post_max_size). Perkecil file atau minta admin menaikkan batas.',
                ], 413);
            }

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Ukuran unggahan melebihi batas server (biasanya PHP post_max_size atau Nginx client_max_body_size). Di aplikasi lampiran biaya operasional dibatasi maksimal 5 MB; naikkan batas di server agar unggahan sampai ke Laravel, lalu pastikan file tidak melebihi 5 MB.'
                );
        });

        $renderExpired = function (Request $request) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Sesi formulir sudah kedaluwarsa. Muat ulang halaman lalu coba lagi.',
                    'reload' => true,
                ], 419);
            }

            $fallback = url()->previous();
            if (! is_string($fallback) || $fallback === '' || $fallback === $request->fullUrl()) {
                $fallback = $request->headers->get('Referer') ?: url('/');
            }

            return redirect()
                ->to($fallback)
                ->withInput($request->except(['_token', 'password', 'password_confirmation']))
                ->with('error', 'Sesi halaman sudah kedaluwarsa. Silakan coba lagi — formulir sudah diperbarui.');
        };

        $exceptions->renderable(function (TokenMismatchException $e, Request $request) use ($renderExpired) {
            return $renderExpired($request);
        });

        $exceptions->renderable(function (HttpException $e, Request $request) use ($renderExpired) {
            if ($e->getStatusCode() !== 419) {
                return null;
            }

            return $renderExpired($request);
        });
    })->create();
