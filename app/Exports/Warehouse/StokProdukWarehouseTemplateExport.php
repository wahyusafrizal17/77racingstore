<?php

namespace App\Exports\Warehouse;

use App\Exports\Concerns\WithExcelHeaderStyle;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StokProdukWarehouseTemplateExport implements FromArray, ShouldAutoSize, WithEvents, WithHeadings
{
    use WithExcelHeaderStyle;

    public function array(): array
    {
        return [];
    }

    public function headings(): array
    {
        return [
            'Kode Produk',
            'Stok',
        ];
    }
}
