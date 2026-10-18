@if ($paginator->hasPages())
<nav class="pagination" aria-label="Navigasi halaman">
    @if ($paginator->onFirstPage())
        <span class="is-disabled" aria-hidden="true">‹</span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Sebelumnya">‹</a>
    @endif
    @foreach ($elements as $element)
        @if (is_string($element))<span class="is-disabled">{{ $element }}</span>@endif
        @if (is_array($element))
            @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                    <span aria-current="page">{{ $page }}</span>
                @else
                    <a href="{{ $url }}">{{ $page }}</a>
                @endif
            @endforeach
        @endif
    @endforeach
    @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Berikutnya">›</a>
    @else
        <span class="is-disabled" aria-hidden="true">›</span>
    @endif
</nav>
@endif
