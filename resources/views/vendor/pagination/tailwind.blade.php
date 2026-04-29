@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="vx-pagination-wrap">
        <div class="sm:hidden">
            <div class="vx-pagination-mobile">
                @if ($paginator->onFirstPage())
                    <span class="vx-page-btn vx-page-btn-disabled">{{ ui_phrase('Previous') }}</span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="vx-page-btn">Previous</a>
                @endif

                <span class="vx-page-mobile-label">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>

                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="vx-page-btn">Next</a>
                @else
                    <span class="vx-page-btn vx-page-btn-disabled">{{ ui_phrase('Next') }}</span>
                @endif
            </div>
        </div>

        <div class="hidden sm:flex sm:items-center sm:justify-between sm:gap-3">
            <div class="vx-page-meta">
                <span>{{ ui_phrase('Showing') }}</span>
                <span class="vx-page-meta-value">{{ $paginator->firstItem() }}</span>
                <span>{{ ui_phrase('To') }}</span>
                <span class="vx-page-meta-value">{{ $paginator->lastItem() }}</span>
                <span>{{ ui_phrase('of') }}</span>
                <span class="vx-page-meta-value">{{ $paginator->total() }}</span>
            </div>

            <div class="vx-page-list" aria-label="Pagination">
                @if ($paginator->onFirstPage())
                    <span class="vx-page-icon vx-page-btn-disabled" aria-hidden="true">&lsaquo;</span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="vx-page-icon" aria-label="Previous page">&lsaquo;</a>
                @endif

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="vx-page-ellipsis">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="vx-page-number vx-page-number-active">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="vx-page-number" aria-label="Go to page {{ $page }}">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="vx-page-icon" aria-label="Next page">&rsaquo;</a>
                @else
                    <span class="vx-page-icon vx-page-btn-disabled" aria-hidden="true">&rsaquo;</span>
                @endif
            </div>
        </div>
    </nav>
@endif
