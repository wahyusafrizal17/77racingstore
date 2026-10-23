<?php

namespace App\Exports;

use App\Exports\Concerns\WithExcelHeaderStyle;
use App\Models\JasaServis;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class JasaServisExport implements FromQuery, WithHeadings, WithMapping, WithEvents, ShouldAutoSize
{
    use WithExcelHeaderStyle;

    public function __construct(private array $filters = [])
    {
    }

    public function query()
    {
        $query = JasaServis::query();

        if (! empty($this->filters['nama'])) {
            $query->where('nama', 'like', '%'.$this->filters['nama'].'%');
        }

        if (isset($this->filters['biaya']) && $this->filters['biaya'] !== '') {
            $query->where('biaya', (int) $this->filters['biaya']);
        }

        return $query->orderBy('nama');
    }

    public function headings(): array
    {
        return [
            'Nama Jasa Servis',
            'Biaya',
        ];
    }

    public function map($item): array
    {
        return [
            $item->nama,
            $item->biaya,
        ];
    }
}
