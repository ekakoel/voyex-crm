@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="vx-pagination-wrap">
        <div class="vx-pagination-mobile sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="vx-page-btn vx-page-btn-disabled">{{ __('Previous') }}</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="vx-page-btn">Previous</a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="vx-page-btn">Next</a>
            @else
                <span class="vx-page-btn vx-page-btn-disabled">{{ __('Next') }}</span>
            @endif
        </div>

        <div class="hidden sm:flex sm:justify-end">
            <div class="vx-page-list" aria-label="Pagination">
                @if ($paginator->onFirstPage())
                    <span class="vx-page-btn vx-page-btn-disabled">{{ __('Previous') }}</span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="vx-page-btn">Previous</a>
                @endif

                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="vx-page-btn">Next</a>
                @else
                    <span class="vx-page-btn vx-page-btn-disabled">{{ __('Next') }}</span>
                @endif
            </div>
        </div>
    </nav>
@endif
