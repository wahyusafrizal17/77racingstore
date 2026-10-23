@php
    $fmt = fn ($v) => number_format((float) $v, 0, ',', '.');
    $topProducts = collect($dashboardTopProducts ?? []);
    $topProductsLabel = match ($mode ?? 'bulanan') {
        'harian' => 'hari ini',
        'bulanan' => 'bulan ini',
        'tahunan' => 'tahun ini',
        default => 'periode terpilih',
    };
@endphp

<div @class(['top-products-block', 'top-products-empty' => $topProducts->isEmpty()])>
    <div class="top-products-hd">
        <h3>Produk Terlaris</h3>
        <p>Top 3 part {{ $topProductsLabel }}</p>
    </div>
    @if($topProducts->isNotEmpty())
    <div class="top-products-list">
        @foreach($topProducts as $index => $product)
        <div class="top-product-row">
            <div class="top-product-rank">{{ $index + 1 }}</div>
            <div class="top-product-info">
                <div class="top-product-name">{{ $product['nama'] }}</div>
                <div class="top-product-code">{{ $product['kode'] }}</div>
            </div>
            <div class="top-product-meta">
                <div class="top-product-qty">{{ $fmt($product['qty']) }} pcs</div>
                <div class="top-product-value">Rp {{ $fmt($product['nilai']) }}</div>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <p class="top-products-empty-text">Belum ada penjualan part pada periode ini.</p>
    @endif
</div>
