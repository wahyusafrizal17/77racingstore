<?php

namespace App\Exports\Speedshop;

use App\Exports\Concerns\WithExcelHeaderStyle;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PelangganSpeedshopTemplateExport implements FromArray, ShouldAutoSize, WithEvents, WithHeadings
{
    use WithExcelHeaderStyle;

    public function array(): array
    {
        return [
            ['Pelanggan Contoh', '081234567890', 'Jl. Contoh No. 123'],
        ];
    }

    public function headings(): array
    {
        return ['Nama', 'No HP', 'Alamat'];
    }
}
