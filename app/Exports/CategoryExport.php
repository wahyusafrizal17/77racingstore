<?php

namespace App\Exports;

use App\Exports\Concerns\WithExcelHeaderStyle;
use App\Models\Category;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CategoryExport implements FromQuery, ShouldAutoSize, WithEvents, WithHeadings, WithMapping
{
    use WithExcelHeaderStyle;

    public function __construct(private array $filters = []) {}

    public function query()
    {
        $query = Category::query();

        if (! empty($this->filters['section'])) {
            $query->where('section', $this->filters['section']);
        }

        if (! empty($this->filters['nama'])) {
            $query->where('nama', 'like', '%'.$this->filters['nama'].'%');
        }

        return $query->orderBy('nama');
    }

    public function headings(): array
    {
        return ['Nama Kategori'];
    }

    public function map($item): array
    {
        return [$item->nama];
    }
}
