<?php

namespace App\Observers;

use App\Models\SpeedshopFinanceLedger;
use App\Services\WarehouseJurnalAutomationService;

class SpeedshopFinanceLedgerObserver
{
    public function created(SpeedshopFinanceLedger $ledger): void
    {
        app(WarehouseJurnalAutomationService::class)->syncFromLedger($ledger);
    }

    public function deleted(SpeedshopFinanceLedger $ledger): void
    {
        app(WarehouseJurnalAutomationService::class)->deleteForLedger($ledger);
    }
}
