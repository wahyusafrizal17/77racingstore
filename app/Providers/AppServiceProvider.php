<?php

namespace App\Providers;

use App\Models\SpeedshopFinanceLedger;
use App\Models\User;
use App\Observers\SpeedshopFinanceLedgerObserver;
use App\Support\AppNotificationCategory;
use App\Support\NavbarNotifications;
use App\Support\SpeedshopPersetujuanPendingCounts;
use App\Support\SpeedshopVerifikasiPendingCounts;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('partials.warehouse.pagination');

        SpeedshopFinanceLedger::observe(SpeedshopFinanceLedgerObserver::class);

        View::composer([
            'layouts.speedshop',
            'layouts.admin',
        ], function ($view): void {
            $user = auth()->user();
            if (! $user instanceof User) {
                $view->with([
                    'financeAuditViewOnly' => false,
                    'navbarNotificationGroups' => [],
                    'navbarNotificationCategories' => [],
                    'speedshopPersetujuanPending' => [
                        'diskon_work_order' => 0,
                        'pembelian_part_luar' => 0,
                        'biaya_operasional' => 0,
                        'total' => 0,
                    ],
                    'speedshopVerifikasiPending' => SpeedshopVerifikasiPendingCounts::empty(),
                ]);

                return;
            }

            $view->with([
                'financeAuditViewOnly' => $user->isModifikasiOriHeadAudit(),
                'navbarNotificationGroups' => NavbarNotifications::groupedPayloadFor($user),
                'navbarNotificationCategories' => AppNotificationCategory::definitionsForUser($user),
                'speedshopPersetujuanPending' => SpeedshopPersetujuanPendingCounts::forUser($user),
                'speedshopVerifikasiPending' => SpeedshopVerifikasiPendingCounts::forUser($user),
            ]);
        });
    }
}
