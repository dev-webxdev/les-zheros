@if ($paginator->hasPages())
    <nav class="admin-pagination admin-pagination--pager" aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <span class="admin-pagination__arrow is-disabled" aria-hidden="true"><i class="fa-solid fa-angles-left"></i></span>
            <span class="admin-pagination__arrow is-disabled" aria-hidden="true"><i class="fa-solid fa-angle-left"></i></span>
        @else
            <a class="admin-pagination__arrow" href="{{ $paginator->url(1) }}" aria-label="Premiere page"><i class="fa-solid fa-angles-left"></i></a>
            <a class="admin-pagination__arrow" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Page precedente"><i class="fa-solid fa-angle-left"></i></a>
        @endif

        <span class="admin-pagination__current">{{ $paginator->currentPage() }}</span>
        <span class="admin-pagination__meta">sur {{ $paginator->lastPage() }}</span>

        @if ($paginator->hasMorePages())
            <a class="admin-pagination__arrow" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Page suivante"><i class="fa-solid fa-angle-right"></i></a>
            <a class="admin-pagination__arrow" href="{{ $paginator->url($paginator->lastPage()) }}" aria-label="Derniere page"><i class="fa-solid fa-angles-right"></i></a>
        @else
            <span class="admin-pagination__arrow is-disabled" aria-hidden="true"><i class="fa-solid fa-angle-right"></i></span>
            <span class="admin-pagination__arrow is-disabled" aria-hidden="true"><i class="fa-solid fa-angles-right"></i></span>
        @endif
    </nav>
@endif
