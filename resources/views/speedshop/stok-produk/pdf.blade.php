<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Stok {{ $stokPage['title'] }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111827; margin: 24px; }
        .title { font-size: 16px; font-weight: bold; margin-bottom: 4px; }
        .meta { font-size: 10px; color: #4b5563; margin-bottom: 14px; line-height: 1.5; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #d1d5db; padding: 5px 6px; vertical-align: top; word-wrap: break-word; }
        th { background: #f3f4f6; text-align: left; font-size: 9px; text-transform: uppercase; }
        .center { text-align: center; }
        .right { text-align: right; }
        .mono { font-family: DejaVu Sans Mono, monospace; }
        .muted { color: #6b7280; }
    </style>
</head>
<body>
@php
    use App\Support\ProductMasterCatalog;

    $isMoCatalog = ProductMasterCatalog::isWarehouseSharedKind($stokPage['catalogKind'] ?? ProductMasterCatalog::MO);
    $f = $filters ?? [];
    $filterParts = [];
    if (! empty($f['kode'])) {
        $filterParts[] = 'Kode: '.$f['kode'];
    }
    if (! empty($f['nama'])) {
        $filterParts[] = 'Nama: '.$f['nama'];
    }
    if (! empty($f['category_id'])) {
        $filterParts[] = 'Kategori ID: '.$f['category_id'];
    }
    if (! empty($f['brand_id'])) {
        $filterParts[] = 'Brand ID: '.$f['brand_id'];
    }
    if (! empty($f['search'])) {
        $filterParts[] = 'Search: '.$f['search'];
    }
    if (! empty($f['terlaris'])) {
        $filterParts[] = 'Produk terlaris';
    }
@endphp
    <div class="title">Stok {{ $stokPage['title'] }}</div>
    <div class="meta">
        Dicetak: {{ ($printedAt ?? now())->format('d-m-Y H:i') }}
        @if($filterParts)
            <br>Filter: {{ implode(' · ', $filterParts) }}
        @endif
        <br>Total: {{ number_format($items->count()) }} produk
    </div>

    <table>
        <thead>
            <tr>
                <th class="center" style="width: 28px;">No</th>
                <th style="width: 78px;">Kode</th>
                <th style="width: 62px;">Kode Sup.</th>
                <th>Nama Produk</th>
                <th style="width: 72px;">Kategori</th>
                <th style="width: 62px;">Brand</th>
                @if($isMoCatalog)
                <th class="right" style="width: 72px;">HPP Speedshop</th>
                <th class="right" style="width: 72px;">Harga Jual Cust</th>
                @else
                <th class="right" style="width: 68px;">HPP</th>
                <th class="right" style="width: 72px;">Harga Jual</th>
                @endif
                <th class="center" style="width: 42px;">Stok</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $i => $p)
                @php $stok = ($speedshopStockMap[$p->id] ?? null)?->jumlah ?? 0; @endphp
                <tr>
                    <td class="center muted">{{ $i + 1 }}</td>
                    <td class="mono">{{ $p->kode_produk }}</td>
                    <td>{{ $p->kode_supplier ?: '-' }}</td>
                    <td>{{ $p->nama_produk }}</td>
                    <td>{{ $p->category->nama ?? '-' }}</td>
                    <td>{{ $p->brand->nama ?? '-' }}</td>
                    @if($isMoCatalog)
                    <td class="right">{{ number_format((float) ($p->harga_jual_speedshop ?? $p->harga_jual ?? 0), 0, ',', '.') }}</td>
                    <td class="right">{{ number_format((float) ($p->harga_eceran_terendah ?? 0), 0, ',', '.') }}</td>
                    @else
                    <td class="right">{{ number_format((float) ($p->hpp ?? 0), 0, ',', '.') }}</td>
                    <td class="right">{{ number_format((float) ($p->harga_jual_speedshop ?? $p->harga_jual ?? 0), 0, ',', '.') }}</td>
                    @endif
                    <td class="center">{{ number_format((int) $stok) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="center muted">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
