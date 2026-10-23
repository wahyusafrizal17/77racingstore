<?php

namespace App\Exports\Speedshop;

use App\Exports\Concerns\WithExcelHeaderStyle;
use App\Models\Product;
use App\Support\ProductMasterCatalog;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProdukSpeedshopExport implements FromQuery, ShouldAutoSize, WithEvents, WithHeadings, WithMapping
{
    use WithExcelHeaderStyle;

    public function __construct(
        private array $filters = [],
        private string $catalogKind = ProductMasterCatalog::SPEEDSHOP_SUPPLIER,
    ) {}

    public function query()
    {
        $warehouseId = Auth::user()->activeWarehouseId();
        $user = Auth::user();
        $sid = $user?->activeSpeedshopId();
        $query = Product::with(['category', 'brand']);

        if (ProductMasterCatalog::isWarehouseSharedKind($this->catalogKind)) {
            $query->forWarehouseSharedCatalog($this->catalogKind, $warehouseId);
        } elseif ($user?->isSpeedshopAllScopeSelected()) {
            $ids = $user->reportSpeedshopFilterIds();
            $query->forUser()->forSpeedshopStokCatalogAny($ids, $this->catalogKind)->collapseToCanonicalKode();
        } elseif ($sid) {
            $query->forSpeedshopStokCatalog((int) $sid, $this->catalogKind);
        } else {
            $query->whereRaw('0 = 1');
        }

        $query->orderBy('nama_produk');

        if (! empty($this->filters['kode'])) {
            $query->where('kode_produk', 'like', '%'.$this->filters['kode'].'%');
        }

        if (! empty($this->filters['nama'])) {
            $query->where('nama_produk', 'like', '%'.$this->filters['nama'].'%');
        }

        if (! empty($this->filters['category_id'])) {
            $query->where('category_id', $this->filters['category_id']);
        }

        if (! empty($this->filters['unbranded'])) {
            $query->whereNull('brand_id');
        } elseif (! empty($this->filters['brand_id'])) {
            $query->where('brand_id', $this->filters['brand_id']);
        }

        if (
            ! empty($this->filters['search'])
            && empty($this->filters['kode'])
            && empty($this->filters['nama'])
            && empty($this->filters['category_id'])
            && empty($this->filters['brand_id'])
            && empty($this->filters['unbranded'])
        ) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('kode_produk', 'like', "%{$search}%")
                    ->orWhere('nama_produk', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    public function headings(): array
    {
        $headings = [
            'Kode Produk',
            'Kode Supplier',
            'Nama Produk',
            'Kategori',
            'Brand',
        ];

        if (ProductMasterCatalog::isWarehouseSharedKind($this->catalogKind)) {
            $headings[] = 'HPP Speedshop';
            $headings[] = 'Harga Jual Cust';
        } else {
            $headings[] = 'HPP';
            $headings[] = 'Harga Jual';
        }

        return $headings;
    }

    public function map($row): array
    {
        $base = [
            $row->kode_produk,
            $row->kode_supplier ?? '-',
            $row->nama_produk,
            $row->category->nama ?? '-',
            $row->brand->nama ?? '-',
        ];

        if (ProductMasterCatalog::isWarehouseSharedKind($this->catalogKind)) {
            $base[] = (float) ($row->harga_jual_speedshop ?? 0);
            $base[] = (float) ($row->harga_eceran_terendah ?? 0);
        } else {
            $base[] = (float) ($row->hpp ?? 0);
            $base[] = (float) ($row->harga_jual_speedshop ?? 0);
        }

        return $base;
    }
}
