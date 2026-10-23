<?php

namespace App\Exports;

use App\Exports\Concerns\WithExcelHeaderStyle;
use App\Models\Brand;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class BrandExport implements FromQuery, ShouldAutoSize, WithEvents, WithHeadings, WithMapping
{
    use WithExcelHeaderStyle;

    public function __construct(private array $filters = []) {}

    public function query()
    {
        $query = Brand::query();

        if (! empty($this->filters['nama'])) {
            $query->where('nama', 'like', '%'.$this->filters['nama'].'%');
        }

        return $query->orderBy('nama');
    }

    public function headings(): array
    {
        return ['Nama Brand'];
    }

    public function map($item): array
    {
        return [$item->nama];
    }
}
