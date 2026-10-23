<?php

namespace App\Http\Controllers\Speedshop;

use App\Support\ProductMasterCatalog;

class MasterProdukMoController extends MasterProdukController
{
    protected function masterCatalogKind(): string
    {
        return ProductMasterCatalog::MO;
    }

    protected function masterIndexRouteName(): string
    {
        return 'speedshop.master.produk-mo';
    }

    protected function masterBasePath(): string
    {
        return 'speedshop/master/produk-mo';
    }

    protected function usesBrandFolderBrowse(): bool
    {
        return true;
    }
}
