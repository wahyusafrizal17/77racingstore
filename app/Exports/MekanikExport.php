<?php

namespace App\Exports;

use App\Exports\Concerns\WithExcelHeaderStyle;
use App\Models\Mekanik;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MekanikExport implements FromQuery, WithHeadings, WithMapping, WithEvents, ShouldAutoSize
{
    use WithExcelHeaderStyle;

    public function __construct(private array $filters = [])
    {
    }

    public function query()
    {
        return Mekanik::query()
            ->when(! empty($this->filters['nama']), fn ($q) => $q->where('nama', 'like', '%'.$this->filters['nama'].'%'))
            ->when(! empty($this->filters['no_hp']), fn ($q) => $q->where('no_hp', 'like', '%'.$this->filters['no_hp'].'%'))
            ->when(! empty($this->filters['level']), fn ($q) => $q->where('spesialisasi', $this->filters['level']))
            ->orderBy('nama');
    }

    public function headings(): array
    {
        return [
            'Nama',
            'No HP',
            'Level',
        ];
    }

    public function map($item): array
    {
        return [
            $item->nama,
            $item->no_hp,
            $item->spesialisasi,
        ];
    }
}

