<?php

namespace App\Exports\Speedshop;

use App\Exports\Concerns\WithExcelHeaderStyle;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LaporanPenjualanPartOliExport implements FromCollection, WithHeadings, WithMapping, WithEvents, ShouldAutoSize
{
    use WithExcelHeaderStyle;
    public function __construct(protected array $filters = [])
    {
    }

    public function collection()
    {
        $warehouseId = auth()->user()->activeWarehouseId();
        $dari = ! empty($this->filters['dari']) ? Carbon::parse($this->filters['dari'])->startOfDay() : now()->startOfMonth();
        $sampai = ! empty($this->filters['sampai']) ? Carbon::parse($this->filters['sampai'])->endOfDay() : now()->endOfDay();

        $items = DB::table('transaksi_penjualan_items')
            ->join('transaksi_penjualan', 'transaksi_penjualan_items.transaksi_penjualan_id', '=', 'transaksi_penjualan.id')
            ->join('products', 'transaksi_penjualan_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->when($warehouseId, fn ($q) => $q->where('transaksi_penjualan.warehouse_id', $warehouseId))
            ->whereBetween('transaksi_penjualan.created_at', [$dari, $sampai])
            ->selectRaw('products.id, products.kode_produk, products.nama_produk, categories.nama as kategori, SUM(transaksi_penjualan_items.qty) as total_qty, SUM(transaksi_penjualan_items.qty * transaksi_penjualan_items.harga_satuan) as total_nilai')
            ->groupBy('products.id', 'products.kode_produk', 'products.nama_produk', 'categories.nama')
            ->get();

        $soItems = DB::table('service_order_products')
            ->join('service_orders', 'service_order_products.service_order_id', '=', 'service_orders.id')
            ->join('products', 'service_order_products.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->when($warehouseId, fn ($q) => $q->where('service_orders.warehouse_id', $warehouseId))
            ->whereBetween('service_orders.tanggal_masuk', [$dari, $sampai])
            ->whereIn('service_orders.status', ['proses', 'selesai'])
            ->selectRaw('products.id, products.kode_produk, products.nama_produk, categories.nama as kategori, SUM(service_order_products.qty) as total_qty, SUM(service_order_products.qty * service_order_products.harga) as total_nilai')
            ->groupBy('products.id', 'products.kode_produk', 'products.nama_produk', 'categories.nama')
            ->get();

        $byProduct = [];
        foreach ($items as $i) {
            $key = $i->id;
            if (! isset($byProduct[$key])) {
                $byProduct[$key] = (object) ['kode_produk' => $i->kode_produk, 'nama_produk' => $i->nama_produk, 'kategori' => $i->kategori ?? '-', 'total_qty' => 0, 'total_nilai' => 0];
            }
            $byProduct[$key]->total_qty += $i->total_qty;
            $byProduct[$key]->total_nilai += $i->total_nilai;
        }
        foreach ($soItems as $i) {
            $key = $i->id;
            if (! isset($byProduct[$key])) {
                $byProduct[$key] = (object) ['kode_produk' => $i->kode_produk, 'nama_produk' => $i->nama_produk, 'kategori' => $i->kategori ?? '-', 'total_qty' => 0, 'total_nilai' => 0];
            }
            $byProduct[$key]->total_qty += $i->total_qty;
            $byProduct[$key]->total_nilai += $i->total_nilai;
        }

        return collect($byProduct)->sortByDesc('total_qty')->values();
    }

    public function headings(): array
    {
        return ['Kode Produk', 'Nama Produk', 'Kategori', 'Total Qty', 'Total Nilai (Rp)'];
    }

    public function map($row): array
    {
        return [$row->kode_produk, $row->nama_produk, $row->kategori, $row->total_qty, number_format($row->total_nilai, 0, ',', '.')];
    }
}
