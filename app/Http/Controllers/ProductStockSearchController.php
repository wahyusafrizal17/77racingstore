<?php

namespace App\Http\Controllers;

use App\Models\Speedshop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductStockSearchController extends Controller
{
    /**
     * Pencarian stok produk di semua warehouse/speedshop (untuk semua akun).
     * Server-side pagination: 20 data per halaman.
     */
    public function __invoke(Request $request)
    {
        $kode = trim((string) $request->get('kode', ''));
        $nama = trim((string) $request->get('nama', ''));
        $lokasiKey = trim((string) $request->get('lokasi', ''));
        $perPage = min(max((int) $request->get('per_page', 20), 1), 100);
        $page = max((int) $request->get('page', 1), 1);

        $warehouseQ = DB::table('products')
            ->leftJoin('warehouses', 'warehouses.id', '=', 'products.warehouse_id')
            ->select([
                DB::raw("CONCAT('warehouse:', COALESCE(products.warehouse_id, 0)) as lokasi_key"),
                DB::raw("'warehouse' as lokasi_type"),
                DB::raw('products.warehouse_id as lokasi_id'),
                DB::raw("CONCAT('Gudang ', COALESCE(warehouses.nama, '-')) as lokasi_nama"),
                'products.id as product_id',
                'products.kode_produk as kode_produk',
                'products.nama_produk as nama_produk',
                DB::raw('COALESCE(products.jumlah, 0) as jumlah'),
            ]);

        $speedshopQ = DB::table('speedshop_stock')
            ->join('products', 'products.id', '=', 'speedshop_stock.product_id')
            ->join('speedshops', 'speedshops.id', '=', 'speedshop_stock.speedshop_id')
            ->select([
                DB::raw("CONCAT('speedshop:', speedshop_stock.speedshop_id) as lokasi_key"),
                DB::raw("'speedshop' as lokasi_type"),
                DB::raw('speedshop_stock.speedshop_id as lokasi_id'),
                DB::raw("CONCAT('Speedshop ', speedshops.nama) as lokasi_nama"),
                'products.id as product_id',
                'products.kode_produk as kode_produk',
                'products.nama_produk as nama_produk',
                DB::raw('COALESCE(speedshop_stock.jumlah, 0) as jumlah'),
            ]);

        $union = $warehouseQ->unionAll($speedshopQ);

        $query = DB::query()
            ->fromSub($union, 't')
            ->when($kode !== '', fn ($q) => $q->where('t.kode_produk', 'like', "%{$kode}%"))
            ->when($nama !== '', fn ($q) => $q->where('t.nama_produk', 'like', "%{$nama}%"))
            ->when($lokasiKey !== '', fn ($q) => $q->where('t.lokasi_key', $lokasiKey))
            ->orderBy('t.lokasi_type')
            ->orderBy('t.lokasi_nama')
            ->orderBy('t.kode_produk');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $items = $paginator->getCollection()->map(fn ($r) => [
            'id' => (string) ($r->lokasi_key ?? '').':'.(string) ($r->product_id ?? ''),
            'product_id' => (int) ($r->product_id ?? 0),
            'kode_produk' => (string) ($r->kode_produk ?? ''),
            'nama_produk' => (string) ($r->nama_produk ?? ''),
            'jumlah' => (int) ($r->jumlah ?? 0),
            'lokasi_key' => (string) ($r->lokasi_key ?? ''),
            'lokasi_type' => (string) ($r->lokasi_type ?? ''),
            'lokasi_id' => $r->lokasi_id !== null ? (int) $r->lokasi_id : null,
            'lokasi_nama' => (string) ($r->lokasi_nama ?? '-'),
        ])->values();

        $locations = [
            ...Speedshop::query()->orderBy('nama')->get(['id', 'nama'])->map(fn ($s) => [
                'key' => 'speedshop:'.$s->id,
                'label' => 'Speedshop '.$s->nama,
            ])->values()->all(),
        ];

        return response()->json([
            'products' => $items,
            'locations' => $locations,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }
}
