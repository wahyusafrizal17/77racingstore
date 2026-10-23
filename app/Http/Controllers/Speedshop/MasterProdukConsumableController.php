<?php

namespace App\Http\Controllers\Speedshop;

use App\Support\ProductMasterCatalog;

class MasterProdukConsumableController extends MasterProdukController
{
    protected function masterCatalogKind(): string
    {
        return ProductMasterCatalog::CONSUMABLE;
    }

    protected function masterIndexRouteName(): string
    {
        return 'speedshop.master.consumable';
    }

    protected function masterBasePath(): string
    {
        return 'speedshop/master/consumable';
    }
}
