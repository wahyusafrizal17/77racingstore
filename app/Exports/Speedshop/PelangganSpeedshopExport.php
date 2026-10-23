<?php

namespace App\Exports\Speedshop;

use App\Exports\Concerns\WithExcelHeaderStyle;
use App\Models\Pelanggan;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PelangganSpeedshopExport implements FromQuery, ShouldAutoSize, WithEvents, WithHeadings, WithMapping
{
    use WithExcelHeaderStyle;

    public function __construct(private readonly array $filters = []) {}

    public function query()
    {
        $query = Pelanggan::query()->forSpeedshop();

        $nama = $this->filters['nama'] ?? null;
        if (filled($nama)) {
            $query->where('nama', 'like', '%'.$nama.'%');
        }

        $noHp = $this->filters['no_hp'] ?? null;
        if (filled($noHp)) {
            $query->where('no_hp', 'like', '%'.$noHp.'%');
        }

        $alamat = $this->filters['alamat'] ?? null;
        if (filled($alamat)) {
            $query->where('alamat', 'like', '%'.$alamat.'%');
        }

        $search = $this->filters['search'] ?? null;
        if (
            filled($search)
            && ! filled($nama)
            && ! filled($noHp)
            && ! filled($alamat)
        ) {
            $s = '%'.$search.'%';
            $query->where(function ($q) use ($s) {
                $q->where('nama', 'like', $s)
                    ->orWhere('no_hp', 'like', $s)
                    ->orWhere('alamat', 'like', $s);
            });
        }

        return $query->orderBy('nama');
    }

    public function headings(): array
    {
        return [
            'Nama',
            'No HP',
            'Alamat',
        ];
    }

    public function map($row): array
    {
        return [
            $row->nama,
            $row->no_hp ?? '-',
            $row->alamat ?? '-',
        ];
    }
}
