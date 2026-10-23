<?php

namespace App\Exports\Speedshop;

use App\Exports\Concerns\WithExcelHeaderStyle;
use App\Support\ProductMasterCatalog;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProdukSpeedshopPriceTemplateExport implements FromArray, ShouldAutoSize, WithEvents, WithHeadings
{
    use WithExcelHeaderStyle;

    public function __construct(
        private string $catalogKind = ProductMasterCatalog::SPEEDSHOP_SUPPLIER,
    ) {}

    public function array(): array
    {
        return [];
    }

    public function headings(): array
    {
        $headings = [
            'Kode Produk',
            'Kode Supplier',
            'Nama Produk',
            'Kategori',
            'Brand',
        ];

        if (ProductMasterCatalog::isWarehouseSharedKind($this->catalogKind)) {
            $headings[] = 'HPP Speedshop';
            $headings[] = 'Harga Jual Cust';
        } else {
            $headings[] = 'HPP';
            $headings[] = 'Harga Jual';
        }

        return $headings;
    }
}
