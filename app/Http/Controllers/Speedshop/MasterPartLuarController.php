<?php

namespace App\Http\Controllers\Speedshop;

use App\Models\Product;
use App\Support\ProductMasterCatalog;

class MasterPartLuarController extends MasterProdukController
{
    protected function masterCatalogKind(): string
    {
        return ProductMasterCatalog::PART_LUAR;
    }

    protected function masterJenisProduk(): string
    {
        return Product::JENIS_PART_LUAR;
    }

    protected function masterIndexRouteName(): string
    {
        return 'speedshop.master.part-luar';
    }

    protected function masterBasePath(): string
    {
        return 'speedshop/master/part-luar';
    }

    protected function masterPageConfig(): array
    {
        return array_merge(parent::masterPageConfig(), [
            'hide_import_export' => false,
        ]);
    }
}
