<?php

namespace App\Exports;

use App\Exports\Concerns\WithExcelHeaderStyle;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SupplierTemplateExport implements FromArray, ShouldAutoSize, WithEvents, WithHeadings
{
    use WithExcelHeaderStyle;

    public function array(): array
    {
        return [
            [
                'ABC',
                'Supplier Contoh',
                '081234567890',
                'supplier@contoh.com',
                'Bandung',
                'Jl. Contoh No. 123',
                'BCA',
                'Supplier Contoh',
                '1234567890',
            ],
        ];
    }

    public function headings(): array
    {
        return SupplierExcelColumns::headings();
    }
}
