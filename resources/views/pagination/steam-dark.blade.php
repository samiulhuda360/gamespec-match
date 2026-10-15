@if ($paginator->hasPages())
    <nav class="steam-pager" role="navigation" aria-label="Pagination Navigation">

        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <span class="p disabled" aria-disabled="true">‹</span>
        @else
            <a class="p" href="{{ $paginator->previousPageUrl() }}" rel="prev">‹</a>
        @endif

        {{-- Pagination Elements --}}
        @foreach ($elements as $element)

            {{-- "Three Dots" Separator --}}
            @if (is_string($element))
                <span class="p dots">{{ $element }}</span>
            @endif

            {{-- Array Of Links --}}
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="p active" aria-current="page">{{ $page }}</span>
                    @else
                        <a class="p" href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <a class="p" href="{{ $paginator->nextPageUrl() }}" rel="next">›</a>
        @else
            <span class="p disabled" aria-disabled="true">›</span>
        @endif

    </nav>
@endif
