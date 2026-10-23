<?php

namespace App\Exports;

use App\Exports\Concerns\WithExcelHeaderStyle;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;

class MekanikTemplateExport implements FromArray, WithHeadings, WithEvents, ShouldAutoSize
{
    use WithExcelHeaderStyle;

    public function array(): array
    {
        return [];
    }

    public function headings(): array
    {
        return [
            'Nama',
            'No HP',
            'Level',
        ];
    }
}

