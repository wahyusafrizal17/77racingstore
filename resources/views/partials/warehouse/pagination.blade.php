@if ($paginator->hasPages())
<nav class="ss-pagination" role="navigation" aria-label="Pagination">
    @if ($paginator->total() > 0)
    <p class="ss-pagination-summary">
        Menampilkan
        <span class="font-semibold text-gray-800">{{ $paginator->firstItem() }}</span>
        –
        <span class="font-semibold text-gray-800">{{ $paginator->lastItem() }}</span>
        dari
        <span class="font-semibold text-gray-800">{{ $paginator->total() }}</span>
        data
    </p>
    @endif

    <div class="ss-pagination-controls">
        @if ($paginator->onFirstPage())
            <span class="ss-page-btn disabled" aria-disabled="true">‹</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="ss-page-btn" rel="prev" aria-label="Halaman sebelumnya">‹</a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="ss-page-btn disabled">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="ss-page-btn active" aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="ss-page-btn">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="ss-page-btn" rel="next" aria-label="Halaman berikutnya">›</a>
        @else
            <span class="ss-page-btn disabled" aria-disabled="true">›</span>
        @endif
    </div>
</nav>
@endif
