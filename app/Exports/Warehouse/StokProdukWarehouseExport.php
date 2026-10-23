<?php

namespace App\Exports\Warehouse;

use App\Exports\Concerns\WithExcelHeaderStyle;
use App\Support\Warehouse\WarehouseInventoriProductQuery;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class StokProdukWarehouseExport implements FromQuery, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithStrictNullComparison
{
    use WithExcelHeaderStyle;

    public function __construct(
        private readonly Builder $query,
        private readonly bool $usesOutletStock = false,
    ) {}

    public function query()
    {
        return clone $this->query;
    }

    public function headings(): array
    {
        return [
            'Kode Produk',
            'Nama Produk',
            'Kategori',
            'Brand',
            'Stok',
        ];
    }

    public function map($row): array
    {
        return [
            $row->kode_produk,
            $row->nama_produk,
            $row->category->nama ?? '-',
            $row->brand->nama ?? '-',
            (float) max(0, WarehouseInventoriProductQuery::stockValue($row, $this->usesOutletStock)),
        ];
    }
}
