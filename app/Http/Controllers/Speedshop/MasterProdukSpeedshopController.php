<?php

namespace App\Http\Controllers\Speedshop;

use App\Support\ProductMasterCatalog;

class MasterProdukSpeedshopController extends MasterProdukController
{
    protected function masterCatalogKind(): string
    {
        return ProductMasterCatalog::SPEEDSHOP_SUPPLIER;
    }

    protected function masterIndexRouteName(): string
    {
        return 'speedshop.master.produk-speedshop';
    }

    protected function masterBasePath(): string
    {
        return 'speedshop/master/produk-speedshop';
    }

    protected function usesBrandFolderBrowse(): bool
    {
        return true;
    }
}
