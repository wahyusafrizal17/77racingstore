<?php

namespace App\Http\Controllers\Speedshop;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\Product;
use App\Models\SpeedshopClaim;
use App\Models\SpeedshopStock;
use App\Models\TransaksiOffline;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ClaimController extends Controller
{
    /**
     * Sama dengan warehouse produk-cacat: status tampilan dari item claim + Transaksi Offline (claim_number).
     */
    private function resolveClaimDisplayStatus(Collection $group, ?TransaksiOffline $transaksiPengganti): string
    {
        if ($group->contains(fn ($c) => $c->status === 'pending')) {
            return 'pending';
        }
        if (! $transaksiPengganti) {
            return 'diproses';
        }
        if ($transaksiPengganti->isVerified()) {
            return 'selesai';
        }
        if ($transaksiPengganti->isRejected()) {
            return 'ditolak';
        }

        return 'dikirim';
    }

    public function index(Request $request)
    {
        $speedshopId = auth()->user()->activeSpeedshopId();
        if (! $speedshopId) {
            $claimGroups = new LengthAwarePaginator([], 0, 20);

            return view('speedshop.claim.index', compact('claimGroups'));
        }

        $all = SpeedshopClaim::with(['product', 'warehouse', 'mutasi'])
            ->where('speedshop_id', $speedshopId)
            ->latest()
            ->get();

        $grouped = $all->groupBy(function ($c) {
            return $c->claim_number ?: 'single-'.$c->id;
        });
        $groups = $grouped->values();

        $claimNumbers = $groups->map(fn ($g) => $g->first()->claim_number)->filter()->unique()->values();

        $transaksiByClaimNumber = collect();
        if ($claimNumbers->isNotEmpty()) {
            $transaksiByClaimNumber = TransaksiOffline::query()
                ->where('speedshop_id', $speedshopId)
                ->whereIn('claim_number', $claimNumbers)
                ->orderByDesc('id')
                ->get()
                ->groupBy('claim_number')
                ->map(fn ($rows) => $rows->first());
        }

        $rows = $groups->map(function ($group) use ($transaksiByClaimNumber) {
            $first = $group->first();
            $cn = $first->claim_number;
            $to = $cn ? $transaksiByClaimNumber->get($cn) : null;

            return [
                'group' => $group,
                'display' => $this->resolveClaimDisplayStatus($group, $to),
                'transaksi' => $to,
            ];
        });

        if ($request->filled('claim_number')) {
            $term = mb_strtolower((string) $request->claim_number);
            $rows = $rows->filter(function (array $row) use ($term) {
                $first = $row['group']->first();
                $cn = mb_strtolower((string) ($first->claim_number ?? ''));

                return str_contains($cn, $term);
            })->values();
        }

        if ($request->filled('tanggal')) {
            $date = (string) $request->tanggal;
            $rows = $rows->filter(function (array $row) use ($date) {
                $first = $row['group']->first();

                return $first?->created_at?->toDateString() === $date;
            })->values();
        }

        if ($request->filled('produk')) {
            $term = mb_strtolower((string) $request->produk);
            $rows = $rows->filter(function (array $row) use ($term) {
                /** @var \Illuminate\Support\Collection $group */
                $group = $row['group'];

                return $group->contains(function ($c) use ($term) {
                    $kode = mb_strtolower((string) ($c->product?->kode_produk ?? ''));
                    $nama = mb_strtolower((string) ($c->product?->nama_produk ?? ''));

                    return str_contains($kode, $term) || str_contains($nama, $term);
                });
            })->values();
        }

        if ($request->filled('mutasi')) {
            $term = mb_strtolower((string) $request->mutasi);
            $rows = $rows->filter(function (array $row) use ($term) {
                $first = $row['group']->first();
                $kode = mb_strtolower((string) ($first->mutasi?->kode ?? ''));
                $sj = mb_strtolower((string) ($first->mutasi?->nomor_surat_jalan ?? ''));

                return str_contains($kode, $term) || str_contains($sj, $term);
            })->values();
        }

        if ($request->filled('status')) {
            $st = $request->status;
            if (in_array($st, ['pending', 'diproses', 'dikirim', 'selesai', 'ditolak'], true)) {
                $rows = $rows->filter(fn (array $row) => $row['display'] === $st)->values();
            }
        }

        $perPage = 20;
        $page = (int) $request->input('page', 1);
        $total = $rows->count();
        $claimGroups = new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('speedshop.claim.index', compact('claimGroups'));
    }

    public function show(SpeedshopClaim $speedshop_claim)
    {
        $speedshopId = auth()->user()->activeSpeedshopId();
        if (! $speedshopId || $speedshop_claim->speedshop_id != $speedshopId) {
            return redirect()->route('speedshop.claim.index')->with('error', 'Unauthorized.');
        }

        $speedshop_claim->load(['product', 'warehouse', 'mutasi']);
        $claimNumber = $speedshop_claim->claim_number;

        $items = $claimNumber
            ? SpeedshopClaim::with(['product'])
                ->where('speedshop_id', $speedshopId)
                ->where('claim_number', $claimNumber)
                ->orderBy('id')
                ->get()
            : collect([$speedshop_claim]);

        $transaksiPengganti = null;
        if ($claimNumber && $speedshop_claim->warehouse_id) {
            $transaksiPengganti = TransaksiOffline::query()
                ->where('warehouse_id', $speedshop_claim->warehouse_id)
                ->where('speedshop_id', $speedshopId)
                ->where('claim_number', $claimNumber)
                ->latest('id')
                ->first();
        }

        return view('speedshop.claim.show', [
            'claim' => $speedshop_claim,
            'items' => $items,
            'transaksiPengganti' => $transaksiPengganti,
        ]);
    }

    public function create()
    {
        $speedshopId = auth()->user()->activeSpeedshopId();
        if (! $speedshopId) {
            return redirect()->route('speedshop.claim.index')
                ->with('error', 'Speedshop tidak terdeteksi.');
        }

        $speedshop = \App\Models\Speedshop::find($speedshopId);
        $warehouseId = $speedshop?->warehouse_id ?? $speedshop?->mutasi_warehouse_id;
        if (! $warehouseId) {
            return redirect()->route('speedshop.claim.index')
                ->with('error', 'Warehouse tidak terhubung ke Speedshop ini.');
        }

        // Produk yang ditampilkan = katalog cabang (via speedshop_stock), bukan gabungan semua cabang.
        $products = Product::query()
            ->where('warehouse_id', $warehouseId)
            ->forSpeedshop((int) $speedshopId)
            ->whereSpeedshopOutletCategory((int) $speedshopId)
            ->orderBy('nama_produk')
            ->get();

        $speedshopStockMap = SpeedshopStock::query()
            ->where('speedshop_id', $speedshopId)
            ->get()
            ->keyBy('product_id');

        $stocks = $products->map(fn (Product $p) => [
            'id' => $p->id,
            'kode_produk' => $p->kode_produk,
            'nama_produk' => $p->nama_produk,
            'jumlah' => (int) (($speedshopStockMap[$p->id] ?? null)?->jumlah ?? 0),
        ])->values();

        return view('speedshop.claim.create', compact('stocks', 'warehouseId'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ]);

        $speedshopId = auth()->user()->activeSpeedshopId();
        if (! $speedshopId) {
            return response()->json(['message' => 'Speedshop tidak terdeteksi.'], 403);
        }

        $claimNumber = $this->generateClaimNumber();
        $catatan = $request->catatan;

        $created = [];
        $summaryParts = [];

        foreach ($request->items as $item) {
            $productId = (int) $item['product_id'];
            $qty = (int) $item['quantity'];

            $stock = SpeedshopStock::where('speedshop_id', $speedshopId)
                ->where('product_id', $productId)
                ->first();

            if (! $stock || $stock->jumlah < $qty) {
                $product = Product::find($productId);

                return response()->json([
                    'message' => 'Stok produk '.($product->kode_produk ?? $productId).' tidak mencukupi. Tersedia: '.($stock->jumlah ?? 0).'.',
                ], 422);
            }

            $claim = SpeedshopClaim::create([
                'claim_number' => $claimNumber,
                'speedshop_id' => $speedshopId,
                'warehouse_id' => null,
                'product_id' => $productId,
                'qty' => $qty,
                'mutasi_id' => null,
                'catatan' => $catatan,
                'status' => 'pending',
            ]);
            $created[] = $claim;
            $product = Product::find($productId);
            $summaryParts[] = $product->kode_produk.' - '.$product->nama_produk.' ('.$qty.' unit)';
        }

        $speedshopName = \App\Models\Speedshop::query()->whereKey($speedshopId)->value('nama') ?? 'Speedshop';
        $totalItems = count($created);

        return response()->json([
            'success' => true,
            'message' => 'Claim berhasil diajukan ('.$totalItems.' produk) dari '.$speedshopName.'. Stok Speedshop tidak berkurang.',
            'redirect' => route('speedshop.claim.index'),
        ]);
    }

    private function generateClaimNumber(): string
    {
        $prefix = 'CLM-'.now()->format('Ymd').'-';
        $last = SpeedshopClaim::where('claim_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('claim_number');
        $seq = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
