@php
    $hasMore = method_exists($paginator, 'hasMorePages') && $paginator->hasMorePages();
    $nextPage = $hasMore ? ($paginator->currentPage() + 1) : null;
    $endpoint = $endpoint ?? url()->current();
    $total = method_exists($paginator, 'total') ? (int) $paginator->total() : 0;
@endphp
@if($hasMore)
    <div class="et-load-more" data-load-more
         data-endpoint="{{ $endpoint }}"
         data-page="{{ $paginator->currentPage() }}"
         data-last-page="{{ $paginator->lastPage() }}"
         data-total="{{ $total }}">
        <button type="button" class="et-btn et-btn--primary" data-load-more-btn data-next-page="{{ $nextPage }}">
            <span data-load-more-label>Load more</span>
            <span class="et-spinner et-spinner--sm" data-load-more-spinner hidden aria-hidden="true"></span>
        </button>
    </div>
@elseif($total > 0)
    <div class="et-load-more" data-load-more
         data-endpoint="{{ $endpoint }}"
         data-page="{{ $paginator->currentPage() }}"
         data-last-page="{{ $paginator->lastPage() }}"
         data-total="{{ $total }}"
         hidden>
        <button type="button" class="et-btn et-btn--primary" data-load-more-btn hidden aria-hidden="true" data-next-page="">
            <span data-load-more-label>Load more</span>
            <span class="et-spinner et-spinner--sm" data-load-more-spinner hidden aria-hidden="true"></span>
        </button>
    </div>
@endif
