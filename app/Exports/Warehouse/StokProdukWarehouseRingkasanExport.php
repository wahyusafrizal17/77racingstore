<?php

namespace App\Exports\Warehouse;

use App\Models\Product;
use App\Support\Warehouse\WarehouseStokRingkasanQuery;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class StokProdukWarehouseRingkasanExport extends StokProdukWarehouseExport implements WithColumnFormatting, WithStrictNullComparison
{
    /** @param  array<string, mixed>  $filters */
    public function __construct(private readonly array $filters = [])
    {
        parent::__construct(Product::query()->whereRaw('0 = 1'));
    }

    public function columnFormats(): array
    {
        return [
            'E' => '0',
            'F' => '0',
        ];
    }

    public function query()
    {
        $warehouseId = Auth::user()->activeWarehouseId();

        $query = Product::with(['category', 'brand'])
            ->where('warehouse_id', $warehouseId)
            ->where('speedshop_scope_id', 0)
            ->visibleInWarehouseMasterKatalog();

        $kode = $this->filters['kode'] ?? null;
        if (filled($kode)) {
            $query->where('kode_produk', 'like', '%'.$kode.'%');
        }

        $nama = $this->filters['nama'] ?? null;
        if (filled($nama)) {
            $query->where('nama_produk', 'like', '%'.$nama.'%');
        }

        $categoryId = $this->filters['category_id'] ?? null;
        if (filled($categoryId)) {
            $query->where('category_id', (int) $categoryId);
        }

        $brandId = $this->filters['brand_id'] ?? null;
        if (filled($brandId)) {
            $query->where('brand_id', (int) $brandId);
        }

        $search = $this->filters['search'] ?? null;
        if (
            filled($search)
            && ! filled($kode)
            && ! filled($nama)
            && ! filled($categoryId)
            && ! filled($brandId)
        ) {
            $query->where(function ($q) use ($search) {
                $q->where('kode_produk', 'like', "%{$search}%")
                    ->orWhere('nama_produk', 'like', "%{$search}%");
            });
        }

        $filterStok = $this->filters['filter_stok'] ?? null;
        if ($filterStok === 'habis') {
            $query->where('jumlah', '<=', 0);
        } elseif ($filterStok === 'bawah_10') {
            $query->where('jumlah', '<', 10);
        }

        return WarehouseStokRingkasanQuery::withStokKeluarTotal($query, $warehouseId);
    }

    public function headings(): array
    {
        return [
            'Kode Produk',
            'Nama Produk',
            'Kategori',
            'Brand',
            'Stok Sekarang',
            'Stok Keluar',
        ];
    }

    public function map($row): array
    {
        return [
            $row->kode_produk,
            $row->nama_produk,
            $row->category->nama ?? '-',
            $row->brand->nama ?? '-',
            (float) WarehouseStokRingkasanQuery::stokSekarangValue($row),
            (float) WarehouseStokRingkasanQuery::stokKeluarValue($row),
        ];
    }
}
