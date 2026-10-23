<?php

namespace App\Exports\Speedshop;

use App\Exports\Concerns\WithExcelHeaderStyle;
use App\Models\Product;
use App\Models\ServiceOrder;
use App\Support\ProductMasterCatalog;
use App\Support\Warehouse\WarehouseInventoriProductQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StokProdukSpeedshopExport implements FromQuery, ShouldAutoSize, WithEvents, WithHeadings, WithMapping
{
    use WithExcelHeaderStyle;

    /**
     * @param  list<int>  $filterSpeedshopIds
     */
    public function __construct(
        private array $filters = [],
        private array $filterSpeedshopIds = [],
        private ?int $singleSpeedshopId = null,
        private ?int $warehouseId = null,
        private string $catalogKind = ProductMasterCatalog::MO,
    ) {}

    public function query()
    {
        $query = Product::query()
            ->with(['category', 'brand'])
            ->when($this->warehouseId !== null, fn ($q) => $q->where('warehouse_id', $this->warehouseId));

        if ($this->singleSpeedshopId) {
            $query->forSpeedshopStokCatalog($this->singleSpeedshopId, $this->catalogKind);
            $stockSpeedshopIds = [$this->singleSpeedshopId];
        } elseif ($this->filterSpeedshopIds !== []) {
            $ids = $this->filterSpeedshopIds;
            $catalogKind = $this->catalogKind;
            $isCombinedOutlet = ! ProductMasterCatalog::isWarehouseSharedKind($catalogKind)
                && $this->singleSpeedshopId === null
                && count($ids) > 1;

            if ($isCombinedOutlet) {
                $query->forSpeedshopStokCatalogAny($ids, $catalogKind)->collapseToCanonicalKode();
            } else {
                $query->where(function ($outer) use ($ids, $catalogKind) {
                    foreach ($ids as $speedshopId) {
                        $sid = (int) $speedshopId;
                        $outer->orWhere(function ($q) use ($sid, $catalogKind) {
                            $q->forSpeedshopStokCatalog($sid, $catalogKind);
                        });
                    }
                });
            }
            $stockSpeedshopIds = $this->filterSpeedshopIds;
        } else {
            $stockSpeedshopIds = [];
        }

        if ($stockSpeedshopIds !== []) {
            WarehouseInventoriProductQuery::applyOutletStockJoin(
                $query,
                $stockSpeedshopIds,
                $this->catalogKind,
            );
        } else {
            $query->select([
                'products.*',
                DB::raw('0 as speedshop_jumlah'),
            ]);
        }

        $query->orderBy('products.nama_produk');

        if (! empty($this->filters['kode'])) {
            $query->where('products.kode_produk', 'like', '%'.$this->filters['kode'].'%');
        }

        if (! empty($this->filters['nama'])) {
            $query->where('products.nama_produk', 'like', '%'.$this->filters['nama'].'%');
        }

        if (! empty($this->filters['category_id'])) {
            $query->where('products.category_id', $this->filters['category_id']);
        }

        if (! empty($this->filters['brand_id'])) {
            $query->where('products.brand_id', $this->filters['brand_id']);
        }

        if (
            ! empty($this->filters['search'])
            && empty($this->filters['kode'])
            && empty($this->filters['nama'])
            && empty($this->filters['category_id'])
            && empty($this->filters['brand_id'])
        ) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('products.kode_produk', 'like', "%{$search}%")
                    ->orWhere('products.nama_produk', 'like', "%{$search}%");
            });
        }

        if (! empty($this->filters['terlaris']) && $stockSpeedshopIds !== []) {
            $orderIdsQuery = ServiceOrder::query()
                ->where('status', 'selesai')
                ->when(
                    Schema::hasColumn('service_orders', 'speedshop_id'),
                    function ($q) use ($stockSpeedshopIds) {
                        if (count($stockSpeedshopIds) === 1) {
                            $q->where('speedshop_id', (int) $stockSpeedshopIds[0]);
                        } else {
                            $q->whereIn('speedshop_id', $stockSpeedshopIds);
                        }
                    },
                    fn ($q) => $q->when($this->warehouseId, fn ($q2) => $q2->where('warehouse_id', $this->warehouseId)),
                )
                ->select('id');

            $salesSub = DB::table('service_order_products')
                ->whereIn('service_order_id', $orderIdsQuery)
                ->selectRaw('product_id, SUM(qty) as total_qty')
                ->groupBy('product_id');

            $query
                ->joinSub($salesSub, 'sales', 'products.id', '=', 'sales.product_id')
                ->reorder()
                ->orderByDesc('sales.total_qty')
                ->orderBy('products.nama_produk');
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

        $headings[] = 'Stok Speedshop';

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
            $base[] = (float) ($row->harga_jual_speedshop ?? $row->harga_jual ?? 0);
            $base[] = (float) ($row->harga_eceran_terendah ?? 0);
        } else {
            $base[] = (float) ($row->hpp ?? 0);
            $base[] = (float) ($row->harga_jual_speedshop ?? $row->harga_jual ?? 0);
        }

        $base[] = (int) ($row->speedshop_jumlah ?? 0);

        return $base;
    }
}
