<?php

namespace App\Exports\Admin;

use App\Exports\Concerns\WithExcelHeaderStyle;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RolesExport implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithMapping
{
    use WithExcelHeaderStyle;

    /** @param Collection<int, array{section: string, section_label: string, role: string, karyawan_count: int, in_catalog: bool}> $rows */
    public function __construct(private Collection $rows)
    {
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Divisi',
            'Role',
            'Jumlah Karyawan',
            'Status',
        ];
    }

    /** @param array{section: string, section_label: string, role: string, karyawan_count: int, in_catalog: bool} $row */
    public function map($row): array
    {
        return [
            $row['section_label'],
            $row['role'],
            $row['karyawan_count'],
            $row['in_catalog'] ? 'Aktif' : 'Legacy / di luar katalog',
        ];
    }
}
