<div class="doc-header">
    <div class="doc-brand">
        <div class="doc-brand-logo">
            <div class="logo-mark">@include('layouts.partials.pdf-logo-img')</div>
            <span class="logo-name">77racing</span>
        </div>
        <div class="doc-sub">77racing Speedshop</div>
        <div class="doc-title-block">
            <div class="doc-title">{{ strtoupper($listTitle) }}</div>
        </div>
    </div>
    <div class="doc-meta">
        <div class="doc-inv-no">Laporan</div>
        <div class="doc-meta-row">Dicetak: <span>{{ ($printedAt ?? now())->format('d M Y H:i') }}</span></div>
        @if(!empty($metaLines))
            @foreach($metaLines as $line)
                <div class="doc-meta-row">{{ $line }}</div>
            @endforeach
        @endif
    </div>
</div>
