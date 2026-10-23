<?php

namespace App\Http\Controllers\Speedshop;

use App\Support\ProductMasterCatalog;

class MasterProdukMerchandiseController extends MasterProdukController
{
    protected function masterCatalogKind(): string
    {
        return ProductMasterCatalog::MERCHANDISE;
    }

    protected function masterIndexRouteName(): string
    {
        return 'speedshop.master.merchandise';
    }

    protected function masterBasePath(): string
    {
        return 'speedshop/master/merchandise';
    }
}
