@extends('frontend.layouts.app')

@php
    $seo = [
        'title' => 'Authors',
        'description' => 'Meet the mentors and writers who publish exams, blogs, and news on '.($siteBrand['name'] ?? 'Examtube').'.',
        'image_type' => 'profile',
        'breadcrumbs' => [
            ['label' => 'Home', 'url' => route('home')],
            ['label' => 'Authors'],
        ],
    ];

    $activeFilterCount = collect([
        request('search'),
        request('sort') && request('sort') !== 'name' ? request('sort') : null,
    ])->filter(fn ($v) => filled($v))->count();
@endphp

@section('content')
<x-ad-layout page="authors">
    <div class="et-listing et-listing--stack" data-listing data-endpoint="{{ route('frontend.authors.index') }}">
        <div class="et-page-hero et-page-hero--listing et-page-hero--authors">
            <div class="et-container">
                @include('frontend.partials.breadcrumbs', ['breadcrumbs' => $seo['breadcrumbs']])
                <div class="et-page-hero__row">
                    <div class="et-page-hero__copy">
                        <p class="et-eyebrow">Community</p>
                        <h1>Meet our authors</h1>
                        <p>Discover the mentors and writers behind exams, blogs, and learning updates on {{ $siteBrand['name'] ?? 'Examtube' }}.</p>
                    </div>
                    <div class="et-filter-toolbar">
                        <button type="button" class="et-btn et-btn--soft et-filter-trigger" data-filter-open aria-haspopup="dialog" aria-controls="et-author-filter-modal">
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

        <div class="et-container et-section et-authors-section">
            <div class="et-filter-modal" id="et-author-filter-modal" data-filter-modal hidden>
                <div class="et-filter-modal__backdrop" data-filter-close tabindex="-1"></div>
                <div class="et-filter-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="et-author-filter-title">
                    <div class="et-filter-modal__head">
                        <h2 id="et-author-filter-title">Filter authors</h2>
                        <button type="button" class="et-icon-btn" data-filter-close aria-label="Close filters">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </button>
                    </div>

                    <form class="et-filters et-filters--modal" method="get" action="{{ route('frontend.authors.index') }}" data-load-more-filters data-default-sort="name">
                        <label class="et-field">
                            <span class="et-field__label">Search author</span>
                            <span class="et-field__control">
                                <svg class="et-field__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/>
                                    <path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                <input type="search" name="search" value="{{ request('search') }}" placeholder="Search by name…">
                            </span>
                        </label>

                        <label class="et-field">
                            <span class="et-field__label">Sort by</span>
                            <span class="et-field__control">
                                <svg class="et-field__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M8 7h12M8 12h8M8 17h4M4 7h.01M4 12h.01M4 17h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                                <select name="sort" aria-label="Sort">
                                    <option value="name" @selected(request('sort', 'name') === 'name')>Name A–Z</option>
                                    <option value="name_desc" @selected(request('sort') === 'name_desc')>Name Z–A</option>
                                    <option value="latest" @selected(request('sort') === 'latest')>Newest</option>
                                </select>
                            </span>
                        </label>

                        <div class="et-filter-modal__actions">
                            <button type="button" class="et-btn et-btn--ghost" data-filter-close>Cancel</button>
                            <button type="submit" class="et-btn et-btn--primary">Apply filters</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="et-listing__main" data-listing-main>
                <div class="et-listing__skeleton et-authors-grid" data-listing-skeleton hidden aria-hidden="true">
                    @for($i = 0; $i < 6; $i++)
                        <div class="et-author-card et-author-card--skeleton" aria-hidden="true">
                            <div class="et-author-card__media et-skeleton__media"></div>
                            <div class="et-author-card__body">
                                <span class="et-skeleton__line et-skeleton__line--sm"></span>
                                <span class="et-skeleton__line et-skeleton__line--md"></span>
                                <span class="et-skeleton__line"></span>
                            </div>
                        </div>
                    @endfor
                </div>

                <div class="et-listing__empty" data-listing-empty @if(($authors ?? collect())->isNotEmpty()) hidden @endif>
                    @include('frontend.partials.empty-state', [
                        'title' => 'No authors found',
                        'message' => 'Try adjusting or resetting your filters.',
                        'actionUrl' => route('frontend.authors.index'),
                        'actionLabel' => 'Reset filters',
                    ])
                </div>

                <div class="et-authors-grid" data-load-more-list @if(($authors ?? collect())->isEmpty()) hidden @endif>
                    @foreach($authors ?? [] as $author)
                        @include('frontend.components.author-card', ['author' => $author])
                    @endforeach
                </div>

                <x-ad-slot page="authors" position="below_items" />

                <div data-load-more-slot>
                    @include('frontend.partials.load-more', [
                        'paginator' => $authors,
                        'endpoint' => route('frontend.authors.index', request()->query()),
                    ])
                </div>
            </div>
        </div>
    </div>
</x-ad-layout>
@endsection
