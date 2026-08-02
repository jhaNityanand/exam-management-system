{{--
  Shared listing chrome (hero toolbar + filter modal + AJAX list).
  Expected vars:
    $listingEndpoint, $listingModalId, $listingTitle, $listingHeading, $listingLead
    $listingItems, $listingCard, $listingCardKey, $listingEmptyTitle, $listingEmptyMessage
    $listingResetUrl, $listingBreadcrumbs (optional), $listingHeroExtra (optional HTML)
    $listingFilters (callable/closure not possible) — include extra fields via $listingExtraFields view
    $activeFilterCount
    $adPage (optional catalog page key for advertisement rails/slots)
--}}
@php
    $activeFilterCount = $activeFilterCount ?? 0;
    $listingGridClass = $listingGridClass ?? 'et-grid et-grid--3';
    $listingSkeletonCount = $listingSkeletonCount ?? 6;
    $adPage = $adPage ?? null;
@endphp

@if($adPage)
<x-ad-layout :page="$adPage">
@endif
<div class="et-listing et-listing--stack" data-listing data-endpoint="{{ $listingEndpoint }}">
    <div class="et-page-hero et-page-hero--listing">
        <div class="et-container">
            @if(! empty($listingBreadcrumbs))
                @include('frontend.partials.breadcrumbs', ['breadcrumbs' => $listingBreadcrumbs])
            @endif
            <div class="et-page-hero__row">
                <div class="et-page-hero__copy">
                    {!! $listingHeroPrefix ?? '' !!}
                    <h1>{{ $listingHeading }}</h1>
                    @if(! empty($listingLead))
                        <p>{{ $listingLead }}</p>
                    @endif
                    {!! $listingHeroSuffix ?? '' !!}
                </div>
                <div class="et-filter-toolbar">
                    {!! $listingToolbarExtra ?? '' !!}
                    <button type="button" class="et-btn et-btn--soft et-filter-trigger" data-filter-open aria-haspopup="dialog" aria-controls="{{ $listingModalId }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M4 5h16M7 12h10M10 19h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <span>Filters</span>
                        <span class="et-filter-trigger__count" data-filter-count @if($activeFilterCount < 1) hidden @endif>{{ $activeFilterCount }}</span>
                    </button>
                    <button type="button" class="et-btn et-btn--ghost et-filter-reset" data-filters-reset @if($activeFilterCount < 1) hidden @endif>Reset</button>
                </div>
            </div>
        </div>
    </div>

    @if($adPage)
        <x-ad-slot :page="$adPage" position="after_filters" />
    @endif

    <div class="et-container et-section">
        <div class="et-filter-modal" id="{{ $listingModalId }}" data-filter-modal hidden>
            <div class="et-filter-modal__backdrop" data-filter-close tabindex="-1"></div>
            <div class="et-filter-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="{{ $listingModalId }}-title">
                <div class="et-filter-modal__head">
                    <h2 id="{{ $listingModalId }}-title">{{ $listingTitle }}</h2>
                    <button type="button" class="et-icon-btn" data-filter-close aria-label="Close filters">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </button>
                </div>

                <form
                    class="et-filters et-filters--modal"
                    method="get"
                    action="{{ $listingEndpoint }}"
                    data-load-more-filters
                    @if(! empty($listingDefaultSort)) data-default-sort="{{ $listingDefaultSort }}" @endif
                >
                    <label class="et-field">
                        <span class="et-field__label">{{ $listingSearchLabel ?? 'Search' }}</span>
                        <span class="et-field__control">
                            <svg class="et-field__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/>
                                <path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                            <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ $listingSearchPlaceholder ?? 'Search…' }}">
                        </span>
                    </label>

                    {!! $listingExtraFields ?? '' !!}

                    @unless(($listingHideSort ?? false) === true)
                        <label class="et-field">
                            <span class="et-field__label">Sort by</span>
                            <span class="et-field__control">
                                <svg class="et-field__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M8 7h12M8 12h8M8 17h4M4 7h.01M4 12h.01M4 17h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                <select name="sort" aria-label="Sort" data-placeholder="Select sort order">
                                    @foreach(($listingSortOptions ?? ['latest' => 'Latest', 'oldest' => 'Oldest', 'title' => 'Title A–Z', 'popular' => 'Most viewed']) as $val => $label)
                                        <option value="{{ $val }}" @selected(request('sort', $listingDefaultSort ?? 'latest') === $val)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </span>
                        </label>
                    @endunless

                    <div class="et-filter-modal__actions">
                        <button type="button" class="et-btn et-btn--ghost" data-filter-close>Cancel</button>
                        <button type="submit" class="et-btn et-btn--primary">Apply filters</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="et-listing__main" data-listing-main>
            <div class="et-listing__skeleton {{ $listingGridClass }}" data-listing-skeleton hidden aria-hidden="true">
                @for($i = 0; $i < $listingSkeletonCount; $i++)
                    @include('frontend.partials.skeleton-card')
                @endfor
            </div>

            <div class="et-listing__empty" data-listing-empty @if(($listingItems ?? collect())->isNotEmpty()) hidden @endif>
                @include('frontend.partials.empty-state', [
                    'title' => $listingEmptyTitle,
                    'message' => $listingEmptyMessage ?? 'Try adjusting or resetting your filters.',
                    'actionUrl' => $listingResetUrl,
                    'actionLabel' => 'Reset filters',
                ])
            </div>

            <div class="{{ $listingGridClass }}" data-load-more-list @if(($listingItems ?? collect())->isEmpty()) hidden @endif>
                @foreach($listingItems ?? [] as $item)
                    @include($listingCard, [$listingCardKey => $item])
                @endforeach
            </div>

            @if($adPage)
                <x-ad-slot :page="$adPage" position="below_items" />
            @endif

            <div data-load-more-slot>
                @include('frontend.partials.load-more', [
                    'paginator' => $listingItems,
                    'endpoint' => $listingLoadMoreEndpoint ?? ($listingEndpoint.(request()->getQueryString() ? '?'.request()->getQueryString() : '')),
                ])
            </div>

            @if($adPage)
                <x-ad-slot :page="$adPage" position="after_content" />
            @endif
        </div>
    </div>
</div>
@if($adPage)
</x-ad-layout>
@endif
