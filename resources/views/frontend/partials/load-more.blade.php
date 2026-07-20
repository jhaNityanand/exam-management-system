@php
    $hasMore = method_exists($paginator, 'hasMorePages') && $paginator->hasMorePages();
    $nextPage = $hasMore ? ($paginator->currentPage() + 1) : null;
    $endpoint = $endpoint ?? url()->current();
@endphp
@if($hasMore)
    <div class="et-load-more" data-load-more
         data-endpoint="{{ $endpoint }}"
         data-page="{{ $paginator->currentPage() }}"
         data-last-page="{{ $paginator->lastPage() }}"
         data-total="{{ $paginator->total() }}">
        <p class="et-load-more__status" data-load-more-status role="status" aria-live="polite">
            Showing {{ $paginator->lastItem() }} of {{ $paginator->total() }}
        </p>
        <button type="button" class="et-btn et-btn--primary et-btn--sm" data-load-more-btn data-next-page="{{ $nextPage }}">
            <span data-load-more-label>Load more</span>
            <span class="et-spinner et-spinner--sm" data-load-more-spinner hidden aria-hidden="true"></span>
        </button>
    </div>
@elseif(method_exists($paginator, 'total') && $paginator->total() > 0)
    <p class="et-load-more__status">Showing all {{ $paginator->total() }} results</p>
@endif
